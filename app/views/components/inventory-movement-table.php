<table class="admin-inventory-table admin-inventory-movement-table">
    <thead>
        <tr>
            <th scope="col">Date</th>
            <th scope="col">Product / variant</th>
            <th scope="col">SKU</th>
            <th scope="col">Change</th>
            <th scope="col">Stock after</th>
            <th scope="col">Reason / reference</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($inventoryMovements): ?>
            <?php foreach ($inventoryMovements as $movement): ?>
                <?php
                $movementLabel = match ($movement['movement_type']) {
                    'initial_stock' => 'Initial stock',
                    'restock' => 'Restock',
                    'removal' => 'Removal',
                    'count_correction' => 'Count correction',
                    'sale' => 'Order sale',
                    default => 'Adjustment',
                };
                $actorLabel = is_string($movement['username'] ?? null)
                    ? $movement['username']
                    : (is_string($movement['order_number'] ?? null) ? 'Order processing' : 'System');
                ?>
                <tr>
                    <td data-label="Date"><?= store_escape(date('Y-m-d H:i', strtotime($movement['created_at']))) ?></td>
                    <td data-label="Product / variant">
                        <span class="admin-inventory-product"><?= store_escape($movement['product_name_snapshot']) ?></span>
                        <?= store_escape($movement['variant_name_snapshot']) ?>
                    </td>
                    <td data-label="SKU"><span class="admin-inventory-sku"><?= store_escape($movement['sku_snapshot']) ?></span></td>
                    <td data-label="Movement">
                        <span class="admin-inventory-movement-type admin-inventory-movement-type--<?= store_escape($movement['movement_type']) ?>"><?= store_escape($movementLabel) ?></span>
                        <strong class="admin-inventory-movement-quantity"><?= sprintf('%+d', (int) $movement['quantity_change']) ?></strong>
                        <span class="sr-only"><?= store_escape($movementLabel) ?></span>
                    </td>
                    <td data-label="Stock change">
                        <span class="admin-inventory-stock-transition">
                            <span><?= (int) $movement['previous_quantity'] ?></span>
                            <span aria-hidden="true">→</span>
                            <strong><?= (int) $movement['new_quantity'] ?></strong>
                        </span>
                    </td>
                    <td data-label="Reason / reference">
                        <?= store_escape($movement['reason']) ?>
                        <span><?= store_escape($actorLabel) ?></span>
                        <?php if (is_string($movement['order_number'] ?? null)): ?>
                            <span>Order <?= store_escape($movement['order_number']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td class="admin-inventory-empty" colspan="6">No inventory movements have been recorded yet.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
