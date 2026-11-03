<?php
require_once __DIR__ . '/../app/store.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !store_verify_csrf()) {
    http_response_code(403);
    exit('Invalid logout request.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
}
session_destroy();
header('Location: /login');
exit;
