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
                header('Location: admin-dashboard#accounts');
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
                    header('Location: admin-dashboard#accounts');
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

$productsResult = $conn->query(
    'SELECT p.product_id, p.product_name, p.status, p.created_at, pv.price, pv.stock_quantity
     FROM product_tbl p
     LEFT JOIN product_variants_tbl pv ON pv.product_id = p.product_id
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
    'SELECT user_id, username, email, user_role, created_at
     FROM user_tbl
     ORDER BY (user_id = ' . (int) $_SESSION['user_id'] . ') DESC, created_at DESC'
);
$recentUsers = $usersResult ? $usersResult->fetch_all(PDO::FETCH_ASSOC) : [];

$quickLinks = [
    ['label' => 'Add Product', 'href' => 'product-input', 'accent' => 'primary'],
    ['label' => 'Manage Flash Sales', 'href' => 'flash-sale-admin', 'accent' => 'secondary'],
    ['label' => 'Inventory List', 'href' => 'inventory', 'accent' => 'secondary'],
    ['label' => 'Order Queue', 'href' => '#orders', 'accent' => 'secondary'],
    ['label' => 'Customer Accounts', 'href' => '#accounts', 'accent' => 'secondary'],
];
?>

<body>
    <main class="page-shell admin-dashboard-shell">
        <?php require __DIR__ . '/../app/views/components/site-header.php'; ?>

        <section class="admin-dashboard">
            <header class="admin-dashboard-header">
                <div>
                    <p class="admin-kicker">Operations control</p>
                    <h1>Admin dashboard</h1>
                </div>
                <div class="admin-badge">Store Overview</div>
            </header>

            <?php if ($dashboardNotice !== ''): ?>
                <p class="admin-notice<?= $dashboardNoticeIsError ? ' admin-notice--error' : '' ?>" role="<?= $dashboardNoticeIsError ? 'alert' : 'status' ?>"><?= store_escape($dashboardNotice) ?></p>
            <?php endif; ?>

            <div class="admin-quick-actions" aria-label="Quick actions">
                <?php foreach ($quickLinks as $link): ?>
                    <a class="admin-quick-action admin-quick-action--<?= $link['accent'] ?>" href="<?= store_escape($link['href']) ?>"><?= store_escape($link['label']) ?></a>
                <?php endforeach; ?>
            </div>

            <div class="admin-stats-grid">
                <article class="admin-stat-card">
                    <span>Total products</span>
                    <strong><?= (int) $stats['products'] ?></strong>
                    <small>Catalog listings</small>
                </article>
                <article class="admin-stat-card">
                    <span>Active listings</span>
                    <strong><?= (int) $stats['active_products'] ?></strong>
                    <small>Published now</small>
                </article>
                <article class="admin-stat-card">
                    <span>Registered users</span>
                    <strong><?= (int) $stats['users'] ?></strong>
                    <small>Customer accounts</small>
                </article>
                <article class="admin-stat-card">
                    <span>Orders</span>
                    <strong><?= (int) $stats['orders'] ?></strong>
                    <small>All time</small>
                </article>
                <article class="admin-stat-card">
                    <span>Low stock</span>
                    <strong><?= (int) $stats['low_stock'] ?></strong>
                    <small>Need restock</small>
                </article>
            </div>

            <div class="admin-content-grid">
                <section class="admin-panel" id="products">
                    <div class="admin-panel-header">
                        <h2>Latest products</h2>
                        <a href="product-input">Add product</a>
                    </div>
                    <div class="admin-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentProducts): ?>
                                    <?php foreach ($recentProducts as $product): ?>
                                        <tr>
                                            <td><?= store_escape($product['product_name']) ?></td>
                                            <td><span class="admin-status admin-status--<?= store_escape($product['status'] === 'active' ? 'active' : 'draft') ?>"><?= store_escape($product['status']) ?></span></td>
                                            <td><?= store_escape(store_format_price((float) ($product['price'] ?? 0))) ?></td>
                                            <td><?= (int) ($product['stock_quantity'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No products have been added yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-panel" id="orders">
                    <div class="admin-panel-header">
                        <h2>Recent orders</h2>
                    </div>
                    <div class="admin-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Items</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentOrders): ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td><?= store_escape($order['order_number']) ?></td>
                                            <td><?= store_escape($order['username'] ?? 'Guest') ?></td>
                                            <td><span class="admin-status admin-status--<?= store_escape(in_array($order['status'], ['confirmed', 'paid', 'shipped'], true) ? 'active' : 'draft') ?>"><?= store_escape($order['status']) ?></span></td>
                                            <td><?= (int) $order['item_count'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No orders yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="admin-panel" id="accounts">
                    <div class="admin-panel-header">
                        <h2>User privileges</h2>
                    </div>
                    <p class="admin-panel-description">Choose whether each account has customer access or full administrator access. Your own administrator access cannot be changed here.</p>
                    <div class="admin-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Privilege</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentUsers): ?>
                                    <?php foreach ($recentUsers as $user): ?>
                                        <tr>
                                            <td><?= store_escape($user['username']) ?></td>
                                            <td><?= store_escape($user['email']) ?></td>
                                            <?php if ((int) $user['user_id'] === (int) $_SESSION['user_id']): ?>
                                                <td><span class="admin-status admin-status--active">Administrator (you)</span></td>
                                                <td>Self-managed access is disabled</td>
                                            <?php else: ?>
                                                <td colspan="2">
                                                    <form class="admin-privilege-form" method="post" action="admin-dashboard#accounts">
                                                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                                        <input type="hidden" name="user_id" value="<?= (int) $user['user_id'] ?>">
                                                        <label class="sr-only" for="role-<?= (int) $user['user_id'] ?>">Privilege for <?= store_escape($user['username']) ?></label>
                                                        <select id="role-<?= (int) $user['user_id'] ?>" name="role">
                                                            <option value="user"<?= $user['user_role'] === 'user' ? ' selected' : '' ?>>Customer</option>
                                                            <option value="admin"<?= $user['user_role'] === 'admin' ? ' selected' : '' ?>>Administrator</option>
                                                        </select>
                                                        <button type="submit">Save privilege</button>
                                                    </form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No users found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </section>
    </main>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
