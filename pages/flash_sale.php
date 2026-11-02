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

// Select spotlight product: live drop first, or fallback from discounted catalog
$saleProduct = $flashSaleProducts['live'][0] ?? null;
$isLiveScheduledFlash = $saleProduct !== null;

if ($saleProduct === null) {
    $discountedRows = store_product_rows(
        $conn,
        'AND v.compare_at_price IS NOT NULL AND v.compare_at_price > v.price AND v.stock_quantity > 0',
        'v.compare_at_price - v.price DESC, p.product_id DESC',
        1
    );
    if ($discountedRows !== []) {
        $saleProduct = $discountedRows[0];
    } else {
        $fallbackRows = store_catalog_product_rows($conn, limit: 1);
        $saleProduct = $fallbackRows[0] ?? null;
    }
}

if ($saleProduct !== null && empty($saleProduct['talent_name'])) {
    $talentStmt = $conn->prepare(
        'SELECT t.talent_name
         FROM product_talents_tbl pt
         INNER JOIN talent_tbl t ON t.talent_id = pt.talent_id
         WHERE pt.product_id = ?
         ORDER BY t.talent_id ASC
         LIMIT 1'
    );
    $flashProdId = (int) $saleProduct['product_id'];
    $talentStmt->bind_param('i', $flashProdId);
    $talentStmt->execute();
    $talentRow = $talentStmt->get_result()->fetch_assoc();
    $talentStmt->close();
    $saleProduct['talent_name'] = $talentRow['talent_name'] ?? $saleProduct['category_name'] ?? 'hololive production';
}

$saleGalleryImages = [];
if ($saleProduct) {
    $saleProductId = (int) $saleProduct['product_id'];
    if (isset($flashSaleProductImages[$saleProductId])) {
        $saleGalleryImages = $flashSaleProductImages[$saleProductId];
    } else {
        $imgStmt = $conn->prepare(
            'SELECT image_url, alt_text FROM product_image_tbl WHERE product_id = ? ORDER BY sort_order, image_id'
        );
        $imgStmt->bind_param('i', $saleProductId);
        $imgStmt->execute();
        $loadedImages = $imgStmt->get_result()->fetch_all(PDO::FETCH_ASSOC);
        $imgStmt->close();
        foreach ($loadedImages as $img) {
            $saleGalleryImages[] = [
                'image_url' => store_asset_url((string) ($img['image_url'] ?? 'assets/images/logo.png'), '../'),
                'alt_text' => $img['alt_text'] ?? '',
            ];
        }
    }
}

if ($saleProduct) {
    $saleGalleryImages = $saleGalleryImages ?: [[
        'image_url' => store_asset_url($saleProduct['image_url'] ?? 'assets/images/logo.png', '../'),
        'alt_text' => $saleProduct['alt_text'] ?? $saleProduct['product_name'],
    ]];
    $saleImageUrl = $saleGalleryImages[0]['image_url'];
    $saleImageAlt = $saleGalleryImages[0]['alt_text'] ?: $saleProduct['product_name'];
    $flashStock = max(0, (int) ($saleProduct['stock_quantity'] ?? 0));
    $flashCapacity = max(24, $flashStock + 6);
    $flashClaimedPercent = min(100, max(12, (int) round((($flashCapacity - $flashStock) / $flashCapacity) * 100)));
    $flashPrice = (float) ($saleProduct['price'] ?? 0);
    $flashComparePrice = !empty($saleProduct['compare_at_price']) && (float) $saleProduct['compare_at_price'] > $flashPrice
        ? (float) $saleProduct['compare_at_price']
        : round($flashPrice * 1.2, 2);
    $discountPercent = $flashComparePrice > 0 ? (int) round((1 - ($flashPrice / $flashComparePrice)) * 100) : 0;
    $flashTalent = (string) ($saleProduct['talent_name'] ?: ($saleProduct['category_name'] ?? 'hololive production'));
    $saleDescription = (string) ($saleProduct['description'] ?? '');
    $flashDeadlineIso = !empty($saleProduct['ends_at'])
        ? (new DateTimeImmutable($saleProduct['ends_at'], $flashSaleTimezone))->format(DateTimeInterface::ATOM)
        : (new DateTimeImmutable('now', $flashSaleTimezone))->setTime(23, 59, 59)->format(DateTimeInterface::ATOM);
    $flashProductUrl = store_product_url((string) $saleProduct['product_name']);
}

$pageTitle = 'Flash Drops | HoloVerse Official Shop';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="home-landing flash-sale-page">
    <div class="page-shell info-page flash-sale-page home-landing">
        <?php
        $activePage = 'flash_sale';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="flash-sale-main landing-shell">
            <?php if (!$flashSaleSchedulingAvailable): ?>
                <p class="flash-setup-notice" role="status">Flash sale scheduling is temporarily unavailable, so no drops are being shown. The database must include the <code>flash_sale_tbl</code> table before an administrator can schedule drops.</p>
            <?php endif; ?>

            <section class="section-heading flash-page-heading" aria-labelledby="flash-sale-title">
                <div>
                    <p class="eyebrow">Limited-time offers &middot; Official hololive production merchandise</p>
                    <h1 id="flash-sale-title">Flash Drops</h1>
                    <p class="flash-checkout-note">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                        Your bag is saved to this browser and can be linked to your account. Live flash drops are held for five minutes after being added.
                    </p>
                </div>

                <div class="section-heading-aside">
                    <span class="all-products-count" id="flash-sale-live-count"><?= count($flashSaleProducts['live']) ?> <?= count($flashSaleProducts['live']) === 1 ? 'drop' : 'drops' ?> live</span>
                    <nav class="products-promo-links" aria-label="Shop product collections">
                        <a href="../products">
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                            <span>All products</span>
                        </a>
                    </nav>
                </div>
            </section>

            <?php if ($saleProduct): ?>
                <article class="flash-spotlight-card flash-drop-section" id="flash-drop-spotlight" data-flash-drop-section>
                    <div class="flash-drop-layout">
                        <div class="flash-copy">
                            <div class="flash-eyebrow">
                                <span class="live-dot" aria-hidden="true"></span>
                                <span data-sale-detail="state-label"><?= $isLiveScheduledFlash ? 'Live flash sale drop' : 'Limited-time drop' ?></span>
                            </div>

                            <p class="eyebrow">
                                <span data-sale-detail="talent"><?= store_escape($flashTalent) ?></span> &middot;
                                <span data-sale-detail="category"><?= store_escape($saleProduct['category_name']) ?></span>
                                <?php if (!empty($saleProduct['variant_name'])): ?>
                                    <span class="eyebrow-sep">/</span>
                                    <span data-sale-detail="variant"><?= store_escape($saleProduct['variant_name']) ?></span>
                                <?php endif; ?>
                            </p>

                            <h2 id="flash-product-title" data-sale-detail="name"><?= store_escape($saleProduct['product_name']) ?></h2>

                            <div class="flash-product-description-block">
                                <p class="flash-description" data-sale-detail="description-preview"><?= store_escape($saleDescription) ?></p>
                                <div class="flash-product-description-full" id="flash-product-description-full" data-sale-detail="description-full" hidden>
                                    <div class="description-full-text"><?= nl2br(store_escape($saleDescription)) ?></div>
                                </div>
                                <button class="flash-product-description-toggle" type="button" aria-expanded="false" aria-controls="flash-product-description-full" data-sale-description-toggle<?= strlen($saleDescription) <= 160 ? ' hidden' : '' ?>>
                                    <span>Read full product details</span>
                                    <svg class="toggle-chevron" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                                </button>
                            </div>

                            <div class="countdown-block" aria-label="Flash drop countdown" data-sale-countdown-wrap<?= empty($flashDeadlineIso) ? ' hidden' : '' ?>>
                                <div class="countdown-heading">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <span data-sale-countdown-text>Drop window ends in</span>
                                </div>
                                <div class="countdown-timer flash-countdown-clock" aria-live="off" data-countdown="<?= store_escape($flashDeadlineIso) ?>">
                                    <div class="countdown-unit"><strong data-countdown-days>00</strong><span>days</span></div>
                                    <span class="countdown-colon">:</span>
                                    <div class="countdown-unit"><strong data-countdown-hours>00</strong><span>hours</span></div>
                                    <span class="countdown-colon">:</span>
                                    <div class="countdown-unit"><strong data-countdown-minutes>00</strong><span>minutes</span></div>
                                    <span class="countdown-colon">:</span>
                                    <div class="countdown-unit"><strong data-countdown-seconds>00</strong><span>seconds</span></div>
                                </div>
                                <p class="countdown-footnote" data-sale-detail="countdown-footnote">Limited-time allocation while supplies last</p>
                            </div>

                            <div class="flash-stock">
                                <div class="flash-stock-heading">
                                    <span data-sale-detail="stock-status" class="flash-stock-status<?= $flashStock > 0 ? '' : ' is-unavailable' ?>" role="status">
                                        <span data-sale-detail="stock-text"><?= $flashStock > 0 ? 'Drop quantity' : 'Currently sold out' ?></span>
                                    </span>
                                    <strong data-sale-detail="stock-claimed-label"><?= $flashStock > 0 ? $flashStock . ' left' : '0 left' ?></strong>
                                </div>
                                <div class="flash-stock-track" role="progressbar" aria-label="Flash drop availability claimed" aria-valuenow="<?= $flashClaimedPercent ?>" aria-valuemin="0" aria-valuemax="100">
                                    <span data-sale-detail="stock-bar" style="width: <?= $flashClaimedPercent ?>%;"></span>
                                </div>
                            </div>

                            <div class="flash-cta-row">
                                <form action="cart" method="post" data-sale-detail="cart-form"<?= $flashStock > 0 ? '' : ' hidden' ?> data-toast-message="<?= store_escape($saleProduct['product_name'] . ' added to your bag') ?>">
                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                    <input type="hidden" name="variant_id" value="<?= (int) $saleProduct['variant_id'] ?>" data-sale-detail="variant-id">
                                    <button class="button-primary flash-buy-button" type="submit" name="action" value="add" data-flash-buy-btn>
                                        <span>Get the drop</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </button>
                                </form>
                                <button class="button-primary flash-buy-button" type="button" data-sale-detail="cart-disabled" disabled<?= $flashStock > 0 ? ' hidden' : '' ?>>
                                    Sold out
                                </button>
                                <p data-sale-detail="cart-message" role="status" hidden></p>

                                <button class="drop-reminder" type="button" data-drop-reminder-btn>
                                    <svg data-reminder-icon-bell xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                                    <svg data-reminder-icon-check hidden xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    <span data-reminder-label>Remind me</span>
                                </button>
                            </div>

                            <p class="flash-prototype-note">
                                Flash price <strong data-sale-detail="price"><?= store_escape(store_format_price($flashPrice)) ?></strong>
                                <del data-sale-detail="compare-price"<?= $flashComparePrice > $flashPrice ? '' : ' hidden' ?>><?= store_escape(store_format_price($flashComparePrice)) ?></del>
                                <span class="flash-discount-tag" data-sale-detail="discount"><?= $discountPercent ?>% off</span>
                                <span class="flash-prototype-sep">&middot;</span>
                                <a class="flash-product-link" href="<?= store_escape($flashProductUrl) ?>" data-sale-detail="product-link">View product page</a>
                            </p>
                        </div>

                        <section class="flash-gallery flash-artwork" aria-label="Product gallery">
                            <a href="<?= store_escape($flashProductUrl) ?>" data-sale-detail="product-link" aria-label="View <?= store_escape((string) $saleProduct['product_name']) ?>">
                                <img class="flash-product-main-image" src="<?= store_escape($saleImageUrl) ?>" alt="<?= store_escape($saleImageAlt) ?>" fetchpriority="high">
                            </a>
                            <div class="flash-artwork-caption">
                                <span data-sale-detail="stock-badge">01 / LIMITED RELEASE</span>
                                <span data-sale-detail="variant-caption">OFFICIAL CHARACTER ARTWORK</span>
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
                    </div>
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
