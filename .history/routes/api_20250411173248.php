<?php

use App\Http\Controllers\TeacherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('teacher/sign-up', [TeacherController::class, "signUp"]);
Route::post('teacher/login', [TeacherController::class, "login"]);
Route::post('teacher/logout', [TeacherController::class, "logout"]);
Route::post('teacher/activate', [TeacherController::class, "activate"]);
Route::post('teacher/resend', [TeacherController::class, "resend"]);
