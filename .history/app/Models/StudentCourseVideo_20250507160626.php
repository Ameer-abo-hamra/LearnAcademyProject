<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCourseVideo extends Model
{
    protected $fillable = [
        'student_id', 'course_id', 'video_id', 'locked', 'completed_at'
    ];

    // العلاقات

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
