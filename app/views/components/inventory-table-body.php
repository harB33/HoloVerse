<tbody>
    <?php if ($inventory): ?>
        <?php foreach ($inventory as $item): ?>
            <tr>
                <td data-label="Product"><span class="admin-inventory-product"><?= store_escape($item['product_name']) ?></span></td>
                <td data-label="Variant"><?= store_escape($item['variant_name']) ?></td>
                <td data-label="SKU"><span class="admin-inventory-sku"><?= store_escape($item['sku']) ?></span></td>
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
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td class="admin-inventory-empty" colspan="5"><?= $inventorySearch !== '' ? 'No inventory variants match your search.' : 'There are no active product variants to manage.' ?></td>
        </tr>
    <?php endif; ?>
</tbody>
