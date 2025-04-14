<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{


    protected $fillable = ["is_quizes_auto_generated", "name", "description", "specilization_id", "teacher_id"];


    public function quiezes()
    {
        return $this->hasMany(Quize::class, "course_id");
    }
}
