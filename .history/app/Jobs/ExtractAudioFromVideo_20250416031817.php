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
            Log::error("❌ Video not found with ID: {$this->videoId}");
            return;
        }

        $disk = 'teachers'; // تأكد من أن هذا الـ disk معرف في config/filesystems.php
        $videoPath = $video->path; // مسار الفيديو داخل الـ disk

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
                "path" => $audioFileName
            ]);
            // ✅ إزالة الصوت من الفيديو باستخدام الفلتر '-an'
            FFMpeg::fromDisk($disk)
                ->open($videoPath)
                ->export()
                ->toDisk($disk)
                ->addFilter('-an')
                ->save($videoNoAudioFileName);

            $video->path = $videoNoAudioFileName;
            $video->save();
            Log::info("✅ Audio extracted to: {$audioFileName}");
            Log::info("✅ Video without audio saved to: {$videoNoAudioFileName}");

        } catch (\Exception $e) {
            Log::error("❌ FFmpeg process failed: " . $e->getMessage());
        }
    }
}
