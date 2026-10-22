<?php
$flashSaleTabs = [
    'live' => ['label' => 'Live Right Now', 'products' => $flashSaleProducts['live']],
    'starting' => ['label' => 'Starting in 1h', 'products' => $flashSaleProducts['starting']],
    'tomorrow' => ['label' => 'Tomorrow', 'products' => $flashSaleProducts['tomorrow']],
    'archive' => ['label' => 'Vault Archive', 'products' => $flashSaleProducts['archive']],
];
?>
<section class="vault-inventory-ration" aria-labelledby="vault-inventory-title">
    <div class="vault-inventory-header">
        <div class="vault-inventory-title-wrap">
            <span class="vault-inventory-kicker">VAULT INVENTORY RATION</span>
            <h2 id="vault-inventory-title">CURATED ACTIVE DROPS</h2>
        </div>
        <div class="vault-filters" role="tablist" aria-label="Flash sale categories">
            <?php foreach ($flashSaleTabs as $key => $tab): ?>
                <button class="vault-filter<?= $key === 'live' ? ' is-active' : '' ?>" id="flash-tab-<?= store_escape($key) ?>" type="button" role="tab" data-sale-filter="<?= store_escape($key) ?>" aria-controls="flash-sale-products" aria-selected="<?= $key === 'live' ? 'true' : 'false' ?>" tabindex="<?= $key === 'live' ? '0' : '-1' ?>">
                    <?= store_escape($tab['label']) ?> <span>(<?= count($tab['products']) ?>)</span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="catalog-search flash-sale-search-wrap" role="search">
        <label class="sr-only" for="flash-sale-search">Search flash sale products</label>
        <input id="flash-sale-search" type="search" placeholder="Search products, categories, or variants" autocomplete="off">
    </div>

    <div class="vault-product-grid" id="flash-sale-products" role="tabpanel" aria-labelledby="flash-tab-live">
        <?php foreach ($flashSaleTabs as $state => $tab): ?>
            <?php foreach ($tab['products'] as $product): ?>
                <?php
                $stock = (int) $product['stock_quantity'];
                $discountPercent = (int) round((1 - ((float) $product['price'] / (float) $product['compare_at_price'])) * 100);
                $imageUrl = store_asset_url($product['image_url'] ?? 'assets/images/logo.png', '../');
                ?>
                <article class="vault-product-card" data-sale-state="<?= store_escape($state) ?>" data-search="<?= store_escape($product['product_name'] . ' ' . $product['category_name'] . ' ' . $product['variant_name']) ?>" data-variant-id="<?= (int) $product['variant_id'] ?>">
                    <div class="vault-product-visual">
                        <span class="vault-series-tag"><?= store_escape($product['category_name']) ?></span>
                        <span class="vault-product-meta"><?= store_escape($product['variant_name']) ?></span>
                        <button class="vault-favorite" type="button" data-favorite-button aria-label="Save <?= store_escape($product['product_name']) ?>" aria-pressed="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5" />
                            </svg>
                        </button>
                        <img src="<?= store_escape($imageUrl) ?>" alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>" loading="lazy" decoding="async">
                        <div class="vault-stock-badge"><?= $stock === 1 ? 'ONLY 1 UNIT LEFT' : $stock . ' UNITS AVAILABLE' ?></div>
                    </div>
                    <div class="vault-product-info">
                        <small><?= store_escape($product['category_name']) ?></small>
                        <h3><?= store_escape($product['product_name']) ?></h3>
                        <div class="vault-price-row">
                            <strong><?= store_escape(store_format_price((float) $product['price'])) ?></strong>
                            <span><?= store_escape(store_format_price((float) $product['compare_at_price'])) ?></span>
                            <em><?= $discountPercent ?>% off</em>
                        </div>
                        <div class="vault-stats-row">
                            <span class="vault-stat <?= $stock <= 5 ? 'red' : 'blue' ?>"><?= $stock ?> in stock</span>
                            <?php if ($state === 'starting' || $state === 'tomorrow'): ?>
                                <?php $startTime = new DateTimeImmutable($product['starts_at'], new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila')); ?>
                                <span class="vault-stat blue">Starts <?= store_escape($startTime->format('M j, g:i a')) ?></span>
                            <?php elseif ($state === 'archive'): ?>
                                <span class="vault-stat red">Sale ended</span>
                            <?php else: ?>
                                <span class="vault-stat blue">Live now</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($state === 'live' && $stock > 0): ?>
                            <form action="cart" method="post">
                                <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="variant_id" value="<?= (int) $product['variant_id'] ?>">
                                <button class="vault-action-button" type="submit">ADD TO CART</button>
                            </form>
                        <?php else: ?>
                            <button class="vault-action-button" type="button" disabled><?= $state === 'archive' ? 'SALE ENDED' : 'NOT LIVE YET' ?></button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
    <p class="vault-empty-state" data-sale-empty hidden></p>
</section>
