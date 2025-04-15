<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Script extends Model
{
    protected $fillable = ["script_path", "video_id"];

    public function video() {
        return $this->
    }
}
