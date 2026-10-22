<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/dbconnect.php';

const STORE_CART_HOLD_SECONDS = 300;
const STORE_SALES_TAX_RATE = 0.12;
const STORE_TALENT_FILTER_GROUP_SLUGS = [
    'gen-0', 'gen-1', 'gamers', 'gen-2', 'gen-3', 'gen-4', 'gen-5', 'holox',
    'regloss', 'flow-glow', 'myth', 'promise', 'advent', 'justice',
    'id-gen-1', 'id-gen-2', 'id-gen-3', 'asobi-mawaritai',
];

require_once __DIR__ . '/store/helpers.php';
require_once __DIR__ . '/store/auth.php';
require_once __DIR__ . '/store/cart.php';
require_once __DIR__ . '/store/catalog.php';
require_once __DIR__ . '/store/flash-sales.php';
require_once __DIR__ . '/store/collector-showcase.php';

store_refresh_authenticated_user($conn);
