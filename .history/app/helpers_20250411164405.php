<?php
use App\Mail\Teacher;
use Illuminate\Support\Env;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
function sendEmail($to)
{
    $activationCode = '123456'; // مثال على كود التفعيل الذي ستولده

    // إرسال البريد الإلكتروني
    Mail::send('activation', ['user' => $to, 'activationCode' => $activationCode], function ($message) use ($user) {
        $message->to($user->email)
                ->subject('تفعيل حسابك');
    });
}
