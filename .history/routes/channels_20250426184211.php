<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });


Broadcast::channel("teacher.{teacher_id}", function ($user=u("teacher"), $id) {

    if ($user->id === $id) {
        return true;
    }
});
Broadcast::channel("student.{student_id}", function ($user, $id) {

    if (->id === $id) {
        return true;
    }
});
