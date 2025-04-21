<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specilization extends Model
{
    protected $fillable = ["title" , "is_completed"];

    public function courses() {
        return $this->belongsToMany(Course::class, 'course_specialization');
    }

    public function categories() {
        return $this->belongsToMany(Category::class, '_category__specilization' , "specialization_id" , "category_id"); // افتراضًا
    }

}
