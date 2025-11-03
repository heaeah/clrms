<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

function sendSMTPMail($to, $subject, $body, $from = 'noreply@example.com', $fromName = 'CLRMS') {
    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'clrmsbsistalisay@gmail.com'; // <-- Replace with your new Gmail address
        $mail->Password   = 'pkrk cskn csju kddg';    // <-- Replace with your new Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);

        //Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}