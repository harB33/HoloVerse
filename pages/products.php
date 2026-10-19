<?php
require_once __DIR__ . '/../app/store.php';

$products = store_product_rows($conn, '', 'p.product_id DESC');
$pageTitle = 'All Products | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = '';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="all-products-main" aria-labelledby="all-products-title">
            <section class="all-products-intro">
                <span class="drops-eyebrow">Official hololive production merchandise</span>
                <h1 id="all-products-title">Shop all products</h1>
                <p>Browse the full collection of available talent merchandise.</p>
                <span class="all-products-count"><?= count($products) ?> products</span>
            </section>

            <?php if ($products): ?>
                <section class="drops-grid all-products-grid" aria-label="All available products">
                    <?php foreach ($products as $product): ?>
                        <?php $productInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product['product_name']), 0, 1)); ?>
                        <article class="drop-card">
                            <a class="drop-card-link" href="product.php?id=<?= (int) $product['product_id'] ?>" aria-label="View <?= store_escape($product['product_name']) ?>"></a>
                            <div class="drop-art">
                                <div class="drop-art-labels">
                                    <span class="drop-status"><?= (int) $product['stock_quantity'] > 0 ? 'Available now' : 'Sold out' ?></span>
                                    <span class="drop-edition"><?= store_escape($product['variant_name']) ?></span>
                                </div>
                                <?php if (!empty($product['image_url'])): ?>
                                    <img class="drop-product-image-backdrop" src="<?= store_escape(store_asset_url($product['image_url'], '../')) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                    <img class="drop-product-image" src="<?= store_escape(store_asset_url($product['image_url'], '../')) ?>" alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>" loading="lazy" decoding="async">
                                <?php endif; ?>
                                <div class="drop-character<?= !empty($product['image_url']) ? ' hidden' : '' ?>"><?= store_escape($productInitial) ?></div>
                                <span class="drop-art-tag"><?= (int) $product['stock_quantity'] ?> in stock</span>
                            </div>
                            <div class="drop-details">
                                <span class="drop-category"><?= store_escape($product['category_name']) ?></span>
                                <h2><?= store_escape($product['product_name']) ?></h2>
                                <p class="drop-description"><?= store_escape($product['description'] ?? '') ?></p>
                                <div class="drop-allocation"><span>Available quantity</span><strong><?= (int) $product['stock_quantity'] ?></strong></div>
                                <div class="drop-purchase">
                                    <div>
                                        <span class="drop-price-label"><?= (int) $product['is_preorder'] === 1 ? 'Pre-order price' : 'Price' ?></span>
                                        <strong><?= store_escape(store_format_price((float) $product['price'])) ?></strong>
                                    </div>
                                    <?php if ((int) $product['stock_quantity'] > 0): ?>
                                        <form action="cart.php" method="post">
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
                </section>
            <?php else: ?>
                <p class="all-products-empty" role="status">There are no products available right now.</p>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.querySelectorAll(".drop-product-image").forEach((image) => {
            const showInitialFallback = () => {
                image.classList.add("hidden");
                image.nextElementSibling?.classList.remove("hidden");
            };
            image.addEventListener("error", showInitialFallback, { once: true });
            if (image.complete && image.naturalWidth === 0) {
                showInitialFallback();
            }
        });
    </script>

    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
