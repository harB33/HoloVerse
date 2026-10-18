<?php
require_once __DIR__ . '/../app/store.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: cart.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$intent = $_SESSION['checkout_intent'] ?? [];
$message = '';
$orderNumber = '';
$shipping = [
    'recipient_name' => (string) ($_POST['recipient_name'] ?? $_SESSION['username'] ?? ''),
    'address_line1' => (string) ($_POST['address_line1'] ?? ''),
    'address_line2' => (string) ($_POST['address_line2'] ?? ''),
    'city' => (string) ($_POST['city'] ?? ''),
    'region' => (string) ($_POST['region'] ?? ''),
    'postal_code' => (string) ($_POST['postal_code'] ?? ''),
    'country_code' => strtoupper((string) ($_POST['country_code'] ?? 'PH')),
];

if (isset($_GET['order']) && is_string($_GET['order'])) {
    $requestedOrder = $_GET['order'];
    $statement = $conn->prepare('SELECT order_number FROM order_tbl WHERE order_number = ? AND user_id = ? LIMIT 1');
    $statement->bind_param('si', $requestedOrder, $userId);
    $statement->execute();
    $completedOrder = $statement->get_result()->fetch_assoc();
    $statement->close();
    if ($completedOrder) {
        $orderNumber = $completedOrder['order_number'];
    }
}

if ($orderNumber === '' && ($intent['scope'] ?? '') !== 'cart') {
    header('Location: cart.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $orderNumber === '') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') !== 'place_order') {
        $message = 'That checkout action is not supported.';
    } else {
        $limits = [
            'recipient_name' => 150,
            'address_line1' => 255,
            'address_line2' => 255,
            'city' => 100,
            'region' => 100,
            'postal_code' => 20,
        ];
        foreach ($limits as $field => $maxLength) {
            $shipping[$field] = trim($shipping[$field]);
            if (strlen($shipping[$field]) > $maxLength) {
                $message = 'One or more shipping address fields are too long.';
                break;
            }
        }
        if ($message === '' && (
            $shipping['recipient_name'] === ''
            || $shipping['address_line1'] === ''
            || $shipping['city'] === ''
            || $shipping['postal_code'] === ''
            || !preg_match('/^[A-Z]{2}$/', $shipping['country_code'])
        )) {
            $message = 'Complete the required shipping address fields.';
        }

        if ($message === '') {
            $cartId = store_current_cart_id($conn, false);
            try {
                $conn->begin_transaction();
                if ($cartId > 0) {
                    $statement = $conn->prepare(
                        'SELECT p.product_name, p.status, v.variant_id, v.variant_name, v.sku,
                                v.price, v.currency, v.stock_quantity, ci.quantity
                         FROM cart_items_tbl ci
                         INNER JOIN product_variants_tbl v ON v.variant_id = ci.variant_id
                         INNER JOIN product_tbl p ON p.product_id = v.product_id
                         WHERE ci.cart_id = ?
                         FOR UPDATE'
                    );
                    $statement->bind_param('i', $cartId);
                } else {
                    throw new RuntimeException('Your cart is empty. Add an item before checkout.');
                }

                $statement->execute();
                $items = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
                $statement->close();

                if (!$items) {
                    throw new RuntimeException('Your cart is empty. Add an item before checkout.');
                }

                $currency = (string) $items[0]['currency'];
                foreach ($items as $item) {
                    if (
                        $item['status'] !== 'active'
                        || (int) $item['quantity'] < 1
                        || (int) $item['quantity'] > 99
                        || (int) $item['quantity'] > (int) $item['stock_quantity']
                        || $item['currency'] !== $currency
                    ) {
                        throw new RuntimeException('An item is unavailable or no longer has enough stock. Update your cart and try again.');
                    }
                }

                $orderNumber = 'HV' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
                $statement = $conn->prepare(
                    "INSERT INTO order_tbl (user_id, order_number, status, currency, shipping_fee, tax)
                     VALUES (?, ?, 'pending', ?, 0, 0) RETURNING order_id"
                );
                $statement->bind_param('iss', $userId, $orderNumber, $currency);
                $statement->execute();
                $createdOrder = $statement->get_result()->fetch_assoc();
                $orderId = (int) ($createdOrder['order_id'] ?? 0);
                if ($orderId < 1) {
                    throw new RuntimeException('PostgreSQL did not return the new order ID.');
                }
                $statement->close();

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
                $stockStatement = $conn->prepare(
                    'UPDATE product_variants_tbl SET stock_quantity = stock_quantity - ?
                     WHERE variant_id = ? AND stock_quantity >= ?'
                );
                foreach ($items as $item) {
                    $variantId = (int) $item['variant_id'];
                    $quantity = (int) $item['quantity'];
                    $unitPrice = (float) $item['price'];
                    $productName = (string) $item['product_name'];
                    $variantName = (string) $item['variant_name'];
                    $sku = (string) $item['sku'];
                    $statement->bind_param('iisssdi', $orderId, $variantId, $productName, $variantName, $sku, $unitPrice, $quantity);
                    $statement->execute();

                    $stockStatement->bind_param('iii', $quantity, $variantId, $quantity);
                    $stockStatement->execute();
                    if ($stockStatement->affected_rows !== 1) {
                        throw new RuntimeException('An item is no longer in stock. Update your cart and try again.');
                    }
                }
                $statement->close();
                $stockStatement->close();

                $statement = $conn->prepare("UPDATE cart_tbl SET status = 'checked_out' WHERE cart_id = ? AND user_id = ?");
                $statement->bind_param('ii', $cartId, $userId);
                $statement->execute();
                $statement->close();

                $conn->commit();
                unset($_SESSION['checkout_intent']);
                header('Location: checkout.php?order=' . rawurlencode($orderNumber));
                exit;
            } catch (Throwable $error) {
                $conn->rollback();
                if ($error instanceof InvalidArgumentException || $error instanceof RuntimeException) {
                    $orderNumber = '';
                    $message = $error->getMessage();
                } else {
                    throw $error;
                }
            }
        }
    }
}

$items = [];
if ($orderNumber === '') {
    $cartId = store_current_cart_id($conn, false);
    if ($cartId > 0) {
        $statement = $conn->prepare(
            "SELECT p.product_name, v.variant_name, v.price, v.currency, v.stock_quantity, ci.quantity
             FROM cart_items_tbl ci
             INNER JOIN product_variants_tbl v ON v.variant_id = ci.variant_id
             INNER JOIN product_tbl p ON p.product_id = v.product_id
             WHERE ci.cart_id = ? AND p.status = 'active'
             ORDER BY ci.added_at DESC"
        );
        $statement->bind_param('i', $cartId);
        $statement->execute();
        $items = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
        $statement->close();
    }
}

$pageTitle = $orderNumber !== '' ? 'Order Confirmed | HoloVerse' : 'Checkout | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell checkout-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = '';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>
        <main class="checkout-main">
            <?php if ($orderNumber !== ''): ?>
                <section class="checkout-panel checkout-confirmation" aria-labelledby="checkout-title">
                    <span class="drops-eyebrow">Order placed</span>
                    <h1 id="checkout-title">Thank you for your order</h1>
                    <p>Your order is recorded as pending. Order number: <strong><?= store_escape($orderNumber) ?></strong></p>
                    <a class="cart-continue-button" href="products.php">Continue shopping</a>
                </section>
            <?php elseif (!$items): ?>
                <section class="checkout-panel" aria-labelledby="checkout-title">
                    <h1 id="checkout-title">Nothing to check out</h1>
                    <p><?= $message !== '' ? store_escape($message) : 'Your selected item is no longer available. Return to the shop and try again.' ?></p>
                    <a class="cart-continue-button" href="products.php">Browse products</a>
                </section>
            <?php else: ?>
                <div class="checkout-layout">
                    <section class="checkout-panel" aria-labelledby="checkout-title">
                        <span class="drops-eyebrow">Account checkout</span>
                        <h1 id="checkout-title">Shipping details</h1>
                        <?php if ($message !== ''): ?>
                            <p class="cart-message" role="alert"><?= store_escape($message) ?></p>
                        <?php endif; ?>
                        <form class="checkout-address-form" action="checkout.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="place_order">
                            <label>
                                Recipient name
                                <input type="text" name="recipient_name" maxlength="150" required value="<?= store_escape($shipping['recipient_name']) ?>">
                            </label>
                            <label>
                                Address
                                <input type="text" name="address_line1" maxlength="255" required autocomplete="address-line1" value="<?= store_escape($shipping['address_line1']) ?>">
                            </label>
                            <label>
                                Apartment, suite, etc. (optional)
                                <input type="text" name="address_line2" maxlength="255" autocomplete="address-line2" value="<?= store_escape($shipping['address_line2']) ?>">
                            </label>
                            <div class="checkout-address-row">
                                <label>
                                    City
                                    <input type="text" name="city" maxlength="100" required autocomplete="address-level2" value="<?= store_escape($shipping['city']) ?>">
                                </label>
                                <label>
                                    Region / province
                                    <input type="text" name="region" maxlength="100" autocomplete="address-level1" value="<?= store_escape($shipping['region']) ?>">
                                </label>
                            </div>
                            <div class="checkout-address-row">
                                <label>
                                    Postal code
                                    <input type="text" name="postal_code" maxlength="20" required autocomplete="postal-code" value="<?= store_escape($shipping['postal_code']) ?>">
                                </label>
                                <label>
                                    Country code
                                    <input type="text" name="country_code" minlength="2" maxlength="2" pattern="[A-Za-z]{2}" required autocomplete="country" value="<?= store_escape($shipping['country_code']) ?>">
                                </label>
                            </div>
                            <button class="cart-checkout-button" type="submit">Place order</button>
                        </form>
                    </section>

                    <aside class="checkout-panel checkout-order-summary" aria-label="Order summary">
                        <h2>Order summary</h2>
                        <?php $checkoutTotal = 0.0; ?>
                        <?php foreach ($items as $item): ?>
                            <?php $lineTotal = (float) $item['price'] * (int) $item['quantity']; $checkoutTotal += $lineTotal; ?>
                            <div class="checkout-item">
                                <div>
                                    <strong><?= store_escape($item['product_name']) ?></strong>
                                    <span><?= store_escape($item['variant_name']) ?> · Qty <?= (int) $item['quantity'] ?></span>
                                </div>
                                <strong><?= store_escape(store_format_price($lineTotal)) ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <div class="cart-summary-total">
                            <span>Total</span>
                            <strong><?= store_escape(store_format_price($checkoutTotal)) ?></strong>
                        </div>
                        <p>Shipping is free. Payment is not collected by this checkout.</p>
                    </aside>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
