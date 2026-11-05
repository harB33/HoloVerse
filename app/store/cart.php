<?php

function store_current_cart_id(DatabaseConnection $conn, bool $create = true): int
{
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    if ($userId !== null) {
        $statement = $conn->prepare("SELECT cart_id FROM cart_tbl WHERE user_id = ? AND status = 'active' LIMIT 1");
        $statement->bind_param('i', $userId);
    } else {
        if (empty($_SESSION['cart_token'])) {
            $_SESSION['cart_token'] = bin2hex(random_bytes(32));
        }
        $token = $_SESSION['cart_token'];
        $statement = $conn->prepare("SELECT cart_id FROM cart_tbl WHERE session_token = ? AND status = 'active' LIMIT 1");
        $statement->bind_param('s', $token);
    }

    $statement->execute();
    $cart = $statement->get_result()->fetch_assoc();
    $statement->close();

    if ($cart) {
        return (int) $cart['cart_id'];
    }

    if (!$create) {
        return 0;
    }

    if ($userId !== null) {
        $statement = $conn->prepare("INSERT INTO cart_tbl (user_id, status) VALUES (?, 'active') RETURNING cart_id");
        $statement->bind_param('i', $userId);
    } else {
        $statement = $conn->prepare("INSERT INTO cart_tbl (session_token, status) VALUES (?, 'active') RETURNING cart_id");
        $statement->bind_param('s', $token);
    }
    $statement->execute();
    $createdCart = $statement->get_result()->fetch_assoc();
    $cartId = (int) ($createdCart['cart_id'] ?? 0);
    if ($cartId < 1) {
        throw new RuntimeException('PostgreSQL did not return the new cart ID.');
    }
    $statement->close();

    return $cartId;
}

function store_cart_item_count(DatabaseConnection $conn, int $cartId): int
{
    if ($cartId < 1) {
        return 0;
    }

    $statement = $conn->prepare('SELECT COALESCE(SUM(quantity), 0) AS item_count FROM cart_items_tbl WHERE cart_id = ?');
    $statement->bind_param('i', $cartId);
    $statement->execute();
    $count = (int) $statement->get_result()->fetch_assoc()['item_count'];
    $statement->close();

    return $count;
}

function store_clear_expired_cart_items(DatabaseConnection $conn, int $cartId): void
{
    if ($cartId < 1) {
        return;
    }

    $statement = $conn->prepare('DELETE FROM cart_items_tbl WHERE cart_id = ? AND hold_expires_at IS NOT NULL AND hold_expires_at <= statement_timestamp()');
    $statement->bind_param('i', $cartId);
    $statement->execute();
    $statement->close();
}

function store_available_stock_sql(): string
{
    return 'GREATEST(v.stock_quantity - COALESCE((
        SELECT SUM(ci.quantity)
        FROM cart_items_tbl ci
        WHERE ci.variant_id = v.variant_id
          AND ci.hold_expires_at > statement_timestamp()
    ), 0), 0)';
}

function store_variant_is_live_flash_sale(DatabaseConnection $conn, int $variantId): bool
{
    $statement = $conn->prepare(
        "SELECT v.compare_at_price IS NOT NULL AND v.compare_at_price > v.price AS is_discounted
         FROM product_variants_tbl v
         INNER JOIN product_tbl p ON p.product_id = v.product_id
         WHERE v.variant_id = ? AND p.status = 'active'"
    );
    $statement->bind_param('i', $variantId);
    $statement->execute();
    $variant = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$variant || !in_array($variant['is_discounted'], [true, 't', 1, '1'], true)) {
        return false;
    }
    if (!store_flash_sale_table_exists($conn)) {
        return false;
    }

    $timezone = new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila');
    $now = (new DateTimeImmutable('now', $timezone))->format('Y-m-d H:i:s');
    $statement = $conn->prepare(
        "SELECT EXISTS (
             SELECT 1 FROM flash_sale_tbl
             WHERE variant_id = ? AND status = 'scheduled'
               AND starts_at <= ? AND ends_at > ?
         ) AS is_live"
    );
    $statement->bind_param('iss', $variantId, $now, $now);
    $statement->execute();
    $isLive = $statement->get_result()->fetch_assoc()['is_live'];
    $statement->close();

    return in_array($isLive, [true, 't', 1, '1'], true);
}

function store_merge_guest_cart(DatabaseConnection $conn, int $userId): void
{
    if (empty($_SESSION['cart_token'])) {
        return;
    }

    $token = $_SESSION['cart_token'];
    $conn->begin_transaction();

    try {
        $statement = $conn->prepare("SELECT cart_id FROM cart_tbl WHERE user_id = ? AND status = 'active' LIMIT 1 FOR UPDATE");
        $statement->bind_param('i', $userId);
        $statement->execute();
        $userCart = $statement->get_result()->fetch_assoc();
        $statement->close();

        $statement = $conn->prepare("SELECT cart_id FROM cart_tbl WHERE session_token = ? AND status = 'active' LIMIT 1 FOR UPDATE");
        $statement->bind_param('s', $token);
        $statement->execute();
        $guestCart = $statement->get_result()->fetch_assoc();
        $statement->close();

        if ($guestCart) {
            $guestCartId = (int) $guestCart['cart_id'];
            if ($userCart) {
                $userCartId = (int) $userCart['cart_id'];
                $statement = $conn->prepare(
                    'INSERT INTO cart_items_tbl (cart_id, variant_id, quantity, hold_expires_at)
                     SELECT ?, ci.variant_id, ci.quantity, ci.hold_expires_at
                     FROM cart_items_tbl ci
                     WHERE ci.cart_id = ?
                       AND (ci.hold_expires_at IS NULL OR ci.hold_expires_at > statement_timestamp())
                     ON CONFLICT (cart_id, variant_id) DO UPDATE SET
                         quantity = LEAST(
                             CASE
                                 WHEN cart_items_tbl.hold_expires_at IS NOT NULL
                                      AND cart_items_tbl.hold_expires_at <= statement_timestamp()
                                 THEN EXCLUDED.quantity
                                 ELSE cart_items_tbl.quantity + EXCLUDED.quantity
                             END,
                             (SELECT stock_quantity FROM product_variants_tbl WHERE variant_id = cart_items_tbl.variant_id)
                         ),
                         hold_expires_at = CASE
                             WHEN cart_items_tbl.hold_expires_at IS NOT NULL
                                  AND cart_items_tbl.hold_expires_at <= statement_timestamp()
                             THEN EXCLUDED.hold_expires_at
                             WHEN cart_items_tbl.hold_expires_at IS NULL THEN EXCLUDED.hold_expires_at
                             WHEN EXCLUDED.hold_expires_at IS NULL THEN cart_items_tbl.hold_expires_at
                             ELSE GREATEST(cart_items_tbl.hold_expires_at, EXCLUDED.hold_expires_at)
                         END'
                );
                $statement->bind_param('ii', $userCartId, $guestCartId);
                $statement->execute();
                $statement->close();

                $statement = $conn->prepare('DELETE FROM cart_tbl WHERE cart_id = ?');
                $statement->bind_param('i', $guestCartId);
            } else {
                $statement = $conn->prepare('UPDATE cart_tbl SET user_id = ?, session_token = NULL WHERE cart_id = ?');
                $statement->bind_param('ii', $userId, $guestCartId);
            }
            $statement->execute();
            $statement->close();
        }

        $conn->commit();
        unset($_SESSION['cart_token']);
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

function store_add_to_cart(DatabaseConnection $conn, int $variantId, int $quantity, int $cartId = 0): int
{
    if ($variantId < 1 || $quantity < 1 || $quantity > 99) {
        throw new InvalidArgumentException('Choose a valid item quantity.');
    }

    if ($cartId < 1) {
        $cartId = store_current_cart_id($conn);
    }
    $conn->begin_transaction();

    try {
        $statement = $conn->prepare(
            "SELECT v.stock_quantity
             FROM product_variants_tbl v
             INNER JOIN product_tbl p ON p.product_id = v.product_id
             WHERE v.variant_id = ? AND p.status = 'active'
             FOR UPDATE OF v"
        );
        $statement->bind_param('i', $variantId);
        $statement->execute();
        $variant = $statement->get_result()->fetch_assoc();
        $statement->close();

        if (!$variant || (int) $variant['stock_quantity'] < 1) {
            throw new RuntimeException('This item is no longer available or is out of stock.');
        }

        $statement = $conn->prepare(
            'SELECT quantity, CASE
                 WHEN hold_expires_at IS NULL THEN 2
                 WHEN hold_expires_at > statement_timestamp() THEN 1
                 ELSE 0
             END AS hold_state
             FROM cart_items_tbl WHERE cart_id = ? AND variant_id = ? FOR UPDATE'
        );
        $statement->bind_param('ii', $cartId, $variantId);
        $statement->execute();
        $existing = $statement->get_result()->fetch_assoc();
        $statement->close();
        if ($existing && (int) $existing['hold_state'] === 0) {
            $statement = $conn->prepare('DELETE FROM cart_items_tbl WHERE cart_id = ? AND variant_id = ?');
            $statement->bind_param('ii', $cartId, $variantId);
            $statement->execute();
            $statement->close();
            $existing = null;
        }
        $isFlashSale = store_variant_is_live_flash_sale($conn, $variantId);

        $statement = $conn->prepare(
            'SELECT COALESCE(SUM(quantity), 0) AS reserved_quantity
             FROM cart_items_tbl
             WHERE variant_id = ? AND cart_id <> ? AND hold_expires_at > statement_timestamp()'
        );
        $statement->bind_param('ii', $variantId, $cartId);
        $statement->execute();
        $otherReservations = (int) $statement->get_result()->fetch_assoc()['reserved_quantity'];
        $statement->close();
        $newQuantity = $quantity + ($existing ? (int) $existing['quantity'] : 0);
        $availableToCart = (int) $variant['stock_quantity'] - $otherReservations;

        if ($newQuantity > $availableToCart) {
            throw new RuntimeException('The requested quantity exceeds the stock available to reserve.');
        }

        if ($existing) {
            if ($isFlashSale && (int) $existing['hold_state'] === 2) {
                $holdDuration = STORE_CART_HOLD_SECONDS;
                $statement = $conn->prepare(
                    "UPDATE cart_items_tbl
                     SET quantity = ?, hold_expires_at = statement_timestamp() + (? * INTERVAL '1 second')
                     WHERE cart_id = ? AND variant_id = ?"
                );
                $statement->bind_param('iiii', $newQuantity, $holdDuration, $cartId, $variantId);
            } else {
                $statement = $conn->prepare('UPDATE cart_items_tbl SET quantity = ? WHERE cart_id = ? AND variant_id = ?');
                $statement->bind_param('iii', $newQuantity, $cartId, $variantId);
            }
        } else {
            $holdDuration = $isFlashSale ? STORE_CART_HOLD_SECONDS : null;
            $statement = $conn->prepare(
                "INSERT INTO cart_items_tbl (cart_id, variant_id, quantity, hold_expires_at)
                 VALUES (?, ?, ?, statement_timestamp() + (? * INTERVAL '1 second'))"
            );
            $statement->bind_param('iiii', $cartId, $variantId, $quantity, $holdDuration);
        }
        $statement->execute();
        $statement->close();
        $conn->commit();
        return $cartId;
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}

function store_update_cart_item(DatabaseConnection $conn, int $cartId, int $itemId, int $quantity): void
{
    if ($cartId < 1 || $itemId < 1 || $quantity < 1 || $quantity > 99) {
        throw new InvalidArgumentException('Choose a valid item quantity.');
    }

    $statement = $conn->prepare('SELECT variant_id FROM cart_items_tbl WHERE cart_item_id = ? AND cart_id = ?');
    $statement->bind_param('ii', $itemId, $cartId);
    $statement->execute();
    $item = $statement->get_result()->fetch_assoc();
    $statement->close();
    if (!$item) {
        throw new RuntimeException('That cart item is no longer available.');
    }
    $variantId = (int) $item['variant_id'];

    $conn->begin_transaction();
    try {
        $statement = $conn->prepare('SELECT stock_quantity FROM product_variants_tbl WHERE variant_id = ? FOR UPDATE');
        $statement->bind_param('i', $variantId);
        $statement->execute();
        $variant = $statement->get_result()->fetch_assoc();
        $statement->close();

        $statement = $conn->prepare(
            'SELECT quantity, CASE
                 WHEN hold_expires_at IS NULL THEN 1
                 WHEN hold_expires_at > statement_timestamp() THEN 1
                 ELSE 0
             END AS hold_active
             FROM cart_items_tbl WHERE cart_item_id = ? AND cart_id = ? AND variant_id = ? FOR UPDATE'
        );
        $statement->bind_param('iii', $itemId, $cartId, $variantId);
        $statement->execute();
        $item = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$item || (int) $item['hold_active'] === 0) {
            throw new RuntimeException('Your 5-minute item reservation expired. Add the item to your cart again.');
        }

        $statement = $conn->prepare(
            'SELECT COALESCE(SUM(quantity), 0) AS reserved_quantity
             FROM cart_items_tbl
             WHERE variant_id = ? AND cart_id <> ? AND hold_expires_at > statement_timestamp()'
        );
        $statement->bind_param('ii', $variantId, $cartId);
        $statement->execute();
        $otherReservations = (int) $statement->get_result()->fetch_assoc()['reserved_quantity'];
        $statement->close();
        if (!$variant || $quantity > (int) $variant['stock_quantity'] - $otherReservations) {
            throw new RuntimeException('Choose a quantity within the stock available to reserve.');
        }

        $statement = $conn->prepare('UPDATE cart_items_tbl SET quantity = ? WHERE cart_item_id = ? AND cart_id = ?');
        $statement->bind_param('iii', $quantity, $itemId, $cartId);
        $statement->execute();
        $statement->close();
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        throw $error;
    }
}


function store_cart_drawer_payload(DatabaseConnection $conn, int $cartId): array
{
    if ($cartId < 1) {
        return [
            'success' => true,
            'count' => 0,
            'items' => [],
            'summaries' => [],
            'subtotal' => store_format_price(0),
            'has_preorder' => false,
            'has_non_preorder' => false,
        ];
    }

    store_clear_expired_cart_items($conn, $cartId);

    $statement = $conn->prepare(
        "SELECT ci.cart_item_id, ci.quantity, p.product_id, p.product_name, p.is_preorder, p.expected_ship_date,
                c.category_name, v.variant_id, v.variant_name, v.price, v.currency,
                (
                    SELECT t.talent_name
                    FROM product_talents_tbl pt
                    INNER JOIN talent_tbl t ON t.talent_id = pt.talent_id
                    WHERE pt.product_id = p.product_id
                    ORDER BY t.talent_id ASC
                    LIMIT 1
                ) AS talent_name,
                GREATEST(v.stock_quantity - COALESCE((
                    SELECT SUM(other.quantity)
                    FROM cart_items_tbl other
                    WHERE other.variant_id = v.variant_id
                      AND other.cart_id <> ci.cart_id
                      AND other.hold_expires_at > statement_timestamp()
                ), 0), 0) AS stock_quantity,
                EXTRACT(EPOCH FROM ci.hold_expires_at)::bigint AS hold_expires_at_epoch,
                i.image_url
         FROM cart_items_tbl ci
         INNER JOIN product_variants_tbl v ON v.variant_id = ci.variant_id
         INNER JOIN product_tbl p ON p.product_id = v.product_id
         INNER JOIN category_tbl c ON c.category_id = p.category_id
         LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
         WHERE ci.cart_id = ?
         ORDER BY ci.added_at DESC"
    );
    $statement->bind_param('i', $cartId);
    $statement->execute();
    $updatedItems = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();

    $responseItems = [];
    $responseSummaries = [];
    $responseQuantitiesByCurrency = [];
    $responseTotalQuantity = 0;
    $totalSubtotal = 0.0;
    $hasPreorder = false;
    $hasNonPreorder = false;

    foreach ($updatedItems as $item) {
        $currency = (string) $item['currency'];
        $quantity = (int) $item['quantity'];
        $price = (float) $item['price'];
        $lineSubtotal = round($price * $quantity, 2);
        $totalSubtotal += $lineSubtotal;
        $responseSummaries[$currency]['subtotal'] = ($responseSummaries[$currency]['subtotal'] ?? 0) + $lineSubtotal;
        $responseSummaries[$currency]['tax'] = ($responseSummaries[$currency]['tax'] ?? 0) + store_calculate_sales_tax([$item]);
        $responseQuantitiesByCurrency[$currency] = ($responseQuantitiesByCurrency[$currency] ?? 0) + $quantity;
        $responseTotalQuantity += $quantity;

        $isPreorder = (int) $item['is_preorder'] === 1;
        $isFlash = $item['hold_expires_at_epoch'] !== null;
        if ($isPreorder) {
            $hasPreorder = true;
        } else {
            $hasNonPreorder = true;
        }
        $status = $isFlash ? 'flash' : ($isPreorder ? 'preorder' : 'ready');
        $statusLabel = $isFlash ? 'Flash drop' : ($isPreorder ? 'Pre-order' : 'Ready to ship');
        $shipWindow = $isPreorder && !empty($item['expected_ship_date'])
            ? 'Estimated ' . date('F Y', strtotime((string) $item['expected_ship_date']))
            : '';

        $responseItems[] = [
            'id' => (int) $item['cart_item_id'],
            'variant_id' => (int) $item['variant_id'],
            'product_name' => (string) $item['product_name'],
            'product_url' => store_product_url((string) $item['product_name']),
            'variant_name' => (string) $item['variant_name'],
            'talent' => (string) ($item['talent_name'] ?: $item['category_name']),
            'category_name' => (string) $item['category_name'],
            'quantity' => $quantity,
            'stock_quantity' => (int) $item['stock_quantity'],
            'unit_price' => store_format_price($price),
            'line_total' => store_format_price($lineSubtotal),
            'line_subtotal_amount' => $lineSubtotal,
            'image_url' => (string) ($item['image_url'] ?: 'assets/images/logo.png'),
            'status' => $status,
            'status_label' => $statusLabel,
            'ship_window' => $shipWindow,
        ];
    }

    $summaries = [];
    foreach ($responseSummaries as $currency => $summary) {
        $summaries[] = [
            'subtotal_label' => count($responseSummaries) > 1
                ? 'Subtotal (' . $currency . ') (' . $responseQuantitiesByCurrency[$currency] . ' items)'
                : 'Subtotal (' . $responseTotalQuantity . ' items)',
            'subtotal' => store_format_price($summary['subtotal']),
            'tax' => store_format_price($summary['tax']),
            'total' => store_format_price($summary['subtotal'] + $summary['tax']),
        ];
    }

    return [
        'success' => true,
        'count' => $responseTotalQuantity,
        'items' => $responseItems,
        'summaries' => $summaries,
        'subtotal' => store_format_price($totalSubtotal),
        'currency_rate' => store_currency_rate(),
        'currency_symbol' => store_currency_symbol(),
        'currency_fraction_digits' => store_current_currency() === 'JPY' ? 0 : 2,
        'has_preorder' => $hasPreorder,
        'has_non_preorder' => $hasNonPreorder,
    ];
}
