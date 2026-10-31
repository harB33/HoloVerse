<?php
require_once __DIR__ . '/../app/store.php';

$favoriteProductIds = store_collector_favorite_ids($conn, (int) ($_SESSION['user_id'] ?? 0));
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
                   p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date, c.category_name,
                   v.variant_id, v.variant_name, v.price, v.currency, {$availableStock} AS stock_quantity,
                   i.image_url, i.alt_text,
                   (SELECT t.talent_name
                    FROM product_talents_tbl pt
                    INNER JOIN talent_tbl t ON t.talent_id = pt.talent_id
                    WHERE pt.product_id = p.product_id
                    ORDER BY t.talent_id
                    LIMIT 1) AS talent_name
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

$pageTitle = 'Pre-Order | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell preorder-page">
        <?php
        $activePage = 'pre_order';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="all-products-main landing-shell preorder-main">
            <section class="preorder-hero-banner" aria-labelledby="preorder-page-title">
                <div class="preorder-hero-copy">
                    <p class="eyebrow">Made to order &middot; Pre-order window open</p>
                    <?php if ($talentName !== ''): ?>
                        <h1 id="preorder-page-title"><?= store_escape($talentName) ?> pre-orders</h1>
                        <p>Browse official made-to-order merchandise for <?= store_escape($talentName) ?> and reserve your pieces before the window closes.</p>
                    <?php else: ?>
                        <h1 id="preorder-page-title">Worth the wait.</h1>
                        <p>Reserve limited-run collectibles, scale figures, and anniversary sets while they are in production. Every order is produced to your reservation.</p>
                    <?php endif; ?>
                </div>
                <div class="preorder-hero-meta">
                    <span class="all-products-count"><?= number_format($productCount) ?> <?= $productCount === 1 ? 'pre-order' : 'pre-orders' ?></span>
                    <?php
                    $catalogSearchAction = 'pre_order';
                    $catalogSearchId = 'preorder-search';
                    $catalogSearchLabel = 'Search pre-order products';
                    $catalogSearchPlaceholder = 'Search pre-orders...';
                    $catalogSearchValue = $productSearch;
                    $catalogSearchHiddenFields = $selectedTalent !== '' ? ['talent' => $selectedTalent] : [];
                    require __DIR__ . '/../app/views/components/catalog-search.php';
                    ?>
                </div>
            </section>

            <?php if ($products): ?>
                <section class="product-grid all-products-grid" aria-label="Pre-order items">
                    <?php foreach ($products as $product): ?>
                        <?php require __DIR__ . '/../app/views/components/product-card.php'; ?>
                    <?php endforeach; ?>
                </section>
            <?php else: ?>
                <p class="all-products-empty" role="status"><?= $productSearch !== '' ? 'No pre-orders match your search' : 'There are no available pre-orders' ?><?= $talentName !== '' ? ' for ' . store_escape($talentName) : '' ?> right now.</p>
            <?php endif; ?>

            <?php if ($pageCount > 1): ?>
                <nav class="all-products-pagination preorder-pagination" aria-label="Pre-order product pages">
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
                    <p class="eyebrow">Fulfillment &amp; dispatch guide</p>
                    <h2>How pre-orders work</h2>
                    <p>Add an available item to your bag and complete checkout to lock in your reservation. Each item lists its estimated dispatch window; mixed carts with in-stock items ship together when your pre-order arrives unless ordered separately.</p>
                </div>
                <a class="button-primary" href="collector-showcase">
                    <span>Collector Showcase</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                        <path d="m12 5 7 7-7 7" />
                    </svg>
                </a>
            </aside>
        </main>
    </div>
    <?php
    $cartHref = 'cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
