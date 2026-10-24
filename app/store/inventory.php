<?php

function store_record_inventory_movement(
    DatabaseConnection $conn,
    int $variantId,
    string $productName,
    string $variantName,
    string $sku,
    string $movementType,
    int $quantityChange,
    int $previousQuantity,
    int $newQuantity,
    string $reason,
    ?int $userId,
    ?int $orderId = null
): void {
    if ($quantityChange === 0 || $previousQuantity < 0 || $newQuantity < 0) {
        throw new InvalidArgumentException('Inventory movement quantities are invalid.');
    }

    $statement = $conn->prepare(
        'INSERT INTO inventory_movements_tbl
            (variant_id, product_name_snapshot, variant_name_snapshot, sku_snapshot,
             movement_type, quantity_change, previous_quantity, new_quantity, reason, user_id, order_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->bind_param(
        'issssiiisii',
        $variantId,
        $productName,
        $variantName,
        $sku,
        $movementType,
        $quantityChange,
        $previousQuantity,
        $newQuantity,
        $reason,
        $userId,
        $orderId
    );
    $statement->execute();
    $statement->close();
}

function store_adjust_inventory(
    DatabaseConnection $conn,
    int $variantId,
    string $adjustmentType,
    int $quantity,
    string $reason,
    int $userId
): array {
    if ($variantId < 1 || $quantity < 0 || $quantity > 2147483647) {
        throw new InvalidArgumentException('Enter a valid inventory quantity.');
    }
    if (!in_array($adjustmentType, ['restock', 'remove', 'count'], true)) {
        throw new InvalidArgumentException('Choose a valid inventory adjustment.');
    }
    if (trim($reason) === '' || strlen($reason) > 500) {
        throw new InvalidArgumentException('Enter an adjustment reason of no more than 500 characters.');
    }
    if ($adjustmentType !== 'count' && $quantity < 1) {
        throw new InvalidArgumentException('Enter a quantity greater than zero.');
    }

    $conn->begin_transaction();
    try {
        $statement = $conn->prepare(
            "SELECT p.product_name, p.status, v.variant_name, v.sku, v.stock_quantity
             FROM product_variants_tbl v
             INNER JOIN product_tbl p ON p.product_id = v.product_id
             WHERE v.variant_id = ?
             FOR UPDATE OF v"
        );
        $statement->bind_param('i', $variantId);
        $statement->execute();
        $variant = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (!$variant || $variant['status'] !== 'active') {
            throw new RuntimeException('This active item could not be updated. It may have been removed.');
        }

        $previousQuantity = (int) $variant['stock_quantity'];
        $newQuantity = match ($adjustmentType) {
            'restock' => $previousQuantity + $quantity,
            'remove' => $previousQuantity - $quantity,
            'count' => $quantity,
        };
        if ($newQuantity > 2147483647 || $newQuantity < 0) {
            throw new InvalidArgumentException(
                $adjustmentType === 'remove'
                    ? 'The removal quantity cannot exceed current stock.'
                    : 'The resulting stock quantity is outside the supported range.'
            );
        }
        $quantityChange = $newQuantity - $previousQuantity;
        if ($quantityChange === 0) {
            throw new InvalidArgumentException('The counted quantity is already the current stock.');
        }

        $statement = $conn->prepare('UPDATE product_variants_tbl SET stock_quantity = ? WHERE variant_id = ?');
        $statement->bind_param('ii', $newQuantity, $variantId);
        $statement->execute();
        $statement->close();

        $movementType = match ($adjustmentType) {
            'restock' => 'restock',
            'remove' => 'removal',
            'count' => 'count_correction',
        };
        store_record_inventory_movement(
            $conn,
            $variantId,
            (string) $variant['product_name'],
            (string) $variant['variant_name'],
            (string) $variant['sku'],
            $movementType,
            $quantityChange,
            $previousQuantity,
            $newQuantity,
            trim($reason),
            $userId
        );

        $conn->commit();

        return [
            'previous_quantity' => $previousQuantity,
            'new_quantity' => $newQuantity,
            'quantity_change' => $quantityChange,
        ];
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}
