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
                <td data-label="Restock">
                    <form class="admin-restock-form" method="post" action="inventory">
                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                        <input type="hidden" name="variant_id" value="<?= (int) $item['variant_id'] ?>">
                        <input type="hidden" name="page" value="<?= $currentPage ?>">
                        <input type="hidden" name="search" value="<?= store_escape($inventorySearch) ?>">
                        <label class="sr-only" for="restock-<?= (int) $item['variant_id'] ?>">Units to add for <?= store_escape($item['product_name'] . ' ' . $item['variant_name']) ?></label>
                        <input id="restock-<?= (int) $item['variant_id'] ?>" name="restock_quantity" type="number" min="1" max="2147483647" step="1" value="1" required>
                        <button type="submit">Add stock</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td class="admin-inventory-empty" colspan="5"><?= $inventorySearch !== '' ? 'No inventory variants match your search.' : 'There are no active product variants to manage.' ?></td>
        </tr>
    <?php endif; ?>
</tbody>
