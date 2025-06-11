<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    protected $fillable = ["student_id"];
    protected $table = "sessions_ai";
    public function student()
    {
        return $this->belongsTo(Student::class, "student_id");
    }
}
