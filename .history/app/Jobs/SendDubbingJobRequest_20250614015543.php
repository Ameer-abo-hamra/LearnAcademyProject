<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDubbingJobRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $transcriptionJson;
    protected $targetLanguage;
    protected $outputFormat;

    public function __construct($filePath, $transcriptionJson, $targetLanguage = 'en', $outputFormat = 'mp3')
    {
        $this->filePath = $filePath;
        $this->transcriptionJson = $transcriptionJson;
        $this->targetLanguage = $targetLanguage;
        $this->outputFormat = $outputFormat;
    }

    public function handle()
    {
        $response = Http::attach(
            'input_audio_file',
            file_get_contents($this->filePath),
            basename($this->filePath)
        )
            ->asMultipart()
            ->post('http://localhost:8089/api/v1/dubbing-jobs', [
                ['name' => 'transcription_data_str', 'contents' => $this->transcriptionJson],
                ['name' => 'target_language', 'contents' => $this->targetLanguage],
                ['name' => 'output_format', 'contents' => $this->outputFormat],
            ]);

        if (!$response->successful()) {
            echo "❌ Failed to create dubbing job\n";
            echo "Status Code: " . $response->status() . "\n";
            echo "Response Body:\n" . $response->body() . "\n";
            return;
        }

        $data = $response->json();
        $jobId = $data['job_id'] ?? null;

        if (!$jobId) {
            echo "❌ No job_id returned from API.\n";
            return;
        }

        echo "✅ Dubbing job created. Job ID: {$jobId}\n";

        // ✅ تتبع الحالة
        $status = null;
        $attempts = 0;
        $maxAttempts = 180;

        do {
            sleep(1);
            $attempts++;

            $statusResponse = Http::get("http://localhost:8089/api/v1/dubbing-jobs/{$jobId}/status");

            if ($statusResponse->failed()) {
                echo "⚠️ Failed to fetch status for job {$jobId}. Response: " . $statusResponse->body() . "\n";
                continue;
            }

            $status = $statusResponse->json('status');
            echo "Polling job status [{$jobId}]: {$status}\n";

        } while (strtolower($status) !== 'completed' && $attempts < $maxAttempts);

        // ✅ عند الاكتمال
        if (strtolower($status) === 'completed') {
            echo "✅ Dubbing job {$jobId} completed.\n";

            $result = Http::get("http://localhost:8089/api/v1/dubbing-jobs/{$jobId}/results");

            if ($result->successful()) {
                $audioBinary = $result->body();

                // إنشاء اسم مناسب للملف الجديد بجانب الفيديو
                $videoDirectory = dirname($this->filePath);
                $videoBaseName = pathinfo($this->filePath, PATHINFO_FILENAME);

                $outputFilePath = "{$videoDirectory}/{$videoBaseName}_dubbed_{$this->targetLanguage}.{$this->outputFormat}";

                // حفظ الملف
                File::put($outputFilePath, $audioBinary);

                // استخراج مسار الفيديو النسبي (للبحث عن الفيديو)
                $relativeVideoPath = str_replace(public_path('uploads') . '/', '', $this->filePath);
                echo "this is relative path : \n " .
                // محاولة جلب الفيديو الذي يحتوي على هذا المسار
                $video = Video::where('path', 'like', "%{$relativeVideoPath}")->first();

                if ($video) {
                    $relativeDubbedPath = str_replace(public_path(''), '', $outputFilePath); // لحفظ الرابط الصحيح

                    $video->audios()->create([
                        'language' => $this->targetLanguage,
                        'path' => asset($relativeDubbedPath)
                    ]);

                    echo "✅ Dubbed audio path saved in database for video ID {$video->id}\n";
                } else {
                    echo "⚠️ Could not find video in database to attach dubbed audio.\n";
                }

                echo "✅ Dubbed audio saved at: {$outputFilePath}\n";
            } else {
                echo "❌ Failed to download dubbed audio. Status: " . $result->status() . "\n";
            }
        } else {
            echo "⚠️ Dubbing job {$jobId} did not complete in time.\n";
        }
    }
}
