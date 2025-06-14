<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
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

        // ✅ Loop لتتبع الحالة
        $status = null;
        $attempts = 0;
        $maxAttempts = 180; // ⏱ 3 دقائق كحد أقصى

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

        } while ($status !== 'COMPLETED' && $attempts < $maxAttempts);

        // ✅ عند الاكتمال
        if ($status === 'COMPLETED') {
            echo "✅ Dubbing job {$jobId} completed.\n";

            // يمكنك هنا تنفيذ أي شيء بعد الاكتمال مثل جلب النتائج:
            // $result = Http::get("http://localhost:8089/api/v1/dubbing-jobs/{$jobId}/results");

        } else {
            echo "⚠️ Dubbing job {$jobId} did not complete in time.\n";
        }
    }
}
