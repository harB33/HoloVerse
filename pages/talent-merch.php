<?php
require_once __DIR__ . '/../app/store.php';

$talentParameter = $_GET['talent'] ?? '';
$selectedTalent = is_string($talentParameter) ? trim($talentParameter) : '';
$searchParameter = $_GET['search'] ?? '';
$productSearch = is_string($searchParameter) ? trim($searchParameter) : '';
$talent = null;

if ($selectedTalent !== '') {
    $statement = $conn->prepare('SELECT talent_id, talent_name FROM talent_tbl WHERE talent_name = ? OR talent_slug = ? LIMIT 1');
    $statement->bind_param('ss', $selectedTalent, $selectedTalent);
    $statement->execute();
    $talent = $statement->get_result()->fetch_assoc();
    $statement->close();
}

if (!$talent) {
    http_response_code(404);
}

$products = [];
if ($talent) {
    $talentId = (int) $talent['talent_id'];
    $availableStock = store_available_stock_sql();
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
             ORDER BY p.product_id DESC, {$availableStock} DESC, v.variant_id ASC
         ) AS collection_products
         ORDER BY (collection_products.stock_quantity > 0) DESC, collection_products.product_id DESC"
    );
    $searchPattern = '%' . $productSearch . '%';
    $statement->bind_param('ssssi', $searchPattern, $searchPattern, $searchPattern, $searchPattern, $talentId);
    $statement->execute();
    $products = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();
}

$pageTitle = $talent ? $talent['talent_name'] . ' Merchandise | HoloVerse' : 'Talent Not Found | HoloVerse';
$siteRoot = '../';
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
                    <?php
                    $catalogSearchAction = 'talent-merch.php';
                    $catalogSearchId = 'talent-merch-search';
                    $catalogSearchLabel = 'Search this talent collection';
                    $catalogSearchPlaceholder = 'Search this collection';
                    $catalogSearchValue = $productSearch;
                    $catalogSearchHiddenFields = ['talent' => $selectedTalent];
                    require __DIR__ . '/../app/views/components/catalog-search.php';
                    ?>
                    <?php if ($products): ?>
                        <span class="all-products-count"><?= count($products) ?> products<?= $productSearch !== '' ? ' found' : '' ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <?php if ($talent && $products): ?>
                <section class="drops-grid all-products-grid" aria-label="<?= store_escape($talent['talent_name']) ?> merchandise">
                    <?php foreach ($products as $product): ?>
                        <?php $productInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product['product_name']), 0, 1)); ?>
                        <article class="drop-card">
                            <a class="drop-card-link" href="product.php?id=<?= (int) $product['product_id'] ?>" aria-label="View <?= store_escape($product['product_name']) ?>"></a>
                            <div class="drop-art">
                                <div class="drop-art-labels">
                                    <span class="drop-status"><?= (int) $product['stock_quantity'] > 0 ? 'Available now' : 'Sold out' ?></span>
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
            <?php elseif ($talent): ?>
                <p class="all-products-empty" role="status"><?= $productSearch !== '' ? 'No merchandise matches your search for ' . store_escape($talent['talent_name']) . '.' : 'There is no available merchandise for ' . store_escape($talent['talent_name']) . ' right now.' ?></p>
            <?php endif; ?>
            <a class="talent-directory-back-link" href="../index.php#explore">Back to the talent directory</a>
        </main>
    </div>

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
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
