<?php if ($products): ?>
    <section class="drops-grid all-products-grid" aria-label="All available products">
        <?php foreach ($products as $product): ?>
            <?php $productInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product['product_name']), 0, 1)); ?>
            <article class="drop-card">
                <a class="drop-card-link" href="product?id=<?= (int) $product['product_id'] ?>" aria-label="View <?= store_escape($product['product_name']) ?>"></a>
                <div class="drop-art">
                    <div class="drop-art-labels<?= (int) $product['stock_quantity'] > 0 ? ' drop-art-labels-no-status' : '' ?>">
                        <?php if ((int) $product['stock_quantity'] <= 0): ?>
                            <span class="drop-status">Sold out</span>
                        <?php endif; ?>
                        <span class="drop-edition"><?= store_escape($product['variant_name']) ?></span>
                    </div>
                    <?php if (!empty($product['image_url'])): ?>
                        <img class="drop-product-image-backdrop" src="<?= store_escape(store_asset_url($product['image_url'], '../')) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                        <img class="drop-product-image is-loading" src="<?= store_escape(store_asset_url($product['image_url'], '../')) ?>" alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>" loading="lazy" decoding="async">
                    <?php endif; ?>
                    <div class="drop-character<?= !empty($product['image_url']) ? ' hidden' : '' ?>"><?= store_escape($productInitial) ?></div>
                </div>
                <div class="drop-details">
                    <span class="drop-category"><?= store_escape($product['category_name']) ?></span>
                    <h2><?= store_escape($product['product_name']) ?></h2>
                    <p class="drop-description"><?= store_escape($product['description'] ?? '') ?></p>
                    <hr class="drop-allocation-divider">
                    <div class="drop-purchase">
                        <div>
                            <span class="drop-price-label"><?= (int) $product['is_preorder'] === 1 ? 'Pre-order price' : 'Price' ?></span>
                            <div class="drop-price-row">
                                <strong><?= store_escape(store_format_price((float) $product['price'])) ?></strong>
                                <div class="drop-allocation"><strong><?= (int) $product['stock_quantity'] ?></strong><span>Left</span></div>
                            </div>
                        </div>
                        <?php if ((int) $product['stock_quantity'] > 0): ?>
                            <form action="cart" method="post">
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
    <?php if ($pageCount > 1): ?>
        <nav class="all-products-pagination" aria-label="Product pages">
            <?php if ($currentPage > 1): ?>
                <a href="?<?= store_escape(http_build_query(['page' => $currentPage - 1] + $activeSearchQuery + ['generation' => $selectedGeneration, 'category' => $selectedCategory ?: ''])) ?>" aria-label="Previous page">Previous</a>
            <?php endif; ?>
            <span>Page <?= $currentPage ?> of <?= $pageCount ?></span>
            <?php if ($currentPage < $pageCount): ?>
                <a href="?<?= store_escape(http_build_query(['page' => $currentPage + 1] + $activeSearchQuery + ['generation' => $selectedGeneration, 'category' => $selectedCategory ?: ''])) ?>" aria-label="Next page">Next</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <p class="all-products-empty" role="status"><?= $productSearch !== '' ? 'No products match your selected filters and search.' : (($selectedGeneration !== '' || $selectedCategory !== 0) ? 'There are no products available for the selected filters.' : 'There are no products available right now.') ?></p>
<?php endif; ?>
