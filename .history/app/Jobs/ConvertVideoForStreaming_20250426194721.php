<?php

namespace App\Jobs;

use App\Events\TeacherEvent;
use App\Models\Video;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use FFMpeg;

class ConvertVideoForStreaming implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $video;
    public $timeout = 1200; // تحديد مدة الانتظار القصوى للـ job

    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    public function handle()
    {
        try {
            // تحديد تنسيقات الفيديو المختلفة
            $lowBitrateFormat = (new X264)->setKiloBitrate(500);
            $midBitrateFormat = (new X264)->setKiloBitrate(1500);
            $highBitrateFormat = (new X264)->setKiloBitrate(3000);

            // طباعة مسار الفيديو الحالي للتحقق من العمل
            echo $this->video->path;

            // فتح الفيديو باستخدام FFMpeg
            \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk($this->video->disk)
                ->open($this->video->path)

                ->exportForHLS()
                ->toDisk('streamable_videos')
                ->addFormat($lowBitrateFormat)
                ->addFormat($midBitrateFormat)
                ->addFormat($highBitrateFormat)
                ->save(
                    $this->video->teacher_id . '/' .
                    $this->video->course_id . '/' .
                    $this->video->id . '/master.m3u8'
                );

            $path = $this->video->teacher_id . '/' .
                $this->video->course_id . '/' .
                $this->video->id . '/master.m3u8';

            $this->video->path = assetFromDisk("streamable_videos", $path);
            $this->video->save();
            event(new TeacherEvent($this->video->teacher_id, "Video conversion successful you can now see your video in multi resolutions"));

        } catch (\Exception $e) {
            TeacherEvent::broadcast($this->video->teacher_id, "there are a problem  please try upload video again :(");
        }
    }
}
