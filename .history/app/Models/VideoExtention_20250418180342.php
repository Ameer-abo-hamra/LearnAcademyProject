<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoExtention extends Model
{
    protected $fillable = ['file_path' , 'text' , 'video_id'];
}
