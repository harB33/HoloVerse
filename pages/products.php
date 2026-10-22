<?php
require_once __DIR__ . '/../app/store.php';

$pageSize = 12;
$searchParameter = $_GET['search'] ?? '';
$filterSearch = is_string($searchParameter) ? trim($searchParameter) : '';
$generalSearchParameter = $_GET['global_search'] ?? '';
$generalProductSearch = is_string($generalSearchParameter) ? trim($generalSearchParameter) : '';
$productSearch = $filterSearch !== '' ? $filterSearch : $generalProductSearch;
$isCatalogSearchRequest = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && ($_GET['ajax'] ?? '') === 'search';
$activeSearchQuery = $filterSearch !== ''
    ? ['search' => $filterSearch]
    : ($generalProductSearch !== '' ? ['global_search' => $generalProductSearch] : []);
$generationParameter = $_GET['generation'] ?? '';
$selectedGeneration = is_string($generationParameter)
    && in_array($generationParameter, STORE_TALENT_FILTER_GROUP_SLUGS, true)
        ? $generationParameter
        : '';
$categories = [];
$categoryIds = [];
if (!$isCatalogSearchRequest) {
    $categoryResult = $conn->query('SELECT category_id, category_name FROM category_tbl ORDER BY category_name');
    $categories = $categoryResult->fetch_all(PDO::FETCH_ASSOC);
    $categoryIds = array_map(static fn (array $category): int => (int) $category['category_id'], $categories);
}
$categoryParameter = $_GET['category'] ?? '';
$selectedCategory = is_string($categoryParameter)
    ? filter_var($categoryParameter, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
if ($isCatalogSearchRequest) {
    $selectedCategory = $selectedCategory !== false && $selectedCategory > 0 ? $selectedCategory : 0;
} else {
    $selectedCategory = $selectedCategory !== false && in_array($selectedCategory, $categoryIds, true)
        ? $selectedCategory
        : 0;
}

if (!$isCatalogSearchRequest) {
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
}

if ($isCatalogSearchRequest) {
    $searchPageSize = 12;
    $searchTotalCount = store_catalog_product_count($conn, $productSearch, $selectedGeneration, $selectedCategory);
    $searchPageCount = max(1, (int) ceil($searchTotalCount / $searchPageSize));
    $searchRequestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
    $searchCurrentPage = min(max(1, $searchRequestedPage), $searchPageCount);
    $searchProducts = store_catalog_product_rows(
        $conn,
        $searchPageSize,
        ($searchCurrentPage - 1) * $searchPageSize,
        $productSearch,
        $selectedGeneration,
        $selectedCategory
    );
    ob_start();
    $products = $searchProducts;
    $totalProductCount = $searchTotalCount;
    $pageCount = $searchPageCount;
    $currentPage = $searchCurrentPage;
    $activeSearchQuery = $productSearch !== '' ? ['search' => $productSearch] : [];
    $catalogFilterQuery = array_filter($activeSearchQuery + [
        'category' => $selectedCategory,
    ], static fn (string|int $value): bool => $value !== '' && $value !== 0);
    echo '<div class="catalog-product-results-fragment"><span id="all-products-count">'
        . number_format($totalProductCount)
        . ' products'
        . ($productSearch !== '' ? ' found' : '')
        . '</span>';
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

$totalProductCount = store_catalog_product_count($conn, $productSearch, $selectedGeneration, $selectedCategory);
$pageCount = max(1, (int) ceil($totalProductCount / $pageSize));
$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$currentPage = min(max(1, $currentPage), $pageCount);
$products = store_catalog_product_rows($conn, $pageSize, ($currentPage - 1) * $pageSize, $productSearch, $selectedGeneration, $selectedCategory);
$catalogSearchHiddenFields = array_filter([
    'generation' => $selectedGeneration,
    'category' => $selectedCategory,
], static fn (string|int $value): bool => $value !== '' && $value !== 0);
$catalogFilterQuery = array_filter($activeSearchQuery + [
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
                <span class="all-products-count" id="all-products-count"><?= number_format($totalProductCount) ?> products<?= $productSearch !== '' ? ' found' : '' ?></span>
                <nav class="products-promo-links" aria-label="Shop product collections">
                    <a href="flash_sale"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap preview-icon" aria-hidden="true"><path d="M15.914 4a1.5 1.5 0 00-2.474-1.561l-9 9A1.5 1.5 0 005.5 14h4.002a.5.5 0 01.471.666L8.086 20a1.5 1.5 0 002.475 1.56l9-9A1.5 1.5 0 0018.5 10h-3.997a.5.5 0 01-.472-.667z"/></svg>Flash Sale</a>
                    <a href="pre_order"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-arrow-up preview-icon" aria-hidden="true"><path d="m14 17 4-4 4 4"/><path d="M16 2v3"/><path d="M18 21v-8"/><path d="M21 10.343V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h9"/><path d="M3 9h18"/><path d="M8 2v3"/></svg>Pre-order</a>
                </nav>
            </section>

            <div class="products-catalog-layout">
                <aside class="generation-filter" aria-labelledby="generation-filter-title">
                    <?php
                    $catalogSearchAction = 'products';
                    $catalogSearchId = 'filter-panel-catalog-search';
                    $catalogSearchClass = 'filter-panel-search';
                    $catalogSearchLabel = 'Search products, categories, or talents';
                    $catalogSearchPlaceholder = 'Search products...';
                    $catalogSearchInputName = 'search';
                    $catalogSearchValue = $filterSearch;
                    $catalogSearchShowClear = false;
                    require __DIR__ . '/../app/views/components/catalog-search.php';
                    ?>
                    <form class="category-filter" method="get" action="products">
                        <label for="catalog-category-trigger">Filter by category</label>
                        <?php foreach ($activeSearchQuery as $name => $value): ?>
                            <input type="hidden" name="<?= store_escape($name) ?>" value="<?= store_escape((string) $value) ?>">
                        <?php endforeach; ?>
                        <?php if ($selectedGeneration !== ''): ?>
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
                                    <button class="category-filter-option<?= $selectedCategory === $categoryId ? ' is-selected' : '' ?>" type="button" role="option" data-category-option data-category-value="<?= $categoryId ?>" aria-selected="<?= $selectedCategory === $categoryId ? 'true' : 'false' ?>"><?= store_escape($category['category_name']) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </form>
                    <h2 id="generation-filter-title">Filter by generation</h2>
                    <nav aria-label="Product generations">
                        <a class="generation-filter-option<?= $selectedGeneration === '' ? ' is-active' : '' ?>" href="products<?= $catalogFilterQuery ? '?' . store_escape(http_build_query($catalogFilterQuery)) : '' ?>"<?= $selectedGeneration === '' ? ' aria-current="true"' : '' ?>>All Generations</a>
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
            <?php require __DIR__ . '/../app/views/components/catalog-product-results.php'; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        const categoryFilter = document.querySelector(".category-filter");
        if (categoryFilter) {
            const categoryInput = categoryFilter.querySelector("#catalog-category");
            const categoryTrigger = categoryFilter.querySelector("[data-category-trigger]");
            const categoryLabel = categoryFilter.querySelector("[data-category-label]");
            const categoryOptions = categoryFilter.querySelector("#catalog-category-options");
            const setCategoryMenuOpen = (open) => {
                categoryTrigger.setAttribute("aria-expanded", String(open));
                categoryOptions.hidden = !open;
            };

            categoryTrigger.addEventListener("click", () => {
                setCategoryMenuOpen(categoryTrigger.getAttribute("aria-expanded") !== "true");
            });
            categoryFilter.querySelectorAll("[data-category-option]").forEach((option) => {
                option.addEventListener("click", () => {
                    categoryInput.value = option.dataset.categoryValue;
                    categoryLabel.textContent = option.textContent.trim();
                    categoryFilter.querySelectorAll("[data-category-option]").forEach((item) => {
                        const selected = item === option;
                        item.classList.toggle("is-selected", selected);
                        item.setAttribute("aria-selected", String(selected));
                    });
                    setCategoryMenuOpen(false);
                    categoryFilter.requestSubmit();
                });
                option.addEventListener("keydown", (event) => {
                    const options = [...categoryFilter.querySelectorAll("[data-category-option]")];
                    const index = options.indexOf(option);
                    const nextIndex = event.key === "ArrowDown"
                        ? Math.min(index + 1, options.length - 1)
                        : event.key === "ArrowUp"
                            ? Math.max(index - 1, 0)
                            : event.key === "Home"
                                ? 0
                                : event.key === "End"
                                    ? options.length - 1
                                    : -1;
                    if (nextIndex >= 0) {
                        event.preventDefault();
                        options[nextIndex].focus();
                    } else if (event.key === "Escape") {
                        event.preventDefault();
                        setCategoryMenuOpen(false);
                        categoryTrigger.focus();
                    }
                });
            });
            categoryTrigger.addEventListener("keydown", (event) => {
                if (event.key === "ArrowDown" || event.key === "ArrowUp") {
                    event.preventDefault();
                    setCategoryMenuOpen(true);
                    categoryFilter.querySelector('[data-category-option][aria-selected="true"]')?.focus();
                } else if (event.key === "Escape") {
                    setCategoryMenuOpen(false);
                }
            });
            document.addEventListener("click", (event) => {
                if (!categoryFilter.contains(event.target)) {
                    setCategoryMenuOpen(false);
                }
            });
        }

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
            if (form.matches(".catalog-search")) {
                const searchInput = form.querySelector('input[name="search"]');
                if (searchInput) {
                    let debounceTimer;
                    let activeController;
                    let requestId = 0;

                    const updateCatalogSearch = async (page = 1) => {
                        const request = ++requestId;
                        activeController?.abort();
                        activeController = new AbortController();
                        catalogResults?.classList.add("is-loading");
                        catalogResults?.setAttribute("aria-busy", "true");

                        const searchUrl = new URL(form.action, window.location.href);
                        const params = new URLSearchParams(new FormData(form));
                        params.set("ajax", "search");
                        params.set("page", String(page));
                        searchUrl.search = params.toString();

                        try {
                            const response = await fetch(searchUrl, { signal: activeController.signal });
                            if (!response.ok) {
                                throw new Error(`Product search failed with status ${response.status}.`);
                            }
                            const data = await response.json();
                            if (request !== requestId) {
                                return;
                            }
                            const resultDocument = new DOMParser().parseFromString(data.html, "text/html");
                            const nextResults = resultDocument.querySelector(".catalog-product-results-fragment");
                            const nextCount = resultDocument.querySelector("#all-products-count");
                            if (!nextResults || !nextCount || !catalogResults) {
                                throw new Error("Product search returned incomplete results.");
                            }
                            catalogResults.replaceChildren(...nextResults.childNodes);
                            document.querySelector("#all-products-count").textContent = nextCount.textContent;

                            const currentUrl = new URL(window.location.href);
                            currentUrl.searchParams.delete("ajax");
                            currentUrl.searchParams.delete("global_search");
                            currentUrl.searchParams.delete("page");
                            currentUrl.searchParams.delete("search");
                            params.delete("ajax");
                            params.delete("page");
                            for (const [name, value] of params) {
                                if (value) {
                                    currentUrl.searchParams.set(name, value);
                                } else {
                                    currentUrl.searchParams.delete(name);
                                }
                            }
                            if (searchInput.value.trim()) {
                                currentUrl.searchParams.set("search", searchInput.value.trim());
                            }
                            currentUrl.searchParams.set("page", String(data.currentPage));
                            window.history.replaceState({}, "", currentUrl);
                            initializeProductImages();
                        } catch (error) {
                            if (error.name !== "AbortError" && request === requestId) {
                                console.error(error);
                                catalogResults?.setAttribute("aria-busy", "false");
                                catalogResults?.classList.remove("is-loading");
                            }
                        } finally {
                            if (request === requestId) {
                                catalogResults?.setAttribute("aria-busy", "false");
                                catalogResults?.classList.remove("is-loading");
                            }
                        }
                    };

                    form.addEventListener("input", (event) => {
                        if (event.target !== searchInput) {
                            return;
                        }
                        activeController?.abort();
                        requestId++;
                        window.clearTimeout(debounceTimer);
                        debounceTimer = window.setTimeout(() => updateCatalogSearch(), 100);
                    });
                    form.addEventListener("submit", (event) => {
                        event.preventDefault();
                        window.clearTimeout(debounceTimer);
                        updateCatalogSearch();
                    });
                }
            } else {
                form.addEventListener("submit", startCatalogLoading);
            }
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

        const initializeProductImages = () => {
            document.querySelectorAll(".drop-product-image:not([data-image-initialized])").forEach((image) => {
                image.dataset.imageInitialized = "true";
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
        };
        initializeProductImages();
    </script>

    <?php
    $cartHref = 'cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
