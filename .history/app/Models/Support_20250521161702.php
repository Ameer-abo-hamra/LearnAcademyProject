<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Support extends Model
{

    protected $fillable = [
        'message',
        'sender_id', 'sender_type',
        'receiver_id', 'receiver_type',
    ];

    public function sender()
    {
        return $this->morphTo();
    }

    public function receiver()
    {
        return $this->morphTo();
    }
}
