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

                // تصدير الفيديو بصيغة HLS وتحديد الوجهة التي سيتم التصدير إليها
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

            // تحديد المسار النهائي للفيديو الذي تم تحويله
            $path = $this->video->teacher_id . '/' .
                $this->video->course_id . '/' .
                $this->video->id . '/master.m3u8';

            // تحديث المسار في قاعدة البيانات
            $this->video->path = assetFromDisk("streamable_videos", $path);
            $this->video->save();
            event(new TeacherEvent($this->video->teacher_id ,  "Video conversion successful for video ID: " . $this->video->id));
            // echo  "this is the teacher id " . u("teacher")->id;
            // إشعار بنجاح التحويل (يمكنك تخصيص هذه الخطوة لإرسال إشعار للمستخدم)
            \Log::info('Video conversion successful for video ID: ' . $this->video->id);

        } catch (\Exception $e) {
            // التعامل مع الأخطاء في حال حدوث مشكلة أثناء التحويل
            // يمكنك أيضًا إرسال إشعار للمستخدم في حالة الفشل
            \Log::error('Video conversion failed for video ID: ' . $this->video->id . ' Error: ' . $e->getMessage());

            // // تحديث حالة الفيديو إلى "فشل" أو أي شيء يشير إلى أن التحويل فشل
            // $this->video->status = 'failed'; // تأكد من وجود الحقل في جدول الفيديو
            // $this->video->save();

            TeacherEvent::broadcast($this->video->teacher_id, "there are a problem :(");
        }
    }
}
