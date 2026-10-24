<?php

function store_checkout_order_status_for_payment(string $paymentMethod): string
{
    return $paymentMethod === 'cash_on_delivery' ? 'pending' : 'pending_payment';
}

function store_checkout_should_reserve_stock(string $paymentMethod): bool
{
    return $paymentMethod === 'cash_on_delivery';
}

function store_checkout_place_order(
    DatabaseConnection $conn,
    int $userId,
    int $cartId,
    array $shipping,
    string $paymentMethod,
    array $items
): string {
    if ($cartId < 1) {
        throw new RuntimeException('Your cart is empty. Add an item before checkout.');
    }
    if ($items === []) {
        throw new RuntimeException('Your cart is empty. Add an item before checkout.');
    }

    $currency = (string) ($items[0]['currency'] ?? '');
    foreach ($items as $item) {
        $productName = (string) ($item['product_name'] ?? '');
        $quantity = (int) ($item['quantity'] ?? 0);
        $availableQuantity = (int) ($item['stock_quantity'] ?? 0);

        if (($item['status'] ?? '') !== 'active') {
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
        if ((string) ($item['currency'] ?? '') !== $currency) {
            throw new RuntimeException('Items in different currencies cannot be placed in one order. Update your cart and try again.');
        }
    }

    $orderStatus = store_checkout_order_status_for_payment($paymentMethod);
    $shouldReserveStock = store_checkout_should_reserve_stock($paymentMethod);
    $orderTax = store_calculate_sales_tax($items);
    $orderNumber = 'HV' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));

    $conn->begin_transaction();
    try {
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
        $statement->bind_param(
            'issssssss',
            $orderId,
            $addressType,
            $shipping['recipient_name'],
            $shipping['address_line1'],
            $shipping['address_line2'],
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
                 WHERE variant_id = ? AND stock_quantity >= ?'
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
                if ($stockStatement->affected_rows !== 1) {
                    throw new RuntimeException("{$productName} just went out of stock. Update your cart and try again.");
                }
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

        return $orderNumber;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}
