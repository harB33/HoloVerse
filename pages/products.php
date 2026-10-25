<?php
require_once __DIR__ . '/../app/store.php';

$favoriteProductIds = store_collector_favorite_ids($conn, (int) ($_SESSION['user_id'] ?? 0));
$pageSize = 20;
$searchParameter = $_GET['search'] ?? '';
$filterSearch = is_string($searchParameter) ? trim($searchParameter) : '';
$generalSearchParameter = $_GET['global_search'] ?? '';
$generalProductSearch = is_string($generalSearchParameter) ? trim($generalSearchParameter) : '';
$isCatalogSearchRequest = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && ($_GET['ajax'] ?? '') === 'search';
$generationParameter = $_GET['generation'] ?? '';
$routeSegment = $_GET['route_segment'] ?? '';
$routeSegment = is_string($routeSegment) ? rawurldecode($routeSegment) : '';
$selectedGeneration = is_string($generationParameter)
    && in_array($generationParameter, STORE_TALENT_FILTER_GROUP_SLUGS, true)
        ? $generationParameter
        : '';
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '';
$routeSegments = array_filter(explode('/', trim($requestPath, '/')), static fn (string $segment): bool => $segment !== '');
$siteRoot = str_repeat('../', max(1, count($routeSegments) - 1));
$categories = [];
$categoryIds = [];
$categoryResult = $conn->query('SELECT category_id, category_name FROM category_tbl ORDER BY category_name');
$categories = $categoryResult->fetch_all(PDO::FETCH_ASSOC);
$categoryIds = array_map(static fn (array $category): int => (int) $category['category_id'], $categories);
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
$catalogBasePath = $selectedGeneration !== ''
    ? '/products/generation/' . rawurlencode($selectedGeneration)
    : '/products';
$catalogPath = $selectedCategorySlug !== ''
    ? $catalogBasePath . '/category/' . rawurlencode($selectedCategorySlug)
    : $catalogBasePath;
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
    $searchPageSize = 20;
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
    $catalogPaginationBasePath = $catalogPath;
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
$catalogSearchHiddenFields = [];
$catalogPaginationBasePath = $catalogPath;
$pageTitle = 'All Products | HoloVerse';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <script>document.body.classList.add("products-page-loading");</script>
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
                    <a href="<?= $siteRoot ?>flash-sale"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap preview-icon" aria-hidden="true"><path d="M15.914 4a1.5 1.5 0 00-2.474-1.561l-9 9A1.5 1.5 0 005.5 14h4.002a.5.5 0 01.471.666L8.086 20a1.5 1.5 0 002.475 1.56l9-9A1.5 1.5 0 0018.5 10h-3.997a.5.5 0 01-.472-.667z"/></svg>Flash Sale</a>
                    <a href="<?= $siteRoot ?>pre_order"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-calendar-arrow-up preview-icon" aria-hidden="true"><path d="m14 17 4-4 4 4"/><path d="M16 2v3"/><path d="M18 21v-8"/><path d="M21 10.343V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h9"/><path d="M3 9h18"/><path d="M8 2v3"/></svg>Pre-order</a>
                </nav>
            </section>

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
                    require __DIR__ . '/../app/views/components/catalog-search.php';
                    ?>
                    <form class="category-filter" method="get" action="<?= store_escape($catalogBasePath) ?>" data-catalog-base-path="<?= store_escape($catalogBasePath) ?>">
                        <label for="catalog-category-trigger">Filter by category</label>
                        <?php foreach ($activeSearchQuery as $name => $value): ?>
                            <input type="hidden" name="<?= store_escape($name) ?>" value="<?= store_escape((string) $value) ?>">
                        <?php endforeach; ?>
                        <input id="catalog-category" type="hidden" value="<?= $selectedCategory > 0 ? $selectedCategory : '' ?>">
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
                    <h2 id="generation-filter-title">Filter by generation</h2>
                    <nav aria-label="Product generations">
                        <?php
                        $allGenerationsHref = '/products' . ($selectedCategorySlug !== '' ? '/category/' . rawurlencode($selectedCategorySlug) : '');
                        if ($activeSearchQuery) {
                            $allGenerationsHref .= '?' . http_build_query($activeSearchQuery);
                        }
                        ?>
                        <a class="generation-filter-option<?= $selectedGeneration === '' ? ' is-active' : '' ?>" href="<?= store_escape($allGenerationsHref) ?>" data-generation-slug=""<?= $selectedGeneration === '' ? ' aria-current="true"' : '' ?>>All Generations</a>
                        <?php foreach ($generations as $generation): ?>
                            <?php
                            $generationHref = '/products/generation/' . rawurlencode($generation['group_slug']);
                            if ($selectedCategorySlug !== '') {
                                $generationHref .= '/category/' . rawurlencode($selectedCategorySlug);
                            }
                            if ($activeSearchQuery) {
                                $generationHref .= '?' . http_build_query($activeSearchQuery);
                            }
                            ?>
                            <a class="generation-filter-option<?= $selectedGeneration === $generation['group_slug'] ? ' is-active' : '' ?>" href="<?= store_escape($generationHref) ?>" data-generation-slug="<?= store_escape($generation['group_slug']) ?>"<?= $selectedGeneration === $generation['group_slug'] ? ' aria-current="true"' : '' ?>><?= store_escape($generation['group_name']) ?></a>
                        <?php endforeach; ?>
                    </nav>
                </aside>

                <div class="products-catalog-results" aria-busy="false">
                    <script>document.currentScript.parentElement.setAttribute("aria-busy", "true");</script>
                    <div class="drops-grid products-loading-skeleton" aria-hidden="true">
                        <?php for ($skeletonIndex = 0; $skeletonIndex < 8; $skeletonIndex++): ?>
                            <article class="drop-card products-skeleton-card">
                                <div class="drop-art">
                                    <span class="products-skeleton-block products-skeleton-favorite"></span>
                                    <span class="products-skeleton-block products-skeleton-artwork"></span>
                                    <span class="products-skeleton-block products-skeleton-edition"></span>
                                </div>
                                <div class="drop-details">
                                    <span class="products-skeleton-block products-skeleton-category"></span>
                                    <div class="products-skeleton-title">
                                        <span class="products-skeleton-block"></span>
                                        <span class="products-skeleton-block products-skeleton-title-short"></span>
                                    </div>
                                    <div class="products-skeleton-description">
                                        <span class="products-skeleton-block"></span>
                                        <span class="products-skeleton-block"></span>
                                        <span class="products-skeleton-block products-skeleton-description-short"></span>
                                    </div>
                                    <hr class="drop-allocation-divider">
                                    <div class="products-skeleton-purchase">
                                        <div class="products-skeleton-price-group">
                                            <span class="products-skeleton-block products-skeleton-price-label"></span>
                                            <div class="products-skeleton-price-row">
                                                <span class="products-skeleton-block products-skeleton-price"></span>
                                                <span class="products-skeleton-block products-skeleton-allocation"></span>
                                            </div>
                                        </div>
                                        <span class="products-skeleton-block products-skeleton-button"></span>
                                    </div>
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
                    const categoryBasePath = categoryFilter.dataset.catalogBasePath;
                    categoryFilter.action = option.dataset.categorySlug
                        ? `${categoryBasePath}/category/${encodeURIComponent(option.dataset.categorySlug)}`
                        : categoryBasePath;
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

        let updateCatalogResults;
        const syncCatalogFilters = (url, searchTerm) => {
            const generation = url.pathname.match(/\/generation\/([^/]+)/)?.[1] || "";
            const categorySlug = url.pathname.match(/\/category\/([^/]+)/)?.[1] || "";
            const catalogBasePath = `/products${generation ? `/generation/${generation}` : ""}`;
            const catalogPath = `${catalogBasePath}${categorySlug ? `/category/${categorySlug}` : ""}`;
            const categoryFilter = document.querySelector(".category-filter");
            const searchForm = document.querySelector(".filter-panel-search");

            if (categoryFilter) {
                categoryFilter.dataset.catalogBasePath = catalogBasePath;
                categoryFilter.action = catalogPath;
                const selectedOption = [...categoryFilter.querySelectorAll("[data-category-option]")]
                    .find((option) => (option.dataset.categorySlug || "") === categorySlug);
                categoryFilter.querySelectorAll("[data-category-option]").forEach((option) => {
                    const selected = option === selectedOption || (!categorySlug && !option.dataset.categorySlug);
                    option.classList.toggle("is-selected", selected);
                    option.setAttribute("aria-selected", String(selected));
                });
                const categoryInput = categoryFilter.querySelector("#catalog-category");
                if (categoryInput) {
                    categoryInput.value = selectedOption?.dataset.categoryValue || "";
                }
                const categoryLabel = categoryFilter.querySelector("[data-category-label]");
                if (categoryLabel) {
                    categoryLabel.textContent = selectedOption?.textContent.trim() || "All Categories";
                }
            }

            if (searchForm) {
                searchForm.action = catalogPath;
            }

            document.querySelectorAll(".generation-filter-option").forEach((link) => {
                const linkGeneration = link.dataset.generationSlug || "";
                const linkBasePath = `/products${linkGeneration ? `/generation/${linkGeneration}` : ""}`;
                const linkPath = `${linkBasePath}${categorySlug ? `/category/${categorySlug}` : ""}`;
                const linkUrl = new URL(linkPath, window.location.origin);
                if (searchTerm) {
                    linkUrl.searchParams.set("search", searchTerm);
                }
                link.href = linkUrl.href;
                const selected = linkGeneration === generation;
                link.classList.toggle("is-active", selected);
                if (selected) {
                    link.setAttribute("aria-current", "true");
                } else {
                    link.removeAttribute("aria-current");
                }
            });
        };

        document.querySelectorAll(".catalog-search, .category-filter").forEach((form) => {
            if (form.matches(".catalog-search")) {
                const searchInput = form.querySelector('input[name="search"]');
                if (searchInput) {
                    let debounceTimer;
                    let activeController;
                    let requestId = 0;

                    const updateCatalogSearch = async (route = form.action, page, historyMode = "replace") => {
                        const request = ++requestId;
                        activeController?.abort();
                        activeController = new AbortController();
                        catalogResults?.classList.add("is-loading");
                        catalogResults?.setAttribute("aria-busy", "true");

                        const searchUrl = new URL(route, window.location.href);
                        const requestedPage = page || Number(searchUrl.searchParams.get("page")) || 1;
                        searchUrl.searchParams.delete("ajax");
                        searchUrl.searchParams.delete("page");
                        searchUrl.searchParams.delete("search");
                        searchUrl.searchParams.delete("global_search");
                        const searchTerm = searchInput.value.trim();
                        if (searchTerm) {
                            searchUrl.searchParams.set("search", searchTerm);
                        }
                        const params = searchUrl.searchParams;
                        params.set("ajax", "search");
                        params.set("page", String(requestedPage));

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
                            document.querySelector("[data-catalog-search-error]")?.remove();
                            const loadingSkeleton = catalogResults.querySelector(".products-loading-skeleton");
                            loadingSkeleton?.remove();
                            catalogResults.replaceChildren(
                                ...(loadingSkeleton ? [loadingSkeleton] : []),
                                ...nextResults.childNodes
                            );
                            document.querySelector("#all-products-count").textContent = nextCount.textContent;

                            const currentUrl = new URL(searchUrl);
                            currentUrl.searchParams.delete("ajax");
                            currentUrl.searchParams.delete("page");
                            if (searchTerm && !/\/(?:generation|category)\//.test(currentUrl.pathname)) {
                                currentUrl.pathname = `/products/${encodeURIComponent(searchTerm)}`;
                                currentUrl.searchParams.delete("search");
                            } else if (searchTerm) {
                                currentUrl.searchParams.set("search", searchTerm);
                            } else {
                                currentUrl.searchParams.delete("search");
                            }
                            if (data.currentPage > 1) {
                                currentUrl.searchParams.set("page", String(data.currentPage));
                            }
                            syncCatalogFilters(currentUrl, searchTerm);
                            window.history[historyMode === "push" ? "pushState" : "replaceState"]({}, "", currentUrl);
                            initializeProductImages();
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
                    updateCatalogResults = updateCatalogSearch;

                    form.addEventListener("input", (event) => {
                        if (event.target !== searchInput) {
                            return;
                        }
                        activeController?.abort();
                        requestId++;
                        window.clearTimeout(debounceTimer);
                        debounceTimer = window.setTimeout(() => updateCatalogSearch(form.action, 1), 100);
                    });
                    form.addEventListener("submit", (event) => {
                        event.preventDefault();
                        window.clearTimeout(debounceTimer);
                        updateCatalogSearch(form.action, 1, "push");
                    });
                }
            } else {
                form.addEventListener("submit", (event) => {
                    event.preventDefault();
                    updateCatalogResults?.(form.action, 1, "push");
                });
            }
        });

        document.addEventListener("click", (event) => {
            const link = event.target instanceof Element
                ? event.target.closest(".generation-filter-option, .all-products-pagination a, .filter-panel-search a")
                : null;
            if (!link || !updateCatalogResults || event.defaultPrevented
                || event.button !== 0 || event.metaKey || event.ctrlKey
                || event.shiftKey || event.altKey || (link.target && link.target !== "_self")) {
                return;
            }
            event.preventDefault();
            const linkUrl = new URL(link.href, window.location.href);
            if (link.matches(".filter-panel-search a")) {
                const searchInput = document.querySelector('.filter-panel-search input[name="search"]');
                if (searchInput) {
                    searchInput.value = linkUrl.searchParams.get("search")
                        || linkUrl.searchParams.get("global_search")
                        || "";
                }
            }
            updateCatalogResults(linkUrl.href, Number(linkUrl.searchParams.get("page")) || 1, "push");
        });

        window.addEventListener("popstate", () => {
            const currentUrl = new URL(window.location.href);
            updateCatalogResults?.(currentUrl.href, Number(currentUrl.searchParams.get("page")) || 1);
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
