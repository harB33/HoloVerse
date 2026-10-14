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
        <?php
        $activePage = '';
        $showLogin = true;
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="cart-main">
            <section class="cart-panel" aria-labelledby="cart-title">
                <svg class="cart-panel-icon" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 11-1 9" />
                    <path d="m19 11-4-7" />
                    <path d="M2 11h20" />
                    <path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4" />
                    <path d="M4.5 15.5h15" />
                    <path d="m5 11 4-7" />
                    <path d="m9 11 1 9" />
                </svg>
                <span class="cart-eyebrow">HoloVerse checkout</span>
                <h1 id="cart-title"><?= $cartItems ? 'Your cart' : 'Your cart is empty' ?></h1>
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
                                <?php if ($item['image_url']): ?>
                                    <img src="<?= store_escape(store_asset_url($item['image_url'], '../')) ?>" alt="" loading="lazy">
                                <?php endif; ?>
                                <div class="cart-item-details">
                                    <h2><?= store_escape($item['product_name']) ?></h2>
                                    <p><?= store_escape($item['variant_name']) ?> · <?= store_escape($item['currency']) ?> <?= number_format((float) $item['price'], 2) ?></p>
                                </div>
                                <form action="cart.php" method="post" class="cart-item-controls">
                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                    <label>
                                        <span class="sr-only">Quantity</span>
                                        <input type="number" name="quantity" min="1" max="<?= (int) $item['stock_quantity'] ?>" value="<?= (int) $item['quantity'] ?>" required>
                                    </label>
                                    <button type="submit">Update</button>
                                </form>
                                <strong><?= store_escape($item['currency']) ?> <?= number_format((float) $item['price'] * (int) $item['quantity'], 2) ?></strong>
                                <form action="cart.php" method="post">
                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                    <button type="submit" class="cart-remove-button">Remove</button>
                                </form>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <div class="cart-subtotals">
                        <?php foreach ($subtotals as $currency => $subtotal): ?>
                            <p class="cart-subtotal">Subtotal (<?= store_escape($currency) ?>) <strong><?= store_escape($currency) ?> <?= number_format($subtotal, 2) ?></strong></p>
                        <?php endforeach; ?>
                    </div>
                    <p>Checkout and payment processing are not enabled yet.</p>
                <?php else: ?>
                    <p>Browse the current drops to find your next favorite.</p>
                <?php endif; ?>
                <a class="cart-continue-button" href="flash_sale.php">Browse flash sales <span aria-hidden="true">→</span></a>
            </section>
        </main>
    </div>
</body>

</html>
