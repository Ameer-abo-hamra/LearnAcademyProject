<?php

use App\Http\Controllers\TeacherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('sign-up', [TeacherController::class , "signUp"]);
Route::post('login', [TeacherController::class , ""]);
