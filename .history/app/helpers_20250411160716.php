<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
function sendEmail($to)
{
    $code = Str::random(6);

    $subject = "Verification";
    $message = "Verification code = " . $code;

    // $mail = new PHPMailer(true);

    // try {
    //     $mail->isSMTP();
    //     $mail->Host = env("MAIL_HOST");
    //     $mail->SMTPAuth = true;
    //     $mail->Username = env("MAIL_USERNAME");
    //     $mail->Password = env("MAIL_PASSWORD");
    //     $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    //     $mail->Port = env("MAIL_PORT");
    //     $mail->setFrom(env("MAIL_FROM_ADDRESS"), 'Adds');
    //     $mail->addAddress($to);
    //     $mail->Subject = $subject;
    //     $mail->Body = $message;
    //     $mail->send();
    //     return $code;

    // } catch (Exception $e) {
    //     return $e->getMessage();
    // }
}
