<?php
require_once __DIR__ . '/../app/store.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

if (!store_has_role('admin')) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Access denied</title></head><body style="font-family: Arial, sans-serif; padding: 48px;">';
    echo '<h1>Access denied</h1><p>You do not have permission to view this dashboard.</p>';
    echo '</body></html>';
    exit;
}

$dashboardNotice = '';
$dashboardNoticeIsError = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $userId = isset($_POST['user_id']) && is_string($_POST['user_id'])
        ? filter_var($_POST['user_id'], FILTER_VALIDATE_INT)
        : false;
    $role = isset($_POST['role']) && is_string($_POST['role']) ? $_POST['role'] : '';

    if (!store_verify_csrf()) {
        http_response_code(403);
        $dashboardNotice = 'Your session expired. Refresh the page and try again.';
        $dashboardNoticeIsError = true;
    } elseif ($userId === false || $userId < 1 || !in_array($role, ['user', 'admin'], true)) {
        $dashboardNotice = 'Choose a valid user and privilege level.';
        $dashboardNoticeIsError = true;
    } elseif ((int) $userId === (int) $_SESSION['user_id']) {
        $dashboardNotice = 'You cannot change your own administrator privilege.';
        $dashboardNoticeIsError = true;
    } else {
        try {
            $conn->begin_transaction();

            $statement = $conn->prepare('SELECT user_role FROM user_tbl WHERE user_id = ? FOR UPDATE');
            $targetUserId = (int) $userId;
            $statement->bind_param('i', $targetUserId);
            $statement->execute();
            $targetUser = $statement->get_result()->fetch_assoc();
            $statement->close();

            if (!$targetUser) {
                $conn->rollback();
                $dashboardNotice = 'That user account could not be found.';
                $dashboardNoticeIsError = true;
            } elseif ($targetUser['user_role'] === $role) {
                $conn->commit();
                $_SESSION['admin_dashboard_notice'] = 'That account already has the selected privilege.';
                header('Location: admin-dashboard#user-privileges');
                exit;
            } else {
                if ($targetUser['user_role'] === 'admin' && $role === 'user') {
                    $adminRows = $conn->query("SELECT user_id FROM user_tbl WHERE user_role = 'admin' FOR UPDATE");
                    if (count($adminRows->fetch_all(PDO::FETCH_ASSOC)) <= 1) {
                        $conn->rollback();
                        $dashboardNotice = 'The last administrator cannot be changed to a customer account.';
                        $dashboardNoticeIsError = true;
                    }
                }

                if ($dashboardNotice === '') {
                    $statement = $conn->prepare('UPDATE user_tbl SET user_role = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?');
                    $statement->bind_param('si', $role, $targetUserId);
                    $statement->execute();
                    $statement->close();
                    $conn->commit();
                    $_SESSION['admin_dashboard_notice'] = 'User privileges updated successfully.';
                    header('Location: admin-dashboard#user-privileges');
                    exit;
                }
            }
        } catch (PDOException $error) {
            $conn->rollback();
            error_log('Admin dashboard privilege update failed: ' . $error->getMessage());
            $dashboardNotice = 'The user privilege could not be updated. Please try again.';
            $dashboardNoticeIsError = true;
        }
    }
}

if (isset($_SESSION['admin_dashboard_notice']) && $dashboardNotice === '') {
    $dashboardNotice = (string) $_SESSION['admin_dashboard_notice'];
    unset($_SESSION['admin_dashboard_notice']);
}

$siteRoot = '../';
$pageTitle = 'Admin dashboard | HoloVerse';
$pageStylesheet = $siteRoot . 'assets/css/admin-dashboard.css?v=' . filemtime(__DIR__ . '/../Assets/css/admin-dashboard.css');
require __DIR__ . '/../app/views/layout/head.php';

$stats = [
    'products' => 0,
    'active_products' => 0,
    'users' => 0,
    'orders' => 0,
    'low_stock' => 0,
];

$result = $conn->query('SELECT COUNT(*) AS total FROM product_tbl');
if ($result && $row = $result->fetch_assoc()) {
    $stats['products'] = (int) ($row['total'] ?? 0);
}

$result = $conn->query("SELECT COUNT(*) AS total FROM product_tbl WHERE status = 'active'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['active_products'] = (int) ($row['total'] ?? 0);
}

$result = $conn->query('SELECT COUNT(*) AS total FROM user_tbl');
if ($result && $row = $result->fetch_assoc()) {
    $stats['users'] = (int) ($row['total'] ?? 0);
}

$result = $conn->query('SELECT COUNT(*) AS total FROM order_tbl');
if ($result && $row = $result->fetch_assoc()) {
    $stats['orders'] = (int) ($row['total'] ?? 0);
}

$result = $conn->query('SELECT COUNT(*) AS total FROM product_variants_tbl WHERE stock_quantity <= 5');
if ($result && $row = $result->fetch_assoc()) {
    $stats['low_stock'] = (int) ($row['total'] ?? 0);
}

$inventoryResult = $conn->query(
    'SELECT COUNT(*) AS total_variants,
            COALESCE(SUM(stock_quantity), 0) AS total_units,
            COUNT(*) FILTER (WHERE stock_quantity = 0) AS out_of_stock,
            COUNT(*) FILTER (WHERE stock_quantity BETWEEN 1 AND 5) AS low_stock,
            COUNT(*) FILTER (WHERE stock_quantity BETWEEN 6 AND 20) AS watch_stock,
            COUNT(*) FILTER (WHERE stock_quantity > 20) AS healthy_stock
     FROM product_variants_tbl'
);
$inventory = $inventoryResult ? $inventoryResult->fetch_assoc() : [];
$inventory = $inventory ?: [
    'total_variants' => 0,
    'total_units' => 0,
    'out_of_stock' => 0,
    'low_stock' => 0,
    'watch_stock' => 0,
    'healthy_stock' => 0,
];

$lowStockResult = $conn->query(
    'SELECT p.product_name, pv.variant_name, pv.sku, pv.stock_quantity
     FROM product_variants_tbl pv
     INNER JOIN product_tbl p ON p.product_id = pv.product_id
     WHERE pv.stock_quantity <= 5
     ORDER BY pv.stock_quantity ASC, pv.variant_id DESC
     LIMIT 4'
);
$lowStockItems = $lowStockResult ? $lowStockResult->fetch_all(PDO::FETCH_ASSOC) : [];

$openOrdersResult = $conn->query(
    "SELECT COUNT(*) AS total FROM order_tbl WHERE status IN ('pending', 'pending_payment')"
);
$openOrders = $openOrdersResult ? (int) ($openOrdersResult->fetch_assoc()['total'] ?? 0) : 0;

$liveSale = null;
if (store_flash_sale_table_exists($conn)) {
    $liveSaleResult = $conn->query(
        "SELECT p.product_name AS title,
                ROUND((v.compare_at_price - v.price) * 100 / NULLIF(v.compare_at_price, 0)) AS discount_percent,
                fs.ends_at
         FROM flash_sale_tbl fs
         INNER JOIN product_variants_tbl v ON v.variant_id = fs.variant_id
         INNER JOIN product_tbl p ON p.product_id = v.product_id
         WHERE fs.status = 'scheduled'
           AND fs.starts_at <= CURRENT_TIMESTAMP
           AND fs.ends_at > CURRENT_TIMESTAMP
         ORDER BY fs.ends_at ASC
         LIMIT 1"
    );
    $liveSale = $liveSaleResult ? $liveSaleResult->fetch_assoc() : null;
}

$productsResult = $conn->query(
    'SELECT p.product_id, p.product_name, p.status, p.created_at,
            c.category_name, COALESCE(MIN(pv.price), 0) AS price,
            COALESCE(SUM(pv.stock_quantity), 0) AS stock_quantity,
            (SELECT pi.image_url
             FROM product_image_tbl pi
             WHERE pi.product_id = p.product_id
             ORDER BY pi.sort_order, pi.image_id
             LIMIT 1) AS image_url,
            (SELECT pi.alt_text
             FROM product_image_tbl pi
             WHERE pi.product_id = p.product_id
             ORDER BY pi.sort_order, pi.image_id
             LIMIT 1) AS image_alt_text
     FROM product_tbl p
     LEFT JOIN category_tbl c ON c.category_id = p.category_id
     LEFT JOIN product_variants_tbl pv ON pv.product_id = p.product_id
     GROUP BY p.product_id, p.product_name, p.status, p.created_at, c.category_name
     ORDER BY p.created_at DESC
     LIMIT 5'
);
$recentProducts = $productsResult ? $productsResult->fetch_all(PDO::FETCH_ASSOC) : [];

$ordersResult = $conn->query(
    'SELECT o.order_id, o.order_number, o.status, o.created_at, u.username, COALESCE(SUM(oi.quantity), 0) AS item_count
     FROM order_tbl o
     LEFT JOIN user_tbl u ON u.user_id = o.user_id
     LEFT JOIN order_items_tbl oi ON oi.order_id = o.order_id
     GROUP BY o.order_id, o.order_number, o.status, o.created_at, u.username
     ORDER BY o.created_at DESC
     LIMIT 5'
);
$recentOrders = $ordersResult ? $ordersResult->fetch_all(PDO::FETCH_ASSOC) : [];

$usersResult = $conn->query(
    'SELECT u.user_id, u.username, u.email, u.user_role, u.created_at,
            COUNT(o.order_id) AS order_count
     FROM user_tbl u
     LEFT JOIN order_tbl o ON o.user_id = u.user_id
     GROUP BY u.user_id, u.username, u.email, u.user_role, u.created_at
     ORDER BY (u.user_id = ' . (int) $_SESSION['user_id'] . ') DESC, u.created_at DESC'
);
$recentUsers = $usersResult ? $usersResult->fetch_all(PDO::FETCH_ASSOC) : [];
$adminCount = count(array_filter(
    $recentUsers,
    static fn (array $user): bool => $user['user_role'] === 'admin'
));
$dashboardInitials = static function (string $value, int $length = 1): string {
    if (preg_match_all('/./us', $value, $characters) === false) {
        return strtoupper(substr($value, 0, $length));
    }

    $initials = implode('', array_slice($characters[0], 0, $length));

    return $initials === '' ? 'A' : strtoupper($initials);
};
$currentUserName = (string) ($_SESSION['username'] ?? 'Administrator');
$dashboardDate = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$activePage = 'admin-dashboard';

$quickLinks = [
    ['number' => '01', 'label' => 'Add product', 'detail' => 'Create a listing', 'href' => 'product-input', 'icon' => 'plus'],
    ['number' => '02', 'label' => 'Manage flash sales', 'detail' => 'Schedule campaigns', 'href' => 'flash-sale-admin', 'icon' => 'tag'],
    ['number' => '03', 'label' => 'Inventory list', 'detail' => 'Products and stock', 'href' => 'inventory', 'icon' => 'box'],
    ['number' => '04', 'label' => 'Order queue', 'detail' => 'Recent transactions', 'href' => '#recent-orders', 'icon' => 'bag'],
    ['number' => '05', 'label' => 'Customer accounts', 'detail' => 'Access control', 'href' => '#user-privileges', 'icon' => 'users'],
];
?>

<body>
    <div class="page-shell admin-dashboard-shell ops-page">
        <?php require __DIR__ . '/../app/views/components/site-header.php'; ?>
        <main class="ops-main">
        <div class="ops-dashboard">
            <section class="ops-intro" aria-labelledby="dashboard-title">
                <div class="ops-intro-top">
                    <div class="ops-intro-copy">
                        <p class="ops-overline"><span aria-hidden="true"></span> STORE ADMINISTRATION <small>/ 001</small></p>
                        <h1 id="dashboard-title">Admin dashboard<span>.</span></h1>
                        <p>Store operations at a glance. Welcome back, <strong><?= store_escape($currentUserName) ?></strong>.</p>
                    </div>
                    <div class="ops-intro-aside">
                        <span class="ops-aside-label">TODAY AT HOLOVERSE</span>
                        <time class="ops-aside-date" datetime="<?= $dashboardDate->format('Y-m-d') ?>"><?= store_escape($dashboardDate->format('l, F j, Y')) ?></time>
                        <span class="ops-aside-foot"><i aria-hidden="true"></i> All systems operational</span>
                    </div>
                </div>

                <?php if ($dashboardNotice !== ''): ?>
                    <p class="ops-notice<?= $dashboardNoticeIsError ? ' ops-notice-error' : ' ops-notice-success' ?>" role="<?= $dashboardNoticeIsError ? 'alert' : 'status' ?>"><?= store_escape($dashboardNotice) ?></p>
                <?php endif; ?>

                <div class="ops-message-row">
                    <p><svg class="ops-inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg><strong><?= $openOrders ?></strong> orders awaiting action <a href="#recent-orders">View queue <svg class="ops-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></a></p>
                    <p class="ops-message-sale"><svg class="ops-inline-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-12h-7l1-8Z" /></svg>
                        <?php if ($liveSale): ?>
                            <strong><?= store_escape((string) $liveSale['title']) ?></strong> is live / <?= (int) ($liveSale['discount_percent'] ?? 0) ?>% off / ends <?= store_escape(date('M j', strtotime((string) $liveSale['ends_at']))) ?>
                            <a href="<?= store_escape($siteRoot . 'flash-sale-admin') ?>">Manage <svg class="ops-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></a>
                        <?php else: ?>
                            Campaigns are managed on the flash sale desk
                            <a href="<?= store_escape($siteRoot . 'flash-sale-admin') ?>">Open desk <svg class="ops-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></a>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="ops-actions-caption"><span>QUICK ACTIONS</span><span>TOOLS FOR DAY-TO-DAY OPERATIONS</span></div>
                <nav class="ops-actions" aria-label="Quick actions">
                    <?php foreach ($quickLinks as $link): ?>
                        <a class="ops-action<?= $link['number'] === '01' ? ' ops-action-primary' : '' ?>" href="<?= store_escape(str_starts_with($link['href'], '#') ? $link['href'] : $siteRoot . $link['href']) ?>">
                            <span class="ops-action-top"><span class="ops-action-icon" aria-hidden="true">
                                <?php if ($link['icon'] === 'plus'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                                <?php elseif ($link['icon'] === 'tag'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.6 13.4 11 23l-9-9V2h12l9 9a1.8 1.8 0 0 1-.4 2.4Z" /><circle cx="7" cy="7" r="1" /></svg>
                                <?php elseif ($link['icon'] === 'box'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z" /><path d="m3 8 9 5 9-5M3 8v9l9 5 9-5V8M12 13v9" /></svg>
                                <?php elseif ($link['icon'] === 'bag'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l1 13H4L5 8Z" /><path d="M9 9V6a3 3 0 0 1 6 0v3" /></svg>
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                                <?php endif; ?>
                            </span><span><?= store_escape($link['number']) ?> / 05</span></span>
                            <span class="ops-action-bottom"><span><strong><?= store_escape($link['label']) ?></strong><small><?= store_escape($link['detail']) ?></small></span><svg class="ops-arrow-icon ops-action-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10" /></svg></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </section>

            <section class="ops-metrics-block" aria-label="Store metrics">
                <div class="ops-block-caption"><span>STORE OVERVIEW</span><span>LIVE METRICS / ALL TIME</span></div>
                <div class="ops-metric-grid">
                    <?php
                    $metrics = [
                        ['label' => 'Total products', 'value' => $stats['products'], 'caption' => 'Catalog listings', 'href' => 'inventory', 'icon' => 'box'],
                        ['label' => 'Active listings', 'value' => $stats['active_products'], 'caption' => 'Published now', 'href' => 'inventory', 'icon' => 'layers'],
                        ['label' => 'Registered users', 'value' => $stats['users'], 'caption' => 'Customer accounts', 'href' => '#user-privileges', 'icon' => 'users'],
                        ['label' => 'Orders', 'value' => $stats['orders'], 'caption' => 'All time', 'href' => '#recent-orders', 'icon' => 'bag'],
                        ['label' => 'Low stock', 'value' => $stats['low_stock'], 'caption' => '5 units or fewer', 'href' => '#stock-health-panel', 'icon' => 'alert'],
                    ];
                    foreach ($metrics as $index => $metric):
                        $isWarning = $index === 4;
                    ?>
                        <a class="ops-metric<?= $isWarning ? ' ops-metric-warning' : '' ?>" href="<?= store_escape(str_starts_with($metric['href'], '#') ? $metric['href'] : $siteRoot . $metric['href']) ?>">
                            <span class="ops-metric-top"><span>0<?= $index + 1 ?> / METRIC</span><span class="ops-metric-icon" aria-hidden="true">
                                <?php if ($metric['icon'] === 'box'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z" /><path d="m3 8 9 5 9-5M3 8v9l9 5 9-5V8M12 13v9" /></svg>
                                <?php elseif ($metric['icon'] === 'layers'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 5-9 5-9-5 9-5Z" /><path d="m3 12 9 5 9-5M3 16l9 5 9-5" /></svg>
                                <?php elseif ($metric['icon'] === 'users'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                                <?php elseif ($metric['icon'] === 'bag'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l1 13H4L5 8Z" /><path d="M9 9V6a3 3 0 0 1 6 0v3" /></svg>
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m10.3 3.2-8 14A2 2 0 0 0 4 20h16a2 2 0 0 0 1.7-2.8l-8-14a2 2 0 0 0-3.4 0Z" /><path d="M12 9v4m0 4h.01" /></svg>
                                <?php endif; ?>
                            </span></span>
                            <span class="ops-metric-label"><?= store_escape($metric['label']) ?></span>
                            <strong class="ops-metric-number"><?= number_format((int) $metric['value']) ?></strong>
                            <span class="ops-metric-bottom"><?= store_escape($metric['caption']) ?><svg class="ops-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10" /></svg></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="ops-tables-grid">
                <section class="ops-panel" id="latest-products" aria-labelledby="latest-products-title">
                    <div class="ops-section-head">
                        <div class="ops-section-heading"><div><p>CATALOGUE</p><h2 id="latest-products-title">Latest products</h2><small>The five newest additions to your store</small></div></div>
                        <a class="ops-text-link" href="<?= store_escape($siteRoot . 'product-input') ?>">Add product <svg class="ops-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10" /></svg></a>
                    </div>
                    <div class="ops-table-scroll">
                        <table class="ops-table ops-product-table">
                            <thead><tr><th>PRODUCT</th><th>STATUS</th><th>PRICE</th><th>STOCK</th></tr></thead>
                            <tbody>
                                <?php if ($recentProducts): ?>
                                    <?php foreach ($recentProducts as $index => $product): ?>
                                        <?php
                                        $productName = (string) $product['product_name'];
                                        $productInitials = $dashboardInitials($productName, 2);
                                        $productStatus = preg_replace('/[^a-z0-9-]/', '-', strtolower((string) $product['status']));
                                        $productImageUrl = is_string($product['image_url'] ?? null) && $product['image_url'] !== ''
                                            ? store_asset_url($product['image_url'], $siteRoot)
                                            : '';
                                        ?>
                                        <tr>
                                            <td><div class="ops-product-cell"><?php if ($productImageUrl !== ''): ?><img class="ops-product-image" src="<?= store_escape($productImageUrl) ?>" alt="<?= store_escape(is_string($product['image_alt_text'] ?? null) && $product['image_alt_text'] !== '' ? $product['image_alt_text'] : $productName) ?>" loading="lazy" decoding="async"><?php else: ?><span class="ops-product-monogram ops-product-color-<?= $index % 5 ?>"><?= store_escape($productInitials) ?></span><?php endif; ?><span class="ops-product-text"><strong title="<?= store_escape($productName) ?>"><?= store_escape($productName) ?></strong><small><?= store_escape((string) ($product['category_name'] ?? 'Uncategorised')) ?> / Added <?= store_escape(date('M j, Y', strtotime((string) $product['created_at']))) ?></small></span></div></td>
                                            <td><span class="ops-status ops-status-<?= store_escape((string) $productStatus) ?>"><i aria-hidden="true"></i><?= store_escape((string) $product['status']) ?></span></td>
                                            <td class="ops-table-price"><?= store_escape(store_format_price((float) $product['price'])) ?></td>
                                            <td><span class="ops-stock-value<?= (int) $product['stock_quantity'] <= 5 ? ' ops-stock-critical' : '' ?>"><?= (int) $product['stock_quantity'] ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td class="ops-empty-cell" colspan="4">No products have been added yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="ops-panel-foot"><span>SHOWING <strong><?= count($recentProducts) ?></strong> OF <strong><?= number_format($stats['products']) ?></strong> PRODUCTS</span><a href="<?= store_escape($siteRoot . 'inventory') ?>">OPEN INVENTORY <svg class="ops-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></a></div>
                </section>

                <section class="ops-panel" id="recent-orders" aria-labelledby="recent-orders-title">
                    <div class="ops-section-head">
                        <div class="ops-section-heading"><div><p>FULFILMENT</p><h2 id="recent-orders-title">Recent orders</h2><small>The five most recent transactions</small></div></div>
                        <span class="ops-section-count"><?= number_format($stats['orders']) ?> TOTAL</span>
                    </div>
                    <div class="ops-table-scroll">
                        <table class="ops-table ops-orders-table">
                            <thead><tr><th>ORDER</th><th>CUSTOMER</th><th>STATUS</th><th>ITEMS</th></tr></thead>
                            <tbody>
                                <?php if ($recentOrders): ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <?php
                                        $orderStatus = preg_replace('/[^a-z0-9-]/', '-', strtolower((string) $order['status']));
                                        $customerName = (string) ($order['username'] ?? 'Guest');
                                        ?>
                                        <tr>
                                            <td><div class="ops-order-cell"><strong><?= store_escape((string) $order['order_number']) ?></strong><small><?= store_escape(date('M j, Y', strtotime((string) $order['created_at']))) ?></small></div></td>
                                            <td><span class="ops-order-customer"><i><?= store_escape($dashboardInitials($customerName)) ?></i><span title="<?= store_escape($customerName) ?>"><?= store_escape($customerName) ?></span></span></td>
                                            <td><span class="ops-status ops-status-<?= store_escape((string) $orderStatus) ?>"><i aria-hidden="true"></i><?= store_escape((string) $order['status']) ?></span></td>
                                            <td class="ops-order-count"><?= (int) $order['item_count'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td class="ops-empty-cell" colspan="4">No orders yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="ops-panel-foot"><span>SHOWING THE LAST <strong><?= count($recentOrders) ?></strong> ORDERS</span><a href="#user-privileges">CUSTOMER ACCOUNTS <svg class="ops-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></a></div>
                </section>
            </div>

            <div class="ops-lower-grid">
                <section class="ops-panel ops-privileges" id="user-privileges" aria-labelledby="privileges-title">
                    <div class="ops-section-head">
                        <div class="ops-section-heading"><div><p>ACCESS CONTROL</p><h2 id="privileges-title">User privileges</h2><small>Manage who can access the admin console</small></div></div>
                        <span class="ops-privileges-total"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" /><path d="m9 12 2 2 4-4" /></svg> <?= $adminCount ?> ADMINISTRATOR<?= $adminCount === 1 ? '' : 'S' ?></span>
                    </div>
                    <div class="ops-privileges-filters" role="tablist" aria-label="Filter accounts">
                        <button type="button" role="tab" aria-selected="true" class="ops-filter-active" data-account-filter="all">All accounts <span><?= count($recentUsers) ?></span></button>
                        <button type="button" role="tab" aria-selected="false" data-account-filter="admin">Administrators <span><?= $adminCount ?></span></button>
                        <button type="button" role="tab" aria-selected="false" data-account-filter="user">Customers <span><?= count($recentUsers) - $adminCount ?></span></button>
                    </div>
                    <div class="ops-table-scroll">
                        <table class="ops-table ops-accounts-table">
                            <thead><tr><th>ACCOUNT</th><th>ORDERS</th><th>ACCESS LEVEL</th><th>ACTION</th></tr></thead>
                            <tbody>
                                <?php if ($recentUsers): ?>
                                    <?php foreach ($recentUsers as $user): ?>
                                        <?php
                                        $isCurrentUser = (int) $user['user_id'] === (int) $_SESSION['user_id'];
                                        $isLastAdmin = $user['user_role'] === 'admin' && $adminCount <= 1;
                                        $accountInitial = $dashboardInitials((string) $user['username']);
                                        ?>
                                        <tr data-account-row data-role="<?= store_escape((string) $user['user_role']) ?>">
                                            <td><div class="ops-account-cell"><span class="ops-account-monogram"><?= store_escape($accountInitial) ?></span><span><strong><?= store_escape((string) $user['username']) ?><?= $isCurrentUser ? ' <em>YOU</em>' : '' ?></strong><small><?= store_escape((string) $user['email']) ?></small><small>Joined <?= store_escape(date('M j, Y', strtotime((string) $user['created_at']))) ?></small></span></div></td>
                                            <td class="ops-account-orders"><strong><?= (int) $user['order_count'] ?></strong><small>orders</small></td>
                                            <?php if ($isCurrentUser): ?>
                                                <td><span class="ops-role-current ops-role-self">ADMINISTRATOR</span><small class="ops-role-lock">Your access is locked</small></td>
                                                <td class="ops-account-action"><span class="ops-role-current">CURRENT</span></td>
                                            <?php else: ?>
                                                <td>
                                                    <form id="privilege-form-<?= (int) $user['user_id'] ?>" class="ops-privilege-form" method="post" action="<?= store_escape($siteRoot . 'admin-dashboard#user-privileges') ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                                        <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                                        <label class="sr-only" for="role-<?= (int) $user['user_id'] ?>">Access level for <?= store_escape((string) $user['username']) ?></label>
                                                        <select id="role-<?= (int) $user['user_id'] ?>" name="role">
                                                            <option value="user"<?= $user['user_role'] === 'user' ? ' selected' : '' ?><?= $isLastAdmin ? ' disabled' : '' ?>>Customer</option>
                                                            <option value="admin"<?= $user['user_role'] === 'admin' ? ' selected' : '' ?>>Administrator</option>
                                                        </select>
                                                    </form>
                                                </td>
                                                <td class="ops-account-action"><button class="ops-save-role" type="submit" form="privilege-form-<?= (int) $user['user_id'] ?>">SAVE ROLE <svg class="ops-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></button></td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td class="ops-empty-cell" colspan="4">No user accounts found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="ops-empty-filter" data-account-empty hidden>No accounts in this category.</div>
                    <div class="ops-panel-foot"><span>ROLE CHANGES ARE CSRF-PROTECTED</span><span>YOUR ACCESS AND THE LAST ADMINISTRATOR CANNOT BE CHANGED</span></div>
                </section>

                <section class="ops-panel ops-health-panel" id="stock-health-panel" aria-labelledby="stock-health-title">
                    <div class="ops-section-head">
                        <div class="ops-section-heading"><div><p>INVENTORY SIGNAL</p><h2 id="stock-health-title">Stock health</h2><small><?= number_format((int) $inventory['total_variants']) ?> variants tracked</small></div></div>
                        <a class="ops-text-link" href="<?= store_escape($siteRoot . 'inventory') ?>">View inventory <svg class="ops-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10" /></svg></a>
                    </div>
                    <div class="ops-health-content">
                        <div class="ops-health-summary"><strong><?= number_format((int) $inventory['total_units']) ?></strong><span>UNITS ON HAND</span></div>
                        <?php
                        $stockGroups = [
                            ['label' => 'Out of stock', 'count' => (int) $inventory['out_of_stock'], 'class' => 'ops-health-out'],
                            ['label' => 'Low / 1 to 5 units', 'count' => (int) $inventory['low_stock'], 'class' => 'ops-health-low'],
                            ['label' => 'Watch / 6 to 20 units', 'count' => (int) $inventory['watch_stock'], 'class' => 'ops-health-watch'],
                            ['label' => 'Healthy / 21+ units', 'count' => (int) $inventory['healthy_stock'], 'class' => 'ops-health-good'],
                        ];
                        ?>
                        <div class="ops-health-bar" role="img" aria-label="Inventory stock distribution">
                            <?php foreach ($stockGroups as $group): ?>
                                <span class="<?= store_escape($group['class']) ?>" title="<?= store_escape($group['label'] . ': ' . $group['count']) ?>" style="width: <?= $inventory['total_variants'] ? round($group['count'] / (int) $inventory['total_variants'] * 100, 2) : 0 ?>%"></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="ops-health-legend">
                            <?php foreach ($stockGroups as $group): ?>
                                <div><i class="<?= store_escape($group['class']) ?>"></i><span><?= store_escape($group['label']) ?></span><strong><?= number_format($group['count']) ?></strong></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="ops-health-divider"><span>REQUIRES RESTOCK</span><span>5 UNITS OR FEWER</span></div>
                        <div class="ops-health-items">
                            <?php if ($lowStockItems): ?>
                                <?php foreach ($lowStockItems as $item): ?>
                                    <div class="ops-health-item"><span><strong title="<?= store_escape((string) $item['product_name']) ?>"><?= store_escape((string) $item['product_name']) ?></strong><small><?= store_escape((string) $item['sku']) ?> / <?= store_escape((string) $item['variant_name']) ?></small></span><b><?= (int) $item['stock_quantity'] === 0 ? 'OUT' : (int) $item['stock_quantity'] . ' LEFT' ?></b></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="ops-health-clear">All variants have more than five units in stock.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ops-panel-foot"><span><strong><?= number_format((int) $stats['low_stock']) ?></strong> VARIANTS NEED ATTENTION</span><a href="<?= store_escape($siteRoot . 'inventory') ?>">REVIEW INVENTORY <svg class="ops-arrow-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg></a></div>
                </section>
            </div>

            <footer class="ops-dashboard-footer"><span>HOLOVERSE / OPERATIONS CONTROL</span><span>INVENTORY AND PROMOTIONS ARE MANAGED ON SEPARATE PAGES / <a href="<?= store_escape($siteRoot . 'inventory') ?>">INVENTORY</a> / <a href="<?= store_escape($siteRoot . 'flash-sale-admin') ?>">FLASH SALES</a></span></footer>
        </div>
        </main>
        <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    </div>
    <script>
        (() => {
            const filters = document.querySelectorAll("[data-account-filter]");
            const rows = document.querySelectorAll("[data-account-row]");
            const emptyMessage = document.querySelector("[data-account-empty]");

            filters.forEach((filter) => {
                filter.addEventListener("click", () => {
                    const selectedRole = filter.dataset.accountFilter;
                    let visibleCount = 0;

                    filters.forEach((button) => {
                        const selected = button === filter;
                        button.setAttribute("aria-selected", String(selected));
                        button.classList.toggle("ops-filter-active", selected);
                    });

                    rows.forEach((row) => {
                        const visible = selectedRole === "all" || row.dataset.role === selectedRole;
                        row.hidden = !visible;
                        visibleCount += Number(visible);
                    });

                    if (emptyMessage) {
                        emptyMessage.hidden = visibleCount > 0;
                    }
                });
            });
        })();
    </script>
</body>

</html>
