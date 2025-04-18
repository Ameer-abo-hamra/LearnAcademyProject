<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseAttachments extends Model
{

    public function course()
    {
        return $this->belongsTo(Course::class, "category_id");
    }
}
