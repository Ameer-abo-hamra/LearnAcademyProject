<?php

namespace App\Jobs;

use App\Models\Video;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConvertVideoForStreaming implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Video $video;
    public $timeout = 1200; // 20 دقيقة

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle()
    {
        // إعدادات الدقات المطلوبة
        $formats = [
            ['bitrate' => 150, 'width' => 256, 'height' => 144],   // 144p
            ['bitrate' => 400, 'width' => 426, 'height' => 240],   // 240p
            ['bitrate' => 800, 'width' => 640, 'height' => 360],   // 360p
            ['bitrate' => 1200, 'width' => 854, 'height' => 480],  // 480p
            ['bitrate' => 2500, 'width' => 1280, 'height' => 720], // 720p
            ['bitrate' => 5000, 'width' => 1920, 'height' => 1080] // 1080p
        ];

        // قيمة المسار يجب أن تكون نسبية بالنسبة لجذر القرص الذي يرتبط بالفيديو الأصلي
        $videoPath = $this->video->path; // تأكد أن القيمة مثل "1/1/79.mp4"
        $diskOriginal = $this->video->disk; // مثلاً "teachers" الذي يعرف جذر 'public/uploads/'
        $diskOutput  = 'stremable_video';

        // المسار الذي سيتم حفظ ملفات HLS فيه داخل قرص stremable_video
        $basePath = $this->video->teacher_id . '/' . $this->video->course_id . '/' . $this->video->id;
        echo "this is the idddddddddddd {$this->video->id}"
        // إعداد FFmpeg باستخدام المسار المُستلم مباشرة
        $hls = \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk($diskOriginal)
            ->open($videoPath)
            ->exportForHLS()
            ->toDisk($diskOutput)
            ->setSegmentLength(10);

        // إضافة كل دقة مع إعدادات الكوديك
        foreach ($formats as $format) {
            $hls->addFormat(
                (new X264('aac', 'libx264'))
                    ->setKiloBitrate($format['bitrate'])
                    ->setAdditionalParameters(['-preset', 'ultrafast']),
                function ($filters) use ($format) {
                    $filters->resize($format['width'], $format['height']);
                }
            );
        }

        // حفظ ملف master.m3u8 داخل المسار المطلوب
        try {
            $hls->save("{$basePath}/master.m3u8");
            echo "Hi, process completed!";
        } catch (\Exception $e) {
            \Log::error('FFMpeg HLS Save Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
