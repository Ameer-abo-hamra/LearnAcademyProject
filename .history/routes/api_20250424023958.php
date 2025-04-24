<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CourseAttachmentsController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\QuizeController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SpecilizationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('teacher/sign-up', [TeacherController::class, "signUp"]);

Route::post('teacher/login', [TeacherController::class, "login"]);

Route::get('search/courses-and-specializations', [StudentController::class, 'search']);


Route::group(["middleware" => 'checkuser:student'], function () {

    Route::get('student/logout', [StudentController::class, "logout"]);
});

Route::group(["middleware" => 'checkuser:teacher'], function () {

    Route::post("teacher/update-profile" , [TeacherController::class , "updateProfile"]);

    Route::get("")

    Route::get('teacher/logout', [TeacherController::class, "logout"]);

    Route::post("teacher/make-course", [CourseController::class, "makeCourse"]);

    Route::post('teacher/add-quiz', [QuizeController::class, "addQuize"]);

    Route::post("teacher/upload-video", [VideoController::class, "store"]);

    Route::get("teacher/get-category", [CategoryController::class, "getAll"]);

    Route::get("teacher/skills/{category_id}", [SkillController::class, "getSkillFromCategory"]);

    Route::post("teacher/add-course-attachmet", [CourseAttachmentsController::class, "addAttachment"]);

    Route::post("teacher/create-specialization", [SpecilizationController::class, "createSpecialization"]);

    Route::get("teacher/publish-course/{course_id}", [CourseController::class, "publishCourse"]);

    Route::post('/teacher/{video}/extensions', [VideoController::class, 'updateExtension']);

    Route::put('/teacher/{video}/questions', [VideoController::class, 'updateQuestions']);

    Route::get("teacher/courses" , [CourseController::class , "getTeacherCourses"]);

    Route::get("teacher/courses-title-id" , [CourseController::class , "getTeacherCoursesTitleId"]);

    Route::get("teacher/course-video/{course_id}" , [VideoController::class , "getCourseVideos"]);

    Route::get("teacher/specializations" , [SpecilizationController::class , "getSpecializations"]);

    Route::put("teacher/update-specialization/{specialization_id}" , [SpecilizationController::class , "updateSpecialization"]);

    Route::get("teacher/specialization-courses/{spec_id}" , [SpecilizationController::class , "getSpecializationCourse"]);

    Route::get("teacher/course-details/{course_id}" , [CourseController::class , "getCourseDetails"]);
});


Route::post('student/resend', [StudentController::class, "resend"]);

Route::post('teacher/resend', [TeacherController::class, "resend"]);

Route::post('student/activate', [StudentController::class, "activate"]);

Route::post('teacher/activate', [TeacherController::class, "activate"]);

Route::post('student/sign-up', [StudentController::class, "signUp"]);

Route::post('student/login', [StudentController::class, "login"]);






