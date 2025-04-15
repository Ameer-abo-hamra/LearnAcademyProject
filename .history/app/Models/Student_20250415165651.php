<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
class Student extends Authenticatable implements JWTSubject
{
    protected $fillable = ["full_name", "admin_activation", "username", "password", "email", "id", "activation_code", "is_active"];

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

    public function courses()
    {
        return $this->belongsToMany(Course::class, "course_student", "","course_id");
    }
}
