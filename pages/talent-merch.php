<?php
require_once __DIR__ . '/../app/store.php';

$favoriteProductIds = store_collector_favorite_ids($conn, (int) ($_SESSION['user_id'] ?? 0));
$talentParameter = $_GET['talent_slug'] ?? $_GET['talent'] ?? '';
$selectedTalent = is_string($talentParameter) ? trim($talentParameter) : '';
$searchParameter = $_GET['search'] ?? '';
$productSearch = is_string($searchParameter) ? trim($searchParameter) : '';
$pageSizeOptions = [12, 24, 48];
$pageSizeParameter = $_GET['per_page'] ?? '';
$validatedPageSize = is_string($pageSizeParameter)
    ? filter_var($pageSizeParameter, FILTER_VALIDATE_INT)
    : false;
$pageSize = $validatedPageSize !== false && in_array($validatedPageSize, $pageSizeOptions, true)
    ? $validatedPageSize
    : 12;
$pageSizeQuery = $pageSize !== 12 ? ['per_page' => $pageSize] : [];
$pageParameter = $_GET['page'] ?? '';
$currentPage = is_string($pageParameter)
    ? filter_var($pageParameter, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
$currentPage = $currentPage === false ? 1 : $currentPage;
$categoryParameter = $_GET['category'] ?? '';
$selectedCategory = is_string($categoryParameter)
    ? filter_var($categoryParameter, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
$selectedCategory = $selectedCategory === false ? 0 : (int) $selectedCategory;
$categorySlugInput = $_GET['category_slug'] ?? '';
$categorySlugInput = is_string($categorySlugInput) ? strtolower(trim($categorySlugInput)) : '';
$talent = null;

if ($selectedTalent !== '') {
    $statement = $conn->prepare('SELECT talent_id, talent_name, talent_slug FROM talent_tbl WHERE talent_name = ? OR talent_slug = ? LIMIT 1');
    $statement->bind_param('ss', $selectedTalent, $selectedTalent);
    $statement->execute();
    $talent = $statement->get_result()->fetch_assoc();
    $statement->close();
}

if (!$talent) {
    http_response_code(404);
}

$products = [];
$totalProductCount = 0;
$pageCount = 1;
$categories = [];
$talentMerchPath = '';
$talentCollectionPath = '';
if ($talent) {
    $talentId = (int) $talent['talent_id'];
    $categoryStatement = $conn->prepare(
        'SELECT category_id, category_name FROM category_tbl ORDER BY category_name'
    );
    $categoryStatement->execute();
    $categories = $categoryStatement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $categoryStatement->close();

    $categoryIds = array_map(static fn (array $category): int => (int) $category['category_id'], $categories);
    if ($categorySlugInput !== '') {
        $selectedCategory = 0;
        foreach ($categories as $category) {
            if (store_category_slug($category['category_name']) === $categorySlugInput) {
                $selectedCategory = (int) $category['category_id'];
                break;
            }
        }
    } elseif (!in_array($selectedCategory, $categoryIds, true)) {
        $selectedCategory = 0;
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
    $talentMerchPath = '/talent-merch/' . rawurlencode($talent['talent_slug']);
    $talentCollectionPath = $talentMerchPath . ($selectedCategorySlug !== '' ? '/' . rawurlencode($selectedCategorySlug) : '');
    $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    $requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '';
    if (
        $requestPath !== $talentCollectionPath
        || array_key_exists('talent', $_GET)
        || array_key_exists('category', $_GET)
        || (($_GET['per_page'] ?? null) === '12')
    ) {
        $canonicalQuery = $_GET;
        unset($canonicalQuery['talent'], $canonicalQuery['talent_slug'], $canonicalQuery['category'], $canonicalQuery['category_slug']);
        if (($_GET['per_page'] ?? null) === '12') {
            unset($canonicalQuery['per_page']);
        }
        $redirectUrl = $talentCollectionPath;
        if ($canonicalQuery) {
            $redirectUrl .= '?' . http_build_query($canonicalQuery);
        }
        header('Location: ' . $redirectUrl, true, 308);
        exit;
    }
    $selectedTalent = $talent['talent_slug'];
    $availableStock = store_available_stock_sql();
    $searchPattern = '%' . $productSearch . '%';
    $countStatement = $conn->prepare(
        "SELECT COUNT(DISTINCT p.product_id) AS product_count
         FROM product_tbl p
         INNER JOIN category_tbl c ON c.category_id = p.category_id
         INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
         WHERE p.status = 'active'
           AND (
               p.product_name ILIKE ?
               OR p.description ILIKE ?
               OR c.category_name ILIKE ?
               OR v.variant_name ILIKE ?
           )
           AND EXISTS (
               SELECT 1
               FROM product_talents_tbl pt
               WHERE pt.product_id = p.product_id AND pt.talent_id = ?
           )
           AND (? = 0 OR p.category_id = ?)"
    );
    $countStatement->bind_param(
        'ssssiii',
        $searchPattern,
        $searchPattern,
        $searchPattern,
        $searchPattern,
        $talentId,
        $selectedCategory,
        $selectedCategory
    );
    $countStatement->execute();
    $totalProductCount = (int) $countStatement->get_result()->fetch_assoc()['product_count'];
    $countStatement->close();
    $pageCount = max(1, (int) ceil($totalProductCount / $pageSize));
    $currentPage = min($currentPage, $pageCount);
    $offset = ($currentPage - 1) * $pageSize;
    $statement = $conn->prepare(
        "SELECT collection_products.*
         FROM (
             SELECT DISTINCT ON (p.product_id)
                    p.product_id, p.product_name, p.description, p.is_preorder,
                    c.category_name, v.variant_id, v.variant_name, v.price, v.currency,
                    {$availableStock} AS stock_quantity, i.image_url, i.alt_text
             FROM product_tbl p
             INNER JOIN category_tbl c ON c.category_id = p.category_id
             INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
             LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
             WHERE p.status = 'active'
               AND (
                   p.product_name ILIKE ?
                   OR p.description ILIKE ?
                   OR c.category_name ILIKE ?
                   OR v.variant_name ILIKE ?
               )
               AND EXISTS (
                   SELECT 1
                   FROM product_talents_tbl pt
                   WHERE pt.product_id = p.product_id AND pt.talent_id = ?
               )
               AND (? = 0 OR p.category_id = ?)
             ORDER BY p.product_id DESC, {$availableStock} DESC, v.variant_id ASC
         ) AS collection_products
         ORDER BY (collection_products.stock_quantity > 0) DESC, collection_products.product_id DESC
         LIMIT ? OFFSET ?"
    );
    $statement->bind_param(
        'ssssiiiii',
        $searchPattern,
        $searchPattern,
        $searchPattern,
        $searchPattern,
        $talentId,
        $selectedCategory,
        $selectedCategory,
        $pageSize,
        $offset
    );
    $statement->execute();
    $products = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();
}

$pageTitle = $talent ? $talent['talent_name'] . ' Merchandise | HoloVerse' : 'Talent Not Found | HoloVerse';
$siteRoot = '/';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = '';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="all-products-main" aria-labelledby="talent-merch-title">
            <section class="all-products-intro">
                <span class="drops-eyebrow">Talent collection</span>
                <?php if ($talent): ?>
                    <h1 id="talent-merch-title"><?= store_escape($talent['talent_name']) ?> merchandise</h1>
                    <p>Browse all available merchandise associated with <?= store_escape($talent['talent_name']) ?>.</p>
                <?php else: ?>
                    <h1 id="talent-merch-title">Talent not found</h1>
                    <p>This talent collection could not be found. Return to the directory to choose a talent.</p>
                <?php endif; ?>
                <?php if ($talent): ?>
                    <?php if ($products): ?>
                        <span class="all-products-count"><?= number_format($totalProductCount) ?> products<?= $productSearch !== '' ? ' found' : '' ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <?php if ($talent): ?>
                <div class="products-catalog-layout talent-merch-layout">
                    <aside class="generation-filter talent-merch-filter" aria-labelledby="talent-merch-search-title">
                        <h2 id="talent-merch-search-title">Search this collection</h2>
                        <?php
                        $catalogSearchAction = $talentCollectionPath;
                        $catalogSearchId = 'talent-merch-search';
                        $catalogSearchClass = 'talent-merch-filter-search';
                        $catalogSearchLabel = 'Search this talent collection';
                        $catalogSearchPlaceholder = 'Search this collection';
                        $catalogSearchValue = $productSearch;
                        $catalogSearchHiddenFields = $pageSizeQuery;
                        $catalogSearchShowClear = false;
                        require __DIR__ . '/../app/views/components/catalog-search.php';
                        ?>
                        <h2 class="talent-merch-category-title" id="talent-merch-category-title">Filter by category</h2>
                        <nav aria-labelledby="talent-merch-category-title">
                            <?php
                            $categoryFilterQuery = array_filter([
                                'search' => $productSearch,
                            ], static fn (string|int $value): bool => $value !== '' && $value !== 0);
                            $categoryFilterQuery += $pageSizeQuery;
                            ?>
                            <a class="generation-filter-option<?= $selectedCategory === 0 ? ' is-active' : '' ?>" href="<?= store_escape($talentMerchPath . ($categoryFilterQuery ? '?' . http_build_query($categoryFilterQuery) : '')) ?>"<?= $selectedCategory === 0 ? ' aria-current="true"' : '' ?>>All Categories</a>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                $categoryId = (int) $category['category_id'];
                                $categoryHref = $talentMerchPath . '/' . rawurlencode(store_category_slug($category['category_name']));
                                if ($categoryFilterQuery) {
                                    $categoryHref .= '?' . http_build_query($categoryFilterQuery);
                                }
                                ?>
                                <a class="generation-filter-option<?= $selectedCategory === $categoryId ? ' is-active' : '' ?>" href="<?= store_escape($categoryHref) ?>"<?= $selectedCategory === $categoryId ? ' aria-current="true"' : '' ?>><?= store_escape($category['category_name']) ?></a>
                            <?php endforeach; ?>
                        </nav>
                    </aside>
                    <div class="products-catalog-results">
                        <?php if ($totalProductCount > 0): ?>
                            <form class="product-page-size-form" method="get" action="<?= store_escape($talentCollectionPath) ?>">
                                <?php if ($productSearch !== ''): ?>
                                    <input type="hidden" name="search" value="<?= store_escape($productSearch) ?>">
                                <?php endif; ?>
                                <label for="talent-product-limit">Products per page</label>
                                <select id="talent-product-limit" name="per_page">
                                    <?php foreach ($pageSizeOptions as $option): ?>
                                        <option value="<?= $option ?>"<?= $pageSize === $option ? ' selected' : '' ?>><?= $option ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php endif; ?>
                        <?php if ($products): ?>
                            <section class="drops-grid all-products-grid" aria-label="<?= store_escape($talent['talent_name']) ?> merchandise">
                                <?php foreach ($products as $product): ?>
                                    <?php $productInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product['product_name']), 0, 1)); ?>
                                    <article class="drop-card">
                                        <a class="drop-card-link" href="<?= store_escape(store_product_url($product['product_name'])) ?>" aria-label="View <?= store_escape($product['product_name']) ?>"></a>
                                        <div class="drop-art">
                                            <?php require __DIR__ . '/../app/views/components/collector-favorite-button.php'; ?>
                                            <div class="drop-art-labels<?= (int) $product['stock_quantity'] > 0 ? ' drop-art-labels-no-status' : '' ?>">
                                                <?php if ((int) $product['stock_quantity'] <= 0): ?>
                                                    <span class="drop-status">Sold out</span>
                                                <?php endif; ?>
                                                <span class="drop-edition"><?= store_escape($product['variant_name']) ?></span>
                                            </div>
                                            <?php if (!empty($product['image_url'])): ?>
                                                <img class="drop-product-image-backdrop" src="<?= store_escape(store_asset_url($product['image_url'], '/')) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                                <img class="drop-product-image" src="<?= store_escape(store_asset_url($product['image_url'], '/')) ?>" alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>" loading="lazy" decoding="async">
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
                                                    <form action="cart" method="post">
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
                                <?php
                                $paginationQuery = array_filter([
                                    'search' => $productSearch,
                                ], static fn (string|int $value): bool => $value !== '' && $value !== 0);
                                $paginationQuery += $pageSizeQuery;
                                ?>
                                <nav class="all-products-pagination" aria-label="Talent product pages">
                                    <?php if ($currentPage > 1): ?>
                                        <a href="<?= store_escape($talentCollectionPath . '?' . http_build_query($paginationQuery + ['page' => $currentPage - 1])) ?>" aria-label="Previous page">Previous</a>
                                    <?php endif; ?>
                                    <span>Page <?= $currentPage ?> of <?= $pageCount ?></span>
                                    <?php if ($currentPage < $pageCount): ?>
                                        <a href="<?= store_escape($talentCollectionPath . '?' . http_build_query($paginationQuery + ['page' => $currentPage + 1])) ?>" aria-label="Next page">Next</a>
                                    <?php endif; ?>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="all-products-empty" role="status"><?= $productSearch !== '' || $selectedCategory > 0 ? 'No merchandise matches the selected filters for ' . store_escape($talent['talent_name']) . '.' : 'There is no available merchandise for ' . store_escape($talent['talent_name']) . ' right now.' ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <a class="talent-directory-back-link" href="/talents">Back to the talent directory</a>
        </main>
    </div>

    <script>
        const initializeProductImages = (root = document) => {
            root.querySelectorAll(".drop-product-image:not([data-image-initialized])").forEach((image) => {
                image.dataset.imageInitialized = "true";
                const showInitialFallback = () => {
                    image.classList.add("hidden");
                    image.nextElementSibling?.classList.remove("hidden");
                };
                image.addEventListener("error", showInitialFallback, { once: true });
                if (image.complete && image.naturalWidth === 0) {
                    showInitialFallback();
                }
            });
        };

        initializeProductImages();

        let activeCatalogRequest;
        let catalogRequestId = 0;
        const updateTalentCatalog = async (url, historyMode = "push") => {
            const requestId = ++catalogRequestId;
            activeCatalogRequest?.abort();
            activeCatalogRequest = new AbortController();
            const currentLayout = document.querySelector(".talent-merch-layout");
            currentLayout?.setAttribute("aria-busy", "true");

            try {
                const response = await fetch(url, {
                    signal: activeCatalogRequest.signal,
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                if (!response.ok) {
                    throw new Error(`Collection update failed with status ${response.status}.`);
                }
                const responseDocument = new DOMParser().parseFromString(await response.text(), "text/html");
                const nextLayout = responseDocument.querySelector(".talent-merch-layout");
                const nextCount = responseDocument.querySelector(".all-products-intro .all-products-count");
                const currentCount = document.querySelector(".all-products-intro .all-products-count");
                const intro = document.querySelector(".all-products-intro");
                if (!nextLayout || !currentLayout || !intro) {
                    throw new Error("The collection response was incomplete.");
                }
                if (requestId !== catalogRequestId) {
                    return;
                }

                currentLayout.replaceWith(nextLayout);
                if (nextCount) {
                    if (currentCount) {
                        currentCount.replaceWith(nextCount);
                    } else {
                        intro.insertBefore(nextCount, intro.querySelector(".products-promo-links"));
                    }
                } else {
                    currentCount?.remove();
                }

                const responseUrl = new URL(response.url);
                window.history[historyMode === "push" ? "pushState" : "replaceState"]({}, "", responseUrl);
                initializeProductImages(nextLayout);
                document.querySelector("[data-talent-catalog-error]")?.remove();
            } catch (error) {
                if (error.name === "AbortError" || requestId !== catalogRequestId) {
                    return;
                }
                console.error("Unable to update the talent collection.", error);
                let errorNotice = document.querySelector("[data-talent-catalog-error]");
                if (!errorNotice) {
                    errorNotice = document.createElement("p");
                    errorNotice.className = "all-products-empty";
                    errorNotice.setAttribute("role", "alert");
                    errorNotice.dataset.talentCatalogError = "";
                    document.querySelector(".talent-merch-layout")?.before(errorNotice);
                }
                errorNotice.textContent = "This collection could not be updated. Please try again.";
            } finally {
                if (requestId === catalogRequestId) {
                    document.querySelector(".talent-merch-layout")?.removeAttribute("aria-busy");
                }
            }
        };

        const formUrl = (form) => {
            const url = new URL(form.action, window.location.href);
            url.search = "";
            for (const [name, value] of new FormData(form)) {
                url.searchParams.set(name, value);
            }
            return url;
        };

        document.addEventListener("submit", (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)
                || !form.matches(".talent-merch-filter-search, .product-page-size-form")) {
                return;
            }
            event.preventDefault();
            updateTalentCatalog(formUrl(form));
        });

        document.addEventListener("click", (event) => {
            const link = event.target instanceof Element
                ? event.target.closest(".talent-merch-filter a.generation-filter-option, .talent-merch-layout .all-products-pagination a")
                : null;
            if (!link || event.defaultPrevented || event.button !== 0
                || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
                || (link.target && link.target !== "_self")) {
                return;
            }
            event.preventDefault();
            updateTalentCatalog(link.href);
        });

        window.addEventListener("popstate", () => {
            updateTalentCatalog(window.location.href, "replace");
        });

        document.querySelector("#talent-product-limit")?.addEventListener("change", (event) => {
            event.currentTarget.form.requestSubmit();
        });
    </script>
    <?php
    $cartHref = '/cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
