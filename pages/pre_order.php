<?php
require_once __DIR__ . '/../app/store.php';

$talentParameter = $_GET['talent'] ?? '';
$selectedTalent = is_string($talentParameter) ? trim($talentParameter) : '';
$searchParameter = $_GET['search'] ?? '';
$productSearch = is_string($searchParameter) ? trim($searchParameter) : '';
$talentName = '';
$talentId = 0;
if ($selectedTalent !== '') {
    $statement = $conn->prepare('SELECT talent_id, talent_name FROM talent_tbl WHERE talent_name = ? OR talent_slug = ? LIMIT 1');
    $statement->bind_param('ss', $selectedTalent, $selectedTalent);
    $statement->execute();
    $selectedTalentRow = $statement->get_result()->fetch_assoc();
    $statement->close();
    if ($selectedTalentRow) {
        $talentId = (int) $selectedTalentRow['talent_id'];
        $talentName = $selectedTalentRow['talent_name'];
    }
}

$availableStock = store_available_stock_sql();
$sql = "SELECT p.product_id, p.product_name, p.description, p.expected_ship_date, c.category_name,
               v.variant_id, v.variant_name, v.price, v.currency, {$availableStock} AS stock_quantity
        FROM product_tbl p
        INNER JOIN category_tbl c ON c.category_id = p.category_id
        INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
        WHERE p.status = 'active' AND p.is_preorder = 1";
if ($productSearch !== '') {
    $sql .= ' AND (
        p.product_name ILIKE ?
        OR p.description ILIKE ?
        OR c.category_name ILIKE ?
        OR v.variant_name ILIKE ?
        OR EXISTS (
            SELECT 1
            FROM product_talents_tbl search_pt
            INNER JOIN talent_tbl search_t ON search_t.talent_id = search_pt.talent_id
            WHERE search_pt.product_id = p.product_id AND search_t.talent_name ILIKE ?
        )
    )';
}
if ($selectedTalent !== '') {
    $sql .= ' AND EXISTS (
        SELECT 1 FROM product_talents_tbl pt
        WHERE pt.product_id = p.product_id AND pt.talent_id = ?
    )';
}
$sql .= ' ORDER BY p.product_id';
if ($productSearch !== '' || $selectedTalent !== '') {
    $statement = $conn->prepare($sql);
    if ($productSearch !== '' && $selectedTalent !== '') {
        $searchPattern = '%' . $productSearch . '%';
        $statement->bind_param('sssssi', $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $talentId);
    } elseif ($productSearch !== '') {
        $searchPattern = '%' . $productSearch . '%';
        $statement->bind_param('sssss', $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    } else {
        $statement->bind_param('i', $talentId);
    }
    $statement->execute();
    $products = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();
} else {
    $products = $conn->query($sql)->fetch_all(PDO::FETCH_ASSOC);
}
$merchInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $talentName !== '' ? $talentName : 'HoloVerse'), 0, 1));

$pageTitle = 'Pre-Order | hololive merchandise';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell preorder-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>

        <?php
        $activePage = 'pre_order';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="preorder-main">
            <section class="preorder-intro">
                <span class="preorder-eyebrow">Pre-order window open</span>
                <?php if ($talentName !== ''): ?>
                    <h1><?= store_escape($talentName) ?> merchandise</h1>
                    <p>Browse the official merch collection for <?= store_escape($talentName) ?> and reserve your favorite pieces before they sell out.</p>
                <?php else: ?>
                    <h1>Get your next favorite before it arrives.</h1>
                    <p>Reserve limited-run collectibles and apparel while they're still in production. Browse the current preview, then check the order guide for what to expect.</p>
                <?php endif; ?>
            </section>

            <?php
            $catalogSearchAction = 'pre_order.php';
            $catalogSearchId = 'preorder-search';
            $catalogSearchLabel = 'Search pre-order products';
            $catalogSearchPlaceholder = 'Search pre-orders';
            $catalogSearchValue = $productSearch;
            $catalogSearchHiddenFields = $selectedTalent !== '' ? ['talent' => $selectedTalent] : [];
            require __DIR__ . '/../app/views/components/catalog-search.php';
            ?>
            <section class="preorder-grid" aria-label="Pre-order items">
                <?php foreach ($products as $product): ?>
                    <article class="preorder-card">
                        <div class="preorder-art">
                            <span class="preorder-badge"><?= store_escape($product['category_name']) ?></span>
                            <span class="preorder-symbol" aria-hidden="true"><?= htmlspecialchars($merchInitial, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="preorder-body">
                            <div class="preorder-meta"><?= store_escape($product['variant_name']) ?></div>
                            <h2><?= store_escape($product['product_name']) ?></h2>
                            <p><?= store_escape($product['description'] ?? '') ?></p>
                            <div class="preorder-footer">
                                <span class="preorder-price"><?= store_escape(store_format_price((float) $product['price'])) ?></span>
                                <?php if ((int) $product['stock_quantity'] > 0): ?>
                                    <form action="cart.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                        <input type="hidden" name="variant_id" value="<?= (int) $product['variant_id'] ?>">
                                        <button class="preorder-button" type="submit" name="action" value="add">Add to cart</button>
                                    </form>
                                <?php else: ?>
                                    <span class="preorder-button">Sold out</span>
                                <?php endif; ?>
                            </div>
                            <div class="preorder-shipping"><?= $product['expected_ship_date'] ? 'Estimated dispatch: ' . store_escape(date('F Y', strtotime($product['expected_ship_date']))) : 'Estimated dispatch date to be announced' ?></div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$products): ?>
                    <p role="status"><?= $productSearch !== '' ? 'No pre-orders match your search' : 'There are no available pre-orders' ?><?= $talentName !== '' ? ' for ' . store_escape($talentName) : '' ?> right now.</p>
                <?php endif; ?>
            </section>

            <aside class="preorder-guide">
                <div>
                    <h2>New to pre-orders?</h2>
                    <p>See how reservations, payment, and delivery work before choosing your next collectible.</p>
                </div>
                <a href="collector_showcase.php">
                    Explore the order process
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                        <path d="m12 5 7 7-7 7" />
                    </svg>
                </a>
            </aside>
        </main>
    </div>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
