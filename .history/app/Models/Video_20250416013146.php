<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = ["description", "path", "disk", "original_name", "course_id" , "id","teacher_id" , "title"];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function scripts() {
        return $this->hasMany(Script::class , "video_id")
    }
}
