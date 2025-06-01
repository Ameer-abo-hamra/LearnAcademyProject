<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{

    protected $fillable = ["title", "category_id"];
    public function courses()
    {
        return $this->belongsToMany(Course::class, "course_skill", "skill_id", "course_id");
    }

    public function specializations()
    {
        return $this->belongsToMany(Specilization::class, '_skill__specilization', "skill_id", "specialization_id"); // افتراضًا
    }

    public function aquirements()
    {
        return $this->belongsToMany(Course::class, "course_aquirement", "skill_id", "course_id");
    }

    public function category () {
        return $this->belongsTo(Category::class , $category)
    }
}
