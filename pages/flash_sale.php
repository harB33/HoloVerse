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
$heroDrops = [];
$seenHeroVariants = [];
foreach ($flashSaleProducts['live'] as $liveRow) {
    $liveVariantId = (int) ($liveRow['variant_id'] ?? 0);
    if ($liveVariantId > 0 && !isset($seenHeroVariants[$liveVariantId])) {
        $liveRow['_drop_state'] = 'live';
        $heroDrops[] = $liveRow;
        $seenHeroVariants[$liveVariantId] = true;
    }
}

$isLiveScheduledFlash = $heroDrops !== [];

if (count($heroDrops) < 7) {
    $fallbackRows = store_flash_sale_fallback_rows($conn, 7);
    foreach ($fallbackRows as $fallbackRow) {
        $fbVariantId = (int) ($fallbackRow['variant_id'] ?? 0);
        if ($fbVariantId > 0 && !isset($seenHeroVariants[$fbVariantId])) {
            $fallbackRow['_drop_state'] = 'live';
            $heroDrops[] = $fallbackRow;
            $seenHeroVariants[$fbVariantId] = true;
            if (count($heroDrops) >= 7) {
                break;
            }
        }
    }
}

foreach ($heroDrops as &$heroDropRef) {
    if (empty($heroDropRef['talent_name']) || empty($heroDropRef['talent_slug'])) {
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
        $flashProdId = (int) $heroDropRef['product_id'];
        $talentStmt->bind_param('i', $flashProdId);
        $talentStmt->execute();
        $talentRow = $talentStmt->get_result()->fetch_assoc();
        $talentStmt->close();
        $heroDropRef['talent_name'] = $talentRow['talent_name'] ?? $heroDropRef['category_name'] ?? 'hololive production';
        $heroDropRef['talent_slug'] = $talentRow['talent_slug'] ?? '';
        $heroDropRef['talent_img'] = $talentRow['talent_img'] ?? '';
        $heroDropRef['talent_group'] = $talentRow['talent_group'] ?? '';
    }
}
unset($heroDropRef);

$flashSaleProductImages = [];
$flashSaleProductIds = [];
foreach ($flashSaleProducts as $products) {
    foreach ($products as $product) {
        $flashSaleProductIds[] = (int) $product['product_id'];
    }
}
foreach ($heroDrops as $heroDrop) {
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

$heroDropPayloads = [];
foreach ($heroDrops as $heroDrop) {
    $dropProdId = (int) $heroDrop['product_id'];
    $dropImages = $flashSaleProductImages[$dropProdId] ?? [];
    $heroDropPayloads[] = store_flash_sale_build_selection_data(
        $heroDrop,
        (string) ($heroDrop['_drop_state'] ?? 'live'),
        $dropImages,
        $flashSaleTimezone,
        '../'
    );
}

$saleProduct = $heroDrops[0] ?? null;
$saleSelection = $heroDropPayloads[0] ?? null;

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

        <main class="flash-sale-main landing-shell">
            <?php if (!$flashSaleSchedulingAvailable): ?>
                <p class="flash-setup-notice" role="status">Flash sale scheduling is temporarily unavailable, so no drops are being shown. The database must include the <code>flash_sale_tbl</code> table before an administrator can schedule drops.</p>
            <?php endif; ?>

            <?php if ($saleProduct && $saleSelection): ?>
                <section
                    id="flash-drop-spotlight"
                    class="flash-cyber-hero relative overflow-hidden w-screen py-8 sm:py-10 lg:py-12 mb-12 text-slate-100 shadow-2xl"
                    data-flash-drop-section
                    aria-labelledby="flash-product-title"
                    style="--hero-theme: <?= store_escape($saleSelection['themeColor']) ?>; --hero-accent: <?= store_escape($saleSelection['accentColor']) ?>; --hero-glow: <?= store_escape($saleSelection['glowColor']) ?>;"
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

                    <!-- Main Grid Container -->
                    <div class="flash-hero-shell relative mx-auto w-full grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center z-10">

                        <!-- LEFT COLUMN: Deal Details, Countdown, Oshi Switcher & CTAs (7 cols on lg) -->
                        <div class="lg:col-span-7 flex flex-col space-y-5 min-w-0">

                            <!-- Top Brand Badges -->
                            <div class="flex flex-wrap items-center gap-2.5">
                                <div class="inline-flex items-center gap-2 bg-slate-900/90 border border-cyan-500/30 px-3 py-1.5 rounded-full text-xs text-cyan-300 shadow-sm">
                                    <img
                                        src="https://hololivepro.com/wp-content/themes/hololive_production/images/rnew/common_logo_holopro_wt_rnew.svg"
                                        alt="hololive production"
                                        class="h-3.5 w-auto object-contain"
                                        onerror="this.onerror=null;this.src='../assets/images/logo.png';"
                                    >
                                    <span class="w-1 h-1 rounded-full bg-cyan-400" aria-hidden="true"></span>
                                    <span class="font-bold tracking-wider font-outfit uppercase">2026 OFFICIAL FLASH SALE</span>
                                    <span class="w-1 h-1 rounded-full bg-cyan-400/60" aria-hidden="true"></span>
                                    <span id="flash-sale-live-count" class="text-[11px] font-semibold text-cyan-200"><?= count($flashSaleProducts['live']) ?> <?= count($flashSaleProducts['live']) === 1 ? 'drop' : 'drops' ?> live</span>
                                </div>

                                <div class="inline-flex items-center gap-1.5 bg-pink-500/20 text-pink-300 border border-pink-500/30 px-3 py-1.5 rounded-full text-xs font-black uppercase tracking-wider font-outfit">
                                    <svg class="w-3.5 h-3.5 text-pink-400 fill-pink-400 animate-pulse" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
                                    <span data-sale-detail="hero-badge"><?= store_escape($saleSelection['badge']) ?></span>
                                </div>

                                <div class="hidden sm:inline-flex items-center gap-1.5 text-[11px] text-slate-300 font-mono bg-slate-900/60 border border-slate-800 px-2.5 py-1 rounded-full">
                                    <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                                    <span data-sale-detail="state-label"><?= $isLiveScheduledFlash ? 'LIVE FLASH DROP' : 'AUTHENTICITY VERIFIED' ?></span>
                                </div>

                                <button
                                    type="button"
                                    data-hero-audio-toggle
                                    aria-pressed="false"
                                    class="inline-flex items-center gap-1.5 bg-slate-900/80 hover:bg-slate-800 border border-slate-700/80 px-2.5 py-1 rounded-full text-[11px] font-bold text-cyan-300 hover:text-white transition-colors cursor-pointer"
                                    title="Toggle Web Audio Synth FX"
                                >
                                    <svg class="w-3.5 h-3.5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                                    <span data-hero-audio-label>SFX ON</span>
                                </button>
                            </div>

                            <!-- Headline & Character Titles -->
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                    <span class="text-cyan-400 font-jp text-sm" data-sale-detail="jp-name"><?= store_escape($saleSelection['japaneseName']) ?></span>
                                    <span aria-hidden="true">•</span>
                                    <span data-sale-detail="unit"><?= store_escape($saleSelection['unit']) ?></span>
                                    <span aria-hidden="true">•</span>
                                    <span class="text-slate-200" data-sale-detail="talent"><?= store_escape($flashTalent) ?></span>
                                    <span aria-hidden="true">&middot;</span>
                                    <span data-sale-detail="category"><?= store_escape($saleSelection['category']) ?></span>
                                    <span data-sale-detail="variant-wrap"<?= !empty($saleSelection['variant']) ? '' : ' hidden' ?>>
                                        <span class="text-slate-600">/</span>
                                        <span data-sale-detail="variant"><?= store_escape($saleSelection['variant']) ?></span>
                                    </span>
                                </div>

                                <h1 id="flash-product-title" data-sale-detail="name" class="text-2xl sm:text-4xl lg:text-5xl font-black text-white font-outfit tracking-tight leading-none">
                                    <?= store_escape($saleSelection['name']) ?>
                                </h1>

                                <div class="flash-product-description-block pt-1 max-w-2xl">
                                    <p class="text-sm sm:text-base text-slate-300 font-normal leading-relaxed line-clamp-2" data-sale-detail="description-preview"><?= store_escape($saleDescription) ?></p>
                                    <div class="flash-product-description-full mt-2 p-3 rounded-xl bg-slate-950/80 border border-cyan-500/20 text-xs sm:text-sm text-slate-300 leading-relaxed max-h-40 overflow-y-auto" id="flash-product-description-full" data-sale-detail="description-full" hidden>
                                        <div class="description-full-text"><?= nl2br(store_escape($saleDescription)) ?></div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 mt-1.5">
                                        <button class="inline-flex items-center gap-1 text-xs font-bold text-cyan-400 hover:text-cyan-300 cursor-pointer" type="button" aria-expanded="false" aria-controls="flash-product-description-full" data-sale-description-toggle<?= strlen($saleDescription) <= 160 ? ' hidden' : '' ?>>
                                            <span>Read full product details</span>
                                            <svg class="toggle-chevron w-3.5 h-3.5 transition-transform" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                                        </button>
                                        <span class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-cyan-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                            <span>Direct from Cover Corp Tokyo · 5-minute bag hold on live drops</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Urgent Live Countdown Timer & Stock Progress Bar (FlashCountdown) -->
                            <div
                                class="w-full bg-slate-950/75 border border-cyan-500/25 rounded-2xl p-3.5 sm:p-4 backdrop-blur-md shadow-[0_0_25px_rgba(0,195,255,0.12)]"
                                aria-label="Flash drop countdown and allocation"
                                data-sale-countdown-wrap
                            >
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="p-1.5 rounded-lg bg-red-500/20 text-red-400 border border-red-500/30 animate-pulse">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-black uppercase tracking-wider text-red-400" data-sale-countdown-text>
                                                    FLASH SALE ENDS IN
                                                </span>
                                                <span class="flex h-2 w-2 relative" aria-hidden="true">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 m-0" data-sale-detail="countdown-footnote">Limited-Time Cover Corp Official Drop</p>
                                        </div>
                                    </div>

                                    <!-- Live Watching Count -->
                                    <div class="flex items-center gap-1.5 text-xs text-slate-300 bg-slate-900/80 px-2.5 py-1 rounded-lg border border-slate-800 self-start sm:self-auto">
                                        <svg class="w-3.5 h-3.5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                        <span class="font-bold text-cyan-300 font-outfit" data-hero-viewers>4,819</span>
                                        <span class="text-slate-400 text-[11px]">fans viewing</span>
                                    </div>
                                </div>

                                <!-- Digits Grid -->
                                <div
                                    class="countdown-timer flash-countdown-clock grid grid-cols-5 gap-2 mb-3 font-outfit"
                                    aria-live="off"
                                    data-countdown="<?= store_escape($flashDeadlineIso) ?>"
                                >
                                    <div class="flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-slate-900/90 border border-slate-800 shadow-inner">
                                        <strong class="text-lg sm:text-2xl font-black text-white tracking-wider" data-countdown-days>00</strong>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">DAYS</span>
                                    </div>
                                    <div class="flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-slate-900/90 border border-slate-800 shadow-inner">
                                        <strong class="text-lg sm:text-2xl font-black text-white tracking-wider" data-countdown-hours>00</strong>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">HOURS</span>
                                    </div>
                                    <div class="flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-slate-900/90 border border-slate-800 shadow-inner">
                                        <strong class="text-lg sm:text-2xl font-black text-white tracking-wider" data-countdown-minutes>00</strong>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">MINUTES</span>
                                    </div>
                                    <div class="flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-slate-900/90 border border-slate-800 shadow-inner">
                                        <strong class="text-lg sm:text-2xl font-black text-cyan-300 tracking-wider" data-countdown-seconds>00</strong>
                                        <span class="text-[9px] uppercase tracking-wider text-slate-400 font-medium">SECONDS</span>
                                    </div>
                                    <div class="flex flex-col items-center justify-center py-2 px-1 rounded-xl bg-slate-900/90 border border-cyan-500/30 shadow-inner">
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

                                    <div class="flash-stock-track w-full h-2.5 bg-slate-900 rounded-full overflow-hidden p-0.5 border border-slate-800" role="progressbar" aria-label="Flash drop availability claimed" aria-valuenow="<?= $flashClaimedPercent ?>" aria-valuemin="0" aria-valuemax="100">
                                        <span
                                            class="block h-full rounded-full transition-all duration-500 relative"
                                            data-sale-detail="stock-bar"
                                            style="width: <?= $flashClaimedPercent ?>%; background: linear-gradient(90deg, #10b981 0%, #3b82f6 50%, var(--hero-theme) 100%);"
                                        ></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Interactive Talent / Flash Drop Selector (Switch Your Oshi) -->
                            <?php if (count($heroDropPayloads) > 0): ?>
                                <div class="space-y-2 pt-0.5">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-slate-300 flex items-center gap-1.5 uppercase tracking-wider text-[11px]">
                                            <svg class="w-3.5 h-3.5 text-cyan-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/></svg>
                                            <span>Select Featured Oshi Vault (<?= count($heroDropPayloads) ?> Drops):</span>
                                        </span>
                                        <span class="text-[11px] text-cyan-400 font-medium">
                                            Click avatar to preview
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none" role="tablist" aria-label="Featured Oshi Flash Drops" data-hero-drop-switcher>
                                        <?php foreach ($heroDropPayloads as $dropIdx => $dropPayload): ?>
                                            <?php $isDropSelected = $dropIdx === 0; ?>
                                            <button
                                                type="button"
                                                role="tab"
                                                aria-selected="<?= $isDropSelected ? 'true' : 'false' ?>"
                                                data-hero-drop-pill
                                                data-variant-id="<?= (int) $dropPayload['variantId'] ?>"
                                                data-sale-product="<?= store_escape(json_encode($dropPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)) ?>"
                                                class="flash-oshi-pill flex items-center gap-2 px-2.5 py-1.5 rounded-2xl border transition-all cursor-pointer whitespace-nowrap shrink-0 group <?= $isDropSelected ? 'is-active bg-slate-900 border-cyan-400 shadow-[0_0_15px_rgba(0,195,255,0.4)] scale-105' : 'bg-slate-950/80 border-slate-800 hover:border-slate-700 opacity-75 hover:opacity-100' ?>"
                                            >
                                                <span class="relative w-8 h-8 rounded-xl overflow-hidden border border-slate-700 block shrink-0 bg-slate-900">
                                                    <img
                                                        src="<?= store_escape($dropPayload['avatarThumb']) ?>"
                                                        alt="<?= store_escape($dropPayload['talent']) ?>"
                                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform"
                                                        loading="lazy"
                                                        onerror="this.onerror=null;this.src='../assets/images/logo.png';"
                                                    >
                                                </span>
                                                <span class="text-left pr-1 block">
                                                    <span class="text-xs font-bold block leading-tight <?= $isDropSelected ? 'text-white' : 'text-slate-300' ?>" data-pill-name>
                                                        <?= store_escape($dropPayload['talent']) ?>
                                                    </span>
                                                    <span class="text-[10px] text-pink-400 font-extrabold font-outfit block leading-tight">
                                                        -<?= (int) $dropPayload['discount'] ?>% OFF
                                                    </span>
                                                </span>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Glassmorphic Pricing & Savings Card -->
                            <div class="p-4 rounded-2xl bg-gradient-to-r from-slate-950/90 via-slate-900/80 to-slate-950/90 border border-cyan-500/30 backdrop-blur-md flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-lg">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-400 font-medium">
                                        <span>Original Retail:</span>
                                        <del class="line-through font-mono text-slate-400" data-sale-detail="compare-price"><?= store_escape($saleSelection['comparePrice']) ?></del>
                                        <span class="text-emerald-400 font-bold bg-emerald-950/80 border border-emerald-800/40 px-2 py-0.5 rounded text-[10px]" data-sale-detail="discount">
                                            SAVE <?= $discountPercent ?>%
                                        </span>
                                    </div>

                                    <div class="flex flex-wrap items-baseline gap-2 mt-1">
                                        <strong class="text-3xl sm:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-slate-100 to-cyan-300 font-outfit" data-sale-detail="price">
                                            <?= store_escape($saleSelection['price']) ?>
                                        </strong>
                                        <span class="text-xs text-slate-400 font-mono" data-sale-detail="approx-price">
                                            (~<?= store_escape($saleSelection['approxUsd']) ?> USD &middot; ~<?= store_escape($saleSelection['approxJpy']) ?> JPY)
                                        </span>
                                    </div>
                                </div>

                                <!-- Quick Benefits -->
                                <div class="flex flex-col text-xs text-slate-300 space-y-1">
                                    <span class="flex items-center gap-1.5 text-emerald-400 font-medium">
                                        <svg class="w-3.5 h-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                        <span>Worldwide Express Airmail Dispatch</span>
                                    </span>
                                    <span class="flex items-center gap-1.5 text-cyan-300 font-medium">
                                        <svg class="w-3.5 h-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                        <span>5-Minute Bag Hold &amp; Holographic Seal</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Action CTAs Row -->
                            <div class="flash-cta-row flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-1">
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
                                        class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-cyan-500 via-sky-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-black text-sm sm:text-base shadow-[0_0_30px_rgba(0,195,255,0.45)] flex items-center justify-center gap-2.5 transition-all transform hover:-translate-y-0.5 active:translate-y-0 cursor-pointer group relative overflow-hidden border-0"
                                        type="submit"
                                        name="action"
                                        value="add"
                                        data-flash-buy-btn
                                    >
                                        <span class="absolute inset-0 animate-holo-shimmer pointer-events-none" aria-hidden="true"></span>
                                        <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
                                        <span class="tracking-wide font-outfit uppercase">CLAIM FLASH DEAL NOW</span>
                                        <svg class="w-5 h-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                    </button>
                                </form>

                                <button
                                    class="flex-1 py-4 px-6 rounded-2xl bg-slate-800/90 text-slate-400 font-black text-sm sm:text-base border border-slate-700 flex items-center justify-center gap-2 cursor-not-allowed font-outfit uppercase"
                                    type="button"
                                    data-sale-detail="cart-disabled"
                                    disabled<?= $flashStock > 0 ? ' hidden' : '' ?>
                                >
                                    Sold out
                                </button>
                                <p data-sale-detail="cart-message" role="status" hidden></p>

                                <!-- View Product Page CTA -->
                                <a
                                    href="<?= store_escape($flashProductUrl) ?>"
                                    data-sale-detail="product-link"
                                    class="py-4 px-5 rounded-2xl bg-slate-900/90 hover:bg-slate-800 text-cyan-300 hover:text-white border border-cyan-500/30 hover:border-cyan-400 text-sm font-bold flex items-center justify-center gap-2 transition-all cursor-pointer shadow-md no-underline"
                                >
                                    <svg class="w-4 h-4 text-cyan-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                                    <span>View Product</span>
                                </a>

                                <!-- Remind Me Alert Button -->
                                <button
                                    class="drop-reminder py-4 px-4 rounded-2xl bg-pink-950/40 hover:bg-pink-900/50 text-pink-300 hover:text-pink-200 border border-pink-500/40 text-sm font-bold flex items-center justify-center gap-1.5 transition-all cursor-pointer shadow-md"
                                    type="button"
                                    data-drop-reminder-btn
                                >
                                    <svg data-reminder-icon-bell class="w-4 h-4 text-pink-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                                    <svg data-reminder-icon-check hidden class="w-4 h-4 text-emerald-400 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                    <span data-reminder-label>Remind me</span>
                                </button>
                            </div>

                            <!-- Talent Speech Quote Bubble -->
                            <div class="p-3.5 rounded-2xl bg-slate-900/70 border border-slate-800/80 backdrop-blur-sm flex items-start gap-3">
                                <div class="p-2 rounded-xl bg-cyan-950/80 text-cyan-400 border border-cyan-800/40 shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 mb-0.5">
                                        <span class="text-[11px] font-bold text-cyan-400 uppercase tracking-wider" data-sale-detail="quote-speaker">
                                            <?= store_escape($flashTalent) ?>'s Flash Message:
                                        </span>
                                        <button
                                            type="button"
                                            data-hero-play-quote
                                            class="text-[10px] text-slate-300 hover:text-cyan-300 flex items-center gap-1 cursor-pointer bg-slate-800/80 border border-slate-700 px-2 py-0.5 rounded-md transition-colors"
                                        >
                                            <svg class="w-2.5 h-2.5 text-yellow-300" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/></svg>
                                            <span data-hero-play-quote-label>Play Audio Tone</span>
                                        </button>
                                    </div>

                                    <p class="text-xs text-slate-300 italic leading-relaxed m-0" data-sale-detail="quote">
                                        <?= store_escape($saleSelection['quote']) ?>
                                    </p>
                                </div>
                            </div>

                        </div>

                        <!-- RIGHT COLUMN: Official High-Res Standing Talent Character & Merch Visual (5 cols on lg) -->
                        <section class="flash-gallery lg:col-span-5 relative flex flex-col items-center justify-center min-h-[460px] sm:min-h-[540px] lg:min-h-[600px]" aria-label="Product and character gallery">

                            <!-- Vertical Japanese Character Name Background Watermark -->
                            <div class="absolute right-0 top-1/2 -translate-y-1/2 select-none pointer-events-none opacity-15 text-right" aria-hidden="true">
                                <span
                                    class="text-6xl sm:text-7xl lg:text-8xl font-black font-jp tracking-tighter block leading-none"
                                    data-sale-detail="jp-watermark"
                                    style="color: var(--hero-theme); writing-mode: vertical-rl;"
                                >
                                    <?= store_escape($saleSelection['japaneseName']) ?>
                                </span>
                            </div>

                            <!-- Background Concentric Holographic Rings -->
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
                                <div
                                    class="w-64 h-64 sm:w-88 sm:h-88 rounded-full border border-dashed animate-spin-slow opacity-30"
                                    style="border-color: var(--hero-theme);"
                                ></div>
                                <div
                                    class="absolute w-52 h-52 sm:w-72 sm:h-72 rounded-full border opacity-20"
                                    style="border-color: var(--hero-accent);"
                                ></div>
                            </div>

                            <!-- Floating Top-Left Badges on Visual -->
                            <div class="absolute top-2 left-2 sm:left-4 z-20 flex flex-col gap-2">
                                <div class="bg-slate-950/85 backdrop-blur-md border border-cyan-500/40 text-cyan-300 px-3 py-1.5 rounded-xl shadow-lg flex items-center gap-2 text-xs font-bold">
                                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping" aria-hidden="true"></span>
                                    <span data-sale-detail="generation"><?= store_escape($saleSelection['generation']) ?></span>
                                </div>

                                <div class="bg-slate-950/85 backdrop-blur-md border border-slate-800 text-slate-300 px-2.5 py-1 rounded-xl shadow-lg flex items-center gap-1.5 text-[11px]">
                                    <svg class="w-3 h-3 text-yellow-400 fill-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                    <span class="font-bold text-white">4.98/5</span>
                                    <span class="text-slate-400">(Verified Official)</span>
                                </div>
                            </div>

                            <!-- Top-Right Visual / Pose Switcher Button -->
                            <div class="absolute top-2 right-2 sm:right-4 z-20">
                                <button
                                    type="button"
                                    data-hero-pose-toggle
                                    class="bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 text-xs font-bold text-cyan-300 px-3 py-1.5 rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer transition-all<?= count($heroVisualModes) < 2 ? ' hidden' : '' ?>"
                                    title="Switch between Character Visual and Official Merch Image"
                                >
                                    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                                    <span data-hero-pose-label><?= store_escape($heroVisualModes[1]['mode_label'] ?? 'Switch Visual') ?></span>
                                </button>
                            </div>

                            <!-- Floating Main Visual -->
                            <div class="relative z-10 w-full max-w-[320px] sm:max-w-[390px] lg:max-w-[430px] flex items-center justify-center animate-float-subtle mt-8">
                                <a href="<?= store_escape($flashProductUrl) ?>" data-sale-detail="product-link" aria-label="View <?= store_escape($saleSelection['name']) ?>" class="block">
                                    <img
                                        class="flash-product-main-image w-auto h-[340px] sm:h-[420px] lg:h-[460px] object-contain filter drop-shadow-[0_10px_35px_rgba(0,195,255,0.3)] transition-all duration-500"
                                        src="<?= store_escape($saleImageUrl) ?>"
                                        alt="<?= store_escape($saleImageAlt) ?>"
                                        data-fallback-src="<?= store_escape($merchFallbackUrl) ?>"
                                        fetchpriority="high"
                                        onerror="if(this.dataset.fallbackSrc && this.src !== this.dataset.fallbackSrc){this.src=this.dataset.fallbackSrc;}"
                                    >
                                </a>
                            </div>

                            <!-- Holographic Glowing Pedestal at Base -->
                            <div class="relative w-64 sm:w-88 h-10 -mt-6 z-0 flex items-center justify-center pointer-events-none" aria-hidden="true">
                                <div
                                    class="w-full h-7 rounded-[100%] border-2 shadow-[0_0_30px_rgba(0,195,255,0.4)] opacity-80"
                                    style="border-color: var(--hero-theme); background: radial-gradient(ellipse at center, var(--hero-glow) 0%, transparent 70%);"
                                ></div>
                                <div
                                    class="absolute w-3/4 h-4 rounded-[100%] border opacity-60"
                                    style="border-color: var(--hero-accent);"
                                ></div>
                            </div>

                            <!-- Gallery Thumbnail Strip (Character Pose + Official Merch Images) -->
                            <div class="product-gallery-carousel relative z-20 mt-2 flex items-center gap-2" data-gallery-carousel>
                                <button class="product-gallery-arrow w-7 h-7 rounded-lg bg-slate-900/90 border border-slate-700 text-cyan-300 hover:bg-cyan-500 hover:text-slate-950 flex items-center justify-center cursor-pointer transition-colors" type="button" data-gallery-previous aria-label="Show previous image"<?= count($heroVisualModes) < 2 ? ' hidden' : '' ?>>‹</button>
                                <div class="product-gallery-thumbnails flex items-center gap-2 overflow-x-auto max-w-[260px] sm:max-w-[320px] py-1 scrollbar-none" aria-label="Product and character visuals" role="group">
                                    <?php foreach ($heroVisualModes as $imageIndex => $image): ?>
                                        <button
                                            class="product-gallery-thumbnail w-11 h-11 rounded-xl border p-1 bg-slate-950/85 cursor-pointer transition-all shrink-0 <?= $imageIndex === 0 ? 'is-active border-cyan-400 shadow-[0_0_10px_rgba(0,195,255,0.4)]' : 'border-slate-800 opacity-75 hover:opacity-100' ?>"
                                            type="button"
                                            data-gallery-image
                                            data-image-src="<?= store_escape($image['image_url']) ?>"
                                            data-image-alt="<?= store_escape($image['alt_text']) ?>"
                                            data-mode-label="<?= store_escape($image['mode_label']) ?>"
                                            aria-label="Show <?= store_escape($image['mode_label']) ?>"
                                            aria-pressed="<?= $imageIndex === 0 ? 'true' : 'false' ?>"
                                        >
                                            <img src="<?= store_escape($image['image_url']) ?>" alt="" class="w-full h-full object-contain" loading="lazy" decoding="async" onerror="this.closest('button').hidden=true;">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <button class="product-gallery-arrow w-7 h-7 rounded-lg bg-slate-900/90 border border-slate-700 text-cyan-300 hover:bg-cyan-500 hover:text-slate-950 flex items-center justify-center cursor-pointer transition-colors" type="button" data-gallery-next aria-label="Show next image"<?= count($heroVisualModes) < 2 ? ' hidden' : '' ?>>›</button>
                            </div>

                            <!-- Bottom Talent Info Strip -->
                            <div class="relative z-20 mt-2.5 flex items-center gap-3 bg-slate-950/85 backdrop-blur-md px-4 py-2 rounded-2xl border border-slate-800 shadow-xl">
                                <div class="text-right">
                                    <div class="text-xs font-bold text-white">
                                        Fan Club: <span data-sale-detail="fan-name"><?= store_escape($saleSelection['fanName']) ?></span>
                                    </div>
                                    <div class="text-[10px] text-cyan-400" data-sale-detail="variant-caption">
                                        Official Cover Corp Key Visual
                                    </div>
                                </div>
                                <div class="w-1.5 h-6 bg-cyan-500 rounded-full" aria-hidden="true"></div>
                                <div class="text-left">
                                    <div class="text-[10px] text-slate-400 uppercase" data-sale-detail="stock-badge">Rarity Tier</div>
                                    <div class="text-xs font-black text-yellow-400 font-outfit" data-sale-detail="rarity">
                                        <?= store_escape($saleSelection['rarity']) ?>
                                    </div>
                                </div>
                            </div>

                        </section>

                    </div>
                </section>
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
