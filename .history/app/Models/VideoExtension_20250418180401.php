<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoExtension extends Model
{
    protected $fillable = ['file_path' , 'text' , 'video_id'];
}
