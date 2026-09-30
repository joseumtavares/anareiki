<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/db.php';

/** Envia e-mail em texto puro pelo SMTP da Hostinger. Falha vira false + error_log (sem o conteúdo). */
function enviarEmail(string $para, string $assunto, string $texto): bool
{
    $smtp = config()['smtp'];
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->Port = (int) $smtp['porta'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['usuario'];
        $mail->Password = $smtp['senha'];
        $mail->SMTPSecure = (int) $smtp['porta'] === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Timeout = 15;
        $mail->setFrom($smtp['usuario'], $smtp['remetente_nome']);
        $mail->addAddress($para);
        $mail->Subject = $assunto;
        $mail->Body = $texto;
        $mail->send();
        return true;
    } catch (MailerException) {
        error_log('Falha no envio SMTP.');
        return false;
    }
}
