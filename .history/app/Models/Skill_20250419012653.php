<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    public function courses()
    {
        return $this->belongsToMany(Course::class, "course_skill", "skill_id", "course_id");
    }

    public function spe()
    {
        return $this->belongsToMany(Skill::class, '_skill__specilization', "specialization_id", "skill_id"); // افتراضًا
    }
}
