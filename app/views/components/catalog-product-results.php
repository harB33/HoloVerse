<?php
$currentPage = isset($currentPage) ? max(1, (int) $currentPage) : 1;
$totalProductCount = isset($totalProductCount) ? max(0, (int) $totalProductCount) : 0;
$pageSize = isset($pageSize) ? max(1, (int) $pageSize) : 20;
$pageCount = isset($pageCount) ? max(1, (int) $pageCount) : 1;
$catalogPaginationBasePath = isset($catalogPaginationBasePath) && is_string($catalogPaginationBasePath) ? $catalogPaginationBasePath : '/products';
$productSearch = isset($productSearch) && is_string($productSearch) ? trim($productSearch) : '';
$selectedAvailability = isset($selectedAvailability) && is_string($selectedAvailability) ? $selectedAvailability : 'all';
$selectedSort = isset($selectedSort) && is_string($selectedSort) ? $selectedSort : 'featured';
$selectedCategory = isset($selectedCategory) ? (int) $selectedCategory : 0;
$selectedCategorySlug = isset($selectedCategorySlug) && is_string($selectedCategorySlug) ? $selectedCategorySlug : '';
$selectedGeneration = isset($selectedGeneration) && is_string($selectedGeneration) ? $selectedGeneration : '';
$categories = isset($categories) && is_array($categories) ? $categories : [];
$generations = isset($generations) && is_array($generations) ? $generations : [];

$categoryName = '';
if ($selectedCategory > 0) {
    foreach ($categories as $cat) {
        if ((int) $cat['category_id'] === $selectedCategory) {
            $categoryName = (string) $cat['category_name'];
            break;
        }
    }
}

$generationName = '';
if ($selectedGeneration !== '') {
    foreach ($generations as $gen) {
        if (($gen['group_slug'] ?? '') === $selectedGeneration) {
            $generationName = (string) ($gen['group_name'] ?? $selectedGeneration);
            break;
        }
    }
    if ($generationName === '') {
        $generationName = $selectedGeneration;
    }
}

$availabilityLabels = [
    'ready' => 'In stock',
    'preorder' => 'Pre-order',
    'flash' => 'Flash drop',
    'saved' => 'Saved',
];

$hasActiveFilters = $productSearch !== ''
    || $selectedCategory > 0
    || $selectedGeneration !== ''
    || $selectedAvailability !== 'all';

$buildFilterUrl = static function (array $overrides) use (
    $catalogPaginationBasePath,
    $productSearch,
    $selectedAvailability,
    $selectedSort,
    $selectedGeneration,
    $selectedCategorySlug
): string {
    $gen = array_key_exists('generation', $overrides) ? $overrides['generation'] : $selectedGeneration;
    $catSlug = array_key_exists('category_slug', $overrides) ? $overrides['category_slug'] : $selectedCategorySlug;
    $search = array_key_exists('search', $overrides) ? $overrides['search'] : $productSearch;
    $avail = array_key_exists('availability', $overrides) ? $overrides['availability'] : $selectedAvailability;
    $sort = array_key_exists('sort', $overrides) ? $overrides['sort'] : $selectedSort;
    $page = array_key_exists('page', $overrides) ? $overrides['page'] : 1;

    $base = '/products';
    if ($gen !== '') {
        $base .= '/generation/' . rawurlencode($gen);
    }
    if ($catSlug !== '') {
        $base .= '/category/' . rawurlencode($catSlug);
    }

    $params = [];
    if ($page > 1) {
        $params['page'] = $page;
    }
    if ($search !== '') {
        $params['search'] = $search;
    }
    if ($avail !== 'all') {
        $params['availability'] = $avail;
    }
    if ($sort !== 'featured') {
        $params['sort'] = $sort;
    }

    $qs = http_build_query($params);
    return $base . ($qs !== '' ? '?' . $qs : '');
};
?>

<div class="catalog-results-bar">
    <p class="results-caption" data-catalog-results-caption>
        <?php if ($totalProductCount > 0): ?>
            <?php
            $showingStart = ($currentPage - 1) * $pageSize + 1;
            $showingEnd = min($totalProductCount, $currentPage * $pageSize);
            ?>
            Showing <?= $showingStart ?>–<?= $showingEnd ?> of <?= number_format($totalProductCount) ?> <?= $totalProductCount === 1 ? 'piece' : 'pieces' ?> &middot; Official character goods
        <?php else: ?>
            0 pieces &middot; Official character goods
        <?php endif; ?>
    </p>

    <?php if ($hasActiveFilters): ?>
        <div class="active-filter-chips" aria-label="Active filters">
            <?php if ($selectedGeneration !== ''): ?>
                <a class="filter-chip" href="<?= store_escape($buildFilterUrl(['generation' => '', 'page' => 1])) ?>" data-remove-filter="generation" aria-label="Remove <?= store_escape($generationName) ?> generation filter">
                    <span>Generation: <?= store_escape($generationName) ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </a>
            <?php endif; ?>

            <?php if ($selectedCategory > 0 && $categoryName !== ''): ?>
                <a class="filter-chip" href="<?= store_escape($buildFilterUrl(['category_slug' => '', 'page' => 1])) ?>" data-remove-filter="category" aria-label="Remove <?= store_escape($categoryName) ?> category filter">
                    <span>Category: <?= store_escape($categoryName) ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </a>
            <?php endif; ?>

            <?php if ($selectedAvailability !== 'all' && isset($availabilityLabels[$selectedAvailability])): ?>
                <a class="filter-chip" href="<?= store_escape($buildFilterUrl(['availability' => 'all', 'page' => 1])) ?>" data-remove-filter="availability" aria-label="Remove <?= store_escape($availabilityLabels[$selectedAvailability]) ?> availability filter">
                    <span><?= store_escape($availabilityLabels[$selectedAvailability]) ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </a>
            <?php endif; ?>

            <?php if ($productSearch !== ''): ?>
                <a class="filter-chip" href="<?= store_escape($buildFilterUrl(['search' => '', 'page' => 1])) ?>" data-remove-filter="search" aria-label="Remove search query">
                    <span>&ldquo;<?= store_escape($productSearch) ?>&rdquo;</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </a>
            <?php endif; ?>

            <a class="filter-clear-all" href="/products" data-clear-all-filters>Clear all</a>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($products)): ?>
    <section class="product-grid all-products-grid" aria-label="All available products">
        <?php foreach ($products as $product): ?>
            <?php require __DIR__ . '/product-card.php'; ?>
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
        <nav class="all-products-pagination" aria-label="Product pages">
            <?php if ($currentPage > 1): ?>
                <a class="all-products-page-button" href="<?= store_escape($buildFilterUrl(['page' => $currentPage - 1])) ?>" aria-label="Previous page">Prev</a>
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
                        <a class="all-products-page-number" href="<?= store_escape($buildFilterUrl(['page' => (int) $pageDot])) ?>" aria-label="Page <?= $pageDot ?>"><?= $pageDot ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($currentPage < $pageCount): ?>
                <a class="all-products-page-button" href="<?= store_escape($buildFilterUrl(['page' => $currentPage + 1])) ?>" aria-label="Next page">Next</a>
            <?php else: ?>
                <span class="all-products-page-button is-disabled" aria-hidden="true">Next</span>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <div class="empty-search" data-catalog-empty>
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <h3>No merch found</h3>
        <p><?= $productSearch !== '' ? 'No items match &ldquo;' . store_escape($productSearch) . '&rdquo;. Try another keyword or reset filters.' : 'No products match your selected filters. Try choosing different options or reset filters.' ?></p>
        <a class="button-primary dark" href="/products" data-reset-catalog-filters>
            Reset filters
        </a>
    </div>
<?php endif; ?>
