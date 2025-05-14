<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin implements 
{
    //
    public function sentNotifications()
{
    return $this->morphMany(Notification::class, 'sender');
}

}
