<?php

use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('teacher/sign-up', [TeacherController::class, "signUp"]);

Route::post('teacher/login', [TeacherController::class, "login"]);

Route::group(["middleware" => 'checkuser:student'], function () {

    Route::get('student/logout', [StudentController::class, "logout"]);
});

Route::group(["middleware" => 'checkuser:teacher'], function () {

    Route::get('teacher/logout', [TeacherController::class, "logout"]);

    Route::post("teacher/make-course")

    Route::post("teacher/upload-video", [VideoController::class, "store"]);

});


Route::post('student/resend', [StudentController::class, "resend"]);

Route::post('student/activate', [StudentController::class, "activate"]);

Route::post('teacher/activate', [TeacherController::class, "activate"]);

Route::post('student/sign-up', [StudentController::class, "signUp"]);

Route::post('student/login', [StudentController::class, "login"]);






