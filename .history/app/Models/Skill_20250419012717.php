<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    public function courses()
    {
        return $this->belongsToMany(Course::class, "course_skill", "skill_id", "course_id");
    }

    public function specializations()
    {
        return $this->belongsToMany(Specilization::class, '_skill__specilization', ""skill_id,"specialization_id"); // افتراضًا
    }
}
