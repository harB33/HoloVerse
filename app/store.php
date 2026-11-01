<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'] ?? 0,
        'path' => $cookieParams['path'] ?? '/',
        'domain' => $cookieParams['domain'] ?? '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/dbconnect.php';

const STORE_CART_HOLD_SECONDS = 300;
const STORE_SALES_TAX_RATE = 0.12;
const STORE_CURRENCY_RATES = [
    'PHP' => 1.0,
    'JPY' => 2.64,
    'USD' => 0.017,
];
const STORE_TALENT_FILTER_GROUP_SLUGS = [
    'gen-0', 'gen-1', 'gamers', 'gen-2', 'gen-3', 'gen-4', 'gen-5', 'holox',
    'regloss', 'flow-glow', 'myth', 'promise', 'advent', 'justice',
    'id-gen-1', 'id-gen-2', 'id-gen-3', 'asobi-mawaritai',
];

require_once __DIR__ . '/store/helpers.php';
require_once __DIR__ . '/store/auth.php';
require_once __DIR__ . '/store/inventory.php';
require_once __DIR__ . '/store/cart.php';
require_once __DIR__ . '/store/catalog.php';
require_once __DIR__ . '/store/flash-sales.php';
require_once __DIR__ . '/store/collector-showcase.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && ($_POST['store_currency_action'] ?? '') === 'change') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        exit('Your session expired. Refresh the page and try again.');
    }

    $currency = $_POST['currency'] ?? null;
    if (!is_string($currency) || !array_key_exists($currency, STORE_CURRENCY_RATES)) {
        http_response_code(400);
        exit('The selected currency is not supported.');
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $requestParts = is_string($requestUri) ? parse_url($requestUri) : false;
    $returnTo = is_array($requestParts)
        ? ($requestParts['path'] ?? '/') . (isset($requestParts['query']) ? '?' . $requestParts['query'] : '')
        : '/';
    if (!str_starts_with($returnTo, '/')
        || str_starts_with($returnTo, '//')
        || str_contains($returnTo, '\\')
        || preg_match('/[\r\n]/', $returnTo) === 1) {
        http_response_code(400);
        exit('The return address is invalid.');
    }

    $_SESSION['store_currency'] = $currency;
    header('Location: ' . $returnTo, true, 303);
    exit;
}

store_refresh_authenticated_user($conn);
