<?php
require_once __DIR__ . '/../config/dbconnect.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function store_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function store_asset_url(string $path, string $siteRoot = ''): string
{
    return preg_match('/^https?:\/\//i', $path) ? $path : $siteRoot . ltrim($path, '/');
}

function store_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function store_verify_csrf(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function store_current_cart_id(mysqli $conn, bool $create = true): int
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
        $statement = $conn->prepare("INSERT INTO cart_tbl (user_id, status) VALUES (?, 'active')");
        $statement->bind_param('i', $userId);
    } else {
        $statement = $conn->prepare("INSERT INTO cart_tbl (session_token, status) VALUES (?, 'active')");
        $statement->bind_param('s', $token);
    }
    $statement->execute();
    $cartId = (int) $conn->insert_id;
    $statement->close();

    return $cartId;
}

function store_merge_guest_cart(mysqli $conn, int $userId): void
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
                    'INSERT INTO cart_items_tbl (cart_id, variant_id, quantity)
                     SELECT ?, ci.variant_id, ci.quantity FROM cart_items_tbl ci WHERE ci.cart_id = ?
                     ON DUPLICATE KEY UPDATE quantity = LEAST(
                         cart_items_tbl.quantity + VALUES(quantity),
                         (SELECT stock_quantity FROM product_variants_tbl WHERE variant_id = cart_items_tbl.variant_id)
                     )'
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

function store_add_to_cart(mysqli $conn, int $variantId, int $quantity): void
{
    if ($variantId < 1 || $quantity < 1 || $quantity > 99) {
        throw new InvalidArgumentException('Choose a valid item quantity.');
    }

    $statement = $conn->prepare(
        "SELECT v.stock_quantity
         FROM product_variants_tbl v
         INNER JOIN product_tbl p ON p.product_id = v.product_id
         WHERE v.variant_id = ? AND p.status = 'active'"
    );
    $statement->bind_param('i', $variantId);
    $statement->execute();
    $variant = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$variant || (int) $variant['stock_quantity'] < 1) {
        throw new RuntimeException('This item is no longer available or is out of stock.');
    }

    $cartId = store_current_cart_id($conn);
    $statement = $conn->prepare('SELECT quantity FROM cart_items_tbl WHERE cart_id = ? AND variant_id = ?');
    $statement->bind_param('ii', $cartId, $variantId);
    $statement->execute();
    $existing = $statement->get_result()->fetch_assoc();
    $statement->close();
    $newQuantity = $quantity + ($existing ? (int) $existing['quantity'] : 0);

    if ($newQuantity > (int) $variant['stock_quantity']) {
        throw new RuntimeException('The requested quantity exceeds the available stock.');
    }

    if ($existing) {
        $statement = $conn->prepare('UPDATE cart_items_tbl SET quantity = ? WHERE cart_id = ? AND variant_id = ?');
        $statement->bind_param('iii', $newQuantity, $cartId, $variantId);
    } else {
        $statement = $conn->prepare('INSERT INTO cart_items_tbl (cart_id, variant_id, quantity) VALUES (?, ?, ?)');
        $statement->bind_param('iii', $cartId, $variantId, $quantity);
    }
    $statement->execute();
    $statement->close();
}

function store_product_rows(mysqli $conn, string $where = '', string $order = 'p.created_at DESC', int $limit = 0): array
{
    $sql = "SELECT p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date,
                   c.category_name, v.variant_id, v.variant_name, v.price, v.compare_at_price,
                   v.currency, v.stock_quantity, i.image_url, i.alt_text
            FROM product_tbl p
            INNER JOIN category_tbl c ON c.category_id = p.category_id
            INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
            LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
            WHERE p.status = 'active' " . $where . "
            ORDER BY " . $order . ($limit > 0 ? ' LIMIT ' . $limit : '');
    $result = $conn->query($sql);

    return $result->fetch_all(MYSQLI_ASSOC);
}
