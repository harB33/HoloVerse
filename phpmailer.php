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
    $mailer->Subject = 'Your HoloVerse password reset code';
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $mailer->isHTML(true);
    $mailer->Body = <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your HoloVerse password reset code</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f1f5f9;">
        <tr>
            <td align="center" style="padding:36px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 32px;background-color:#111827;background-image:linear-gradient(120deg,#111827,#164e63);">
                            <p style="margin:0;color:#67e8f9;font-size:13px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;">HoloVerse</p>
                            <h1 style="margin:12px 0 0;color:#ffffff;font-size:24px;line-height:1.3;font-weight:700;">Reset your password</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 12px;color:#334155;font-size:16px;line-height:1.6;">Use this one-time code to continue resetting your password:</p>
                            <div style="margin:24px 0;padding:20px 12px;background-color:#ecfeff;border:1px solid #a5f3fc;border-radius:12px;text-align:center;">
                                <span style="color:#0e7490;font-size:34px;line-height:1.3;font-weight:700;letter-spacing:10px;">{$safeOtp}</span>
                            </div>
                            <p style="margin:0 0 20px;color:#475569;font-size:14px;line-height:1.6;"><strong>This code expires in 10 minutes</strong> and can only be used once.</p>
                            <hr style="height:1px;margin:24px 0;border:0;background-color:#e2e8f0;">
                            <p style="margin:0;color:#64748b;font-size:13px;line-height:1.6;">If you didn’t request a password reset, you can safely ignore this email. Your password will not change unless the code is used.</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:18px 0 0;color:#94a3b8;font-size:12px;line-height:1.5;">This is an automated message from HoloVerse. Please do not reply.</p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    $mailer->AltBody = "HoloVerse password reset\n\n"
        . "Your one-time code is: {$otp}\n\n"
        . "This code expires in 10 minutes and can only be used once.\n\n"
        . "If you didn’t request a password reset, you can safely ignore this email. "
        . "Your password will not change unless the code is used.";
    $mailer->send();
}
