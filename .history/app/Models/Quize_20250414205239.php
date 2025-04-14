<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quize extends Model
{


    public function course() {
        return $this->belongsTo(Course::class , "id");
    }
    public function questions()
{
    return $this->hasMany(Question::class);
}

}
