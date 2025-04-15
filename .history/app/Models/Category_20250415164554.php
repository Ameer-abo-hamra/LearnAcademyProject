<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    public function cour() {
        return $this->belongsToMany(Category::class , "course_category" , "course_id" , "category_id");
    }
}
