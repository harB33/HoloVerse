<?php
require_once __DIR__ . '/../app/store.php';

$requestMode = $_GET['mode'] ?? $_POST['mode'] ?? '';
$requestMode = is_string($requestMode) ? $requestMode : '';
$isRegistration = $requestMode === 'register';
$isPasswordResetRequest = $requestMode === 'forgot_password';
$isPasswordResetResend = $requestMode === 'resend_reset_otp';
$isPasswordReset = in_array($requestMode, ['reset_password', 'verify_reset_otp'], true);
$isOtpVerification = $requestMode === 'verify_reset_otp';
$returnTo = ($_GET['return_to'] ?? $_POST['return_to'] ?? '') === 'checkout' ? 'checkout' : '';
$message = '';
$loginRateLimitSeconds = 0;
$notice = is_string($_SESSION['auth_notice'] ?? null) ? $_SESSION['auth_notice'] : '';
unset($_SESSION['auth_notice']);
$registrationName = '';
$registrationEmail = '';
$resetRequestEmail = '';
$resetEmail = is_string($_SESSION['password_reset_email'] ?? null)
    ? $_SESSION['password_reset_email']
    : '';
$resetEmailHash = $resetEmail !== '' ? hash('sha256', strtolower(trim($resetEmail))) : '';
$resetOtpExpiresAt = (int) ($_SESSION['password_reset_expires_at'] ?? 0);
$verifiedReset = $_SESSION['password_reset_verified'] ?? null;
$isResetPasswordVerified = is_array($verifiedReset)
    && $resetEmailHash !== ''
    && is_string($verifiedReset['email_hash'] ?? null)
    && hash_equals($resetEmailHash, $verifiedReset['email_hash'])
    && is_string($verifiedReset['otp_hash'] ?? null);

function login_password_is_strong(string $password): bool
{
    if (strlen($password) < 12 || strlen($password) > 72) {
        return false;
    }

    $characterTypes = [
        preg_match('/[a-z]/', $password) === 1,
        preg_match('/[A-Z]/', $password) === 1,
        preg_match('/[0-9]/', $password) === 1,
        preg_match('/[^a-zA-Z0-9]/', $password) === 1,
    ];

    return count(array_filter($characterTypes)) >= 3;
}

function login_apply_failed_attempt_limit(
    DatabaseConnection $conn,
    string &$message,
    int &$loginRateLimitSeconds
): void
{
    $loginRateLimitSeconds = store_record_login_failure($conn);
    if ($loginRateLimitSeconds > 0) {
        http_response_code(429);
        header('Retry-After: ' . $loginRateLimitSeconds);
        $message = 'Too many sign-in attempts. Please wait before trying again.';
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif ($isPasswordResetRequest || $isPasswordResetResend) {
        $emailInput = $isPasswordResetResend ? $resetEmail : ($_POST['email'] ?? '');
        $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
        $resetRequestEmail = $isPasswordResetRequest ? $email : '';
        if ($isPasswordResetResend && $resetEmail === '') {
            $message = 'Start a new password reset request to receive a code.';
            $isPasswordResetRequest = true;
            $isPasswordResetResend = false;
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $message = 'Enter a valid email address.';
        } else {
            $userStatement = $conn->prepare('SELECT user_id FROM user_tbl WHERE LOWER(email) = LOWER(?) LIMIT 1');
            $userStatement->bind_param('s', $email);
            $userStatement->execute();
            $user = $userStatement->get_result()->fetch_assoc();
            $userStatement->close();
            $resetUserId = $user ? (int) $user['user_id'] : null;

            $emailHash = hash('sha256', $email);
            $conn->query(
                "DELETE FROM password_reset_codes
                 WHERE requested_at < CURRENT_TIMESTAMP - INTERVAL '1 day'"
            );
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash = password_hash($otp, PASSWORD_DEFAULT);
            $expiresAt = (new DateTimeImmutable('+10 minutes', new DateTimeZone('UTC')))->format('Y-m-d H:i:sP');
            $resetStatement = $conn->prepare(
                "INSERT INTO password_reset_codes (email_hash, user_id, otp_hash, expires_at)
                 VALUES (?, ?, ?, ?)
                 ON CONFLICT (email_hash) DO UPDATE
                 SET user_id = EXCLUDED.user_id,
                     otp_hash = EXCLUDED.otp_hash,
                     requested_at = CURRENT_TIMESTAMP,
                     expires_at = EXCLUDED.expires_at,
                     attempt_count = 0,
                     used_at = NULL
                 WHERE password_reset_codes.requested_at <= CURRENT_TIMESTAMP - INTERVAL '1 minute'
                 RETURNING email_hash"
            );
            $resetStatement->bind_param('siss', $emailHash, $resetUserId, $otpHash, $expiresAt);
            $resetStatement->execute();
            $codeIssued = $resetStatement->get_result()->fetch_assoc() !== null;
            $resetStatement->close();

            if ($codeIssued && $resetUserId !== null) {
                try {
                    require_once __DIR__ . '/../phpmailer.php';
                    sendPasswordResetOtp($email, $otp);
                } catch (Throwable $error) {
                    error_log('HoloVerse password reset email could not be sent: ' . $error->getMessage());
                    $deleteFailedCode = $conn->prepare(
                        'DELETE FROM password_reset_codes WHERE email_hash = ? AND otp_hash = ? AND used_at IS NULL'
                    );
                    $deleteFailedCode->bind_param('ss', $emailHash, $otpHash);
                    $deleteFailedCode->execute();
                    $deleteFailedCode->close();
                }
            }

            $_SESSION['password_reset_email'] = $email;
            if ($codeIssued || !$isPasswordResetResend || !isset($_SESSION['password_reset_expires_at'])) {
                $_SESSION['password_reset_expires_at'] = time() + 600;
            }
            unset($_SESSION['password_reset_verified']);
            $_SESSION['auth_notice'] = $isPasswordResetResend && !$codeIssued
                ? 'Please wait a minute before requesting another code.'
                : 'If an account exists for that email, we’ll send a reset code. Check your spam folder if you do not see it.';
            header('Location: login?mode=reset_password' . ($returnTo === 'checkout' ? '&return_to=checkout' : ''));
            exit;
        }
    } elseif ($isOtpVerification) {
        $otpInput = $_POST['otp'] ?? '';
        $otp = is_string($otpInput) ? trim($otpInput) : '';

        if ($resetEmail === '') {
            $message = 'Start a new password reset request to receive a code.';
            $isPasswordResetRequest = true;
            $isPasswordReset = false;
        } elseif (preg_match('/^\d{6}$/', $otp) !== 1) {
            $message = 'Enter the six-digit code from your email.';
        } else {
            $emailHash = $resetEmailHash;
            $conn->begin_transaction();
            try {
                $resetStatement = $conn->prepare(
                    'SELECT user_id, otp_hash, attempt_count, used_at, expires_at > CURRENT_TIMESTAMP AS is_unexpired
                     FROM password_reset_codes
                     WHERE email_hash = ?
                     FOR UPDATE'
                );
                $resetStatement->bind_param('s', $emailHash);
                $resetStatement->execute();
                $reset = $resetStatement->get_result()->fetch_assoc();
                $resetStatement->close();

                $isUnexpired = in_array($reset['is_unexpired'] ?? false, [true, 't', 1, '1'], true);
                if (
                    !$reset
                    || $reset['user_id'] === null
                    || $reset['used_at'] !== null
                    || !$isUnexpired
                    || (int) $reset['attempt_count'] >= 5
                ) {
                    $conn->commit();
                    $message = 'Invalid or expired code.';
                } elseif (!password_verify($otp, $reset['otp_hash'])) {
                    $attemptStatement = $conn->prepare(
                        'UPDATE password_reset_codes SET attempt_count = attempt_count + 1 WHERE email_hash = ?'
                    );
                    $attemptStatement->bind_param('s', $emailHash);
                    $attemptStatement->execute();
                    $attemptStatement->close();
                    $conn->commit();
                    $message = 'Invalid or expired code.';
                } else {
                    $conn->commit();
                    $_SESSION['password_reset_verified'] = [
                        'email_hash' => $emailHash,
                        'otp_hash' => $reset['otp_hash'],
                    ];
                    header('Location: login?mode=reset_password' . ($returnTo === 'checkout' ? '&return_to=checkout' : ''));
                    exit;
                }
            } catch (Throwable $error) {
                $conn->rollback();
                throw $error;
            }
        }
    } elseif ($isPasswordReset) {
        $passwordInput = $_POST['password'] ?? '';
        $confirmationInput = $_POST['password_confirmation'] ?? '';
        $password = is_string($passwordInput) ? $passwordInput : '';
        $passwordConfirmation = is_string($confirmationInput) ? $confirmationInput : '';

        if ($resetEmail === '') {
            $message = 'Start a new password reset request to receive a code.';
            $isPasswordResetRequest = true;
            $isPasswordReset = false;
        } elseif (!$isResetPasswordVerified) {
            $message = 'Invalid or expired code.';
        } elseif (!login_password_is_strong($password)) {
            $message = 'Choose a strong password: at least 12 characters and at least 3 of these: lowercase, uppercase, number, and symbol.';
        } elseif ($password !== $passwordConfirmation) {
            $message = 'The password confirmation does not match.';
        } else {
            $emailHash = $resetEmailHash;
            $conn->begin_transaction();
            try {
                $resetStatement = $conn->prepare(
                    'SELECT user_id, otp_hash, attempt_count, used_at, expires_at > CURRENT_TIMESTAMP AS is_unexpired
                     FROM password_reset_codes
                     WHERE email_hash = ?
                     FOR UPDATE'
                );
                $resetStatement->bind_param('s', $emailHash);
                $resetStatement->execute();
                $reset = $resetStatement->get_result()->fetch_assoc();
                $resetStatement->close();

                $isUnexpired = in_array($reset['is_unexpired'] ?? false, [true, 't', 1, '1'], true);
                $verifiedCodeMatches = $reset
                    && is_string($verifiedReset['otp_hash'] ?? null)
                    && hash_equals($verifiedReset['otp_hash'], $reset['otp_hash']);
                if (
                    !$reset
                    || !$verifiedCodeMatches
                    || $reset['user_id'] === null
                    || $reset['used_at'] !== null
                    || !$isUnexpired
                    || (int) $reset['attempt_count'] >= 5
                ) {
                    $conn->commit();
                    unset($_SESSION['password_reset_verified']);
                    $isResetPasswordVerified = false;
                    $message = 'Invalid or expired code.';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $userId = (int) $reset['user_id'];
                    $updateUser = $conn->prepare(
                        'UPDATE user_tbl SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?'
                    );
                    $updateUser->bind_param('si', $passwordHash, $userId);
                    $updateUser->execute();
                    $updateUser->close();

                    $consumeCode = $conn->prepare(
                        'UPDATE password_reset_codes SET used_at = CURRENT_TIMESTAMP WHERE email_hash = ?'
                    );
                    $consumeCode->bind_param('s', $emailHash);
                    $consumeCode->execute();
                    $consumeCode->close();
                    $conn->commit();

                    unset($_SESSION['password_reset_email'], $_SESSION['password_reset_verified'], $_SESSION['password_reset_expires_at']);
                    $_SESSION['auth_notice'] = 'Your password has been reset. Sign in with your new password.';
                    header('Location: login' . ($returnTo === 'checkout' ? '?return_to=checkout' : ''));
                    exit;
                }
            } catch (Throwable $error) {
                $conn->rollback();
                throw $error;
            }
        }
    } else {
        $emailInput = $_POST['email'] ?? '';
        $passwordInput = $_POST['password'] ?? '';
        $confirmationInput = $_POST['password_confirmation'] ?? '';
        $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';
        $passwordConfirmation = is_string($confirmationInput) ? $confirmationInput : '';
        $registrationEmail = $isRegistration ? $email : '';
        $usernameInput = $_POST['username'] ?? '';
        $registrationName = is_string($usernameInput) ? trim($usernameInput) : '';
        $retryAfter = $isRegistration ? 0 : store_login_rate_limit_retry_after($conn);

        if ($retryAfter > 0) {
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            $loginRateLimitSeconds = $retryAfter;
            $message = 'Too many sign-in attempts. Please wait before trying again.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            $message = 'Enter a valid email address.';
            if (!$isRegistration) {
                login_apply_failed_attempt_limit($conn, $message, $loginRateLimitSeconds);
            }
        } elseif ($password === '') {
            $message = 'Enter your password.';
            if (!$isRegistration) {
                login_apply_failed_attempt_limit($conn, $message, $loginRateLimitSeconds);
            }
        } elseif ($isRegistration) {
            $username = $registrationName;
            if (strlen($username) < 2 || strlen($username) > 50) {
                $message = 'Choose a name between 2 and 50 characters.';
            } elseif (!login_password_is_strong($password)) {
                $message = 'Choose a strong password: at least 12 characters and at least 3 of these: lowercase, uppercase, number, and symbol.';
            } elseif ($password !== $passwordConfirmation) {
                $message = 'The password confirmation does not match.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $statement = $conn->prepare('INSERT INTO user_tbl (username, password_hash, email) VALUES (?, ?, ?) RETURNING user_id');
                $statement->bind_param('sss', $username, $passwordHash, $email);
                $registeredUserId = 0;
                try {
                    $statement->execute();
                    $registeredUser = $statement->get_result()->fetch_assoc();
                    $registeredUserId = (int) ($registeredUser['user_id'] ?? 0);
                    if ($registeredUserId < 1) {
                        throw new RuntimeException('PostgreSQL did not return the new user ID.');
                    }
                } catch (PDOException $error) {
                    if ($error->getCode() !== '23505') {
                        throw $error;
                    }
                    $message = 'An account with this email already exists.';
                } finally {
                    $statement->close();
                }
                if ($registeredUserId > 0) {
                    $_SESSION['user_id'] = $registeredUserId;
                    $_SESSION['username'] = $username;
                    $_SESSION['user_role'] = 'user';
                    session_regenerate_id(true);
                    store_merge_guest_cart($conn, $registeredUserId);
                    header('Location: ' . ($returnTo === 'checkout' ? '/checkout' : '/home'));
                    exit;
                }
            }
        } else {
            $statement = $conn->prepare('SELECT user_id, username, password_hash, user_role FROM user_tbl WHERE LOWER(email) = LOWER(?) LIMIT 1');
            $statement->bind_param('s', $email);
            $statement->execute();
            $user = $statement->get_result()->fetch_assoc();
            $statement->close();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $message = 'Email or password is incorrect.';
                login_apply_failed_attempt_limit($conn, $message, $loginRateLimitSeconds);
            } else {
                store_reset_login_rate_limit($conn);
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['user_role'];
                store_merge_guest_cart($conn, (int) $user['user_id']);
                header('Location: ' . ($returnTo === 'checkout' ? '/checkout' : '/home'));
                exit;
            }
        }
    }
}

$pageTitle = $isRegistration
    ? 'Create Account | HoloVerse'
    : (($isPasswordResetRequest || $isPasswordReset) ? 'Reset Password | HoloVerse' : 'Sign In | HoloVerse');
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="auth-page home-landing">
    <div class="page-shell auth-page-shell">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = '';
        $showLogin = true;
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>
        <main class="auth-main landing-shell">
            <div class="auth-layout<?= $isRegistration ? ' is-register' : '' ?>" data-auth-layout>
                <section class="auth-login-panel<?= $isPasswordResetRequest ? ' reset-request-panel' : '' ?>" aria-label="<?= $isPasswordResetRequest || $isPasswordReset ? 'Password reset' : 'Sign in' ?>" aria-hidden="<?= $isRegistration ? 'true' : 'false' ?>"<?= $isRegistration ? ' inert' : '' ?>>
                    <div class="auth-panel-inner w-full max-w-md<?= $isPasswordResetRequest ? ' reset-request-layout' : '' ?>">
                        <?php if ($isPasswordReset && !$isResetPasswordVerified): ?>
                        <div class="otp-verification">
                        <?php endif; ?>
                        <a class="brand auth-card-brand" href="/home" aria-label="HoloVerse home">
                            <img class="brand-logo" src="<?= store_escape($siteRoot . 'assets/images/logo.png') ?>" alt="" draggable="false">
                            <span class="brand-wordmark">holo<span>verse</span></span>
                        </a>
                        <?php if ($isPasswordResetRequest): ?>
                            <header class="auth-header">
                                <p class="eyebrow">Account Recovery</p>
                                <h1>Forgot password?</h1>
                                <p class="auth-lead">Enter your account email and we’ll send you a one-time reset code.</p>
                            </header>
                            <?php if ($message !== ''): ?>
                                <p class="auth-alert auth-alert-error reset-request-error" role="alert"><?= store_escape($message) ?></p>
                            <?php endif; ?>
                            <form action="login<?= $returnTo === 'checkout' ? '?return_to=checkout' : '' ?>" method="post" class="reset-request-form space-y-5 auth-form">
                                <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                <input type="hidden" name="mode" value="forgot_password">
                                <input type="hidden" name="return_to" value="<?= store_escape($returnTo) ?>">
                                <div class="auth-field">
                                    <label for="reset-request-email" class="auth-label">Email</label>
                                    <input id="reset-request-email" type="email" name="email" autocomplete="email" placeholder="example@email.com" required value="<?= store_escape($resetRequestEmail) ?>"
                                        class="auth-input">
                                </div>
                                <button type="submit" class="auth-submit-button">
                                    Send reset code
                                </button>
                            </form>
                            <p class="auth-switch-prompt">
                                <a class="auth-mode-button" href="login<?= $returnTo === 'checkout' ? '?return_to=checkout' : '' ?>">Back to sign in</a>
                            </p>
                        <?php elseif ($isPasswordReset): ?>
                            <header class="auth-header">
                                <p class="eyebrow"><?= $isResetPasswordVerified ? 'Security Update' : 'One-Time Verification' ?></p>
                                <h1><?= $isResetPasswordVerified ? 'Choose a new password.' : 'Verify your email.' ?></h1>
                                <p class="auth-lead">
                                    <?php if ($isResetPasswordVerified): ?>
                                        Your code is verified. Set a new password for <?= store_escape($resetEmail) ?>.
                                    <?php else: ?>
                                        Enter the six-digit code sent to <?= $resetEmail !== '' ? store_escape($resetEmail) : 'your email' ?> to continue.
                                    <?php endif; ?>
                                </p>
                            </header>
                            <?php if ($message !== '' && ($isResetPasswordVerified || $resetEmail === '')): ?>
                                <p class="auth-alert auth-alert-error" role="alert"><?= store_escape($message) ?></p>
                            <?php endif; ?>
                            <?php if ($resetEmail !== ''): ?>
                                <?php if ($isResetPasswordVerified): ?>
                                <form action="login?mode=reset_password<?= $returnTo === 'checkout' ? '&return_to=checkout' : '' ?>" method="post" class="space-y-5 auth-form">
                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                    <input type="hidden" name="mode" value="reset_password">
                                    <input type="hidden" name="return_to" value="<?= store_escape($returnTo) ?>">
                                    <div class="auth-field">
                                        <label for="reset-password" class="auth-label">New password</label>
                                        <input id="reset-password" type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" placeholder="At least 12 characters" required aria-describedby="reset-password-strength-help reset-password-strength-label"
                                            class="auth-input">
                                    </div>
                                    <div class="password-strength" aria-live="polite">
                                        <div class="mb-2 flex items-center justify-between text-xs">
                                            <span id="reset-password-strength-help">Use at least 12 characters and 3 character types.</span>
                                            <span id="reset-password-strength-label" class="font-semibold text-slate-500">Strength: empty</span>
                                        </div>
                                        <div class="h-2 overflow-hidden bg-slate-200" role="progressbar" aria-label="Password strength" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0">
                                            <div id="reset-password-strength-bar" class="password-strength-bar h-full w-0 bg-rose-500 transition-all duration-300"></div>
                                        </div>
                                    </div>
                                    <div class="auth-field">
                                        <label for="reset-password-confirmation" class="auth-label">Confirm password</label>
                                        <input id="reset-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" placeholder="Re-enter your password" required aria-describedby="reset-password-confirmation-message"
                                            class="auth-input">
                                        <p id="reset-password-confirmation-message" class="mt-2 min-h-4 text-xs text-rose-600" aria-live="polite"></p>
                                    </div>
                                    <button id="reset-password-submit" type="submit" class="auth-submit-button disabled:cursor-not-allowed disabled:opacity-50">
                                        Reset password
                                    </button>
                                </form>
                                <?php else: ?>
                                <form action="login?mode=verify_reset_otp<?= $returnTo === 'checkout' ? '&return_to=checkout' : '' ?>" method="post" class="otp-verify-form space-y-5 auth-form">
                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                    <input type="hidden" name="mode" value="verify_reset_otp">
                                    <input type="hidden" name="return_to" value="<?= store_escape($returnTo) ?>">
                                    <div class="auth-field">
                                        <label for="reset-otp" class="auth-label">One-time code</label>
                                        <div class="otp-entry">
                                            <input id="reset-otp" class="otp-input auth-input" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required aria-label="Six-digit one-time code">
                                            <div class="otp-boxes" aria-hidden="true">
                                                <span class="otp-digit-box"></span>
                                                <span class="otp-digit-box"></span>
                                                <span class="otp-digit-box"></span>
                                                <span class="otp-digit-box"></span>
                                                <span class="otp-digit-box"></span>
                                                <span class="otp-digit-box"></span>
                                            </div>
                                        </div>
                                        <div class="otp-meta-row">
                                            <p class="otp-timer">Remaining time: <span id="reset-otp-timer" data-expires-at="<?= $resetOtpExpiresAt ?>">--:--</span></p>
                                            <p class="otp-resend-prompt">Didn't get the code? <button type="submit" class="otp-resend-link" formaction="login?mode=resend_reset_otp<?= $returnTo === 'checkout' ? '&return_to=checkout' : '' ?>" formmethod="post" formnovalidate>Resend</button></p>
                                        </div>
                                    </div>
                                    <button type="submit" class="otp-verify-button auth-submit-button">
                                        Verify code
                                    </button>
                                </form>
                                <div class="otp-feedback-row">
                                    <?php if ($message !== ''): ?>
                                        <p class="otp-error" role="alert"><?= store_escape($message) ?></p>
                                    <?php endif; ?>
                                    <p class="otp-signin-link">
                                        <a class="auth-mode-button" href="login<?= $returnTo === 'checkout' ? '?return_to=checkout' : '' ?>">Back to sign in</a>
                                    </p>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="auth-alert auth-alert-error" role="alert">Start a new password reset request to receive a code.</p>
                            <?php endif; ?>
                            <?php if (!$isResetPasswordVerified): ?>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                        <header class="auth-header">
                            <p class="eyebrow">Official Member Sign In</p>
                            <h1>Welcome back.</h1>
                            <p class="auth-lead">Sign in to manage your HoloVerse account and saved cart.</p>
                        </header>

                        <?php if ($notice !== ''): ?>
                            <p class="auth-alert auth-alert-success" role="status"><?= store_escape($notice) ?></p>
                        <?php endif; ?>
                        <form action="login" method="post" class="login-form space-y-5 auth-form">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="mode" value="login">
                            <input type="hidden" name="return_to" value="<?= store_escape($returnTo) ?>">
                            <div class="auth-field">
                                <label for="login-email" class="auth-label">Email</label>
                                <input id="login-email" type="email" name="email" autocomplete="email" placeholder="example@email.com" required
                                    class="auth-input">
                            </div>
                            <div class="auth-field">
                                <label for="login-password" class="auth-label">Password</label>
                                <input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Your password" required
                                    class="auth-input">
                            </div>
                            <div class="login-feedback-row">
                                <?php if ($message !== '' && !$isRegistration): ?>
                                    <div class="login-feedback space-y-1">
                                        <p class="auth-error-message otp-error" role="alert" data-retry-after="<?= $loginRateLimitSeconds ?>"><?= store_escape($message) ?></p>
                                        <p class="login-retry-timer otp-timer"<?= $loginRateLimitSeconds > 0 ? '' : ' hidden' ?>>Try again in <span>--:--</span>.</p>
                                    </div>
                                <?php endif; ?>
                                <p class="login-forgot-password">
                                    <a class="auth-mode-button" href="login?mode=forgot_password<?= $returnTo === 'checkout' ? '&return_to=checkout' : '' ?>">Forgot password?</a>
                                </p>
                            </div>
                            <button type="submit" class="auth-submit-button">
                                Sign in
                            </button>
                        </form>

                        <p class="auth-switch-prompt">
                            Don’t have an account?
                            <button type="button" class="auth-mode-button" data-show-register>Create account</button>
                        </p>
                        <?php endif; ?>
                    </div>
                </section>

                <aside class="auth-artwork-panel" aria-label="Official Collector Access">
                    <img src="<?= store_escape($siteRoot . 'assets/images/suisei2.jpg') ?>" alt="Featured Talent Hoshimachi Suisei" draggable="false" class="auth-artwork-image">
                    <div class="auth-artwork-shade"></div>
                    <div class="auth-artwork-body">
                    </div>
                </aside>
                <section class="auth-register-surface" aria-label="Create account" aria-hidden="<?= $isRegistration ? 'false' : 'true' ?>"<?= $isRegistration ? '' : ' inert' ?>>
                    <div class="auth-register-content">
                        <a class="brand auth-card-brand" href="/home" aria-label="HoloVerse home">
                            <img class="brand-logo" src="<?= store_escape($siteRoot . 'assets/images/logo.png') ?>" alt="" draggable="false">
                            <span class="brand-wordmark">holo<span>verse</span></span>
                        </a>
                        <header class="auth-header">
                            <p class="eyebrow">New Collector Registration</p>
                            <h2>Create your account.</h2>
                            <p class="auth-lead">Create an account to keep your cart, favorites, and showcase.</p>
                        </header>

                        <?php if ($message !== '' && $isRegistration): ?>
                            <p class="auth-alert auth-alert-error" role="alert"><?= store_escape($message) ?></p>
                        <?php endif; ?>

                        <form action="login?mode=register" method="post" class="space-y-5 auth-form">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="mode" value="register">
                            <input type="hidden" name="return_to" value="<?= store_escape($returnTo) ?>">
                            <div class="auth-field">
                                <label for="register-username" class="auth-label">Name</label>
                                <input id="register-username" type="text" name="username" autocomplete="name" minlength="2" maxlength="50" required value="<?= store_escape($registrationName) ?>"
                                    class="auth-input">
                            </div>
                            <div class="auth-field">
                                <label for="register-email" class="auth-label">Email</label>
                                <input id="register-email" type="email" name="email" autocomplete="email" placeholder="example@email.com" required value="<?= store_escape($registrationEmail) ?>"
                                    class="auth-input">
                            </div>
                            <div class="auth-field">
                                <label for="register-password" class="auth-label">Password</label>
                                <input id="register-password" type="password" name="password" autocomplete="new-password" placeholder="At least 12 characters" minlength="12" maxlength="72" required aria-describedby="password-strength-help password-strength-label"
                                    class="auth-input">
                            </div>
                            <div class="password-strength" aria-live="polite">
                                <div class="mb-2 flex items-center justify-between text-xs">
                                    <span id="password-strength-help">Use at least 12 characters and 3 character types.</span>
                                    <span id="password-strength-label" class="font-semibold text-slate-500">Strength: empty</span>
                                </div>
                                <div class="h-2 overflow-hidden bg-slate-200" role="progressbar" aria-label="Password strength" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0">
                                    <div class="password-strength-bar h-full w-0 bg-rose-500 transition-all duration-300"></div>
                                </div>
                            </div>
                            <div class="auth-field">
                                <label for="register-password-confirmation" class="auth-label">Confirm password</label>
                                <input id="register-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Re-enter your password" required aria-describedby="password-confirmation-message"
                                    class="auth-input">
                                <p id="password-confirmation-message" class="mt-2 min-h-4 text-xs text-rose-600" aria-live="polite"></p>
                            </div>
                            <button id="register-submit" type="submit" class="auth-submit-button disabled:cursor-not-allowed disabled:opacity-50">
                                Create account
                            </button>
                        </form>
                        <p class="auth-register-footer auth-switch-prompt">
                            Already have an account?
                            <button type="button" class="auth-mode-button" data-show-login>Sign in</button>
                        </p>
                    </div>
                </section>
            </div>
        </main>
    <script>
        const authShell = document.querySelector(".auth-layout");
        const loginPanel = document.querySelector(".auth-login-panel");
        const registerPanel = document.querySelector(".auth-register-surface");
        const registerForm = registerPanel.querySelector("form");
        const passwordInput = registerPanel.querySelector("#register-password");
        const confirmationInput = registerPanel.querySelector("#register-password-confirmation");
        const submitButton = registerPanel.querySelector("#register-submit");
        const strengthMeter = registerPanel.querySelector('[role="progressbar"]');
        const strengthBar = registerPanel.querySelector(".password-strength-bar");
        const strengthLabel = registerPanel.querySelector("#password-strength-label");
        const confirmationMessage = registerPanel.querySelector("#password-confirmation-message");
        let otpTimerInterval = null;
        const setupOtpControls = (panel) => {
            const otpInput = panel.querySelector("#reset-otp");
            const otpTimer = panel.querySelector("#reset-otp-timer");
            if (otpTimer) {
                if (otpTimerInterval !== null) {
                    window.clearInterval(otpTimerInterval);
                }
                const expiresAt = Number(otpTimer.dataset.expiresAt);
                const updateOtpTimer = () => {
                    const remainingSeconds = Math.max(0, expiresAt - Math.floor(Date.now() / 1000));
                    const minutes = Math.floor(remainingSeconds / 60);
                    const seconds = remainingSeconds % 60;
                    otpTimer.textContent = `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
                };

                updateOtpTimer();
                otpTimerInterval = window.setInterval(() => {
                    updateOtpTimer();
                    if (expiresAt <= Math.floor(Date.now() / 1000)) {
                        window.clearInterval(otpTimerInterval);
                        otpTimerInterval = null;
                    }
                }, 1000);
            }
            if (!otpInput) {
                return;
            }

            const otpEntry = otpInput.closest(".otp-entry");
            const otpBoxes = otpEntry.querySelectorAll(".otp-digit-box");
            otpEntry.classList.add("is-enhanced");

            const updateOtpBoxes = () => {
                const digits = otpInput.value.replace(/\D/g, "").slice(0, otpBoxes.length);
                if (digits !== otpInput.value) {
                    otpInput.value = digits;
                }

                otpBoxes.forEach((box, index) => {
                    box.textContent = digits[index] || "";
                    box.classList.toggle("is-current", otpEntry.classList.contains("is-focused") && index === Math.min(digits.length, otpBoxes.length - 1));
                });
            };

            otpInput.addEventListener("input", updateOtpBoxes);
            otpInput.addEventListener("focus", () => {
                otpEntry.classList.add("is-focused");
                otpInput.setSelectionRange(otpInput.value.length, otpInput.value.length);
                updateOtpBoxes();
            });
            otpInput.addEventListener("click", () => {
                otpInput.setSelectionRange(otpInput.value.length, otpInput.value.length);
            });
            otpInput.addEventListener("blur", () => {
                otpEntry.classList.remove("is-focused");
                updateOtpBoxes();
            });
            updateOtpBoxes();
        };
        setupOtpControls(loginPanel);

        const authErrorDismissTimers = new WeakMap();
        const dismissAuthErrorAfterDelay = (errorNotice) => {
            const currentTimer = authErrorDismissTimers.get(errorNotice);
            if (currentTimer) {
                window.clearTimeout(currentTimer);
            }

            const timer = window.setTimeout(() => {
                errorNotice.hidden = true;
                authErrorDismissTimers.delete(errorNotice);
            }, 5000);
            authErrorDismissTimers.set(errorNotice, timer);
        };

        const startLoginRateLimitTimer = (form, errorNotice, retryAfter) => {
            const seconds = Number(retryAfter);
            const retryTimer = errorNotice.parentElement.querySelector(".login-retry-timer");
            if (!Number.isFinite(seconds) || seconds <= 0) {
                if (retryTimer) {
                    retryTimer.hidden = true;
                }
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            const timerDisplay = retryTimer?.querySelector("span");
            if (!retryTimer || !timerDisplay) {
                return;
            }
            retryTimer.hidden = false;
            const deadline = Date.now() + seconds * 1000;
            const updateTimer = () => {
                const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                if (remaining === 0) {
                    errorNotice.textContent = "You can try signing in again now.";
                    errorNotice.dataset.retryAfter = "0";
                    retryTimer.hidden = true;
                    submitButton.disabled = false;
                    return false;
                }

                const minutes = Math.floor(remaining / 60);
                const secondsRemaining = remaining % 60;
                timerDisplay.textContent = `${minutes}:${String(secondsRemaining).padStart(2, "0")}`;
                submitButton.disabled = true;
                return true;
            };

            if (updateTimer()) {
                const timer = window.setInterval(() => {
                    if (!updateTimer()) {
                        window.clearInterval(timer);
                    }
                }, 1000);
            }
        };

        const loginForm = loginPanel.querySelector(".login-form");
        const initialLoginError = loginPanel.querySelector(".auth-error-message");
        if (loginForm && initialLoginError) {
            dismissAuthErrorAfterDelay(initialLoginError);
            startLoginRateLimitTimer(loginForm, initialLoginError, initialLoginError.dataset.retryAfter);
        }
        loginPanel.querySelectorAll(".otp-feedback-row .otp-error").forEach(dismissAuthErrorAfterDelay);

        loginPanel.addEventListener("submit", async (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            const submitter = event.submitter;
            const isResetRequest = form.matches(".reset-request-form");
            const isLogin = form.matches(".login-form");
            const isResendRequest = submitter instanceof HTMLButtonElement
                && submitter.formAction.includes("mode=resend_reset_otp");
            const isOtpVerification = form.matches(".otp-verify-form");
            const isOtpAction = isOtpVerification || isResendRequest;
            if (!isLogin && !isResetRequest && !isOtpAction) {
                return;
            }

            event.preventDefault();
            const submitButton = submitter instanceof HTMLButtonElement ? submitter : form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = isLogin ? "Signing in..." : isOtpVerification ? "Verifying..." : "Sending...";
            form.setAttribute("aria-busy", "true");

            try {
                const formData = new FormData(form);
                if (isResendRequest) {
                    formData.delete("otp");
                }
                const response = await fetch(submitter?.formAction || form.action, {
                    method: submitter?.formMethod || form.method,
                    body: formData,
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                    redirect: "follow",
                });
                const responseText = await response.text();
                if (isLogin) {
                    if (response.redirected) {
                        window.location.assign(response.url);
                        return;
                    }

                    const responseDocument = new DOMParser().parseFromString(responseText, "text/html");
                    const responseError = responseDocument.querySelector(".auth-login-panel .auth-error-message");
                    if (!responseError) {
                        throw new Error("We couldn't process your sign-in. Please try again.");
                    }

                    const passwordInput = form.querySelector('input[name="password"]');
                    if (passwordInput instanceof HTMLInputElement) {
                        passwordInput.value = "";
                    }
                    let feedback = form.querySelector(".login-feedback");
                    if (!feedback) {
                        feedback = document.createElement("div");
                        feedback.className = "login-feedback space-y-1";
                        form.querySelector(".login-feedback-row").prepend(feedback);
                    }
                    let errorNotice = form.parentElement.querySelector(".auth-error-message");
                    if (!errorNotice) {
                        errorNotice = document.createElement("p");
                        errorNotice.className = "auth-error-message otp-error";
                        errorNotice.setAttribute("role", "alert");
                        feedback.append(errorNotice);
                    } else if (errorNotice.parentElement !== feedback) {
                        feedback.append(errorNotice);
                    }
                    let retryTimer = feedback.querySelector(".login-retry-timer");
                    if (!retryTimer) {
                        retryTimer = document.createElement("p");
                        retryTimer.className = "login-retry-timer otp-timer";
                        retryTimer.innerHTML = 'Try again in <span>--:--</span>.';
                        feedback.append(retryTimer);
                    }
                    errorNotice.textContent = responseError.textContent;
                    errorNotice.hidden = false;
                    errorNotice.dataset.retryAfter = responseError.dataset.retryAfter || "0";
                    dismissAuthErrorAfterDelay(errorNotice);
                    retryTimer.hidden = Number(errorNotice.dataset.retryAfter) <= 0;
                    startLoginRateLimitTimer(form, errorNotice, errorNotice.dataset.retryAfter);
                    if (!response.ok && response.status !== 429) {
                        throw new Error(responseError.textContent || "Unable to sign in. Please try again.");
                    }
                    if (Number(errorNotice.dataset.retryAfter) <= 0) {
                        submitButton.disabled = false;
                    }
                    submitButton.textContent = originalButtonText;
                    form.removeAttribute("aria-busy");
                    passwordInput?.focus();
                    return;
                }

                if (!response.ok) {
                    throw new Error(isOtpVerification
                        ? "Unable to verify the code. Please try again."
                        : "Unable to send the reset code. Please refresh and try again.");
                }

                const responseDocument = new DOMParser().parseFromString(responseText, "text/html");
                const nextPanelContent = responseDocument.querySelector(".auth-login-panel > div");
                if (!nextPanelContent) {
                    throw new Error("Unable to load the verification step. Please try again.");
                }

                const currentPanelContent = loginPanel.querySelector(":scope > div");
                currentPanelContent.innerHTML = nextPanelContent.innerHTML;
                currentPanelContent.classList.toggle("reset-request-layout", nextPanelContent.classList.contains("reset-request-layout"));
                loginPanel.classList.toggle("reset-request-panel", responseDocument.querySelector(".auth-login-panel").classList.contains("reset-request-panel"));
                loginPanel.setAttribute("aria-label", responseDocument.querySelector(".auth-login-panel").getAttribute("aria-label"));
                if (response.redirected) {
                    const currentUrl = new URL(window.location.href);
                    const nextUrl = new URL(response.url);
                    const updateHistory = currentUrl.searchParams.get("mode") === "reset_password"
                        ? window.history.replaceState
                        : window.history.pushState;
                    updateHistory.call(window.history, { authMode: "reset_password" }, "", nextUrl);
                }
                setupOtpControls(loginPanel);
                const otpError = loginPanel.querySelector(".otp-feedback-row .otp-error");
                if (otpError) {
                    otpError.hidden = false;
                    dismissAuthErrorAfterDelay(otpError);
                }
                loginPanel.querySelector("#reset-otp")?.focus();
            } catch (error) {
                const errorMessage = error instanceof Error
                    ? error.message
                    : isLogin
                        ? "We couldn't process your sign-in. Please try again."
                        : isOtpAction
                        ? "Unable to verify the code. Please try again."
                        : "Unable to send the reset code. Please try again.";
                let errorNotice = isLogin
                    ? form.parentElement.querySelector(".auth-error-message")
                    : form.parentElement.querySelector(".reset-request-error, .otp-feedback-row .otp-error");
                if (!errorNotice) {
                    errorNotice = document.createElement("p");
                    errorNotice.className = isLogin
                        ? "auth-error-message otp-error"
                        : isResetRequest ? "reset-request-error mb-5 text-sm text-rose-700" : "otp-error";
                    errorNotice.setAttribute("role", "alert");
                    if (isLogin) {
                        let feedback = form.querySelector(".login-feedback");
                        if (!feedback) {
                            feedback = document.createElement("div");
                            feedback.className = "login-feedback space-y-1";
                            form.querySelector(".login-feedback-row").prepend(feedback);
                        }
                        feedback.append(errorNotice);
                    } else if (isResetRequest) {
                        form.before(errorNotice);
                    } else {
                        const feedbackRow = form.parentElement.querySelector(".otp-feedback-row");
                        feedbackRow.insertBefore(errorNotice, feedbackRow.querySelector(".otp-signin-link"));
                    }
                }
                errorNotice.textContent = errorMessage;
                errorNotice.hidden = false;
                if (isLogin || isOtpAction) {
                    dismissAuthErrorAfterDelay(errorNotice);
                }
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
                form.removeAttribute("aria-busy");
            }
        });
        const setupPasswordFeedback = (passwordInput, confirmationInput, submitButton, strengthMeter, strengthBar, strengthLabel, confirmationMessage) => {
            if (!passwordInput || !confirmationInput || !submitButton || !strengthMeter || !strengthBar || !strengthLabel || !confirmationMessage) {
                return;
            }

            const updatePasswordFeedback = () => {
                const password = passwordInput.value;
                const passwordLength = new TextEncoder().encode(password).length;
                const checks = [
                    passwordLength >= 12 && passwordLength <= 72,
                    /[a-z]/.test(password),
                    /[A-Z]/.test(password),
                    /[0-9]/.test(password),
                    /[^a-zA-Z0-9]/.test(password),
                ];
                const score = checks.filter(Boolean).length;
                const isStrong = checks[0] && score >= 4;
                const strength = password.length === 0 ? "empty" : isStrong ? "strong" : score >= 3 ? "medium" : "weak";
                const widths = { empty: 0, weak: 25, medium: 55, strong: 100 };
                const colors = { empty: "bg-slate-300", weak: "bg-rose-500", medium: "bg-amber-500", strong: "bg-emerald-500" };

                strengthBar.style.width = `${widths[strength]}%`;
                strengthBar.className = `password-strength-bar h-full rounded-full transition-all duration-300 ${colors[strength]}`;
                strengthMeter.setAttribute("aria-valuenow", String(score));
                strengthLabel.textContent = `Strength: ${strength}`;
                strengthLabel.className = `font-semibold ${strength === "strong" ? "text-emerald-700" : strength === "medium" ? "text-amber-700" : strength === "empty" ? "text-slate-500" : "text-rose-700"}`;

                const passwordsMatch = confirmationInput.value !== "" && password === confirmationInput.value;
                confirmationMessage.textContent = confirmationInput.value && !passwordsMatch ? "Passwords do not match." : "";
                confirmationInput.setCustomValidity(confirmationInput.value && !passwordsMatch ? "Passwords do not match." : "");
                submitButton.disabled = !isStrong || !passwordsMatch;
            };

            passwordInput.addEventListener("input", updatePasswordFeedback);
            confirmationInput.addEventListener("input", updatePasswordFeedback);
            updatePasswordFeedback();

            passwordInput.form?.addEventListener("submit", (event) => {
                updatePasswordFeedback();
                if (submitButton.disabled) {
                    event.preventDefault();
                    passwordInput.form.reportValidity();
                }
            });
        };

        setupPasswordFeedback(
            passwordInput,
            confirmationInput,
            submitButton,
            strengthMeter,
            strengthBar,
            strengthLabel,
            confirmationMessage
        );
        setupPasswordFeedback(
            loginPanel.querySelector("#reset-password"),
            loginPanel.querySelector("#reset-password-confirmation"),
            loginPanel.querySelector("#reset-password-submit"),
            loginPanel.querySelector("#reset-password")?.closest("form")?.querySelector('[role="progressbar"]'),
            loginPanel.querySelector("#reset-password-strength-bar"),
            loginPanel.querySelector("#reset-password-strength-label"),
            loginPanel.querySelector("#reset-password-confirmation-message")
        );

        const setAuthMode = (showRegister) => {
            authShell.classList.toggle("is-register", showRegister);
            loginPanel.setAttribute("aria-hidden", String(showRegister));
            loginPanel.inert = showRegister;
            registerPanel.setAttribute("aria-hidden", String(!showRegister));
            registerPanel.inert = !showRegister;
            if (showRegister) {
                document.querySelector("#register-username").focus();
            } else {
                document.querySelector("#login-email").focus();
            }
        };

        document.querySelectorAll("[data-show-register]").forEach((button) => {
            button.addEventListener("click", () => {
                const params = new URLSearchParams(window.location.search);
                params.set("mode", "register");
                window.history.pushState({ authMode: "register" }, "", `?${params.toString()}`);
                setAuthMode(true);
            });
        });
        document.querySelectorAll("[data-show-login]").forEach((button) => {
            button.addEventListener("click", () => {
                const params = new URLSearchParams(window.location.search);
                params.delete("mode");
                const query = params.toString();
                window.history.pushState({ authMode: "login" }, "", `${window.location.pathname}${query ? `?${query}` : ""}`);
                setAuthMode(false);
            });
        });
        window.addEventListener("popstate", () => setAuthMode(new URLSearchParams(window.location.search).get("mode") === "register"));
    </script>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    </div>
</body>

</html>
