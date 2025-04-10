<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacherextends Authenticatable implements JWTSubject
{

    protected $fillable = ["full_name", "username", "password", "email" , "id"];

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
