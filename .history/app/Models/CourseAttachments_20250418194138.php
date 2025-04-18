<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseAttachments extends Model
{
    protected $fillable = ['file_path', 'text', 'course_id'];
    public function course()
    {
        return $this->belongsTo(Course::class, "id");
    }
}
