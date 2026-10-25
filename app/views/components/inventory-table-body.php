<tbody>
    <?php if ($inventory): ?>
        <?php foreach ($inventory as $item): ?>
            <tr>
                <td data-label="Product"><span class="admin-inventory-product"><?= store_escape($item['product_name']) ?></span></td>
                <td data-label="Variant"><?= store_escape($item['variant_name']) ?></td>
                <td data-label="SKU"><span class="admin-inventory-sku"><?= store_escape($item['sku']) ?></span></td>
                <td data-label="Pricing">
                    <span class="admin-inventory-price"><?= store_escape(store_format_price((float) $item['price'])) ?></span>
                    <?php if ($item['compare_at_price'] !== null && (float) $item['compare_at_price'] > (float) $item['price']): ?>
                        <span class="admin-inventory-compare-price"><?= store_escape(store_format_price((float) $item['compare_at_price'])) ?> regular</span>
                        <?php $discountPercent = (int) round((1 - (float) $item['price'] / (float) $item['compare_at_price']) * 100); ?>
                        <span class="admin-inventory-sale-ready"><?= $discountPercent ?>% off</span>
                    <?php else: ?>
                        <span class="admin-inventory-no-sale">No sale price</span>
                    <?php endif; ?>
                </td>
                <td data-label="Current stock">
                    <span class="admin-status<?= (int) $item['stock_quantity'] > 0 ? ' admin-status--active' : ' admin-status--draft' ?>">
                        <?= (int) $item['stock_quantity'] > 0 ? (int) $item['stock_quantity'] : 'Sold out' ?>
                    </span>
                </td>
                <td data-label="Stock action">
                    <details class="admin-inventory-adjustment">
                        <summary>Adjust stock</summary>
                        <form class="admin-restock-form" method="post" action="inventory">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="adjust_stock">
                            <input type="hidden" name="variant_id" value="<?= (int) $item['variant_id'] ?>">
                            <input type="hidden" name="page" value="<?= $currentPage ?>">
                            <input type="hidden" name="search" value="<?= store_escape($inventorySearch) ?>">
                            <input type="hidden" name="history_page" value="<?= $historyPage ?>">
                            <label for="adjustment-type-<?= (int) $item['variant_id'] ?>">Adjustment</label>
                            <select id="adjustment-type-<?= (int) $item['variant_id'] ?>" name="adjustment_type" required>
                                <option value="restock">Add units</option>
                                <option value="remove">Remove units</option>
                                <option value="count">Set counted stock</option>
                            </select>
                            <label for="adjustment-quantity-<?= (int) $item['variant_id'] ?>">Quantity</label>
                            <input id="adjustment-quantity-<?= (int) $item['variant_id'] ?>" name="quantity" type="number" min="0" max="2147483647" step="1" value="1" required>
                            <label for="adjustment-reason-<?= (int) $item['variant_id'] ?>">Reason</label>
                            <textarea id="adjustment-reason-<?= (int) $item['variant_id'] ?>" name="reason" rows="2" maxlength="500" placeholder="Why is this stock changing?" required></textarea>
                            <button type="submit">Save adjustment</button>
                        </form>
                    </details>
                </td>
                <td data-label="Pricing action">
                    <details class="admin-inventory-adjustment">
                        <summary>Edit pricing</summary>
                        <form class="admin-restock-form admin-price-form" method="post" action="inventory">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="update_pricing">
                            <input type="hidden" name="variant_id" value="<?= (int) $item['variant_id'] ?>">
                            <input type="hidden" name="page" value="<?= $currentPage ?>">
                            <input type="hidden" name="search" value="<?= store_escape($inventorySearch) ?>">
                            <input type="hidden" name="history_page" value="<?= $historyPage ?>">
                            <p class="admin-price-form-product"><?= store_escape($item['product_name'] . ' / ' . $item['variant_name']) ?></p>
                            <label for="sale-price-<?= (int) $item['variant_id'] ?>">Sale price (PHP)</label>
                            <input id="sale-price-<?= (int) $item['variant_id'] ?>" name="price" type="number" min="0.01" max="99999999.99" step="0.01" value="<?= store_escape((string) $item['price']) ?>" required>
                            <label for="compare-at-price-<?= (int) $item['variant_id'] ?>">Regular price (PHP)</label>
                            <input id="compare-at-price-<?= (int) $item['variant_id'] ?>" name="compare_at_price" type="number" min="0.01" max="99999999.99" step="0.01" value="<?= $item['compare_at_price'] !== null ? store_escape((string) $item['compare_at_price']) : '' ?>" placeholder="Optional — must be higher">
                            <output class="admin-price-discount-preview" data-discount-preview aria-live="polite">Enter a higher regular price to calculate the discount.</output>
                            <small>Set a regular price above the sale price to make this item eligible for flash sales.</small>
                            <button type="submit">Save pricing</button>
                        </form>
                    </details>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td class="admin-inventory-empty" colspan="7"><?= $inventorySearch !== '' ? 'No inventory variants match your search.' : 'There are no active product variants to manage.' ?></td>
        </tr>
    <?php endif; ?>
</tbody>
