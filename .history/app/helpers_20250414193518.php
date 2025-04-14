<?php
use App\Mail\Teacher;
use Illuminate\Support\Env;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
function sendEmail($to)
{

    $activationCode = Str::random(6); // مثال على كود التفعيل الذي ستولده
    try {
        Mail::send('activationPage', ['user' => $to, 'activationCode' => $activationCode], function ($message) use ($to) {
            $message->to($to->email)
                ->subject('تفعيل حسابك');
        });
    } catch (\Exception $e) {
        return false;
    }
    return $activationCode;
}


function fileupload($request, $teacherId, $courseId, $videoId): string|bool
{
    // اسم الملف: [video_id].[الامتداد]
    $extension = $request->file("video")->getClientOriginalExtension();
    $fileName = $videoId . '.' . $extension;

    // المسار: public/uploads/{teacher_id}/{course_id}/
    $folderPath = "uploads/{$teacherId}/{$courseId}";

    // خزن الملف
    $path = $request->file("video")->storeAs($folderPath, $fileName, 'teachers');

    return $path; // يرجع المسار الكامل داخل public/
}


function u() {

}
