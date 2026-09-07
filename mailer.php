<?php
/*
 * أداة إرسال البريد عبر SMTP (PHPMailer).
 * الاستعمال:
 *   $result = send_mail($to_email, $subject, $body);
 *   // الناتج: ['ok' => true] أو ['ok' => false, 'error' => '..']
 */
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_mail($to, $subject, $body)
{
    $cfg = require __DIR__ . '/mail_config.php';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $cfg['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $cfg['username'];
        $mail->Password   = $cfg['password'];
        $mail->SMTPSecure = $cfg['secure'];
        $mail->Port       = $cfg['port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($cfg['username'], $cfg['from_name']);
        $mail->addAddress($to);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return ['ok' => true];
    } catch (Exception $e) {
        return ['ok' => false, 'error' => $mail->ErrorInfo];
    }
}
