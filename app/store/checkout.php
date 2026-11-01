<?php

function store_checkout_should_reserve_stock(string $paymentMethod): bool
{
    return $paymentMethod === 'cash_on_delivery';
}

function store_checkout_address_line2(array $shipping): string
{
    $barangay = trim((string) ($shipping['barangay'] ?? ''));
    $addressLine2 = trim((string) ($shipping['address_line2'] ?? ''));
    $barangayLine = $barangay === ''
        ? ''
        : (preg_match('/^barangay\b/i', $barangay) ? $barangay : 'Barangay ' . $barangay);
    $lines = array_filter([
        $barangayLine,
        $addressLine2,
    ]);
    $formattedAddressLine2 = implode(', ', $lines);

    if (strlen($formattedAddressLine2) > 255) {
        throw new InvalidArgumentException('The barangay and apartment details exceed the address length limit.');
    }

    return $formattedAddressLine2;
}

function store_checkout_place_order(
    DatabaseConnection $conn,
    int $userId,
    int $cartId,
    array $shipping,
    string $paymentMethod
): array {
    if ($cartId < 1) {
        throw new RuntimeException('Your cart is empty. Add an item before checkout.');
    }
    if (!in_array($paymentMethod, ['card', 'e_wallet', 'cash_on_delivery'], true)) {
        throw new InvalidArgumentException('Choose a valid payment method before placing your order.');
    }

    $orderStatus = 'confirmed';
    $shouldReserveStock = store_checkout_should_reserve_stock($paymentMethod);
    $orderNumber = 'HV' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));

    $conn->begin_transaction();
    try {
        $statement = $conn->prepare(
            'SELECT DISTINCT variant_id
             FROM cart_items_tbl
             WHERE cart_id = ?
             ORDER BY variant_id'
        );
        $statement->bind_param('i', $cartId);
        $statement->execute();
        $reservedVariants = $statement->get_result()->fetch_all(PDO::FETCH_COLUMN);
        $statement->close();
        foreach ($reservedVariants as $reservedVariantId) {
            $reservedVariantId = (int) $reservedVariantId;
            $statement = $conn->prepare('SELECT variant_id FROM product_variants_tbl WHERE variant_id = ? FOR UPDATE');
            $statement->bind_param('i', $reservedVariantId);
            $statement->execute();
            $statement->close();
        }

        $statement = $conn->prepare(
            'DELETE FROM cart_items_tbl
             WHERE cart_id = ? AND hold_expires_at IS NOT NULL
               AND hold_expires_at <= statement_timestamp()'
        );
        $statement->bind_param('i', $cartId);
        $statement->execute();
        $statement->close();

        $statement = $conn->prepare(
            'SELECT p.product_name, p.status, v.variant_id, v.variant_name, v.sku,
                    v.price, v.currency,
                    GREATEST(v.stock_quantity - COALESCE((
                        SELECT SUM(other.quantity)
                        FROM cart_items_tbl other
                        WHERE other.variant_id = v.variant_id
                          AND other.cart_id <> ci.cart_id
                          AND other.hold_expires_at > statement_timestamp()
                    ), 0), 0) AS stock_quantity,
                    ci.quantity,
                    ci.hold_expires_at
             FROM cart_items_tbl ci
             INNER JOIN product_variants_tbl v ON v.variant_id = ci.variant_id
             INNER JOIN product_tbl p ON p.product_id = v.product_id
             WHERE ci.cart_id = ?
             ORDER BY v.variant_id
             FOR UPDATE OF ci'
        );
        $statement->bind_param('i', $cartId);
        $statement->execute();
        $items = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
        $statement->close();

        if ($items === []) {
            throw new RuntimeException('Your cart is empty. Add an item before checkout.');
        }

        $currency = (string) $items[0]['currency'];
        foreach ($items as $item) {
            $productName = (string) $item['product_name'];
            $quantity = (int) $item['quantity'];
            $availableQuantity = (int) $item['stock_quantity'];
            if ($item['status'] !== 'active') {
                throw new RuntimeException("{$productName} is no longer available. Remove it from your cart and try again.");
            }
            if ($quantity < 1 || $quantity > 99) {
                throw new RuntimeException("The quantity for {$productName} is invalid. Update your cart and try again.");
            }
            if ($quantity > $availableQuantity) {
                $availabilityMessage = $availableQuantity > 0
                    ? "Only {$availableQuantity} of {$productName} remain in stock, but your cart has {$quantity}. Update the quantity in your cart and try again."
                    : "{$productName} is now out of stock. Remove it from your cart and try again.";
                throw new RuntimeException($availabilityMessage);
            }
            if ((string) $item['currency'] !== $currency) {
                throw new RuntimeException('Items in different currencies cannot be placed in one order. Update your cart and try again.');
            }
        }

        $orderTax = store_calculate_sales_tax($items);
        $statement = $conn->prepare(
            "INSERT INTO order_tbl (user_id, order_number, status, currency, payment_method, shipping_fee, tax)
             VALUES (?, ?, ?, ?, ?, 0, ?) RETURNING order_id"
        );
        $statement->bind_param('issssd', $userId, $orderNumber, $orderStatus, $currency, $paymentMethod, $orderTax);
        $statement->execute();
        $createdOrder = $statement->get_result()->fetch_assoc();
        $orderId = (int) ($createdOrder['order_id'] ?? 0);
        $statement->close();

        if ($orderId < 1) {
            throw new RuntimeException('PostgreSQL did not return the new order ID.');
        }

        $addressType = 'shipping';
        $statement = $conn->prepare(
            'INSERT INTO order_address_tbl
                (order_id, address_type, recipient_name, address_line1, address_line2, city, region, postal_code, country_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $addressLine2 = store_checkout_address_line2($shipping);
        $statement->bind_param(
            'issssssss',
            $orderId,
            $addressType,
            $shipping['recipient_name'],
            $shipping['address_line1'],
            $addressLine2,
            $shipping['city'],
            $shipping['region'],
            $shipping['postal_code'],
            $shipping['country_code']
        );
        $statement->execute();
        $statement->close();

        $statement = $conn->prepare(
            'INSERT INTO order_items_tbl
                (order_id, variant_id, product_name_snapshot, variant_name_snapshot, sku_snapshot, unit_price, quantity)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stockStatement = null;
        if ($shouldReserveStock) {
            $stockStatement = $conn->prepare(
                'UPDATE product_variants_tbl SET stock_quantity = stock_quantity - ?
                 WHERE variant_id = ? AND stock_quantity >= ?
                 RETURNING stock_quantity'
            );
        }

        foreach ($items as $item) {
            $variantId = (int) ($item['variant_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['price'] ?? 0.0);
            $productName = (string) ($item['product_name'] ?? '');
            $variantName = (string) ($item['variant_name'] ?? '');
            $sku = (string) ($item['sku'] ?? '');

            $statement->bind_param('iisssdi', $orderId, $variantId, $productName, $variantName, $sku, $unitPrice, $quantity);
            $statement->execute();

            if ($stockStatement !== null) {
                $stockStatement->bind_param('iii', $quantity, $variantId, $quantity);
                $stockStatement->execute();
                $updatedStock = $stockStatement->get_result()->fetch_assoc();
                $stockStatement->close();
                if (!$updatedStock) {
                    throw new RuntimeException("{$productName} just went out of stock. Update your cart and try again.");
                }
                $newStockQuantity = (int) $updatedStock['stock_quantity'];
                store_record_inventory_movement(
                    $conn,
                    $variantId,
                    $productName,
                    $variantName,
                    $sku,
                    'sale',
                    -$quantity,
                    $newStockQuantity + $quantity,
                    $newStockQuantity,
                    'Stock deducted for order ' . $orderNumber,
                    $userId,
                    $orderId
                );
            }
        }

        $statement->close();
        if ($stockStatement !== null) {
            $stockStatement->close();
        }

        $statement = $conn->prepare("UPDATE cart_tbl SET status = 'checked_out' WHERE cart_id = ? AND user_id = ?");
        $statement->bind_param('ii', $cartId, $userId);
        $statement->execute();
        $statement->close();

        $statement = $conn->prepare('DELETE FROM cart_items_tbl WHERE cart_id = ?');
        $statement->bind_param('i', $cartId);
        $statement->execute();
        $statement->close();

        $conn->commit();
        unset($_SESSION['checkout_intent']);
        unset($_SESSION['checkout_shipping'], $_SESSION['checkout_step']);

        return [
            'order_number' => $orderNumber,
            'items' => $items,
            'currency' => $currency,
            'tax' => $orderTax,
        ];
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}
