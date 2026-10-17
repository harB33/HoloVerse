<?php
require_once __DIR__ . '/../app/store.php';

$message = '';
$cartId = store_current_cart_id($conn, false);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $message = 'Your session expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add') {
                store_add_to_cart($conn, (int) ($_POST['variant_id'] ?? 0), (int) ($_POST['quantity'] ?? 1));
            } elseif ($action === 'update') {
                $itemId = (int) ($_POST['cart_item_id'] ?? 0);
                $quantity = (int) ($_POST['quantity'] ?? 0);
                $statement = $conn->prepare(
                    'SELECT ci.variant_id, pv.stock_quantity
                     FROM cart_items_tbl ci
                     INNER JOIN product_variants_tbl pv ON pv.variant_id = ci.variant_id
                     WHERE ci.cart_item_id = ? AND ci.cart_id = ?'
                );
                $statement->bind_param('ii', $itemId, $cartId);
                $statement->execute();
                $item = $statement->get_result()->fetch_assoc();
                $statement->close();

                if (!$item || $quantity < 1 || $quantity > 99 || $quantity > (int) $item['stock_quantity']) {
                    throw new RuntimeException('Choose a quantity within the available stock.');
                }

                $statement = $conn->prepare('UPDATE cart_items_tbl SET quantity = ? WHERE cart_item_id = ? AND cart_id = ?');
                $statement->bind_param('iii', $quantity, $itemId, $cartId);
                $statement->execute();
                $statement->close();
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
            v.stock_quantity, v.variant_id, i.image_url
     FROM cart_items_tbl ci
     INNER JOIN product_variants_tbl v ON v.variant_id = ci.variant_id
     INNER JOIN product_tbl p ON p.product_id = v.product_id
     LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
     WHERE ci.cart_id = ?
     ORDER BY ci.added_at DESC'
);
$statement->bind_param('i', $cartId);
$statement->execute();
$cartItems = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();
$subtotals = [];
foreach ($cartItems as $item) {
    $currency = $item['currency'];
    $subtotals[$currency] = ($subtotals[$currency] ?? 0) + (float) $item['price'] * (int) $item['quantity'];
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
            <section class="cart-panel" aria-labelledby="cart-title">
                <header class="cart-heading">
                    <svg class="cart-panel-icon lucide lucide-shopping-cart" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18" />
                        <path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25" />
                        <circle cx="18" cy="20" r="2" />
                        <circle cx="8" cy="20" r="2" />
                    </svg>
                    <h1 id="cart-title"><?= $cartItems ? 'Order Summary' : 'Your cart is empty' ?></h1>
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
                <?php if ($cartItems): ?>
                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <article class="cart-item">
                                <div class="cart-item-image">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?= store_escape(store_asset_url($item['image_url'], '../')) ?>" alt="" loading="lazy">
                                    <?php endif; ?>
                                </div>
                                <div class="cart-item-details">
                                    <h2><?= (int) $item['quantity'] ?>x <?= store_escape($item['product_name']) ?></h2>
                                    <p><span>Variant:</span> <?= store_escape($item['variant_name']) ?></p>
                                    <div class="cart-item-actions">
                                        <form action="cart.php" method="post" class="cart-item-controls">
                                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                            <label>
                                                <span class="sr-only">Quantity for <?= store_escape($item['product_name']) ?></span>
                                                <input type="number" name="quantity" min="1" max="<?= (int) $item['stock_quantity'] ?>" value="<?= (int) $item['quantity'] ?>" required>
                                            </label>
                                            <button type="submit">Update</button>
                                        </form>
                                        <form action="cart.php" method="post" class="cart-remove-form">
                                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                            <button type="submit" class="cart-remove-button">Remove</button>
                                        </form>
                                    </div>
                                </div>
                                <strong class="cart-item-price"><?= store_escape(store_format_price((float) $item['price'] * (int) $item['quantity'])) ?></strong>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="cart-totals">
                        <?php foreach ($subtotals as $currency => $subtotal): ?>
                            <p class="cart-total-row">
                                <span><?= count($subtotals) > 1 ? 'Subtotal (' . store_escape((string) $currency) . ')' : 'Subtotal' ?></span>
                                <strong><?= store_escape(store_format_price($subtotal)) ?></strong>
                            </p>
                        <?php endforeach; ?>
                        <p class="cart-total-row"><span>Shipping Fee:</span><strong>Free</strong></p>
                        <?php foreach ($subtotals as $currency => $subtotal): ?>
                            <p class="cart-total-row cart-total-final">
                                <span><?= count($subtotals) > 1 ? 'Total (' . store_escape((string) $currency) . ')' : 'Total' ?></span>
                                <strong><?= store_escape(store_format_price($subtotal)) ?></strong>
                            </p>
                        <?php endforeach; ?>
                    </div>
                    <p class="cart-checkout-note">Checkout and payment processing are not enabled yet.</p>
                <?php else: ?>
                    <p class="cart-empty-message">Browse the current drops to find your next favorite.</p>
                <?php endif; ?>
                <a class="cart-continue-button" href="flash_sale.php">
                    Browse flash sales
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                        <path d="m12 5 7 7-7 7" />
                    </svg>
                </a>
            </section>
        </main>
    </div>
</body>

</html>
