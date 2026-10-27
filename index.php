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

if ($requestPath === '/collector_showcase' || $requestPath === '/collector_showcase/') {
    $query = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    $location = '/collector-showcase' . (is_string($query) && $query !== '' ? '?' . $query : '');
    header('Location: ' . $location, true, 308);
    exit;
}

if ($requestPath === '/collector-showcase' || $requestPath === '/collector-showcase/') {
    $_SERVER['SCRIPT_NAME'] = '/pages/collector_showcase.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/collector_showcase.php';
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
$homeCategories = $conn->query(
    "SELECT c.category_id, c.category_name, sample.image_url
     FROM category_tbl c
     LEFT JOIN LATERAL (
         SELECT i.image_url
         FROM product_tbl p
         INNER JOIN product_image_tbl i ON i.product_id = p.product_id
         WHERE p.category_id = c.category_id AND p.status = 'active'
         ORDER BY i.sort_order, p.created_at DESC, i.image_id
         LIMIT 1
     ) sample ON TRUE
     ORDER BY c.category_name"
)->fetch_all(PDO::FETCH_ASSOC);
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
                    $categoryImageUrl = match ($categorySlug) {
                        'acrylic' => 'assets/images/category/acrylic.jpg',
                        'album-cd' => 'assets/images/category/album.jpg',
                        'apparel' => 'assets/images/category/apparel-transparent.webp',
                        'badge' => 'assets/images/category/badge.webp',
                        'blanket' => 'assets/images/category/blanket-transparent.webp',
                        'curtain' => 'assets/images/category/curtain.webp',
                        'figure' => 'assets/images/category/figure-transparent.webp',
                        'keychain' => 'assets/images/category/keychain-transparent.webp',
                        'mug' => 'assets/images/category/mug-transparent.webp',
                        'plush' => 'assets/images/category/plush-transparent.webp',
                        'poster-print' => 'assets/images/category/poster.jpg',
                        default => (string) ($category['image_url'] ?? ''),
                    };
                    ?>
                    <a class="home-category-card" href="/products?category=<?= (int) $category['category_id'] ?>">
                        <span class="home-category-image<?= $categoryImageUrl === '' ? ' is-unavailable' : '' ?><?= $categorySlug === 'acrylic' ? ' home-category-image-acrylic' : '' ?><?= in_array($categorySlug, ['curtain', 'figure', 'keychain'], true) ? ' home-category-image-full-fit' : '' ?><?= $categorySlug === 'curtain' ? ' home-category-image-curtain' : '' ?><?= in_array($categorySlug, ['keychain', 'mug', 'plush'], true) ? ' home-category-image-product-cutout' : '' ?><?= $categorySlug === 'mug' ? ' home-category-image-mug' : '' ?>" aria-hidden="true">
                            <?php if ($categoryImageUrl !== ''): ?>
                                <img src="<?= store_escape(store_asset_url($categoryImageUrl)) ?>" alt="" loading="lazy" decoding="async">
                            <?php endif; ?>
                        </span>
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
            categoriesTrack.addEventListener('error', (event) => {
                if (!(event.target instanceof HTMLImageElement) || !event.target.matches('.home-category-image img')) {
                    return;
                }

                const imageContainer = event.target.closest('.home-category-image');
                if (imageContainer) {
                    imageContainer.classList.add('is-unavailable');
                }
                event.target.remove();
            }, true);

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