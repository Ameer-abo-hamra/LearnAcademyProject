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
use FFMpeg;

class ConvertVideoForStreaming implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

     $video;
    public $timeout = 1200;

    public function __construct(Video $video)
    {
        $this->video = $video;
    }


    public function handle()
    {
        // create some video formats...
        $lowBitrateFormat = (new X264)->setKiloBitrate(500);
        $midBitrateFormat = (new X264)->setKiloBitrate(1500);
        $highBitrateFormat = (new X264)->setKiloBitrate(3000);

        // open the uploaded video from the right disk...
        \ProtoneMedia\LaravelFFMpeg\Support\FFMpeg::fromDisk($this->video->disk)
            ->open($this->video->path)

            // call the 'exportForHLS' method and specify the disk to which we want to export...
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

    }
}
