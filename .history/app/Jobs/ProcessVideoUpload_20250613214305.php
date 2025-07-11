<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessVideoUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $videoId;

    /**
     * إنشاء Job مع معرف الفيديو فقط
     */
    public function __construct($videoId)
    {
        $this->videoId = $videoId;
    }

    /*
     * تنفيذ Job رفع الفيديو.
     */
    public function handle()
    {
        try {
            // استرجاع الفيديو من قاعدة البيانات
            $video = Video::findOrFail($this->videoId);

            if (!$video) {
                echo ('Video not found: ' . $this->videoId);
                return;
            }

            // إرسال مهام تحويل الفيديو
            dispatch(new SendDubbingJobRequest($video->path, $video->scripts()->where("language","en" )));

            dispatch(new ExtractAudioFromVideo($this->videoId));
            // dispatch(new ConvertVideoForStreaming($video));
        } catch (\Exception $e) {
            echo ('Error processing video upload: ' . $e->getMessage());
        }
    }
}
