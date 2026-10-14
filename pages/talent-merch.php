<?php
require_once __DIR__ . '/../app/store.php';

$talentParameter = $_GET['talent'] ?? '';
$selectedTalent = is_string($talentParameter) ? trim($talentParameter) : '';
$talent = null;

if ($selectedTalent !== '') {
    $statement = $conn->prepare('SELECT talent_id, talent_name FROM talent_tbl WHERE talent_name = ? OR talent_slug = ? LIMIT 1');
    $statement->bind_param('ss', $selectedTalent, $selectedTalent);
    $statement->execute();
    $talent = $statement->get_result()->fetch_assoc();
    $statement->close();
}

if (!$talent) {
    http_response_code(404);
}

$products = [];
if ($talent) {
    $talentId = (int) $talent['talent_id'];
    $statement = $conn->prepare(
        "SELECT p.product_name, p.description, p.is_preorder, p.expected_ship_date,
                c.category_name, v.variant_id, v.variant_name, v.price, v.currency,
                v.stock_quantity, i.image_url, i.alt_text
         FROM product_tbl p
         INNER JOIN category_tbl c ON c.category_id = p.category_id
         INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
         INNER JOIN product_talents_tbl pt ON pt.product_id = p.product_id
         LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
         WHERE p.status = 'active' AND pt.talent_id = ?
         ORDER BY p.product_id, v.variant_id"
    );
    $statement->bind_param('i', $talentId);
    $statement->execute();
    $products = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
}

$pageTitle = $talent ? $talent['talent_name'] . ' Merchandise | HoloVerse' : 'Talent Not Found | HoloVerse';
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
            <section class="preorder-intro">
                <span class="preorder-eyebrow">Talent collection</span>
                <?php if ($talent): ?>
                    <h1><?= store_escape($talent['talent_name']) ?> merchandise</h1>
                    <p>Browse all available merchandise associated with <?= store_escape($talent['talent_name']) ?>.</p>
                <?php else: ?>
                    <h1>Talent not found</h1>
                    <p>This talent collection could not be found. Return to the directory to choose a talent.</p>
                <?php endif; ?>
            </section>

            <?php if ($talent && $products): ?>
                <section class="preorder-grid" aria-label="<?= store_escape($talent['talent_name']) ?> merchandise">
                    <?php foreach ($products as $product): ?>
                        <article class="preorder-card">
                            <div class="preorder-art">
                                <span class="preorder-badge"><?= (int) $product['is_preorder'] === 1 ? 'Pre-order' : store_escape($product['category_name']) ?></span>
                                <img
                                    class="talent-merch-image"
                                    src="<?= store_escape(store_asset_url($product['image_url'] ?? 'assets/images/logo.png', '../')) ?>"
                                    alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>"
                                    loading="lazy">
                            </div>
                            <div class="preorder-body">
                                <div class="preorder-meta"><?= store_escape($product['variant_name']) ?></div>
                                <h2><?= store_escape($product['product_name']) ?></h2>
                                <p><?= store_escape($product['description'] ?? '') ?></p>
                                <div class="preorder-footer">
                                    <span class="preorder-price"><?= store_escape($product['currency']) ?> <?= number_format((float) $product['price'], 2) ?></span>
                                    <?php if ((int) $product['stock_quantity'] > 0): ?>
                                        <form action="cart.php" method="post">
                                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="variant_id" value="<?= (int) $product['variant_id'] ?>">
                                            <button class="preorder-button" type="submit">Add to cart</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="preorder-button">Sold out</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ((int) $product['is_preorder'] === 1): ?>
                                    <div class="preorder-shipping">
                                        <?= $product['expected_ship_date'] ? 'Estimated dispatch: ' . store_escape(date('F Y', strtotime($product['expected_ship_date']))) : 'Estimated dispatch date to be announced' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php elseif ($talent): ?>
                <p role="status">There is no available merchandise for <?= store_escape($talent['talent_name']) ?> right now.</p>
            <?php endif; ?>
            <a class="preorder-guide" href="../index.php#explore">Back to the talent directory</a>
        </main>
    </div>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
</body>

</html>
