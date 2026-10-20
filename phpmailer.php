<?php

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createUnsafeImmutable(__DIR__, '.env.local');
$dotenv->safeLoad();
\Dotenv\Dotenv::createUnsafeImmutable(__DIR__)->safeLoad();

function createHoloVerseMailer(string $email): \PHPMailer\PHPMailer\PHPMailer
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
        throw new RuntimeException('HoloVerse email SMTP settings are missing or invalid.');
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
    return $mailer;
}

function getHoloVerseSiteUrl(): string
{
    $siteUrl = getenv('HOLOVERSE_SITE_URL') ?: 'http://localhost:8000/index.php';
    if (filter_var($siteUrl, FILTER_VALIDATE_URL) === false || !in_array(parse_url($siteUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
        throw new RuntimeException('HOLOVERSE_SITE_URL must be a valid HTTP or HTTPS URL.');
    }

    return $siteUrl;
}

function sendPasswordResetOtp(string $email, string $otp): void
{
    $mailer = createHoloVerseMailer($email);
    $mailer->addEmbeddedImage(
        __DIR__ . '/assets/images/holoverse-wordmark-dark.png',
        'holoverse-wordmark',
        'holoverse-wordmark-dark.png',
        'base64',
        'image/png'
    );
    $mailer->Subject = 'Your HoloVerse password reset code';
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeSiteUrl = htmlspecialchars(getHoloVerseSiteUrl(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
                        <td style="padding:24px 32px;border-bottom:1px solid #eef0f4;background-color:#ffffff;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left" valign="middle">
                                        <a href="{$safeSiteUrl}" style="display:inline-block;text-decoration:none;">
                                            <img src="cid:holoverse-wordmark" width="190" alt="HoloVerse" style="display:block;width:190px;max-width:100%;height:auto;border:0;">
                                        </a>
                                    </td>
                                    <td align="right" valign="middle">
                                        <a href="{$safeSiteUrl}" style="display:inline-block;padding:11px 20px;border:1px solid #77758e;border-radius:6px;color:#292747;font-size:14px;line-height:1.2;font-weight:600;text-decoration:none;white-space:nowrap;">Go to HoloVerse</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 16px;color:#172033;font-size:24px;line-height:1.3;font-weight:700;">Reset your password</h1>
                            <p style="margin:0 0 12px;color:#334155;font-size:16px;line-height:1.6;">Use this one-time code to continue resetting your password:</p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;background-color:#ecfeff;border:1px solid #a5f3fc;border-radius:12px;">
                                <tr>
                                    <td align="center" style="padding:20px 12px;">
                                        <span style="color:#0e7490;font-size:34px;line-height:1.3;font-weight:700;letter-spacing:10px;">{$safeOtp}</span>
                                    </td>
                                </tr>
                            </table>
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
        . "Your password will not change unless the code is used.\n\n"
        . "This is an automated message from HoloVerse. Please do not reply.";
    $mailer->send();
}

function sendOrderReceipt(
    string $email,
    string $orderNumber,
    string $customerName,
    string $paymentMethod,
    string $currency,
    array $items,
    array $shippingAddress,
    float $shippingFee,
    float $tax
): void {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-Z]{3}$/', $currency) || !$items) {
        throw new InvalidArgumentException('Order receipt details are incomplete or invalid.');
    }

    $mailer = createHoloVerseMailer($email);
    $mailer->addEmbeddedImage(
        __DIR__ . '/assets/images/holoverse-wordmark-dark.png',
        'holoverse-wordmark',
        'holoverse-wordmark-dark.png',
        'base64',
        'image/png'
    );
    $safe = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeSiteUrl = $safe(getHoloVerseSiteUrl());
    $paymentLabel = match ($paymentMethod) {
        'card' => 'Card',
        'e_wallet' => 'E-wallet',
        'cash_on_delivery' => 'Cash on delivery',
        default => 'Not specified',
    };

    $subtotal = 0.0;
    $itemRows = '';
    foreach ($items as $item) {
        $productName = $safe((string) $item['product_name']);
        $variantName = $safe((string) $item['variant_name']);
        $sku = $safe((string) $item['sku']);
        $unitPrice = (float) $item['unit_price'];
        $quantity = (int) $item['quantity'];
        if ($quantity < 1 || $unitPrice < 0) {
            throw new InvalidArgumentException('Order receipt contains an invalid item.');
        }
        $lineTotal = $unitPrice * $quantity;
        $subtotal += $lineTotal;
        $itemRows .= '<tr>'
            . '<td style="padding:12px 8px;border-bottom:1px solid #e2e8f0;color:#334155;">'
            . '<strong>' . $productName . '</strong><br>'
            . '<span style="font-size:12px;color:#64748b;">' . $variantName . ' · ' . $sku . '</span></td>'
            . '<td align="center" style="padding:12px 8px;border-bottom:1px solid #e2e8f0;color:#334155;">' . $quantity . '</td>'
            . '<td align="right" style="padding:12px 8px;border-bottom:1px solid #e2e8f0;color:#334155;">'
            . $safe($currency) . ' ' . number_format($lineTotal, 2) . '</td></tr>';
    }

    $total = $subtotal + $shippingFee + $tax;
    $addressLines = array_filter([
        (string) ($shippingAddress['recipient_name'] ?? $customerName),
        (string) ($shippingAddress['address_line1'] ?? ''),
        (string) ($shippingAddress['address_line2'] ?? ''),
        implode(', ', array_filter([
            (string) ($shippingAddress['city'] ?? ''),
            (string) ($shippingAddress['region'] ?? ''),
            (string) ($shippingAddress['postal_code'] ?? ''),
        ])),
        (string) ($shippingAddress['country_code'] ?? ''),
    ], static fn (string $line): bool => trim($line) !== '');
    $safeAddress = implode('<br>', array_map($safe, $addressLines));
    $plainAddress = implode("\n", $addressLines);
    $safeOrderNumber = $safe($orderNumber);
    $safeCustomerName = $safe($customerName);
    $safePaymentLabel = $safe($paymentLabel);
    $safeCurrency = $safe($currency);
    $formattedShippingFee = $safeCurrency . ' ' . number_format($shippingFee, 2);
    $formattedTax = $safeCurrency . ' ' . number_format($tax, 2);
    $formattedSubtotal = $safeCurrency . ' ' . number_format($subtotal, 2);
    $formattedTotal = $safeCurrency . ' ' . number_format($total, 2);

    $mailer->Subject = 'Your HoloVerse order receipt · ' . $orderNumber;
    $mailer->isHTML(true);
    $mailer->Body = <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your HoloVerse order receipt</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f1f5f9;">
        <tr><td align="center" style="padding:36px 16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                <tr><td style="padding:24px 32px;border-bottom:1px solid #eef0f4;">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"><tr>
                        <td><a href="{$safeSiteUrl}" style="text-decoration:none;"><img src="cid:holoverse-wordmark" width="190" alt="HoloVerse" style="display:block;width:190px;max-width:100%;height:auto;border:0;"></a></td>
                        <td align="right"><a href="{$safeSiteUrl}" style="display:inline-block;padding:11px 20px;border:1px solid #77758e;border-radius:6px;color:#292747;font-size:14px;font-weight:600;text-decoration:none;white-space:nowrap;">Go to HoloVerse</a></td>
                    </tr></table>
                </td></tr>
                <tr><td style="padding:32px;">
                    <p style="margin:0 0 8px;color:#0e7490;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">Order confirmed</p>
                    <h1 style="margin:0 0 12px;color:#172033;font-size:24px;line-height:1.3;">Thanks for your order, {$safeCustomerName}!</h1>
                    <p style="margin:0 0 24px;color:#475569;font-size:14px;line-height:1.6;">Receipt for order <strong>{$safeOrderNumber}</strong><br>Payment method: {$safePaymentLabel}</p>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;font-size:14px;">
                        <thead><tr>
                            <th align="left" style="padding:10px 8px;background-color:#f8fafc;color:#475569;font-size:12px;text-transform:uppercase;">Item</th>
                            <th align="center" style="padding:10px 8px;background-color:#f8fafc;color:#475569;font-size:12px;text-transform:uppercase;">Qty</th>
                            <th align="right" style="padding:10px 8px;background-color:#f8fafc;color:#475569;font-size:12px;text-transform:uppercase;">Amount</th>
                        </tr></thead>
                        <tbody>{$itemRows}</tbody>
                    </table>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px;font-size:14px;">
                        <tr><td style="padding:5px 8px;color:#64748b;">Subtotal</td><td align="right" style="padding:5px 8px;color:#334155;">{$formattedSubtotal}</td></tr>
                        <tr><td style="padding:5px 8px;color:#64748b;">Shipping</td><td align="right" style="padding:5px 8px;color:#334155;">{$formattedShippingFee}</td></tr>
                        <tr><td style="padding:5px 8px;color:#64748b;">Tax</td><td align="right" style="padding:5px 8px;color:#334155;">{$formattedTax}</td></tr>
                        <tr><td style="padding:12px 8px;border-top:1px solid #e2e8f0;color:#172033;font-weight:bold;">Total</td><td align="right" style="padding:12px 8px;border-top:1px solid #e2e8f0;color:#172033;font-size:18px;font-weight:bold;">{$formattedTotal}</td></tr>
                    </table>
                    <h2 style="margin:28px 0 8px;color:#172033;font-size:16px;">Shipping address</h2>
                    <p style="margin:0;color:#475569;font-size:14px;line-height:1.6;">{$safeAddress}</p>
                </td></tr>
            </table>
            <p style="margin:18px 0 0;color:#94a3b8;font-size:12px;line-height:1.5;">This is an automated message from HoloVerse. Please do not reply.</p>
        </td></tr>
    </table>
</body>
</html>
HTML;
    $mailer->AltBody = "HoloVerse order receipt\n\n"
        . "Thanks for your order, {$customerName}!\n"
        . "Order: {$orderNumber}\n"
        . "Payment method: {$paymentLabel}\n\n"
        . "Items:\n";
    foreach ($items as $item) {
        $mailer->AltBody .= sprintf(
            "%s — %s (SKU: %s), quantity %d: %s %s\n",
            $item['product_name'],
            $item['variant_name'],
            $item['sku'],
            (int) $item['quantity'],
            $currency,
            number_format((float) $item['unit_price'] * (int) $item['quantity'], 2)
        );
    }
    $mailer->AltBody .= sprintf(
        "\nSubtotal: %s %s\nShipping: %s\nTax: %s\nTotal: %s\n\nShipping address:\n%s\n\nThis is an automated message from HoloVerse. Please do not reply.",
        $currency,
        number_format($subtotal, 2),
        $formattedShippingFee,
        $formattedTax,
        $formattedTotal,
        $plainAddress
    );
    $mailer->send();
}
