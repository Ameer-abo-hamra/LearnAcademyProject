<?php

namespace App\Jobs;

use App\Models\Video;
use Carbon\Carbon;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConvertVideoForStreaming implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $video;
    public $timeout = 1200; // المهلة 20 دقيقة

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle()
    {
        // قائمة الدقات مع معدل البت والدقة المناسبة
        $formats = [
            ['bitrate' => 150, 'width' => 256, 'height' => 144],   // 144p
            ['bitrate' => 400, 'width' => 426, 'height' => 240],   // 240p
            ['bitrate' => 800, 'width' => 640, 'height' => 360],   // 360p
            ['bitrate' => 1200, 'width' => 854, 'height' => 480],   // 480p
            ['bitrate' => 2500, 'width' => 1280, 'height' => 720],   // 720p
            ['bitrate' => 5000, 'width' => 1920, 'height' => 1080],  // 1080p
        ];

        $videoPath = $this->video->path;
        $disk = $this->video->disk;

        // تحديد المسار الذي سيتم حفظ الفيديوهات المحولة فيه
        $basePath = public_path('/' . $this->video->teacher_id . '/' . $this->video->course_id . '/' . $this->video->id);

        // تأكد من وجود المجلدات
        if (!file_exists($basePath)) {
            mkdir($basePath, 0777, true);  // إنشاء المجلدات بشكل متسلسل
        }

        // إنشاء كائن HLS لتصدير الفيديو بجميع الدقات
        $hls = \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk($disk)
            ->open($videoPath)
            ->exportForHLS()
            ->toDisk('public');  // تأكد من أن الحفظ في public path

        // إضافة كل دقة بعد معالجتها
        foreach ($formats as $format) {
            $resolutionFolder = $basePath . '/' . $format['width'] . 'x' . $format['height'];

            // تأكد من وجود المجلد الخاص بالدقة
            if (!file_exists($resolutionFolder)) {
                mkdir($resolutionFolder, 0777, true);
            }

            // إضافة التنسيق للدقة
            $hls->addFormat(
                (new X264)->setKiloBitrate($format['bitrate']),
                function ($filters) use ($format) {
                    $filters->resize($format['width'], $format['height']);
                }
            );
        }

        // حفظ قائمة التشغيل الرئيسية HLS (m3u8)
        $hls->save($basePath . '/' . $this->video->id . '.m3u8');

        // إذا كنت ترغب في حفظ كل فيديو بتنسيق الدقة المحددة:
        foreach ($formats as $format) {
            $fileName = $this->video->id . '-' . $format['width'] . 'x' . $format['height'] . '.mp4';
            $hls->save($basePath . '/' . $format['width'] . 'x' . $format['height'] . '/' . $fileName);
        }

        // تحديث مسار الفيديو المحول في قاعدة البيانات
        $this->video->path = $basePath . '/' . $this->video->id . '.m3u8';  // أو يمكن تخزين المسار للمجلد الأساسي
        $this->video->save();
    }
}
