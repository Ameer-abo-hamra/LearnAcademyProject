<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Storage;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
class Teacher extends Authenticatable implements JWTSubject
{

    protected $fillable = ["age", "gender", "image", "specialization", "full_name", "username", "password", "admin_activation", "email", "id", "activation_code", "is_active", "teacher_id"];


    public function courses() {
        return $this->hasMany(Course::class , "teacher_id");
    }
    public function specializations() {
        return $this->hasMany(Course::class , "teacher_id");
    }
    public function getImageUrlAttribute()
    {
        return $this->image
            ? Storage::disk('teacher_image')->url($this->image)
            : null;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
