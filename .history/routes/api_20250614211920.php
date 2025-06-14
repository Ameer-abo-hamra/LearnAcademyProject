<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CourseAttachmentsController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\QuizeController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SpecilizationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
//YourNewPassword123!
//StrongPassword123!

/**
 * update video audios when the request done from AI
 *
 */

Route::get("get-course-data/{course_id}", [CourseController::class, "getCourseData"]);

Route::get("get-spec-data/{spec_id}", [SpecilizationController::class, "getSpecData"]);

Route::post('teacher/sign-up', [TeacherController::class, "signUp"]);

Route::post('teacher/login', [TeacherController::class, "login"]);

Route::get('search/courses-and-specializations', [StudentController::class, 'search']);

Route::get("teacher/get-category", [CategoryController::class, "getAll"]);

Route::get("teacher/skills/{category_id}", [SkillController::class, "getSkillFromCategory"]);

Route::group(["middleware" => 'checkuser:student'], function () {

    Route::get("student/get-video-audio/{video_id}", [VideoController::class, "getAudioFiles"]);


    Route::post('student/support/send', [SupportController::class, 'sendSupportMessage']);

    Route::get('student/support/messages', [SupportController::class, 'getSupportMessages']);

    Route::get('student/notifications', [StudentController::class, 'getStudentNotifications']);

    Route::get('student/logout', [StudentController::class, "logout"]);

    Route::post("student/enroll-course/{course_id}", [StudentController::class, "courseEnroll"]);

    Route::post("student/save-course/{course_id}", [StudentController::class, "saveCourse"]);

    Route::get("student/get-course/{course_id}", [CourseController::class, "getCourseForEnrolledStudents"]);

    Route::get('student/watch-video/{video_id}', [VideoController::class, "watchVideoForStudent"]);

    Route::get("student/get-subtitles/{video_id}/{lang}", [VideoController::class, "getSubTitles"]);

    Route::post("student/mark-content-as-done", [VideoController::class, "completeContent"]);

    Route::get('student/get-percentage-for-course/{course_id}', [VideoController::class, "getCoursePercentage"]);

    Route::get("student/get-quiz/{quiz_id}", [QuizeController::class, "getQuizForStudent"]);

    Route::post("student/solve-quiz", [QuizeController::class, 'submitQuizAnswers']);

    Route::get("student/courses-in-progerss", [StudentController::class, "getCoursesInProgress"]);

    Route::get("student/courses-completed", [StudentController::class, "getCoursesCompleted"]);

    Route::get("student/courses-saved", [StudentController::class, "getCoursesSaved"]);

    Route::get('student/course-for-unenrolled-student/{course_id}', [CourseController::class, "getCourseForStudent"]);

    Route::get('/student/profile', [StudentController::class, 'getProfile']);

    Route::post('/student/profile/update', [StudentController::class, 'updateProfile']);

    Route::get("student/get-spec/{spec_id}", [SpecilizationController::class, "getSpecForStudent"]);

    Route::post("student/generate-quiz/{quiz_id}", [QuizeController::class, "generateAutoQuiz"]);

    Route::post("student/create-session", [SessionController::class, "createSession"]);

    Route::post("student/send-message", [SessionController::class, "sendMessageToVideoBot"]);
});
Route::group(["middleware" => 'checkuser:teacher'], function () {
    /*

    1- add three apis for status 0,1,2 for teacher
    2- add publish logic for students courses
    */

    Route::get('teacher/courses/in-progress', [CourseController::class, 'getInProgressCourses']);

    Route::get('teacher/courses/pending', [CourseController::class, 'getPendingCourses']);

    Route::get('teacher/courses/published', [CourseController::class, 'getPublishedCourses']);

    Route::post('teacher/support/send', [SupportController::class, 'sendSupportMessage']);

    Route::get('teacher/support/messages', [SupportController::class, 'getSupportMessages']);

    Route::get('teacher/notifications', [TeacherController::class, 'getTeacherNotifications']);

    Route::post("teacher/update-profile", [TeacherController::class, "updateProfile"]);

    Route::get("teacher/profile", [TeacherController::class, "getProfile"]);

    Route::get('teacher/logout', [TeacherController::class, "logout"]);

    Route::post("teacher/make-course", [CourseController::class, "makeCourse"]);

    Route::post("teacher/update-course/{course_id}", [CourseController::class, "updateCourse"]);

    Route::post('teacher/add-quiz', [QuizeController::class, "addQuize"]);

    Route::delete('teacher/quiz/{quiz_id}', [QuizeController::class, 'deleteQuize']);

    Route::put("teacher/update-quiz/{quiz_id}", [QuizeController::class, "updateQuize"]);

    Route::post("teacher/upload-video", [VideoController::class, "store"]);

    Route::post("teacher/update-video-info/{video_id}", [VideoController::class, "updateVideoInfo"]);


    // Route::get("teacher/show-video", [VideoController::class, "showVideo"]);

    Route::post("teacher/add-course-attachmet", [CourseAttachmentsController::class, "addAttachment"]);

    Route::post("teacher/update-attachments/{attachment_id}", [CourseAttachmentsController::class, "updateAttachment"]);

    Route::post("teacher/create-specialization", [SpecilizationController::class, "createSpecialization"]);

    Route::get("teacher/publish-course/{course_id}", [CourseController::class, "publishCourse"]);

    Route::post('/teacher/{video}/extensions', [VideoController::class, 'updateExtension']);

    Route::put('/teacher/{video}/questions', [VideoController::class, 'updateQuestions']);

    Route::get("teacher/courses", [CourseController::class, "getTeacherCourses"]);

    Route::get("teacher/courses-title-id", [CourseController::class, "getTeacherCoursesTitleId"]);

    Route::get("teacher/course-video", [VideoController::class, "getCourseVideo"]);

    Route::get("teacher/get-subtitles/{video_id}/{lang}", [VideoController::class, "getSubTitles"]);

    Route::get("teacher/specializations", [SpecilizationController::class, "getSpecializations"]);

    Route::post("teacher/update-specialization/{specialization_id}", [SpecilizationController::class, "updateSpecialization"]);

    Route::get("teacher/specialization-courses/{spec_id}", [SpecilizationController::class, "getSpecializationCourse"]);

    Route::get("teacher/course-details/{course_id}", [CourseController::class, "getCourseDetails"]);

    Route::get("teacher/get-quiz", [QuizeController::class, "getQuizeForTeacher"]);

    Route::get("teacher/get-video-audio/{video_id}", [VideoController::class, "getAudioFiles"]);

});







Route::post("admin/login", [UserController::class, "adminLogin"]);

Route::prefix('admin')->middleware(['checkuser:admin'])->group(function () {

    Route::get('categories', [CategoryController::class, 'index']);

    Route::post('categories', [CategoryController::class, 'store']);

    Route::put('categories/{id}', [CategoryController::class, 'update']);

    Route::delete('categories/{id}', [CategoryController::class, 'destroy']);

    Route::get('skills', [SkillController::class, 'index']);

    Route::post('skills', [SkillController::class, 'store']);

    Route::put('skills/{id}', [SkillController::class, 'update']);

    Route::delete('skills/{id}', [SkillController::class, 'destroy']);

    // Students
    Route::post('/students', [UserController::class, 'createStudent']);

    Route::post('/students/{id}', [UserController::class, 'updateStudent']);

    Route::delete('/students/{id}', [UserController::class, 'deleteStudent']);

    Route::get('/get-students', [UserController::class, 'getStudents']);

    // Teachers
    Route::post('/teachers', [UserController::class, 'createTeacher']);

    Route::post('/teachers/{id}', [UserController::class, 'updateTeacher']);

    Route::delete('/teachers/{id}', [UserController::class, 'deleteTeacher']);

    Route::get('/get-teachers', [UserController::class, 'getTeachers']);

    // Course Subscriptions
    Route::get('/subscriptions', [UserController::class, 'getCourseSubscriptions']);

    Route::get("/completed-quizes", [UserController::class, "getCompletedQuizzes"]);

    Route::get("get-ready-courses", [UserController::class, "getReadyCourses"]);

    Route::post("publish-course/{course_id}", [UserController::class, "publishCourse"]);

    Route::get('/courses/{id}/preview', [UserController::class, 'getPendingCourseDetails']);

    Route::post('/courses/{id}/reject', [UserController::class, 'rejectCourse']);

    Route::post('/create-specializations', [UserController::class, 'createSpecializationByAdmin']);

    Route::post('/update-specializations/{id}', [UserController::class, 'updateSpecializationByAdmin']);

    Route::delete('/delete-specializations/{id}', [UserController::class, 'deleteSpecializationByAdmin']);

    Route::get('/get-specializations', [UserController::class, 'getSpecializations']);

    Route::get('/specializations/{id}/courses', [UserController::class, 'getCoursesBySpecialization']);

    Route::get('/notifications', [UserController::class, 'getAdminNotifications']);

    Route::post('/support/send', [SupportController::class, 'sendSupportMessage']);

    // جلب جميع الرسائل التي تخص المستخدم (مرسلة أو مستقبلة)
    Route::get('/support/messages', [SupportController::class, 'getSupportMessages']);

    Route::get('video', [UserController::class, 'getAdminCourseVideo']);

    Route::get('get-quiz-for-admin/{quiz_id}', [UserController::class, 'getQuizForAdmin']);

    Route::get('get-spec-for-admin/{spec_id}', [UserController::class, 'getSpecForAdmin']);

    Route::get('get-courses-by-teacher', [UserController::class, 'getCoursesByTeacher']);

    Route::get("get-all-category", [CategoryController::class, "getAll"]);
});

Route::get("w", function () {
    return view("test");
});


Route::post('student/resend', [StudentController::class, "resend"]);

Route::post('teacher/resend', [TeacherController::class, "resend"]);

Route::post('student/activate', [StudentController::class, "activate"]);

Route::post('teacher/activate', [TeacherController::class, "activate"]);

Route::post('student/sign-up', [StudentController::class, "signUp"]);

Route::post('student/login', [StudentController::class, "login"]);
