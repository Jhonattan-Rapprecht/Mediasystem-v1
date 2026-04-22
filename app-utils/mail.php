<?php
/**
 * App mail helper.
 *
 * Recommended setup:
 * - Install PHPMailer: composer require phpmailer/phpmailer
 * - Configure env vars in Apache/PHP environment:
 *   APP_MAIL_FROM, APP_SMTP_HOST, APP_SMTP_PORT, APP_SMTP_USER, APP_SMTP_PASS
 */

function app_mail_from(): string {
    $from = getenv('APP_MAIL_FROM');
    return $from !== false && $from !== '' ? $from : 'no-reply@localhost';
}

function app_send_mail(string $to, string $subject, string $htmlBody): bool {
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    $smtpHost = getenv('APP_SMTP_HOST') ?: '';
    $smtpUser = getenv('APP_SMTP_USER') ?: '';
    $smtpPass = getenv('APP_SMTP_PASS') ?: '';
    $smtpPort = (int)(getenv('APP_SMTP_PORT') ?: 587);

    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer') && $smtpHost !== '' && $smtpUser !== '' && $smtpPass !== '') {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;

            $mail->setFrom(app_mail_from(), 'Mediasystem');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $htmlBody));
            $mail->send();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: Mediasystem <' . app_mail_from() . '>',
    ];

    return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
}
