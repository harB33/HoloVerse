<?php
require_once __DIR__ . '/../config/dbconnect.php';

const STORE_CART_HOLD_SECONDS = 300;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function store_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function store_format_price(float $amount): string
{
    return '₱' . number_format($amount, 2);
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

function store_refresh_authenticated_user(DatabaseConnection $conn): void
{
    if (!isset($_SESSION['user_id'])) {
        unset($_SESSION['user_role'], $_SESSION['user_email']);
        return;
    }

    $userId = (int) $_SESSION['user_id'];
    $statement = $conn->prepare('SELECT username, email, user_role FROM user_tbl WHERE user_id = ? LIMIT 1');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $user = $statement->get_result()->fetch_assoc();
    $statement->close();

    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_email'], $_SESSION['user_role']);
        session_regenerate_id(true);
        return;
    }

    $_SESSION['username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['user_role'];
}

function store_has_role(string $role): bool
{
    return isset($_SESSION['user_id'], $_SESSION['user_role'])
        && hash_equals($role, (string) $_SESSION['user_role']);
}

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
        return true;
    }

    $timezone = new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila');
    $now = (new DateTimeImmutable('now', $timezone))->format('Y-m-d H:i:s');
    $statement = $conn->prepare(
        "SELECT
             NOT EXISTS (
                 SELECT 1 FROM flash_sale_tbl
                 WHERE variant_id = ? AND status = 'scheduled'
             )
             OR EXISTS (
                 SELECT 1 FROM flash_sale_tbl
                 WHERE variant_id = ? AND status = 'scheduled'
                   AND starts_at <= ? AND ends_at > ?
             ) AS is_live"
    );
    $statement->bind_param('iiss', $variantId, $variantId, $now, $now);
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

function store_add_to_cart(DatabaseConnection $conn, int $variantId, int $quantity): void
{
    if ($variantId < 1 || $quantity < 1 || $quantity > 99) {
        throw new InvalidArgumentException('Choose a valid item quantity.');
    }

    $cartId = store_current_cart_id($conn);
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

function store_product_rows(DatabaseConnection $conn, string $where = '', string $order = 'p.created_at DESC', int $limit = 0): array
{
    $availableStock = store_available_stock_sql();
    $sql = "SELECT p.product_id, p.product_name, p.description, p.is_preorder, p.expected_ship_date,
                   c.category_name, v.variant_id, v.variant_name, v.price, v.compare_at_price,
                   v.currency, {$availableStock} AS stock_quantity, i.image_url, i.alt_text
            FROM product_tbl p
            INNER JOIN category_tbl c ON c.category_id = p.category_id
            INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
            LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
            WHERE p.status = 'active' " . $where . "
            ORDER BY " . $order . ($limit > 0 ? ' LIMIT ' . $limit : '');
    $result = $conn->query($sql);

    return $result->fetch_all(PDO::FETCH_ASSOC);
}

function store_flash_sale_table_exists(DatabaseConnection $conn): bool
{
    $statement = $conn->prepare(
        "SELECT EXISTS (
             SELECT 1
         FROM information_schema.tables
             WHERE table_schema = current_schema() AND table_name = 'flash_sale_tbl'
         ) AS table_exists"
    );
    $statement->execute();
    $tableExists = $statement->get_result()->fetch_assoc()['table_exists'];
    $exists = in_array($tableExists, [true, 't', 1, '1'], true);
    $statement->close();

    return $exists;
}

function store_flash_sale_rows(DatabaseConnection $conn, string $state, DateTimeImmutable $now): array
{
    $nowValue = $now->format('Y-m-d H:i:s');
    $availableStock = store_available_stock_sql();

    $sql = "SELECT p.product_id, p.product_name, p.description, p.is_preorder,
                   c.category_name, v.variant_id, v.variant_name, v.price, v.compare_at_price,
                   v.currency, {$availableStock} AS stock_quantity, i.image_url, i.alt_text,
                   fs.flash_sale_id, fs.starts_at, fs.ends_at
            FROM product_tbl p
            INNER JOIN category_tbl c ON c.category_id = p.category_id
            INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
            LEFT JOIN product_image_tbl i ON i.product_id = p.product_id AND i.sort_order = 0
            LEFT JOIN flash_sale_tbl fs ON fs.variant_id = v.variant_id AND fs.status = 'scheduled'
            WHERE p.status = 'active'
              AND v.compare_at_price IS NOT NULL
              AND v.compare_at_price > v.price";

    if ($state === 'live') {
        $sql .= " AND {$availableStock} > 0
                  AND (
                      (fs.flash_sale_id IS NULL AND NOT EXISTS (
                          SELECT 1 FROM flash_sale_tbl scheduled
                          WHERE scheduled.variant_id = v.variant_id AND scheduled.status = 'scheduled'
                      ))
                      OR (fs.starts_at <= ? AND fs.ends_at > ?)
                  )
                  ORDER BY fs.ends_at IS NULL, fs.ends_at ASC, p.product_id DESC";
        $statement = $conn->prepare($sql);
        $statement->bind_param('ss', $nowValue, $nowValue);
    } elseif ($state === 'starting') {
        $endOfWindow = $now->modify('+1 hour')->format('Y-m-d H:i:s');
        $sql .= " AND {$availableStock} > 0 AND fs.starts_at > ? AND fs.starts_at <= ?
                  ORDER BY fs.starts_at ASC, p.product_id DESC";
        $statement = $conn->prepare($sql);
        $statement->bind_param('ss', $nowValue, $endOfWindow);
    } elseif ($state === 'tomorrow') {
        $tomorrow = $now->modify('tomorrow')->setTime(0, 0)->format('Y-m-d H:i:s');
        $dayAfter = $now->modify('+2 days')->setTime(0, 0)->format('Y-m-d H:i:s');
        $sql .= " AND {$availableStock} > 0 AND fs.starts_at >= ? AND fs.starts_at < ?
                  ORDER BY fs.starts_at ASC, p.product_id DESC";
        $statement = $conn->prepare($sql);
        $statement->bind_param('ss', $tomorrow, $dayAfter);
    } elseif ($state === 'archive') {
        $sql .= ' AND fs.ends_at <= ? ORDER BY fs.ends_at DESC, p.product_id DESC';
        $statement = $conn->prepare($sql);
        $statement->bind_param('s', $nowValue);
    } else {
        throw new InvalidArgumentException('Choose a valid flash sale state.');
    }

    $statement->execute();
    $rows = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $statement->close();

    return $rows;
}

store_refresh_authenticated_user($conn);
