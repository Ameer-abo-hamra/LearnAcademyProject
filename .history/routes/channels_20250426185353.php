<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });


Broadcast::channel("teacher.{teacher_id}", function () {

    if (u("teacher")->id === $id) {
        return true;

    }
});
Broadcast::channel("student.{student_id}", function () {

    if (u("student")->id === $id) {
        return true;
    }
});
