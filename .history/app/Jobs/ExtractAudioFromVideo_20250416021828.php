<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use FFMpeg;
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

        $relativePath = "uploads/" . $video->path;
        $disk = 'teachers'; // أو أي disk معرف عندك بيرجع لـ public/

        $directory = dirname(public_path($relativePath));
        $filename = pathinfo($relativePath, PATHINFO_FILENAME);
        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

        $audioFileName = "{$filename}_audio.mp3";
        $videoNoAudioFileName = "{$filename}_no_audio.{$extension}";

        try {
            // ✅ استخراج الصوت
            \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk($disk)
                ->open($video->path)
                ->export()
                ->toDisk($disk)
                ->inFormat(new \FFMpeg\Format\Audio\Mp3)
                ->save("{$audioFileName}");

            // ✅ إزالة الصوت من الفيديو
            \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk($disk)
                ->open($relativePath)
                ->export()
                ->withoutAudio()
                ->toDisk($disk)
                ->save("{$videoNoAudioFileName}");

            echo "✅ Audio extracted to: uploads/{$audioFileName}" . PHP_EOL;
            echo "✅ Video without audio saved to: uploads/{$videoNoAudioFileName}" . PHP_EOL;

        } catch (\Exception $e) {
            echo "❌ FFmpeg process failed: " . $e->getMessage();
        }
    }

}
