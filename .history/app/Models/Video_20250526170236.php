<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = ["image", "sequential_order", "description", "path", "disk", "original_name", "course_id", "id", "teacher_id", "title"];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function studentCourseVideos()
    {
        return $this->hasMany(StudentCourseVideo::class);
    }

    public function scripts()
    {
        return $this->hasMany(Script::class, "video_id");
    }
    public function audios()
    {
        return $this->hasMany(VideoAudio::class, "video_id");
    }

    public function questions()
    {
        return $this->hasMany(VideoQuestion::class, "video_id");
    }

    public function extensions()
    {
        return $this->hasMany(VideoExtension::class, "video_id");
    }
    public function sub()
    {
        return $this->hasMany(VideoExtension::class, "video_id");
    }
}
