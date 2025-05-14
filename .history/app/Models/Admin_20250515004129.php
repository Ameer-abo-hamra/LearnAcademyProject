<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Authenticatable implements JWTSubject
{
    //
    public function sentNotifications()
    {
        return $this->morphMany(Notification::class, 'sender');
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
