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

class StripAudioAndSaveJob implements ShouldQueue
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

        // full path للفيديو
        $videoPath = public_path($video->path); // مثال: public/uploads/videos/myvideo.mp4

        $directory = dirname($videoPath);
        $filename = pathinfo($videoPath, PATHINFO_FILENAME);
        $extension = pathinfo($videoPath, PATHINFO_EXTENSION);

        // ملفات الإخراج
        $audioPath = "{$directory}/{$filename}_audio.mp3"; // ملف الصوت المستخرج
        $videoNoAudioPath = "{$directory}/{$filename}_no_audio.{$extension}"; // نسخة الفيديو بدون صوت

        try {
            // ✅ استخراج الصوت
            FFMpeg::fromDisk('local')
                ->open($videoPath)
                ->export()
                ->toDisk('local')
                ->inFormat(new \FFMpeg\Format\Audio\Mp3)
                ->save($audioPath);

            // ✅ إزالة الصوت من الفيديو
            FFMpeg::fromDisk('local')
                ->open($videoPath)
                ->export()
                ->withoutAudio()
                ->toDisk('local')
                ->save($videoNoAudioPath);

            Log::info("✅ Audio extracted to: {$audioPath}");
            Log::info("✅ Video without audio saved to: {$videoNoAudioPath}");

            // (اختياري) حدّث مسارات جديدة بالـ DB
            // $video->audio_path = str_replace(public_path(), '', $audioPath);
            // $video->video_no_audio_path = str_replace(public_path(), '', $videoNoAudioPath);
            // $video->save();

        } catch (\Exception $e) {
            Log::error("❌ FFmpeg process failed: " . $e->getMessage());
        }
    }
}
