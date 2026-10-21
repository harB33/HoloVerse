<?php
require_once __DIR__ . '/../app/store.php';

$requestMode = $_GET['mode'] ?? $_POST['mode'] ?? '';
$requestMode = is_string($requestMode) ? $requestMode : '';
$isRegistration = $requestMode === 'register';
$isPasswordResetRequest = $requestMode === 'forgot_password';
$isPasswordReset = in_array($requestMode, ['reset_password', 'verify_reset_otp'], true);
$isOtpVerification = $requestMode === 'verify_reset_otp';
$returnTo = ($_GET['return_to'] ?? $_POST['return_to'] ?? '') === 'checkout' ? 'checkout' : '';
$message = '';
$notice = is_string($_SESSION['auth_notice'] ?? null) ? $_SESSION['auth_notice'] : '';
unset($_SESSION['auth_notice']);
$registrationName = '';
$registrationEmail = '';
$resetRequestEmail = '';
$resetEmail = is_string($_SESSION['password_reset_email'] ?? null)
    ? $_SESSION['password_reset_email']
    : '';
$resetEmailHash = $resetEmail !== '' ? hash('sha256', strtolower(trim($resetEmail))) : '';
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

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif ($isPasswordResetRequest) {
        $emailInput = $_POST['email'] ?? '';
        $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
        $resetRequestEmail = $email;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
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
            unset($_SESSION['password_reset_verified']);
            $_SESSION['auth_notice'] = 'If an account exists for that email, we’ll send a reset code. Check your spam folder if you do not see it.';
            header('Location: login.php?mode=reset_password' . ($returnTo === 'checkout' ? '&return_to=checkout' : ''));
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
                    $message = 'That code is invalid or expired. Request a new code and try again.';
                } elseif (!password_verify($otp, $reset['otp_hash'])) {
                    $attemptStatement = $conn->prepare(
                        'UPDATE password_reset_codes SET attempt_count = attempt_count + 1 WHERE email_hash = ?'
                    );
                    $attemptStatement->bind_param('s', $emailHash);
                    $attemptStatement->execute();
                    $attemptStatement->close();
                    $conn->commit();
                    $message = 'That code is invalid or expired. Request a new code and try again.';
                } else {
                    $conn->commit();
                    $_SESSION['password_reset_verified'] = [
                        'email_hash' => $emailHash,
                        'otp_hash' => $reset['otp_hash'],
                    ];
                    header('Location: login.php?mode=reset_password' . ($returnTo === 'checkout' ? '&return_to=checkout' : ''));
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
            $message = 'Verify your reset code before choosing a new password.';
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
                    $message = 'That code is invalid or expired. Request a new code and try again.';
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

                    unset($_SESSION['password_reset_email'], $_SESSION['password_reset_verified']);
                    $_SESSION['auth_notice'] = 'Your password has been reset. Sign in with your new password.';
                    header('Location: login.php' . ($returnTo === 'checkout' ? '?return_to=checkout' : ''));
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

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || $password === '') {
            $message = 'Enter a valid email and password.';
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
                    header('Location: ' . ($returnTo === 'checkout' ? '/pages/checkout.php' : '/index.php'));
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
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['user_role'];
                store_merge_guest_cart($conn, (int) $user['user_id']);
                header('Location: ' . ($returnTo === 'checkout' ? '/pages/checkout.php' : '/index.php'));
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

<body class="auth-page relative isolate min-h-screen border-t-2 border-sky-500 bg-transparent font-['Kanit'] text-slate-800">
    <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
    <main class="auth-main flex min-h-screen w-full items-center justify-center px-4 py-4 sm:px-8 lg:px-6">
        <div class="auth-layout<?= $isRegistration ? ' is-register' : '' ?> relative grid min-h-0 w-full max-w-7xl grid-cols-1 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-300/40 lg:grid-cols-2">
            <section class="auth-login-panel relative flex flex-col justify-center py-10 lg:pl-12 xl:pl-20" aria-label="<?= $isPasswordResetRequest || $isPasswordReset ? 'Password reset' : 'Sign in' ?>" aria-hidden="<?= $isRegistration ? 'true' : 'false' ?>"<?= $isRegistration ? ' inert' : '' ?>>
                <div class="w-full max-w-md">
                    <a class="brand mb-6 rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-500" href="/index.php" aria-label="HoloVerse home">
                        <img class="brand-logo" src="../assets/images/logo.png" alt="" draggable="false">
                        <span class="brand-wordmark">holo<span>verse</span></span>
                    </a>
                    <?php if ($isPasswordResetRequest): ?>
                        <header class="mb-8">
                            <h1 class="text-3xl font-bold text-slate-800">Forgot password?</h1>
                            <p class="mt-4 max-w-sm text-sm leading-6 text-slate-600 sm:text-base">Enter your account email and we’ll send you a one-time reset code.</p>
                        </header>
                        <?php if ($message !== ''): ?>
                            <p class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= store_escape($message) ?></p>
                        <?php endif; ?>
                        <form action="login.php<?= $returnTo === 'checkout' ? '?return_to=checkout' : '' ?>" method="post" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="mode" value="forgot_password">
                            <input type="hidden" name="return_to" value="<?= store_escape($returnTo) ?>">
                            <div>
                                <label for="reset-request-email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                                <input id="reset-request-email" type="email" name="email" autocomplete="email" placeholder="Example@email.com" required value="<?= store_escape($resetRequestEmail) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-slate-800 px-5 py-3 text-center text-base font-medium text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                Send reset code
                            </button>
                        </form>
                        <p class="mt-7 text-center text-sm text-slate-600">
                            <a class="auth-mode-button" href="login.php<?= $returnTo === 'checkout' ? '?return_to=checkout' : '' ?>">Back to sign in</a>
                        </p>
                    <?php elseif ($isPasswordReset): ?>
                        <header class="mb-8">
                            <h1 class="text-3xl font-bold text-slate-800"><?= $isResetPasswordVerified ? 'Choose a new password' : 'Verify your email' ?></h1>
                            <p class="mt-4 max-w-sm text-sm leading-6 text-slate-600 sm:text-base">
                                <?php if ($isResetPasswordVerified): ?>
                                    Your code is verified. Set a new password for <?= store_escape($resetEmail) ?>.
                                <?php else: ?>
                                    Enter the six-digit code sent to <?= $resetEmail !== '' ? store_escape($resetEmail) : 'your email' ?> to continue.
                                <?php endif; ?>
                            </p>
                        </header>
                        <?php if ($message !== ''): ?>
                            <p class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= store_escape($message) ?></p>
                        <?php endif; ?>
                        <?php if ($resetEmail !== ''): ?>
                            <?php if ($isResetPasswordVerified): ?>
                            <form action="login.php?mode=reset_password<?= $returnTo === 'checkout' ? '&return_to=checkout' : '' ?>" method="post" class="space-y-5">
                                <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                <input type="hidden" name="mode" value="reset_password">
                                <input type="hidden" name="return_to" value="<?= store_escape($returnTo) ?>">
                                <div>
                                    <label for="reset-password" class="mb-2 block text-sm font-medium text-slate-700">New password</label>
                                    <input id="reset-password" type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" placeholder="At least 12 characters" required aria-describedby="reset-password-strength-help reset-password-strength-label"
                                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                </div>
                                <div class="password-strength" aria-live="polite">
                                    <div class="mb-2 flex items-center justify-between text-xs">
                                        <span id="reset-password-strength-help" class="text-slate-500">Use at least 12 characters and 3 character types.</span>
                                        <span id="reset-password-strength-label" class="font-semibold text-slate-500">Strength: empty</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Password strength" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0">
                                        <div id="reset-password-strength-bar" class="password-strength-bar h-full w-0 rounded-full bg-rose-500 transition-all duration-300"></div>
                                    </div>
                                </div>
                                <div>
                                    <label for="reset-password-confirmation" class="mb-2 block text-sm font-medium text-slate-700">Confirm password</label>
                                    <input id="reset-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" placeholder="Re-enter your password" required aria-describedby="reset-password-confirmation-message"
                                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <p id="reset-password-confirmation-message" class="mt-2 min-h-4 text-xs text-rose-600" aria-live="polite"></p>
                                </div>
                                <button id="reset-password-submit" type="submit" class="w-full rounded-lg bg-slate-800 px-5 py-3 text-center text-base font-medium text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200 disabled:cursor-not-allowed disabled:opacity-50">
                                    Reset password
                                </button>
                            </form>
                            <?php else: ?>
                            <form action="login.php?mode=verify_reset_otp<?= $returnTo === 'checkout' ? '&return_to=checkout' : '' ?>" method="post" class="space-y-5">
                                <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                <input type="hidden" name="mode" value="verify_reset_otp">
                                <input type="hidden" name="return_to" value="<?= store_escape($returnTo) ?>">
                                <div>
                                    <label for="reset-otp" class="mb-2 block text-sm font-medium text-slate-700">One-time code</label>
                                    <input id="reset-otp" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit code" required
                                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm tracking-[0.3em] text-slate-800 shadow-sm outline-none transition placeholder:tracking-normal placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                </div>
                                <button type="submit" class="w-full rounded-lg bg-slate-800 px-5 py-3 text-center text-base font-medium text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                    Verify code
                                </button>
                            </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">Start a new password reset request to receive a code.</p>
                        <?php endif; ?>
                        <p class="mt-7 text-center text-sm text-slate-600">
                            <a class="auth-mode-button" href="login.php?mode=forgot_password<?= $returnTo === 'checkout' ? '&return_to=checkout' : '' ?>">Request another code</a>
                        </p>
                    <?php else: ?>
                    <header class="mb-8">
                        <h1 class="text-3xl font-bold text-slate-800">Welcome back</h1>
                        <p class="mt-4 max-w-sm text-sm leading-6 text-slate-600 sm:text-base">Sign in to manage your HoloVerse account and saved cart.</p>
                    </header>

                    <?php if ($notice !== ''): ?>
                        <p class="mb-5 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status"><?= store_escape($notice) ?></p>
                    <?php endif; ?>
                    <?php if ($message !== '' && !$isRegistration): ?>
                        <p class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= store_escape($message) ?></p>
                    <?php endif; ?>

                    <form action="login.php" method="post" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                        <input type="hidden" name="mode" value="login">
                        <input type="hidden" name="return_to" value="<?= store_escape($returnTo) ?>">
                        <div>
                            <label for="login-email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                            <input id="login-email" type="email" name="email" autocomplete="email" placeholder="Example@email.com" required
                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        </div>
                        <div>
                            <label for="login-password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                            <input id="login-password" type="password" name="password" autocomplete="current-password" placeholder="Your password" required
                                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        </div>
                        <p class="-mt-2 text-right text-sm">
                            <a class="auth-mode-button" href="login.php?mode=forgot_password<?= $returnTo === 'checkout' ? '&return_to=checkout' : '' ?>">Forgot password?</a>
                        </p>
                        <button type="submit" class="w-full rounded-lg bg-slate-800 px-5 py-3 text-center text-base font-medium text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200">
                            Sign in
                        </button>
                    </form>

                    <p class="mt-9 text-center text-sm text-slate-600">
                        Don’t have an account?
                        <button type="button" class="auth-mode-button" data-show-register>Create account</button>
                    </p>
                    <?php endif; ?>
                </div>
            </section>

            <aside class="auth-artwork-panel relative min-h-[420px] overflow-hidden rounded-2xl shadow-sm lg:min-h-full" aria-label="Create account">
                <img src="../assets/images/suisei2.jpg" alt="" draggable="false" class="absolute inset-0 h-full w-full object-cover object-[center_70%]">
            </aside>
                <section class="auth-register-surface" aria-label="Create account" aria-hidden="<?= $isRegistration ? 'false' : 'true' ?>"<?= $isRegistration ? '' : ' inert' ?>>
                    <div class="auth-register-content">
                        <a class="brand mb-6 rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-500" href="/index.php" aria-label="HoloVerse home">
                            <img class="brand-logo" src="../assets/images/logo.png" alt="" draggable="false">
                            <span class="brand-wordmark">holo<span>verse</span></span>
                        </a>
                        <header class="mb-4">
                            <h2 class="text-3xl font-bold text-slate-800">Create your account</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Create an account to keep your cart with you.</p>
                        </header>

                        <?php if ($message !== '' && $isRegistration): ?>
                            <p class="mb-5 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= store_escape($message) ?></p>
                        <?php endif; ?>

                        <form action="login.php?mode=register" method="post" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="mode" value="register">
                            <input type="hidden" name="return_to" value="<?= store_escape($returnTo) ?>">
                            <div>
                                <label for="register-username" class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                                <input id="register-username" type="text" name="username" autocomplete="name" minlength="2" maxlength="50" required value="<?= store_escape($registrationName) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </div>
                            <div>
                                <label for="register-email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                                <input id="register-email" type="email" name="email" autocomplete="email" placeholder="Example@email.com" required value="<?= store_escape($registrationEmail) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </div>
                            <div>
                                <label for="register-password" class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                                <input id="register-password" type="password" name="password" autocomplete="new-password" placeholder="At least 12 characters" minlength="12" maxlength="72" required aria-describedby="password-strength-help password-strength-label"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </div>
                            <div class="password-strength" aria-live="polite">
                                <div class="mb-2 flex items-center justify-between text-xs">
                                    <span id="password-strength-help" class="text-slate-500">Use at least 12 characters and 3 character types.</span>
                                    <span id="password-strength-label" class="font-semibold text-slate-500">Strength: empty</span>
                                </div>
                                <div class="h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Password strength" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0">
                                    <div class="password-strength-bar h-full w-0 rounded-full bg-rose-500 transition-all duration-300"></div>
                                </div>
                            </div>
                            <div>
                                <label for="register-password-confirmation" class="mb-2 block text-sm font-medium text-slate-700">Confirm password</label>
                                <input id="register-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Re-enter your password" required aria-describedby="password-confirmation-message"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <p id="password-confirmation-message" class="mt-2 min-h-4 text-xs text-rose-600" aria-live="polite"></p>
                            </div>
                            <button id="register-submit" type="submit" class="w-full rounded-lg bg-slate-800 px-5 py-3 text-center text-base font-medium text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200 disabled:cursor-not-allowed disabled:opacity-50">
                                Create account
                            </button>
                        </form>
                        <p class="auth-register-footer mt-3 text-center text-sm text-slate-600">
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
</body>

</html>
