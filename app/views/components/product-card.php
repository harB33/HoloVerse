<?php
$cardSiteRoot = isset($siteRoot) && is_string($siteRoot) ? $siteRoot : '';
$cardProductId = (int) ($product['product_id'] ?? 0);
$cardVariantId = (int) ($product['variant_id'] ?? 0);
$cardProductName = (string) ($product['product_name'] ?? 'Official Merchandise');
$cardProductUrl = store_product_url($cardProductName);
$cardCategoryName = (string) ($product['category_name'] ?? 'Merch');
$cardTalentName = trim((string) ($product['talent_name'] ?? ''));
if ($cardTalentName === '') {
    $cardTalentName = $cardCategoryName;
}
$cardPrice = (float) ($product['price'] ?? 0);
$cardCompareAtPrice = !empty($product['compare_at_price']) && (float) $product['compare_at_price'] > $cardPrice
    ? (float) $product['compare_at_price']
    : 0.0;
$cardStockQuantity = array_key_exists('stock_quantity', $product)
    ? max(0, (int) $product['stock_quantity'])
    : 1;
$cardIsPreorder = (int) ($product['is_preorder'] ?? 0) === 1;
$cardIsFlash = isset($liveFlashProductIds[$cardProductId]);
$cardStatus = $cardIsFlash ? 'flash' : ($cardIsPreorder ? 'preorder' : 'ready');
$cardStatusLabel = $cardIsFlash ? 'Flash drop' : ($cardIsPreorder ? 'Pre-order' : 'Ready to ship');
$cardShipWindow = $cardIsPreorder
    ? (!empty($product['expected_ship_date'])
        ? 'Expected ' . date('F Y', strtotime((string) $product['expected_ship_date']))
        : 'Expected Spring 2027')
    : '';
$cardBadge = '';
if ($cardStockQuantity <= 0) {
    $cardBadge = 'Sold out';
} elseif ($cardIsFlash) {
    $cardBadge = 'Flash drop';
} elseif ($cardIsPreorder) {
    $cardBadge = 'Pre-order';
} elseif ($cardStockQuantity > 0 && $cardStockQuantity <= 5) {
    $cardBadge = $cardStockQuantity . ' left';
}
$cardCatalogStatus = $cardBadge === 'Sold out' ? 'sold-out' : $cardStatus;
$cardCatalogStatusLabel = $cardBadge === 'Sold out' ? 'Sold out' : $cardStatusLabel;
$cardRawImageUrl = (string) ($product['image_url'] ?? '');
$cardImageUrl = $cardRawImageUrl !== ''
    ? store_asset_url($cardRawImageUrl, $cardSiteRoot)
    : store_asset_url('assets/images/logo.png', $cardSiteRoot);
$cardFavoriteIds = isset($favoriteProductIds) && is_array($favoriteProductIds) ? $favoriteProductIds : [];
$cardIsFavorite = in_array($cardProductId, $cardFavoriteIds, true);
$cardVariantNote = trim((string) ($product['variant_name'] ?? ''));
if ($cardVariantNote === '' || strtolower($cardVariantNote) === 'default' || strtolower($cardVariantNote) === 'standard') {
    $cardVariantNote = $cardCategoryName;
}
$cardCartAction = '/cart';
$cardFavoriteUrl = store_collector_showcase_url();
$cardCsrfToken = store_csrf_token();
$cardQuickViewPayload = [
    'productId' => $cardProductId,
    'variantId' => $cardVariantId,
    'name' => $cardProductName,
    'talent' => $cardTalentName,
    'category' => $cardCategoryName,
    'priceFormatted' => store_format_price($cardPrice),
    'compareAtFormatted' => $cardCompareAtPrice > 0 ? store_format_price($cardCompareAtPrice) : '',
    'description' => trim((string) ($product['description'] ?? '')) ?: 'Official hololive production merchandise. Designed for fans and produced with authentic character artwork.',
    'image' => $cardImageUrl,
    'status' => $cardStatus,
    'statusLabel' => $cardStatusLabel,
    'shipWindow' => $cardShipWindow,
    'stockQuantity' => $cardStockQuantity,
    'productUrl' => $cardProductUrl,
];
$cardExtraAttrs = '';
if (isset($productCardAttributes) && is_array($productCardAttributes)) {
    foreach ($productCardAttributes as $attrName => $attrVal) {
        $cardExtraAttrs .= ' ' . store_escape((string) $attrName) . '="' . store_escape((string) $attrVal) . '"';
    }
}
?>
<article
    class="product-card<?= !empty($catalogProductCardDesign) ? ' catalog-product-card' : '' ?>"
    data-product-card
    data-product-id="<?= $cardProductId ?>"
    data-category="<?= store_escape($cardCategoryName) ?>"
    data-status="<?= store_escape($cardStatus) ?>"
    data-price="<?= $cardPrice ?>"
    data-name="<?= store_escape(strtolower($cardProductName)) ?>"
    data-talent="<?= store_escape(strtolower($cardTalentName)) ?>"
    data-saved="<?= $cardIsFavorite ? 'true' : 'false' ?>"
    data-quick-view="<?= store_escape((string) json_encode($cardQuickViewPayload, JSON_UNESCAPED_SLASHES)) ?>"<?= $cardExtraAttrs ?>
>
    <?php if (!empty($catalogProductCardDesign)): ?>
        <div class="catalog-product-media">
    <?php endif; ?>
    <button class="product-image-wrap" type="button" data-quick-view-open aria-label="Quick view <?= store_escape($cardProductName) ?>">
        <img src="<?= store_escape($cardImageUrl) ?>" alt="<?= store_escape((string) (($product['alt_text'] ?? '') ?: $cardProductName)) ?>" loading="lazy" decoding="async">
        <?php if ($cardBadge !== ''): ?>
            <span class="product-badge"><?= store_escape($cardBadge) ?></span>
        <?php endif; ?>
        <span class="quick-view">Quick view</span>
    </button>

    <form class="collector-favorite-form" action="<?= store_escape($cardFavoriteUrl) ?>" method="post" data-favorite-form>
        <input type="hidden" name="csrf_token" value="<?= store_escape($cardCsrfToken) ?>">
        <input type="hidden" name="product_id" value="<?= $cardProductId ?>">
        <input type="hidden" name="action" value="<?= $cardIsFavorite ? 'remove' : 'save' ?>">
        <button
            class="collector-favorite-button favorite-button<?= $cardIsFavorite ? ' is-favorite active' : '' ?>"
            type="submit"
            aria-label="<?= $cardIsFavorite ? 'Remove ' : 'Add ' ?><?= store_escape($cardProductName) ?> <?= $cardIsFavorite ? 'from' : 'to' ?> your Collector Showcase"
            aria-pressed="<?= $cardIsFavorite ? 'true' : 'false' ?>"
            title="<?= $cardIsFavorite ? 'Remove from Collector Showcase' : 'Save to Collector Showcase' ?>"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="<?= $cardIsFavorite ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
            </svg>
        </button>
    </form>

    <?php if (!empty($catalogProductCardDesign)): ?>
        <div class="catalog-product-cart-form">
            <?php if ($cardStockQuantity > 0 && $cardVariantId > 0): ?>
                <form action="<?= store_escape($cardCartAction) ?>" method="post" data-toast-message="<?= store_escape($cardProductName . ($cardStatus === 'preorder' ? ' reserved in your bag' : ' added to your bag')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= store_escape($cardCsrfToken) ?>">
                    <input type="hidden" name="variant_id" value="<?= $cardVariantId ?>">
                    <button class="add-button<?= $cardStatus === 'preorder' ? ' preorder-add' : '' ?>" type="submit" name="action" value="add">
                        <span><?= $cardStatus === 'preorder' ? 'Reserve pre-order' : ($cardStatus === 'flash' ? 'Claim flash price' : 'Add to bag') ?></span>
                        <?php if ($cardStatus === 'preorder'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        <?php endif; ?>
                    </button>
                </form>
            <?php else: ?>
                <button class="add-button" type="button" disabled>
                    <span>Sold out</span>
                </button>
            <?php endif; ?>
        </div>
        </div>
    <?php endif; ?>

    <div class="product-info">
        <p><?= store_escape(!empty($catalogProductCardDesign) ? $cardCategoryName : $cardTalentName) ?></p>
        <?php if (!empty($catalogProductCardDesign)): ?>
            <div class="catalog-product-title-row">
                <a class="product-title-link" href="<?= store_escape($cardProductUrl) ?>"><?= store_escape($cardProductName) ?></a>
                <div class="catalog-product-price">
                    <strong><?= store_escape(store_format_price($cardPrice)) ?></strong>
                    <?php if ($cardCompareAtPrice > 0): ?>
                        <del><?= store_escape(store_format_price($cardCompareAtPrice)) ?></del>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <a class="product-title-link" href="<?= store_escape($cardProductUrl) ?>"><?= store_escape($cardProductName) ?></a>
        <?php endif; ?>
        <?php if (empty($catalogProductCardDesign)): ?>
            <span class="merch-status status-<?= store_escape($cardStatus) ?>">
                <?php if ($cardStatus === 'flash'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
                <?php elseif ($cardStatus === 'preorder'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                <?php endif; ?>
                <?= store_escape($cardStatusLabel) ?><?= $cardShipWindow !== '' ? ' · ' . store_escape($cardShipWindow) : '' ?>
            </span>
        <?php endif; ?>
        <div class="product-meta">
            <?php if (empty($catalogProductCardDesign)): ?>
                <strong><?= store_escape(store_format_price($cardPrice)) ?></strong>
                <?php if ($cardCompareAtPrice > 0): ?>
                    <del><?= store_escape(store_format_price($cardCompareAtPrice)) ?></del>
                <?php endif; ?>
            <?php else: ?>
                <span class="merch-status status-<?= store_escape($cardCatalogStatus) ?>">
                    <?php if ($cardCatalogStatus === 'flash'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>
                    <?php elseif ($cardCatalogStatus === 'preorder'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                    <?php endif; ?>
                    <?= store_escape($cardCatalogStatusLabel) ?><?= $cardCatalogStatus === 'preorder' && $cardShipWindow !== '' ? ' · ' . store_escape($cardShipWindow) : '' ?>
                </span>
            <?php endif; ?>
            <span><?= store_escape($cardVariantNote) ?></span>
        </div>

        <?php if (empty($catalogProductCardDesign)): ?>
            <?php if ($cardStockQuantity > 0 && $cardVariantId > 0): ?>
                <form action="<?= store_escape($cardCartAction) ?>" method="post" data-toast-message="<?= store_escape($cardProductName . ($cardStatus === 'preorder' ? ' reserved in your bag' : ' added to your bag')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= store_escape($cardCsrfToken) ?>">
                    <input type="hidden" name="variant_id" value="<?= $cardVariantId ?>">
                    <button class="add-button<?= $cardStatus === 'preorder' ? ' preorder-add' : '' ?>" type="submit" name="action" value="add">
                        <span><?= $cardStatus === 'preorder' ? 'Reserve pre-order' : ($cardStatus === 'flash' ? 'Claim flash price' : 'Add to bag') ?></span>
                        <?php if ($cardStatus === 'preorder'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                        <?php endif; ?>
                    </button>
                </form>
            <?php else: ?>
                <button class="add-button" type="button" disabled>
                    <span>Sold out</span>
                </button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</article>
