<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quize extends Model
{
    protected $fillable = ["from_video", "to_video", "title", "course_id", "point", "is_final" , "is_auto_generated"];

    public function course()
    {
        return $this->belongsTo(Course::class, "id");
    }
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

}
