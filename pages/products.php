<?php
require_once __DIR__ . '/../app/store.php';

$pageSize = 48;
$searchParameter = $_GET['search'] ?? '';
$productSearch = is_string($searchParameter) ? trim($searchParameter) : '';
$generationParameter = $_GET['generation'] ?? '';
$selectedGeneration = is_string($generationParameter)
    && in_array($generationParameter, STORE_TALENT_FILTER_GROUP_SLUGS, true)
        ? $generationParameter
        : '';
$categoryResult = $conn->query('SELECT category_id, category_name FROM category_tbl ORDER BY category_name');
$categories = $categoryResult->fetch_all(PDO::FETCH_ASSOC);
$categoryIds = array_map(static fn (array $category): int => (int) $category['category_id'], $categories);
$categoryParameter = $_GET['category'] ?? '';
$selectedCategory = is_string($categoryParameter)
    ? filter_var($categoryParameter, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
$selectedCategory = $selectedCategory !== false && in_array($selectedCategory, $categoryIds, true)
    ? $selectedCategory
    : 0;
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
$totalProductCount = store_catalog_product_count($conn, $productSearch, $selectedGeneration, $selectedCategory);
$pageCount = max(1, (int) ceil($totalProductCount / $pageSize));
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$currentPage = min(max(1, $currentPage), $pageCount);
$products = store_catalog_product_rows($conn, $pageSize, ($currentPage - 1) * $pageSize, $productSearch, $selectedGeneration, $selectedCategory);
$catalogSearchHiddenFields = array_filter([
    'generation' => $selectedGeneration,
    'category' => $selectedCategory,
], static fn (string|int $value): bool => $value !== '' && $value !== 0);
$catalogFilterQuery = array_filter([
    'search' => $productSearch,
    'category' => $selectedCategory,
], static fn (string|int $value): bool => $value !== '' && $value !== 0);
$pageTitle = 'All Products | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = 'products';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="all-products-main" aria-labelledby="all-products-title">
            <section class="all-products-intro">
                <span class="drops-eyebrow">Official hololive production merchandise</span>
                <h1 id="all-products-title">Shop all products</h1>
                <p>Browse the full collection of available talent merchandise.</p>
                <span class="all-products-count"><?= number_format($totalProductCount) ?> products<?= $productSearch !== '' ? ' found' : '' ?></span>
                <nav class="products-promo-links" aria-label="Shop product collections">
                    <a href="flash_sale.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap preview-icon" aria-hidden="true"><path d="M15.914 4a1.5 1.5 0 00-2.474-1.561l-9 9A1.5 1.5 0 005.5 14h4.002a.5.5 0 01.471.666L8.086 20a1.5 1.5 0 002.475 1.56l9-9A1.5 1.5 0 0018.5 10h-3.997a.5.5 0 01-.472-.667z"/></svg>Flash Sale</a>
                    <a href="pre_order.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-arrow-up preview-icon" aria-hidden="true"><path d="m14 17 4-4 4 4"/><path d="M16 2v3"/><path d="M18 21v-8"/><path d="M21 10.343V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h9"/><path d="M3 9h18"/><path d="M8 2v3"/></svg>Pre-order</a>
                </nav>
            </section>

            <div class="products-catalog-layout">
                <aside class="generation-filter" aria-labelledby="generation-filter-title">
                    <?php
                    $catalogSearchAction = 'products.php';
                    $catalogSearchId = 'filter-panel-catalog-search';
                    $catalogSearchClass = 'filter-panel-search';
                    $catalogSearchLabel = 'Search products, categories, or talents';
                    $catalogSearchPlaceholder = 'Search products...';
                    $catalogSearchValue = $productSearch;
                    $catalogSearchShowClear = false;
                    require __DIR__ . '/../app/views/components/catalog-search.php';
                    ?>
                    <form class="category-filter" method="get" action="products.php">
                        <label for="catalog-category">Filter by category</label>
                        <?php if ($productSearch !== ''): ?>
                            <input type="hidden" name="search" value="<?= store_escape($productSearch) ?>">
                        <?php endif; ?>
                        <?php if ($selectedGeneration !== ''): ?>
                            <input type="hidden" name="generation" value="<?= store_escape($selectedGeneration) ?>">
                        <?php endif; ?>
                        <select id="catalog-category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['category_id'] ?>"<?= $selectedCategory === (int) $category['category_id'] ? ' selected' : '' ?>><?= store_escape($category['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <h2 id="generation-filter-title">Filter by generation</h2>
                    <nav aria-label="Product generations">
                        <a class="generation-filter-option<?= $selectedGeneration === '' ? ' is-active' : '' ?>" href="products.php<?= $catalogFilterQuery ? '?' . store_escape(http_build_query($catalogFilterQuery)) : '' ?>"<?= $selectedGeneration === '' ? ' aria-current="true"' : '' ?>>All Generations</a>
                        <?php foreach ($generations as $generation): ?>
                            <?php
                            $generationHref = 'products.php?' . http_build_query($catalogFilterQuery + [
                                'generation' => $generation['group_slug'],
                            ]);
                            ?>
                            <a class="generation-filter-option<?= $selectedGeneration === $generation['group_slug'] ? ' is-active' : '' ?>" href="<?= store_escape($generationHref) ?>"<?= $selectedGeneration === $generation['group_slug'] ? ' aria-current="true"' : '' ?>><?= store_escape($generation['group_name']) ?></a>
                        <?php endforeach; ?>
                    </nav>
                </aside>

                <div class="products-catalog-results" aria-busy="false">
                    <div class="drops-grid all-products-grid products-loading-skeleton" aria-hidden="true">
                        <?php for ($skeletonIndex = 0; $skeletonIndex < 8; $skeletonIndex++): ?>
                            <article class="drop-card products-skeleton-card">
                                <div class="drop-art products-skeleton-block"></div>
                                <div class="drop-details">
                                    <span class="products-skeleton-block products-skeleton-category"></span>
                                    <span class="products-skeleton-block products-skeleton-title"></span>
                                    <span class="products-skeleton-block products-skeleton-description"></span>
                                    <span class="products-skeleton-block products-skeleton-description products-skeleton-description-short"></span>
                                    <hr class="drop-allocation-divider">
                                    <span class="products-skeleton-block products-skeleton-price"></span>
                                    <span class="products-skeleton-block products-skeleton-button"></span>
                                </div>
                            </article>
                        <?php endfor; ?>
                    </div>
            <?php if ($products): ?>
                <section class="drops-grid all-products-grid" aria-label="All available products">
                    <?php foreach ($products as $product): ?>
                        <?php $productInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product['product_name']), 0, 1)); ?>
                        <article class="drop-card">
                            <a class="drop-card-link" href="product.php?id=<?= (int) $product['product_id'] ?>" aria-label="View <?= store_escape($product['product_name']) ?>"></a>
                            <div class="drop-art">
                                <div class="drop-art-labels<?= (int) $product['stock_quantity'] > 0 ? ' drop-art-labels-no-status' : '' ?>">
                                    <?php if ((int) $product['stock_quantity'] <= 0): ?>
                                        <span class="drop-status">Sold out</span>
                                    <?php endif; ?>
                                    <span class="drop-edition"><?= store_escape($product['variant_name']) ?></span>
                                </div>
                                <?php if (!empty($product['image_url'])): ?>
                                    <img class="drop-product-image-backdrop" src="<?= store_escape(store_asset_url($product['image_url'], '../')) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                    <img class="drop-product-image is-loading" src="<?= store_escape(store_asset_url($product['image_url'], '../')) ?>" alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>" loading="lazy" decoding="async">
                                <?php endif; ?>
                                <div class="drop-character<?= !empty($product['image_url']) ? ' hidden' : '' ?>"><?= store_escape($productInitial) ?></div>
                            </div>
                            <div class="drop-details">
                                <span class="drop-category"><?= store_escape($product['category_name']) ?></span>
                                <h2><?= store_escape($product['product_name']) ?></h2>
                                <p class="drop-description"><?= store_escape($product['description'] ?? '') ?></p>
                                <hr class="drop-allocation-divider">
                                <div class="drop-purchase">
                                    <div>
                                        <span class="drop-price-label"><?= (int) $product['is_preorder'] === 1 ? 'Pre-order price' : 'Price' ?></span>
                                        <div class="drop-price-row">
                                            <strong><?= store_escape(store_format_price((float) $product['price'])) ?></strong>
                                            <div class="drop-allocation"><strong><?= (int) $product['stock_quantity'] ?></strong><span>Left</span></div>
                                        </div>
                                    </div>
                                    <?php if ((int) $product['stock_quantity'] > 0): ?>
                                        <form action="cart.php" method="post">
                                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                            <input type="hidden" name="variant_id" value="<?= (int) $product['variant_id'] ?>">
                                            <button type="submit" name="action" value="add">Add to cart</button>
                                        </form>
                                    <?php else: ?>
                                        <span>Sold out</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
                <?php if ($pageCount > 1): ?>
                    <nav class="all-products-pagination" aria-label="Product pages">
                        <?php if ($currentPage > 1): ?>
                            <a href="?<?= store_escape(http_build_query(['page' => $currentPage - 1, 'search' => $productSearch, 'generation' => $selectedGeneration, 'category' => $selectedCategory ?: ''])) ?>" aria-label="Previous page">Previous</a>
                        <?php endif; ?>
                        <span>Page <?= $currentPage ?> of <?= $pageCount ?></span>
                        <?php if ($currentPage < $pageCount): ?>
                            <a href="?<?= store_escape(http_build_query(['page' => $currentPage + 1, 'search' => $productSearch, 'generation' => $selectedGeneration, 'category' => $selectedCategory ?: ''])) ?>" aria-label="Next page">Next</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <p class="all-products-empty" role="status"><?= $productSearch !== '' ? 'No products match your selected filters and search.' : (($selectedGeneration !== '' || $selectedCategory !== 0) ? 'There are no products available for the selected filters.' : 'There are no products available right now.') ?></p>
            <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        const categoryFilter = document.querySelector(".category-filter");
        categoryFilter?.querySelector("select")?.addEventListener("change", () => {
            categoryFilter.requestSubmit();
        });

        const catalogResults = document.querySelector(".products-catalog-results");
        const startCatalogLoading = () => {
            catalogResults?.classList.add("is-loading");
            catalogResults?.setAttribute("aria-busy", "true");
        };

        const stopCatalogLoading = () => {
            catalogResults?.classList.remove("is-loading");
            catalogResults?.setAttribute("aria-busy", "false");
        };

        window.addEventListener("beforeunload", startCatalogLoading);
        window.addEventListener("pageshow", stopCatalogLoading);

        document.querySelectorAll(".catalog-search, .category-filter").forEach((form) => {
            form.addEventListener("submit", startCatalogLoading);
        });

        document.querySelectorAll(".generation-filter-option, .all-products-pagination a, .catalog-search a").forEach((link) => {
            link.addEventListener("click", (event) => {
                if (
                    event.defaultPrevented ||
                    event.button !== 0 ||
                    event.metaKey ||
                    event.ctrlKey ||
                    event.shiftKey ||
                    event.altKey ||
                    (link.target && link.target !== "_self")
                ) {
                    return;
                }

                startCatalogLoading();
            });
        });

        document.querySelectorAll(".drop-product-image").forEach((image) => {
            const showLoadedImage = () => image.classList.remove("is-loading");
            const showInitialFallback = () => {
                showLoadedImage();
                image.classList.add("hidden");
                image.nextElementSibling?.classList.remove("hidden");
            };
            image.addEventListener("load", showLoadedImage, { once: true });
            image.addEventListener("error", showInitialFallback, { once: true });
            if (image.complete) {
                if (image.naturalWidth === 0) {
                    showInitialFallback();
                } else {
                    showLoadedImage();
                }
            }
        });
    </script>

    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
