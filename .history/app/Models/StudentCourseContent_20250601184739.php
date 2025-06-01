<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StudentCourseContent extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'content_id',
        'content_type',
        'locked',
        'completed_at',
        'order_index'
    ];

    const TYPE_VIDEO = 'App\Models\Video';
    const TYPE_QUIZ = 'App\Models\Quize';
    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}

