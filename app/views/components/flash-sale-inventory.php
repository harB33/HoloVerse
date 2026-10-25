<?php
$flashSaleTabs = [
    'live' => ['label' => 'Live Right Now', 'products' => $flashSaleProducts['live']],
    'starting' => ['label' => 'Starting in 1h', 'products' => $flashSaleProducts['starting']],
    'tomorrow' => ['label' => 'Tomorrow', 'products' => $flashSaleProducts['tomorrow']],
    'archive' => ['label' => 'Vault Archive', 'products' => $flashSaleProducts['archive']],
];
$flashSaleFilterLabels = ['live' => 'Live now', 'starting' => 'Starting soon', 'tomorrow' => 'Tomorrow', 'archive' => 'Archive'];
?>
<section class="vault-inventory-ration" aria-labelledby="vault-inventory-title">
    <div class="vault-inventory-header">
        <div class="vault-inventory-title-wrap">
            <span class="vault-inventory-kicker">Flash sale</span>
            <h2 id="vault-inventory-title">Offers worth a look</h2>
        </div>
        <div class="vault-filters" role="tablist" aria-label="Flash sale categories">
            <?php foreach ($flashSaleTabs as $key => $tab): ?>
                <button class="vault-filter<?= $key === 'live' ? ' is-active' : '' ?>" id="flash-tab-<?= store_escape($key) ?>" type="button" role="tab" data-sale-filter="<?= store_escape($key) ?>" aria-controls="flash-sale-products" aria-selected="<?= $key === 'live' ? 'true' : 'false' ?>" tabindex="<?= $key === 'live' ? '0' : '-1' ?>">
                    <?= store_escape($flashSaleFilterLabels[$key]) ?> <span><?= count($tab['products']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flash-sale-toolbar">
        <div class="flash-sale-carousel-controls" role="group" aria-label="Flash sale products carousel controls">
            <button type="button" data-sale-previous aria-label="Show previous flash sale products" disabled>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            </button>
            <button type="button" data-sale-next aria-label="Show next flash sale products">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
            </button>
        </div>
    </div>
    <div class="flash-sale-carousel" data-sale-carousel>
        <div class="vault-product-grid" id="flash-sale-products" role="tabpanel" aria-labelledby="flash-tab-live" tabindex="0" data-sale-track>
            <?php foreach ($flashSaleTabs as $state => $tab): ?>
                <?php foreach ($tab['products'] as $product): ?>
                    <?php
                    $stock = (int) $product['stock_quantity'];
                    $discountPercent = (int) round((1 - ((float) $product['price'] / (float) $product['compare_at_price'])) * 100);
                    $productImages = $flashSaleProductImages[(int) $product['product_id']] ?? [];
                    if (!$productImages) {
                        $productImages = [[
                            'image_url' => store_asset_url($product['image_url'] ?? 'assets/images/logo.png', '../'),
                            'alt_text' => $product['alt_text'] ?? $product['product_name'],
                        ]];
                    }
                    $imageUrl = $productImages[0]['image_url'];
                    $saleStartLabel = !empty($product['starts_at'])
                        ? (new DateTimeImmutable($product['starts_at'], $flashSaleTimezone))->format('M j, g:i a')
                        : '';
                    $saleSelectionData = [
                        'name' => $product['product_name'],
                        'description' => $product['description'] ?? '',
                        'category' => $product['category_name'],
                        'variant' => $product['variant_name'],
                        'stock' => $stock,
                        'price' => store_format_price((float) $product['price']),
                        'comparePrice' => store_format_price((float) $product['compare_at_price']),
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
                    ];
                    ?>
                    <article class="drop-card flash-sale-drop-card" data-sale-state="<?= store_escape($state) ?>" data-variant-id="<?= (int) $product['variant_id'] ?>" data-sale-select tabindex="0" aria-label="View details for <?= store_escape($product['product_name']) ?>" data-sale-product="<?= store_escape(json_encode($saleSelectionData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR)) ?>">
                    <div class="drop-art">
                        <div class="drop-art-labels<?= $state === 'live' ? ' drop-art-labels-no-status' : '' ?>">
                            <?php if ($state !== 'live'): ?>
                                <span class="drop-status"><?= $state === 'archive' ? 'Sale ended' : 'Coming soon' ?></span>
                            <?php endif; ?>
                            <span class="drop-edition"><?= store_escape($product['variant_name']) ?></span>
                        </div>
                        <?php require __DIR__ . '/collector-favorite-button.php'; ?>
                        <img class="drop-product-image-backdrop" src="<?= store_escape($imageUrl) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                        <img class="drop-product-image" src="<?= store_escape($imageUrl) ?>" alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>" loading="lazy" decoding="async">
                        <div class="drop-character hidden"></div>
                    </div>
                    <div class="drop-details">
                        <span class="drop-category"><?= store_escape($product['category_name']) ?></span>
                        <h3><?= store_escape($product['product_name']) ?></h3>
                        <?php if (!empty($product['description'])): ?>
                            <p class="drop-description"><?= store_escape($product['description']) ?></p>
                        <?php else: ?>
                            <p class="drop-description"></p>
                        <?php endif; ?>
                        <hr class="drop-allocation-divider">
                        <div class="drop-purchase flash-sale-drop-purchase">
                            <div>
                                <span class="drop-price-label">Sale price</span>
                                <div class="drop-price-row flash-sale-drop-price-row">
                                    <strong><?= store_escape(store_format_price((float) $product['price'])) ?></strong>
                                    <del><?= store_escape(store_format_price((float) $product['compare_at_price'])) ?></del>
                                    <em><?= $discountPercent ?>% off</em>
                                </div>
                                <div class="drop-allocation"><strong><?= $stock ?></strong><span>Left</span></div>
                            </div>
                            <?php if ($state === 'starting' || $state === 'tomorrow' || $state === 'archive'): ?>
                                <div class="flash-sale-drop-status">
                                    <?php if ($state === 'starting' || $state === 'tomorrow'): ?>
                                        <?php $startTime = new DateTimeImmutable($product['starts_at'], new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila')); ?>
                                        <span class="vault-stat blue">Starts <?= store_escape($startTime->format('M j, g:i a')) ?></span>
                                    <?php else: ?>
                                        <span class="vault-stat red">Sale ended</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($state === 'live' && $stock > 0): ?>
                                <form action="cart" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="variant_id" value="<?= (int) $product['variant_id'] ?>">
                                    <button type="submit">Add to cart</button>
                                </form>
                            <?php else: ?>
                                <button type="button" disabled><?= $state === 'archive' ? 'Sale ended' : 'Not live yet' ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    </article>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="vault-empty-state" data-sale-empty hidden></p>
</section>
