<?php
require_once __DIR__ . '/../app/store.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$requestedOrder = isset($_GET['order']) && is_string($_GET['order'])
    ? trim($_GET['order'])
    : '';
$pageSize = 5;
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$currentPage = $requestedPage === false || $requestedPage === null ? 1 : max(1, $requestedPage);
$purchases = [];
$purchaseProductCount = 0;
$pageCount = 1;
$purchaseLoadError = false;

try {
    $whereSql = ' WHERE o.user_id = ?';
    if ($requestedOrder !== '') {
        $whereSql .= ' AND o.order_number = ?';
    }

    $countSql =
        'SELECT COUNT(*) AS product_count
         FROM order_tbl o
         INNER JOIN order_items_tbl oi ON oi.order_id = o.order_id' . $whereSql;
    $countStatement = $conn->prepare($countSql);
    if ($requestedOrder !== '') {
        $countStatement->bind_param('is', $userId, $requestedOrder);
    } else {
        $countStatement->bind_param('i', $userId);
    }
    $countStatement->execute();
    $purchaseCounts = $countStatement->get_result()->fetch_assoc();
    $countStatement->close();
    $purchaseProductCount = (int) $purchaseCounts['product_count'];

    $pageCount = max(1, (int) ceil($purchaseProductCount / $pageSize));
    $currentPage = min($currentPage, $pageCount);
    $offset = ($currentPage - 1) * $pageSize;

    $sql =
        'SELECT o.order_number, o.status, o.created_at, oi.order_item_id,
                oi.product_name_snapshot, oi.variant_name_snapshot,
                oi.unit_price, oi.quantity,
                p.product_name AS current_product_name,
                (SELECT pi.image_url
                 FROM product_image_tbl pi
                 WHERE pi.product_id = p.product_id
                 ORDER BY pi.sort_order, pi.image_id
                 LIMIT 1) AS product_image_url
         FROM order_tbl o
         INNER JOIN order_items_tbl oi ON oi.order_id = o.order_id
         LEFT JOIN product_variants_tbl v ON v.variant_id = oi.variant_id
         LEFT JOIN product_tbl p ON p.product_id = v.product_id' . $whereSql .
        ' ORDER BY o.created_at DESC, oi.order_item_id ASC
         LIMIT ? OFFSET ?';
    $statement = $conn->prepare($sql);
    if ($requestedOrder !== '') {
        $statement->bind_param('isii', $userId, $requestedOrder, $pageSize, $offset);
    } else {
        $statement->bind_param('iii', $userId, $pageSize, $offset);
    }
    $statement->execute();
    $purchases = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();
} catch (PDOException $error) {
    error_log('Purchase page load failed: ' . $error->getMessage());
    $purchaseLoadError = true;
    http_response_code(500);
}

$buildPurchasePageUrl = static function (int $page) use ($requestedOrder): string {
    $query = ['page' => $page];
    if ($requestedOrder !== '') {
        $query['order'] = $requestedOrder;
    }

    return '?' . http_build_query($query);
};

$pageTitle = 'Your Purchases | HoloVerse';
$pageLayout = 'purchase';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="order-history-page home-landing">
    <div class="page-shell">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = 'orders';
        $showLogin = true;
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="order-history-main landing-shell">
            <header class="section-heading all-products-heading order-history-heading">
                <div>
                    <p class="eyebrow">Purchase history</p>
                    <h1>My purchases</h1>
                    <p>Review the products you’ve purchased and check each order’s status.</p>
                </div>
                <?php if (!$purchaseLoadError && $purchases !== []): ?>
                    <div class="section-heading-aside">
                        <span class="all-products-count"><?= $purchaseProductCount ?> <?= $purchaseProductCount === 1 ? 'product' : 'products' ?> purchased · <?= $pageSize ?> per page</span>
                    </div>
                <?php endif; ?>
            </header>

            <?php if ($purchaseLoadError): ?>
                <p class="order-history-notice" role="alert">Your purchases could not be loaded right now. Please try again later.</p>
            <?php elseif ($purchases === []): ?>
                <section class="order-history-empty">
                    <h2>No purchases found</h2>
                    <p>Your purchased products will appear here after you place an order.</p>
                    <a href="products">Browse products</a>
                </section>
            <?php else: ?>
                <div class="order-history-list">
                    <?php foreach ($purchases as $purchase): ?>
                        <?php
                        $statusKey = strtolower((string) $purchase['status']);
                        $statusLabel = ucwords(str_replace('_', ' ', $statusKey));
                        $statusMessage = match ($statusKey) {
                            'confirmed' => 'Your order is confirmed',
                            'delivered' => 'Your order has been delivered',
                            'shipped', 'in_transit' => 'Your order is on its way',
                            'cancelled', 'canceled', 'refunded' => 'This order is ' . strtolower($statusLabel),
                            default => 'Your order is being processed',
                        };
                        $imageUrl = is_string($purchase['product_image_url']) && $purchase['product_image_url'] !== ''
                            ? store_asset_url($purchase['product_image_url'], '../')
                            : null;
                        $itemTotal = (float) $purchase['unit_price'] * (int) $purchase['quantity'];
                        $orderDetailsUrl = '../purchase/orders?order=' . rawurlencode((string) $purchase['order_number'])
                            . '&item=' . (int) $purchase['order_item_id'];
                        ?>
                        <a class="order-history-card purchase-detail-card" href="<?= store_escape($orderDetailsUrl) ?>">
                            <header class="order-history-card-heading">
                                <div>
                                    <span class="order-history-label">Order placed</span>
                                    <h2><?= store_escape($purchase['order_number']) ?></h2>
                                    <time datetime="<?= store_escape(date('Y-m-d\TH:i:s', strtotime($purchase['created_at']))) ?>">
                                        <?= store_escape(date('M j, Y · g:i A', strtotime($purchase['created_at']))) ?>
                                    </time>
                                </div>
                                <div class="purchase-detail-status">
                                    <span><?= store_escape($statusMessage) ?></span>
                                    <strong><?= store_escape($statusLabel) ?></strong>
                                </div>
                            </header>

                            <div class="order-history-items">
                                <div class="order-history-item">
                                    <?php if ($imageUrl !== null): ?>
                                        <div class="order-history-item-image order-history-item-image--unavailable" aria-hidden="true">
                                            <img src="<?= store_escape($imageUrl) ?>" alt="" loading="lazy">
                                        </div>
                                    <?php else: ?>
                                        <div class="order-history-item-image order-history-item-image--unavailable" aria-hidden="true">
                                            <span>Image unavailable</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="order-history-item-copy">
                                        <h3><?= store_escape($purchase['product_name_snapshot']) ?></h3>
                                        <p><?= store_escape($purchase['variant_name_snapshot']) ?> · Qty <?= (int) $purchase['quantity'] ?></p>
                                    </div>
                                    <strong><?= store_escape(store_format_price($itemTotal)) ?></strong>
                                </div>
                            </div>

                            <div class="order-history-summary">
                                <div class="order-history-summary-total">
                                    <span>Product total</span>
                                    <strong><?= store_escape(store_format_price($itemTotal)) ?></strong>
                                </div>
                            </div>

                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if ($pageCount > 1): ?>
                    <?php
                    $pageNumbers = [];
                    for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                        if ($pageNumber === 1 || $pageNumber === $pageCount || abs($pageNumber - $currentPage) <= 1) {
                            $pageNumbers[] = $pageNumber;
                        }
                    }
                    $pagesWithEllipses = [];
                    $lastPageNumber = 0;
                    foreach ($pageNumbers as $pageNumber) {
                        if ($lastPageNumber > 0 && $pageNumber - $lastPageNumber > 1) {
                            $pagesWithEllipses[] = '...';
                        }
                        $pagesWithEllipses[] = $pageNumber;
                        $lastPageNumber = $pageNumber;
                    }
                    ?>
                    <nav class="all-products-pagination" aria-label="Purchase history pages">
                        <?php if ($currentPage > 1): ?>
                            <a class="all-products-page-button" href="<?= store_escape($buildPurchasePageUrl($currentPage - 1)) ?>" aria-label="Previous page">Prev</a>
                        <?php else: ?>
                            <span class="all-products-page-button is-disabled" aria-hidden="true">Prev</span>
                        <?php endif; ?>

                        <div class="all-products-page-list">
                            <?php foreach ($pagesWithEllipses as $pageNumber): ?>
                                <?php if ($pageNumber === '...'): ?>
                                    <span class="all-products-page-ellipsis" aria-hidden="true">&hellip;</span>
                                <?php elseif ($pageNumber === $currentPage): ?>
                                    <span class="all-products-page-number is-active" aria-current="page"><?= $pageNumber ?></span>
                                <?php else: ?>
                                    <a class="all-products-page-number" href="<?= store_escape($buildPurchasePageUrl((int) $pageNumber)) ?>" aria-label="Page <?= $pageNumber ?>"><?= $pageNumber ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($currentPage < $pageCount): ?>
                            <a class="all-products-page-button" href="<?= store_escape($buildPurchasePageUrl($currentPage + 1)) ?>" aria-label="Next page">Next</a>
                        <?php else: ?>
                            <span class="all-products-page-button is-disabled" aria-hidden="true">Next</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>
</html>
