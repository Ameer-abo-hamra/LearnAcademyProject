<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    public function courses()
    {
        return $this->belongsToMany(Course::class, "course_skill", "course_id", "skill_id");
    }
}
