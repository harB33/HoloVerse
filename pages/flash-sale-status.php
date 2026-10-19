<?php
require_once __DIR__ . '/../app/store.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$timezone = new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila');
$now = new DateTimeImmutable('now', $timezone);
$liveProducts = store_flash_sale_table_exists($conn)
    ? store_flash_sale_rows($conn, 'live', $now)
    : store_product_rows(
        $conn,
        'AND v.compare_at_price IS NOT NULL AND v.compare_at_price > v.price AND ' . store_available_stock_sql() . ' > 0',
        'p.product_id DESC'
    );

echo json_encode(['count' => count($liveProducts)], JSON_THROW_ON_ERROR);