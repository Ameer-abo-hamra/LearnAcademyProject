<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });


Broadcast::channel('teacher.{teacher_id}', function ($user, $teacher_id) {
    return $user->id === (int) $teacher_id;
})->middleware('checkuser:teacher');

Broadcast::channel('student.{student_id}', function ($user, $student_id) {
    return $user->id === (int) $student_id;
})->middleware('checkusers:student');

