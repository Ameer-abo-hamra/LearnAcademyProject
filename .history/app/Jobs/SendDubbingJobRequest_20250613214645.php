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
        ECHO file_get_contents($this->filePath)
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

        if ($response->successful()) {
            $data = $response->json();
            echo "✅ Dubbing job created:\n";
            print_r($data);
        } else {
            echo "❌ Failed to create dubbing job\n";
            echo "Status Code: " . $response->status() . "\n";
            echo "Response Body:\n" . $response->body() . "\n";
        }
    }

}
