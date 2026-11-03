<?php
require_once __DIR__ . '/../app/store.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId < 1) {
    header('Location: /login', true, 303);
    exit;
}

$profileMessage = '';
$profileStatement = $conn->prepare(
    'SELECT username, email, password_hash, profile_picture FROM user_tbl WHERE user_id = ? LIMIT 1'
);
$profileStatement->bind_param('i', $userId);
$profileStatement->execute();
$profile = $profileStatement->get_result()->fetch_assoc();
$profileStatement->close();

if (!$profile) {
    http_response_code(404);
    exit('Your account could not be found.');
}

$talents = [];
$talentResult = $conn->query('SELECT talent_id, talent_name, talent_slug, talent_img FROM talent_tbl ORDER BY talent_name');
while ($talent = $talentResult->fetch_assoc()) {
    $talent['talent_id'] = (int) $talent['talent_id'];
    $talents[$talent['talent_id']] = $talent;
}
$fanbaseNames = [
    'inugami-korone' => 'Koronesuki',
    'gawr-gura' => 'Chumbuds',
    'mori-calliope' => 'Dead Beats',
    'usada-pekora' => 'Nousagi',
    'tokino-sora' => 'Soratomos',
    'roboco' => 'Robosa',
    'azki' => 'Pioneer',
    'hoshimachi-suisei' => 'Hoshiyomi',
    'sakura-miko' => '35P',
    'aki-rosenthal' => 'Rosetai',
    'akai-haato' => 'Haatons',
    'shirakami-fubuki' => 'Sukon-bu',
    'natsuiro-matsuri' => 'Matsurisu',
    'ookami-mio' => 'Mio-fa',
    'nekomata-okayu' => 'Onigiryaa',
    'nakiri-ayame' => 'Nakirigumi',
    'yuzuki-choco' => 'Chocoate',
    'oozora-subaru' => 'Subatomos',
    'shiranui-flare' => 'Elfriends',
    'shirogane-noel' => 'Shirogane Knights',
    'houshou-marine' => 'Houshou Pirates',
    'tsunomaki-watame' => 'Watamates',
    'tokoyami-towa' => 'Kenzoku',
    'himemori-luna' => 'Lu-knights',
    'yukihana-lamy' => 'Yukimin',
    'momosuzu-nene' => 'Nene-tomo',
    'shishiro-botan' => 'SSRB',
    'omaru-polka' => 'Omaruza',
    'la-darknesss' => 'Plusmate',
    'takanashi-kiara' => 'KFP',
    'ninomae-inanis' => 'Takodachis',
    'watson-amelia' => 'Teamates',
    'irys' => 'IRyStocrats',
    'ouro-kronii' => 'Kronies',
    'hakos-baelz' => 'Brats',
    'shiori-novella' => 'Novellites',
    'koseki-bijou' => 'Pebbles',
    'nerissa-ravencroft' => 'Jailbirds',
    'fuwawa-abyssgard' => 'Ruffians',
    'mococo-abyssgard' => 'Ruffians',
    'elizabeth-rose-bloodflame' => 'Rosebuds',
    'gigi-murin' => 'Grems',
    'cecilia-immergreen' => 'Saplings',
    'raora-panthera' => 'Panthera Fam',
    'ayunda-risu' => 'Risuners',
    'moona-hoshinova' => 'Moonafics',
    'airani-iofifteen' => 'Ioforia',
    'kureiji-ollie' => 'Ollievers',
    'anya-melfissa' => 'Melfriends',
    'pavolia-reine' => 'Pavonashi',
    'vestia-zeta' => 'Zecretary',
    'kaela-kovalskia' => 'Kaelites',
    'kobo-kanaeru' => 'Kobokerz',
    'hyakuto-kyoko' => 'Hyakuto Ikka',
    'achichi-mela' => 'Mela-gumi',
    'suzuna-tsuzuri' => 'Haitatsuin',
    'sorashina-sopia' => 'SopiCrew',
];

$addressValues = [
    'recipient_name' => '',
    'phone' => '',
    'address_line1' => '',
    'address_line2' => '',
    'barangay' => '',
    'city' => '',
    'region' => '',
    'postal_code' => '',
    'country_code' => 'PH',
];
$addressStatement = $conn->prepare(
    'SELECT recipient_name, phone, address_line1, address_line2, barangay, city, region, postal_code, country_code
     FROM address_tbl WHERE user_id = ? ORDER BY address_id LIMIT 1'
);
$addressStatement->bind_param('i', $userId);
$addressStatement->execute();
$savedAddress = $addressStatement->get_result()->fetch_assoc();
$addressStatement->close();
$hasSavedAddress = is_array($savedAddress);
$addressValues = array_merge($addressValues, $savedAddress ?? []);

$selectedTalentId = 0;
foreach ($talents as $talentId => $talent) {
    if (($profile['profile_picture'] ?? null) === $talent['talent_img']) {
        $selectedTalentId = $talentId;
        break;
    }
}
if ($selectedTalentId === 0 && $talents !== []) {
    $selectedTalentId = (int) array_key_first($talents);
}
$profilePictureValue = (string) ($talents[$selectedTalentId]['talent_img'] ?? 'Assets/images/hololive.png');
$selectedFanbaseName = $fanbaseNames[$talents[$selectedTalentId]['talent_slug'] ?? ''] ?? '';
$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $profileMessage = 'Your session expired. Refresh the page and try again.';
    } elseif ($action === 'save_profile') {
        $displayNameInput = $_POST['display_name'] ?? '';
        $talentInput = $_POST['talent_id'] ?? null;
        $talentIdInput = is_string($talentInput)
            ? filter_var($talentInput, FILTER_VALIDATE_INT)
            : false;
        $displayName = is_string($displayNameInput) ? trim($displayNameInput) : '';

        if (strlen($displayName) < 2 || strlen($displayName) > 50) {
            $profileMessage = 'Your display name must be between 2 and 50 characters.';
        } elseif (!is_int($talentIdInput) || !isset($talents[$talentIdInput])) {
            $profileMessage = 'Choose a hololive talent for your profile picture.';
        } else {
            $profilePictureValue = (string) $talents[$talentIdInput]['talent_img'];
            $statement = $conn->prepare(
                'UPDATE user_tbl
                 SET username = ?, profile_picture = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = ?'
            );
            $statement->bind_param('ssi', $displayName, $profilePictureValue, $userId);
            $statement->execute();
            $statement->close();

            $_SESSION['username'] = $displayName;
            $_SESSION['profile_picture'] = $profilePictureValue;
            $_SESSION['profile_notice'] = 'Your profile details were saved.';
            $_SESSION['profile_notice_type'] = 'success';
            header('Location: /profile#profile-panel', true, 303);
            exit;
        }
        if (is_int($talentIdInput) && isset($talents[$talentIdInput])) {
            $selectedTalentId = $talentIdInput;
            $profilePictureValue = (string) $talents[$selectedTalentId]['talent_img'];
        }
    } elseif ($action === 'save_address') {
        $addressLimits = [
            'recipient_name' => 150,
            'phone' => 30,
            'address_line1' => 255,
            'address_line2' => 255,
            'barangay' => 100,
            'city' => 100,
            'region' => 100,
            'postal_code' => 20,
        ];
        foreach ($addressLimits as $field => $maxLength) {
            $input = $_POST[$field] ?? ($field === 'phone' ? $addressValues[$field] : '');
            $addressValues[$field] = is_string($input) ? trim($input) : '';
            if (strlen($addressValues[$field]) > $maxLength) {
                $profileMessage = 'One or more address fields are too long.';
                break;
            }
        }
        $addressValues['country_code'] = 'PH';
        if ($profileMessage === '' && (
            $addressValues['recipient_name'] === ''
            || $addressValues['address_line1'] === ''
            || $addressValues['barangay'] === ''
            || $addressValues['city'] === ''
            || $addressValues['region'] === ''
            || $addressValues['postal_code'] === ''
        )) {
            $profileMessage = 'Complete all required address fields.';
        }

        if ($profileMessage === '') {
            $addressIdStatement = $conn->prepare(
                'SELECT address_id FROM address_tbl WHERE user_id = ? ORDER BY address_id LIMIT 1'
            );
            $addressIdStatement->bind_param('i', $userId);
            $addressIdStatement->execute();
            $existingAddress = $addressIdStatement->get_result()->fetch_assoc();
            $addressIdStatement->close();

            $conn->begin_transaction();
            try {
                if ($existingAddress) {
                    $addressId = (int) $existingAddress['address_id'];
                    $statement = $conn->prepare(
                        'UPDATE address_tbl
                         SET recipient_name = ?, phone = ?, address_line1 = ?, address_line2 = ?,
                             barangay = ?, city = ?, region = ?, postal_code = ?, country_code = ?
                         WHERE address_id = ? AND user_id = ?'
                    );
                    $statement->bind_param(
                        'sssssssssii',
                        $addressValues['recipient_name'],
                        $addressValues['phone'],
                        $addressValues['address_line1'],
                        $addressValues['address_line2'],
                        $addressValues['barangay'],
                        $addressValues['city'],
                        $addressValues['region'],
                        $addressValues['postal_code'],
                        $addressValues['country_code'],
                        $addressId,
                        $userId
                    );
                } else {
                    $statement = $conn->prepare(
                        'INSERT INTO address_tbl
                            (user_id, recipient_name, phone, address_line1, address_line2, barangay,
                             city, region, postal_code, country_code)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $statement->bind_param(
                        'isssssssss',
                        $userId,
                        $addressValues['recipient_name'],
                        $addressValues['phone'],
                        $addressValues['address_line1'],
                        $addressValues['address_line2'],
                        $addressValues['barangay'],
                        $addressValues['city'],
                        $addressValues['region'],
                        $addressValues['postal_code'],
                        $addressValues['country_code']
                    );
                }
                $statement->execute();
                $statement->close();
                $conn->commit();
            } catch (Throwable $error) {
                $conn->rollback();
                throw $error;
            }

            unset($_SESSION['checkout_shipping']);
            $_SESSION['profile_notice'] = 'Your saved address was updated.';
            $_SESSION['profile_notice_type'] = 'success';
            header('Location: /profile#profile-panel', true, 303);
            exit;
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

$notice = is_string($_SESSION['profile_notice'] ?? null) ? $_SESSION['profile_notice'] : '';
$noticeType = ($_SESSION['profile_notice_type'] ?? '') === 'success' ? 'success' : 'error';
unset($_SESSION['profile_notice'], $_SESSION['profile_notice_type']);
$displayNameValue = is_string($_POST['display_name'] ?? null)
    ? trim($_POST['display_name'])
    : (string) $profile['username'];
$emailValue = (string) $profile['email'];
$activeSettingsTab = $action === 'change_password' ? 'security' : 'profile';
$openAddressDialog = $action === 'save_address' && $profileMessage !== '';
$addressSummaryParts = array_filter([
    trim((string) $addressValues['address_line1']),
    trim((string) $addressValues['address_line2']),
    trim((string) $addressValues['barangay']) === ''
        ? ''
        : (preg_match('/^barangay\b/i', trim((string) $addressValues['barangay']))
            ? trim((string) $addressValues['barangay'])
            : 'Barangay ' . trim((string) $addressValues['barangay'])),
    trim((string) $addressValues['city']),
    trim((string) $addressValues['region']),
    trim((string) $addressValues['postal_code']),
    trim((string) $addressValues['country_code']),
], static fn (string $part): bool => $part !== '');
$addressSummary = implode(', ', $addressSummaryParts);
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
                <div class="profile-identity-details">
                    <div class="profile-identity-avatar">
                        <img data-profile-avatar src="<?= store_escape(store_asset_url($profilePictureValue, $siteRoot)) ?>" alt="">
                    </div>
                    <div class="profile-identity-copy">
                        <p class="profile-card-kicker">Welcome back</p>
                        <h2><?= store_escape($displayNameValue) ?></h2>
                        <p class="profile-identity-email"><?= store_escape($emailValue) ?></p>
                        <p class="profile-identity-oshi" data-profile-fanbase<?= $selectedFanbaseName === '' ? ' hidden' : '' ?>>
                            <span data-profile-talent-name><?= store_escape($selectedFanbaseName) ?></span>
                        </p>
                    </div>
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
                            <span><strong>Profile</strong><small>Details and address</small></span>
                            <span class="profile-nav-arrow" aria-hidden="true">›</span>
                        </a>
                        <a class="profile-nav-link" href="#security-panel" data-settings-tab="security" aria-controls="security-panel">
                            <span class="profile-nav-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <span><strong>Security</strong><small>Password and access</small></span>
                            <span class="profile-nav-arrow" aria-hidden="true">›</span>
                        </a>
                    </div>
                </nav>

                <div class="profile-settings-content">
                    <section class="profile-settings-card" id="profile-panel" aria-labelledby="profile-details-title" data-settings-panel="profile">
                        <div class="profile-card-heading">
                            <div>
                                <p class="profile-card-kicker">Personal details</p>
                                <h2 id="profile-details-title">Profile information</h2>
                                <p class="profile-card-description">Your email address is fixed. Update your display name and choose an official hololive talent avatar.</p>
                            </div>
                        </div>

                        <form class="profile-form profile-details-form" method="post" action="<?= store_escape($siteRoot . 'profile') ?>">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="save_profile">
                            <label class="profile-field" for="display-name">
                                <span>Display name</span>
                                <input id="display-name" name="display_name" type="text" value="<?= store_escape($displayNameValue) ?>" minlength="2" maxlength="50" autocomplete="nickname" required>
                            </label>
                            <label class="profile-field" for="profile-email">
                                <span>Email address</span>
                                <input id="profile-email" type="email" value="<?= store_escape($emailValue) ?>" autocomplete="email" readonly>
                                <small>Email addresses cannot be changed.</small>
                            </label>
                            <?php if ($talents !== []): ?>
                                <div class="profile-avatar-picker" role="group" aria-labelledby="profile-avatar-picker-title">
                                    <div class="profile-avatar-picker-current">
                                        <span class="profile-avatar-picker-preview">
                                            <img data-profile-avatar src="<?= store_escape($profilePictureValue) ?>" alt="">
                                        </span>
                                        <span class="profile-avatar-picker-copy">
                                            <strong id="profile-avatar-picker-title">Profile picture</strong>
                                            <span>Choose an official hololive talent avatar.</span>
                                            <span class="profile-avatar-selected-name" data-selected-talent-name><?= store_escape((string) ($talents[$selectedTalentId]['talent_name'] ?? '')) ?></span>
                                        </span>
                                    </div>
                                    <input id="talent-avatar" type="hidden" name="talent_id" value="<?= $selectedTalentId ?>">
                                    <div class="profile-avatar-options" role="group" aria-label="Available talent avatars">
                                        <?php foreach (array_values($talents) as $talentIndex => $talent): ?>
                                            <button
                                                class="profile-avatar-option<?= $selectedTalentId === $talent['talent_id'] ? ' is-selected' : '' ?>"
                                                type="button"
                                                data-talent-index="<?= $talentIndex ?>"
                                                data-talent-id="<?= (int) $talent['talent_id'] ?>"
                                                data-image="<?= store_escape($talent['talent_img']) ?>"
                                                data-name="<?= store_escape($talent['talent_name']) ?>"
                                                data-fanbase="<?= store_escape($fanbaseNames[$talent['talent_slug']] ?? '') ?>"
                                                aria-label="Choose <?= store_escape($talent['talent_name']) ?> as profile picture"
                                                aria-pressed="<?= $selectedTalentId === $talent['talent_id'] ? 'true' : 'false' ?>"
                                            >
                                                <img src="<?= store_escape($talent['talent_img']) ?>" alt="" loading="lazy">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                    <nav class="profile-avatar-pagination" aria-label="Talent avatar pages">
                                        <button class="profile-avatar-page-button" type="button" data-avatar-page-previous aria-label="Previous talent avatars">‹</button>
                                        <span data-avatar-page-status aria-live="polite"></span>
                                        <button class="profile-avatar-page-button" type="button" data-avatar-page-next aria-label="Next talent avatars">›</button>
                                    </nav>
                                </div>
                            <?php else: ?>
                                <p class="profile-card-description">No talent portraits are currently available.</p>
                            <?php endif; ?>
                            <div class="profile-form-actions">
                                <button class="profile-primary-button" type="submit">Save profile</button>
                            </div>
                        </form>

                        <div class="profile-card-heading profile-address-heading">
                            <div>
                                <p class="profile-card-kicker">Checkout defaults</p>
                                <h2>Saved address</h2>
                                <p class="profile-card-description">This address will be filled in automatically at checkout.</p>
                            </div>
                        </div>
                        <div class="profile-saved-address">
                            <?php if ($hasSavedAddress): ?>
                                <div class="profile-saved-address-copy">
                                    <strong><?= store_escape((string) $addressValues['recipient_name']) ?></strong>
                                    <?php if (trim((string) $addressValues['phone']) !== ''): ?>
                                        <span><?= store_escape((string) $addressValues['phone']) ?></span>
                                    <?php endif; ?>
                                    <p><?= store_escape($addressSummary) ?></p>
                                </div>
                            <?php else: ?>
                                <p class="profile-saved-address-empty">You haven’t added a checkout address yet.</p>
                            <?php endif; ?>
                            <button class="profile-primary-button" type="button" data-address-dialog-open>
                                <?= $hasSavedAddress ? 'Edit address' : 'Add address' ?>
                            </button>
                        </div>
                        <dialog class="profile-address-dialog" aria-labelledby="profile-address-dialog-title" data-open-on-load="<?= $openAddressDialog ? 'true' : 'false' ?>">
                            <div class="profile-address-dialog-heading">
                                <div>
                                    <p class="profile-card-kicker">Checkout defaults</p>
                                    <h2 id="profile-address-dialog-title"><?= $hasSavedAddress ? 'Edit saved address' : 'Add a saved address' ?></h2>
                                    <p class="profile-card-description">Use the same address details you enter at checkout.</p>
                                </div>
                                <button class="profile-address-dialog-close" type="button" data-address-dialog-close aria-label="Close address form">×</button>
                            </div>
                            <form class="profile-form profile-address-form checkout-address-form" method="post" action="<?= store_escape($siteRoot . 'profile') ?>">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="save_address">
                            <label class="profile-field" for="address-recipient">
                                <span>Recipient name</span>
                                <input id="address-recipient" name="recipient_name" type="text" maxlength="150" autocomplete="name" value="<?= store_escape((string) $addressValues['recipient_name']) ?>" required>
                            </label>
                            <label class="profile-field" for="address-line1">
                                <span>Address</span>
                                <input id="address-line1" name="address_line1" type="text" maxlength="255" autocomplete="address-line1" value="<?= store_escape((string) $addressValues['address_line1']) ?>" required>
                            </label>
                            <label class="profile-field" for="address-line2">
                                <span>Apartment, suite, etc. (optional)</span>
                                <input id="address-line2" name="address_line2" type="text" maxlength="255" autocomplete="address-line2" value="<?= store_escape((string) $addressValues['address_line2']) ?>">
                            </label>
                            <div class="checkout-address-row">
                                <label class="profile-field" for="checkout-region">
                                    <span>Region / province</span>
                                    <span class="checkout-location-autocomplete">
                                        <input type="text" id="checkout-region" name="region" maxlength="100" required autocomplete="address-level1" aria-autocomplete="list" aria-controls="checkout-region-suggestions" aria-expanded="false" placeholder="Enter or select a region / province" value="<?= store_escape((string) $addressValues['region']) ?>">
                                        <span id="checkout-region-suggestions" class="checkout-location-suggestions" role="listbox" hidden></span>
                                    </span>
                                </label>
                                <label class="profile-field" for="checkout-city">
                                    <span>City</span>
                                    <span class="checkout-location-autocomplete">
                                        <input type="text" id="checkout-city" name="city" maxlength="100" required autocomplete="address-level2" aria-autocomplete="list" aria-controls="checkout-city-suggestions" aria-expanded="false" placeholder="Enter or select a city / municipality" value="<?= store_escape((string) $addressValues['city']) ?>">
                                        <span id="checkout-city-suggestions" class="checkout-location-suggestions" role="listbox" hidden></span>
                                    </span>
                                </label>
                            </div>
                            <p id="checkout-location-status" class="checkout-location-status" role="status" aria-live="polite"></p>
                            <div class="checkout-address-row">
                                <label class="profile-field" for="checkout-barangay">
                                    <span>Barangay</span>
                                    <span class="checkout-location-autocomplete">
                                        <input type="text" id="checkout-barangay" name="barangay" maxlength="100" required autocomplete="address-line3" aria-autocomplete="list" aria-controls="checkout-barangay-suggestions" aria-expanded="false" placeholder="Enter or select a barangay" value="<?= store_escape((string) $addressValues['barangay']) ?>">
                                        <span id="checkout-barangay-suggestions" class="checkout-location-suggestions" role="listbox" hidden></span>
                                    </span>
                                </label>
                                <label class="profile-field" for="address-postal">
                                    <span>Postal code</span>
                                    <input id="address-postal" name="postal_code" type="text" maxlength="20" required autocomplete="postal-code" value="<?= store_escape((string) $addressValues['postal_code']) ?>">
                                </label>
                            </div>
                            <div class="profile-form-actions">
                                <button class="profile-primary-button" type="submit">Save address</button>
                            </div>
                            </form>
                        </dialog>
                    </section>

                    <section class="profile-settings-card" id="security-panel" aria-labelledby="profile-password-title" data-settings-panel="security">
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
                        <form class="profile-form profile-password-form" method="post" action="<?= store_escape($siteRoot . 'profile') ?>">
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
                        <p class="profile-password-help">
                            Forgot your password?
                            <a href="<?= store_escape($siteRoot . 'login?mode=forgot_password') ?>">Reset it from the sign-in page.</a>
                        </p>
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
    <script
        src="<?= store_escape($siteRoot) ?>assets/js/checkout-location-selects.js"
        data-initial-region="<?= store_escape((string) $addressValues['region']) ?>"
        data-initial-city="<?= store_escape((string) $addressValues['city']) ?>"
        data-initial-barangay="<?= store_escape((string) $addressValues['barangay']) ?>"
        defer
    ></script>
    <script src="<?= store_escape($siteRoot . 'Assets/js/profile-settings.js?v=' . filemtime(__DIR__ . '/../Assets/js/profile-settings.js')) ?>" defer></script>
</body>
</html>
