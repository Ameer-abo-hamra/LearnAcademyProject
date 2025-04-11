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
    Mail::send('hi', ['user' => $to, 'activation' => $activationCode], function ($message) use ($to) {
        $message->to($to)
                ->subject('تفعيل حسابك');
    });
}
