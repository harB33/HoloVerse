<?php
require_once __DIR__ . '/../app/store.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$favoriteProductIds = store_collector_favorite_ids($conn, $userId);
$pageSize = 20;

// Categories
$categoryResult = $conn->query('SELECT category_id, category_name FROM category_tbl ORDER BY category_name');
$categories = $categoryResult->fetch_all(PDO::FETCH_ASSOC);

// Category filter
$categorySlugInput = $_GET['category_slug'] ?? '';
$categorySlugInput = is_string($categorySlugInput) ? strtolower(trim($categorySlugInput)) : '';
$categoryParam = $_GET['category'] ?? '';
$selectedCategory = is_string($categoryParam) ? filter_var($categoryParam, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
$selectedCategory = $selectedCategory !== false ? (int) $selectedCategory : 0;
$selectedCategorySlug = '';
$selectedCategoryName = '';

if ($categorySlugInput !== '') {
    foreach ($categories as $cat) {
        if (store_category_slug($cat['category_name']) === $categorySlugInput) {
            $selectedCategory = (int) $cat['category_id'];
            $selectedCategorySlug = $categorySlugInput;
            $selectedCategoryName = (string) $cat['category_name'];
            break;
        }
    }
} elseif ($selectedCategory > 0) {
    foreach ($categories as $cat) {
        if ((int) $cat['category_id'] === $selectedCategory) {
            $selectedCategorySlug = store_category_slug($cat['category_name']);
            $selectedCategoryName = (string) $cat['category_name'];
            break;
        }
    }
}

// Generations
$groupOrderSql = "ARRAY[" . implode(
    ', ',
    array_map(static fn (string $slug): string => "'" . str_replace("'", "''", $slug) . "'", STORE_TALENT_FILTER_GROUP_SLUGS)
) . "]::text[]";
$generationResult = $conn->query(
    "SELECT group_slug, group_name
     FROM talent_group_tbl
     WHERE group_slug IN (" . store_talent_filter_group_slugs_sql() . ")
     ORDER BY array_position({$groupOrderSql}, group_slug)"
);
$generations = $generationResult->fetch_all(PDO::FETCH_ASSOC);

$generationParam = $_GET['generation'] ?? '';
$selectedGeneration = is_string($generationParam) && in_array($generationParam, STORE_TALENT_FILTER_GROUP_SLUGS, true)
    ? $generationParam
    : '';
$selectedGenerationName = '';
if ($selectedGeneration !== '') {
    foreach ($generations as $gen) {
        if ($gen['group_slug'] === $selectedGeneration) {
            $selectedGenerationName = (string) $gen['group_name'];
            break;
        }
    }
}

// Talent filter
$talentParameter = $_GET['talent'] ?? '';
$selectedTalent = is_string($talentParameter) ? trim($talentParameter) : '';
$talentName = '';
$talentId = 0;
if ($selectedTalent !== '') {
    $talentStatement = $conn->prepare('SELECT talent_id, talent_name FROM talent_tbl WHERE talent_name = ? OR talent_slug = ? LIMIT 1');
    $talentStatement->bind_param('ss', $selectedTalent, $selectedTalent);
    $talentStatement->execute();
    $selectedTalentRow = $talentStatement->get_result()->fetch_assoc();
    $talentStatement->close();
    if ($selectedTalentRow) {
        $talentId = (int) $selectedTalentRow['talent_id'];
        $talentName = (string) $selectedTalentRow['talent_name'];
    }
}

// Search
$searchParameter = $_GET['search'] ?? '';
$filterSearch = is_string($searchParameter) ? trim($searchParameter) : '';
$generalSearchParameter = $_GET['global_search'] ?? '';
$generalProductSearch = is_string($generalSearchParameter) ? trim($generalSearchParameter) : '';
$productSearch = $filterSearch !== '' ? $filterSearch : $generalProductSearch;

// Availability: all, ready (stock > 0), saved
$validAvailabilities = ['all', 'ready', 'saved'];
$availabilityParam = $_GET['availability'] ?? 'all';
$selectedAvailability = is_string($availabilityParam) && in_array($availabilityParam, $validAvailabilities, true)
    ? $availabilityParam
    : 'all';

// Sort: featured, price-low, price-high, name
$validSorts = ['featured', 'price-low', 'price-high', 'name'];
$sortParam = $_GET['sort'] ?? 'featured';
$selectedSort = is_string($sortParam) && in_array($sortParam, $validSorts, true)
    ? $sortParam
    : 'featured';

$isAjaxSearch = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && ($_GET['ajax'] ?? '') === 'search';

// Query Construction
$availableStock = store_available_stock_sql();
$whereConditions = ["p.status = 'active'", 'p.is_preorder = 1'];
$bindTypes = '';
$bindValues = [];

if ($selectedGeneration !== '') {
    $whereConditions[] = "EXISTS (
        SELECT 1
        FROM product_talents_tbl pt_gen
        INNER JOIN talent_group_members_tbl gm ON gm.talent_id = pt_gen.talent_id
        INNER JOIN talent_group_tbl tg ON tg.group_id = gm.group_id
        WHERE pt_gen.product_id = p.product_id AND tg.group_slug = ?
    )";
    $bindTypes .= 's';
    $bindValues[] = $selectedGeneration;
}

if ($selectedCategory > 0) {
    $whereConditions[] = 'c.category_id = ?';
    $bindTypes .= 'i';
    $bindValues[] = $selectedCategory;
}

if ($talentId > 0) {
    $whereConditions[] = 'EXISTS (
        SELECT 1 FROM product_talents_tbl pt_t
        WHERE pt_t.product_id = p.product_id AND pt_t.talent_id = ?
    )';
    $bindTypes .= 'i';
    $bindValues[] = $talentId;
}

if ($selectedAvailability === 'ready') {
    $whereConditions[] = "({$availableStock}) > 0";
} elseif ($selectedAvailability === 'saved') {
    $cleanFavIds = array_values(array_filter(array_map('intval', $favoriteProductIds), static fn (int $id): bool => $id > 0));
    if ($cleanFavIds !== []) {
        $whereConditions[] = 'p.product_id IN (' . implode(',', $cleanFavIds) . ')';
    } else {
        $whereConditions[] = '1 = 0';
    }
}

if ($productSearch !== '') {
    $whereConditions[] = '(
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
    $searchPattern = '%' . $productSearch . '%';
    $bindTypes .= 'sssss';
    $bindValues[] = $searchPattern;
    $bindValues[] = $searchPattern;
    $bindValues[] = $searchPattern;
    $bindValues[] = $searchPattern;
    $bindValues[] = $searchPattern;
}

$whereSql = 'WHERE ' . implode(' AND ', $whereConditions);

$countSql = "SELECT COUNT(DISTINCT p.product_id) AS product_count
             FROM product_tbl p
             INNER JOIN category_tbl c ON c.category_id = p.category_id
             INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
             {$whereSql}";

if ($bindValues !== []) {
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param($bindTypes, ...$bindValues);
    $countStmt->execute();
    $totalProductCount = (int) $countStmt->get_result()->fetch_assoc()['product_count'];
    $countStmt->close();
} else {
    $totalProductCount = (int) $conn->query($countSql)->fetch_assoc()['product_count'];
}

$pageCount = max(1, (int) ceil($totalProductCount / $pageSize));
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$currentPage = $requestedPage === false || $requestedPage === null ? 1 : max(1, $requestedPage);
$currentPage = min($currentPage, $pageCount);
$offset = ($currentPage - 1) * $pageSize;

$outerOrder = match ($selectedSort) {
    'price-low' => '(preorder_products.stock_quantity > 0) DESC, preorder_products.price ASC, preorder_products.product_id DESC',
    'price-high' => '(preorder_products.stock_quantity > 0) DESC, preorder_products.price DESC, preorder_products.product_id DESC',
    'name' => '(preorder_products.stock_quantity > 0) DESC, LOWER(preorder_products.product_name) ASC, preorder_products.product_id DESC',
    default => '(preorder_products.stock_quantity > 0) DESC, preorder_products.product_id DESC',
};

$sql = "SELECT preorder_products.*
        FROM (
            SELECT DISTINCT ON (p.product_id)
                   p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date,
                   c.category_id, c.category_name, v.variant_id, v.variant_name, v.price, v.compare_at_price,
                   v.currency, {$availableStock} AS stock_quantity, i.image_url, i.alt_text,
                   (
                       SELECT t.talent_name
                       FROM product_talents_tbl pt_name
                       INNER JOIN talent_tbl t ON t.talent_id = pt_name.talent_id
                       WHERE pt_name.product_id = p.product_id
                       ORDER BY t.talent_id ASC
                       LIMIT 1
                   ) AS talent_name
            FROM product_tbl p
            INNER JOIN category_tbl c ON c.category_id = p.category_id
            INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
            LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
            {$whereSql}
            ORDER BY p.product_id DESC, ({$availableStock} > 0) DESC, v.variant_id ASC
        ) AS preorder_products
        ORDER BY {$outerOrder}
        LIMIT {$pageSize} OFFSET {$offset}";

if ($bindValues !== []) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($bindTypes, ...$bindValues);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $stmt->close();
} else {
    $products = $conn->query($sql)->fetch_all(PDO::FETCH_ASSOC);
}

$buildPreorderUrl = static function (array $overrides) use (
    $selectedGeneration,
    $selectedCategory,
    $selectedCategorySlug,
    $selectedTalent,
    $selectedAvailability,
    $selectedSort,
    $productSearch
): string {
    $gen = array_key_exists('generation', $overrides) ? $overrides['generation'] : $selectedGeneration;
    $catId = array_key_exists('category', $overrides) ? $overrides['category'] : $selectedCategory;
    $catSlug = array_key_exists('category_slug', $overrides) ? $overrides['category_slug'] : $selectedCategorySlug;
    $talent = array_key_exists('talent', $overrides) ? $overrides['talent'] : $selectedTalent;
    $avail = array_key_exists('availability', $overrides) ? $overrides['availability'] : $selectedAvailability;
    $sort = array_key_exists('sort', $overrides) ? $overrides['sort'] : $selectedSort;
    $search = array_key_exists('search', $overrides) ? $overrides['search'] : $productSearch;
    $page = array_key_exists('page', $overrides) ? (int) $overrides['page'] : 1;

    $params = [];
    if ($gen !== '') {
        $params['generation'] = $gen;
    }
    if ($catSlug !== '') {
        $params['category_slug'] = $catSlug;
    } elseif ($catId > 0) {
        $params['category'] = $catId;
    }
    if ($talent !== '') {
        $params['talent'] = $talent;
    }
    if ($avail !== 'all') {
        $params['availability'] = $avail;
    }
    if ($sort !== 'featured') {
        $params['sort'] = $sort;
    }
    if ($search !== '') {
        $params['search'] = $search;
    }
    if ($page > 1) {
        $params['page'] = $page;
    }

    $qs = http_build_query($params);
    return 'pre-order' . ($qs !== '' ? '?' . $qs : '');
};

$hasActiveFilters = $productSearch !== ''
    || $selectedCategory > 0
    || $selectedGeneration !== ''
    || $selectedTalent !== ''
    || $selectedAvailability !== 'all';

$availabilityLabels = [
    'ready' => 'Open for reservation',
    'saved' => 'Saved',
];

$renderPreorderResults = function () use (
    $totalProductCount,
    $currentPage,
    $pageSize,
    $pageCount,
    $hasActiveFilters,
    $selectedGeneration,
    $selectedGenerationName,
    $selectedCategory,
    $selectedCategoryName,
    $selectedTalent,
    $talentName,
    $selectedAvailability,
    $availabilityLabels,
    $productSearch,
    $buildPreorderUrl,
    $products,
    $favoriteProductIds
): void {
    ?>
    <div class="catalog-results-bar">
        <p class="results-caption" data-catalog-results-caption>
            <?php if ($totalProductCount > 0): ?>
                <?php
                $showingStart = ($currentPage - 1) * $pageSize + 1;
                $showingEnd = min($totalProductCount, $currentPage * $pageSize);
                ?>
                Showing <?= $showingStart ?>–<?= $showingEnd ?> of <?= number_format($totalProductCount) ?> <?= $totalProductCount === 1 ? 'pre-order' : 'pre-orders' ?> &middot; Made to order
            <?php else: ?>
                0 pre-orders &middot; Made to order
            <?php endif; ?>
        </p>

        <?php if ($hasActiveFilters): ?>
            <div class="active-filter-chips" aria-label="Active filters">
                <?php if ($selectedGeneration !== ''): ?>
                    <a class="filter-chip" href="<?= store_escape($buildPreorderUrl(['generation' => '', 'page' => 1])) ?>" data-remove-filter="generation" aria-label="Remove <?= store_escape($selectedGenerationName) ?> generation filter">
                        <span>Generation: <?= store_escape($selectedGenerationName) ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>

                <?php if ($selectedCategory > 0 && $selectedCategoryName !== ''): ?>
                    <a class="filter-chip" href="<?= store_escape($buildPreorderUrl(['category' => 0, 'category_slug' => '', 'page' => 1])) ?>" data-remove-filter="category" aria-label="Remove <?= store_escape($selectedCategoryName) ?> category filter">
                        <span>Category: <?= store_escape($selectedCategoryName) ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>

                <?php if ($selectedTalent !== ''): ?>
                    <a class="filter-chip" href="<?= store_escape($buildPreorderUrl(['talent' => '', 'page' => 1])) ?>" data-remove-filter="talent" aria-label="Remove <?= store_escape($talentName ?: $selectedTalent) ?> talent filter">
                        <span>Talent: <?= store_escape($talentName ?: $selectedTalent) ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>

                <?php if ($selectedAvailability !== 'all' && isset($availabilityLabels[$selectedAvailability])): ?>
                    <a class="filter-chip" href="<?= store_escape($buildPreorderUrl(['availability' => 'all', 'page' => 1])) ?>" data-remove-filter="availability" aria-label="Remove <?= store_escape($availabilityLabels[$selectedAvailability]) ?> availability filter">
                        <span><?= store_escape($availabilityLabels[$selectedAvailability]) ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>

                <?php if ($productSearch !== ''): ?>
                    <a class="filter-chip" href="<?= store_escape($buildPreorderUrl(['search' => '', 'page' => 1])) ?>" data-remove-filter="search" aria-label="Remove search query">
                        <span>&ldquo;<?= store_escape($productSearch) ?>&rdquo;</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </a>
                <?php endif; ?>

                <a class="filter-clear-all" href="pre-order" data-clear-all-filters>Clear all</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($products)): ?>
        <section class="product-grid all-products-grid" aria-label="Available pre-order products">
            <?php foreach ($products as $product): ?>
                <?php require __DIR__ . '/../app/views/components/product-card.php'; ?>
            <?php endforeach; ?>
        </section>

        <?php if ($pageCount > 1): ?>
            <?php
            $delta = 1;
            $range = [];
            $rangeWithDots = [];
            for ($i = 1; $i <= $pageCount; $i++) {
                if ($i === 1 || $i === $pageCount || ($i >= $currentPage - $delta && $i <= $currentPage + $delta)) {
                    $range[] = $i;
                }
            }
            $lastPageNum = 0;
            foreach ($range as $pageItem) {
                if ($lastPageNum > 0 && $pageItem - $lastPageNum > 1) {
                    $rangeWithDots[] = '...';
                }
                $rangeWithDots[] = $pageItem;
                $lastPageNum = $pageItem;
            }
            ?>
            <nav class="all-products-pagination" aria-label="Pre-order product pages">
                <?php if ($currentPage > 1): ?>
                    <a class="all-products-page-button" href="<?= store_escape($buildPreorderUrl(['page' => $currentPage - 1])) ?>" aria-label="Previous page">Prev</a>
                <?php else: ?>
                    <span class="all-products-page-button is-disabled" aria-hidden="true">Prev</span>
                <?php endif; ?>

                <div class="all-products-page-list">
                    <?php foreach ($rangeWithDots as $pageDot): ?>
                        <?php if ($pageDot === '...'): ?>
                            <span class="all-products-page-ellipsis" aria-hidden="true">&hellip;</span>
                        <?php elseif ((int) $pageDot === $currentPage): ?>
                            <span class="all-products-page-number is-active" aria-current="page"><?= $pageDot ?></span>
                        <?php else: ?>
                            <a class="all-products-page-number" href="<?= store_escape($buildPreorderUrl(['page' => $pageDot])) ?>"><?= $pageDot ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($currentPage < $pageCount): ?>
                    <a class="all-products-page-button" href="<?= store_escape($buildPreorderUrl(['page' => $currentPage + 1])) ?>" aria-label="Next page">Next</a>
                <?php else: ?>
                    <span class="all-products-page-button is-disabled" aria-hidden="true">Next</span>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-search" data-catalog-empty>
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <h3>No pre-orders found</h3>
            <p><?= $productSearch !== '' ? 'No pre-orders match "' . store_escape($productSearch) . '".' : 'There are no active pre-orders matching your selection.' ?> Try adjusting your filters.</p>
            <a class="button-primary dark" href="pre-order" data-reset-catalog-filters>
                Reset filters
            </a>
        </div>
    <?php endif;
};

if ($isAjaxSearch) {
    ob_start();
    echo '<div class="catalog-product-results-fragment"><span id="all-products-count">'
        . number_format($totalProductCount)
        . ' ' . ($totalProductCount === 1 ? 'pre-order' : 'pre-orders')
        . ($productSearch !== '' ? ' found' : '')
        . '</span>';
    $renderPreorderResults();
    echo '</div>';
    $resultsHtml = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'html' => $resultsHtml,
        'totalCount' => $totalProductCount,
        'countLabel' => number_format($totalProductCount) . ' ' . ($totalProductCount === 1 ? 'pre-order' : 'pre-orders'),
        'currentPage' => $currentPage,
    ], JSON_THROW_ON_ERROR);
    exit;
}

$pageTitle = 'Pre-Order | HoloVerse Official Shop';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="preorder-page all-products-page home-landing">
    <div class="page-shell">
        <?php
        $activePage = 'pre_order';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="all-products-main landing-shell preorder-main" aria-labelledby="preorder-page-title">
            <!-- 1. Editorial Hero Banner with 3-Step Strip -->
            <section class="preorder-hero-banner" aria-labelledby="preorder-page-title">
                <div class="preorder-hero-top">
                    <div class="preorder-hero-copy">
                        <p class="eyebrow">Made to order &middot; Reserve yours</p>
                        <?php if ($talentName !== ''): ?>
                            <h1 id="preorder-page-title"><?= store_escape($talentName) ?> pre-orders</h1>
                            <p>Browse official made-to-order merchandise for <?= store_escape($talentName) ?> and reserve your pieces before the order window closes.</p>
                        <?php else: ?>
                            <h1 id="preorder-page-title">Worth the wait.</h1>
                            <p>Lock in a favorite before the order window closes. Every piece is produced to your reservation with authentic character artwork.</p>
                        <?php endif; ?>
                    </div>
                    <div class="section-heading-aside">
                        <span class="all-products-count" id="all-products-count"><?= number_format($totalProductCount) ?> <?= $totalProductCount === 1 ? 'pre-order' : 'pre-orders' ?><?= $productSearch !== '' ? ' found' : '' ?></span>
                        <nav class="products-promo-links" aria-label="Shop product collections">
                            <a href="<?= store_escape($siteRoot . 'products') ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                                <span>Shop all products</span>
                            </a>
                            <a href="<?= store_escape($siteRoot . 'flash-sale') ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
                                <span>Flash drop</span>
                            </a>
                        </nav>
                    </div>
                </div>

                <div class="preorder-process" aria-label="How pre-orders work">
                    <div>
                        <span>01</span>
                        <p><strong>Reserve your merch</strong>Secure the items you want during the pre-order window.</p>
                    </div>
                    <div>
                        <span>02</span>
                        <p><strong>Made for fans</strong>Production starts after the order window completes.</p>
                    </div>
                    <div>
                        <span>03</span>
                        <p><strong>Ships to your door</strong>Track estimated shipping window in your order details.</p>
                    </div>
                </div>
            </section>

            <!-- 2. Product Toolbar (Category Tabs + Search trigger) -->
            <div class="product-toolbar preorder-product-toolbar">
                <div class="filter-tabs" role="tablist" aria-label="Pre-order categories">
                    <button
                        type="button"
                        class="<?= $selectedCategory === 0 ? 'active' : '' ?>"
                        role="tab"
                        aria-selected="<?= $selectedCategory === 0 ? 'true' : 'false' ?>"
                        data-category-tab=""
                    >
                        All Categories
                    </button>
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $catId = (int) $cat['category_id'];
                        $catSlug = store_category_slug($cat['category_name']);
                        $isTabActive = $selectedCategory === $catId || $selectedCategorySlug === $catSlug;
                        ?>
                        <button
                            type="button"
                            class="<?= $isTabActive ? 'active' : '' ?>"
                            role="tab"
                            aria-selected="<?= $isTabActive ? 'true' : 'false' ?>"
                            data-category-tab="<?= $catId ?>"
                            data-category-slug="<?= store_escape($catSlug) ?>"
                        >
                            <?= store_escape($cat['category_name']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <button class="button-text" type="button" data-search-overlay-open>
                    <span>Search merch</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
            </div>

            <!-- Availability Toolbar & Sort -->
            <div class="availability-toolbar">
                <div class="availability-filters" role="group" aria-label="Filter pre-orders by availability">
                    <span class="filter-hint">Availability</span>
                    <button type="button" class="<?= $selectedAvailability === 'all' ? 'active' : '' ?>" data-availability-filter="all" aria-pressed="<?= $selectedAvailability === 'all' ? 'true' : 'false' ?>">
                        All pre-orders
                    </button>
                    <button type="button" class="<?= $selectedAvailability === 'ready' ? 'active' : '' ?>" data-availability-filter="ready" aria-pressed="<?= $selectedAvailability === 'ready' ? 'true' : 'false' ?>">
                        Open for reservation
                    </button>
                    <button type="button" class="saved-filter<?= $selectedAvailability === 'saved' ? ' active' : '' ?>" data-availability-filter="saved" data-saved-filter aria-pressed="<?= $selectedAvailability === 'saved' ? 'true' : 'false' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                        Saved <span class="filter-count" data-saved-filter-count><?= count($favoriteProductIds) ?></span>
                    </button>
                </div>
                <label class="sort-control">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 16-4 4-4-4"/><path d="M17 20V4"/><path d="m3 8 4-4 4 4"/><path d="M7 4v16"/></svg>
                    <span>Sort</span>
                    <select data-shop-sort name="sort" aria-label="Sort pre-orders">
                        <option value="featured"<?= $selectedSort === 'featured' ? ' selected' : '' ?>>Featured</option>
                        <option value="price-low"<?= $selectedSort === 'price-low' ? ' selected' : '' ?>>Price: low to high</option>
                        <option value="price-high"<?= $selectedSort === 'price-high' ? ' selected' : '' ?>>Price: high to low</option>
                        <option value="name"<?= $selectedSort === 'name' ? ' selected' : '' ?>>Name: A to Z</option>
                    </select>
                </label>
            </div>

            <!-- 3. 2-Column Catalog Layout -->
            <div class="products-catalog-layout">
                <aside class="generation-filter" aria-labelledby="generation-filter-title">
                    <?php
                    $catalogSearchAction = 'pre-order';
                    $catalogSearchId = 'filter-panel-catalog-search';
                    $catalogSearchClass = 'filter-panel-search';
                    $catalogSearchLabel = 'Search pre-orders, categories, or talents';
                    $catalogSearchPlaceholder = 'Search pre-orders...';
                    $catalogSearchInputName = 'search';
                    $catalogSearchValue = $filterSearch;
                    $catalogSearchShowClear = false;
                    $catalogSearchHiddenFields = [];
                    if ($selectedAvailability !== 'all') {
                        $catalogSearchHiddenFields['availability'] = $selectedAvailability;
                    }
                    if ($selectedSort !== 'featured') {
                        $catalogSearchHiddenFields['sort'] = $selectedSort;
                    }
                    if ($selectedCategorySlug !== '') {
                        $catalogSearchHiddenFields['category_slug'] = $selectedCategorySlug;
                    }
                    if ($selectedGeneration !== '') {
                        $catalogSearchHiddenFields['generation'] = $selectedGeneration;
                    }
                    if ($selectedTalent !== '') {
                        $catalogSearchHiddenFields['talent'] = $selectedTalent;
                    }
                    require __DIR__ . '/../app/views/components/catalog-search.php';
                    ?>

                    <form class="category-filter" method="get" action="pre-order">
                        <label class="category-filter-label" for="catalog-category-trigger">Category</label>
                        <div class="category-filter-dropdown" data-category-dropdown>
                            <button class="category-filter-trigger" id="catalog-category-trigger" type="button" aria-expanded="false" aria-controls="catalog-category-options" data-category-trigger>
                                <span data-category-label><?= $selectedCategoryName !== '' ? store_escape($selectedCategoryName) : 'All Categories' ?></span>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                            </button>
                            <div class="category-filter-options" id="catalog-category-options" role="listbox" aria-label="Choose a category" hidden>
                                <button class="category-filter-option<?= $selectedCategory === 0 ? ' is-selected' : '' ?>" type="button" role="option" data-category-option data-category-slug="" aria-selected="<?= $selectedCategory === 0 ? 'true' : 'false' ?>">All Categories</button>
                                <?php foreach ($categories as $cat): ?>
                                    <?php
                                    $catId = (int) $cat['category_id'];
                                    $catSlug = store_category_slug($cat['category_name']);
                                    $isSel = $selectedCategory === $catId || $selectedCategorySlug === $catSlug;
                                    ?>
                                    <button class="category-filter-option<?= $isSel ? ' is-selected' : '' ?>" type="button" role="option" data-category-option data-category-id="<?= $catId ?>" data-category-slug="<?= store_escape($catSlug) ?>" aria-selected="<?= $isSel ? 'true' : 'false' ?>"><?= store_escape($cat['category_name']) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </form>

                    <h2 class="generation-filter-title" id="generation-filter-title">Generation</h2>
                    <nav class="generation-filter-list" aria-label="Product generations">
                        <a class="generation-filter-option<?= $selectedGeneration === '' ? ' is-active' : '' ?>" href="<?= store_escape($buildPreorderUrl(['generation' => '', 'page' => 1])) ?>" data-generation-slug=""<?= $selectedGeneration === '' ? ' aria-current="true"' : '' ?>>All Generations</a>
                        <?php foreach ($generations as $generation): ?>
                            <?php $isGenActive = $selectedGeneration === $generation['group_slug']; ?>
                            <a class="generation-filter-option<?= $isGenActive ? ' is-active' : '' ?>" href="<?= store_escape($buildPreorderUrl(['generation' => $generation['group_slug'], 'page' => 1])) ?>" data-generation-slug="<?= store_escape($generation['group_slug']) ?>"<?= $isGenActive ? ' aria-current="true"' : '' ?>><?= store_escape($generation['group_name']) ?></a>
                        <?php endforeach; ?>
                    </nav>
                </aside>

                <div class="products-catalog-results" aria-busy="false">
                    <div class="product-grid products-loading-skeleton" aria-hidden="true">
                        <?php for ($skeletonIndex = 0; $skeletonIndex < 6; $skeletonIndex++): ?>
                            <article class="product-card products-skeleton-card">
                                <div class="product-image-wrap">
                                    <span class="products-skeleton-block products-skeleton-artwork"></span>
                                </div>
                                <div class="product-info">
                                    <span class="products-skeleton-block products-skeleton-category"></span>
                                    <div class="products-skeleton-title">
                                        <span class="products-skeleton-block"></span>
                                        <span class="products-skeleton-block products-skeleton-title-short"></span>
                                    </div>
                                    <span class="products-skeleton-block products-skeleton-button"></span>
                                </div>
                            </article>
                        <?php endfor; ?>
                    </div>
                    <?php $renderPreorderResults(); ?>
                </div>
            </div>

            <!-- 4. Pre-Order Guide Callout -->
            <aside class="preorder-guide">
                <div>
                    <p class="eyebrow">Fulfillment &amp; dispatch guide</p>
                    <h2>How pre-orders work</h2>
                    <p>Add an available item to your bag and complete checkout to lock in your reservation. Each item lists its estimated dispatch window; mixed orders with in-stock items ship together when your pre-order arrives unless placed as separate orders.</p>
                    <p class="preorder-disclaimer">Pre-order dates and shipping estimates are subject to production schedules. Check individual product listings for the latest fulfillment timeline.</p>
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

    <!-- Client-Side Catalog AJAX & Interaction Script -->
    <script>
        const categoryFilter = document.querySelector(".category-filter");
        if (categoryFilter) {
            const categoryTrigger = categoryFilter.querySelector("[data-category-trigger]");
            const categoryOptions = categoryFilter.querySelector("#catalog-category-options");
            const setCategoryMenuOpen = (open) => {
                categoryTrigger?.setAttribute("aria-expanded", String(open));
                if (categoryOptions) categoryOptions.hidden = !open;
            };

            categoryTrigger?.addEventListener("click", () => {
                setCategoryMenuOpen(categoryTrigger.getAttribute("aria-expanded") !== "true");
            });

            categoryFilter.querySelectorAll("[data-category-option]").forEach((option) => {
                option.addEventListener("click", () => {
                    setCategoryMenuOpen(false);
                    const categorySlug = option.dataset.categorySlug || "";
                    const currentUrl = new URL(window.location.href);
                    if (categorySlug) {
                        currentUrl.searchParams.set("category_slug", categorySlug);
                        currentUrl.searchParams.delete("category");
                    } else {
                        currentUrl.searchParams.delete("category_slug");
                        currentUrl.searchParams.delete("category");
                    }
                    updateCatalogResults?.(currentUrl.href, 1, "push");
                });
            });

            document.addEventListener("click", (event) => {
                if (!categoryFilter.contains(event.target)) {
                    setCategoryMenuOpen(false);
                }
            });
        }

        const catalogResults = document.querySelector(".products-catalog-results");
        let debounceTimer;
        let activeController;
        let requestId = 0;
        let updateCatalogResults;

        const getActiveStateFromUrl = (url) => {
            const generation = url.searchParams.get("generation") || "";
            const categorySlug = url.searchParams.get("category_slug") || "";
            const category = url.searchParams.get("category") || "";
            const talent = url.searchParams.get("talent") || "";
            const availability = url.searchParams.get("availability") || "all";
            const sort = url.searchParams.get("sort") || "featured";
            const search = url.searchParams.get("search") || url.searchParams.get("global_search") || "";
            const page = Number(url.searchParams.get("page")) || 1;
            return { generation, categorySlug, category, talent, availability, sort, search, page };
        };

        const syncAllCatalogControls = (url) => {
            const { generation, categorySlug, category, availability, sort, search } = getActiveStateFromUrl(url);

            // 1. Sync Category Toolbar Tabs
            document.querySelectorAll(".product-toolbar [data-category-tab]").forEach((tab) => {
                const tabSlug = tab.dataset.categorySlug || "";
                const tabId = tab.dataset.categoryTab || "";
                const isActive = (tabSlug && tabSlug === categorySlug) || (tabId && tabId === category) || (!tabSlug && !tabId && !categorySlug && !category);
                tab.classList.toggle("active", isActive);
                tab.setAttribute("aria-selected", String(isActive));
            });

            // 2. Sync Availability Buttons
            document.querySelectorAll("[data-availability-filter]").forEach((btn) => {
                const filterVal = btn.dataset.availabilityFilter || "all";
                const isActive = filterVal === availability;
                btn.classList.toggle("active", isActive);
                btn.setAttribute("aria-pressed", String(isActive));
            });

            // 3. Sync Sort Select
            const sortSelect = document.querySelector("select[data-shop-sort]");
            if (sortSelect && sortSelect.value !== sort) {
                sortSelect.value = sort;
            }

            // 4. Sync Left Sidebar Category Dropdown
            const categoryForm = document.querySelector(".category-filter");
            if (categoryForm) {
                const selectedOption = [...categoryForm.querySelectorAll("[data-category-option]")]
                    .find((opt) => (opt.dataset.categorySlug || "") === categorySlug);
                categoryForm.querySelectorAll("[data-category-option]").forEach((opt) => {
                    const selected = opt === selectedOption || (!categorySlug && !opt.dataset.categorySlug);
                    opt.classList.toggle("is-selected", selected);
                    opt.setAttribute("aria-selected", String(selected));
                });
                const categoryLabel = categoryForm.querySelector("[data-category-label]");
                if (categoryLabel) {
                    categoryLabel.textContent = selectedOption?.textContent.trim() || "All Categories";
                }
            }

            // 5. Sync Generation Links
            document.querySelectorAll(".generation-filter-option").forEach((link) => {
                const linkGen = link.dataset.generationSlug || "";
                const linkUrl = new URL(link.href, window.location.origin);
                if (categorySlug) linkUrl.searchParams.set("category_slug", categorySlug);
                else linkUrl.searchParams.delete("category_slug");
                if (availability !== "all") linkUrl.searchParams.set("availability", availability);
                else linkUrl.searchParams.delete("availability");
                if (sort !== "featured") linkUrl.searchParams.set("sort", sort);
                else linkUrl.searchParams.delete("sort");
                if (search) linkUrl.searchParams.set("search", search);
                else linkUrl.searchParams.delete("search");
                link.href = linkUrl.href;
                const isActive = linkGen === generation;
                link.classList.toggle("is-active", isActive);
                if (isActive) {
                    link.setAttribute("aria-current", "true");
                } else {
                    link.removeAttribute("aria-current");
                }
            });

            // 6. Sync Search Input if present
            const searchInputs = document.querySelectorAll('.filter-panel-search input[name="search"]');
            searchInputs.forEach((input) => {
                if (document.activeElement !== input) {
                    input.value = search;
                }
            });
        };

        updateCatalogResults = async (targetUrl, targetPage = 1, historyMode = "push") => {
            const request = ++requestId;
            activeController?.abort();
            activeController = new AbortController();

            catalogResults?.classList.add("is-loading");
            catalogResults?.setAttribute("aria-busy", "true");

            const fetchUrl = new URL(targetUrl, window.location.origin);
            const requestedPage = targetPage || Number(fetchUrl.searchParams.get("page")) || 1;
            fetchUrl.searchParams.set("ajax", "search");
            fetchUrl.searchParams.set("page", String(requestedPage));

            try {
                const response = await fetch(fetchUrl, { credentials: "same-origin", signal: activeController.signal });
                if (!response.ok) {
                    throw new Error(`Pre-order search failed with status ${response.status}.`);
                }
                const data = await response.json();
                if (request !== requestId) {
                    return;
                }
                const resultDocument = new DOMParser().parseFromString(data.html, "text/html");
                const nextResults = resultDocument.querySelector(".catalog-product-results-fragment");
                const nextCount = resultDocument.querySelector("#all-products-count");
                if (!nextResults || !catalogResults) {
                    throw new Error("Pre-order catalog returned incomplete results.");
                }

                document.querySelector("[data-catalog-search-error]")?.remove();
                const loadingSkeleton = catalogResults.querySelector(".products-loading-skeleton");
                catalogResults.replaceChildren(
                    ...(loadingSkeleton ? [loadingSkeleton] : []),
                    ...nextResults.childNodes
                );

                const countEl = document.querySelector("#all-products-count");
                if (countEl && nextCount) {
                    countEl.textContent = nextCount.textContent;
                }

                const nextUrl = new URL(fetchUrl);
                nextUrl.searchParams.delete("ajax");
                if (data.currentPage <= 1) {
                    nextUrl.searchParams.delete("page");
                } else {
                    nextUrl.searchParams.set("page", String(data.currentPage));
                }

                syncAllCatalogControls(nextUrl);
                window.history[historyMode === "push" ? "pushState" : "replaceState"]({}, "", nextUrl);
            } catch (error) {
                if (error.name !== "AbortError" && request === requestId) {
                    console.error(error);
                    let errorNotice = document.querySelector("[data-catalog-search-error]");
                    if (!errorNotice) {
                        errorNotice = document.createElement("p");
                        errorNotice.className = "all-products-empty";
                        errorNotice.setAttribute("role", "alert");
                        errorNotice.dataset.catalogSearchError = "";
                        catalogResults?.before(errorNotice);
                    }
                    errorNotice.textContent = "Pre-order results could not be updated. Please try again.";
                }
            } finally {
                if (request === requestId) {
                    catalogResults?.setAttribute("aria-busy", "false");
                    catalogResults?.classList.remove("is-loading");
                }
            }
        };

        // Delegated clicks
        document.addEventListener("click", (event) => {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            // Category tabs
            const tabBtn = event.target instanceof Element ? event.target.closest(".product-toolbar [data-category-tab]") : null;
            if (tabBtn) {
                event.preventDefault();
                const currentUrl = new URL(window.location.href);
                const tabSlug = tabBtn.dataset.categorySlug || "";
                if (tabSlug) {
                    currentUrl.searchParams.set("category_slug", tabSlug);
                    currentUrl.searchParams.delete("category");
                } else {
                    currentUrl.searchParams.delete("category_slug");
                    currentUrl.searchParams.delete("category");
                }
                updateCatalogResults(currentUrl.href, 1, "push");
                return;
            }

            // Availability buttons
            const availBtn = event.target instanceof Element ? event.target.closest("[data-availability-filter]") : null;
            if (availBtn) {
                event.preventDefault();
                const currentUrl = new URL(window.location.href);
                const filter = availBtn.dataset.availabilityFilter || "all";
                if (filter === "all") {
                    currentUrl.searchParams.delete("availability");
                } else {
                    currentUrl.searchParams.set("availability", filter);
                }
                updateCatalogResults(currentUrl.href, 1, "push");
                return;
            }

            // Generation links
            const genLink = event.target instanceof Element ? event.target.closest(".generation-filter-option") : null;
            if (genLink) {
                event.preventDefault();
                updateCatalogResults(genLink.href, 1, "push");
                return;
            }

            // Filter removal chips & clear/reset
            const removeFilter = event.target instanceof Element ? event.target.closest("[data-remove-filter], [data-clear-all-filters], [data-reset-catalog-filters]") : null;
            if (removeFilter) {
                event.preventDefault();
                if (removeFilter.matches("[data-remove-filter='search']") || removeFilter.matches("[data-clear-all-filters]") || removeFilter.matches("[data-reset-catalog-filters]")) {
                    document.querySelectorAll('.filter-panel-search input[name="search"]').forEach(i => i.value = "");
                }
                updateCatalogResults(removeFilter.href, 1, "push");
                return;
            }

            // Pagination buttons
            const pageLink = event.target instanceof Element ? event.target.closest(".all-products-pagination a") : null;
            if (pageLink) {
                event.preventDefault();
                const linkUrl = new URL(pageLink.href, window.location.origin);
                const page = Number(linkUrl.searchParams.get("page")) || 1;
                updateCatalogResults(pageLink.href, page, "push");
                const catalogTop = document.querySelector(".products-catalog-layout");
                if (catalogTop) {
                    window.scrollTo({ top: catalogTop.offsetTop - 120, behavior: "smooth" });
                }
                return;
            }
        });

        // Sort control changes
        document.addEventListener("change", (event) => {
            const select = event.target instanceof Element ? event.target.closest("select[data-shop-sort]") : null;
            if (select) {
                const currentUrl = new URL(window.location.href);
                const sort = select.value || "featured";
                if (sort === "featured") {
                    currentUrl.searchParams.delete("sort");
                } else {
                    currentUrl.searchParams.set("sort", sort);
                }
                updateCatalogResults(currentUrl.href, 1, "push");
            }
        });

        // Search inputs
        document.addEventListener("input", (event) => {
            const searchInput = event.target instanceof Element ? event.target.closest('.filter-panel-search input[name="search"]') : null;
            if (!searchInput) return;
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => {
                const currentUrl = new URL(window.location.href);
                const term = searchInput.value.trim();
                if (term) {
                    currentUrl.searchParams.set("search", term);
                } else {
                    currentUrl.searchParams.delete("search");
                }
                updateCatalogResults(currentUrl.href, 1, "replace");
            }, 150);
        });

        document.addEventListener("submit", (event) => {
            const form = event.target instanceof Element ? event.target.closest(".filter-panel-search") : null;
            if (!form) return;
            event.preventDefault();
            window.clearTimeout(debounceTimer);
            const searchInput = form.querySelector('input[name="search"]');
            const currentUrl = new URL(window.location.href);
            const term = searchInput ? searchInput.value.trim() : "";
            if (term) {
                currentUrl.searchParams.set("search", term);
            } else {
                currentUrl.searchParams.delete("search");
            }
            updateCatalogResults(currentUrl.href, 1, "push");
        });

        // Browser history navigation
        window.addEventListener("popstate", () => {
            const currentUrl = new URL(window.location.href);
            updateCatalogResults?.(currentUrl.href, Number(currentUrl.searchParams.get("page")) || 1, "replace");
        });
    </script>

    <?php
    $cartHref = 'cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
