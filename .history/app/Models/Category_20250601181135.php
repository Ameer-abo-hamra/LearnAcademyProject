<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
protected $fiilable = ["title"];
    public function courses()
    {
        return $this->hasMany(Course::class, "category_id");
    }

    public function skills()
    {
        return $this->hasMany(Skill::class, "category_id");
    }
}
