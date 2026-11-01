<?php
require_once __DIR__ . '/../app/store.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$orders = [];
$ordersLoadError = false;
$requestedOrder = isset($_GET['order']) && is_string($_GET['order'])
    ? trim($_GET['order'])
    : '';
$hasRequestedItem = array_key_exists('item', $_GET);
$requestedItemId = $hasRequestedItem
    ? filter_var($_GET['item'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : null;

try {
    $statement = $conn->prepare(
        'SELECT o.order_id, o.order_number, o.status, o.payment_method, o.currency,
                o.shipping_fee, o.tax, o.created_at,
                oi.order_item_id, oi.product_name_snapshot, oi.variant_name_snapshot,
                oi.unit_price, oi.quantity,
                p.product_name AS current_product_name,
                (SELECT pi.image_url
                 FROM product_image_tbl pi
                 WHERE pi.product_id = p.product_id
                 ORDER BY pi.sort_order, pi.image_id
                 LIMIT 1) AS product_image_url,
                oa.recipient_name, oa.address_line1, oa.address_line2,
                oa.city, oa.region, oa.postal_code, oa.country_code
         FROM order_tbl o
         LEFT JOIN order_items_tbl oi ON oi.order_id = o.order_id
         LEFT JOIN product_variants_tbl v ON v.variant_id = oi.variant_id
         LEFT JOIN product_tbl p ON p.product_id = v.product_id
         LEFT JOIN order_address_tbl oa ON oa.order_id = o.order_id AND oa.address_type = \'shipping\'
         WHERE o.user_id = ?
         ORDER BY o.created_at DESC, oi.order_item_id ASC'
    );
    $statement->bind_param('i', $userId);
    $statement->execute();
    $orderRows = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();

    foreach ($orderRows as $row) {
        $orderId = (int) $row['order_id'];
        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'order_number' => (string) $row['order_number'],
                'status' => (string) $row['status'],
                'payment_method' => (string) ($row['payment_method'] ?? ''),
                'currency' => (string) $row['currency'],
                'shipping_fee' => (float) $row['shipping_fee'],
                'tax' => (float) $row['tax'],
                'created_at' => (string) $row['created_at'],
                'recipient_name' => (string) ($row['recipient_name'] ?? ''),
                'address_line1' => (string) ($row['address_line1'] ?? ''),
                'address_line2' => (string) ($row['address_line2'] ?? ''),
                'city' => (string) ($row['city'] ?? ''),
                'region' => (string) ($row['region'] ?? ''),
                'postal_code' => (string) ($row['postal_code'] ?? ''),
                'country_code' => (string) ($row['country_code'] ?? ''),
                'items' => [],
                'subtotal' => 0.0,
            ];
        }

        if ($row['order_item_id'] !== null) {
            $quantity = (int) $row['quantity'];
            $unitPrice = (float) $row['unit_price'];
            $orders[$orderId]['items'][] = [
                'order_item_id' => (int) $row['order_item_id'],
                'product_name' => (string) $row['product_name_snapshot'],
                'product_url' => is_string($row['current_product_name']) && $row['current_product_name'] !== ''
                    ? store_product_url((string) $row['current_product_name'])
                    : null,
                'image_url' => is_string($row['product_image_url']) && $row['product_image_url'] !== ''
                    ? store_asset_url((string) $row['product_image_url'], '../')
                    : null,
                'variant_name' => (string) $row['variant_name_snapshot'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ];
            $orders[$orderId]['subtotal'] += $unitPrice * $quantity;
        }
    }
} catch (PDOException $error) {
    error_log('Order history load failed: ' . $error->getMessage());
    $ordersLoadError = true;
    http_response_code(500);
}

if ($requestedOrder !== '' || $hasRequestedItem) {
    $orders = array_filter($orders, static function (array $order) use ($requestedOrder, $hasRequestedItem, $requestedItemId): bool {
        if ($requestedOrder !== '' && $order['order_number'] !== $requestedOrder) {
            return false;
        }
        if (!$hasRequestedItem) {
            return true;
        }
        if (!is_int($requestedItemId)) {
            return false;
        }

        return count(array_filter(
            $order['items'],
            static fn (array $item): bool => $item['order_item_id'] === $requestedItemId
        )) > 0;
    });

    if ($hasRequestedItem && is_int($requestedItemId)) {
        foreach ($orders as &$order) {
            $order['items'] = array_values(array_filter(
                $order['items'],
                static fn (array $item): bool => $item['order_item_id'] === $requestedItemId
            ));
        }
        unset($order);
    }
}

$pageTitle = 'Your Orders | HoloVerse';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isPurchaseOrderDetails = is_string($requestPath) && preg_match('~^/purchase/orders/?$~', $requestPath);
$pageLayout = $isPurchaseOrderDetails ? 'purchase-detail' : 'content';
$siteRoot = $isPurchaseOrderDetails
    ? '../../'
    : '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="order-history-page home-landing">
    <div class="page-shell">
        <?php
        $activePage = 'orders';
        $showLogin = true;
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="order-history-main landing-shell">
            <header class="order-history-heading">
                <h1>My Orders</h1>
                <p>Review the items, payment details, and delivery address for your orders.</p>
            </header>

            <?php if ($ordersLoadError): ?>
                <p class="order-history-notice" role="alert">Your orders could not be loaded right now. Please try again later.</p>
            <?php elseif ($orders === []): ?>
                <section class="order-history-empty">
                    <h2>No orders yet</h2>
                    <p>When you place an order, its details will appear here.</p>
                    <a href="<?= $siteRoot ?>products">Browse products</a>
                </section>
            <?php else: ?>
                <div class="order-history-list">
                    <?php foreach ($orders as $order): ?>
                        <?php foreach ($order['items'] ?: [null] as $item): ?>
                        <?php
                        $statusKey = strtolower($order['status']);
                        $progressSteps = ['Order placed', 'Processing', 'Shipped', 'Delivered'];
                        $statusSteps = [
                            'pending_payment' => 0,
                            'pending' => 0,
                            'processing' => 1,
                            'shipped' => 2,
                            'in_transit' => 2,
                            'confirmed' => 3,
                            'delivered' => 3,
                        ];
                        $isOrderClosed = in_array($statusKey, ['cancelled', 'canceled', 'refunded'], true);
                        $activeStep = $statusSteps[$statusKey] ?? 0;
                        ?>
                        <article class="order-history-card">
                            <header class="order-history-card-heading">
                                <div>
                                    <span class="order-history-label">Order placed</span>
                                    <h2><?= store_escape($order['order_number']) ?></h2>
                                    <time datetime="<?= store_escape(date('Y-m-d\TH:i:s', strtotime($order['created_at']))) ?>"><?= store_escape(date('M j, Y', strtotime($order['created_at']))) ?></time>
                                </div>
                                <span class="order-history-status"><?= store_escape(ucwords(str_replace('_', ' ', $order['status']))) ?></span>
                            </header>

                            <div class="order-history-delivery">
                                <section class="order-history-address">
                                    <h3>Delivery address</h3>
                                    <?php if ($order['recipient_name'] !== ''): ?>
                                        <p>
                                            <strong><?= store_escape($order['recipient_name']) ?></strong><br>
                                            <?= store_escape($order['address_line1']) ?>
                                            <?= $order['address_line2'] !== '' ? '<br>' . store_escape($order['address_line2']) : '' ?><br>
                                            <?= store_escape(implode(', ', array_filter([$order['city'], $order['region'], $order['postal_code']]))) ?><br>
                                            <?= store_escape($order['country_code']) ?>
                                        </p>
                                    <?php else: ?>
                                        <p>Shipping address unavailable.</p>
                                    <?php endif; ?>
                                </section>
                                <section class="order-history-progress" aria-label="Order progress">
                                    <h3>Delivery status</h3>
                                    <?php if ($isOrderClosed): ?>
                                        <p class="order-history-progress-closed">This order is <?= store_escape(str_replace('_', ' ', $statusKey)) ?>.</p>
                                    <?php else: ?>
                                        <ol class="order-history-timeline">
                                            <?php foreach ($progressSteps as $stepIndex => $stepLabel): ?>
                                                <?php $stepClass = $stepIndex < $activeStep ? 'is-complete' : ($stepIndex === $activeStep ? 'is-current' : ''); ?>
                                                <li class="<?= $stepClass ?>"<?= $stepIndex === $activeStep ? ' aria-current="step"' : '' ?>>
                                                    <span class="order-history-timeline-marker" aria-hidden="true"></span>
                                                    <span class="order-history-timeline-copy">
                                                        <strong><?= store_escape($stepLabel) ?></strong>
                                                        <?php if ($stepIndex === 0): ?>
                                                            <time datetime="<?= store_escape(date('Y-m-d\TH:i:s', strtotime($order['created_at']))) ?>"><?= store_escape(date('M j, Y · g:i A', strtotime($order['created_at']))) ?></time>
                                                        <?php elseif ($stepIndex === $activeStep && $statusKey === 'pending_payment'): ?>
                                                            <span>Waiting for payment confirmation</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ol>
                                    <?php endif; ?>
                                </section>
                            </div>

                            <div class="order-history-store">HoloVerse Store</div>
                            <div class="order-history-items">
                                <?php if ($item !== null): ?>
                                    <div class="order-history-item">
                                        <?php if ($item['product_url'] !== null): ?>
                                            <a class="order-history-item-image" href="<?= store_escape($item['product_url']) ?>" aria-label="View product: <?= store_escape($item['product_name']) ?>">
                                                <?php if ($item['image_url'] !== null): ?>
                                                    <img src="<?= store_escape($item['image_url']) ?>" alt="" loading="lazy">
                                                <?php else: ?>
                                                    <span aria-hidden="true">Image unavailable</span>
                                                <?php endif; ?>
                                            </a>
                                        <?php else: ?>
                                            <div class="order-history-item-image order-history-item-image--unavailable" aria-hidden="true">
                                                <?php if ($item['image_url'] !== null): ?>
                                                    <img src="<?= store_escape($item['image_url']) ?>" alt="" loading="lazy">
                                                <?php else: ?>
                                                    <span>Image unavailable</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="order-history-item-copy">
                                            <?php if ($item['product_url'] !== null): ?>
                                                <h3><a class="order-history-product-link" href="<?= store_escape($item['product_url']) ?>"><?= store_escape($item['product_name']) ?></a></h3>
                                            <?php else: ?>
                                                <h3><?= store_escape($item['product_name']) ?></h3>
                                            <?php endif; ?>
                                            <p><?= store_escape($item['variant_name']) ?> · Qty <?= $item['quantity'] ?></p>
                                        </div>
                                        <strong><?= store_escape(store_format_price($item['unit_price'] * $item['quantity'])) ?></strong>
                                    </div>
                                <?php else: ?>
                                    <p class="order-history-no-items">Product details are unavailable for this order.</p>
                                <?php endif; ?>
                            </div>

                            <div class="order-history-summary">
                                <div class="order-history-summary-row">
                                    <span>Merchandise subtotal</span>
                                    <strong><?= store_escape(store_format_price($order['subtotal'])) ?></strong>
                                </div>
                                <div class="order-history-summary-row">
                                    <span>Shipping fee</span>
                                    <strong><?= store_escape(store_format_price($order['shipping_fee'])) ?></strong>
                                </div>
                                <div class="order-history-summary-row">
                                    <span>Tax</span>
                                    <strong><?= store_escape(store_format_price($order['tax'])) ?></strong>
                                </div>
                                <div class="order-history-summary-total">
                                    <span>Order total</span>
                                    <strong><?= store_escape(store_format_price($order['subtotal'] + $order['tax'] + $order['shipping_fee'])) ?></strong>
                                </div>
                                <div class="order-history-payment">
                                    <span>Payment method</span>
                                    <strong><?= store_escape(ucwords(str_replace('_', ' ', $order['payment_method'] ?: 'Not specified'))) ?></strong>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>
