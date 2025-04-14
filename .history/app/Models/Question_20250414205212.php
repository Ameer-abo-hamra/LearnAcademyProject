<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{

    public function choices()
    {
        return $this->hasMany(Choice::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quize::class);
    }

}
