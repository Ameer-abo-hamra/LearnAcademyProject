<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quize extends Model
{
    protected $fillable = ["from_video", "to_video", "title", "course_id", "point", "is_final" , "is_auto_generated"];

    public function course()
    {
        return $this->belongsTo(Course::class, "course_id");
    }
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function students() {
        return $this->belongsToMany(Student::class , "student__quiz" , "student_i")
    }
}
