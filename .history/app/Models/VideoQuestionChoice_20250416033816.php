<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoQuestionChoice extends Model
{

    protected $fillable = ["time_to_appear", "question"];

    public function videoQuestion()
    {
        return $this->belongsTo(VideoQuestion::class, "id");
    }
}
