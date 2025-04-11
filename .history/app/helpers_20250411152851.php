<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
function sendEmail($to)
{
    $code = Str::random(6);

    $subject = "Verification";
    $message = "Verification code = " . $code;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'mail.wemarketglobal.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@wemarketglobal.com';
        $mail->Password = 'R4_+YBcLs;*O';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom('info@wemarketglobal.com', 'Adds');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->send();
        return $code;

    } catch (Exception $e) {
        return false;
    }
}
