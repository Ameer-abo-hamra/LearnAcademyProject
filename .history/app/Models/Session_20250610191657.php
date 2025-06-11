<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $fillable = ["student_id" , "session__id"];
    protected $table = "sessions_Ai";
    public function student()
    {
        return $this->belongsTo(Student::class, "student_id");
    }
}
