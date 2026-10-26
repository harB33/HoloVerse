<?php
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
$requestPath = '/' . ltrim($requestPath, '/');

if (preg_match('~(?:^|/)\.[^/]*~', $requestPath) === 1 && $requestPath !== '/index.php') {
    http_response_code(404);
    exit('Not Found');
}

if ($requestPath === '/index.php') {
    header('Location: /home', true, 308);
    exit;
}

if ($requestPath === '/home/') {
    header('Location: /home', true, 308);
    exit;
}

if ($requestPath === '/flash_sale' || $requestPath === '/flash_sale/') {
    $query = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    $location = '/flash-sale' . (is_string($query) && $query !== '' ? '?' . $query : '');
    header('Location: ' . $location, true, 308);
    exit;
}

if ($requestPath === '/flash-sale' || $requestPath === '/flash-sale/') {
    $_SERVER['SCRIPT_NAME'] = '/pages/flash_sale.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/flash_sale.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/product/([^/]+)$~', $requestPath, $productRoute) === 1) {
    $_GET['slug'] = $productRoute[1];
    $_SERVER['SCRIPT_NAME'] = '/pages/product.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/product.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/talent-merch/([^/]+)/?$~', $requestPath, $talentMerchRoute) === 1) {
    $_GET['talent_slug'] = $talentMerchRoute[1];
    $_SERVER['SCRIPT_NAME'] = '/pages/talent-merch.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/talent-merch.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/talent-merch/([^/]+)/([^/]+)/?$~', $requestPath, $talentCategoryRoute) === 1) {
    $_GET['talent_slug'] = $talentCategoryRoute[1];
    $_GET['category_slug'] = $talentCategoryRoute[2];
    $_SERVER['SCRIPT_NAME'] = '/pages/talent-merch.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/talent-merch.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/category/([^/]+)/?$~', $requestPath, $categoryRoute) === 1) {
    $_GET['category_slug'] = $categoryRoute[1];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/generation/([A-Za-z0-9_-]+)/category/([^/]+)/?$~', $requestPath, $generationCategoryRoute) === 1) {
    $_GET['generation'] = $generationCategoryRoute[1];
    $_GET['category_slug'] = $generationCategoryRoute[2];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/generation/([A-Za-z0-9_-]+)/?$~', $requestPath, $generationRoute) === 1) {
    $_GET['generation'] = $generationRoute[1];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/([A-Za-z0-9_-]+)/category/([^/]+)/?$~', $requestPath, $generationCategoryRoute) === 1) {
    $_GET['generation'] = $generationCategoryRoute[1];
    $_GET['category_slug'] = $generationCategoryRoute[2];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/([A-Za-z0-9_-]+)/([A-Za-z0-9_-]+)/?$~', $requestPath, $generationCategoryRoute) === 1) {
    $_GET['generation'] = $generationCategoryRoute[1];
    $_GET['category_slug'] = $generationCategoryRoute[2];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/([^/]+)/?$~', $requestPath, $generationRoute) === 1) {
    $_GET['route_segment'] = $generationRoute[1];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/(?:pages/|Functions/)?([A-Za-z0-9_-]+)\.php$~i', $requestPath, $legacyPage) === 1
    && is_file(__DIR__ . '/pages/' . $legacyPage[1] . '.php')) {
    header('Location: /' . $legacyPage[1], true, 308);
    exit;
}

if (preg_match('~^/([A-Za-z0-9_-]+)/$~', $requestPath, $trailingSlashPage) === 1
    && is_file(__DIR__ . '/pages/' . $trailingSlashPage[1] . '.php')) {
    header('Location: /' . $trailingSlashPage[1], true, 308);
    exit;
}

if (preg_match('~^/([A-Za-z0-9_-]+)$~', $requestPath, $cleanPage) === 1
    && is_file(__DIR__ . '/pages/' . $cleanPage[1] . '.php')) {
    $pageScript = __DIR__ . '/pages/' . $cleanPage[1] . '.php';
    $_SERVER['SCRIPT_NAME'] = '/pages/' . $cleanPage[1] . '.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = $pageScript;
    require $pageScript;
    exit;
}

if ($requestPath !== '/' && $requestPath !== '/home') {
    $requestedFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($requestPath, '/')));
    $projectRoot = realpath(__DIR__);
    if ($requestedFile !== false
        && $projectRoot !== false
        && str_starts_with($requestedFile, $projectRoot . DIRECTORY_SEPARATOR)
        && is_file($requestedFile)) {
        return false;
    }

    http_response_code(404);
    exit('Not Found');
}

require_once __DIR__ . '/app/store.php';

$featuredProducts = store_catalog_product_rows($conn, limit: 10, randomize: true, includeImageGallery: true);
$homeCategories = $conn->query('SELECT category_id, category_name FROM category_tbl ORDER BY category_name')->fetch_all(PDO::FETCH_ASSOC);
$homeCategoryIcons = [
    'figure' => 'figure',
    'apparel' => 'apparel',
    'box-set' => 'box-set',
    'album-cd' => 'album-cd',
    'plush' => 'plush',
    'acrylic' => 'acrylic',
    'blanket' => 'blanket',
    'curtain' => 'curtain',
    'blu-ray-dvd' => 'disc',
    'digital-content' => 'digital',
    'other' => 'other',
    'badge' => 'badge',
    'coaster' => 'coaster',
    'book-pamphlet' => 'book',
    'keychain' => 'keychain',
    'mug' => 'mug',
    'poster-print' => 'poster',
];
$homeBanners = [
    ['image' => 'supernova.webp', 'alt' => 'hololive SuperNova REBOOT'],
    ['image' => 'azki_member.webp', 'alt' => 'AZKi members-only banner'],
    ['image' => 'compas_horizon.webp', 'alt' => 'hololive Compass Horizon'],
    ['image' => 'dreamspark.webp', 'alt' => 'hololive DreamSpark'],
    ['image' => 'himemori_luna.webp', 'alt' => 'Himemori Luna banner'],
    ['image' => 'kyoko_mela.webp', 'alt' => 'Kyoko Mela banner'],
    ['image' => 'official_card_game.webp', 'alt' => 'hololive Official Card Game'],
    ['image' => 'ollie.webp', 'alt' => 'Kureiji Ollie banner'],
    ['image' => 'regloss_ar_live.webp', 'alt' => 'hololive ReGLOSS AR Live'],
    ['image' => 'seasonaldays.webp', 'alt' => 'hololive Seasonal Days'],
    ['image' => 'travel_to_indo.webp', 'alt' => 'hololive Travel to Indonesia'],
];

$pageTitle = 'HoloVerse';
$siteRoot = '';
require __DIR__ . '/app/views/layout/head.php';
?>

<body>
    <main class="page-shell">
        <?php require __DIR__ . '/app/views/components/watermark.php'; ?>
        <?php
        $activePage = 'home';
        require __DIR__ . '/app/views/components/site-header.php';
        ?>

        <div id="top" class="hero">
            <section class="hero-copy" id="new-arrivals">
                <h1>hololive<br>production<br>merchandise</h1>
                <p>Exclusive Apparel, Figures, and Collectibles!</p>
                <p>Limited-Edition items for all hololive fans.</p>
            </section>
            <div class="hero-symbol">
                <img src="assets/images/logo.svg" alt="hololive logo" fetchpriority="high">
            </div>
        </div>

        <section class="home-banner-carousel" aria-label="Featured hololive banners" data-banner-carousel>
            <div class="home-banner-viewport">
                <div class="home-banner-track" data-banner-track tabindex="0" role="region" aria-roledescription="carousel" aria-label="Featured hololive banners">
                    <?php foreach ($homeBanners as $index => $banner): ?>
                        <div class="home-banner-slide" role="group" aria-roledescription="slide" aria-label="<?= $index + 1 ?> of <?= count($homeBanners) ?>: <?= store_escape($banner['alt']) ?>">
                            <img
                                src="assets/images/banner/<?= store_escape($banner['image']) ?>"
                                alt="<?= store_escape($banner['alt']) ?>"
                                <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                                decoding="async"
                            >
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="home-banner-arrow home-banner-previous" type="button" data-banner-previous aria-label="Show previous banner">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                </button>
                <button class="home-banner-arrow home-banner-next" type="button" data-banner-next aria-label="Show next banner">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                </button>
                <div class="home-banner-pagination" data-banner-pagination aria-label="Choose a banner"></div>
            </div>
        </section>

        <section class="home-categories" aria-labelledby="home-categories-title">
            <div class="home-categories-heading">
                <h2 id="home-categories-title">Shop by category</h2>
            </div>
            <div class="home-categories-controls" aria-label="Category carousel controls">
                <button type="button" data-categories-previous aria-label="Show previous categories" disabled>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                </button>
            <div class="home-categories-track" data-categories-track tabindex="0" role="region" aria-roledescription="carousel" aria-label="Shop by category">
                <?php foreach ($homeCategories as $category): ?>
                    <?php
                    $categorySlug = store_category_slug((string) $category['category_name']);
                    $categoryIcon = $homeCategoryIcons[$categorySlug] ?? 'other';
                    ?>
                    <a class="home-category-card" href="/products?category=<?= (int) $category['category_id'] ?>">
                        <svg viewBox="0 0 48 48" aria-hidden="true" focusable="false">
                            <?php if ($categoryIcon === 'figure'): ?>
                                <path d="M24 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Z" /><path d="M15 24a9 9 0 0 1 18 0v3h-5l-1 13h-6l-1-13h-5v-3Z" />
                            <?php elseif ($categoryIcon === 'apparel'): ?>
                                <g transform="scale(2)">
                                    <path vector-effect="non-scaling-stroke" d="M20.76 18.68H3.24A1.74 1.74 0 0 1 1.5 16.94a1.76 1.76 0 0 1 1-1.59L12 11.05l9.48 4.3a1.76 1.76 0 0 1 1 1.59 1.74 1.74 0 0 1-1.72 1.74Z" />
                                    <path vector-effect="non-scaling-stroke" d="M12 11.05v-.26a2.53 2.53 0 0 1 1.13-2 1.91 1.91 0 1 0-3-1.54" />
                                </g>
                            <?php elseif ($categoryIcon === 'box-set'): ?>
                                <path d="M7 15 24 7l17 8-17 9-17-9Z" /><path d="M7 15v19l17 8 17-8V15M24 24v18M15 11l17 8" />
                            <?php elseif ($categoryIcon === 'album-cd'): ?>
                                <circle cx="24" cy="24" r="18" /><circle cx="24" cy="24" r="5" /><circle cx="24" cy="24" r="1" /><path d="m27.5 21 8-8M20 28l-7 7" />
                            <?php elseif ($categoryIcon === 'plush'): ?>
                                <circle cx="15" cy="14" r="5" /><circle cx="33" cy="14" r="5" /><circle cx="24" cy="23" r="13" /><ellipse cx="24" cy="35" rx="11" ry="7" /><circle cx="20" cy="22" r="1" /><circle cx="28" cy="22" r="1" /><ellipse cx="24" cy="27" rx="3" ry="2" />
                            <?php elseif ($categoryIcon === 'acrylic'): ?>
                                <path d="M14 7h20l5 5v22l-5 5H14l-5-5V12l5-5Z" /><path d="M19 34V21a5 5 0 0 1 10 0v13m-5-18v3m-9 16h18" /><path d="m34 7 5 5" />
                            <?php elseif ($categoryIcon === 'blanket'): ?>
                                <path d="M9 12a5 5 0 0 1 5-5h25v28H14a5 5 0 0 0-5 5V12Z" /><path d="M9 17h30M9 24h30m-25 11v4m7-4v4m7-4v4m7-4v4" />
                            <?php elseif ($categoryIcon === 'curtain'): ?>
                                <path d="M6 10h36M10 12v29l14-8 14 8V12M14 13v23m20-23v23" /><circle cx="12" cy="9" r="1" /><circle cx="20" cy="9" r="1" /><circle cx="28" cy="9" r="1" /><circle cx="36" cy="9" r="1" />
                            <?php elseif ($categoryIcon === 'disc'): ?>
                                <rect x="7" y="8" width="23" height="32" rx="2" /><path d="M12 13h13v9H12z" /><circle cx="34" cy="29" r="10" /><circle cx="34" cy="29" r="2" /><path d="M34 19v8" />
                            <?php elseif ($categoryIcon === 'digital'): ?>
                                <rect x="7" y="10" width="34" height="26" rx="3" /><path d="M18 42h12m-6-6v6" /><path d="m21 17 10 6-10 6V17Z" />
                            <?php elseif ($categoryIcon === 'badge'): ?>
                                <circle cx="24" cy="19" r="12" /><circle cx="24" cy="19" r="7" /><path d="m16 29-3 14 11-6 11 6-3-14M24 14v10m-5-5h10" />
                            <?php elseif ($categoryIcon === 'coaster'): ?>
                                <circle cx="24" cy="24" r="18" /><path d="M17 18h11v9a6 6 0 0 1-12 0v-9Zm11 3h3a4 4 0 0 1 0 8h-4" /><path d="M19 14h9" />
                            <?php elseif ($categoryIcon === 'book'): ?>
                                <path d="M7 9a4 4 0 0 1 4-4h12v34H11a4 4 0 0 0-4 4V9Zm34 0a4 4 0 0 0-4-4H25v34h12a4 4 0 0 1 4 4V9Z" /><path d="M12 13h5m-5 6h5m11-6h8m-8 6h8" />
                            <?php elseif ($categoryIcon === 'keychain'): ?>
                                <circle cx="18" cy="14" r="9" /><circle cx="18" cy="14" r="4" /><path d="m24 20 13 13-7 7-13-13m7-7 5-5 7 7-5 5" />
                            <?php elseif ($categoryIcon === 'mug'): ?>
                                <path d="M8 17h27v15a9 9 0 0 1-9 9h-9a9 9 0 0 1-9-9V17Zm27 4h4a5 5 0 0 1 0 10h-4M15 8c-2 3 2 4 0 7m8-9c-2 3 2 4 0 7m8-5c-2 3 2 4 0 7" />
                            <?php elseif ($categoryIcon === 'poster'): ?>
                                <path d="M10 6h28v36H10z" /><path d="M15 34h18M16 30l7-8 5 5 4-6 3 9H16Z" /><circle cx="21" cy="17" r="2" />
                            <?php else: ?>
                                <path d="m24 5 4.5 10 11 1.5-8 7.5 2 11-9.5-5.5-9.5 5.5 2-11-8-7.5 11-1.5L24 5Z" /><path d="M8 42h32" />
                            <?php endif; ?>
                        </svg>
                        <span><?= store_escape((string) $category['category_name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
                <button type="button" data-categories-next aria-label="Show next categories">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                </button>
            </div>
        </section>

        <section class="limited-drops" id="featured" aria-labelledby="limited-drops-title">
            <div class="drops-heading">
                <div>
                    <span class="drops-eyebrow">From the official hololive production shop</span>
                    <h2 id="limited-drops-title">Talent Merch Picks</h2>
                </div>
                <div class="drops-heading-actions">
                    <div class="drops-carousel-controls" aria-label="Featured product carousel controls">
                        <button type="button" data-featured-previous aria-label="Show previous featured products" disabled>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                        </button>
                        <button type="button" data-featured-next aria-label="Show next featured products">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                        </button>
                    </div>
                    <a class="drops-view-all" href="products">View all products</a>
                </div>
            </div>
            <div class="drops-carousel" data-featured-carousel>
              <div class="drops-grid drops-carousel-track" data-featured-track tabindex="0" role="region" aria-roledescription="carousel" aria-label="Featured products">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php $productInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product['product_name']), 0, 1)); ?>
                    <?php
                    $galleryImages = json_decode((string) ($product['gallery_images'] ?? '[]'), true);
                    $galleryImages = is_array($galleryImages)
                        ? array_values(array_filter($galleryImages, static fn ($image): bool => is_array($image) && !empty($image['image_url'])))
                        : [];
                    if ($galleryImages === [] && !empty($product['image_url'])) {
                        $galleryImages[] = ['image_url' => $product['image_url'], 'alt_text' => $product['alt_text'] ?? $product['product_name']];
                    }
                    ?>
                    <article class="drop-card">
                        <a class="drop-card-link" href="<?= store_escape(store_product_url($product['product_name'])) ?>" aria-label="View <?= store_escape($product['product_name']) ?>"></a>
                        <div class="drop-art">
                            <div class="drop-art-labels<?= (int) $product['stock_quantity'] > 0 ? ' drop-art-labels-no-status' : '' ?>">
                                <?php if ((int) $product['stock_quantity'] <= 0): ?>
                                    <span class="drop-status">Sold out</span>
                                <?php endif; ?>
                                <span class="drop-edition"><?= store_escape($product['variant_name']) ?></span>
                            </div>
                            <?php if ($galleryImages !== []): ?>
                                <img class="drop-product-image-backdrop" src="<?= store_escape(store_asset_url((string) $galleryImages[0]['image_url'])) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                <div class="drop-image-slideshow" data-drop-slideshow>
                                    <?php foreach ($galleryImages as $imageIndex => $image): ?>
                                        <img
                                            class="drop-product-image<?= $imageIndex === 0 ? ' is-active' : '' ?>"
                                            src="<?= store_escape(store_asset_url((string) $image['image_url'])) ?>"
                                            alt="<?= store_escape((string) ($image['alt_text'] ?: $product['product_name'])) ?>"
                                            <?= $imageIndex === 0 ? 'aria-hidden="false"' : 'aria-hidden="true"' ?>
                                            loading="lazy"
                                            decoding="async"
                                        >
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="drop-character<?= $galleryImages !== [] ? ' hidden' : '' ?>"><?= store_escape($productInitial) ?></div>
                        </div>
                        <div class="drop-details">
                            <span class="drop-category"><?= store_escape($product['category_name']) ?></span>
                            <h3><?= store_escape($product['product_name']) ?></h3>
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
                                    <form action="cart" method="post">
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
              </div>
            </div>
        </section>

        <?php require __DIR__ . '/app/views/components/site-footer.php'; ?>
    </main>

    <script>
        const categoriesTrack = document.querySelector('[data-categories-track]');
        const categoriesPrevious = document.querySelector('[data-categories-previous]');
        const categoriesNext = document.querySelector('[data-categories-next]');
        if (categoriesTrack && categoriesPrevious && categoriesNext) {
            const updateCategoryControls = () => {
                const maxScroll = categoriesTrack.scrollWidth - categoriesTrack.clientWidth;
                categoriesPrevious.disabled = categoriesTrack.scrollLeft <= 1;
                categoriesNext.disabled = categoriesTrack.scrollLeft >= maxScroll - 1;
            };
            const scrollCategories = (direction) => {
                const card = categoriesTrack.querySelector('.home-category-card');
                if (!card) {
                    return;
                }
                const gap = Number.parseFloat(getComputedStyle(categoriesTrack).columnGap) || 0;
                categoriesTrack.scrollBy({
                    left: direction * (card.getBoundingClientRect().width + gap),
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                });
            };

            categoriesPrevious.addEventListener('click', () => scrollCategories(-1));
            categoriesNext.addEventListener('click', () => scrollCategories(1));
            categoriesTrack.addEventListener('scroll', updateCategoryControls, { passive: true });
            categoriesTrack.addEventListener('wheel', (event) => {
                if (Math.abs(event.deltaY) > Math.abs(event.deltaX)) {
                    categoriesTrack.scrollLeft += event.deltaY;
                    event.preventDefault();
                }
            }, { passive: false });
            let dragStartX = 0;
            let dragStartScrollLeft = 0;
            let draggedCategories = false;
            let activeCategoryPointer = null;
            categoriesTrack.addEventListener('pointerdown', (event) => {
                if (event.pointerType !== 'mouse' || event.button !== 0) {
                    return;
                }
                activeCategoryPointer = event.pointerId;
                dragStartX = event.clientX;
                dragStartScrollLeft = categoriesTrack.scrollLeft;
                draggedCategories = false;
            });
            window.addEventListener('pointermove', (event) => {
                if (event.pointerId !== activeCategoryPointer) {
                    return;
                }
                const deltaX = event.clientX - dragStartX;
                if (Math.abs(deltaX) > 4) {
                    draggedCategories = true;
                    categoriesTrack.classList.add('is-dragging');
                }
                if (draggedCategories) {
                    categoriesTrack.scrollLeft = dragStartScrollLeft - deltaX;
                }
            });
            const finishCategoryDrag = (event) => {
                if (event.pointerId !== activeCategoryPointer) {
                    return;
                }
                activeCategoryPointer = null;
                categoriesTrack.classList.remove('is-dragging');
                if (draggedCategories) {
                    window.setTimeout(() => { draggedCategories = false; }, 0);
                }
            };
            window.addEventListener('pointerup', finishCategoryDrag);
            window.addEventListener('pointercancel', finishCategoryDrag);
            categoriesTrack.addEventListener('click', (event) => {
                if (draggedCategories) {
                    event.preventDefault();
                    event.stopPropagation();
                    draggedCategories = false;
                }
            }, true);
            window.addEventListener('resize', updateCategoryControls);
            updateCategoryControls();
        }

        const bannerCarousel = document.querySelector('[data-banner-carousel]');
        const bannerTrack = document.querySelector('[data-banner-track]');
        const bannerPrevious = document.querySelector('[data-banner-previous]');
        const bannerNext = document.querySelector('[data-banner-next]');
        const bannerPagination = document.querySelector('[data-banner-pagination]');
        const originalBannerSlides = bannerTrack ? Array.from(bannerTrack.querySelectorAll('.home-banner-slide')) : [];
        const bannerReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        const shouldReduceBannerMotion = () => (
            bannerReducedMotion.matches || document.documentElement.dataset.reduceMotion === 'true'
        );

        if (bannerCarousel && bannerTrack && bannerPrevious && bannerNext && bannerPagination && originalBannerSlides.length > 1) {
            const makeBannerClone = (slide) => {
                const clone = slide.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                clone.setAttribute('role', 'presentation');
                clone.removeAttribute('aria-label');
                clone.querySelector('img')?.setAttribute('alt', '');
                return clone;
            };
            bannerTrack.prepend(makeBannerClone(originalBannerSlides[originalBannerSlides.length - 1]));
            bannerTrack.append(makeBannerClone(originalBannerSlides[0]));
            const bannerSlides = Array.from(bannerTrack.querySelectorAll('.home-banner-slide'));
            let activeBanner = 0;
            let bannerPaused = false;
            let bannerScrollFrame = null;
            const bannerDots = originalBannerSlides.map((slide, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'home-banner-dot';
                button.setAttribute('aria-label', `Show banner ${index + 1}`);
                button.addEventListener('click', () => showBanner(index));
                bannerPagination.append(button);
                return button;
            });

            const centerBanner = (index, behavior = 'smooth') => {
                const slide = bannerSlides[index];
                const trackBounds = bannerTrack.getBoundingClientRect();
                const slideBounds = slide.getBoundingClientRect();
                const left = bannerTrack.scrollLeft + slideBounds.left - trackBounds.left
                    - (bannerTrack.clientWidth - slideBounds.width) / 2;
                bannerTrack.scrollTo({ left, behavior });
            };

            const updateBannerPagination = () => {
                const trackCenter = bannerTrack.getBoundingClientRect().left + bannerTrack.clientWidth / 2;
                let centeredIndex = bannerSlides.reduce((closest, slide, index) => {
                    const distance = Math.abs(
                        slide.getBoundingClientRect().left + slide.getBoundingClientRect().width / 2 - trackCenter,
                    );
                    return distance < closest.distance ? { index, distance } : closest;
                }, { index: 0, distance: Infinity }).index;

                if (centeredIndex === 0 || centeredIndex === bannerSlides.length - 1) {
                    centeredIndex = centeredIndex === 0 ? bannerSlides.length - 2 : 1;
                    centerBanner(centeredIndex, 'instant');
                }
                bannerSlides.forEach((slide, index) => {
                    slide.classList.toggle('is-active-banner', index === centeredIndex);
                });
                activeBanner = (centeredIndex - 1 + originalBannerSlides.length) % originalBannerSlides.length;
                bannerDots.forEach((dot, index) => {
                    if (index === activeBanner) {
                        dot.setAttribute('aria-current', 'true');
                    } else {
                        dot.removeAttribute('aria-current');
                    }
                });
            };

            function showBanner(index) {
                const nextBanner = (index + originalBannerSlides.length) % originalBannerSlides.length;
                centerBanner(nextBanner + 1, shouldReduceBannerMotion() ? 'instant' : 'smooth');
            }

            bannerPrevious.addEventListener('click', () => showBanner(activeBanner - 1));
            bannerNext.addEventListener('click', () => showBanner(activeBanner + 1));
            bannerTrack.addEventListener('scroll', () => {
                if (bannerScrollFrame !== null) {
                    window.cancelAnimationFrame(bannerScrollFrame);
                }
                bannerScrollFrame = window.requestAnimationFrame(() => {
                    updateBannerPagination();
                    bannerScrollFrame = null;
                });
            }, { passive: true });
            window.addEventListener('resize', updateBannerPagination);

            bannerCarousel.addEventListener('mouseenter', () => { bannerPaused = true; });
            bannerCarousel.addEventListener('mouseleave', () => { bannerPaused = false; });
            bannerCarousel.addEventListener('focusin', () => { bannerPaused = true; });
            bannerCarousel.addEventListener('focusout', (event) => {
                if (!bannerCarousel.contains(event.relatedTarget)) {
                    bannerPaused = false;
                }
            });
            bannerCarousel.addEventListener('pointerdown', () => { bannerPaused = true; });
            bannerCarousel.addEventListener('pointerup', (event) => {
                if (
                    (event.pointerType === 'touch' || !bannerCarousel.matches(':hover')) &&
                    !bannerCarousel.contains(document.activeElement)
                ) {
                    bannerPaused = false;
                }
            });
            document.addEventListener('visibilitychange', updateBannerPagination);

            window.setInterval(() => {
                if (bannerPaused || document.hidden || shouldReduceBannerMotion()) {
                    return;
                }
                showBanner(activeBanner + 1);
            }, 5000);
            centerBanner(1, 'instant');
            updateBannerPagination();
        }

        const reduceSlideshowMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        document.querySelectorAll('[data-drop-slideshow]').forEach((slideshow) => {
            const images = Array.from(slideshow.querySelectorAll('.drop-product-image'));
            const card = slideshow.closest('.drop-card');
            const fallback = slideshow.closest('.drop-art')?.querySelector('.drop-character');
            if (!card || images.length < 2) {
                return;
            }

            let activeIndex = 0;
            let cycleTimer = null;
            const showNextImage = () => {
                const availableImages = images.filter((image) => !image.classList.contains('hidden'));
                if (availableImages.length === 0) {
                    fallback?.classList.remove('hidden');
                    return;
                }
                const activeImage = images[activeIndex];
                const nextImage = availableImages.find((image) => images.indexOf(image) > activeIndex) ?? availableImages[0];
                activeImage.classList.remove('is-active');
                activeImage.setAttribute('aria-hidden', 'true');
                activeIndex = images.indexOf(nextImage);
                nextImage.classList.add('is-active');
                nextImage.setAttribute('aria-hidden', 'false');
            };
            const startCycling = () => {
                if (cycleTimer !== null || reduceSlideshowMotion.matches || document.hidden) {
                    return;
                }
                cycleTimer = window.setInterval(showNextImage, 1600);
            };
            const stopCycling = () => {
                if (cycleTimer !== null) {
                    window.clearInterval(cycleTimer);
                    cycleTimer = null;
                }
                images[activeIndex].classList.remove('is-active');
                images[activeIndex].setAttribute('aria-hidden', 'true');
                activeIndex = 0;
                images[activeIndex].classList.add('is-active');
                images[activeIndex].setAttribute('aria-hidden', 'false');
            };

            images.forEach((image, imageIndex) => image.addEventListener('error', () => {
                image.classList.add('hidden');
                if (imageIndex === activeIndex) {
                    showNextImage();
                }
            }, { once: true }));
            card.addEventListener('mouseenter', startCycling);
            card.addEventListener('mouseleave', stopCycling);
            card.addEventListener('focusin', startCycling);
            card.addEventListener('focusout', (event) => {
                if (!card.contains(event.relatedTarget)) {
                    stopCycling();
                }
            });
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopCycling();
                } else if (card.matches(':hover') || card.contains(document.activeElement)) {
                    startCycling();
                }
            });
        });

        const featuredTrack = document.querySelector('[data-featured-track]');
        const featuredPrevious = document.querySelector('[data-featured-previous]');
        const featuredNext = document.querySelector('[data-featured-next]');
        if (featuredTrack && featuredPrevious && featuredNext) {
            const updateFeaturedControls = () => {
                const maxScroll = featuredTrack.scrollWidth - featuredTrack.clientWidth;
                featuredPrevious.disabled = featuredTrack.scrollLeft <= 1;
                featuredNext.disabled = featuredTrack.scrollLeft >= maxScroll - 1;
            };
            let featuredAnimationFrame = null;
            const animateFeaturedTo = (target) => {
                if (featuredAnimationFrame !== null) {
                    window.cancelAnimationFrame(featuredAnimationFrame);
                    featuredAnimationFrame = null;
                }
                if (reducedMotion.matches) {
                    featuredTrack.scrollLeft = target;
                    return;
                }

                const start = featuredTrack.scrollLeft;
                const distance = target - start;
                const duration = 80;
                let startTime = null;
                const animate = (time) => {
                    startTime ??= time;
                    const progress = Math.min((time - startTime) / duration, 1);
                    const easedProgress = -(Math.cos(Math.PI * progress) - 1) / 2;
                    featuredTrack.scrollLeft = start + distance * easedProgress;
                    if (progress < 1) {
                        featuredAnimationFrame = window.requestAnimationFrame(animate);
                    } else {
                        featuredAnimationFrame = null;
                    }
                };
                featuredAnimationFrame = window.requestAnimationFrame(animate);
            };
            const scrollFeatured = (direction) => {
                const card = featuredTrack.querySelector('.drop-card');
                if (!card) {
                    return;
                }
                const gap = Number.parseFloat(getComputedStyle(featuredTrack).columnGap) || 0;
                const maxScroll = featuredTrack.scrollWidth - featuredTrack.clientWidth;
                const target = Math.max(0, Math.min(
                    maxScroll,
                    featuredTrack.scrollLeft + direction * (card.getBoundingClientRect().width + gap),
                ));
                animateFeaturedTo(target);
            };

            featuredPrevious.addEventListener('click', () => scrollFeatured(-1));
            featuredNext.addEventListener('click', () => scrollFeatured(1));
            featuredTrack.addEventListener('scroll', updateFeaturedControls, { passive: true });
            const featuredCarousel = featuredTrack.closest('[data-featured-carousel]');
            featuredCarousel?.addEventListener('wheel', (event) => {
                let delta = Math.abs(event.deltaY) > Math.abs(event.deltaX)
                    ? event.deltaY
                    : event.deltaX;
                if (event.deltaMode === WheelEvent.DOM_DELTA_LINE) {
                    delta *= 16;
                } else if (event.deltaMode === WheelEvent.DOM_DELTA_PAGE) {
                    delta *= featuredTrack.clientWidth;
                }
                const maxScroll = featuredTrack.scrollWidth - featuredTrack.clientWidth;
                const canScroll = delta > 0
                    ? featuredTrack.scrollLeft < maxScroll - 1
                    : featuredTrack.scrollLeft > 1;
                if (maxScroll <= 1 || delta === 0 || !canScroll) {
                    return;
                }
                featuredTrack.scrollBy({ left: delta, behavior: 'smooth' });
                event.preventDefault();
            }, { passive: false });
            window.addEventListener('resize', updateFeaturedControls);
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            let carouselPaused = false;
            const pauseCarousel = () => {
                carouselPaused = true;
            };
            const resumeCarousel = () => {
                carouselPaused = false;
            };

            featuredCarousel?.addEventListener('mouseenter', pauseCarousel);
            featuredCarousel?.addEventListener('mouseleave', resumeCarousel);
            featuredCarousel?.addEventListener('focusin', pauseCarousel);
            featuredCarousel?.addEventListener('focusout', (event) => {
                if (!featuredCarousel.contains(event.relatedTarget)) {
                    resumeCarousel();
                }
            });
            featuredCarousel?.addEventListener('pointerdown', pauseCarousel);
            featuredCarousel?.addEventListener('pointerup', (event) => {
                if (
                    (event.pointerType === 'touch' || !featuredCarousel.matches(':hover')) &&
                    !featuredCarousel.contains(document.activeElement)
                ) {
                    resumeCarousel();
                }
            });
            document.addEventListener('visibilitychange', updateFeaturedControls);

            window.setInterval(() => {
                const maxScroll = featuredTrack.scrollWidth - featuredTrack.clientWidth;
                if (carouselPaused || document.hidden || reducedMotion.matches || maxScroll <= 1) {
                    return;
                }
                if (featuredTrack.scrollLeft >= maxScroll - 1) {
                    animateFeaturedTo(0);
                } else {
                    scrollFeatured(1);
                }
            }, 4500);
            updateFeaturedControls();
        }
    </script>
    <?php
    $cartHref = 'cart';
    include __DIR__ . '/app/views/components/cart-button.php';
    ?>
</body>

</html>