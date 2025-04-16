<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoAudio extends Model
{
    protected $fillable = ["script_path", "video_id"];

    public function video() {
        return $this->belongsTo(Video::class , "id");
    }
}
