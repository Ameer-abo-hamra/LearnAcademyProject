<?php

namespace App\Jobs;

use App\Models\Video;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ConvertVideoForStreaming implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Video $video;
    public $timeout = 1200;

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle()
    {
        // إعدادات الدقات
        $formats = [
            ['bitrate' => 150,  'width' => 256,  'height' => 144],
            ['bitrate' => 400,  'width' => 426,  'height' => 240],
            ['bitrate' => 800,  'width' => 640,  'height' => 360],
            ['bitrate' => 1200, 'width' => 854,  'height' => 480],
            ['bitrate' => 2500, 'width' => 1280, 'height' => 720],
            ['bitrate' => 5000, 'width' => 1920, 'height' => 1080]
        ];

        $videoPath    = $this->video->path;       // مثال: "1/1/79.mp4"
        $diskOriginal = $this->video->disk;       // مثلاً "teachers"
        $diskOutput   = 'stremable_video';

        // بناء مسار الإخراج باستخدام DIRECTORY_SEPARATOR
        $basePath = $this->video->teacher_id . DIRECTORY_SEPARATOR .
                    $this->video->course_id  . DIRECTORY_SEPARATOR .
                    $this->video->id;

        // التأكد من وجود الملف الأصلي
        $fullInputPath = public_path('uploads/' . $videoPath);
        if (!file_exists($fullInputPath)) {
            \Log::error("الملف الأصلي غير موجود: " . $fullInputPath);
            throw new \Exception("الملف الأصلي غير موجود: " . $fullInputPath);
        }

        // التأكد من إنشاء دليل الإخراج
        $storage = Storage::disk($diskOutput);
        if (!$storage->exists($basePath)) {
            $storage->makeDirectory($basePath);
        }
        $savePath = $basePath . DIRECTORY_SEPARATOR . 'master.m3u8';
        $savePath = str_replace('/', '\\', $basePath . '/master.m3u8');

        // تسجيل القيم للتشخيص
        echo 'Disk Original: ' . $diskOriginal . "\n";
        echo 'Video Path (relative): ' . $videoPath . "\n";
        echo 'Full input path: ' . public_path('uploads/' . $videoPath) . "\n";
        echo 'Output base path: ' . $basePath . "\n";
        echo 'Full save path: ' . $savePath . "\n";


        // إعداد ffmpeg
        $hls = \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk($diskOriginal)
            ->open($videoPath)
            ->exportForHLS()
            ->toDisk($diskOutput)
            ->setSegmentLength(10);

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

        try {
            $hls->save($savePath);
            echo "Hi, process completed!";
        } catch (\Exception $e) {
            \Log::error('FFMpeg HLS Save Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
