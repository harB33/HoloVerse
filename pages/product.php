<?php
require_once __DIR__ . '/../app/store.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;
$variants = [];
$talents = [];
$images = [];

if ($productId !== false && $productId !== null && $productId > 0) {
    $statement = $conn->prepare(
        "SELECT p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date,
            c.category_name
         FROM product_tbl p
         INNER JOIN category_tbl c ON c.category_id = p.category_id
         WHERE p.product_id = ? AND p.status = 'active'
         LIMIT 1"
    );
    $statement->bind_param('i', $productId);
    $statement->execute();
    $product = $statement->get_result()->fetch_assoc();
    $statement->close();

    if ($product) {
        $statement = $conn->prepare(
            'SELECT image_url, alt_text FROM product_image_tbl WHERE product_id = ? ORDER BY sort_order, image_id'
        );
        $statement->bind_param('i', $productId);
        $statement->execute();
        $images = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
        $statement->close();

        $availableStock = store_available_stock_sql();
        $statement = $conn->prepare(
            "SELECT variant_id, variant_name, price, compare_at_price, currency, {$availableStock} AS stock_quantity
             FROM product_variants_tbl v
             WHERE v.product_id = ?
             ORDER BY variant_id"
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
    <style>
        .product-page-main {
            position: relative;
            z-index: 10;
            width: min(100% - 48px, 1420px);
            margin: 0 auto;
            padding: 22px 0 76px;
            color: #151515;
            font-family: "Kanit", "Noto Sans JP", sans-serif;
        }

        .product-breadcrumb {
            display: flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 14px;
            overflow: hidden;
            color: #758091;
            font-size: 13px;
            white-space: nowrap;
        }

        .product-breadcrumb a {
            color: #617187;
            text-decoration: none;
        }

        .product-breadcrumb a:hover { color: #087fa8; }
        .product-breadcrumb span:last-child { overflow: hidden; text-overflow: ellipsis; }

        .product-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.03fr);
            align-items: start;
            gap: clamp(36px, 6.8vw, 100px);
            border: 1px solid #e1e5e9;
            border-radius: 10px;
            background-color: #fff;
            padding: clamp(18px, 2.2vw, 32px);
            box-shadow: 0 12px 34px rgba(25, 39, 52, 0.06);
        }

        .product-gallery { min-width: 0; }

        .product-gallery-stage {
            position: relative;
            display: flex;
            height: clamp(340px, 42vw, 480px);
            align-items: center;
            justify-content: center;
            isolation: isolate;
            overflow: hidden;
            border: 1px solid #d9dce1;
            background: #e6e3ec;
        }

        .product-gallery-backdrop {
            position: absolute;
            z-index: -1;
            inset: -24px;
            width: calc(100% + 48px);
            height: calc(100% + 48px);
            padding: 0;
            object-fit: cover;
            filter: blur(24px) saturate(.8);
            opacity: .42;
            transform: scale(1.04);
        }

        .product-gallery-main {
            position: relative;
            z-index: 0;
            display: block;
            width: 100%;
            height: 100%;
            flex: 0 1 auto;
            padding: 22px;
            object-fit: contain;
        }

        .product-category-tag {
            position: absolute;
            z-index: 10 !important;
            top: 16px;
            left: 16px;
            border: 1px solid #e6e9ed;
            border-radius: 999px;
            background: #fff;
            padding: 6px 11px;
            color: #536273;
            font: 700 10px/1.2 "Montserrat", sans-serif;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .product-gallery-thumbnails {
            display: flex;
            flex: 1 1 auto;
            gap: 10px;
            min-width: 0;
            overflow-x: auto;
            padding: 2px 2px 6px;
            scrollbar-width: none;
        }

        .product-gallery-thumbnails::-webkit-scrollbar { display: none; }

        .product-gallery-carousel {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 18px;
        }

        .product-gallery-arrow {
            display: grid;
            flex: 0 0 28px;
            width: 28px;
            height: 42px;
            cursor: pointer;
            place-items: center;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: #14191f;
            font: 400 30px/1 Arial, sans-serif;
            transition: background-color .18s, color .18s;
        }

        .product-gallery-arrow:hover { background: #f0f5f7; color: #087fa8; }
        .product-gallery-arrow:focus-visible { outline: 2px solid #0ba9d0; outline-offset: 2px; }

        .product-gallery-thumbnail {
            flex: 0 0 120px;
            height: 120px;
            cursor: pointer;
            border: 1px solid #d9dce1;
            background: #fff;
            padding: 8px;
            transition: border-color .18s, box-shadow .18s;
        }

        .product-gallery-thumbnail:hover,
        .product-gallery-thumbnail.is-active {
            border-color: #0ba9d0;
            box-shadow: 0 0 0 1px #0ba9d0;
        }

        .product-gallery-thumbnail img { width: 100%; height: 100%; object-fit: contain; }

        .product-info { min-width: 0; padding: 12px 0 0; }

        .product-info-category {
            display: block;
            margin-bottom: 8px;
            color: #718096;
            font: 700 10px/1.4 "Montserrat", sans-serif;
            letter-spacing: .11em;
            text-transform: uppercase;
        }

        .product-info-heading h1 {
            margin: 0;
            color: #111820;
            font-size: clamp(25px, 2.35vw, 36px);
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -.035em;
        }

        .product-description {
            margin-top: 20px;
            border-bottom: 1px solid #d7d7d7;
            padding-bottom: 15px;
        }

        .product-description summary {
            position: relative;
            cursor: pointer;
            list-style: none;
            color: #303943;
            font-size: 14px;
        }

        .product-description summary::-webkit-details-marker { display: none; }
        .product-description summary::after {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            margin: 0 0 3px 10px;
            transform: rotate(45deg);
            border-right: 1.5px solid currentColor;
            border-bottom: 1.5px solid currentColor;
            transition: transform .18s ease;
        }
        .product-description[open] summary::after { transform: translateY(4px) rotate(225deg); }
        .product-description p { margin: 12px 0 0; color: #66717d; font-size: 14px; line-height: 1.7; }

        .product-price-block {
            display: flex;
            align-items: baseline;
            gap: 12px;
            border-bottom: 1px solid #d7d7d7;
            padding: 20px 0;
        }

        .product-current-price { color: #14191f; font: 700 clamp(26px, 2.2vw, 34px)/1.2 "Montserrat", sans-serif; }
        .product-compare-price { color: #9299a1; font: 500 15px "Montserrat", sans-serif; }

        .product-purchase-form { display: grid; gap: 17px; padding-top: 16px; }
        .product-variant-picker { min-width: 0; margin: 0; border: 0; padding: 0; }
        .product-variant-picker legend { margin-bottom: 10px; color: #344253; font-size: 14px; font-weight: 500; }
        .product-variant-options { display: flex; flex-wrap: wrap; gap: 8px; }
        .product-variant-option {
            min-height: 38px;
            cursor: pointer;
            border: 1px solid #d9dce1;
            background: #fff;
            padding: 7px 12px;
            color: #253142;
            font: 500 12px/1.25 "Montserrat", sans-serif;
            transition: border-color .18s, background-color .18s, color .18s;
        }
        .product-variant-option:hover:not(:disabled),
        .product-variant-option.is-selected { border-color: #149ec0; background: #effaff; color: #086d88; }
        .product-variant-option:disabled { cursor: not-allowed; opacity: .48; }
        .product-single-variant { display: flex; justify-content: space-between; gap: 16px; margin: 0; color: #344253; font-size: 14px; }
        .product-single-variant strong { color: #1b2735; font-weight: 600; }

        .product-stock-status { display: flex; align-items: center; gap: 8px; margin: -7px 0 0; color: #36785a; font: 600 12px "Montserrat", sans-serif; }
        .product-stock-status span { width: 8px; height: 8px; border-radius: 50%; background: #36a66b; }
        .product-stock-status.is-sold-out { color: #b23a48; }
        .product-stock-status.is-sold-out span { background: #d74a58; }

        .product-trust-note { display: flex; align-items: center; gap: 15px; margin: 0; color: #d8792d; }
        .product-trust-note svg { flex: 0 0 38px; width: 38px; height: 38px; }
        .product-trust-note p { margin: 0; color: #425064; font-size: 14px; line-height: 1.45; }

        .product-purchase-actions { display: grid; grid-template-columns: 145px minmax(0, 1fr); gap: 16px; margin-top: 2px; }
        .product-quantity-picker { display: grid; grid-template-columns: 42px minmax(0, 1fr) 42px; min-height: 50px; overflow: hidden; border: 1px solid #cfd5db; border-radius: 999px; }
        .product-quantity-picker button { cursor: pointer; color: #0b9fc5; font-size: 22px; }
        .product-quantity-picker input { width: 100%; min-width: 0; border: 0; outline: 0; text-align: center; color: #1c2530; font: 600 15px "Montserrat", sans-serif; -moz-appearance: textfield; }
        .product-quantity-picker input::-webkit-inner-spin-button,
        .product-quantity-picker input::-webkit-outer-spin-button { margin: 0; -webkit-appearance: none; }
        .product-add-button { display: flex; min-height: 52px; cursor: pointer; align-items: center; justify-content: center; gap: 10px; border: 0; border-radius: 999px; background: #12a9ce; padding: 10px 18px; color: #fff; font: 700 14px "Montserrat", sans-serif; transition: background .18s, transform .18s; }
        .product-add-button:hover:not(:disabled) { transform: translateY(-1px); background: #078cad; }
        .product-add-button:disabled { cursor: not-allowed; background: #aab5bd; }
        .product-shipping-note, .product-talent-links { margin: 16px 0 0; color: #647386; font-size: 13px; line-height: 1.6; }
        .product-talent-links a { color: #087fa8; font-weight: 600; text-decoration: underline; text-underline-offset: 2px; }
        .product-back-link { display: inline-flex; margin-top: 26px; color: #526476; font-size: 13px; text-decoration: none; }
        .product-back-link:hover { color: #087fa8; }
        .product-not-found { max-width: 760px; margin: 80px auto 28px; text-align: center; }
        .product-not-found h1 { color: #172333; font-size: 32px; }
        .product-not-found p { color: #66717d; }

        @media (max-width: 900px) {
            .product-layout { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 32px; }
            .product-gallery-stage { height: clamp(320px, 42vw, 400px); }
            .product-gallery-thumbnail { flex-basis: 88px; height: 88px; }
            .product-purchase-actions { grid-template-columns: 125px minmax(0, 1fr); gap: 10px; }
        }

        @media (max-width: 680px) {
            .product-page-main { width: min(100% - 32px, 560px); padding-top: 16px; }
            .product-layout { grid-template-columns: minmax(0, 1fr); gap: 26px; padding: 16px; }
            .product-gallery-stage { height: clamp(280px, 88vw, 380px); }
            .product-gallery-main { padding: 18px; }
            .product-info { padding-top: 0; }
            .product-info-heading h1 { font-size: 27px; }
            .product-purchase-actions { grid-template-columns: 120px minmax(0, 1fr); }
            .product-trust-note p { font-size: 13px; }
        }
    </style>
    <div class="page-shell preorder-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = '';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="product-page-main">
            <?php if ($product && $variants): ?>
                <?php
                $galleryImages = $images ?: [[
                    'image_url' => 'assets/images/logo.png',
                    'alt_text' => $product['product_name'],
                ]];
                if (count($galleryImages) === 1) {
                    // Temporary carousel slides: real additional product images in product_image_tbl replace these automatically.
                    for ($temporaryView = 2; $temporaryView <= 4; $temporaryView++) {
                        $placeholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480" viewBox="0 0 640 480"><rect width="640" height="480" fill="#f3f6f8"/><rect x="185" y="90" width="270" height="250" rx="18" fill="#e6edf1" stroke="#cbd5dc" stroke-width="3"/><path d="M225 145h190M225 205h190M225 265h190" stroke="#b6c4cc" stroke-width="12" stroke-linecap="round"/><circle cx="320" cy="190" r="78" fill="#fff" fill-opacity=".82"/><path d="M300 160v60l48-30z" fill="#0ba9d0"/><text x="320" y="390" text-anchor="middle" fill="#526476" font-family="Arial,sans-serif" font-size="22" font-weight="700">Temporary product view ' . $temporaryView . '</text></svg>';
                        $galleryImages[] = [
                            'image_url' => 'data:image/svg+xml,' . rawurlencode($placeholderSvg),
                            'alt_text' => $product['product_name'] . ' — temporary gallery view ' . $temporaryView,
                            'is_temporary' => true,
                        ];
                    }
                }
                $defaultImageUrl = str_starts_with($galleryImages[0]['image_url'], 'data:')
                    ? $galleryImages[0]['image_url']
                    : store_asset_url($galleryImages[0]['image_url'], '../');
                ?>
                <nav class="product-breadcrumb" aria-label="Breadcrumb">
                    <a href="../index.php">Home</a><span aria-hidden="true">›</span>
                    <a href="products.php">Products</a><span aria-hidden="true">›</span>
                    <span aria-current="page"><?= store_escape($product['product_name']) ?></span>
                </nav>

                <section class="product-layout" aria-labelledby="product-title">
                    <div class="product-gallery">
                        <div class="product-gallery-stage">
                            <img
                                class="product-gallery-backdrop"
                                src="<?= store_escape($defaultImageUrl) ?>"
                                alt=""
                                aria-hidden="true">
                            <span class="product-category-tag"><?= store_escape($product['category_name']) ?></span>
                            <img
                                class="product-gallery-main"
                                src="<?= store_escape($defaultImageUrl) ?>"
                                alt="<?= store_escape($galleryImages[0]['alt_text'] ?? $product['product_name']) ?>"
                                fetchpriority="high">
                        </div>
                        <?php if (count($galleryImages) > 1): ?>
                            <div class="product-gallery-carousel" data-gallery-carousel>
                                <button class="product-gallery-arrow" type="button" data-gallery-previous aria-label="Show previous product image">‹</button>
                                <div class="product-gallery-thumbnails" aria-label="Product images" role="group">
                                <?php foreach ($galleryImages as $imageIndex => $image): ?>
                                    <?php $thumbnailUrl = str_starts_with($image['image_url'], 'data:') ? $image['image_url'] : store_asset_url($image['image_url'], '../'); ?>
                                    <button class="product-gallery-thumbnail<?= $imageIndex === 0 ? ' is-active' : '' ?>" type="button" data-gallery-image data-image-src="<?= store_escape($thumbnailUrl) ?>" data-image-alt="<?= store_escape($image['alt_text'] ?? $product['product_name']) ?>" aria-label="Show product image <?= $imageIndex + 1 ?><?= !empty($image['is_temporary']) ? ' (temporary placeholder)' : '' ?>" aria-pressed="<?= $imageIndex === 0 ? 'true' : 'false' ?>">
                                        <img src="<?= store_escape($thumbnailUrl) ?>" alt="" loading="lazy">
                                    </button>
                                <?php endforeach; ?>
                                </div>
                                <button class="product-gallery-arrow" type="button" data-gallery-next aria-label="Show next product image">›</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-info">
                        <div class="product-info-heading">
                            <span class="product-info-category"><?= store_escape($product['category_name']) ?></span>
                            <h1 id="product-title"><?= store_escape($product['product_name']) ?></h1>
                        </div>

                        <?php if (!empty($product['description'])): ?>
                            <details class="product-description">
                                <summary>View full product description</summary>
                                <p><?= nl2br(store_escape($product['description'])) ?></p>
                            </details>
                        <?php endif; ?>

                        <div class="product-price-block" aria-live="polite">
                            <strong class="product-current-price" data-product-price><?= store_escape(store_format_price((float) $defaultVariant['price'])) ?></strong>
                            <del class="product-compare-price" data-product-compare <?= empty($defaultVariant['compare_at_price']) || (float) $defaultVariant['compare_at_price'] <= (float) $defaultVariant['price'] ? 'hidden' : '' ?>><?= !empty($defaultVariant['compare_at_price']) ? store_escape(store_format_price((float) $defaultVariant['compare_at_price'])) : '' ?></del>
                        </div>

                        <form class="product-purchase-form" action="cart.php" method="post" data-product-form>
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="variant_id" value="<?= (int) $defaultVariant['variant_id'] ?>" data-variant-input>

                            <?php if (count($variants) > 1): ?>
                                <fieldset class="product-variant-picker">
                                    <legend>Choose edition</legend>
                                    <div class="product-variant-options">
                                        <?php foreach ($variants as $variant): ?>
                                            <button
                                                class="product-variant-option<?= (int) $variant['variant_id'] === (int) $defaultVariant['variant_id'] ? ' is-selected' : '' ?>"
                                                type="button"
                                                data-variant-option
                                                data-variant-id="<?= (int) $variant['variant_id'] ?>"
                                                data-price="<?= store_escape(store_format_price((float) $variant['price'])) ?>"
                                                data-compare="<?= !empty($variant['compare_at_price']) ? store_escape(store_format_price((float) $variant['compare_at_price'])) : '' ?>"
                                                data-stock="<?= (int) $variant['stock_quantity'] ?>"
                                                aria-pressed="<?= (int) $variant['variant_id'] === (int) $defaultVariant['variant_id'] ? 'true' : 'false' ?>"
                                                <?= (int) $variant['stock_quantity'] < 1 ? 'disabled' : '' ?>>
                                                <?= store_escape($variant['variant_name']) ?><?= (int) $variant['stock_quantity'] < 1 ? ' · Sold out' : '' ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            <?php else: ?>
                                <p class="product-single-variant"><span>Edition</span><strong><?= store_escape($defaultVariant['variant_name']) ?></strong></p>
                            <?php endif; ?>

                            <p class="product-stock-status" data-stock-status role="status">
                                <span aria-hidden="true"></span>
                                <?= (int) $defaultVariant['stock_quantity'] > 0 ? (int) $defaultVariant['stock_quantity'] . ' available' : 'Currently sold out' ?>
                            </p>

                            <aside class="product-trust-note">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" />
                                    <path d="m9 12 2 2 4-4" />
                                </svg>
                                <p>Shop with confidence. Your order is protected and ships securely to your door.</p>
                            </aside>

                            <div class="product-purchase-actions">
                                <div class="product-quantity-picker" aria-label="Quantity">
                                    <button type="button" data-quantity-change="-1" aria-label="Decrease quantity">−</button>
                                    <input type="number" name="quantity" value="1" min="1" max="<?= max(1, (int) $defaultVariant['stock_quantity']) ?>" inputmode="numeric" aria-label="Quantity" data-quantity-input>
                                    <button type="button" data-quantity-change="1" aria-label="Increase quantity">+</button>
                                </div>
                                <button class="product-add-button" type="submit" <?= (int) $defaultVariant['stock_quantity'] < 1 ? 'disabled' : '' ?> data-add-button>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m2 2 2 2 2.4 11.5a2 2 0 0 0 2 1.5h8.8a2 2 0 0 0 2-1.6L21 8H6" />
                                        <circle cx="10" cy="20" r="1" /><circle cx="18" cy="20" r="1" />
                                    </svg>
                                    <?= (int) $defaultVariant['stock_quantity'] > 0 ? 'Add to cart' : 'Sold out' ?>
                                </button>
                            </div>
                        </form>

                        <?php if ((int) $product['is_preorder'] === 1): ?>
                            <p class="product-shipping-note">
                                <?= !empty($product['expected_ship_date']) ? 'Estimated dispatch: ' . store_escape(date('F Y', strtotime($product['expected_ship_date']))) : 'Estimated dispatch date to be announced' ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($talents): ?>
                            <p class="product-talent-links">Featuring:
                                <?php foreach ($talents as $index => $talent): ?>
                                    <?php if ($index > 0): ?>, <?php endif; ?>
                                    <a href="talent-merch.php?talent=<?= rawurlencode($talent['talent_slug']) ?>"><?= store_escape($talent['talent_name']) ?></a>
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </section>
                <a class="product-back-link" href="<?= $talents ? 'talent-merch.php?talent=' . rawurlencode($talents[0]['talent_slug']) : '../index.php#featured' ?>">← Back to merchandise</a>
            <?php else: ?>
                <section class="product-not-found">
                    <h1>Product not found</h1>
                    <p>This product is unavailable. Return to the shop to browse available merchandise.</p>
                </section>
                <a class="product-back-link" href="../index.php#featured">Back to the shop</a>
            <?php endif; ?>
        </main>
    </div>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php if ($product && $variants): ?>
        <script>
            (() => {
                const galleryMain = document.querySelector('.product-gallery-main');
                const galleryBackdrop = document.querySelector('.product-gallery-backdrop');
                const galleryThumbnails = [...document.querySelectorAll('[data-gallery-image]')];
                let activeGalleryIndex = 0;

                const showGalleryImage = (index, scrollThumbnail = false) => {
                    if (!galleryThumbnails.length) {
                        return;
                    }

                    activeGalleryIndex = (index + galleryThumbnails.length) % galleryThumbnails.length;
                    const activeThumbnail = galleryThumbnails[activeGalleryIndex];
                    galleryMain.src = activeThumbnail.dataset.imageSrc;
                    galleryBackdrop.src = activeThumbnail.dataset.imageSrc;
                    galleryMain.alt = activeThumbnail.dataset.imageAlt || '';
                    galleryThumbnails.forEach((thumbnail, thumbnailIndex) => {
                        const selected = thumbnailIndex === activeGalleryIndex;
                        thumbnail.classList.toggle('is-active', selected);
                        thumbnail.setAttribute('aria-pressed', String(selected));
                    });

                    if (scrollThumbnail) {
                        activeThumbnail.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
                    }
                };

                galleryThumbnails.forEach((thumbnail, index) => {
                    thumbnail.addEventListener('click', () => {
                        showGalleryImage(index);
                    });
                });

                document.querySelector('[data-gallery-previous]')?.addEventListener('click', () => {
                    showGalleryImage(activeGalleryIndex - 1, true);
                });
                document.querySelector('[data-gallery-next]')?.addEventListener('click', () => {
                    showGalleryImage(activeGalleryIndex + 1, true);
                });
                galleryMain?.addEventListener('keydown', (event) => {
                    if (event.key === 'ArrowLeft') {
                        showGalleryImage(activeGalleryIndex - 1, true);
                    } else if (event.key === 'ArrowRight') {
                        showGalleryImage(activeGalleryIndex + 1, true);
                    }
                });

                const form = document.querySelector('[data-product-form]');
                const variantInput = form.querySelector('[data-variant-input]');
                const price = document.querySelector('[data-product-price]');
                const comparePrice = document.querySelector('[data-product-compare]');
                const stockStatus = document.querySelector('[data-stock-status]');
                const quantityInput = form.querySelector('[data-quantity-input]');
                const addButton = form.querySelector('[data-add-button]');

                const setVariant = (option) => {
                    const stock = Number(option.dataset.stock) || 0;
                    variantInput.value = option.dataset.variantId;
                    price.textContent = option.dataset.price;
                    if (comparePrice) {
                        comparePrice.textContent = option.dataset.compare || '';
                        comparePrice.hidden = !option.dataset.compare;
                    }
                    stockStatus.lastChild.textContent = stock > 0 ? ` ${stock} available` : ' Currently sold out';
                    stockStatus.classList.toggle('is-sold-out', stock < 1);
                    quantityInput.max = String(Math.max(1, stock));
                    quantityInput.value = String(Math.min(Math.max(1, Number(quantityInput.value) || 1), Math.max(1, stock)));
                    addButton.disabled = stock < 1;
                    addButton.lastChild.textContent = stock > 0 ? ' Add to cart' : ' Sold out';
                    document.querySelectorAll('[data-variant-option]').forEach((item) => {
                        const selected = item === option;
                        item.classList.toggle('is-selected', selected);
                        item.setAttribute('aria-pressed', String(selected));
                    });
                };

                document.querySelectorAll('[data-variant-option]').forEach((option) => {
                    option.addEventListener('click', () => setVariant(option));
                });

                form.querySelectorAll('[data-quantity-change]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const delta = Number(button.dataset.quantityChange);
                        const maximum = Number(quantityInput.max) || 1;
                        quantityInput.value = String(Math.min(maximum, Math.max(1, (Number(quantityInput.value) || 1) + delta)));
                    });
                });
                quantityInput.addEventListener('change', () => {
                    const maximum = Number(quantityInput.max) || 1;
                    quantityInput.value = String(Math.min(maximum, Math.max(1, Number(quantityInput.value) || 1)));
                });
            })();
        </script>
    <?php endif; ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
