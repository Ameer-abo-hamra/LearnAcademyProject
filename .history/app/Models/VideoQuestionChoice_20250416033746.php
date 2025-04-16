<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoQuestionChoice extends Model
{



    public function videoQuestion()
    {
        return $this->belongsTo(Video::class, "id");
    }
}
