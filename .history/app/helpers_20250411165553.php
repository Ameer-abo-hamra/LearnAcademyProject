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


        Mail::send('hi', ['user' => $to, 'activationCode' => $activationCode], function ($message) use ($to) {
            $message->to($to->email)
                ->subject('تفعيل حسابك');
        });
    }
}
