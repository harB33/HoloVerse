<?php
require_once __DIR__ . '/../app/store.php';
$flashSaleTimezone = new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila');
$flashSaleNow = new DateTimeImmutable('now', $flashSaleTimezone);
$flashSaleSchedulingAvailable = store_flash_sale_table_exists($conn);
$flashSaleProducts = $flashSaleSchedulingAvailable
    ? [
        'live' => store_flash_sale_rows($conn, 'live', $flashSaleNow),
        'starting' => store_flash_sale_rows($conn, 'starting', $flashSaleNow),
        'tomorrow' => store_flash_sale_rows($conn, 'tomorrow', $flashSaleNow),
        'archive' => store_flash_sale_rows($conn, 'archive', $flashSaleNow),
    ]
    : ['live' => [], 'starting' => [], 'tomorrow' => [], 'archive' => []];
$favoriteProductIds = store_collector_favorite_ids($conn, (int) ($_SESSION['user_id'] ?? 0));
$flashSaleProductImages = [];
$flashSaleProductIds = [];
foreach ($flashSaleProducts as $products) {
    foreach ($products as $product) {
        $flashSaleProductIds[] = (int) $product['product_id'];
    }
}
$flashSaleProductIds = array_values(array_unique($flashSaleProductIds));
if ($flashSaleProductIds) {
    $imageProductIds = implode(', ', $flashSaleProductIds);
    $saleImagesStatement = $conn->prepare(
        "SELECT product_id, image_url, alt_text
         FROM product_image_tbl
         WHERE product_id IN ({$imageProductIds})
         ORDER BY product_id, sort_order, image_id"
    );
    $saleImagesStatement->execute();
    foreach ($saleImagesStatement->get_result()->fetch_all(PDO::FETCH_ASSOC) as $image) {
        $productId = (int) $image['product_id'];
        $flashSaleProductImages[$productId][] = [
            'image_url' => store_asset_url((string) ($image['image_url'] ?? 'assets/images/logo.png'), '../'),
            'alt_text' => $image['alt_text'] ?? '',
        ];
    }
    $saleImagesStatement->close();
}
$saleProduct = $flashSaleProducts['live'][0] ?? null;
$saleGalleryImages = [];
if ($saleProduct) {
    $saleProductId = (int) $saleProduct['product_id'];
    $saleGalleryImages = $flashSaleProductImages[$saleProductId] ?? [];
}
$activeCartQuantity = 0;
$activeCartId = store_current_cart_id($conn, false);
if ($activeCartId > 0) {
    $cartCountStatement = $conn->prepare('SELECT COALESCE(SUM(quantity), 0) AS item_count FROM cart_items_tbl WHERE cart_id = ?');
    $cartCountStatement->bind_param('i', $activeCartId);
    $cartCountStatement->execute();
    $activeCartQuantity = (int) $cartCountStatement->get_result()->fetch_assoc()['item_count'];
    $cartCountStatement->close();
}
$pageTitle = 'Flash Sale | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell info-page flash-sale-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>

        <?php
        $activePage = 'flash_sale';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="flash-sale-main">
            <?php if (!$flashSaleSchedulingAvailable): ?>
                <p class="flash-setup-notice" role="status">Flash sale scheduling is temporarily unavailable, so no drops are being shown. The database must include the <code>flash_sale_tbl</code> table from <code>Database/holoverse.sql</code> before an administrator can schedule sales.</p>
            <?php endif; ?>

            <section class="flash-sale-heading" aria-labelledby="flash-sale-title">
                <div>
                    <div class="flash-heading-meta">
                        <span class="flash-mode-pill">Limited-time offers</span>
                        <span>Official hololive merchandise</span>
                    </div>
                    <h1 id="flash-sale-title">Flash Sales</h1>
                    <p class="flash-sale-subtitle">5-minute holds on live flash-sale items; stock and CSRF checked</p>
                    <?php if (store_has_role('admin')): ?>
                        <a class="flash-manage-drops-link" href="flash-sale-admin">Manage drops</a>
                    <?php endif; ?>
                </div>
                <div class="flash-countdown">
                    <?php if ($saleProduct && !empty($saleProduct['ends_at'])): ?>
                        <span class="flash-countdown-label" data-sale-countdown-label>
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 6v6l4 2" />
                            </svg>
                            <span data-sale-countdown-text>Drop ends in:</span>
                        </span>
                        <div class="flash-countdown-clock" data-countdown="<?= store_escape((new DateTimeImmutable($saleProduct['ends_at'], $flashSaleTimezone))->format(DateTimeInterface::ATOM)) ?>">
                            <span data-countdown-days>00</span><b>d</b><span data-countdown-hours>00</span><b>:</b><span data-countdown-minutes>00</span><b>:</b><small data-countdown-seconds>00</small>
                        </div>
                    <?php else: ?>
                        <span class="flash-countdown-label">Discounts while supplies last</span>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($saleProduct): ?>
            <?php
            $saleGalleryImages = $saleGalleryImages ?: [[
                'image_url' => $saleProduct['image_url'] ?? 'assets/images/logo.png',
                'alt_text' => $saleProduct['alt_text'] ?? $saleProduct['product_name'],
            ]];
            foreach ($saleGalleryImages as $imageIndex => $image) {
                $saleGalleryImages[$imageIndex]['image_url'] = store_asset_url($image['image_url'], '../');
                $saleGalleryImages[$imageIndex]['alt_text'] = $image['alt_text'] ?: $saleProduct['product_name'];
            }
            $saleImageUrl = $saleGalleryImages[0]['image_url'];
            $saleImageAlt = $saleGalleryImages[0]['alt_text'];
            ?>
            <div class="product-page-topbar">
                <nav class="product-breadcrumb" aria-label="Breadcrumb">
                    <a href="../home">Home</a>
                    <svg class="product-breadcrumb-separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                    <a href="../products">Products</a>
                    <svg class="product-breadcrumb-separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                    <span aria-current="page" data-sale-detail="breadcrumb"><?= store_escape($saleProduct['product_name']) ?></span>
                </nav>
                <a class="product-back-link" href="../products">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                    Back to Products
                </a>
            </div>
            <article class="flash-sale-card">
                <section class="flash-gallery" aria-label="Product gallery">
                    <div class="flash-product-image">
                        <img class="flash-product-backdrop" src="<?= store_escape($saleImageUrl) ?>" alt="" aria-hidden="true">
                        <img class="flash-product-main-image" src="<?= store_escape($saleImageUrl) ?>" alt="<?= store_escape($saleImageAlt) ?>" tabindex="0" fetchpriority="high">
                        <div class="flash-image-badges">
                            <span class="drop-edition" data-sale-detail="stock-badge"><?= (int) $saleProduct['stock_quantity'] ?> units available</span>
                            <span class="drop-edition" data-sale-detail="product-badge"><?= store_escape($saleProduct['product_name']) ?></span>
                        </div>
                    </div>
                    <div class="flash-image-caption">
                        <span data-sale-detail="variant-caption"><?= store_escape($saleProduct['variant_name']) ?></span>
                        <span data-sale-detail="category-caption"><?= store_escape($saleProduct['category_name']) ?></span>
                        <span>Official merchandise</span>
                    </div>
                    <div class="product-gallery-carousel" data-gallery-carousel>
                        <button class="product-gallery-arrow" type="button" data-gallery-previous aria-label="Show previous product image"<?= count($saleGalleryImages) < 2 ? ' hidden' : '' ?>>‹</button>
                        <div class="product-gallery-thumbnails" aria-label="Product images" role="group">
                            <?php foreach ($saleGalleryImages as $imageIndex => $image): ?>
                                <button class="product-gallery-thumbnail<?= $imageIndex === 0 ? ' is-active' : '' ?>" type="button" data-gallery-image data-image-src="<?= store_escape($image['image_url']) ?>" data-image-alt="<?= store_escape($image['alt_text']) ?>" aria-label="Show product image <?= $imageIndex + 1 ?>" aria-pressed="<?= $imageIndex === 0 ? 'true' : 'false' ?>">
                                    <img src="<?= store_escape($image['image_url']) ?>" alt="" loading="lazy" decoding="async">
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <button class="product-gallery-arrow" type="button" data-gallery-next aria-label="Show next product image"<?= count($saleGalleryImages) < 2 ? ' hidden' : '' ?>>›</button>
                    </div>
                </section>

                <section class="flash-product-details" aria-labelledby="flash-product-title">
                    <div class="flash-breadcrumb"><span data-sale-detail="category"><?= store_escape($saleProduct['category_name']) ?></span> / <span data-sale-detail="variant"><?= store_escape($saleProduct['variant_name']) ?></span></div>
                    <h2 id="flash-product-title" data-sale-detail="name"><?= store_escape($saleProduct['product_name']) ?></h2>
                    <?php $saleDescription = (string) ($saleProduct['description'] ?? ''); ?>
                    <div class="flash-product-description-block">
                        <p class="flash-product-description" data-sale-detail="description-preview"><?= store_escape($saleDescription) ?></p>
                        <p class="flash-product-description-full" id="flash-product-description-full" data-sale-detail="description-full" hidden><?= store_escape($saleDescription) ?></p>
                        <button class="flash-product-description-toggle" type="button" aria-expanded="false" aria-controls="flash-product-description-full" data-sale-description-toggle<?= strlen($saleDescription) <= 180 ? ' hidden' : '' ?>>View Full Product Description</button>
                    </div>

                    <div class="flash-price-panel">
                        <div class="flash-price-line">
                            <strong data-sale-detail="price"><?= store_escape(store_format_price((float) $saleProduct['price'])) ?></strong>
                            <del data-sale-detail="compare-price"><?= store_escape(store_format_price((float) $saleProduct['compare_at_price'])) ?></del>
                            <?php $discountPercent = (int) round((1 - ((float) $saleProduct['price'] / (float) $saleProduct['compare_at_price'])) * 100); ?>
                            <span data-sale-detail="discount"><?= $discountPercent ?>% off</span>
                        </div>
                        <small data-sale-detail="sale-note">While supplies last</small>
                    </div>

                    <div class="flash-product-actions">
                        <div class="flash-stock-panel">
                            <div class="flash-stock-top">
                                <span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m7.5 4.27 9 5.15" />
                                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                        <path d="m3.3 7 8.7 5 8.7-5" />
                                        <path d="M12 22V12" />
                                    </svg>
                                    <span data-sale-detail="stock-text"><?= (int) $saleProduct['stock_quantity'] ?> units available</span>
                                </span>
                                <span data-sale-detail="stock-count"><?= (int) $saleProduct['stock_quantity'] ?> in stock</span>
                            </div>
                            <div class="flash-stock-track" data-sale-detail="stock-progress" role="progressbar" aria-label="Units available" aria-valuenow="<?= min(100, (int) $saleProduct['stock_quantity']) ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?= min(100, (int) $saleProduct['stock_quantity']) ?>%"></span></div>
                            <div class="flash-stock-bottom">
                                <span data-sale-detail="stock-note">Adding a live flash-sale item reserves stock for five minutes.</span>
                                <span data-sale-detail="availability"><?= (int) $saleProduct['stock_quantity'] > 0 ? 'Available now' : 'Currently sold out' ?></span>
                            </div>
                        </div>

                        <form action="cart" method="post" data-sale-detail="cart-form">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="variant_id" value="<?= (int) $saleProduct['variant_id'] ?>" data-sale-detail="variant-id">
                            <button class="flash-reserve-button" type="submit" name="action" value="add">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-basket" aria-hidden="true">
                                        <path d="m15 11-1 9" />
                                        <path d="m19 11-4-7" />
                                        <path d="M2 11h20" />
                                        <path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4" />
                                        <path d="M4.5 15.5h15" />
                                        <path d="m5 11 4-7" />
                                        <path d="m9 11 1 9" />
                                    </svg>
                                    Add to cart
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M5 12h14" />
                                        <path d="m12 5 7 7-7 7" />
                                    </svg>
                            </button>
                        </form>
                        <p data-sale-detail="cart-message" role="status" hidden></p>
                        <p class="flash-checkout-note">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                <path d="M3 6h18" />
                                <path d="M16 10a4 4 0 0 1-8 0" />
                            </svg>
                            Your cart is saved to this browser and can be linked to your account. Live flash-sale items are held for five minutes after being added.
                        </p>
                    </div>
                </section>
            </article>
            <?php else: ?>
                <p class="flash-empty" role="status">There are no discounted products available right now.</p>
            <?php endif; ?>

            <?php require __DIR__ . '/../app/views/components/flash-sale-inventory.php'; ?>

            <?php require __DIR__ . '/../app/views/components/flash-sale-mechanics.php'; ?>
        </main>
    </div>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>

    <?php $flashSaleScriptPath = __DIR__ . '/../assets/js/flash-sale.js'; ?>
    <script src="../assets/js/flash-sale.js?v=<?= filemtime($flashSaleScriptPath) ?>-<?= filesize($flashSaleScriptPath) ?>" defer></script>

    <?php
    $cartHref = 'cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
</body>

</html>
