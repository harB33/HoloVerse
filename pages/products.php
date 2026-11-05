<?php
require_once __DIR__ . '/../app/store.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$pageSize = 20;

$validAvailabilities = ['all', 'ready', 'preorder', 'flash', 'saved'];
$availabilityParam = $_GET['availability'] ?? 'all';
$selectedAvailability = is_string($availabilityParam) && in_array($availabilityParam, $validAvailabilities, true)
    ? $availabilityParam
    : 'all';
$favoriteProductIds = $selectedAvailability === 'saved'
    ? store_collector_favorite_ids($conn, $userId)
    : [];
$flashProductIds = [];
if ($selectedAvailability === 'flash' && store_flash_sale_table_exists($conn)) {
    $flashSaleTimezone = new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila');
    $flashSaleNow = new DateTimeImmutable('now', $flashSaleTimezone);
    $liveFlashSales = store_flash_sale_rows($conn, 'live', $flashSaleNow);
    foreach ($liveFlashSales as $saleRow) {
        $flashProductIds[(int) $saleRow['product_id']] = (int) $saleRow['product_id'];
    }
    $flashProductIds = array_values($flashProductIds);
}

$searchParameter = $_GET['search'] ?? '';
$filterSearch = is_string($searchParameter) ? trim($searchParameter) : '';
$generalSearchParameter = $_GET['global_search'] ?? '';
$generalProductSearch = is_string($generalSearchParameter) ? trim($generalSearchParameter) : '';
$isCatalogSearchRequest = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && ($_GET['ajax'] ?? '') === 'search';

$validSorts = ['featured', 'price-low', 'price-high', 'name'];
$sortParam = $_GET['sort'] ?? 'featured';
$selectedSort = is_string($sortParam) && in_array($sortParam, $validSorts, true)
    ? $sortParam
    : 'featured';

$generationParameter = $_GET['generation'] ?? '';
$routeSegment = $_GET['route_segment'] ?? '';
$routeSegment = is_string($routeSegment) ? rawurldecode($routeSegment) : '';
$selectedGeneration = is_string($generationParameter)
    && in_array($generationParameter, STORE_TALENT_FILTER_GROUP_SLUGS, true)
        ? $generationParameter
        : '';
$selectedTalentSlug = '';
$selectedTalentId = 0;
$selectedTalentName = '';
$isTalentCatalogRoute = false;
if ($routeSegment !== '') {
    $talentStatement = $conn->prepare('SELECT talent_id, talent_slug, talent_name FROM talent_tbl WHERE talent_slug = ? LIMIT 1');
    $talentStatement->bind_param('s', $routeSegment);
    $talentStatement->execute();
    $talentRow = $talentStatement->get_result()->fetch_assoc();
    $talentStatement->close();
    if ($talentRow) {
        $selectedTalentSlug = (string) $talentRow['talent_slug'];
        $selectedTalentId = (int) $talentRow['talent_id'];
        $selectedTalentName = (string) $talentRow['talent_name'];
        $routeSegment = '';
        $isTalentCatalogRoute = true;
    }
}
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '';
$routeSegments = array_filter(explode('/', trim($requestPath, '/')), static fn (string $segment): bool => $segment !== '');
$siteRoot = str_repeat('../', max(1, count($routeSegments) - 1));

$categoryResult = $conn->query('SELECT category_id, category_name FROM category_tbl ORDER BY category_name');
$categories = $categoryResult->fetch_all(PDO::FETCH_ASSOC);
$categoryIds = array_map(static fn (array $category): int => (int) $category['category_id'], $categories);

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

if (
    $generalProductSearch === ''
    && $routeSegment !== ''
    && $selectedGeneration === ''
    && !array_key_exists('global_search', $_GET)
) {
    $generalProductSearch = $routeSegment;
}
$filterSearch = $filterSearch !== '' ? $filterSearch : $generalProductSearch;
$categorySlugInput = $_GET['category_slug'] ?? '';
$categorySlugInput = is_string($categorySlugInput) ? strtolower(trim($categorySlugInput)) : '';
$productSearch = $filterSearch !== '' ? $filterSearch : $generalProductSearch;
$activeSearchQuery = $filterSearch !== ''
    ? ['search' => $filterSearch]
    : ($generalProductSearch !== '' ? ['global_search' => $generalProductSearch] : []);
$categoryParameter = $_GET['category'] ?? '';
$selectedCategory = is_string($categoryParameter)
    ? filter_var($categoryParameter, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
if ($categorySlugInput !== '') {
    $selectedCategory = 0;
    foreach ($categories as $category) {
        if (store_category_slug($category['category_name']) === $categorySlugInput) {
            $selectedCategory = (int) $category['category_id'];
            break;
        }
    }
} elseif ($isCatalogSearchRequest) {
    $selectedCategory = $selectedCategory !== false && $selectedCategory > 0 ? $selectedCategory : 0;
} else {
    $selectedCategory = $selectedCategory !== false && in_array($selectedCategory, $categoryIds, true)
        ? $selectedCategory
        : 0;
}
$selectedCategorySlug = '';
if ($selectedCategory > 0) {
    foreach ($categories as $category) {
        if ((int) $category['category_id'] === $selectedCategory) {
            $selectedCategorySlug = store_category_slug($category['category_name']);
            break;
        }
    }
}
$catalogBasePath = $selectedTalentSlug !== ''
    ? '/products/' . rawurlencode($selectedTalentSlug)
    : ($selectedGeneration !== ''
        ? '/products/generation/' . rawurlencode($selectedGeneration)
        : '/products');
$catalogPath = $selectedTalentSlug !== ''
    ? $catalogBasePath
    : ($selectedCategorySlug !== ''
        ? $catalogBasePath . '/category/' . rawurlencode($selectedCategorySlug)
        : $catalogBasePath);

$hasQueryFilters = array_key_exists('generation', $_GET)
    || array_key_exists('category', $_GET)
    || array_key_exists('category_slug', $_GET);

if (
    !$isCatalogSearchRequest
    && $routeSegment !== ''
    && $filterSearch === $routeSegment
    && (array_key_exists('search', $_GET) || array_key_exists('page', $_GET))
) {
    $catalogQuery = $_GET;
    unset($catalogQuery['search'], $catalogQuery['route_segment']);
    if (($catalogQuery['page'] ?? '') === '1') {
        unset($catalogQuery['page']);
    }
    $redirectUrl = $requestPath;
    if ($catalogQuery) {
        $redirectUrl .= '?' . http_build_query($catalogQuery);
    }
    header('Location: ' . $redirectUrl, true, 308);
    exit;
}
if (
    !$isCatalogSearchRequest
    && $hasQueryFilters
    && !$isTalentCatalogRoute
    && $requestPath !== $catalogPath
) {
    $catalogQuery = $_GET;
    unset($catalogQuery['generation'], $catalogQuery['category'], $catalogQuery['category_slug'], $catalogQuery['route_segment']);
    $redirectUrl = $catalogPath;
    if ($catalogQuery) {
        $redirectUrl .= '?' . http_build_query($catalogQuery);
    }
    header('Location: ' . $redirectUrl, true, 308);
    exit;
}


if ($isCatalogSearchRequest) {
    $searchPageSize = $pageSize;
    $searchRequestedPage = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
    $searchProducts = store_catalog_product_rows(
        $conn,
        $searchPageSize,
        ($searchRequestedPage - 1) * $searchPageSize,
        $productSearch,
        $selectedGeneration,
        $selectedCategory,
        randomize: false,
        includeImageGallery: false,
        relatedProductId: 0,
        availability: $selectedAvailability,
        sort: $selectedSort,
        flashProductIds: $flashProductIds,
        favoriteProductIds: $favoriteProductIds,
        talentId: $selectedTalentId,
        includeTotalCount: true
    );
    $searchTotalCount = isset($searchProducts[0]['total_product_count'])
        ? (int) $searchProducts[0]['total_product_count']
        : 0;
    if ($searchProducts === [] && $searchRequestedPage > 1) {
        $searchTotalCount = store_catalog_product_count(
            $conn,
            $productSearch,
            $selectedGeneration,
            $selectedCategory,
            $selectedAvailability,
            $flashProductIds,
            $favoriteProductIds,
            $selectedTalentId
        );
    }
    $searchPageCount = max(1, (int) ceil($searchTotalCount / $searchPageSize));
    $searchCurrentPage = min($searchRequestedPage, $searchPageCount);
    if ($searchCurrentPage !== $searchRequestedPage) {
        $searchProducts = store_catalog_product_rows(
            $conn,
            $searchPageSize,
            ($searchCurrentPage - 1) * $searchPageSize,
            $productSearch,
            $selectedGeneration,
            $selectedCategory,
            randomize: false,
            includeImageGallery: false,
            relatedProductId: 0,
            availability: $selectedAvailability,
            sort: $selectedSort,
            flashProductIds: $flashProductIds,
            favoriteProductIds: $favoriteProductIds,
            talentId: $selectedTalentId,
            includeTotalCount: true
        );
    }
    foreach ($searchProducts as &$searchProduct) {
        unset($searchProduct['total_product_count']);
    }
    unset($searchProduct);
    ob_start();
    $products = $searchProducts;
    $totalProductCount = $searchTotalCount;
    $pageCount = $searchPageCount;
    $currentPage = $searchCurrentPage;
    $catalogPaginationBasePath = $catalogPath;
    echo '<div class="catalog-product-results-fragment">';
    require __DIR__ . '/../app/views/components/catalog-product-results.php';
    echo '</div>';
    $resultsHtml = ob_get_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'html' => $resultsHtml,
        'totalCount' => $searchTotalCount,
        'currentPage' => $searchCurrentPage,
    ], JSON_THROW_ON_ERROR);
    exit;
}

$currentPage = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$products = store_catalog_product_rows(
    $conn,
    $pageSize,
    ($currentPage - 1) * $pageSize,
    $productSearch,
    $selectedGeneration,
    $selectedCategory,
    randomize: false,
    includeImageGallery: false,
    relatedProductId: 0,
    availability: $selectedAvailability,
    sort: $selectedSort,
    flashProductIds: $flashProductIds,
    favoriteProductIds: $favoriteProductIds,
    talentId: $selectedTalentId,
    includeTotalCount: true
);
$totalProductCount = isset($products[0]['total_product_count'])
    ? (int) $products[0]['total_product_count']
    : 0;
if ($products === [] && $currentPage > 1) {
    $totalProductCount = store_catalog_product_count(
        $conn,
        $productSearch,
        $selectedGeneration,
        $selectedCategory,
        $selectedAvailability,
        $flashProductIds,
        $favoriteProductIds,
        $selectedTalentId
    );
}
$pageCount = max(1, (int) ceil($totalProductCount / $pageSize));
$normalizedPage = min($currentPage, $pageCount);
if ($normalizedPage !== $currentPage) {
    $currentPage = $normalizedPage;
    $products = store_catalog_product_rows(
        $conn,
        $pageSize,
        ($currentPage - 1) * $pageSize,
        $productSearch,
        $selectedGeneration,
        $selectedCategory,
        randomize: false,
        includeImageGallery: false,
        relatedProductId: 0,
        availability: $selectedAvailability,
        sort: $selectedSort,
        flashProductIds: $flashProductIds,
        favoriteProductIds: $favoriteProductIds,
        talentId: $selectedTalentId,
        includeTotalCount: true
    );
}
foreach ($products as &$product) {
    unset($product['total_product_count']);
}
unset($product);
$catalogPaginationBasePath = $catalogPath;
$pageTitle = 'All Products | HoloVerse';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="all-products-page home-landing">
    <script>document.body.classList.add("products-page-loading");</script>
    <div class="page-shell">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = 'products';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="all-products-main landing-shell" aria-labelledby="all-products-title">
            <section class="section-heading all-products-heading">
                <div>
                    <p class="eyebrow">Official hololive production merchandise</p>
                    <h1 id="all-products-title">Shop all products</h1>
                    <p>Browse the full collection of official character apparel, plushies, scale figures, and collectibles.</p>
                </div>
            </section>

            <div class="availability-toolbar">
                <div class="availability-toolbar-content">
                <div class="availability-filters" role="group" aria-label="Filter by availability">
                    <span class="filter-hint">Availability</span>
                    <button type="button" class="<?= $selectedAvailability === 'all' ? 'active' : '' ?>" data-availability-filter="all" aria-pressed="<?= $selectedAvailability === 'all' ? 'true' : 'false' ?>">
                        Everything
                    </button>
                    <button type="button" class="<?= $selectedAvailability === 'ready' ? 'active' : '' ?>" data-availability-filter="ready" aria-pressed="<?= $selectedAvailability === 'ready' ? 'true' : 'false' ?>">
                        In stock
                    </button>
                    <button type="button" class="<?= $selectedAvailability === 'preorder' ? 'active' : '' ?>" data-availability-filter="preorder" aria-pressed="<?= $selectedAvailability === 'preorder' ? 'true' : 'false' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                        Pre-order
                    </button>
                    <a href="<?= store_escape($siteRoot . 'flash-sale') ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
                        Flash sale
                    </a>
                    <button type="button" class="saved-filter<?= $selectedAvailability === 'saved' ? ' active' : '' ?>" data-availability-filter="saved" data-saved-filter aria-pressed="<?= $selectedAvailability === 'saved' ? 'true' : 'false' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                        Saved
                    </button>
                </div>
                <div class="availability-active-filters" data-active-filter-chips role="group" aria-label="Active filters" hidden></div>
                </div>
            </div>

            <div class="products-catalog-layout">
                <aside class="generation-filter" aria-labelledby="generation-filter-title">
                    <?php
                    $catalogSearchAction = $catalogPath;
                    $catalogSearchId = 'filter-panel-catalog-search';
                    $catalogSearchClass = 'filter-panel-search';
                    $catalogSearchLabel = 'Search products, categories, or talents';
                    $catalogSearchPlaceholder = 'Search products...';
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
                    if ($selectedGeneration !== '' && $selectedTalentSlug !== '') {
                        $catalogSearchHiddenFields['generation'] = $selectedGeneration;
                    }
                    if ($selectedCategorySlug !== '' && $selectedTalentSlug !== '') {
                        $catalogSearchHiddenFields['category_slug'] = $selectedCategorySlug;
                    }
                    require __DIR__ . '/../app/views/components/catalog-search.php';
                    ?>
                    <form class="category-filter" method="get" action="<?= store_escape($catalogBasePath) ?>" data-catalog-base-path="<?= store_escape($catalogBasePath) ?>">
                        <label class="category-filter-label" for="catalog-category-trigger">Category</label>
                        <?php foreach ($activeSearchQuery as $name => $value): ?>
                            <input type="hidden" name="<?= store_escape($name) ?>" value="<?= store_escape((string) $value) ?>">
                        <?php endforeach; ?>
                        <?php if ($selectedAvailability !== 'all'): ?>
                            <input type="hidden" name="availability" value="<?= store_escape($selectedAvailability) ?>">
                        <?php endif; ?>
                        <?php if ($selectedSort !== 'featured'): ?>
                            <input type="hidden" name="sort" value="<?= store_escape($selectedSort) ?>">
                        <?php endif; ?>
                        <?php if ($selectedGeneration !== '' && $selectedTalentSlug !== ''): ?>
                            <input type="hidden" name="generation" value="<?= store_escape($selectedGeneration) ?>">
                        <?php endif; ?>
                        <input id="catalog-category" type="hidden" name="category" value="<?= $selectedCategory > 0 ? $selectedCategory : '' ?>">
                        <div class="category-filter-dropdown" data-category-dropdown>
                            <button class="category-filter-trigger" id="catalog-category-trigger" type="button" aria-expanded="false" aria-controls="catalog-category-options" data-category-trigger>
                                <span data-category-label><?= $selectedCategory > 0
                                    ? store_escape((string) array_column($categories, 'category_name', 'category_id')[$selectedCategory])
                                    : 'All Categories' ?></span>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                            </button>
                            <div class="category-filter-options" id="catalog-category-options" role="listbox" aria-label="Choose a category" hidden>
                                <button class="category-filter-option<?= $selectedCategory === 0 ? ' is-selected' : '' ?>" type="button" role="option" data-category-option data-category-value="" aria-selected="<?= $selectedCategory === 0 ? 'true' : 'false' ?>">All Categories</button>
                                <?php foreach ($categories as $category): ?>
                                    <?php $categoryId = (int) $category['category_id']; ?>
                                    <button class="category-filter-option<?= $selectedCategory === $categoryId ? ' is-selected' : '' ?>" type="button" role="option" data-category-option data-category-value="<?= $categoryId ?>" data-category-slug="<?= store_escape(store_category_slug($category['category_name'])) ?>" aria-selected="<?= $selectedCategory === $categoryId ? 'true' : 'false' ?>"><?= store_escape($category['category_name']) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </form>
                    <div class="category-filter sort-filter">
                        <label class="category-filter-label" for="catalog-sort-trigger">Sort by</label>
                        <div class="category-filter-dropdown" data-sort-dropdown>
                            <button class="category-filter-trigger" id="catalog-sort-trigger" type="button" aria-expanded="false" aria-controls="catalog-sort-options" data-sort-trigger>
                                <span data-sort-label><?= match ($selectedSort) {
                                    'price-low' => 'Price: low to high',
                                    'price-high' => 'Price: high to low',
                                    'name' => 'Name: A to Z',
                                    default => 'Featured',
                                } ?></span>
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 9 6 6 6-6" /></svg>
                            </button>
                            <div class="category-filter-options" id="catalog-sort-options" role="listbox" aria-label="Sort products" hidden>
                                <button class="category-filter-option<?= $selectedSort === 'featured' ? ' is-selected' : '' ?>" type="button" role="option" data-sort-option="featured" aria-selected="<?= $selectedSort === 'featured' ? 'true' : 'false' ?>">Featured</button>
                                <button class="category-filter-option<?= $selectedSort === 'price-low' ? ' is-selected' : '' ?>" type="button" role="option" data-sort-option="price-low" aria-selected="<?= $selectedSort === 'price-low' ? 'true' : 'false' ?>">Price: low to high</button>
                                <button class="category-filter-option<?= $selectedSort === 'price-high' ? ' is-selected' : '' ?>" type="button" role="option" data-sort-option="price-high" aria-selected="<?= $selectedSort === 'price-high' ? 'true' : 'false' ?>">Price: high to low</button>
                                <button class="category-filter-option<?= $selectedSort === 'name' ? ' is-selected' : '' ?>" type="button" role="option" data-sort-option="name" aria-selected="<?= $selectedSort === 'name' ? 'true' : 'false' ?>">Name: A to Z</button>
                            </div>
                            <select data-shop-sort name="sort" aria-label="Sort products" hidden>
                                <option value="featured"<?= $selectedSort === 'featured' ? ' selected' : '' ?>>Featured</option>
                                <option value="price-low"<?= $selectedSort === 'price-low' ? ' selected' : '' ?>>Price: low to high</option>
                                <option value="price-high"<?= $selectedSort === 'price-high' ? ' selected' : '' ?>>Price: high to low</option>
                                <option value="name"<?= $selectedSort === 'name' ? ' selected' : '' ?>>Name: A to Z</option>
                            </select>
                        </div>
                    </div>
                    <h2 class="generation-filter-title" id="generation-filter-title">Generation</h2>
                    <nav class="generation-filter-list" aria-label="Product generations">
                        <?php
                        $genParams = [];
                        if ($selectedAvailability !== 'all') { $genParams['availability'] = $selectedAvailability; }
                        if ($selectedSort !== 'featured') { $genParams['sort'] = $selectedSort; }
                        if ($productSearch !== '') { $genParams['search'] = $productSearch; }
                        $genQuery = $genParams ? '?' . http_build_query($genParams) : '';

                        if ($selectedTalentSlug !== '') {
                            $allGenerationParams = $genParams;
                            if ($selectedCategorySlug !== '') {
                                $allGenerationParams['category_slug'] = $selectedCategorySlug;
                            }
                            $allGenerationsHref = $catalogBasePath
                                . ($allGenerationParams ? '?' . http_build_query($allGenerationParams) : '');
                        } else {
                            $allGenerationsHref = '/products'
                                . ($selectedCategorySlug !== '' ? '/category/' . rawurlencode($selectedCategorySlug) : '')
                                . $genQuery;
                        }
                        ?>
                        <a class="generation-filter-option<?= $selectedGeneration === '' ? ' is-active' : '' ?>" href="<?= store_escape($allGenerationsHref) ?>" data-generation-slug=""<?= $selectedGeneration === '' ? ' aria-current="true"' : '' ?>>All Generations</a>
                        <?php foreach ($generations as $generation): ?>
                            <?php
                            if ($selectedTalentSlug !== '') {
                                $talentParams = $genParams;
                                $talentParams['generation'] = $generation['group_slug'];
                                if ($selectedCategorySlug !== '') {
                                    $talentParams['category_slug'] = $selectedCategorySlug;
                                }
                                $generationHref = $catalogBasePath . '?' . http_build_query($talentParams);
                            } else {
                                $generationHref = '/products/generation/' . rawurlencode($generation['group_slug'])
                                    . ($selectedCategorySlug !== '' ? '/category/' . rawurlencode($selectedCategorySlug) : '')
                                    . $genQuery;
                            }
                            ?>
                            <a class="generation-filter-option<?= $selectedGeneration === $generation['group_slug'] ? ' is-active' : '' ?>" href="<?= store_escape($generationHref) ?>" data-generation-slug="<?= store_escape($generation['group_slug']) ?>"<?= $selectedGeneration === $generation['group_slug'] ? ' aria-current="true"' : '' ?>><?= store_escape($generation['group_name']) ?></a>
                        <?php endforeach; ?>
                    </nav>
                </aside>

                <div class="products-catalog-results" aria-busy="false">
                    <script>document.currentScript.parentElement.setAttribute("aria-busy", "true");</script>
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
                    <?php require __DIR__ . '/../app/views/components/catalog-product-results.php'; ?>
                </div>
            </div>
        </main>
    </div>

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
                    const generation = currentUrl.pathname.match(/\/generation\/([^/]+)/)?.[1] || "";
                    const talentPath = currentUrl.pathname.match(/^\/products\/([^/]+)\/?$/)?.[0] || "";
                    const newPath = talentPath
                        ? talentPath
                        : `/products${generation ? `/generation/${encodeURIComponent(generation)}` : ""}${categorySlug ? `/category/${encodeURIComponent(categorySlug)}` : ""}`;
                    const newUrl = new URL(newPath, window.location.origin);
                    const avail = currentUrl.searchParams.get("availability");
                    const sort = currentUrl.searchParams.get("sort");
                    const search = currentUrl.searchParams.get("search") || currentUrl.searchParams.get("global_search");
                    const activeGeneration = currentUrl.searchParams.get("generation");
                    if (talentPath && activeGeneration) newUrl.searchParams.set("generation", activeGeneration);
                    if (talentPath && categorySlug) newUrl.searchParams.set("category_slug", categorySlug);
                    if (avail && avail !== "all") newUrl.searchParams.set("availability", avail);
                    if (sort && sort !== "featured") newUrl.searchParams.set("sort", sort);
                    if (search) newUrl.searchParams.set("search", search);
                    updateCatalogResults?.(newUrl.href, 1, "push");
                });
            });

            document.addEventListener("click", (event) => {
                if (!categoryFilter.contains(event.target)) {
                    setCategoryMenuOpen(false);
                }
            });
        }

        const sortFilter = document.querySelector(".sort-filter");
        if (sortFilter) {
            const sortTrigger = sortFilter.querySelector("[data-sort-trigger]");
            const sortOptions = sortFilter.querySelector("#catalog-sort-options");
            const sortSelect = sortFilter.querySelector("select[data-shop-sort]");
            const setSortMenuOpen = (open) => {
                sortTrigger?.setAttribute("aria-expanded", String(open));
                if (sortOptions) sortOptions.hidden = !open;
            };

            sortTrigger?.addEventListener("click", () => {
                setSortMenuOpen(sortTrigger.getAttribute("aria-expanded") !== "true");
            });

            sortFilter.querySelectorAll("[data-sort-option]").forEach((option) => {
                option.addEventListener("click", () => {
                    if (!sortSelect) {
                        return;
                    }
                    sortSelect.value = option.dataset.sortOption || "featured";
                    setSortMenuOpen(false);
                    sortSelect.dispatchEvent(new Event("change", { bubbles: true }));
                });
            });

            document.addEventListener("click", (event) => {
                if (event.target instanceof Node && !sortFilter.contains(event.target)) {
                    setSortMenuOpen(false);
                }
            });

            sortTrigger?.addEventListener("keydown", (event) => {
                if (event.key === "Escape") {
                    setSortMenuOpen(false);
                }
            });
        }

        const catalogResults = document.querySelector(".products-catalog-results");
        const activeFilterChipSlot = document.querySelector("[data-active-filter-chips]");
        const syncActiveFilterChips = (root = catalogResults) => {
            if (!activeFilterChipSlot || !root) {
                return;
            }

            const chips = root.querySelectorAll(".filter-chip, .filter-clear-all");
            activeFilterChipSlot.replaceChildren();
            activeFilterChipSlot.hidden = chips.length === 0;
            activeFilterChipSlot.append(...chips);
        };
        syncActiveFilterChips();

        const catalogRevealReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches
            || document.documentElement.dataset.reduceMotion === "true";
        const catalogRevealTargets = [
            ".all-products-heading",
            ".availability-toolbar",
            ".products-catalog-layout > .generation-filter",
            ".products-catalog-results > .product-grid:not(.products-loading-skeleton) > .catalog-product-card",
            ".products-catalog-results > .all-products-pagination",
            ".products-catalog-results > .empty-search",
        ].join(", ");
        let catalogRevealObserver = null;
        const observeCatalogRevealTargets = (root = document) => {
            if (!catalogRevealObserver) {
                return;
            }

            let cardIndex = 0;
            root.querySelectorAll(catalogRevealTargets).forEach((target) => {
                if (target.matches(".catalog-product-card")) {
                    target.style.setProperty("--products-reveal-delay", `${(cardIndex++ % 4) * 70}ms`);
                }
                catalogRevealObserver.observe(target);
            });
        };

        if (!catalogRevealReducedMotion && "IntersectionObserver" in window) {
            catalogRevealObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-visible");
                    } else {
                        entry.target.classList.remove("is-visible");
                    }
                });
            }, { threshold: 0.12, rootMargin: "0px 0px 64px 0px" });

            document.body.classList.add("products-reveal-ready");
            observeCatalogRevealTargets();
        }

        const stopCatalogLoading = () => {
            catalogResults?.classList.remove("is-loading");
            catalogResults?.setAttribute("aria-busy", "false");
            document.body.classList.remove("products-page-loading");
        };

        window.addEventListener("load", () => {
            window.setTimeout(stopCatalogLoading, 300);
        }, { once: true });
        window.addEventListener("pageshow", (event) => {
            if (event.persisted) {
                stopCatalogLoading();
            }
        });

        let debounceTimer;
        let activeController;
        let requestId = 0;
        let updateCatalogResults;

        const getActiveStateFromUrl = (url) => {
            const talentRoute = url.pathname.match(/^\/products\/([^/]+)\/?$/);
            const generation = url.searchParams.get("generation")
                || url.pathname.match(/\/generation\/([^/]+)/)?.[1]
                || "";
            const categorySlug = url.searchParams.get("category_slug")
                || url.pathname.match(/\/category\/([^/]+)/)?.[1]
                || "";
            const availability = url.searchParams.get("availability") || "all";
            const sort = url.searchParams.get("sort") || "featured";
            const search = url.searchParams.get("search") || url.searchParams.get("global_search") || "";
            const page = Number(url.searchParams.get("page")) || 1;
            return { generation, categorySlug, availability, sort, search, talentRoute, page };
        };

        const syncAllCatalogControls = (url) => {
            const { generation, categorySlug, availability, sort, search, talentRoute } = getActiveStateFromUrl(url);

            // 1. Sync Availability Buttons
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
            const sortFilter = document.querySelector(".sort-filter");
            const sortOption = [...(sortFilter?.querySelectorAll("[data-sort-option]") || [])]
                .find((option) => option.dataset.sortOption === sort);
            if (sortOption) {
                sortFilter.querySelectorAll("[data-sort-option]").forEach((option) => {
                    const isSelected = option === sortOption;
                    option.classList.toggle("is-selected", isSelected);
                    option.setAttribute("aria-selected", String(isSelected));
                });
                const sortLabel = sortFilter.querySelector("[data-sort-label]");
                if (sortLabel) {
                    sortLabel.textContent = sortOption.textContent.trim();
                }
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
                const categoryInput = categoryForm.querySelector("#catalog-category");
                if (categoryInput) {
                    categoryInput.value = selectedOption?.dataset.categoryValue || "";
                }
                const categoryLabel = categoryForm.querySelector("[data-category-label]");
                if (categoryLabel) {
                    categoryLabel.textContent = selectedOption?.textContent.trim() || "All Categories";
                }
            }

            // 5. Sync Generation Links & preserve active query params
            document.querySelectorAll(".generation-filter-option").forEach((link) => {
                const linkGen = link.dataset.generationSlug || "";
                const linkPath = talentRoute
                    ? talentRoute[0]
                    : `/products${linkGen ? `/generation/${encodeURIComponent(linkGen)}` : ""}${categorySlug ? `/category/${encodeURIComponent(categorySlug)}` : ""}`;
                const linkUrl = new URL(linkPath, window.location.origin);
                if (talentRoute && linkGen) { linkUrl.searchParams.set("generation", linkGen); }
                if (talentRoute && categorySlug) { linkUrl.searchParams.set("category_slug", categorySlug); }
                if (availability !== "all") { linkUrl.searchParams.set("availability", availability); }
                if (sort !== "featured") { linkUrl.searchParams.set("sort", sort); }
                if (search) { linkUrl.searchParams.set("search", search); }
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
                    throw new Error(`Product search failed with status ${response.status}.`);
                }
                const data = await response.json();
                if (request !== requestId) {
                    return;
                }
                const resultDocument = new DOMParser().parseFromString(data.html, "text/html");
                const nextResults = resultDocument.querySelector(".catalog-product-results-fragment");
                if (!nextResults || !catalogResults) {
                    throw new Error("Product catalog returned incomplete results.");
                }

                document.querySelector("[data-catalog-search-error]")?.remove();
                catalogResults.querySelectorAll(catalogRevealTargets).forEach((target) => {
                    catalogRevealObserver?.unobserve(target);
                });
                const loadingSkeleton = catalogResults.querySelector(".products-loading-skeleton");
                catalogResults.replaceChildren(
                    ...(loadingSkeleton ? [loadingSkeleton] : []),
                    ...nextResults.childNodes
                );
                syncActiveFilterChips(catalogResults);
                observeCatalogRevealTargets(catalogResults);

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
                    errorNotice.textContent = "Product results could not be updated. Please try again.";
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
