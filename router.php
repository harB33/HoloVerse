<?php

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$query = parse_url($requestUri, PHP_URL_QUERY);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
$requestPath = '/' . ltrim($requestPath, '/');

$redirectToCleanUrl = static function (string $path) use ($query): never {
    $location = $path . (is_string($query) && $query !== '' ? '?' . $query : '');
    header('Location: ' . $location, true, 308);
    exit;
};

if ($requestPath === '/index.php') {
    $redirectToCleanUrl('/home');
}

if (preg_match('~^/(?:pages/|Functions/)?([A-Za-z0-9_-]+)\.php$~i', $requestPath, $matches) === 1
    && is_file(__DIR__ . '/pages/' . $matches[1] . '.php')) {
    $redirectToCleanUrl('/' . $matches[1]);
}

if (preg_match('~^/([A-Za-z0-9_-]+)/$~', $requestPath, $matches) === 1) {
    $redirectToCleanUrl('/' . $matches[1]);
}

if ($requestPath === '/home') {
    $scriptPath = __DIR__ . '/index.php';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
} elseif (preg_match('~^/([A-Za-z0-9_-]+)$~', $requestPath, $matches) === 1
    && is_file(__DIR__ . '/pages/' . $matches[1] . '.php')) {
    $scriptPath = __DIR__ . '/pages/' . $matches[1] . '.php';
    $_SERVER['SCRIPT_NAME'] = '/pages/' . $matches[1] . '.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
} else {
    $requestedFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($requestPath, '/')));
    $projectRoot = realpath(__DIR__);
    if ($requestedFile !== false
        && $projectRoot !== false
        && str_starts_with($requestedFile, $projectRoot . DIRECTORY_SEPARATOR)
        && is_file($requestedFile)) {
        return false;
    }

    http_response_code(404);
    exit('Not Found');
}

$_SERVER['SCRIPT_FILENAME'] = $scriptPath;
require $scriptPath;
