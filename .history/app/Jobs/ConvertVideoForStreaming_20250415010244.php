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

        // مسار الفيديو الأصلي من الـ database
        $videoPath = "public/".$this->video->path;
        $disk = 'stremable_video';

        // المسار الذي سيتم حفظ ملفات HLS فيه (داخل public)
        $basePath = $this->video->teacher_id . '/' . $this->video->course_id . '/' . $this->video->id;

        // إعداد FFmpeg
        $hls = \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk($this->video->disk) // الديسك الذي رفع عليه الفيديو الأصلي
            ->open($videoPath)
            ->exportForHLS()
            ->toDisk($disk) // التخزين في stremable_video
            ->setSegmentLength(10); // طول كل قطعة .ts

        // إضافة كل دقة مع تحديد إعدادات الكوديك مثل preset "ultrafast"
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

        // محاولة حفظ ملف master.m3u8 وتسجيل الخطأ في حال حدوث استثناء
        try {
            $hls->save("{$basePath}" . '/master.m3u8');
            echo "hi";
        } catch (\Exception $e) {
            \Log::error('FFMpeg HLS Save Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
