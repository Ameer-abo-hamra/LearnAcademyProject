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

        } catch (\Exception $e) {
            echo "❌ FFmpeg process failed: " . $e->getMessage();
        }
    }
}
