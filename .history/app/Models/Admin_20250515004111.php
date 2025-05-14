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

}
