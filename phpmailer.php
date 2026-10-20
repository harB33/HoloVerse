<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__, '.env.local');
$dotenv->safeLoad();
\Dotenv\Dotenv::createUnsafeImmutable(__DIR__)->safeLoad();

function sendPasswordResetOtp(string $email, string $otp): void
{
    $host = getenv('HOLOVERSE_SMTP_HOST');
    $username = getenv('HOLOVERSE_SMTP_USERNAME');
    $password = getenv('HOLOVERSE_SMTP_PASSWORD');
    $fromAddress = getenv('HOLOVERSE_MAIL_FROM');
    $portInput = getenv('HOLOVERSE_SMTP_PORT') ?: '587';
    $encryption = strtolower(getenv('HOLOVERSE_SMTP_ENCRYPTION') ?: 'tls');
    $port = filter_var($portInput, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 65535],
    ]);

    if (
        !is_string($host) || trim($host) === ''
        || !is_string($username) || $username === ''
        || !is_string($password) || $password === ''
        || !is_string($fromAddress) || filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false
        || $port === false
        || !in_array($encryption, ['tls', 'ssl'], true)
    ) {
        throw new RuntimeException('Password reset SMTP settings are missing or invalid.');
    }

    if (strtolower($host) === 'smtp.gmail.com') {
        $password = preg_replace('/\s+/', '', $password) ?? $password;
    }

    $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $host;
    $mailer->SMTPAuth = true;
    $mailer->Username = $username;
    $mailer->Password = $password;
    $mailer->SMTPSecure = $encryption === 'ssl'
        ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
        : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->Port = $port;
    $mailer->Timeout = 10;
    $mailer->CharSet = \PHPMailer\PHPMailer\PHPMailer::CHARSET_UTF8;
    $mailer->setFrom($fromAddress, getenv('HOLOVERSE_MAIL_FROM_NAME') ?: 'HoloVerse');
    $mailer->addAddress($email);
    $mailer->isHTML(false);
    $mailer->Subject = 'Your HoloVerse password reset code';
    $mailer->Body = "Your HoloVerse password reset code is: {$otp}\n\n"
        . "This code expires in 10 minutes and can only be used once. "
        . "If you did not request a password reset, you can ignore this email.";
    $mailer->send();
}
