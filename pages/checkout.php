<?php
require_once __DIR__ . '/../app/store.php';
require_once __DIR__ . '/../app/store/checkout.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: cart');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$intent = $_SESSION['checkout_intent'] ?? [];
$message = '';
$orderNumber = '';
$paymentMethod = '';
$orderSubtotal = 0.0;
$orderTax = 0.0;
$orderShippingFee = 0.0;
$checkoutStep = $_SESSION['checkout_step'] ?? 'information';
$savedShipping = $_SESSION['checkout_shipping'] ?? [];
$shipping = [
    'recipient_name' => (string) ($_POST['recipient_name'] ?? $savedShipping['recipient_name'] ?? $_SESSION['username'] ?? ''),
    'address_line1' => (string) ($_POST['address_line1'] ?? $savedShipping['address_line1'] ?? ''),
    'address_line2' => (string) ($_POST['address_line2'] ?? $savedShipping['address_line2'] ?? ''),
    'city' => (string) ($_POST['city'] ?? $savedShipping['city'] ?? ''),
    'region' => (string) ($_POST['region'] ?? $savedShipping['region'] ?? ''),
    'barangay' => (string) ($_POST['barangay'] ?? $savedShipping['barangay'] ?? ''),
    'postal_code' => (string) ($_POST['postal_code'] ?? $savedShipping['postal_code'] ?? ''),
    'country_code' => strtoupper((string) ($_POST['country_code'] ?? $savedShipping['country_code'] ?? 'PH')),
];

if (isset($_GET['order']) && is_string($_GET['order'])) {
    $requestedOrder = $_GET['order'];
    $statement = $conn->prepare(
        'SELECT order_id, order_number, payment_method, currency, shipping_fee, tax
         FROM order_tbl WHERE order_number = ? AND user_id = ? LIMIT 1'
    );
    $statement->bind_param('si', $requestedOrder, $userId);
    $statement->execute();
    $completedOrder = $statement->get_result()->fetch_assoc();
    $statement->close();
    if ($completedOrder) {
        $orderNumber = $completedOrder['order_number'];
        $paymentMethod = (string) ($completedOrder['payment_method'] ?? '');
        $orderTax = (float) $completedOrder['tax'];
        $orderShippingFee = (float) $completedOrder['shipping_fee'];
        $orderId = (int) $completedOrder['order_id'];
        $subtotalStatement = $conn->prepare(
            'SELECT COALESCE(SUM(unit_price * quantity), 0) AS subtotal
             FROM order_items_tbl WHERE order_id = ?'
        );
        $subtotalStatement->bind_param('i', $orderId);
        $subtotalStatement->execute();
        $orderSubtotal = (float) $subtotalStatement->get_result()->fetch_assoc()['subtotal'];
        $subtotalStatement->close();
    }
}

if ($orderNumber === '' && ($intent['scope'] ?? '') !== 'cart') {
    header('Location: cart');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $orderNumber === '') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'back_to_information') {
        $checkoutStep = 'information';
        $_SESSION['checkout_step'] = $checkoutStep;
    } elseif (!in_array($_POST['action'] ?? '', ['continue_to_payment', 'place_order'], true)) {
        $message = 'That checkout action is not supported.';
    } else {
        $action = (string) $_POST['action'];
        $limits = [
            'recipient_name' => 150,
            'address_line1' => 255,
            'address_line2' => 255,
            'city' => 100,
            'region' => 100,
            'barangay' => 100,
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
            || $shipping['region'] === ''
            || $shipping['barangay'] === ''
            || $shipping['postal_code'] === ''
            || !preg_match('/^[A-Z]{2}$/', $shipping['country_code'])
        )) {
            $message = 'Complete the required shipping address fields.';
        }

        if ($message === '' && $action === 'continue_to_payment') {
            $_SESSION['checkout_shipping'] = $shipping;
            $checkoutStep = 'payment_method';
            $_SESSION['checkout_step'] = $checkoutStep;
        } elseif ($message === '' && $action === 'place_order') {
            $paymentMethod = (string) ($_POST['payment_method'] ?? '');
            if ($checkoutStep !== 'payment_method' || !in_array($paymentMethod, ['card', 'e_wallet', 'cash_on_delivery'], true)) {
                $message = 'Choose a valid payment method before placing your order.';
                $checkoutStep = 'payment_method';
                $_SESSION['checkout_step'] = $checkoutStep;
            }
        }

        if ($message === '' && $action === 'place_order') {
            $paymentMethod = (string) ($_POST['payment_method'] ?? '');
            $cartId = store_current_cart_id($conn, false);
            try {
                if ($cartId < 1) {
                    throw new RuntimeException('Your cart is empty. Add an item before checkout.');
                }

                $order = store_checkout_place_order($conn, $userId, $cartId, $shipping, $paymentMethod);
                $orderNumber = $order['order_number'];
                $items = $order['items'];

                try {
                    $emailStatement = $conn->prepare('SELECT email FROM user_tbl WHERE user_id = ? LIMIT 1');
                    $emailStatement->bind_param('i', $userId);
                    $emailStatement->execute();
                    $account = $emailStatement->get_result()->fetch_assoc();
                    $emailStatement->close();
                    if (!$account || !is_string($account['email'] ?? null)) {
                        throw new RuntimeException('The order owner email address could not be loaded.');
                    }

                    $receiptItems = array_map(
                        static fn (array $item): array => [
                            'product_name' => (string) $item['product_name'],
                            'variant_name' => (string) $item['variant_name'],
                            'sku' => (string) $item['sku'],
                            'unit_price' => (float) $item['price'],
                            'quantity' => (int) $item['quantity'],
                        ],
                        $items
                    );
                    $receiptShipping = $shipping;
                    $receiptShipping['address_line2'] = store_checkout_address_line2($shipping);
                    require_once __DIR__ . '/../phpmailer.php';
                    sendOrderReceipt(
                        (string) $account['email'],
                        $orderNumber,
                        $shipping['recipient_name'],
                        $paymentMethod,
                        $order['currency'],
                        $receiptItems,
                        $receiptShipping,
                        0.0,
                        $order['tax']
                    );
                } catch (Throwable $error) {
                    error_log('HoloVerse order receipt email could not be sent: ' . $error->getMessage());
                }

                header('Location: checkout?order=' . rawurlencode($orderNumber));
                exit;
            } catch (Throwable $error) {
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

if (!in_array($checkoutStep, ['information', 'payment_method'], true)) {
    $checkoutStep = 'information';
}

$items = [];
if ($orderNumber === '') {
    $cartId = store_current_cart_id($conn, false);
    store_clear_expired_cart_items($conn, $cartId);
    if ($cartId > 0) {
        $statement = $conn->prepare(
            "SELECT p.product_name, p.is_preorder, p.expected_ship_date,
                    v.variant_name, v.price, v.currency, v.stock_quantity, ci.quantity,
                    EXTRACT(EPOCH FROM ci.hold_expires_at)::bigint AS hold_expires_at_epoch,
                    i.image_url, i.alt_text
             FROM cart_items_tbl ci
             INNER JOIN product_variants_tbl v ON v.variant_id = ci.variant_id
             INNER JOIN product_tbl p ON p.product_id = v.product_id
             LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
             WHERE ci.cart_id = ? AND p.status = 'active'
             ORDER BY ci.added_at DESC"
        );
        $statement->bind_param('i', $cartId);
        $statement->execute();
        $items = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
        $statement->close();
    }
}
$checkoutSubtotal = 0.0;
foreach ($items as $item) {
    $checkoutSubtotal += round((float) $item['price'] * (int) $item['quantity'], 2);
}
$checkoutTax = store_calculate_sales_tax($items);

$pageTitle = $orderNumber !== '' ? 'Order Confirmed | HoloVerse' : 'Checkout | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="checkout-page home-landing">
    <div class="page-shell checkout-page">
        <?php
        $activePage = '';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>
        <main class="checkout-main landing-shell">
            <?php if ($orderNumber !== ''): ?>
                <section class="checkout-panel checkout-confirmation" aria-labelledby="checkout-title">
                    <span class="drops-eyebrow">Order placed</span>
                    <h1 id="checkout-title">Thank you for your order</h1>
                    <nav class="checkout-progress" aria-label="Checkout progress">
                        <span class="checkout-progress-step is-complete">Information</span>
                        <span class="checkout-progress-step is-complete">Payment Method</span>
                        <span class="checkout-progress-step is-current" aria-current="step">Confirmation</span>
                    </nav>
                    <?php $confirmationMessage = match ($paymentMethod) {
                        'card', 'e_wallet' => 'Your order has been received and is awaiting payment confirmation before fulfillment.',
                        'cash_on_delivery' => 'Your order has been confirmed and is ready for delivery processing.',
                        default => 'Your order has been received and is ready for processing.',
                    }; ?>
                    <p><?= store_escape($confirmationMessage) ?> Order number: <strong><?= store_escape($orderNumber) ?></strong></p>
                    <div class="checkout-totals">
                        <div><span>Subtotal</span><span><?= store_escape(store_format_price($orderSubtotal)) ?></span></div>
                        <div><span>Tax (12%)</span><span><?= store_escape(store_format_price($orderTax)) ?></span></div>
                        <div><span>Shipping fee</span><span><?= store_escape(store_format_price($orderShippingFee)) ?></span></div>
                        <div class="checkout-total"><strong>Total</strong><strong><?= store_escape(store_format_price($orderSubtotal + $orderTax + $orderShippingFee)) ?></strong></div>
                    </div>
                    <?php if ($paymentMethod !== ''): ?>
                        <p>Payment method: <strong><?= store_escape(match ($paymentMethod) {
                            'card' => 'Card',
                            'e_wallet' => 'E-wallet',
                            'cash_on_delivery' => 'Cash on delivery',
                            default => 'Not specified',
                        }) ?></strong>. <?= store_escape(match ($paymentMethod) {
                            'card', 'e_wallet' => 'Payment verification is still pending before the order is fulfilled.',
                            'cash_on_delivery' => 'The order will be processed for delivery.',
                            default => 'Your order is being processed.',
                        }) ?></p>
                    <?php endif; ?>
                    <a class="cart-continue-button" href="products">Continue shopping</a>
                </section>
            <?php elseif (!$items): ?>
                <section class="checkout-panel" aria-labelledby="checkout-title">
                    <h1 id="checkout-title">Nothing to check out</h1>
                    <p><?= $message !== '' ? store_escape($message) : 'Your selected item is no longer available. Return to the shop and try again.' ?></p>
                    <a class="cart-continue-button" href="products">Browse products</a>
                </section>
            <?php else: ?>
                <div class="checkout-layout">
                    <section class="checkout-panel" aria-labelledby="checkout-title">
                        <span class="drops-eyebrow">Account checkout</span>
                        <h1 id="checkout-title"><?= $checkoutStep === 'information' ? 'Shipping details' : 'Payment method' ?></h1>
                        <nav class="checkout-progress" aria-label="Checkout progress">
                            <span class="checkout-progress-step<?= $checkoutStep === 'information' ? ' is-current' : ' is-complete' ?>"<?= $checkoutStep === 'information' ? ' aria-current="step"' : '' ?>>Information</span>
                            <span class="checkout-progress-step<?= $checkoutStep === 'payment_method' ? ' is-current' : '' ?>"<?= $checkoutStep === 'payment_method' ? ' aria-current="step"' : '' ?>>Payment Method</span>
                            <span class="checkout-progress-step">Confirmation</span>
                        </nav>
                        <?php if ($message !== ''): ?>
                            <p class="cart-message" role="alert"><?= store_escape($message) ?></p>
                        <?php endif; ?>
                        <?php if ($checkoutStep === 'information'): ?>
                            <form class="checkout-address-form" action="checkout" method="post">
                                <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                <input type="hidden" name="action" value="continue_to_payment">
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
                                        Region / province
                                        <span class="checkout-location-autocomplete">
                                            <input type="text" id="checkout-region" name="region" required autocomplete="address-level1" aria-autocomplete="list" aria-controls="checkout-region-suggestions" aria-expanded="false" placeholder="Enter or select a region / province" value="<?= store_escape($shipping['region']) ?>">
                                            <span id="checkout-region-suggestions" class="checkout-location-suggestions" role="listbox" hidden></span>
                                        </span>
                                    </label>
                                    <label>
                                        City
                                        <span class="checkout-location-autocomplete">
                                            <input type="text" id="checkout-city" name="city" required autocomplete="address-level2" aria-autocomplete="list" aria-controls="checkout-city-suggestions" aria-expanded="false" placeholder="Enter or select a city / municipality" value="<?= store_escape($shipping['city']) ?>">
                                            <span id="checkout-city-suggestions" class="checkout-location-suggestions" role="listbox" hidden></span>
                                        </span>
                                    </label>
                                </div>
                                <p id="checkout-location-status" class="checkout-location-status" role="status" aria-live="polite"></p>
                                <div class="checkout-address-row">
                                    <label>
                                        Barangay
                                        <span class="checkout-location-autocomplete">
                                            <input type="text" id="checkout-barangay" name="barangay" required autocomplete="address-line3" aria-autocomplete="list" aria-controls="checkout-barangay-suggestions" aria-expanded="false" placeholder="Enter or select a barangay" value="<?= store_escape($shipping['barangay']) ?>">
                                            <span id="checkout-barangay-suggestions" class="checkout-location-suggestions" role="listbox" hidden></span>
                                        </span>
                                    </label>
                                    <label>
                                        Postal code
                                        <input type="text" name="postal_code" maxlength="20" required autocomplete="postal-code" value="<?= store_escape($shipping['postal_code']) ?>">
                                    </label>
                                </div>
                                <button class="cart-checkout-button" type="submit">Proceed to payment method</button>
                            </form>
                        <?php else: ?>
                            <form class="checkout-address-form checkout-payment-form" action="checkout" method="post">
                                <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                <fieldset class="checkout-payment-options">
                                    <legend>Choose a payment method</legend>
                                    <label class="checkout-payment-option">
                                        <input type="radio" name="payment_method" value="card" required>
                                        <span>Card</span>
                                    </label>
                                    <label class="checkout-payment-option">
                                        <input type="radio" name="payment_method" value="e_wallet" required>
                                        <span>E-wallet</span>
                                    </label>
                                    <label class="checkout-payment-option">
                                        <input type="radio" name="payment_method" value="cash_on_delivery" required>
                                        <span>Cash on delivery</span>
                                    </label>
                                </fieldset>
                                <p class="checkout-payment-notice">This checkout records your selection only. No payment will be collected online.</p>
                                <div class="checkout-payment-actions">
                                    <button class="checkout-back-button" type="submit" name="action" value="back_to_information">Previous</button>
                                    <button class="cart-checkout-button" type="submit" name="action" value="place_order">Place order</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </section>

                    <aside class="checkout-panel checkout-order-summary" aria-label="Order summary">
                        <h2>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18" />
                                <path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25" />
                                <circle cx="18" cy="20" r="2" />
                                <circle cx="8" cy="20" r="2" />
                            </svg>
                            Order summary
                        </h2>
                        <div class="checkout-items">
                            <?php foreach ($items as $item): ?>
                                <?php $lineTotal = round((float) $item['price'] * (int) $item['quantity'], 2); ?>
                                <article class="checkout-item">
                                    <div class="checkout-item-image">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="<?= store_escape(store_asset_url($item['image_url'], '../')) ?>" alt="<?= store_escape($item['alt_text'] ?? $item['product_name']) ?>" loading="lazy" decoding="async">
                                        <?php endif; ?>
                                    </div>
                                    <div class="checkout-item-details">
                                        <div class="checkout-item-heading">
                                            <strong><?= (int) $item['quantity'] ?>x <?= store_escape($item['product_name']) ?></strong>
                                            <strong><?= store_escape(store_format_price($lineTotal)) ?></strong>
                                        </div>
                                        <span><?= store_escape($item['variant_name']) ?></span>
                                        <?php if ((int) $item['is_preorder'] === 1): ?>
                                            <span class="preorder-checkout-note">
                                                Pre-order<?= !empty($item['expected_ship_date']) ? ' · Estimated dispatch: ' . store_escape(date('F Y', strtotime($item['expected_ship_date']))) : ' · Estimated dispatch date to be announced' ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($item['hold_expires_at_epoch'] !== null): ?>
                                            <span class="cart-hold-timer" data-hold-expiry="<?= (int) $item['hold_expires_at_epoch'] ?>">
                                                Flash-sale reservation expires in <time><?= gmdate('i:s', STORE_CART_HOLD_SECONDS) ?></time>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="checkout-totals">
                            <div><span>Subtotal</span><span><?= store_escape(store_format_price($checkoutSubtotal)) ?></span></div>
                            <div><span>Tax (12%)</span><span><?= store_escape(store_format_price($checkoutTax)) ?></span></div>
                            <div><span>Shipping fee</span><span>Free</span></div>
                            <div class="checkout-total"><strong>Total</strong><strong><?= store_escape(store_format_price($checkoutSubtotal + $checkoutTax)) ?></strong></div>
                        </div>
                        <p>Shipping is free. Payment is not collected by this checkout.</p>
                    </aside>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    <?php if ($orderNumber === '' && $items): ?>
        <?php if ($checkoutStep === 'information'): ?>
            <script
                src="<?= store_escape($siteRoot) ?>assets/js/checkout-location-selects.js"
                data-initial-region="<?= store_escape($shipping['region']) ?>"
                data-initial-city="<?= store_escape($shipping['city']) ?>"
                data-initial-barangay="<?= store_escape($shipping['barangay']) ?>"
                defer
            ></script>
        <?php endif; ?>
        <script>
            const updateCheckoutHoldTimers = () => {
                let expired = false;
                document.querySelectorAll("[data-hold-expiry]").forEach((timer) => {
                    const secondsLeft = Number(timer.dataset.holdExpiry) - Math.floor(Date.now() / 1000);
                    if (secondsLeft <= 0) {
                        timer.querySelector("time").textContent = "Expired";
                        expired = true;
                        return;
                    }
                    const minutes = Math.floor(secondsLeft / 60);
                    const seconds = secondsLeft % 60;
                    timer.querySelector("time").textContent = `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
                });
                if (expired) {
                    window.location.reload();
                }
            };
            updateCheckoutHoldTimers();
            setInterval(updateCheckoutHoldTimers, 1000);
        </script>
    <?php endif; ?>
</body>

</html>
