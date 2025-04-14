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

    /**
     * تنفيذ Job رفع الفيديو.
     */
    public function handle()
    {
        try {
            // استرجاع الفيديو من قاعدة البيانات
            $video = Video::find($this->videoId);

            if (!$video) {
                \Log::error('Video not found: ' . $this->videoId);
                return;
            }

            // إرسال مهام تحويل الفيديو
            dispatch(new ConvertVideoForDownloading($video));
            dispatch(new ConvertVideoForStreaming($video));
        } catch (\Exception $e) {
            \Log::error('Error processing video upload: ' . $e->getMessage());
        }
    }
}
