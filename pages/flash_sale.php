<?php
require_once __DIR__ . '/../app/store.php';
$saleProducts = store_product_rows(
    $conn,
    'AND v.compare_at_price IS NOT NULL AND v.compare_at_price > v.price',
    'p.product_id',
    1
);
$saleProduct = $saleProducts[0] ?? null;
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
                <span><i aria-hidden="true"></i> Vault state: live (stage 04)</span>
                <span>Protocol v4.12: anti-bot hardware attestation active</span>
                <span class="flash-status-right"><span>◎ CF-turnstile: validated</span><span>◷ Latency: 18ms (Tokyo-HQ)</span></span>
            </div>

            <section class="flash-sale-heading" aria-labelledby="flash-sale-title">
                <div>
                    <div class="flash-heading-meta">
                        <span class="flash-mode-pill">Flash drop mode #7401</span>
                        <span>[01 per-household enforced]</span>
                    </div>
                    <h1 id="flash-sale-title">Hyper-drop vault // Live flash sale</h1>
                </div>
                <div class="flash-countdown">
                    <span class="flash-countdown-label"><span aria-hidden="true">◷</span> Allocation window closes in:</span>
                    <div class="flash-countdown-clock" aria-label="2 days, 14 hours, 0 minutes, 19 seconds remaining">
                        <span>02</span><b>:</b><span>14</span><b>:</b><span>00</span><b>:</b><small>19</small>
                    </div>
                </div>
            </section>

            <?php if ($saleProduct): ?>
            <article class="flash-sale-card">
                <section class="flash-gallery" aria-label="Product gallery">
                    <div class="flash-product-image">
                        <img src="<?= store_escape(store_asset_url($saleProduct['image_url'] ?? 'assets/images/logo.png', '../')) ?>" alt="<?= store_escape($saleProduct['alt_text'] ?? $saleProduct['product_name']) ?>">
                        <div class="flash-image-badges">
                            <span><?= (int) $saleProduct['stock_quantity'] ?> units available</span>
                            <span><b aria-hidden="true">✓</b> Verified osh(i) asset</span>
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
                            <strong><?= store_escape($saleProduct['currency']) ?> <?= number_format((float) $saleProduct['price'], 2) ?></strong>
                            <del><?= store_escape($saleProduct['currency']) ?> <?= number_format((float) $saleProduct['compare_at_price'], 2) ?></del>
                            <?php $discountPercent = (int) round((1 - ((float) $saleProduct['price'] / (float) $saleProduct['compare_at_price'])) * 100); ?>
                            <span><?= $discountPercent ?>% off</span>
                        </div>
                        <small>While supplies last</small>
                    </div>

                    <div class="flash-stock-panel">
                        <div class="flash-stock-top">
                            <span><b aria-hidden="true">♨</b> <?= (int) $saleProduct['stock_quantity'] ?> units available</span>
                            <span><?= (int) $saleProduct['stock_quantity'] ?> in stock</span>
                        </div>
                        <div class="flash-stock-track" role="progressbar" aria-label="Units available" aria-valuenow="<?= min(100, (int) $saleProduct['stock_quantity']) ?>" aria-valuemin="0" aria-valuemax="100"><span></span></div>
                        <div class="flash-stock-bottom">
                            <span>Stock is confirmed when added to your cart.</span>
                            <span><?= (int) $saleProduct['stock_quantity'] > 0 ? 'Available now' : 'Currently sold out' ?></span>
                        </div>
                    </div>

                    <?php if ((int) $saleProduct['stock_quantity'] > 0): ?>
                        <form action="cart.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="variant_id" value="<?= (int) $saleProduct['variant_id'] ?>">
                            <button class="flash-reserve-button" type="submit"><span aria-hidden="true">♙</span> Add to cart <span aria-hidden="true">→</span></button>
                        </form>
                    <?php else: ?>
                        <p>Sorry, this product is sold out.</p>
                    <?php endif; ?>
                    <p class="flash-checkout-note"><span aria-hidden="true">ϟ</span> Your cart is saved to this browser and can be linked to your account.</p>
                </section>
            </article>
            <?php else: ?>
                <p class="flash-empty" role="status">There are no discounted products available right now.</p>
            <?php endif; ?>

            <section class="flash-telemetry" aria-labelledby="flash-telemetry-title">
                <div class="flash-telemetry-heading">
                    <h2 id="flash-telemetry-title"><span aria-hidden="true"></span> Active queue &amp; anti-snipe telemetry engine</h2>
                    <span>System clock: <?= date('Y-m-d\TH:i:sP') ?></span>
                </div>
                <div class="flash-telemetry-grid">
                    <article class="flash-telemetry-card">
                        <div class="flash-telemetry-label"><span>Gatekeeper pass</span><span aria-hidden="true">♢</span></div>
                        <strong>Passed / verified</strong>
                        <p>Hardware attestation cleared. Zero captcha required during lock.</p>
                        <span class="flash-telemetry-badge">Hardware encrypted</span>
                    </article>

                    <article class="flash-telemetry-card">
                        <div class="flash-telemetry-label"><span>Your cart lock monitor</span><span class="flash-telemetry-icon-red" aria-hidden="true">◷</span></div>
                        <strong class="flash-telemetry-countdown">01:59 <small>remaining</small></strong>
                        <p>Active cart reservation lock protects against out-of-stock slips.</p>
                        <div class="flash-telemetry-progress" role="progressbar" aria-label="Cart lock time remaining" aria-valuenow="66" aria-valuemin="0" aria-valuemax="100"><span></span></div>
                    </article>

                    <article class="flash-telemetry-card">
                        <div class="flash-telemetry-label"><span>Standby queue position</span><span aria-hidden="true">♙</span></div>
                        <strong>#14 <small>est. wait: 12 sec</small></strong>
                        <p>Dynamic pool reallocates unsold slots in real time.</p>
                        <span class="flash-telemetry-footnote"><i aria-hidden="true"></i> 42 releases in past 60s</span>
                    </article>

                    <article class="flash-telemetry-card">
                        <div class="flash-telemetry-label"><span>Fulfillment SLA</span><span aria-hidden="true">♢</span></div>
                        <strong>100% secured</strong>
                        <p>Allocation locked once button clicked. No oversell guarantee.</p>
                        <span class="flash-telemetry-badge">ISO 27001 vault</span>
                    </article>
                </div>
            </section>
        </main>
    </div>
    <script>
        const galleryImage = document.querySelector(".flash-product-image > img");
        const galleryThumbnails = document.querySelectorAll(".flash-thumbnail");

        galleryThumbnails.forEach((thumbnail) => {
            thumbnail.addEventListener("click", () => {
                galleryImage.src = thumbnail.dataset.gallerySrc;
                galleryImage.alt = thumbnail.dataset.galleryAlt;

                galleryThumbnails.forEach((item) => {
                    const isSelected = item === thumbnail;
                    item.classList.toggle("is-selected", isSelected);
                    item.setAttribute("aria-pressed", String(isSelected));
                });
            });
        });
    </script>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
</body>

</html>
