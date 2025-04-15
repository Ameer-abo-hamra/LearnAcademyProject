<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{


    protected $fillable = ["is_quizes_auto_generated", "point_to_enroll", "name", "description", "specilization_id", "teacher_id"];


    public function quiezes()
    {
        return $this->hasMany(Quize::class, "course_id");
    }

    public function videos()
    {
        return $this->hasMany(Video::class, "course_id");
    }

    public function categories() {
        return $this->belongsToMany(Category::class , "course_category" , "")
    }
}
