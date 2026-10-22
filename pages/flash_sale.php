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
    : [
        'live' => store_product_rows(
            $conn,
            'AND v.compare_at_price IS NOT NULL AND v.compare_at_price > v.price AND ' . store_available_stock_sql() . ' > 0',
            'p.product_id DESC'
        ),
        'starting' => [],
        'tomorrow' => [],
        'archive' => [],
    ];
$saleProduct = $flashSaleProducts['live'][0] ?? null;
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
            <div class="flash-status-bar" aria-label="Flash sale system status">
                <span><i aria-hidden="true"></i> Flash sales: <?= count($flashSaleProducts['live']) ?> live drops</span>
                <span>5-minute holds on live flash-sale items; stock and CSRF checked</span>
                <span class="flash-status-right">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        Product availability is live
                    </span>
                    <?php if (store_has_role('admin')): ?><a href="flash-sale-admin">Manage drops</a><?php endif; ?>
                </span>
            </div>
            <?php if (!$flashSaleSchedulingAvailable): ?>
                <p class="flash-setup-notice" role="status">Scheduled drop filters are temporarily unavailable. Showing all discounted, in-stock products. The Supabase database must include the <code>flash_sale_tbl</code> table from <code>Database/holoverse.sql</code>.</p>
            <?php endif; ?>

            <section class="flash-sale-heading" aria-labelledby="flash-sale-title">
                <div>
                    <div class="flash-heading-meta">
                        <span class="flash-mode-pill">Limited-time offers</span>
                        <span>Official merchandise · while supplies last</span>
                    </div>
                    <h1 id="flash-sale-title">Flash Sales</h1>
                    <p class="flash-sale-subtitle">Limited-time prices on official hololive merchandise.</p>
                </div>
                <div class="flash-countdown">
                    <?php if ($saleProduct && !empty($saleProduct['ends_at'])): ?>
                        <span class="flash-countdown-label">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 6v6l4 2" />
                            </svg>
                            Drop ends in:
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
            <article class="flash-sale-card">
                <section class="flash-gallery" aria-label="Product gallery">
                    <div class="flash-product-image">
                        <img src="<?= store_escape(store_asset_url($saleProduct['image_url'] ?? 'assets/images/logo.png', '../')) ?>" alt="<?= store_escape($saleProduct['alt_text'] ?? $saleProduct['product_name']) ?>">
                        <div class="flash-image-badges">
                            <span><?= (int) $saleProduct['stock_quantity'] ?> units available</span>
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 10c0 5-8 11-8 11s-8-6-8-11a8 8 0 1 1 16 0Z" />
                                    <circle cx="12" cy="10" r="3" />
                                </svg>
                                <?= store_escape($saleProduct['category_name']) ?>
                            </span>
                        </div>
                        <div class="flash-image-caption">
                            <span><?= store_escape($saleProduct['variant_name']) ?></span>
                            <span><?= store_escape($saleProduct['category_name']) ?></span>
                            <span>Official merchandise</span>
                        </div>
                    </div>
                    <div class="flash-thumbnails" aria-label="More product images">
                        <button class="flash-thumbnail is-selected" type="button" data-gallery-src="<?= store_escape(store_asset_url($saleProduct['image_url'] ?? 'assets/images/logo.png', '../')) ?>" data-gallery-alt="<?= store_escape($saleProduct['alt_text'] ?? $saleProduct['product_name']) ?>" aria-label="Show product image" aria-pressed="true">
                            <img src="<?= store_escape(store_asset_url($saleProduct['image_url'] ?? 'assets/images/logo.png', '../')) ?>" alt="">
                        </button>
                    </div>
                </section>

                <section class="flash-product-details" aria-labelledby="flash-product-title">
                    <div class="flash-breadcrumb"><?= store_escape($saleProduct['category_name']) ?> / <?= store_escape($saleProduct['variant_name']) ?></div>
                    <h2 id="flash-product-title"><?= store_escape($saleProduct['product_name']) ?></h2>
                    <p class="flash-product-description"><?= store_escape($saleProduct['description'] ?? '') ?></p>

                    <div class="flash-price-panel">
                        <div class="flash-price-line">
                            <strong><?= store_escape(store_format_price((float) $saleProduct['price'])) ?></strong>
                            <del><?= store_escape(store_format_price((float) $saleProduct['compare_at_price'])) ?></del>
                            <?php $discountPercent = (int) round((1 - ((float) $saleProduct['price'] / (float) $saleProduct['compare_at_price'])) * 100); ?>
                            <span><?= $discountPercent ?>% off</span>
                        </div>
                        <small>While supplies last</small>
                    </div>

                    <div class="flash-stock-panel">
                        <div class="flash-stock-top">
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m7.5 4.27 9 5.15" />
                                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                    <path d="m3.3 7 8.7 5 8.7-5" />
                                    <path d="M12 22V12" />
                                </svg>
                                <?= (int) $saleProduct['stock_quantity'] ?> units available
                            </span>
                            <span><?= (int) $saleProduct['stock_quantity'] ?> in stock</span>
                        </div>
                        <div class="flash-stock-track" role="progressbar" aria-label="Units available" aria-valuenow="<?= min(100, (int) $saleProduct['stock_quantity']) ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?= min(100, (int) $saleProduct['stock_quantity']) ?>%"></span></div>
                        <div class="flash-stock-bottom">
                            <span>Adding a live flash-sale item reserves stock for five minutes.</span>
                            <span><?= (int) $saleProduct['stock_quantity'] > 0 ? 'Available now' : 'Currently sold out' ?></span>
                        </div>
                    </div>

                    <?php if ((int) $saleProduct['stock_quantity'] > 0): ?>
                        <form action="cart" method="post">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="variant_id" value="<?= (int) $saleProduct['variant_id'] ?>">
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
                    <?php else: ?>
                        <p>Sorry, this product is sold out.</p>
                    <?php endif; ?>
                    <p class="flash-checkout-note">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                        Your cart is saved to this browser and can be linked to your account. Live flash-sale items are held for five minutes after being added.
                    </p>
                </section>
            </article>
            <?php else: ?>
                <p class="flash-empty" role="status">There are no discounted products available right now.</p>
            <?php endif; ?>

            <?php require __DIR__ . '/../app/views/components/flash-sale-telemetry.php'; ?>

            <?php require __DIR__ . '/../app/views/components/flash-sale-inventory.php'; ?>

            <?php require __DIR__ . '/../app/views/components/flash-sale-mechanics.php'; ?>
        </main>
    </div>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>

    <script src="../assets/js/flash-sale.js" defer></script>

    <?php
    $cartHref = 'cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
</body>

</html>
