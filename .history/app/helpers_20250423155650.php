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
    $extension = $request->file("file")->getClientOriginalExtension();
    $fileName = $videoId . '.' . $extension;

    // المسار: public/uploads/{teacher_id}/{course_id}/
    $folderPath = "{$teacherId}/{$courseId}/{$videoId}";

    // خزن الملف
    $path = $request->file("file")->storeAs($folderPath, $fileName, 'teachers');

    return $path; // يرجع المسار الكامل داخل public/
}

function imageUpload($request, $id, $diskname): string|bool
{
    // اسم الملف: [video_id].[الامتداد]
    $extension = $request->file("image")->getClientOriginalExtension();
    $fileName = $id . '.' . $extension;

    // المسار: public/uploads/{teacher_id}/{course_id}/
    $folderPath = "";
    if ($request->has('teacher_id')) {
        $folderPath = "{$request->teacher_id}/{$id}";
    } else {
        $folderPath = null;
    }
    // خزن الملف
    $path = $request->file("image")->storeAs($folderPath, $fileName, $diskname);

    return $path; // يرجع المسار الكامل داخل public/
}

function assetFromDisk($disk, $filename)
{
    $baseUrl =  $folder . "/" . $filename ;

    return rtrim($baseUrl, '/') . '/' . ltrim($filename, '/');
}

function u($guard)
{
    return Auth::guard($guard)->user();
}
