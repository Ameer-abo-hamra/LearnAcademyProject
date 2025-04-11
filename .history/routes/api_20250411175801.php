<?php

use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('teacher/sign-up', [TeacherController::class, "signUp"]);

Route::post('teacher/login', [TeacherController::class, "login"]);

Route::post('teacher/logout', [TeacherController::class, "logout"]);

Route::post('teacher/activate', [TeacherController::class, "activate"]);



Route::group(["middleware" => 'checkuser'], function () {

    Route::post('teacher/resend', [TeacherController::class, "resend"]);

    Route::post('student/resend', [StudentController::class, "resend"]);

});

Route::post('student/sign-up', [StudentController::class, "signUp"]);

Route::post('student/login', [StudentController::class, "login"]);

Route::post('student/logout', [StudentController::class, "logout"]);

Route::post('student/activate', [StudentController::class, "activate"]);


