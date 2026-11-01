<?php
require_once __DIR__ . '/../app/store.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$favoritePageSize = 12;
$wantsJson = str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
$respondToFavoriteRequest = static function (array $payload, int $status = 200) use ($wantsJson): never {
    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    }

    $_SESSION['collector_showcase_notice'] = (string) ($payload['message'] ?? 'Your Collector Showcase was updated.');
    header('Location: ' . ($status === 401 ? '/login' : '/collector-showcase'), true, 303);
    exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!store_verify_csrf()) {
        $respondToFavoriteRequest(['message' => 'Your session expired. Refresh the page and try again.'], 403);
    }
    if ($userId < 1) {
        $respondToFavoriteRequest([
            'message' => 'Sign in to save items to your Collector Showcase.',
            'login_url' => '/login',
        ], 401);
    }

    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $action = $_POST['action'] ?? '';
    if ($productId === false || !is_string($action) || !in_array($action, ['save', 'remove'], true)) {
        $respondToFavoriteRequest(['message' => 'Choose a valid product to update your favorites.'], 400);
    }

    try {
        $statement = $conn->prepare(
            "SELECT product_name FROM product_tbl WHERE product_id = ? AND status = 'active' LIMIT 1"
        );
        $statement->bind_param('i', $productId);
        $statement->execute();
        $product = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$product) {
            $respondToFavoriteRequest(['message' => 'This product is no longer available to save.'], 404);
        }

        if ($action === 'save') {
            $statement = $conn->prepare(
                'INSERT INTO collector_favorites_tbl (user_id, product_id) VALUES (?, ?)
                 ON CONFLICT (user_id, product_id) DO NOTHING'
            );
        } else {
            $statement = $conn->prepare(
                'DELETE FROM collector_favorites_tbl WHERE user_id = ? AND product_id = ?'
            );
        }
        $statement->bind_param('ii', $userId, $productId);
        $statement->execute();
        $statement->close();
    } catch (PDOException $error) {
        error_log('Collector Showcase favorite update failed: ' . $error->getMessage());
        $respondToFavoriteRequest([
            'message' => 'We could not save that favorite right now. Please try again in a moment.',
        ], 500);
    }

    $isFavorite = $action === 'save';
    $respondToFavoriteRequest([
        'is_favorite' => $isFavorite,
        'favorite_count' => store_collector_favorite_product_count($conn, $userId),
        'favorite_page_size' => $favoritePageSize,
        'message' => $isFavorite
            ? 'Saved to your Collector Showcase.'
            : 'Removed from your Collector Showcase.',
        'product_name' => (string) $product['product_name'],
    ]);
}

$favoriteNotice = is_string($_SESSION['collector_showcase_notice'] ?? null)
    ? $_SESSION['collector_showcase_notice']
    : '';
unset($_SESSION['collector_showcase_notice']);
$favoriteProductCount = $userId > 0 ? store_collector_favorite_product_count($conn, $userId) : 0;
$favoritePageCount = max(1, (int) ceil($favoriteProductCount / $favoritePageSize));
$favoritePageParameter = filter_var(
    $_GET['favorites_page'] ?? 1,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$favoriteCurrentPage = min(
    max(1, $favoritePageParameter === false ? 1 : $favoritePageParameter),
    $favoritePageCount
);
$favoriteProducts = $userId > 0
    ? store_collector_favorite_products(
        $conn,
        $userId,
        $favoritePageSize,
        ($favoriteCurrentPage - 1) * $favoritePageSize
    )
    : [];
$favoriteProductIds = array_map(
    static fn (array $product): int => (int) $product['product_id'],
    $favoriteProducts
);
$purchasedProducts = $userId > 0 ? store_collector_purchase_rows($conn, $userId) : [];
$pageTitle = 'Collector Showcase | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="collector-showcase-page home-landing">
    <div class="page-shell">
        <?php
        $activePage = 'collector_showcase';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="collector-showcase-main landing-shell">
            <header class="all-products-intro">
                <div class="all-products-intro-top">
                    <div>
                        <p class="eyebrow">Your personal collection</p>
                        <h1>Collector Showcase</h1>
                        <p>Keep the pieces you love close, and celebrate the official character goods you have made part of your collection.</p>
                    </div>
                    <?php if ($userId < 1): ?>
                        <div class="all-products-intro-actions">
                            <a class="button-primary dark" href="/login">Sign in to start your collection</a>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <?php if ($favoriteNotice !== ''): ?>
                <p class="collector-showcase-notice" role="status"><?= store_escape($favoriteNotice) ?></p>
            <?php endif; ?>

            <section class="collector-showcase-section" aria-labelledby="collector-favorites-title">
                <div class="collector-showcase-section-heading">
                    <div>
                        <span class="eyebrow">Saved for later</span>
                        <h2 id="collector-favorites-title">Your favorites</h2>
                    </div>
                    <?php if ($userId > 0): ?>
                        <span class="all-products-count" data-favorite-count><?= $favoriteProductCount ?> saved</span>
                    <?php endif; ?>
                </div>
                <?php if ($userId < 1): ?>
                    <div class="collector-showcase-empty">
                        <p>Sign in to save favorite products and find them here whenever you return.</p>
                        <a class="button-primary dark" href="/login">Sign in</a>
                    </div>
                <?php elseif ($favoriteProducts): ?>
                    <div class="product-grid collector-showcase-grid" data-favorite-grid>
                        <?php foreach ($favoriteProducts as $product): ?>
                            <?php
                            $productCardAttributes = ['data-favorite-product-id' => (string) (int) $product['product_id']];
                            require __DIR__ . '/../app/views/components/product-card.php';
                            unset($productCardAttributes);
                            ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="collector-showcase-empty" data-favorite-empty style="display: none">
                        <span class="collector-showcase-empty-heart" aria-hidden="true">♡</span>
                        <h3>Your showcase is waiting for a favorite</h3>
                        <p>Tap the heart on any product to save it here.</p>
                        <a class="button-primary dark" href="/products">Explore products</a>
                    </div>
                    <?php if ($favoritePageCount > 1): ?>
                        <nav class="all-products-pagination collector-showcase-pagination" aria-label="Favorite product pages" data-favorite-pagination>
                            <?php if ($favoriteCurrentPage > 1): ?>
                                <a href="/collector-showcase?favorites_page=<?= $favoriteCurrentPage - 1 ?>" rel="prev">Previous</a>
                            <?php endif; ?>
                            <span aria-current="page">Page <?= $favoriteCurrentPage ?> of <?= $favoritePageCount ?></span>
                            <?php if ($favoriteCurrentPage < $favoritePageCount): ?>
                                <a href="/collector-showcase?favorites_page=<?= $favoriteCurrentPage + 1 ?>" rel="next">Next</a>
                            <?php endif; ?>
                        </nav>
                    <?php else: ?>
                        <nav class="all-products-pagination collector-showcase-pagination" aria-label="Favorite product pages" data-favorite-pagination hidden></nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="collector-showcase-empty">
                        <span class="collector-showcase-empty-heart" aria-hidden="true">♡</span>
                        <h3>Your showcase is waiting for a favorite</h3>
                        <p>Tap the heart on any product to save it here.</p>
                        <a class="button-primary dark" href="/products">Explore products</a>
                    </div>
                <?php endif; ?>
            </section>

            <section class="collector-showcase-section collector-purchases-section" aria-labelledby="collector-purchases-title">
                <div class="collector-showcase-section-heading">
                    <div>
                        <span class="eyebrow">With gratitude</span>
                        <h2 id="collector-purchases-title">Your collection, completed</h2>
                    </div>
                    <?php if ($userId > 0): ?>
                        <span class="all-products-count"><?= count($purchasedProducts) ?> <?= count($purchasedProducts) === 1 ? 'item' : 'items' ?></span>
                    <?php endif; ?>
                </div>
                <p class="collector-purchases-intro">Every piece you choose supports the creators and the community around them. Thank you for being part of HoloVerse.</p>
                <?php if ($userId < 1): ?>
                    <div class="collector-showcase-empty">
                        <p>Sign in to see your purchased items and the appreciation we have saved for you.</p>
                    </div>
                <?php elseif ($purchasedProducts): ?>
                    <div class="collector-purchases-list" role="region" aria-label="Purchased items" tabindex="0">
                        <?php foreach ($purchasedProducts as $purchase): ?>
                            <article class="collector-purchase-card">
                                <?php if (!empty($purchase['image_url'])): ?>
                                    <img src="<?= store_escape(store_asset_url($purchase['image_url'], $siteRoot)) ?>" alt="<?= store_escape($purchase['alt_text'] ?? $purchase['product_name_snapshot']) ?>" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <span class="collector-purchase-placeholder" aria-hidden="true"><?= store_escape(strtoupper(substr($purchase['product_name_snapshot'], 0, 1))) ?></span>
                                <?php endif; ?>
                                <div class="collector-purchase-details">
                                    <span class="eyebrow">A piece of your collection</span>
                                    <h3><?= store_escape($purchase['product_name_snapshot']) ?></h3>
                                    <p><?= store_escape($purchase['variant_name_snapshot']) ?> &middot; Qty <?= (int) $purchase['quantity'] ?></p>
                                    <span class="collector-purchase-order">Order <?= store_escape($purchase['order_number']) ?> &middot; <?= store_escape(date('M j, Y', strtotime($purchase['created_at']))) ?></span>
                                </div>
                                <p class="collector-purchase-thanks">Thank you for choosing this piece. We hope it brings a little joy to your collection.</p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="collector-showcase-empty">
                        <h3>Your next favorite is out there</h3>
                        <p>Your confirmed purchases will appear here with a thank-you from us.</p>
                        <a class="button-primary dark" href="/products">Find your next collectible</a>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <?php
    $cartHref = '/cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
