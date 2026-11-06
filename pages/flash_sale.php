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
$heroDrop = null;
foreach (['live', 'starting', 'tomorrow', 'archive'] as $dropState) {
    foreach ($flashSaleProducts[$dropState] as $dropRow) {
        $dropVariantId = (int) ($dropRow['variant_id'] ?? 0);
        if ($dropVariantId > 0) {
            $dropRow['_drop_state'] = $dropState;
            $heroDrop = $dropRow;
            break 2;
        }
    }
}

if ($heroDrop !== null && (empty($heroDrop['talent_name']) || empty($heroDrop['talent_slug']))) {
    $talentStmt = $conn->prepare(
        'SELECT t.talent_name, t.talent_slug, t.talent_img,
                (
                    SELECT tg.group_name
                    FROM talent_group_members_tbl gm
                    INNER JOIN talent_group_tbl tg ON tg.group_id = gm.group_id
                    WHERE gm.talent_id = t.talent_id
                    ORDER BY tg.group_id ASC
                    LIMIT 1
                ) AS talent_group
         FROM product_talents_tbl pt
         INNER JOIN talent_tbl t ON t.talent_id = pt.talent_id
         WHERE pt.product_id = ?
         ORDER BY t.talent_id ASC
         LIMIT 1'
    );
    $flashProdId = (int) $heroDrop['product_id'];
    $talentStmt->bind_param('i', $flashProdId);
    $talentStmt->execute();
    $talentRow = $talentStmt->get_result()->fetch_assoc();
    $talentStmt->close();
    $heroDrop['talent_name'] = $talentRow['talent_name'] ?? $heroDrop['category_name'] ?? 'hololive production';
    $heroDrop['talent_slug'] = $talentRow['talent_slug'] ?? '';
    $heroDrop['talent_img'] = $talentRow['talent_img'] ?? '';
    $heroDrop['talent_group'] = $talentRow['talent_group'] ?? '';
}

$flashSaleProductImages = [];
$flashSaleProductIds = [];
foreach ($flashSaleProducts as $products) {
    foreach ($products as $product) {
        $flashSaleProductIds[] = (int) $product['product_id'];
    }
}
if ($heroDrop !== null) {
    $flashSaleProductIds[] = (int) $heroDrop['product_id'];
}
$flashSaleProductIds = array_values(array_unique(array_filter($flashSaleProductIds)));
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

$saleProduct = $heroDrop;
$saleSelection = $saleProduct
    ? store_flash_sale_build_selection_data(
        $saleProduct,
        (string) ($saleProduct['_drop_state'] ?? 'live'),
        $flashSaleProductImages[(int) $saleProduct['product_id']] ?? [],
        $flashSaleTimezone,
        '../'
    )
    : null;

if ($saleProduct && $saleSelection) {
    $saleGalleryImages = $saleSelection['images'];
    $heroVisualModes = [];
    $seenVisualUrls = [];

    if (!empty($saleSelection['standingVisual'])) {
        $heroVisualModes[] = [
            'image_url' => $saleSelection['standingVisual'],
            'alt_text' => $saleSelection['talent'] . ' Official Standing Visual',
            'mode_label' => 'Standing Pose',
        ];
        $seenVisualUrls[$saleSelection['standingVisual']] = true;
    }
    if (!empty($saleSelection['prVisualAlt']) && !isset($seenVisualUrls[$saleSelection['prVisualAlt']])) {
        $heroVisualModes[] = [
            'image_url' => $saleSelection['prVisualAlt'],
            'alt_text' => $saleSelection['talent'] . ' PR Costume Visual',
            'mode_label' => 'PR Costume',
        ];
        $seenVisualUrls[$saleSelection['prVisualAlt']] = true;
    }
    foreach ($saleGalleryImages as $merchIndex => $merchImg) {
        $merchUrl = (string) ($merchImg['image_url'] ?? '');
        if ($merchUrl !== '' && !isset($seenVisualUrls[$merchUrl])) {
            $heroVisualModes[] = [
                'image_url' => $merchUrl,
                'alt_text' => (string) ($merchImg['alt_text'] ?: $saleSelection['name']),
                'mode_label' => $merchIndex === 0 ? 'Merch Visual' : 'Merch View ' . ($merchIndex + 1),
            ];
            $seenVisualUrls[$merchUrl] = true;
        }
    }
    if ($heroVisualModes === []) {
        $heroVisualModes[] = [
            'image_url' => '../assets/images/logo.png',
            'alt_text' => $saleSelection['name'],
            'mode_label' => 'Official Visual',
        ];
    }

    $saleImageUrl = $heroVisualModes[0]['image_url'];
    $saleImageAlt = $heroVisualModes[0]['alt_text'];
    $merchFallbackUrl = $saleGalleryImages[0]['image_url'] ?? '../assets/images/logo.png';
    $flashStock = (int) $saleSelection['stock'];
    $flashCapacity = (int) $saleSelection['capacity'];
    $flashClaimedPercent = (int) $saleSelection['claimedPercent'];
    $discountPercent = (int) $saleSelection['discount'];
    $flashTalent = (string) $saleSelection['talent'];
    $saleDescription = (string) $saleSelection['description'];
    $flashDeadlineIso = $saleSelection['endsAt'] !== ''
        ? $saleSelection['endsAt']
        : (new DateTimeImmutable('now', $flashSaleTimezone))->setTime(23, 59, 59)->format(DateTimeInterface::ATOM);
    $flashProductUrl = (string) $saleSelection['productUrl'];
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

        <?php if ($saleProduct && $saleSelection): ?>
        <div
            class="flash-sale-hero-backdrop"
            style="--hero-theme: <?= store_escape($saleSelection['themeColor']) ?>; --hero-accent: <?= store_escape($saleSelection['accentColor']) ?>; --hero-glow: <?= store_escape($saleSelection['glowColor']) ?>;"
            <?= $saleSelection['state'] === 'live' ? '' : 'hidden' ?>
        >
            <section
                    id="flash-drop-spotlight"
                    class="flash-cyber-hero relative overflow-hidden w-full py-8 sm:py-10 lg:py-12 text-slate-100 rounded-none"
                    data-flash-drop-section
                    aria-labelledby="flash-product-title"
                >
                    <!-- Background Cyber Grid & Vignette -->
                    <div class="absolute inset-0 bg-cyber-grid opacity-30 pointer-events-none" aria-hidden="true"></div>
                    <div class="absolute inset-0 bg-radial-vignette pointer-events-none" aria-hidden="true"></div>

                    <!-- Ambient Talent Particle Orbs -->
                    <div
                        class="flash-hero-orb-primary absolute top-16 right-1/4 w-80 h-80 sm:w-96 sm:h-96 rounded-full blur-3xl opacity-20 pointer-events-none"
                        data-sale-detail="orb-primary"
                        style="background: var(--hero-theme);"
                        aria-hidden="true"
                    ></div>
                    <div
                        class="flash-hero-orb-secondary absolute bottom-8 left-8 w-64 h-64 sm:w-72 sm:h-72 rounded-full blur-3xl opacity-15 pointer-events-none"
                        data-sale-detail="orb-secondary"
                        style="background: var(--hero-accent);"
                        aria-hidden="true"
                    ></div>

                    <div class="flash-hero-edge-timer" data-countdown-edge role="timer" aria-label="Time remaining" aria-live="off">00:00:00</div>

                    <!-- Main Grid Container -->
                    <div class="flash-sale-hero-content landing-shell relative mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center z-10">

                        <!-- LEFT COLUMN: Deal Details, Countdown, Oshi Switcher & CTAs (7 cols on lg) -->
                        <div class="lg:col-span-7 flex flex-col space-y-5 min-w-0">

                            <!-- Top Brand Badges -->
                            <div class="flex flex-wrap items-center gap-2.5">
                                <div class="inline-flex items-center gap-2 bg-[#0c1424]/90 border border-cyan-500/30 px-3 py-1.5 rounded-none text-xs text-cyan-300 shadow-sm">
                                    <img
                                        src="https://hololivepro.com/wp-content/themes/hololive_production/images/rnew/common_logo_holopro_wt_rnew.svg"
                                        alt="hololive production"
                                        class="h-3.5 w-auto object-contain rounded-none"
                                        onerror="this.onerror=null;this.src='../assets/images/logo.png';"
                                    >
                                    <span class="w-1 h-1 rounded-none bg-cyan-400" aria-hidden="true"></span>
                                    <span class="font-bold tracking-wider font-outfit uppercase">2026 OFFICIAL FLASH SALE</span>
                                    <span class="w-1 h-1 rounded-none bg-cyan-400/60" aria-hidden="true"></span>
                                    <span id="flash-sale-live-count" class="text-[11px] font-semibold text-cyan-200"><?= count($flashSaleProducts['live']) ?> <?= count($flashSaleProducts['live']) === 1 ? 'drop' : 'drops' ?> live</span>
                                </div>

                                <div class="inline-flex items-center gap-1.5 bg-pink-500/20 text-pink-300 border border-pink-500/30 px-3 py-1.5 rounded-none text-xs font-black uppercase tracking-wider font-outfit">
                                    <svg class="w-3.5 h-3.5 text-pink-400 fill-pink-400 animate-pulse" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                                    <span data-sale-detail="hero-badge"><?= store_escape($saleSelection['badge']) ?></span>
                                </div>

                            </div>

                            <!-- Headline & Character Titles -->
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                    <span class="text-cyan-400 font-jp text-sm" data-sale-detail="jp-name"><?= store_escape($saleSelection['japaneseName']) ?></span>
                                    <span aria-hidden="true">&bull;</span>
                                    <span data-sale-detail="unit"><?= store_escape($saleSelection['unit']) ?></span>
                                    <span aria-hidden="true">&bull;</span>
                                    <span class="text-slate-200" data-sale-detail="talent"><?= store_escape($flashTalent) ?></span>
                                    <span aria-hidden="true">&middot;</span>
                                    <span data-sale-detail="category"><?= store_escape($saleSelection['category']) ?></span>
                                    <span data-sale-detail="variant-wrap"<?= empty($saleSelection['variantName']) ? ' hidden' : '' ?>>
                                        <span class="text-slate-600">/</span>
                                        <span data-sale-detail="variant"><?= store_escape($saleSelection['variantName']) ?></span>
                                    </span>
                                </div>

                                <h1
                                    id="flash-product-title"
                                    data-sale-detail="name"
                                    class="text-2xl sm:text-4xl lg:text-5xl font-black text-white font-outfit tracking-tight leading-none"
                                >
                                    <?= store_escape($saleSelection['name']) ?>
                                </h1>

                                <div class="flash-product-description-block pt-1 max-w-2xl">
                                    <div class="flash-product-description-preview-wrap">
                                        <p class="text-sm sm:text-base text-slate-300 font-normal leading-relaxed line-clamp-2" data-sale-detail="description-preview"><?= store_escape($saleSelection['description']) ?></p>
                                        <div class="flash-product-description-full p-3 rounded-none bg-[#0a0f1d]/90 border border-cyan-500/30 text-xs sm:text-sm text-slate-300 leading-relaxed max-h-40 overflow-y-auto" id="flash-product-description-full" data-sale-detail="description-full" hidden>
                                            <div class="description-full-text"><?= nl2br(store_escape($saleSelection['description'])) ?></div>
                                        </div>
                                    </div>
                                    <div class="flash-product-description-actions flex flex-wrap items-center gap-3 mt-1.5">
                                        <button class="inline-flex items-center gap-1 text-xs font-bold text-cyan-400 hover:text-cyan-300 cursor-pointer" type="button" aria-expanded="false" aria-controls="flash-product-description-full" data-sale-description-toggle>
                                            <span>Read full product details</span>
                                            <svg class="toggle-chevron w-3.5 h-3.5 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                        </button>
                                        <a href="<?= store_escape($flashProductUrl) ?>" data-sale-detail="product-link" class="text-xs font-semibold text-slate-400 hover:text-slate-200 transition-colors">
                                            Open dedicated store page &rarr;
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- High-Urgency Countdown & Live Stock Allocation -->
                            <div
                                class="w-full rounded-none"
                                aria-label="Flash drop countdown and allocation"
                                data-sale-countdown-wrap
                            >
                                <!-- Digits Grid -->
                                <div
                                    class="!mt-0 countdown-timer flash-countdown-clock grid grid-cols-5 gap-2 font-outfit"
                                    aria-live="off"
                                    data-countdown="<?= store_escape($flashDeadlineIso) ?>"
                                >
                                    <div class="flex flex-col items-center justify-center py-2 px-1 rounded-none bg-[#0c1424] shadow-inner">
                                        <strong class="text-lg sm:text-2xl font-black text-white tracking-wider" data-countdown-days>00</strong>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">DAYS</span>
                                    </div>
                                    <div class="flex flex-col items-center justify-center py-2 px-1 rounded-none bg-[#0c1424] shadow-inner">
                                        <strong class="text-lg sm:text-2xl font-black text-white tracking-wider" data-countdown-hours>00</strong>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">HOURS</span>
                                    </div>
                                    <div class="flex flex-col items-center justify-center py-2 px-1 rounded-none bg-[#0c1424] shadow-inner">
                                        <strong class="text-lg sm:text-2xl font-black text-white tracking-wider" data-countdown-minutes>00</strong>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">MINUTES</span>
                                    </div>
                                    <div class="flex flex-col items-center justify-center py-2 px-1 rounded-none bg-[#0c1424] shadow-inner">
                                        <strong class="text-lg sm:text-2xl font-black text-cyan-300 tracking-wider" data-countdown-seconds>00</strong>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">SECONDS</span>
                                    </div>
                                    <div class="flex flex-col items-center justify-center py-2 px-1 rounded-none bg-[#0c1424] shadow-inner">
                                        <strong class="text-lg sm:text-2xl font-black text-pink-400 tracking-wider font-mono" data-countdown-ms>74</strong>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">MS</span>
                                    </div>
                                </div>

                                <!-- Stock Progress Bar -->
                                <div class="flash-stock">
                                    <div class="flex justify-between items-center text-xs mb-1.5 font-medium">
                                        <span data-sale-detail="stock-status" class="flash-stock-status text-slate-300 flex items-center gap-1.5<?= $flashStock > 0 ? '' : ' is-unavailable' ?>" role="status">
                                            <strong class="text-yellow-400 font-bold font-outfit" data-sale-detail="stock-claimed-label"><?= $flashStock > 0 ? $flashStock . ' units left' : '0 units left' ?></strong>
                                            <span class="text-slate-500" data-sale-detail="stock-capacity">/ <?= $flashCapacity ?> total</span>
                                            <span class="sr-only" data-sale-detail="stock-text"><?= $flashStock > 0 ? 'Drop quantity' : 'Currently sold out' ?></span>
                                        </span>
                                        <span class="text-xs font-bold text-cyan-400 font-outfit" data-sale-detail="stock-percent-label">
                                            <?= $flashClaimedPercent ?>% CLAIMED
                                        </span>
                                    </div>

                                    <div class="flash-stock-track w-full h-2 bg-[#0c1424] rounded-none overflow-hidden" role="progressbar" aria-label="Flash drop availability claimed" aria-valuenow="<?= $flashClaimedPercent ?>" aria-valuemin="0" aria-valuemax="100">
                                        <span
                                            class="block h-full rounded-none transition-all duration-500 relative"
                                            data-sale-detail="stock-bar"
                                            style="width: <?= $flashClaimedPercent ?>%; background: linear-gradient(90deg, var(--hero-accent) 0%, var(--hero-theme) 100%);"
                                        ></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Cyber-Holographic Pricing HUD -->
                            <div class="rounded-none flex flex-col sm:flex-row sm:items-center justify-between gap-4" data-sale-price-wrap>
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col">
                                        <div class="flex items-baseline gap-2.5">
                                            <span class="text-3xl sm:text-4xl font-black text-cyan-400 font-outfit tracking-tight" data-sale-detail="price">
                                                <?= store_escape($saleSelection['salePriceFormatted']) ?>
                                            </span>
                                            <span class="text-base text-slate-500 line-through font-medium" data-sale-detail="original-price">
                                                <?= store_escape($saleSelection['originalPriceFormatted']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Action CTAs Row -->
                            <div class="flash-cta-row flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                <!-- Primary Claim Form -->
                                <form
                                    action="cart"
                                    method="post"
                                    class="flex-1 flex"
                                    data-sale-detail="cart-form"<?= $flashStock > 0 ? '' : ' hidden' ?>
                                    data-toast-message="<?= store_escape($saleSelection['name'] . ' added to your bag') ?>"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                    <input type="hidden" name="variant_id" value="<?= (int) $saleSelection['variantId'] ?>" data-sale-detail="variant-id">
                                    <button
                                        class="w-full py-4 px-6 rounded-none text-white font-black text-sm sm:text-base flex items-center justify-center gap-2.5 transition-all cursor-pointer group relative overflow-hidden border-0"
                                        type="submit"
                                        name="action"
                                        value="add"
                                        data-flash-buy-btn
                                        style="background: linear-gradient(90deg, var(--hero-accent) 0%, var(--hero-theme) 100%);"
                                    >
                                        <span class="absolute inset-0 animate-holo-shimmer pointer-events-none" aria-hidden="true"></span>
                                        <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                                        <span class="tracking-wide font-outfit uppercase">CLAIM FLASH DEAL NOW</span>
                                        <svg class="w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                </form>

                                <button
                                    class="flex-1 py-4 px-6 rounded-none bg-slate-800/90 text-slate-400 font-black text-sm sm:text-base border border-slate-700 flex items-center justify-center gap-2 cursor-not-allowed font-outfit uppercase"
                                    type="button"
                                    data-sale-detail="cart-disabled"
                                    disabled<?= $flashStock > 0 ? ' hidden' : '' ?>
                                >
                                    Sold out
                                </button>

                                <p data-sale-detail="cart-message" role="status" hidden></p>

                            </div>

                        </div>

                        <!-- RIGHT COLUMN: Official High-Res Standing Talent Character & Merch Visual (5 cols on lg) -->
                        <section class="flash-gallery lg:col-span-5 relative flex flex-col items-center justify-center min-h-[460px] sm:min-h-[540px] lg:min-h-[600px]" aria-label="Product and character gallery">

                            <!-- Vertical Japanese Character Name Background Watermark -->
                            <div class="absolute right-0 top-1/2 -translate-y-1/2 select-none pointer-events-none text-right" aria-hidden="true">
                                <span
                                    class="text-6xl sm:text-7xl lg:text-8xl font-black font-jp tracking-tighter block leading-none"
                                    data-sale-detail="jp-watermark"
                                    style="color: color-mix(in srgb, var(--hero-theme) 55%, #000000); writing-mode: vertical-rl;"
                                >
                                    <?= store_escape($saleSelection['japaneseName']) ?>
                                </span>
                            </div>

                            <!-- Floating Main Visual -->
                            <div class="relative z-10 w-full max-w-[320px] sm:max-w-[390px] lg:max-w-[430px] flex items-center justify-center animate-float-subtle mt-8">
                                <a href="<?= store_escape($flashProductUrl) ?>" data-sale-detail="product-link" aria-label="View <?= store_escape($saleSelection['name']) ?>" class="block">
                                    <img
                                        class="flash-product-main-image w-auto h-[340px] sm:h-[420px] lg:h-[460px] object-contain transition-all duration-500 rounded-none"
                                        src="<?= store_escape($saleImageUrl) ?>"
                                        alt="<?= store_escape($saleImageAlt) ?>"
                                        data-fallback-src="<?= store_escape($merchFallbackUrl) ?>"
                                        fetchpriority="high"
                                        onerror="if(this.dataset.fallbackSrc && this.src !== this.dataset.fallbackSrc){this.src=this.dataset.fallbackSrc;}"
                                    >
                                </a>
                            </div>

                            <!-- Minimal Cyber Ground Line Indicator -->
                            <div class="relative w-64 sm:w-80 h-0.5 bg-gradient-to-r from-transparent via-cyan-400/60 to-transparent my-1 z-0 pointer-events-none" aria-hidden="true"></div>

                            <!-- Gallery Thumbnail Strip (Character Pose + Official Merch Images) -->
                            <div class="product-gallery-carousel relative z-20 mt-2 flex items-center gap-2" data-gallery-carousel>
                                <button class="product-gallery-arrow w-7 h-7 rounded-none bg-[#0c1424] border border-slate-700 text-cyan-300 hover:bg-cyan-500 hover:text-slate-950 flex items-center justify-center cursor-pointer transition-colors" type="button" data-gallery-previous aria-label="Show previous image"<?= count($heroVisualModes) < 2 ? ' hidden' : '' ?>>&lt;</button>
                                <div class="product-gallery-thumbnails flex items-center gap-2 overflow-x-auto max-w-[260px] sm:max-w-[320px] py-1 scrollbar-none" aria-label="Product and character visuals" role="group">
                                    <?php foreach ($heroVisualModes as $imageIndex => $image): ?>
                                        <button
                                            class="product-gallery-thumbnail w-11 h-11 rounded-none border p-1 bg-slate-950/85 cursor-pointer transition-all shrink-0 <?= $imageIndex === 0 ? 'is-active border-cyan-400 shadow-[0_0_10px_rgba(0,195,255,0.4)]' : 'border-slate-800 opacity-75 hover:opacity-100' ?>"
                                            type="button"
                                            data-gallery-image
                                            data-image-src="<?= store_escape($image['image_url']) ?>"
                                            data-image-alt="<?= store_escape($image['alt_text']) ?>"
                                            aria-label="Show <?= store_escape($image['mode_label']) ?>"
                                            aria-pressed="<?= $imageIndex === 0 ? 'true' : 'false' ?>"
                                        >
                                            <img src="<?= store_escape($image['image_url']) ?>" alt="" class="w-full h-full object-contain rounded-none" loading="lazy" decoding="async" onerror="this.closest('button').hidden=true;">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <button class="product-gallery-arrow w-7 h-7 rounded-none bg-[#0c1424] border border-slate-700 text-cyan-300 hover:bg-cyan-500 hover:text-slate-950 flex items-center justify-center cursor-pointer transition-colors" type="button" data-gallery-next aria-label="Show next image"<?= count($heroVisualModes) < 2 ? ' hidden' : '' ?>>&gt;</button>
                            </div>

                        </section>
                    </div>
            </section>
        </div>
        <?php endif; ?>

        <main class="flash-sale-main landing-shell">
            <?php if (!$flashSaleSchedulingAvailable): ?>
                <p class="flash-setup-notice" role="status">Flash sale scheduling is temporarily unavailable, so no drops are being shown. The database must include the <code>flash_sale_tbl</code> table before an administrator can schedule drops.</p>
            <?php endif; ?>

            <?php if (!$saleProduct || !$saleSelection): ?>
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
