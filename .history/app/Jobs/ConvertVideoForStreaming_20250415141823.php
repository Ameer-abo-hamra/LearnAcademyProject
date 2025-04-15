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

    public Video $video;
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

            // we'll add different formats so the stream will play smoothly
            // with all kinds of internet connections...
            ->addFormat($lowBitrateFormat)
            ->addFormat($midBitrateFormat)
            ->addFormat($highBitrateFormat)

            // call the 'save' method with a filename...
            ->save($this->video->id . '.m3u8');
    }
}
