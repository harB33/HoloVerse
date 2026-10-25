<table class="admin-inventory-table admin-inventory-movement-table">
    <thead>
        <tr>
            <th scope="col">Date</th>
            <th scope="col">Product / variant</th>
            <th scope="col">SKU</th>
            <th scope="col">Action</th>
            <th scope="col">Stock after</th>
            <th scope="col">Pricing action</th>
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
                    'price_update' => 'Pricing update',
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
                    <td data-label="Action">
                        <?php if ($movement['movement_type'] === 'price_update'): ?>
                            <span class="admin-inventory-movement-type admin-inventory-movement-type--price_update"><?= store_escape($movementLabel) ?></span>
                            <span class="admin-inventory-price-transition">
                                <?= store_escape(store_format_price((float) $movement['previous_price'])) ?>
                                <?php if ($movement['previous_compare_at_price'] !== null): ?>
                                    <small>(regular <?= store_escape(store_format_price((float) $movement['previous_compare_at_price'])) ?>)</small>
                                <?php endif; ?>
                                <span aria-hidden="true">→</span>
                                <?= store_escape(store_format_price((float) $movement['new_price'])) ?>
                                <?php if ($movement['new_compare_at_price'] !== null): ?>
                                    <small>(regular <?= store_escape(store_format_price((float) $movement['new_compare_at_price'])) ?>)</small>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="admin-inventory-movement-type admin-inventory-movement-type--<?= store_escape($movement['movement_type']) ?>"><?= store_escape($movementLabel) ?></span>
                            <strong class="admin-inventory-movement-quantity"><?= sprintf('%+d', (int) $movement['quantity_change']) ?></strong>
                            <span class="sr-only"><?= store_escape($movementLabel) ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Stock change">
                        <?php if ($movement['movement_type'] === 'price_update'): ?>
                            <span class="admin-inventory-stock-transition" aria-label="Stock unchanged">—</span>
                        <?php else: ?>
                            <span class="admin-inventory-stock-transition">
                                <span><?= (int) $movement['previous_quantity'] ?></span>
                                <span aria-hidden="true">→</span>
                                <strong><?= (int) $movement['new_quantity'] ?></strong>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Pricing action">
                        <?php if ($movement['movement_type'] === 'price_update'): ?>
                            <span class="admin-inventory-price-change">
                                <span><strong>Sale</strong>
                                    <?= store_escape(store_format_price((float) $movement['previous_price'])) ?>
                                    <span aria-hidden="true">→</span>
                                    <?= store_escape(store_format_price((float) $movement['new_price'])) ?>
                                </span>
                                <span><strong>Regular</strong>
                                    <?= $movement['previous_compare_at_price'] !== null
                                        ? store_escape(store_format_price((float) $movement['previous_compare_at_price']))
                                        : 'Not set' ?>
                                    <span aria-hidden="true">→</span>
                                    <?= $movement['new_compare_at_price'] !== null
                                        ? store_escape(store_format_price((float) $movement['new_compare_at_price']))
                                        : 'Not set' ?>
                                </span>
                            </span>
                        <?php else: ?>
                            <span class="admin-inventory-no-price-change">—</span>
                        <?php endif; ?>
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
                <td class="admin-inventory-empty" colspan="7">No inventory or pricing changes have been recorded yet.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
