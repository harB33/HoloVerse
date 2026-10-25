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

$featuredProducts = store_catalog_product_rows($conn, limit: 10, randomize: true);

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
                    <article class="drop-card">
                        <a class="drop-card-link" href="<?= store_escape(store_product_url($product['product_name'])) ?>" aria-label="View <?= store_escape($product['product_name']) ?>"></a>
                        <div class="drop-art">
                            <div class="drop-art-labels<?= (int) $product['stock_quantity'] > 0 ? ' drop-art-labels-no-status' : '' ?>">
                                <?php if ((int) $product['stock_quantity'] <= 0): ?>
                                    <span class="drop-status">Sold out</span>
                                <?php endif; ?>
                                <span class="drop-edition"><?= store_escape($product['variant_name']) ?></span>
                            </div>
                            <?php if (!empty($product['image_url'])): ?>
                                <img class="drop-product-image-backdrop" src="<?= store_escape(store_asset_url($product['image_url'])) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                <img class="drop-product-image" src="<?= store_escape(store_asset_url($product['image_url'])) ?>" alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>" loading="lazy" decoding="async">
                            <?php endif; ?>
                            <div class="drop-character<?= !empty($product['image_url']) ? ' hidden' : '' ?>"><?= store_escape($productInitial) ?></div>
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

        <?php $showRadarSignup = true; require __DIR__ . '/app/views/components/site-footer.php'; ?>
    </main>

    <script>
        document.querySelectorAll('.drop-product-image').forEach((image) => image.addEventListener('error', () => {
            image.classList.add('hidden');
            image.nextElementSibling?.classList.remove('hidden');
        }, { once: true }));

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
            window.addEventListener('resize', updateFeaturedControls);
            const featuredCarousel = featuredTrack.closest('[data-featured-carousel]');
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