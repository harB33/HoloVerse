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
                    p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date,
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
    foreach ($products as &$productRow) {
        $productRow['talent_name'] = $talent['talent_name'];
    }
    unset($productRow);
}

$pageTitle = $talent ? $talent['talent_name'] . ' Merchandise | HoloVerse' : 'Talent Not Found | HoloVerse';
$siteRoot = '/';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="talents-page all-products-page home-landing">
    <div class="page-shell">
        <?php
        $activePage = 'talents';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="all-products-main landing-shell" aria-labelledby="talent-merch-title">
            <section class="all-products-intro">
                <div class="all-products-intro-top">
                    <div>
                        <p class="eyebrow">Talent collection</p>
                        <?php if ($talent): ?>
                            <h1 id="talent-merch-title"><?= store_escape($talent['talent_name']) ?> merchandise</h1>
                            <p>Browse all official character goods, apparel, and collectibles associated with <?= store_escape($talent['talent_name']) ?>.</p>
                        <?php else: ?>
                            <h1 id="talent-merch-title">Talent not found</h1>
                            <p>This talent collection could not be found. Return to the directory to choose a talent.</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($talent): ?>
                        <div class="all-products-intro-actions">
                            <?php if ($products): ?>
                                <span class="all-products-count"><?= number_format($totalProductCount) ?> products<?= $productSearch !== '' ? ' found' : '' ?></span>
                            <?php endif; ?>
                            <a class="button-text" href="/talents">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                All talents
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
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
                            <section class="product-grid all-products-grid" aria-label="<?= store_escape($talent['talent_name']) ?> merchandise">
                                <?php foreach ($products as $product): ?>
                                    <?php require __DIR__ . '/../app/views/components/product-card.php'; ?>
                                <?php endforeach; ?>
                            </section>
                            <?php if ($pageCount > 1): ?>
                                <?php
                                $paginationQuery = array_filter([
                                    'search' => $productSearch,
                                ], static fn (string|int $value): bool => $value !== '' && $value !== 0);
                                $paginationQuery += $pageSizeQuery;
                                $buildTalentMerchPageUrl = static function (int $pageNum) use ($talentCollectionPath, $paginationQuery): string {
                                    $query = $paginationQuery;
                                    if ($pageNum > 1) {
                                        $query['page'] = $pageNum;
                                    }
                                    return $talentCollectionPath . ($query ? '?' . http_build_query($query) : '');
                                };
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
                                <nav class="all-products-pagination" aria-label="Talent product pages">
                                    <?php if ($currentPage > 1): ?>
                                        <a class="all-products-page-button" href="<?= store_escape($buildTalentMerchPageUrl($currentPage - 1)) ?>" aria-label="Previous page">Prev</a>
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
                                                <a class="all-products-page-number" href="<?= store_escape($buildTalentMerchPageUrl((int) $pageDot)) ?>" aria-label="Page <?= $pageDot ?>"><?= $pageDot ?></a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php if ($currentPage < $pageCount): ?>
                                        <a class="all-products-page-button" href="<?= store_escape($buildTalentMerchPageUrl($currentPage + 1)) ?>" aria-label="Next page">Next</a>
                                    <?php else: ?>
                                        <span class="all-products-page-button is-disabled" aria-hidden="true">Next</span>
                                    <?php endif; ?>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="all-products-empty" role="status"><?= $productSearch !== '' || $selectedCategory > 0 ? 'No merchandise matches the selected filters for ' . store_escape($talent['talent_name']) . '.' : 'There is no available merchandise for ' . store_escape($talent['talent_name']) . ' right now.' ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="talent-directory-back-wrap">
                <a class="button-primary dark" href="/talents">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Back to the talent directory</span>
                </a>
            </div>
        </main>
    </div>

    <script>
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
                const introActions = document.querySelector(".all-products-intro-actions");
                if (!nextLayout || !currentLayout) {
                    throw new Error("The collection response was incomplete.");
                }
                if (requestId !== catalogRequestId) {
                    return;
                }

                currentLayout.replaceWith(nextLayout);
                if (nextCount) {
                    if (currentCount) {
                        currentCount.replaceWith(nextCount);
                    } else if (introActions) {
                        introActions.prepend(nextCount);
                    }
                } else {
                    currentCount?.remove();
                }

                const responseUrl = new URL(response.url);
                window.history[historyMode === "push" ? "pushState" : "replaceState"]({}, "", responseUrl);
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

        document.addEventListener("change", (event) => {
            const target = event.target;
            if (target instanceof HTMLSelectElement && target.id === "talent-product-limit") {
                target.form?.requestSubmit();
            }
        });
    </script>
    <?php
    $cartHref = '/cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
