<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Choice extends Model
{
    protected $fillable = ["choice" , "question_id" ,"is_correct"];
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

}
