<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specilization extends Model
{
    protected $fillable = ["title", "is_completed","teacher_id" , "image"];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_specialization', "specialization_id", "course_id");
    }


    public function 

    public function categories()
    {
        return $this->belongsToMany(Category::class, '_category__specilization', "specialization_id", "category_id"); // افتراضًا
    }
    public function skills()
    {
        return $this->belongsToMany(Skill::class, '_skill__specilization', "specialization_id", "skill_id"); // افتراضًا
    }

}
