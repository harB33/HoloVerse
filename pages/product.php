<?php
require_once __DIR__ . '/../app/store.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;
$variants = [];
$talents = [];

if ($productId !== false && $productId !== null && $productId > 0) {
    $statement = $conn->prepare(
        "SELECT p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date,
                c.category_name, image.image_url, image.alt_text
         FROM product_tbl p
         INNER JOIN category_tbl c ON c.category_id = p.category_id
         LEFT JOIN product_image_tbl image ON image.product_id = p.product_id AND image.sort_order = 0
         WHERE p.product_id = ? AND p.status = 'active'
         LIMIT 1"
    );
    $statement->bind_param('i', $productId);
    $statement->execute();
    $product = $statement->get_result()->fetch_assoc();
    $statement->close();

    if ($product) {
        $statement = $conn->prepare(
            'SELECT variant_id, variant_name, price, currency, stock_quantity
             FROM product_variants_tbl
             WHERE product_id = ?
             ORDER BY variant_id'
        );
        $statement->bind_param('i', $productId);
        $statement->execute();
        $variants = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
        $statement->close();

        $statement = $conn->prepare(
            'SELECT t.talent_name, t.talent_slug
             FROM talent_tbl t
             INNER JOIN product_talents_tbl pt ON pt.talent_id = t.talent_id
             WHERE pt.product_id = ?
             ORDER BY t.talent_name'
        );
        $statement->bind_param('i', $productId);
        $statement->execute();
        $talents = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
        $statement->close();
    }
}

if (!$product || !$variants) {
    http_response_code(404);
}

$availableVariants = array_values(array_filter(
    $variants,
    static fn (array $variant): bool => (int) $variant['stock_quantity'] > 0
));
$defaultVariant = $availableVariants[0] ?? ($variants[0] ?? null);

$pageTitle = $product ? $product['product_name'] . ' | HoloVerse' : 'Product Not Found | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell preorder-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = '';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="preorder-main">
            <?php if ($product && $variants): ?>
                <section class="product-detail" aria-labelledby="product-title">
                    <div class="product-detail-art">
                        <span class="preorder-badge"><?= (int) $product['is_preorder'] === 1 ? 'Pre-order' : store_escape($product['category_name']) ?></span>
                        <img
                            class="product-detail-image"
                            src="<?= store_escape(store_asset_url($product['image_url'] ?? 'assets/images/logo.png', '../')) ?>"
                            alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>">
                    </div>
                    <div class="product-detail-content">
                        <span class="preorder-eyebrow"><?= store_escape($product['category_name']) ?></span>
                        <h1 id="product-title"><?= store_escape($product['product_name']) ?></h1>
                        <p class="product-detail-description"><?= store_escape($product['description'] ?? '') ?></p>
                        <?php if ($talents): ?>
                            <p class="product-detail-talents">
                                Talent<?= count($talents) > 1 ? 's' : '' ?>:
                                <?php foreach ($talents as $index => $talent): ?>
                                    <?php if ($index > 0): ?>, <?php endif; ?>
                                    <a href="talent-merch.php?talent=<?= rawurlencode($talent['talent_slug']) ?>"><?= store_escape($talent['talent_name']) ?></a>
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>

                        <form class="product-detail-form" action="cart.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <label for="product-variant">Edition</label>
                            <?php if (count($variants) === 1): ?>
                                <?php $variant = $variants[0]; ?>
                                <input type="hidden" name="variant_id" value="<?= (int) $variant['variant_id'] ?>">
                                <p class="product-detail-variant"><?= store_escape($variant['variant_name']) ?></p>
                            <?php else: ?>
                                <select id="product-variant" name="variant_id" required>
                                    <?php foreach ($variants as $variant): ?>
                                        <option
                                            value="<?= (int) $variant['variant_id'] ?>"
                                            data-price="<?= store_escape(store_format_price((float) $variant['price'])) ?>"
                                            <?= (int) $variant['variant_id'] === (int) $defaultVariant['variant_id'] ? 'selected' : '' ?>
                                            <?= (int) $variant['stock_quantity'] < 1 ? 'disabled' : '' ?>>
                                            <?= store_escape($variant['variant_name']) ?><?= (int) $variant['stock_quantity'] < 1 ? ' (Sold out)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                            <strong class="product-detail-price"><?= store_escape(store_format_price((float) $defaultVariant['price'])) ?></strong>
                            <?php if ((int) $defaultVariant['stock_quantity'] > 0): ?>
                                <button class="preorder-button" type="submit" name="action" value="add">Add to cart</button>
                            <?php else: ?>
                                <span class="preorder-button">Sold out</span>
                            <?php endif; ?>
                        </form>

                        <?php if ((int) $product['is_preorder'] === 1): ?>
                            <p class="product-detail-shipping">
                                <?= $product['expected_ship_date'] ? 'Estimated dispatch: ' . store_escape(date('F Y', strtotime($product['expected_ship_date']))) : 'Estimated dispatch date to be announced' ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </section>
                <a class="talent-directory-back-link" href="<?= $talents ? 'talent-merch.php?talent=' . rawurlencode($talents[0]['talent_slug']) : '../index.php#featured' ?>">Back to merchandise</a>
            <?php else: ?>
                <section class="preorder-intro">
                    <h1>Product not found</h1>
                    <p>This product is unavailable. Return to the shop to browse available merchandise.</p>
                </section>
                <a class="talent-directory-back-link" href="../index.php#featured">Back to the shop</a>
            <?php endif; ?>
        </main>
    </div>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php if ($product && count($variants) > 1): ?>
        <script>
            const variantSelect = document.querySelector("#product-variant");
            const variantPrice = document.querySelector(".product-detail-price");
            variantSelect.addEventListener("change", () => {
                variantPrice.textContent = variantSelect.selectedOptions[0].dataset.price;
            });
        </script>
    <?php endif; ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
