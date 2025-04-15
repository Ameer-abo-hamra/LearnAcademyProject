<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    public function skills()
    {
        return $this->belongsToMany(Skill::class, "course_skill", "course_id", "skill_id");
    }
}
