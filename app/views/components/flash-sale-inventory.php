<?php
$flashSaleTabs = [
    'live' => ['label' => 'Live Right Now', 'products' => $flashSaleProducts['live']],
    'starting' => ['label' => 'Starting in 1h', 'products' => $flashSaleProducts['starting']],
    'tomorrow' => ['label' => 'Tomorrow', 'products' => $flashSaleProducts['tomorrow']],
    'archive' => ['label' => 'Vault Archive', 'products' => $flashSaleProducts['archive']],
];
$flashSaleFilterLabels = [
    'live' => 'Live right now',
    'starting' => 'Starting in 1h',
    'tomorrow' => 'Tomorrow',
    'archive' => 'Vault archive',
];
?>
<section class="vault-inventory-ration flash-inventory-section" aria-labelledby="vault-inventory-title">
    <div class="section-heading flash-inventory-heading">
        <div>
            <p class="eyebrow">Scheduled drops &middot; Official hololive production merchandise</p>
            <h2 id="vault-inventory-title">Offers worth a look</h2>
            <p>Explore current drop allocations, upcoming limited windows, and recently completed archive drops.</p>
        </div>
    </div>

    <div class="product-toolbar flash-inventory-toolbar">
        <div class="filter-tabs" role="tablist" aria-label="Flash sale schedules">
            <?php foreach ($flashSaleTabs as $key => $tab): ?>
                <button
                    type="button"
                    class="<?= $key === 'live' ? 'active' : '' ?>"
                    role="tab"
                    aria-selected="<?= $key === 'live' ? 'true' : 'false' ?>"
                    data-sale-tab="<?= store_escape($key) ?>"
                >
                    <?= store_escape($tab['label']) ?>
                    <span class="filter-count"><?= count($tab['products']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <select class="vault-filter sr-only" id="flash-sale-filter" data-sale-filter aria-label="Flash sale category">
            <?php foreach ($flashSaleTabs as $key => $tab): ?>
                <option value="<?= store_escape($key) ?>"<?= $key === 'live' ? ' selected' : '' ?>>
                    <?= store_escape($flashSaleFilterLabels[$key]) ?> (<?= count($tab['products']) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <div class="flash-sale-toolbar-actions">
            <button class="button-text" type="button" data-search-overlay-open>
                <span>Search merch</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
            <div class="flash-sale-carousel-controls" role="group" aria-label="Flash sale products carousel controls">
                <button type="button" data-sale-previous aria-label="Show previous flash sale products" disabled>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
                </button>
                <button type="button" data-sale-next aria-label="Show next flash sale products">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="flash-sale-carousel" data-sale-carousel>
        <div class="vault-product-grid" id="flash-sale-products" aria-label="Flash sale products" tabindex="0" data-sale-track>
            <?php foreach ($flashSaleTabs as $state => $tab): ?>
                <?php foreach ($tab['products'] as $product): ?>
                    <?php
                    $stock = max(0, (int) ($product['stock_quantity'] ?? 0));
                    $price = (float) ($product['price'] ?? 0);
                    $comparePrice = !empty($product['compare_at_price']) && (float) $product['compare_at_price'] > $price
                        ? (float) $product['compare_at_price']
                        : round($price * 1.2, 2);
                    $discountPercent = $comparePrice > 0 ? (int) round((1 - ($price / $comparePrice)) * 100) : 0;
                    $productImages = $flashSaleProductImages[(int) $product['product_id']] ?? [];
                    if (!$productImages) {
                        $productImages = [[
                            'image_url' => store_asset_url((string) ($product['image_url'] ?? 'assets/images/logo.png'), '../'),
                            'alt_text' => (string) ($product['alt_text'] ?? $product['product_name']),
                        ]];
                    }
                    $imageUrl = $productImages[0]['image_url'];
                    $saleStartLabel = !empty($product['starts_at'])
                        ? (new DateTimeImmutable($product['starts_at'], $flashSaleTimezone))->format('M j, g:i a')
                        : '';
                    $productTalent = (string) (!empty($product['talent_name']) ? $product['talent_name'] : ($product['category_name'] ?? 'hololive production'));
                    $productUrl = store_product_url((string) $product['product_name']);
                    $saleSelectionData = [
                        'name' => $product['product_name'],
                        'description' => $product['description'] ?? '',
                        'talent' => $productTalent,
                        'category' => $product['category_name'],
                        'variant' => $product['variant_name'],
                        'stock' => $stock,
                        'price' => store_format_price($price),
                        'comparePrice' => store_format_price($comparePrice),
                        'discount' => $discountPercent,
                        'state' => $state,
                        'variantId' => (int) $product['variant_id'],
                        'startsLabel' => $saleStartLabel,
                        'startsAt' => !empty($product['starts_at'])
                            ? (new DateTimeImmutable($product['starts_at'], $flashSaleTimezone))->format(DateTimeInterface::ATOM)
                            : '',
                        'endsAt' => !empty($product['ends_at'])
                            ? (new DateTimeImmutable($product['ends_at'], $flashSaleTimezone))->format(DateTimeInterface::ATOM)
                            : '',
                        'images' => $productImages,
                        'productUrl' => $productUrl,
                    ];
                    ?>
                    <article
                        class="product-card flash-sale-drop-card"
                        data-sale-state="<?= store_escape($state) ?>"
                        data-variant-id="<?= (int) $product['variant_id'] ?>"
                        data-sale-select
                        tabindex="0"
                        aria-label="View details for <?= store_escape($product['product_name']) ?>"
                        data-sale-product="<?= store_escape(json_encode($saleSelectionData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)) ?>"
                    >
                        <div class="product-image-wrap">
                            <img src="<?= store_escape($imageUrl) ?>" alt="<?= store_escape((string) ($product['alt_text'] ?? $product['product_name'])) ?>" loading="lazy" decoding="async">
                            <?php if ($state === 'live'): ?>
                                <span class="product-badge">-<?= $discountPercent ?>%</span>
                            <?php elseif ($state === 'archive'): ?>
                                <span class="product-badge">Ended</span>
                            <?php else: ?>
                                <span class="product-badge"><?= $saleStartLabel !== '' ? 'Starts ' . store_escape($saleStartLabel) : 'Upcoming' ?></span>
                            <?php endif; ?>
                            <span class="quick-view">Inspect drop</span>
                        </div>

                        <?php
                        $favoriteButtonProduct = $product;
                        require __DIR__ . '/collector-favorite-button.php';
                        ?>

                        <div class="product-info">
                            <p><?= store_escape($productTalent) ?></p>
                            <a class="product-title-link" href="<?= store_escape($productUrl) ?>"><?= store_escape($product['product_name']) ?></a>
                            <span class="merch-status status-flash">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
                                Flash drop &middot; <?= $state === 'live' ? ($stock > 0 ? $stock . ' left' : 'Sold out') : ($state === 'archive' ? 'Ended' : 'Upcoming') ?>
                            </span>
                            <div class="product-meta">
                                <strong><?= store_escape(store_format_price($price)) ?></strong>
                                <del><?= store_escape(store_format_price($comparePrice)) ?></del>
                                <span><?= $stock ?> left</span>
                            </div>

                            <?php if ($state === 'live' && $stock > 0): ?>
                                <form action="/cart" method="post" data-toast-message="<?= store_escape($product['product_name'] . ' added to your bag') ?>">
                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="variant_id" value="<?= (int) $product['variant_id'] ?>">
                                    <button class="add-button" type="submit">
                                        <span>Claim flash price</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                    </button>
                                </form>
                            <?php elseif ($state === 'live'): ?>
                                <button class="add-button" type="button" disabled>
                                    <span>Sold out</span>
                                </button>
                            <?php else: ?>
                                <button class="add-button is-preview-button" type="button" data-sale-select-trigger>
                                    <span><?= $state === 'archive' ? 'View archive' : 'Preview in spotlight' ?></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="empty-search" data-sale-empty hidden>
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <h3 data-sale-empty-title>No scheduled drops</h3>
        <p data-sale-empty-message>There are no drops in this schedule right now.</p>
    </div>
</section>
