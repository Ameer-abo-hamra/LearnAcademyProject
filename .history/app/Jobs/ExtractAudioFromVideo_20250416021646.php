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
        $video = Video::find($this->videoId)->first();

        if (!$video) {
            echo "❌ Video not found with ID: {$this->videoId}";
            return;
        }

        // full path للفيديو
        $videoPath = public_path("uploads/" . $video->path); // مثال: public/uploads/videos/myvideo.mp4

        $directory = dirname($videoPath);
        $filename = pathinfo($videoPath, PATHINFO_FILENAME);
        $extension = pathinfo($videoPath, PATHINFO_EXTENSION);

        // ملفات الإخراج
        $audioPath = "{$directory}/{$filename}_audio.mp3"; // ملف الصوت المستخرج
        $videoNoAudioPath = "{$directory}/{$filename}_no_audio.{$extension}"; // نسخة الفيديو بدون صوت

        try {
            // ✅ استخراج الصوت
            \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk('teachers')
                ->open($videoPath)
                ->export()
                ->toDisk('teachers')
                ->inFormat(new \FFMpeg\Format\Audio\Mp3)
                ->save($audioPath);

            // ✅ إزالة الصوت من الفيديو
            \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk('teachers')
                ->open($videoPath)
                ->export()
                ->withoutAudio()
                ->toDisk('teachers')
                ->save($videoNoAudioPath);

            echo "✅ Audio extracted to: {$audioPath}";
            echo "✅ Video without audio saved to: {$videoNoAudioPath}";

            // (اختياري) حدّث مسارات جديدة بالـ DB
            // $video->audio_path = str_replace(public_path(), '', $audioPath);
            // $video->video_no_audio_path = str_replace(public_path(), '', $videoNoAudioPath);
            // $video->save();

        } catch (\Exception $e) {
            echo "❌ FFmpeg process failed: " . $e->getMessage();
        }
    }
}
