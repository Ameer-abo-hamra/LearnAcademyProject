<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Format\Audio\Mp3;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log;

class ExtractAudioFromVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $videoId;

    /**
     * Create a new job instance.
     */
    public function __construct($videoId)
    {
        $this->videoId = $videoId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $video = Video::find($this->videoId);

        if (!$video) {
            echo "❌ Video not found with ID: {$this->videoId}";
            return;
        }

        $disk = 'teachers'; // تأكد من أن هذا الـ disk معرف في config/filesystems.php
        // استخراج المسار من الرابط الكامل
        $videoPath = $video->path;

        // إزالة الجزء الخاص بالرابط وترك فقط المسار داخل مجلد التخزين
        $videoPath = ltrim(parse_url($videoPath, PHP_URL_PATH), '/uploads');

        $directory = pathinfo($videoPath, PATHINFO_DIRNAME);
        $filename = pathinfo($videoPath, PATHINFO_FILENAME);
        $extension = pathinfo($videoPath, PATHINFO_EXTENSION);

        $audioFileName = "{$directory}/{$filename}_audio.mp3";
        $videoNoAudioFileName = "{$directory}/{$filename}_no_audio.{$extension}";

        try {
            // ✅ استخراج الصوت
            FFMpeg::fromDisk($disk)
                ->open($videoPath)
                ->export()
                ->toDisk($disk)
                ->inFormat(new Mp3)
                ->save($audioFileName);
            $video->audios()->create([
                "path" => assetFromDisk($disk, $audioFileName)
            ]);
            echo ' this is the video path ' . $videoPath;
            // ✅ إزالة الصوت من الفيديو باستخدام الفلتر '-an'
            FFMpeg::fromDisk($disk)
                ->open($videoPath)
                ->export()
                ->toDisk($disk)
                ->addFilter('-an')
                ->save($videoNoAudioFileName);

            $video->path = $videoNoAudioFileName;
            $video->save();
            echo "hi man ";
            Log::info("✅ Audio extracted to: {$audioFileName}");
            Log::info("✅ Video without audio saved to: {$videoNoAudioFileName}");
            $audioAbsolutePath = public_path('uploads/' . ltrim($audioFileName, '/'));

            // ✅ إرسال الملف إلى API
            $response = Http::withHeaders([
                'accept' => 'application/json'
            ])->attach(
                    'file',
                    file_get_contents($audioAbsolutePath),
                    basename($audioAbsolutePath)
                )->post('http://localhost:8002/api/v1/jobs', [
                        'target_languages' => 'ar,en'
                    ]);

            $jobId = $response->json('job_id');

            if (!$jobId) {
                Log::error("❌ Job creation failed or no job ID returned.");
                return;
            }

            // polling status
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

                echo ("Job #{$jobId} status: {$status}");

            } while ($status !== 'completed' && $attempts < $maxAttempts);

            if ($status === 'completed') {
                Log::info("🎯 Transcription for job #{$jobId} completed successfully.");

                // استدعاء النتيجة النهائية للـ job
                $resultResponse = Http::withHeaders([
                    'accept' => 'application/json',
                    'X-API-Key' => 'your_api_key_here' // 🔐 استبدله بالمفتاح الحقيقي
                ])->get("http://localhost:8002/api/v1/jobs/{$jobId}/result");

                if ($resultResponse->successful()) {
                    $resultData = $resultResponse->json();

                    // مثال: استخراج نص التفريغ الأساسي
                    $transcription = $resultData['transcription']['segments'] ?? [];

                    // مثال: استخراج الترجمات (الإنجليزية والفرنسية مثلاً)
                    $translations = collect($resultData['translations'])->keyBy('language');

                    $englishTranslation = $translations['en']['segments'] ?? [];
                    $frenchTranslation = $translations['fr']['segments'] ?? [];

                    // ✅ نفذ منطقك هنا: كالحفظ في قاعدة البيانات
                    Log::info("📝 Original Transcription:", $transcription);
                    Log::info("🔤 EN Translation:", $englishTranslation);
                    Log::info("🔤 FR Translation:", $frenchTranslation);

                    // مثال حفظ النتيجة (إذا كان لديك جدول)
                    // $video->transcription_segments = json_encode($transcription);
                    // $video->translation_en_segments = json_encode($englishTranslation);
                    // $video->save();

                } else {
                    Log::error("❌ Failed to retrieve transcription result for job {$jobId}");
                }
            }


            echo "this is response :  /n " . $response;
        } catch (\Exception $e) {
            echo "❌ FFmpeg process failed: " . $e->getMessage();
        }
    }
}
