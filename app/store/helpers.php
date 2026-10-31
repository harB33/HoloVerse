<?php

function store_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function store_format_price(float $amount): string
{
    return '₱' . number_format($amount, 2);
}

function store_calculate_sales_tax(array $items, string $unitPriceField = 'price'): float
{
    $tax = 0.0;
    foreach ($items as $item) {
        $lineSubtotal = round((float) $item[$unitPriceField] * (int) $item['quantity'], 2);
        $tax += round($lineSubtotal * STORE_SALES_TAX_RATE, 2);
    }

    return round($tax, 2);
}

function store_asset_url(string $path, string $siteRoot = ''): string
{
    return preg_match('/^(?:https?:)?\/\//i', $path) ? $path : $siteRoot . ltrim($path, '/');
}

function store_collector_showcase_url(): string
{
    $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $requestPath = is_string($requestPath) ? '/' . trim($requestPath, '/') : '';

    if ($requestPath === '/' || $requestPath === '') {
        return '/collector-showcase';
    }

    if (preg_match(
        '~^(.*?)(?:/home|/index\.php|/products(?:/.*)?|/product/[^/]+|/talent-merch/[^/]+(?:/[^/]+)?|/collector[-_]showcase|/(?:pages/)?flash[-_]sale(?:\.php)?|/(?:pages/)?pre[-_]order(?:\.php)?)/?$~',
        $requestPath,
        $matches
    ) !== 1) {
        return '/collector-showcase';
    }

    return rtrim($matches[1], '/') . '/collector-showcase';
}

function store_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function store_verify_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
