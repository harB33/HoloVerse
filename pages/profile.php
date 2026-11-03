<?php
require_once __DIR__ . '/../app/store.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId < 1) {
    header('Location: /login', true, 303);
    exit;
}

$profileMessage = '';
$profile = null;
$uploadedAvatarPath = null;
$userStatement = $conn->prepare(
    'SELECT username, email, password_hash, profile_picture FROM user_tbl WHERE user_id = ? LIMIT 1'
);
$userStatement->bind_param('i', $userId);
$userStatement->execute();
$profile = $userStatement->get_result()->fetch_assoc();
$userStatement->close();

if (!$profile) {
    http_response_code(404);
    exit('Your account could not be found.');
}

$profilePictureDirectory = __DIR__ . '/../Assets/images/profiles';
$profilePicturePrefix = 'Assets/images/profiles/';
$removeProfilePicture = static function (?string $picturePath) use ($profilePictureDirectory, $profilePicturePrefix): void {
    if (!is_string($picturePath) || !str_starts_with($picturePath, $profilePicturePrefix)) {
        return;
    }

    $fileName = substr($picturePath, strlen($profilePicturePrefix));
    if ($fileName === '' || basename($fileName) !== $fileName) {
        return;
    }

    $filePath = $profilePictureDirectory . DIRECTORY_SEPARATOR . $fileName;
    if (is_file($filePath) && !unlink($filePath)) {
        error_log('HoloVerse could not remove a replaced profile picture: ' . $filePath);
    }
};
$discardUploadedPicture = static function (?string $picturePath): void {
    if (is_string($picturePath) && is_file($picturePath) && !unlink($picturePath)) {
        error_log('HoloVerse could not remove an unsaved profile picture: ' . $picturePath);
    }
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $profileMessage = 'Your session expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'remove_picture') {
            $statement = $conn->prepare(
                'UPDATE user_tbl SET profile_picture = NULL, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?'
            );
            $statement->bind_param('i', $userId);
            $statement->execute();
            $statement->close();

            $removeProfilePicture($profile['profile_picture'] ?? null);
            unset($_SESSION['profile_picture']);
            $_SESSION['profile_notice'] = 'Your profile picture was removed.';
            $_SESSION['profile_notice_type'] = 'success';
            header('Location: /profile#profile-panel', true, 303);
            exit;
        }

        if ($action === 'save_profile') {
            $displayNameInput = $_POST['display_name'] ?? '';
            $emailInput = $_POST['email'] ?? '';
            $currentPasswordInput = $_POST['current_password'] ?? '';
            $displayName = is_string($displayNameInput) ? trim($displayNameInput) : '';
            $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
            $currentPassword = is_string($currentPasswordInput) ? $currentPasswordInput : '';
            $newPicture = $_FILES['profile_picture'] ?? null;
            $picturePath = $profile['profile_picture'] ?? null;

            if (strlen($displayName) < 2 || strlen($displayName) > 50) {
                $profileMessage = 'Your display name must be between 2 and 50 characters.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
                $profileMessage = 'Enter a valid email address.';
            } elseif ($email !== strtolower((string) $profile['email'])
                && !password_verify($currentPassword, (string) $profile['password_hash'])) {
                $profileMessage = 'Enter your current password to change your email address.';
            } else {
                $uploadError = is_array($newPicture) ? (int) ($newPicture['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
                if ($uploadError !== UPLOAD_ERR_NO_FILE && $uploadError !== UPLOAD_ERR_OK) {
                    $profileMessage = $uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
                        ? 'Choose an image smaller than 3 MB.'
                        : 'The profile picture could not be uploaded. Please try again.';
                } elseif ($uploadError === UPLOAD_ERR_OK) {
                    $temporaryPath = $newPicture['tmp_name'] ?? '';
                    $fileSize = (int) ($newPicture['size'] ?? 0);
                    $imageInfo = is_string($temporaryPath) ? @getimagesize($temporaryPath) : false;
                    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                    $detectedMimeType = is_string($temporaryPath) && $fileInfo !== false
                        ? finfo_file($fileInfo, $temporaryPath)
                        : false;
                    if ($fileInfo !== false) {
                        finfo_close($fileInfo);
                    }

                    $allowedImageTypes = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ];
                    if ($fileSize < 1 || $fileSize > 3 * 1024 * 1024) {
                        $profileMessage = 'Choose an image smaller than 3 MB.';
                    } elseif (!is_array($imageInfo)
                        || !is_string($detectedMimeType)
                        || !isset($allowedImageTypes[$detectedMimeType])
                        || ($imageInfo['mime'] ?? '') !== $detectedMimeType
                        || (int) ($imageInfo[0] ?? 0) > 6000
                        || (int) ($imageInfo[1] ?? 0) > 6000
                        || (int) ($imageInfo[0] ?? 0) * (int) ($imageInfo[1] ?? 0) > 25000000) {
                        $profileMessage = 'Use a JPEG, PNG, or WebP image up to 25 megapixels.';
                    } else {
                        if (!is_dir($profilePictureDirectory)
                            && !mkdir($profilePictureDirectory, 0755, true)
                            && !is_dir($profilePictureDirectory)) {
                            throw new RuntimeException('The profile picture directory could not be created.');
                        }

                        $fileName = bin2hex(random_bytes(16)) . '.' . $allowedImageTypes[$detectedMimeType];
                        $destination = $profilePictureDirectory . DIRECTORY_SEPARATOR . $fileName;
                        if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath)
                            || !move_uploaded_file($temporaryPath, $destination)) {
                            throw new RuntimeException('The uploaded profile picture could not be saved.');
                        }
                        $uploadedAvatarPath = $destination;
                        $picturePath = $profilePicturePrefix . $fileName;
                    }
                }

                if ($profileMessage === '') {
                    try {
                        $statement = $conn->prepare(
                            'UPDATE user_tbl
                             SET username = ?, email = ?, profile_picture = ?, updated_at = CURRENT_TIMESTAMP
                             WHERE user_id = ?'
                        );
                        $statement->bind_param('sssi', $displayName, $email, $picturePath, $userId);
                        $statement->execute();
                        $statement->close();
                    } catch (PDOException $error) {
                        $discardUploadedPicture($uploadedAvatarPath);
                        $uploadedAvatarPath = null;
                        if ($error->getCode() === '23505') {
                            $profileMessage = 'An account already uses that email address.';
                        } else {
                            throw $error;
                        }
                    } catch (Throwable $error) {
                        $discardUploadedPicture($uploadedAvatarPath);
                        throw $error;
                    }
                }

                if ($profileMessage === '') {
                    if ($picturePath !== ($profile['profile_picture'] ?? null)) {
                        $removeProfilePicture($profile['profile_picture'] ?? null);
                    }
                    $_SESSION['username'] = $displayName;
                    $_SESSION['user_email'] = $email;
                    if (is_string($picturePath) && $picturePath !== '') {
                        $_SESSION['profile_picture'] = $picturePath;
                    } else {
                        unset($_SESSION['profile_picture']);
                    }
                    $_SESSION['profile_notice'] = 'Your profile details were saved.';
                    $_SESSION['profile_notice_type'] = 'success';
                    header('Location: /profile#profile-panel', true, 303);
                    exit;
                }
            }
        } elseif ($action === 'change_password') {
            $currentPasswordInput = $_POST['current_password'] ?? '';
            $newPasswordInput = $_POST['new_password'] ?? '';
            $confirmationInput = $_POST['password_confirmation'] ?? '';
            $currentPassword = is_string($currentPasswordInput) ? $currentPasswordInput : '';
            $newPassword = is_string($newPasswordInput) ? $newPasswordInput : '';
            $passwordConfirmation = is_string($confirmationInput) ? $confirmationInput : '';
            $characterTypes = [
                preg_match('/[a-z]/', $newPassword) === 1,
                preg_match('/[A-Z]/', $newPassword) === 1,
                preg_match('/[0-9]/', $newPassword) === 1,
                preg_match('/[^a-zA-Z0-9]/', $newPassword) === 1,
            ];

            if (!password_verify($currentPassword, (string) $profile['password_hash'])) {
                $profileMessage = 'Your current password is incorrect.';
            } elseif (strlen($newPassword) < 12 || strlen($newPassword) > 72
                || count(array_filter($characterTypes)) < 3) {
                $profileMessage = 'Choose a password with at least 12 characters and at least 3 of these: lowercase, uppercase, number, and symbol.';
            } elseif ($newPassword !== $passwordConfirmation) {
                $profileMessage = 'The new password confirmation does not match.';
            } elseif (password_verify($newPassword, (string) $profile['password_hash'])) {
                $profileMessage = 'Choose a password you have not used before.';
            } else {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $statement = $conn->prepare(
                    'UPDATE user_tbl SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?'
                );
                $statement->bind_param('si', $passwordHash, $userId);
                $statement->execute();
                $statement->close();

                $_SESSION['profile_notice'] = 'Your password was changed.';
                $_SESSION['profile_notice_type'] = 'success';
                header('Location: /profile#security-panel', true, 303);
                exit;
            }
        } else {
            http_response_code(400);
            $profileMessage = 'Choose a valid profile action.';
        }
    }
}

$notice = is_string($_SESSION['profile_notice'] ?? null) ? $_SESSION['profile_notice'] : '';
$noticeType = ($_SESSION['profile_notice_type'] ?? '') === 'success' ? 'success' : 'error';
unset($_SESSION['profile_notice'], $_SESSION['profile_notice_type']);
$displayNameValue = is_string($_POST['display_name'] ?? null)
    ? trim($_POST['display_name'])
    : (string) $profile['username'];
$emailValue = is_string($_POST['email'] ?? null)
    ? trim($_POST['email'])
    : (string) $profile['email'];
$profilePictureValue = $profile['profile_picture'] ?? null;
$activeSettingsTab = ($_POST['action'] ?? '') === 'change_password' ? 'security' : 'profile';
$pageTitle = 'Your Profile | HoloVerse';
$pageLayout = 'account';
$siteRoot = '../';
$pageStylesheet = $siteRoot . 'Assets/css/profile.css?v=' . filemtime(__DIR__ . '/../Assets/css/profile.css');
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="profile-settings-page">
    <div class="page-shell">
        <?php
        $activePage = 'profile';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="profile-settings-main" data-initial-tab="<?= store_escape($activeSettingsTab) ?>">
            <header class="profile-settings-heading">
                <div>
                    <p class="eyebrow">Your account</p>
                    <h1>Account settings</h1>
                    <p>Manage your profile, security, and collector experience.</p>
                </div>
            </header>

            <?php if ($notice !== ''): ?>
                <div class="profile-notice is-<?= store_escape($noticeType) ?>" role="<?= $noticeType === 'error' ? 'alert' : 'status' ?>">
                    <span class="profile-notice-icon" aria-hidden="true"><?= $noticeType === 'error' ? '!' : '✓' ?></span>
                    <p><?= store_escape($notice) ?></p>
                </div>
            <?php endif; ?>
            <?php if ($profileMessage !== ''): ?>
                <div class="profile-notice is-error" role="alert">
                    <span class="profile-notice-icon" aria-hidden="true">!</span>
                    <p><?= store_escape($profileMessage) ?></p>
                </div>
            <?php endif; ?>

            <section class="profile-identity-card" aria-label="Account overview">
                <div class="profile-identity-avatar">
                    <?php if (is_string($profilePictureValue) && $profilePictureValue !== ''): ?>
                        <img src="<?= store_escape(store_asset_url($profilePictureValue, $siteRoot)) ?>" alt="">
                    <?php else: ?>
                        <img class="profile-avatar-placeholder" src="<?= store_escape(store_asset_url('assets/images/logo.png', $siteRoot)) ?>" alt="">
                    <?php endif; ?>
                </div>
                <div class="profile-identity-copy">
                    <p class="profile-card-kicker">Welcome back</p>
                    <h2><?= store_escape($displayNameValue) ?></h2>
                    <p><?= store_escape($emailValue) ?></p>
                </div>
                <span class="profile-role-badge"><?= store_escape(ucfirst((string) ($_SESSION['user_role'] ?? 'user'))) ?> account</span>
            </section>

            <div class="profile-settings-layout">
                <nav class="profile-settings-nav" aria-label="Account settings">
                    <p class="profile-nav-heading">Settings</p>
                    <div class="profile-settings-tabs">
                        <a class="profile-nav-link" href="#profile-panel" data-settings-tab="profile" aria-controls="profile-panel">
                            <span class="profile-nav-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg>
                            </span>
                            <span><strong>Profile</strong><small>Personal details</small></span>
                            <span class="profile-nav-arrow" aria-hidden="true">›</span>
                        </a>
                        <a class="profile-nav-link" href="#security-panel" data-settings-tab="security" aria-controls="security-panel">
                            <span class="profile-nav-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <span><strong>Security</strong><small>Password and access</small></span>
                            <span class="profile-nav-arrow" aria-hidden="true">›</span>
                        </a>
                        <a class="profile-nav-link" href="#showcase-panel" data-settings-tab="showcase" aria-controls="showcase-panel">
                            <span class="profile-nav-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"/></svg>
                            </span>
                            <span><strong>Showcase</strong><small>Your collection</small></span>
                            <span class="profile-nav-arrow" aria-hidden="true">›</span>
                        </a>
                    </div>
                </nav>

                <div class="profile-settings-content">
                    <section class="profile-settings-card profile-details-card" id="profile-panel" aria-labelledby="profile-details-title" data-settings-panel="profile">
                        <div class="profile-card-heading">
                            <div>
                                <p class="profile-card-kicker">Personal details</p>
                                <h2 id="profile-details-title">Profile information</h2>
                                <p class="profile-card-description">Update the details associated with your HoloVerse account.</p>
                            </div>
                        </div>

                        <div class="profile-photo-row">
                            <div class="profile-photo-preview">
                                <?php if (is_string($profilePictureValue) && $profilePictureValue !== ''): ?>
                                    <img src="<?= store_escape(store_asset_url($profilePictureValue, $siteRoot)) ?>" alt="Your profile picture">
                                <?php else: ?>
                                    <img class="profile-avatar-placeholder" src="<?= store_escape(store_asset_url('assets/images/logo.png', $siteRoot)) ?>" alt="HoloVerse logo">
                                <?php endif; ?>
                            </div>
                            <div class="profile-photo-copy">
                                <strong>Profile picture</strong>
                                <span>JPEG, PNG, or WebP · up to 3 MB</span>
                                <?php if (is_string($profilePictureValue) && $profilePictureValue !== ''): ?>
                                    <form method="post" action="<?= store_escape($siteRoot . 'profile') ?>">
                                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                        <button class="profile-remove-picture" type="submit" name="action" value="remove_picture">Remove picture</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form class="profile-form" method="post" action="<?= store_escape($siteRoot . 'profile') ?>" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="save_profile">
                            <label class="profile-field" for="profile-picture">
                                <span>Upload a picture</span>
                                <input id="profile-picture" name="profile_picture" type="file" accept="image/jpeg,image/png,image/webp">
                                <small>JPEG, PNG, or WebP. Maximum file size 3 MB.</small>
                            </label>
                            <label class="profile-field" for="display-name">
                                <span>Display name</span>
                                <input id="display-name" name="display_name" type="text" value="<?= store_escape($displayNameValue) ?>" minlength="2" maxlength="50" autocomplete="nickname" required>
                            </label>
                            <label class="profile-field" for="profile-email">
                                <span>Email address</span>
                                <input id="profile-email" name="email" type="email" value="<?= store_escape($emailValue) ?>" maxlength="100" autocomplete="email" required>
                                <small>Your email is used to sign in. Enter your current password to change it.</small>
                            </label>
                            <label class="profile-field" for="profile-current-password">
                                <span>Current password <small>(only needed to change your email)</small></span>
                                <input id="profile-current-password" name="current_password" type="password" autocomplete="current-password">
                            </label>
                            <div class="profile-form-actions">
                                <button class="profile-primary-button" type="submit">Save profile</button>
                            </div>
                        </form>
                    </section>

                    <section class="profile-settings-card profile-password-card" id="security-panel" aria-labelledby="profile-password-title" data-settings-panel="security">
                        <div class="profile-card-heading">
                            <div>
                                <p class="profile-card-kicker">Account security</p>
                                <h2 id="profile-password-title">Password and access</h2>
                                <p class="profile-card-description">Keep your account protected with a strong, unique password.</p>
                            </div>
                            <span class="profile-security-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                        </div>
                        <p class="profile-password-help">Choose a password you do not use on another site. Your password must be at least 12 characters and include at least 3 character types.</p>
                        <form class="profile-form" method="post" action="<?= store_escape($siteRoot . 'profile') ?>">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="change_password">
                            <label class="profile-field" for="current-password">
                                <span>Current password</span>
                                <input id="current-password" name="current_password" type="password" autocomplete="current-password" required>
                            </label>
                            <label class="profile-field" for="new-password">
                                <span>New password</span>
                                <input id="new-password" name="new_password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
                                <small>Use at least 3 of these: lowercase, uppercase, number, or symbol.</small>
                            </label>
                            <label class="profile-field" for="password-confirmation">
                                <span>Confirm new password</span>
                                <input id="password-confirmation" name="password_confirmation" type="password" minlength="12" maxlength="72" autocomplete="new-password" required>
                            </label>
                            <div class="profile-form-actions">
                                <button class="profile-primary-button" type="submit">Update password</button>
                            </div>
                        </form>
                    </section>

                    <section class="profile-settings-card profile-showcase-panel" id="showcase-panel" aria-labelledby="profile-showcase-title" data-settings-panel="showcase">
                        <div class="profile-showcase-art" aria-hidden="true">
                            <span class="profile-showcase-star">✦</span>
                            <span class="profile-showcase-orbit profile-showcase-orbit-one"></span>
                            <span class="profile-showcase-orbit profile-showcase-orbit-two"></span>
                            <span class="profile-showcase-gem">✧</span>
                        </div>
                        <div class="profile-showcase-copy">
                            <p class="profile-card-kicker">Made for collectors</p>
                            <h2 id="profile-showcase-title">Your collection, on display.</h2>
                            <p class="profile-card-description">Visit your Collector Showcase to explore your collection and share the things you love.</p>
                            <a class="profile-primary-button profile-showcase-button" href="<?= store_escape($siteRoot . 'collector-showcase') ?>">
                                Visit your Collector Showcase <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
    <?php
    $cartHref = '/cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    <script src="<?= store_escape($siteRoot . 'Assets/js/profile-settings.js?v=' . filemtime(__DIR__ . '/../Assets/js/profile-settings.js')) ?>" defer></script>
</body>
</html>
