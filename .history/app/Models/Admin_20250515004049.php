<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin 
{
    //
    public function sentNotifications()
{
    return $this->morphMany(Notification::class, 'sender');
}

}
