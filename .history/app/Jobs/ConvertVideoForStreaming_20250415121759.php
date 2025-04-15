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
            ['bitrate' => 150,  'width' => 256,  'height' => 144],   // 144p
            ['bitrate' => 400,  'width' => 426,  'height' => 240],   // 240p
            ['bitrate' => 800,  'width' => 640,  'height' => 360],   // 360p
            ['bitrate' => 1200, 'width' => 854,  'height' => 480],   // 480p
            ['bitrate' => 2500, 'width' => 1280, 'height' => 720],   // 720p
            ['bitrate' => 5000, 'width' => 1920, 'height' => 1080],  // 1080p
        ];

        $videoPath = $this->video->path;
        $disk = $this->video->disk;

        // إنشاء كائن HLS لتصدير الفيديو بجميع الدقات
        $hls = \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk($disk)
            ->open($videoPath)
            ->exportForHLS()
            ->toDisk('streamable_videos');

        // إضافة كل دقة بعد معالجتها
        foreach ($formats as $format) {
            $hls->addFormat(
                (new X264)->setKiloBitrate($format['bitrate']),
                function ($filters) use ($format) {
                    $filters->resize($format['width'], $format['height']);
                }
            );
        }

        // حفظ قائمة التشغيل الرئيسية HLS
        $hls->save($this->video->id . '.m3u8');

        // تحديث قاعدة البيانات
        $this->video->update([
            'converted_for_streaming_at' => Carbon::now(),
        ]);
    }
}
