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
            $maxAttempts = 3000;
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

                    // دوال مساعدة
                    $makeScript = function (array $segments): string {
                        return collect($segments)->map(function ($s) {
                            return "[{$s['start_timestamp']}] {$s['text']}";
                        })->implode("\n");
                    };

                    $generateVttContent = function (array $segments): string {
                        $lines = ["WEBVTT\n"];
                        foreach ($segments as $i => $s) {
                            $start = $s['start_timestamp'];
                            $end = $s['end_timestamp'] ?? $start;
                            $lines[] = $i + 1;
                            $lines[] = "{$start} --> {$end}";
                            $lines[] = $s['text'];
                            $lines[] = "";
                        }
                        return implode("\n", $lines);
                    };

                    $baseFolder = "{$video->course->teacher_id}/{$video->course_id}/{$video->id}";
                    echo "this is transcription  : \n ";
                    print_r($transcription);
                    // حفظ التفريغ الأصلي
                    if (!empty($transcription)) {
                        echo "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
                        $lang = $resultData['transcription']['language'] ?? 'ar';

                        $video->scripts()->create([
                            'language' => $lang,
                            'script_path' => $makeScript($transcription),
                        ]);

                        $fileName = "{$video->id}_{$lang}.vtt";
                        $fullPath = "{$baseFolder}/{$fileName}";
                        $vttContent = $generateVttContent($transcription);

                        Storage::disk('video_subTitle')->put($fullPath, $vttContent);

                        $video->videoSubtitles()->create([
                            'language' => $lang,
                            'path' => assetFromDisk('video_subTitle', $fullPath),
                        ]);
                    }

                    // الترجمات
                    foreach ($translations as $lang => $data) {
                        if (!empty($data['segments'])) {
                            $video->scripts()->create([
                                'language' => $lang,
                                'script_path' => $makeScript($data['segments']),
                            ]);

                            $fileName = "{$video->id}_{$lang}.vtt";
                            $fullPath = "{$baseFolder}/{$fileName}";
                            $vttContent = $generateVttContent($data['segments']);

                            Storage::disk('video_subTitle')->put($fullPath, $vttContent);

                            $video->videoSubtitles()->create([
                                'language' => $lang,
                                'path' => assetFromDisk('video_subTitle', $fullPath),
                            ]);
                        }
                    }

                    echo "✅ Script and subtitle (VTT) generation completed.\n";

                    // تجميع نصوص التفريغ
                    $transcriptText = collect($transcription)
                        ->pluck('text')
                        ->implode('\n');

                    // إرسال التفريغ إلى /process
                    $processResponse = Http::timeout(300)
                        ->withHeaders([
                            'accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ])->post("http://localhost:8020/process", [
                                'video_id' => (string) $video->id,
                                'course_id' => (string) $video->course_id,
                                'video_seq' => (int) $video->sequential_order,
                                'transcript_text' => $transcriptText,
                            ]);

                    if ($processResponse->successful()) {
                        echo "✅ Transcript sent to /process successfully.\n";

                        // 🔁 إرسال الدبلجة لكل لغة باستخدام ملفات VTT مباشرة
                        $targetLanguages = ['fr', 'en']; // يمكنك إضافة لغات أخرى هنا

                        foreach ($targetLanguages as $lang) {
                            // تحديد اسم الملف وموقعه
                            $fileName = "{$video->id}_{$lang}.vtt";
                            $fullPathInStorage = "{$baseFolder}/{$fileName}";
                            $vttPath = Storage::disk('video_subTitle')->path($fullPathInStorage);

                            if (!file_exists($vttPath)) {
                                echo "❌ VTT file not found for language: {$lang} at {$vttPath}\n";
                                continue;
                            }

                            // ✅ تحويل محتوى ملف VTT إلى transcription JSON
                            $vttContent = file_get_contents($vttPath);

                            preg_match_all('/(\d+)\s+([\d:.]+)\s+-->\s+([\d:.]+)\s+(.+?)(?=\n\d|\Z)/s', $vttContent, $matches, PREG_SET_ORDER);

                            $segments = [];
                            foreach ($matches as $match) {
                                $segments[] = [
                                    'start_timestamp' => trim($match[2]),
                                    'end_timestamp' => trim($match[3]),
                                    'text' => trim(preg_replace("/\n+/", " ", $match[4]))
                                ];
                            }

                            $transcriptionJson = json_encode($segments, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                            echo "📝 Transcription for [{$lang}]:\n";
                            echo "'{$transcriptionJson}'\n";

                            // إرسال Job الدبلجة
                            $audioAbsolutePath = public_path('uploads/' . ltrim($audioFileName, '/'));

                            dispatch(new SendDubbingJobRequest(
                                $audioAbsolutePath,
                                $transcriptionJson,
                                $lang,
                                'mp3',
                                $video->id
                            ));

                            echo "✅ SendDubbingJobRequest dispatched for language: {$lang}\n";
                        }


                    } else {
                        echo "❌ Failed to send transcript to /process.\n";
                        echo "Status Code: " . $processResponse->status() . "\n";
                        echo "Response Body: " . $processResponse->body() . "\n";
                    }

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
