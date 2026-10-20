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
$fromSql = "FROM product_tbl p
            INNER JOIN category_tbl c ON c.category_id = p.category_id
            INNER JOIN product_variants_tbl v ON v.product_id = p.product_id";
$whereSql = "WHERE p.status = 'active' AND p.is_preorder = 1";
if ($productSearch !== '') {
    $whereSql .= ' AND (
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
    $whereSql .= ' AND EXISTS (
        SELECT 1 FROM product_talents_tbl pt
        WHERE pt.product_id = p.product_id AND pt.talent_id = ?
    )';
}
$searchPattern = '%' . $productSearch . '%';
$bindFilters = static function ($statement) use ($productSearch, $selectedTalent, $searchPattern, $talentId): void {
    if ($productSearch !== '' && $selectedTalent !== '') {
        $statement->bind_param('sssssi', $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $talentId);
    } elseif ($productSearch !== '') {
        $statement->bind_param('sssss', $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    } elseif ($selectedTalent !== '') {
        $statement->bind_param('i', $talentId);
    }
};
$countSql = "SELECT COUNT(DISTINCT p.product_id) AS product_count {$fromSql} {$whereSql}";
if ($productSearch !== '' || $selectedTalent !== '') {
    $countStatement = $conn->prepare($countSql);
    $bindFilters($countStatement);
    $countStatement->execute();
    $productCount = (int) $countStatement->get_result()->fetch_assoc()['product_count'];
    $countStatement->close();
} else {
    $productCount = (int) $conn->query($countSql)->fetch_assoc()['product_count'];
}

$pageSize = 20;
$pageCount = max(1, (int) ceil($productCount / $pageSize));
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$currentPage = $requestedPage === false || $requestedPage === null ? 1 : max(1, $requestedPage);
$currentPage = min($currentPage, $pageCount);
$offset = ($currentPage - 1) * $pageSize;
$sql = "SELECT preorder_products.*
        FROM (
            SELECT DISTINCT ON (p.product_id)
                   p.product_id, p.product_name, p.description, p.expected_ship_date, c.category_name,
                   v.variant_id, v.variant_name, v.price, v.currency, {$availableStock} AS stock_quantity,
                   i.image_url, i.alt_text
            {$fromSql}
            LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
            {$whereSql}
            ORDER BY p.product_id, ({$availableStock} > 0) DESC, v.variant_id
        ) AS preorder_products
        ORDER BY (preorder_products.stock_quantity > 0) DESC, preorder_products.product_id DESC
        LIMIT {$pageSize} OFFSET {$offset}";
if ($productSearch !== '' || $selectedTalent !== '') {
    $statement = $conn->prepare($sql);
    $bindFilters($statement);
    $statement->execute();
    $products = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();
} else {
    $products = $conn->query($sql)->fetch_all(PDO::FETCH_ASSOC);
}

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
            <section class="drops-grid all-products-grid preorder-grid" aria-label="Pre-order items">
                <?php foreach ($products as $product): ?>
                    <?php $productInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product['product_name']), 0, 1)); ?>
                    <article class="drop-card">
                        <a class="drop-card-link" href="product.php?id=<?= (int) $product['product_id'] ?>" aria-label="View <?= store_escape($product['product_name']) ?>"></a>
                        <div class="drop-art">
                            <div class="drop-art-labels">
                                <span class="drop-status"><?= (int) $product['stock_quantity'] > 0 ? 'Pre-order' : 'Sold out' ?></span>
                                <span class="drop-edition"><?= store_escape($product['variant_name']) ?></span>
                            </div>
                            <?php if (!empty($product['image_url'])): ?>
                                <img class="drop-product-image-backdrop" src="<?= store_escape(store_asset_url($product['image_url'], '../')) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                <img class="drop-product-image" src="<?= store_escape(store_asset_url($product['image_url'], '../')) ?>" alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>" loading="lazy" decoding="async">
                            <?php endif; ?>
                            <div class="drop-character<?= !empty($product['image_url']) ? ' hidden' : '' ?>"><?= store_escape($productInitial) ?></div>
                        </div>
                        <div class="drop-details">
                            <span class="drop-category"><?= store_escape($product['category_name']) ?></span>
                            <h2><?= store_escape($product['product_name']) ?></h2>
                            <p class="drop-description"><?= store_escape($product['description'] ?? '') ?></p>
                            <p class="drop-preorder-shipping"><?= !empty($product['expected_ship_date']) ? 'Estimated dispatch: ' . store_escape(date('F Y', strtotime($product['expected_ship_date']))) : 'Estimated dispatch date to be announced' ?></p>
                            <hr class="drop-allocation-divider">
                            <div class="drop-purchase">
                                <div>
                                    <span class="drop-price-label">Pre-order price</span>
                                    <div class="drop-price-row">
                                        <strong><?= store_escape(store_format_price((float) $product['price'])) ?></strong>
                                        <div class="drop-allocation"><strong><?= (int) $product['stock_quantity'] ?></strong><span>Available</span></div>
                                    </div>
                                </div>
                                <?php if ((int) $product['stock_quantity'] > 0): ?>
                                    <form action="cart.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                        <input type="hidden" name="variant_id" value="<?= (int) $product['variant_id'] ?>">
                                        <button type="submit" name="action" value="add">Add pre-order</button>
                                    </form>
                                <?php else: ?>
                                    <span>Sold out</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$products): ?>
                    <p role="status"><?= $productSearch !== '' ? 'No pre-orders match your search' : 'There are no available pre-orders' ?><?= $talentName !== '' ? ' for ' . store_escape($talentName) : '' ?> right now.</p>
                <?php endif; ?>
            </section>
            <?php if ($pageCount > 1): ?>
                <nav class="preorder-pagination" aria-label="Pre-order product pages">
                    <?php
                    $paginationQuery = array_filter([
                        'search' => $productSearch,
                        'talent' => $selectedTalent,
                    ], static fn (string $value): bool => $value !== '');
                    ?>
                    <?php if ($currentPage > 1): ?>
                        <a href="?<?= store_escape(http_build_query($paginationQuery + ['page' => $currentPage - 1])) ?>" aria-label="Previous pre-order page">Previous</a>
                    <?php endif; ?>
                    <span>Page <?= $currentPage ?> of <?= $pageCount ?></span>
                    <?php if ($currentPage < $pageCount): ?>
                        <a href="?<?= store_escape(http_build_query($paginationQuery + ['page' => $currentPage + 1])) ?>" aria-label="Next pre-order page">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>

            <aside class="preorder-guide">
                <div>
                    <h2>How pre-orders work</h2>
                    <p>Add an available item to your cart and complete checkout to place your pre-order. The estimated dispatch date is shown on each item; dates are estimates and may change.</p>
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
    <script>
        document.querySelectorAll(".drop-product-image").forEach((image) => {
            const showInitialFallback = () => {
                image.classList.add("hidden");
                image.nextElementSibling?.classList.remove("hidden");
            };
            image.addEventListener("error", showInitialFallback, { once: true });
            if (image.complete && image.naturalWidth === 0) {
                showInitialFallback();
            }
        });
    </script>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
