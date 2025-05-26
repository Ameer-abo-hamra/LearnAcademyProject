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
            $response = Http::withHeaders([
                'accept' => 'application/json'
            ])->attach(
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

                $statusCheck = Http::withHeaders([
                    'accept' => 'application/json'
                ])->get("http://localhost:8002/api/v1/jobs/{$jobId}/status");

                $status = $statusCheck->json('status');
                echo "Polling job status #{$jobId}: {$status}\n";
            } while ($status !== 'completed' && $attempts < $maxAttempts);

            if ($status === 'completed') {
                $resultResponse = Http::withHeaders([
                    'accept' => 'application/json'
                ])->get("http://localhost:8002/api/v1/jobs/{$jobId}/result");

                if ($resultResponse->successful()) {
                    $resultData = $resultResponse->json();
                    $transcription = $resultData['transcription']['segments'] ?? [];
                    $translations = collect($resultData['translations'])->keyBy('language');

                    // تحويل إلى سكريبت نصي
                    $makeScript = function (array $segments): string {
                        return collect($segments)->map(function ($s) {
                            return "[{$s['timestamp']}] {$s['text']}";
                        })->implode("\n");
                    };

                    // حفظ التفريغ الأصلي
                    if (!empty($transcription)) {
                        $video->scripts()->create([
                            'language' => $resultData['transcription']['language'] ?? 'ar',
                            'script_path' => $makeScript($transcription),
                        ]);
                    }

                    // حفظ الترجمات
                    foreach ($translations as $lang => $data) {
                        if (!empty($data['segments'])) {
                            $video->scripts()->create([
                                'language' => $lang,
                                'script_path' => $makeScript($data['segments']),
                            ]);
                        }
                    }

                    echo "✅ Script generation completed and saved.\n";
                } else {
                    echo "❌ Failed to retrieve transcription result for job {$jobId}\n";
                }
            } else {
                echo "⚠️ Job #{$jobId} did not complete in time.\n";
            }

            $teacher = $video->course->teacher;
            event(new TeacherEvent("Video conversion successful you can now see your video in multi resolutions", $teacher->id));

        } catch (\Exception $e) {
            echo "❌ FFmpeg process failed: " . $e->getMessage() . "\n";
        }
    }
}
/*
 [00:02.530] وليالي الحلم
[00:06.500] واللي تقول ما يلاقوا
[00:10.810] حطت علي
[00:13.290] واللي جوه القلب
 */
