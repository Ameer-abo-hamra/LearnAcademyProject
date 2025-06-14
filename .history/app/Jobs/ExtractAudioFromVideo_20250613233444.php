<?php

namespace App\Jobs;

use App\Events\TeacherEvent;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SendDubbingJobRequest;

class ExtractAudioFromVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $videoId;

    public function __construct($videoId)
    {
        $this->videoId = $videoId;
    }

    public function handle(): void
    {
        $video = Video::find($this->videoId);

        if (!$video) {
            echo "❌ Video not found with ID: {$this->videoId}\n";
            return;
        }

        $disk = 'teachers';
        $videoPath = ltrim(parse_url($video->path, PHP_URL_PATH), '/uploads');

        $directory = pathinfo($videoPath, PATHINFO_DIRNAME);
        $filename = pathinfo($videoPath, PATHINFO_FILENAME);
        $extension = pathinfo($videoPath, PATHINFO_EXTENSION);

        $audioFileName = "{$directory}/{$filename}_audio.mp3";
        $videoNoAudioFileName = "{$directory}/{$filename}_no_audio.{$extension}";

        try {
            // استخراج الصوت
            FFMpeg::fromDisk($disk)
                ->open($videoPath)
                ->export()
                ->toDisk($disk)
                ->inFormat(new Mp3)
                ->save($audioFileName);

            $video->audios()->create([
                "path" => assetFromDisk($disk, $audioFileName)
            ]);

            // إزالة الصوت من الفيديو
            FFMpeg::fromDisk($disk)
                ->open($videoPath)
                ->export()
                ->toDisk($disk)
                ->addFilter('-an')
                ->save($videoNoAudioFileName);

            $video->path = $videoNoAudioFileName;
            $video->save();

            // إرسال ملف MP3 إلى API
            $audioAbsolutePath = public_path('uploads/' . ltrim($audioFileName, '/'));
            $response = Http::withOptions(['timeout' => 120])
                ->withHeaders(['accept' => 'application/json'])
                ->attach(
                    'file',
                    file_get_contents($audioAbsolutePath),
                    basename($audioAbsolutePath)
                )->post('http://localhost:8002/api/v1/jobs', [
                    'target_languages' => 'ar,en,fr'
                ]);

            $jobId = $response->json('job_id');

            if (!$jobId) {
                echo "❌ Job creation failed or no job ID returned.\n";
                return;
            }

            // انتظار حتى الاكتمال
            $status = null;
            $maxAttempts = 300;
            $attempts = 0;

            do {
                sleep(1);
                $attempts++;

                $statusCheck = Http::withHeaders(['accept' => 'application/json'])
                    ->get("http://localhost:8002/api/v1/jobs/{$jobId}/status");

                $status = $statusCheck->json('status');
                echo "Polling job status #{$jobId}: {$status}\n";
            } while ($status !== 'completed' && $attempts < $maxAttempts);

            if ($status === 'completed') {
                $resultResponse = Http::withHeaders(['accept' => 'application/json'])
                    ->get("http://localhost:8002/api/v1/jobs/{$jobId}/result");

                if ($resultResponse->successful()) {
                    $resultData = $resultResponse->json();
                    $transcription = $resultData['transcription']['segments'] ?? [];
                    $translations = collect($resultData['translations'])->keyBy('language');

                    // ✅ حدد اللغة المطلوبة للدبلجة
                    $targetLanguage = 'fr'; // يمكنك تغييره لاحقًا حسب الطلب

                    // ✅ الحصول على الترجمة الخاصة باللغة المطلوبة
                    $targetSegments = $translations[$targetLanguage]['segments'] ?? null;

                    if (!$targetSegments) {
                        echo "❌ No translated segments found for language: {$targetLanguage}\n";
                        return;
                    }

                    // ✅ إنشاء transcriptionJson من الترجمة
                    $transcriptionJson = json_encode($targetSegments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                    echo "📝 This is the transcription data to be sent:\n";
                    echo $transcriptionJson . "\n";

                    // ✅ إرسال Job الدبلجة
                    dispatch(new SendDubbingJobRequest(
                        $audioAbsolutePath,
                        $transcriptionJson,
                        $targetLanguage,
                        'mp3'
                    ));

                    echo "✅ SendDubbingJobRequest dispatched successfully.\n";

                } else {
                    echo "❌ Failed to retrieve transcription result for job {$jobId}\n";
                }
            } else {
                echo "⚠️ Job #{$jobId} did not complete in time.\n";
            }

            $teacher = $video->course->teacher;
            event(new TeacherEvent("Video conversion successful. You can now see your video in multi resolutions", $teacher->id));

        } catch (\Exception $e) {
            echo "❌ FFmpeg process failed: " . $e->getMessage() . "\n";
        }
    }
}
