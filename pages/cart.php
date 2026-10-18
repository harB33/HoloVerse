<?php
require_once __DIR__ . '/../app/store.php';

$message = '';
$cartId = store_current_cart_id($conn, false);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    store_clear_expired_cart_items($conn, $cartId);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $message = 'Your session expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add') {
                store_add_to_cart($conn, (int) ($_POST['variant_id'] ?? 0), (int) ($_POST['quantity'] ?? 1));
            } elseif ($action === 'checkout') {
                store_clear_expired_cart_items($conn, $cartId);
                if (($_POST['checkout_scope'] ?? '') !== 'cart' || $cartId < 1) {
                    throw new RuntimeException('Add an item to your cart before checkout.');
                }
                $statement = $conn->prepare(
                    'SELECT EXISTS (
                         SELECT 1 FROM cart_items_tbl
                         WHERE cart_id = ?
                     ) AS has_items'
                );
                $statement->bind_param('i', $cartId);
                $statement->execute();
                $hasItems = $statement->get_result()->fetch_assoc()['has_items'];
                $statement->close();
                if (!in_array($hasItems, [true, 't', 1, '1'], true)) {
                    throw new RuntimeException('Add an item to your cart before checkout.');
                }
                unset($_SESSION['checkout_shipping'], $_SESSION['checkout_step']);
                $_SESSION['checkout_intent'] = ['scope' => 'cart'];

                if (!isset($_SESSION['user_id'])) {
                    header('Location: login.php?mode=register&return_to=checkout');
                } else {
                    header('Location: checkout.php');
                }
                exit;
            } elseif ($action === 'update') {
                $itemId = (int) ($_POST['cart_item_id'] ?? 0);
                $quantity = (int) ($_POST['quantity'] ?? 0);
                $quantityAction = $_POST['quantity_action'] ?? '';
                if ($quantityAction === 'decrease') {
                    $quantity = max(1, $quantity - 1);
                } elseif ($quantityAction === 'increase') {
                    $quantity++;
                } elseif ($quantityAction !== '') {
                    throw new RuntimeException('That quantity action is not supported.');
                }

                store_update_cart_item($conn, $cartId, $itemId, $quantity);
            } elseif ($action === 'remove') {
                $itemId = (int) ($_POST['cart_item_id'] ?? 0);
                $statement = $conn->prepare('DELETE FROM cart_items_tbl WHERE cart_item_id = ? AND cart_id = ?');
                $statement->bind_param('ii', $itemId, $cartId);
                $statement->execute();
                $statement->close();
            } else {
                throw new RuntimeException('That cart action is not supported.');
            }
        } catch (InvalidArgumentException | RuntimeException $error) {
            $message = $error->getMessage();
        }

        if ($message === '') {
            header('Location: cart.php');
            exit;
        }
    }
}

$statement = $conn->prepare(
    'SELECT ci.cart_item_id, ci.quantity, p.product_name, v.variant_name, v.price, v.currency,
            GREATEST(v.stock_quantity - COALESCE((
                SELECT SUM(other.quantity)
                FROM cart_items_tbl other
                WHERE other.variant_id = v.variant_id
                  AND other.cart_id <> ci.cart_id
                  AND other.hold_expires_at > statement_timestamp()
            ), 0), 0) AS stock_quantity,
            EXTRACT(EPOCH FROM ci.hold_expires_at)::bigint AS hold_expires_at_epoch,
            v.variant_id, i.image_url
     FROM cart_items_tbl ci
     INNER JOIN product_variants_tbl v ON v.variant_id = ci.variant_id
     INNER JOIN product_tbl p ON p.product_id = v.product_id
     LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
     WHERE ci.cart_id = ?
     ORDER BY ci.added_at DESC'
);
$statement->bind_param('i', $cartId);
$statement->execute();
$cartItems = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
$statement->close();
$subtotals = [];
$totalQuantity = 0;
foreach ($cartItems as $item) {
    $currency = $item['currency'];
    $subtotals[$currency] = ($subtotals[$currency] ?? 0) + (float) $item['price'] * (int) $item['quantity'];
    $totalQuantity += (int) $item['quantity'];
}

$pageTitle = 'Your Cart | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell cart-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = '';
        $showLogin = true;
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>
        <main class="cart-main">
            <?php if ($cartItems): ?>
                <div class="cart-layout">
                    <section class="cart-list-panel" aria-labelledby="cart-title">
                        <header class="cart-heading">
                            <svg class="cart-panel-icon lucide lucide-shopping-cart" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18" />
                                <path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25" />
                                <circle cx="18" cy="20" r="2" />
                                <circle cx="8" cy="20" r="2" />
                            </svg>
                            <h1 id="cart-title">Your Cart</h1>
                        </header>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <p class="cart-guest-notice" role="status">
                                Guest mode: your cart is associated with this browser session.
                                <a href="login.php">Sign in or create an account</a> to link it to your account.
                            </p>
                        <?php endif; ?>
                        <?php if ($message !== ''): ?>
                            <p class="cart-message" role="alert"><?= store_escape($message) ?></p>
                        <?php endif; ?>
                        <div class="cart-items">
                            <?php foreach ($cartItems as $item): ?>
                                <article class="cart-item">
                                    <div class="cart-item-image">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?= store_escape(store_asset_url($item['image_url'], '../')) ?>" alt="" loading="lazy">
                                        <?php endif; ?>
                                    </div>
                                    <div class="cart-item-details">
                                        <div class="cart-item-row">
                                            <h2><?= store_escape($item['product_name']) ?></h2>
                                            <strong class="cart-item-price"><?= store_escape(store_format_price((float) $item['price'] * (int) $item['quantity'])) ?></strong>
                                        </div>
                                        <p><span>Variant:</span> <?= store_escape($item['variant_name']) ?></p>
                                        <?php if ($item['hold_expires_at_epoch'] !== null): ?>
                                            <p class="cart-hold-timer" data-hold-expiry="<?= (int) $item['hold_expires_at_epoch'] ?>">
                                                Flash-sale item reserved for <time><?= gmdate('i:s', STORE_CART_HOLD_SECONDS) ?></time>
                                            </p>
                                        <?php endif; ?>
                                        <div class="cart-item-bottom">
                                            <div class="cart-item-controls">
                                                <form action="cart.php" method="post" class="quantity-picker">
                                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                                    <button type="submit" name="quantity_action" value="decrease" class="quantity-button" aria-label="Decrease quantity">−</button>
                                                    <input type="number" name="quantity" min="1" max="<?= (int) $item['stock_quantity'] ?>" value="<?= (int) $item['quantity'] ?>" required aria-label="Quantity for <?= store_escape($item['product_name']) ?>">
                                                    <button type="submit" name="quantity_action" value="increase" class="quantity-button" aria-label="Increase quantity">+</button>
                                                </form>
                                            </div>
                                            <div class="cart-item-secondary-actions">
                                                <form action="cart.php" method="post" class="cart-remove-form">
                                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                                    <button type="submit" class="cart-action cart-action--remove">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <aside class="cart-summary-panel" aria-label="Order summary">
                        <h2>Order Summary</h2>
                        <div class="cart-summary-list">
                            <?php foreach ($subtotals as $currency => $subtotal): ?>
                                <div class="cart-summary-row">
                                    <span><?= count($subtotals) > 1 ? 'Subtotal (' . store_escape((string) $currency) . ') (3 items)' : 'Subtotal (' . $totalQuantity . ' items)' ?></span>
                                    <strong><?= store_escape(store_format_price($subtotal)) ?></strong>
                                </div>
                            <?php endforeach; ?>
                            <div class="cart-summary-row">
                                <span>Shipping Fee</span>
                                <strong>Free</strong>
                            </div>
                        </div>
                        <?php foreach ($subtotals as $currency => $subtotal): ?>
                            <div class="cart-summary-total">
                                <span>Total</span>
                                <strong><?= store_escape(store_format_price($subtotal)) ?></strong>
                            </div>
                        <?php endforeach; ?>
                        <form action="cart.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <input type="hidden" name="action" value="checkout">
                            <input type="hidden" name="checkout_scope" value="cart">
                            <button type="submit" class="cart-checkout-button">Proceed to Checkout</button>
                        </form>
                    </aside>
                </div>
            <?php else: ?>
                <section class="cart-panel" aria-labelledby="cart-title">
                    <header class="cart-heading">
                        <svg class="cart-panel-icon lucide lucide-shopping-cart" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18" />
                            <path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25" />
                            <circle cx="18" cy="20" r="2" />
                            <circle cx="8" cy="20" r="2" />
                        </svg>
                        <h1 id="cart-title">Your cart is empty</h1>
                    </header>
                    <?php if ($message !== ''): ?>
                        <p class="cart-message" role="alert"><?= store_escape($message) ?></p>
                    <?php endif; ?>
                    <p class="cart-empty-message">Browse the current drops to find your next favorite.</p>
                    <a class="cart-continue-button" href="flash_sale.php">
                        Browse flash sales
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14" />
                            <path d="m12 5 7 7-7 7" />
                        </svg>
                    </a>
                </section>
            <?php endif; ?>
        </main>
    </div>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    <?php if ($cartItems): ?>
        <script>
            const updateCartHoldTimers = () => {
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
            updateCartHoldTimers();
            setInterval(updateCartHoldTimers, 1000);
        </script>
    <?php endif; ?>
</body>

</html>
