<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoQuestion extends Model
{
    protected $fillable = ["time_to_appear" , "question"];

    public function video() {
        return $this->belongsTo()
    }
}
