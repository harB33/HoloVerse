<?php
require_once __DIR__ . '/../app/store.php';

$productSlugInput = $_GET['slug'] ?? '';
$productSlug = is_string($productSlugInput) ? strtolower(trim($productSlugInput)) : '';
$productId = $productSlug === '' ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : null;
$product = null;
$variants = [];
$images = [];
$recommendedProducts = [];

if ($productSlug !== '' || ($productId !== false && $productId !== null && $productId > 0)) {
    $productQuery = "SELECT p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date,
                            c.category_name,
                            (SELECT t.talent_name
                             FROM product_talents_tbl pt
                             INNER JOIN talent_tbl t ON t.talent_id = pt.talent_id
                             WHERE pt.product_id = p.product_id
                             ORDER BY t.talent_id
                             LIMIT 1) AS talent_name
                     FROM product_tbl p
                     INNER JOIN category_tbl c ON c.category_id = p.category_id
                     WHERE p.status = 'active' AND ";
    $productStatement = $productSlug !== ''
        ? $conn->prepare(
            $productQuery . "(
                lower(regexp_replace(
                    regexp_replace(p.product_name, '[^[:alnum:]]+', '-', 'g'),
                    '(^-+|-+$)',
                    '',
                    'g'
                )) = ?
                OR lower(regexp_replace(
                    regexp_replace(p.product_name, '^[[:space:]]+|[[:space:]]+$', '', 'g'),
                    '[[:space:]]+',
                    '-',
                    'g'
                )) = ?
            ) ORDER BY p.product_id LIMIT 1"
        )
        : $conn->prepare($productQuery . 'p.product_id = ? LIMIT 1');
    if ($productSlug !== '') {
        $productStatement->bind_param('ss', $productSlug, $productSlug);
    } else {
        $productStatement->bind_param('i', $productId);
    }
    $productStatement->execute();
    $product = $productStatement->get_result()->fetch_assoc();
    $productStatement->close();

    if ($product && $productSlug === '') {
        header('Location: ' . store_product_url($product['product_name']), true, 308);
        exit;
    }

    if ($product && $productSlug !== '') {
        $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if (is_string($requestPath) && $requestPath !== store_product_url($product['product_name'])) {
            header('Location: ' . store_product_url($product['product_name']), true, 308);
            exit;
        }
    }

    if ($product) {
        $productId = (int) $product['product_id'];
    }

    if ($product) {
        $statement = $conn->prepare(
            'SELECT image_url, alt_text FROM product_image_tbl WHERE product_id = ? ORDER BY sort_order, image_id'
        );
        $statement->bind_param('i', $productId);
        $statement->execute();
        $images = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
        $statement->close();

        $availableStock = store_available_stock_sql();
        $eligibleGroupSlugs = store_talent_filter_group_slugs_sql();
        $statement = $conn->prepare(
            "SELECT v.variant_id, v.variant_name, v.price, v.compare_at_price, v.currency,
                    {$availableStock} AS stock_quantity,
                    COALESCE(
                        (
                            SELECT tg.group_name
                            FROM talent_tbl t
                            INNER JOIN talent_group_members_tbl gm ON gm.talent_id = t.talent_id
                            INNER JOIN talent_group_tbl tg ON tg.group_id = gm.group_id
                            WHERE regexp_replace(regexp_replace(lower(v.variant_name), '^.* / ', ''), '[^a-z0-9]', '', 'g')
                                = regexp_replace(lower(t.talent_name), '[^a-z0-9]', '', 'g')
                              AND tg.group_slug IN ({$eligibleGroupSlugs})
                            ORDER BY
                                CASE WHEN tg.group_slug = 'gamers' THEN 1 ELSE 0 END,
                                array_position(ARRAY[{$eligibleGroupSlugs}]::text[], tg.group_slug)
                            LIMIT 1
                        ),
                        (
                            SELECT tg.group_name
                            FROM talent_group_tbl tg
                            WHERE lower(v.variant_name) LIKE lower(tg.group_name || ' / %')
                            ORDER BY length(tg.group_name) DESC
                            LIMIT 1
                        )
                    ) AS edition_groups
             FROM product_variants_tbl v
             WHERE v.product_id = ?
             ORDER BY variant_id"
        );
        $statement->bind_param('i', $productId);
        $statement->execute();
        $variants = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
        $statement->close();
    }
}

if ($product) {
    $currentProductId = (int) $product['product_id'];
    $recommendedProducts = store_catalog_product_rows(
        $conn,
        limit: 20,
        randomize: true,
        includeImageGallery: true,
        relatedProductId: $currentProductId
    );
    $recommendedProducts = array_values(array_filter(
        $recommendedProducts,
        static fn (array $recommendedProduct): bool => (int) $recommendedProduct['product_id'] !== $currentProductId
    ));
    $recommendedProducts = array_slice($recommendedProducts, 0, 8);
}

if (!$product || !$variants) {
    http_response_code(404);
}

$favoriteProductIds = $product
    ? store_collector_favorite_ids($conn, (int) ($_SESSION['user_id'] ?? 0))
    : [];
$availableVariants = array_values(array_filter(
    $variants,
    static fn (array $variant): bool => (int) $variant['stock_quantity'] > 0
));
$defaultVariant = $availableVariants[0] ?? ($variants[0] ?? null);
$variantGroups = [];
$ungroupedVariants = [];
foreach ($variants as $variant) {
    $hasVariantGroups = !empty($variant['edition_groups']);
    $groupNames = $hasVariantGroups ? explode("\n", $variant['edition_groups']) : [];
    $separatorPosition = strpos($variant['variant_name'], ' / ');
    $variant['edition_label'] = $hasVariantGroups && $separatorPosition !== false
        ? substr($variant['variant_name'], $separatorPosition + 3)
        : $variant['variant_name'];

    if (!$groupNames) {
        $ungroupedVariants[] = $variant;
        continue;
    }

    foreach ($groupNames as $groupName) {
        if (!isset($variantGroups[$groupName])) {
            $variantGroups[$groupName] = [
                'name' => $groupName,
                'variants' => [],
            ];
        }

        $variantGroups[$groupName]['variants'][] = $variant;
    }
}
if ($ungroupedVariants) {
    $variantGroups['Other items'] = [
        'name' => 'Other items',
        'variants' => $ungroupedVariants,
    ];
}

$pageTitle = $product ? $product['product_name'] . ' | HoloVerse' : 'Product Not Found | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="home-landing">
    <style>
        .product-page-main {
            position: relative;
            z-index: 10;
            padding: 36px 0 88px;
            color: var(--ink);
            font-family: var(--font-sans);
        }

        .product-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            margin: 0;
            overflow: hidden;
            color: var(--muted);
            font-size: 0.84rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .product-page-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 20px;
        }

        .product-breadcrumb a {
            color: var(--muted);
            text-decoration: none;
            transition: color 160ms ease;
        }

        .product-breadcrumb a:hover { color: var(--holo-deep); }
        .product-breadcrumb span:last-child { color: var(--ink); font-weight: 700; overflow: hidden; text-overflow: ellipsis; }
        .product-breadcrumb-separator { width: 14px; height: 14px; flex: 0 0 14px; color: var(--muted); }

        .product-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.04fr);
            align-items: stretch;
            gap: clamp(32px, 5vw, 68px);
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--bg);
            padding: clamp(20px, 2.5vw, 36px);
            box-shadow: 0 18px 42px rgba(15, 29, 51, 0.06);
        }

        .product-recommendations { margin-top: 56px; }
        .product-recommendations-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
        .product-recommendations-heading h2 { margin: 6px 0 0; color: var(--ink); font-family: var(--font-display); font-size: clamp(1.6rem, 2.6vw, 2.3rem); font-weight: 700; letter-spacing: -0.04em; line-height: 1.05; }
        .product-recommendations-controls { display: flex; flex: 0 0 auto; gap: 8px; }
        .product-recommendations-controls button { display: grid; width: 40px; height: 40px; cursor: pointer; place-items: center; border: 1px solid var(--line); border-radius: 8px; background: var(--bg); color: var(--ink); transition: border-color .18s, color .18s, background-color .18s; }
        .product-recommendations-controls button:hover:not(:disabled) { border-color: var(--holo-cyan); background: var(--surface); color: var(--holo-deep); }
        .product-recommendations-controls button:disabled { cursor: not-allowed; opacity: .4; }
        .product-recommendations-controls button:focus-visible { outline: 2px solid var(--holo-cyan); outline-offset: 2px; }
        .product-recommendations-controls svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; }
        .product-recommendations-track { display: flex; gap: 20px; overflow-x: auto; overscroll-behavior-x: contain; scroll-behavior: smooth; scroll-snap-type: x mandatory; scrollbar-width: none; padding-bottom: 4px; }
        .product-recommendations-track::-webkit-scrollbar { display: none; }
        .product-recommendations-track > .product-card { flex: 0 0 calc((100% - 60px) / 4); min-width: 0; scroll-snap-align: start; }

        .product-gallery { min-width: 0; }

        .product-gallery-stage {
            position: relative;
            display: flex;
            height: clamp(360px, 44vw, 500px);
            align-items: center;
            justify-content: center;
            isolation: isolate;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
        }

        .product-gallery-backdrop {
            position: absolute;
            z-index: -1;
            inset: -24px;
            width: calc(100% + 48px);
            height: calc(100% + 48px);
            padding: 0;
            object-fit: cover;
            filter: blur(28px) saturate(.85);
            opacity: .28;
            transform: scale(1.04);
        }

        .product-gallery-main {
            position: relative;
            z-index: 0;
            display: block;
            width: 100%;
            height: 100%;
            flex: 0 1 auto;
            padding: 24px;
            object-fit: contain;
        }

        .product-category-tag {
            position: absolute;
            z-index: 10 !important;
            top: 14px;
            left: 14px;
            border-radius: 999px;
            background: rgba(16, 24, 38, 0.86);
            padding: 6px 12px;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 800;
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
            margin-top: 14px;
        }

        .product-gallery-arrow {
            display: grid;
            flex: 0 0 32px;
            width: 32px;
            height: 44px;
            cursor: pointer;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--bg);
            color: var(--ink);
            font: 600 22px/1 var(--font-sans);
            transition: background-color .18s, color .18s, border-color .18s;
        }

        .product-gallery-arrow:hover { border-color: var(--holo-cyan); background: var(--surface); color: var(--holo-deep); }
        .product-gallery-arrow:focus-visible { outline: 2px solid var(--holo-cyan); outline-offset: 2px; }

        .product-gallery-thumbnail {
            flex: 0 0 92px;
            height: 92px;
            cursor: pointer;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            padding: 8px;
            transition: border-color .18s, box-shadow .18s;
        }

        .product-gallery-thumbnail:hover,
        .product-gallery-thumbnail.is-active {
            border-color: var(--holo-cyan);
            box-shadow: 0 0 0 2px rgba(19, 168, 222, 0.22);
        }

        .product-gallery-thumbnail img { width: 100%; height: 100%; object-fit: contain; }

        .product-detail-info { display: flex; min-width: 0; flex-direction: column; padding: 4px 0 0; }

        .product-info-category {
            display: block;
            margin-bottom: 8px;
            color: var(--holo-deep);
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .product-info-heading h1 {
            margin: 0;
            color: var(--ink);
            font-family: var(--font-display);
            font-size: clamp(1.75rem, 2.6vw, 2.5rem);
            font-weight: 700;
            line-height: 1.1;
            letter-spacing: -.035em;
        }

        .product-description {
            margin-top: 18px;
            border-bottom: 1px solid var(--line);
            padding-bottom: 16px;
        }

        .product-description-full {
            margin: 0 0 10px;
            color: var(--ink-soft);
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .product-description-full.is-collapsed {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            mask-image: linear-gradient(to bottom, #000 70%, transparent 100%);
        }

        .product-description-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            border: 0;
            background: transparent;
            padding: 0;
            cursor: pointer;
            color: var(--holo-deep);
            font: 800 0.82rem var(--font-sans);
        }

        .product-description-toggle::after {
            width: 6px;
            height: 6px;
            border-right: 1.5px solid currentColor;
            border-bottom: 1.5px solid currentColor;
            content: "";
            transform: translateY(-2px) rotate(45deg);
            transition: transform .18s ease;
        }

        .product-description-toggle[aria-expanded="true"]::after {
            transform: translateY(2px) rotate(225deg);
        }

        .product-price-block {
            display: flex;
            align-items: baseline;
            gap: 12px;
            border-bottom: 1px solid var(--line);
            padding: 20px 0;
        }

        .product-current-price { color: var(--ink); font: 700 clamp(1.75rem, 2.4vw, 2.25rem)/1.1 var(--font-mono); }
        .product-compare-price { color: var(--muted); font: 500 1rem var(--font-mono); }

        .product-purchase-form { display: flex; flex: 1; flex-direction: column; gap: 18px; padding-top: 18px; }
        .product-variant-picker { min-width: 0; margin: 0; border: 0; padding: 0; }
        .product-variant-picker legend { margin-bottom: 10px; color: var(--ink); font-size: 0.78rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; }
        .product-variant-options {
            display: flex;
            max-height: min(184px, 40vh);
            flex-wrap: wrap;
            align-content: flex-start;
            gap: 8px;
            overflow-y: auto;
            overscroll-behavior-y: contain;
            padding: 2px 8px 2px 0;
            scrollbar-width: thin;
        }
        .product-variant-group { flex: 1 0 100%; display: grid; gap: 8px; }
        .product-variant-group h3 { margin: 0; color: var(--muted); font-size: 0.76rem; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; }
        .product-variant-group-options { display: flex; flex-wrap: wrap; gap: 8px; }
        .product-variant-option {
            min-height: 38px;
            cursor: pointer;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--bg);
            padding: 7px 14px;
            color: var(--ink-soft);
            font: 700 0.8rem/1.25 var(--font-sans);
            transition: border-color .18s, background-color .18s, color .18s;
        }
        .product-variant-option:hover:not(:disabled) { border-color: var(--holo-cyan); color: var(--ink); }
        .product-variant-option.is-selected { border-color: var(--ink); background: var(--ink); color: #fff; }
        .product-variant-option:disabled { cursor: not-allowed; opacity: .45; }
        .product-single-variant { display: flex; justify-content: space-between; gap: 16px; margin: 0; padding: 12px 14px; border: 1px solid var(--line); border-radius: 8px; background: var(--surface); color: var(--muted); font-size: 0.88rem; }
        .product-single-variant strong { color: var(--ink); font-weight: 800; }

        .product-stock-status { display: inline-flex; align-items: center; gap: 8px; width: fit-content; margin: 0; border-radius: 999px; background: #e8f8f0; padding: 6px 12px; color: #0e7a53; font: 800 0.76rem var(--font-sans); }
        .product-stock-status span { width: 8px; height: 8px; border-radius: 50%; background: var(--mint); }
        .product-stock-status.is-sold-out { background: #fdecef; color: #b23a48; }
        .product-stock-status.is-sold-out span { background: #d74a58; }

        .product-trust-note { display: flex; align-items: flex-start; gap: 14px; margin: auto 0 0; border: 1px solid var(--line); border-radius: 8px; background: var(--surface); padding: 14px 16px; color: var(--holo-deep); }
        .product-trust-note svg { flex: 0 0 26px; width: 26px; height: 26px; margin-top: 2px; }
        .product-trust-note p { margin: 0; color: var(--ink-soft); font-size: 0.85rem; line-height: 1.55; }

        .product-purchase-actions { display: grid; grid-template-columns: 140px minmax(0, 1fr); gap: 14px; margin-top: 4px; }
        .product-quantity-picker { display: grid; grid-template-columns: 42px minmax(0, 1fr) 42px; min-height: 48px; overflow: hidden; border: 1px solid var(--line); border-radius: 8px; background: var(--bg); }
        .product-quantity-picker button { cursor: pointer; border: 0; background: transparent; color: var(--ink); font-size: 1.25rem; font-weight: 700; transition: background-color 150ms ease; }
        .product-quantity-picker button:hover { background: var(--surface); }
        .product-quantity-picker input { width: 100%; min-width: 0; border: 0; background: transparent; outline: 0; text-align: center; color: var(--ink); font: 700 0.95rem var(--font-mono); -moz-appearance: textfield; }
        .product-quantity-picker input::-webkit-inner-spin-button,
        .product-quantity-picker input::-webkit-outer-spin-button { margin: 0; -webkit-appearance: none; }
        .product-add-button { display: inline-flex; min-height: 48px; cursor: pointer; align-items: center; justify-content: center; gap: 10px; border: 0; border-radius: 8px; background: var(--ink); padding: 12px 20px; color: #fff; font: 800 0.92rem var(--font-sans); transition: background-color 160ms ease, transform 160ms ease; }
        .product-add-button:hover:not(:disabled) { background: var(--holo-deep); transform: translateY(-1px); }
        .product-add-button:disabled { cursor: not-allowed; background: #aab5bd; }
        .product-back-link { display: inline-flex; flex: 0 0 auto; align-items: center; gap: 5px; margin: 0; color: var(--ink); font-size: 0.85rem; font-weight: 800; line-height: 1.5; text-decoration: none; }
        .product-back-link svg { display: block; width: 15px; height: 15px; flex: 0 0 15px; }
        .product-back-link:hover { color: var(--holo-deep); }
        .product-not-found { max-width: 680px; margin: 64px auto 28px; border: 1px solid var(--line); border-radius: 8px; background: var(--surface); padding: 48px 32px; text-align: center; }
        .product-not-found h1 { margin: 0 0 10px; color: var(--ink); font-family: var(--font-display); font-size: 2rem; }
        .product-not-found p { margin: 0 0 22px; color: var(--muted); }

        @media (max-width: 1100px) {
            .product-recommendations-track > .product-card { flex-basis: calc((100% - 40px) / 3); }
        }

        @media (max-width: 900px) {
            .product-layout { grid-template-columns: minmax(0, 1fr); gap: 28px; }
            .product-gallery-stage { height: clamp(320px, 58vw, 420px); }
            .product-gallery-thumbnail { flex-basis: 80px; height: 80px; }
            .product-purchase-actions { grid-template-columns: 125px minmax(0, 1fr); gap: 10px; }
            .product-recommendations-track > .product-card { flex-basis: calc((100% - 20px) / 2); }
        }

        @media (max-width: 620px) {
            .product-page-main { padding-top: 20px; }
            .product-page-topbar { flex-direction: column; align-items: flex-start; gap: 10px; }
            .product-layout { padding: 16px; }
            .product-gallery-stage { height: clamp(280px, 84vw, 360px); }
            .product-recommendations-track > .product-card { flex-basis: min(86%, 320px); }
        }
    </style>
    <div class="page-shell">
        <?php
        $activePage = 'products';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="product-page-main landing-shell">
            <?php if ($product && $variants): ?>
                <?php
                $galleryImages = $images ?: [[
                    'image_url' => 'assets/images/logo.png',
                    'alt_text' => $product['product_name'],
                ]];
                if (count($galleryImages) === 1) {
                    for ($temporaryView = 2; $temporaryView <= 4; $temporaryView++) {
                        $placeholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480" viewBox="0 0 640 480"><rect width="640" height="480" fill="#f3f6f8"/><rect x="185" y="90" width="270" height="250" rx="18" fill="#e6edf1" stroke="#cbd5dc" stroke-width="3"/><path d="M225 145h190M225 205h190M225 265h190" stroke="#b6c4cc" stroke-width="12" stroke-linecap="round"/><circle cx="320" cy="190" r="78" fill="#fff" fill-opacity=".82"/><path d="M300 160v60l48-30z" fill="#0ba9d0"/><text x="320" y="390" text-anchor="middle" fill="#526476" font-family="Arial,sans-serif" font-size="22" font-weight="700">Temporary product view ' . $temporaryView . '</text></svg>';
                        $galleryImages[] = [
                            'image_url' => 'data:image/svg+xml,' . rawurlencode($placeholderSvg),
                            'alt_text' => $product['product_name'] . ' — temporary gallery view ' . $temporaryView,
                            'is_temporary' => true,
                        ];
                    }
                }
                $galleryImageUrl = static fn (string $imageUrl): string => str_starts_with($imageUrl, 'data:')
                    ? $imageUrl
                    : store_asset_url($imageUrl, '../');
                $defaultImageUrl = $galleryImageUrl($galleryImages[0]['image_url']);
                $productTalentLabel = trim((string) ($product['talent_name'] ?? ''));
                ?>
                <div class="product-page-topbar">
                    <nav class="product-breadcrumb" aria-label="Breadcrumb">
                        <a href="../home">Home</a>
                        <svg class="product-breadcrumb-separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                        <a href="../products">Products</a>
                        <svg class="product-breadcrumb-separator" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                        <span aria-current="page"><?= store_escape($product['product_name']) ?></span>
                    </nav>
                    <a class="product-back-link" href="../products"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>Back to Products</a>
                </div>

                <section class="product-layout" aria-labelledby="product-title">
                    <div class="product-gallery">
                        <div class="product-gallery-stage">
                            <img
                                class="product-gallery-backdrop"
                                src="<?= store_escape($defaultImageUrl) ?>"
                                alt=""
                                aria-hidden="true">
                            <span class="product-category-tag"><?= store_escape($product['category_name']) ?></span>
                            <?php require __DIR__ . '/../app/views/components/collector-favorite-button.php'; ?>
                            <img
                                class="product-gallery-main"
                                src="<?= store_escape($defaultImageUrl) ?>"
                                alt="<?= store_escape($galleryImages[0]['alt_text'] ?? $product['product_name']) ?>"
                                tabindex="0"
                                fetchpriority="high">
                        </div>
                        <?php if (count($galleryImages) > 1): ?>
                            <div class="product-gallery-carousel" data-gallery-carousel>
                                <button class="product-gallery-arrow" type="button" data-gallery-previous aria-label="Show previous product image">‹</button>
                                <div class="product-gallery-thumbnails" aria-label="Product images" role="group">
                                <?php foreach ($galleryImages as $imageIndex => $image): ?>
                                    <?php $thumbnailUrl = $galleryImageUrl($image['image_url']); ?>
                                    <?php $thumbnailAlt = (string) ($image['alt_text'] ?? '') !== '' ? $image['alt_text'] : $product['product_name'] . ' image ' . ($imageIndex + 1); ?>
                                    <button class="product-gallery-thumbnail<?= $imageIndex === 0 ? ' is-active' : '' ?>" type="button" data-gallery-image data-image-src="<?= store_escape($thumbnailUrl) ?>" data-image-alt="<?= store_escape($thumbnailAlt) ?>" aria-label="Show product image <?= $imageIndex + 1 ?><?= !empty($image['is_temporary']) ? ' (temporary placeholder)' : '' ?>" aria-pressed="<?= $imageIndex === 0 ? 'true' : 'false' ?>">
                                        <img src="<?= store_escape($thumbnailUrl) ?>" alt="" loading="lazy" decoding="async">
                                    </button>
                                <?php endforeach; ?>
                                </div>
                                <button class="product-gallery-arrow" type="button" data-gallery-next aria-label="Show next product image">›</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-detail-info">
                        <div class="product-info-heading">
                            <span class="product-info-category"><?= store_escape($productTalentLabel !== '' ? $productTalentLabel . ' · ' . $product['category_name'] : $product['category_name']) ?></span>
                            <div class="product-title-row">
                                <h1 id="product-title"><?= store_escape($product['product_name']) ?></h1>
                            </div>
                        </div>

                        <?php if (!empty($product['description'])): ?>
                            <div class="product-description">
                                <p class="product-description-full" id="product-description-full" data-product-description><?= nl2br(store_escape($product['description'])) ?></p>
                                <button class="product-description-toggle" type="button" aria-expanded="false" aria-controls="product-description-full" data-description-toggle hidden>View Full Product Description</button>
                            </div>
                        <?php endif; ?>

                        <div class="product-price-block" aria-live="polite">
                            <strong class="product-current-price" data-product-price><?= store_escape(store_format_price((float) $defaultVariant['price'])) ?></strong>
                            <del class="product-compare-price" data-product-compare <?= empty($defaultVariant['compare_at_price']) || (float) $defaultVariant['compare_at_price'] <= (float) $defaultVariant['price'] ? 'hidden' : '' ?>><?= !empty($defaultVariant['compare_at_price']) ? store_escape(store_format_price((float) $defaultVariant['compare_at_price'])) : '' ?></del>
                        </div>

                        <form class="product-purchase-form" action="cart" method="post" data-product-form data-toast-message="<?= store_escape($product['product_name'] . ' added to your bag') ?>">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="variant_id" value="<?= (int) $defaultVariant['variant_id'] ?>" data-variant-input>

                            <?php if (count($variants) > 1): ?>
                                <fieldset class="product-variant-picker">
                                    <legend>Choose edition</legend>
                                    <div class="product-variant-options">
                                        <?php foreach ($variantGroups as $group): ?>
                                            <div class="product-variant-group">
                                                <?php if ($group['name'] !== ''): ?>
                                                    <h3><?= store_escape($group['name']) ?></h3>
                                                <?php endif; ?>
                                                <div class="product-variant-group-options">
                                                    <?php foreach ($group['variants'] as $variant): ?>
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
                                                            <?= store_escape($variant['edition_label']) ?><?= (int) $variant['stock_quantity'] < 1 ? ' · Sold out' : '' ?>
                                                        </button>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            <?php else: ?>
                                <p class="product-single-variant"><span>Edition</span><strong><?= store_escape($defaultVariant['variant_name']) ?></strong></p>
                            <?php endif; ?>

                            <p class="product-stock-status<?= (int) $defaultVariant['stock_quantity'] < 1 ? ' is-sold-out' : '' ?>" data-stock-status role="status">
                                <span aria-hidden="true"></span>
                                <?= (int) $defaultVariant['stock_quantity'] > 0 ? (int) $defaultVariant['stock_quantity'] . ' available' : 'Currently sold out' ?>
                            </p>

                            <aside class="product-trust-note">
                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" />
                                    <path d="m9 12 2 2 4-4" />
                                </svg>
                                <p>Shop with confidence. Your order is protected throughout checkout and fulfillment, carefully packed, and shipped securely to your door.</p>
                            </aside>

                            <div class="product-purchase-actions">
                                <div class="product-quantity-picker" aria-label="Quantity">
                                    <button type="button" data-quantity-change="-1" aria-label="Decrease quantity">−</button>
                                    <input type="number" name="quantity" value="1" min="1" max="<?= max(1, (int) $defaultVariant['stock_quantity']) ?>" inputmode="numeric" aria-label="Quantity" data-quantity-input>
                                    <button type="button" data-quantity-change="1" aria-label="Increase quantity">+</button>
                                </div>
                                <button class="product-add-button" type="submit" <?= (int) $defaultVariant['stock_quantity'] < 1 ? 'disabled' : '' ?> data-add-button data-in-stock-label="<?= (int) ($product['is_preorder'] ?? 0) === 1 ? 'Reserve pre-order' : 'Add to bag' ?>">
                                    <?= (int) $defaultVariant['stock_quantity'] > 0 ? ((int) ($product['is_preorder'] ?? 0) === 1 ? 'Reserve pre-order' : 'Add to bag') : 'Sold out' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
                <?php if ($recommendedProducts): ?>
                    <section class="product-recommendations" aria-labelledby="product-recommendations-title">
                        <div class="product-recommendations-heading">
                            <div>
                                <p class="eyebrow">Related character goods</p>
                                <h2 id="product-recommendations-title">You may also like</h2>
                            </div>
                            <div class="product-recommendations-controls" role="group" aria-label="Recommended products carousel controls">
                                <button type="button" data-recommendations-previous aria-label="Show previous recommended products" disabled>
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                                </button>
                                <button type="button" data-recommendations-next aria-label="Show next recommended products">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                                </button>
                            </div>
                        </div>
                        <div class="product-recommendations-track" data-recommendations-track tabindex="0" role="region" aria-roledescription="carousel" aria-label="Recommended products">
                            <?php foreach ($recommendedProducts as $recommendedProduct): ?>
                                <?php
                                $recommendationImages = json_decode((string) ($recommendedProduct['gallery_images'] ?? '[]'), true);
                                if (is_array($recommendationImages) && isset($recommendationImages[0]['image_url']) && empty($recommendedProduct['image_url'])) {
                                    $recommendedProduct['image_url'] = $recommendationImages[0]['image_url'];
                                    $recommendedProduct['alt_text'] = $recommendationImages[0]['alt_text'] ?? $recommendedProduct['product_name'];
                                }
                                $product = $recommendedProduct;
                                require __DIR__ . '/../app/views/components/product-card.php';
                                ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            <?php else: ?>
                <section class="product-not-found">
                    <h1>Product not found</h1>
                    <p>This product is unavailable. Return to the shop to browse available merchandise.</p>
                    <a class="button-primary dark" href="../products">Back to the shop</a>
                </section>
            <?php endif; ?>
        </main>
    </div>
    <?php
    $cartHref = 'cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php if ($product && $variants): ?>
        <script>
            (() => {
                const galleryMain = document.querySelector('.product-gallery-main');
                const galleryBackdrop = document.querySelector('.product-gallery-backdrop');
                const galleryThumbnailStrip = document.querySelector('.product-gallery-thumbnails');
                const galleryThumbnails = [...document.querySelectorAll('[data-gallery-image]')];
                let activeGalleryIndex = 0;

                galleryThumbnailStrip?.addEventListener('wheel', (event) => {
                    if (galleryThumbnailStrip.scrollWidth <= galleryThumbnailStrip.clientWidth) {
                        return;
                    }

                    const wheelDelta = Math.abs(event.deltaX) > Math.abs(event.deltaY)
                        ? event.deltaX
                        : event.deltaY;
                    const maxScrollLeft = galleryThumbnailStrip.scrollWidth - galleryThumbnailStrip.clientWidth;
                    const canScroll = wheelDelta < 0
                        ? galleryThumbnailStrip.scrollLeft > 0
                        : galleryThumbnailStrip.scrollLeft < maxScrollLeft;
                    if (canScroll && wheelDelta !== 0) {
                        event.preventDefault();
                        galleryThumbnailStrip.scrollLeft += wheelDelta;
                    }
                }, { passive: false });

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
                    thumbnail.addEventListener('mouseenter', () => {
                        showGalleryImage(index);
                    });
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

                const productDescription = document.querySelector('[data-product-description]');
                const descriptionToggle = document.querySelector('[data-description-toggle]');
                if (productDescription && descriptionToggle) {
                    productDescription.classList.add('is-collapsed');
                    if (productDescription.scrollHeight > productDescription.clientHeight + 1) {
                        descriptionToggle.hidden = false;
                        descriptionToggle.addEventListener('click', () => {
                            const expanded = descriptionToggle.getAttribute('aria-expanded') !== 'true';
                            productDescription.classList.toggle('is-collapsed', !expanded);
                            descriptionToggle.setAttribute('aria-expanded', String(expanded));
                            descriptionToggle.textContent = expanded
                                ? 'Hide Full Product Description'
                                : 'View Full Product Description';
                        });
                    } else {
                        productDescription.classList.remove('is-collapsed');
                    }
                }

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
                    addButton.lastChild.textContent = stock > 0 ? ` ${addButton.dataset.inStockLabel}` : ' Sold out';
                    document.querySelectorAll('[data-variant-option]').forEach((item) => {
                        const selected = item.dataset.variantId === option.dataset.variantId;
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

                const recommendationsTrack = document.querySelector('[data-recommendations-track]');
                const recommendationsPrevious = document.querySelector('[data-recommendations-previous]');
                const recommendationsNext = document.querySelector('[data-recommendations-next]');
                if (recommendationsTrack && recommendationsPrevious && recommendationsNext) {
                    const updateRecommendationsControls = () => {
                        const maxScroll = recommendationsTrack.scrollWidth - recommendationsTrack.clientWidth;
                        recommendationsPrevious.disabled = recommendationsTrack.scrollLeft <= 1;
                        recommendationsNext.disabled = recommendationsTrack.scrollLeft >= maxScroll - 1;
                    };
                    const scrollRecommendations = (direction) => {
                        const card = recommendationsTrack.querySelector('.product-card, .drop-card');
                        if (!card) {
                            return;
                        }
                        const gap = Number.parseFloat(getComputedStyle(recommendationsTrack).columnGap) || 0;
                        recommendationsTrack.scrollBy({
                            left: direction * (card.getBoundingClientRect().width + gap),
                            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                        });
                    };

                    recommendationsPrevious.addEventListener('click', () => scrollRecommendations(-1));
                    recommendationsNext.addEventListener('click', () => scrollRecommendations(1));
                    recommendationsTrack.addEventListener('scroll', updateRecommendationsControls, { passive: true });
                    recommendationsTrack.addEventListener('wheel', (event) => {
                        let delta = Math.abs(event.deltaY) > Math.abs(event.deltaX)
                            ? event.deltaY
                            : event.deltaX;
                        if (event.deltaMode === WheelEvent.DOM_DELTA_LINE) {
                            delta *= 16;
                        } else if (event.deltaMode === WheelEvent.DOM_DELTA_PAGE) {
                            delta *= recommendationsTrack.clientWidth;
                        }
                        const maxScroll = recommendationsTrack.scrollWidth - recommendationsTrack.clientWidth;
                        const canScroll = delta > 0
                            ? recommendationsTrack.scrollLeft < maxScroll - 1
                            : recommendationsTrack.scrollLeft > 1;
                        if (maxScroll <= 1 || delta === 0 || !canScroll) {
                            return;
                        }
                        recommendationsTrack.scrollBy({ left: delta, behavior: 'smooth' });
                        event.preventDefault();
                    }, { passive: false });
                    window.addEventListener('resize', updateRecommendationsControls);
                    updateRecommendationsControls();
                }
            })();
        </script>
    <?php endif; ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
