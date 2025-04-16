<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoQuestionChoice extends Model
{

    protected $fillable = ["is_cor", "question"];

    public function videoQuestion()
    {
        return $this->belongsTo(VideoQuestion::class, "id");
    }
}
