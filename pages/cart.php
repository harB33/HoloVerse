<?php
require_once __DIR__ . '/../app/store.php';

$cartId = store_current_cart_id($conn, false);
$requestMode = (string) ($_GET['mode'] ?? '');
$requestMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$isAjaxRequest = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== '' && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || in_array($requestMode, ['count', 'drawer'], true);

if ($requestMethod !== 'POST') {
    store_clear_expired_cart_items($conn, $cartId);
}

if ($requestMethod === 'GET' && in_array($requestMode, ['count', 'drawer'], true)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(store_cart_drawer_payload($conn, store_current_cart_id($conn, false)));
    exit;
}

if ($requestMethod === 'GET') {
    header('Location: /home');
    exit;
}

if ($requestMethod !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit('Method Not Allowed');
}

if (!store_verify_csrf()) {
    http_response_code(403);
    $message = 'Your session expired. Refresh the page and try again.';
} else {
    $action = $_POST['action'] ?? '';
    $message = '';

    try {
        if ($action === 'add') {
            store_add_to_cart($conn, (int) ($_POST['variant_id'] ?? 0), (int) ($_POST['quantity'] ?? 1));
        } elseif ($action === 'checkout') {
            store_clear_expired_cart_items($conn, $cartId);
            if (($_POST['checkout_scope'] ?? '') !== 'cart' || $cartId < 1) {
                throw new RuntimeException('Add an item to your cart before checkout.');
            }

            $selectedCartItemIds = $_POST['checkout_item_ids'] ?? null;
            if (!is_array($selectedCartItemIds) || $selectedCartItemIds === []) {
                throw new RuntimeException('Select at least one item to check out.');
            }

            $normalizedCartItemIds = [];
            foreach ($selectedCartItemIds as $selectedCartItemId) {
                if (!is_string($selectedCartItemId) || !ctype_digit($selectedCartItemId) || (int) $selectedCartItemId < 1) {
                    throw new RuntimeException('The selected cart items are invalid. Please try again.');
                }
                $normalizedCartItemIds[] = (int) $selectedCartItemId;
            }
            if (count(array_unique($normalizedCartItemIds)) !== count($normalizedCartItemIds)) {
                throw new RuntimeException('The selected cart items are invalid. Please try again.');
            }

            $selectedCartItemSql = implode(',', $normalizedCartItemIds);
            $statement = $conn->prepare(
                'SELECT cart_item_id, variant_id
                 FROM cart_items_tbl
                 WHERE cart_id = ? AND cart_item_id IN (' . $selectedCartItemSql . ')'
            );
            $statement->bind_param('i', $cartId);
            $statement->execute();
            $selectedItems = $statement->get_result()->fetch_all(PDO::FETCH_ASSOC);
            $statement->close();

            if (count($selectedItems) !== count($normalizedCartItemIds)) {
                throw new RuntimeException('One or more selected items are no longer in your cart. Refresh and try again.');
            }

            unset($_SESSION['checkout_shipping'], $_SESSION['checkout_step']);
            $_SESSION['checkout_intent'] = [
                'scope' => 'cart',
                'variant_ids' => array_map(
                    static fn (array $item): int => (int) $item['variant_id'],
                    $selectedItems
                ),
            ];

            if (!isset($_SESSION['user_id'])) {
                header('Location: login?mode=register&return_to=checkout');
            } else {
                header('Location: checkout');
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
}

if ($message !== '') {
    if ($isAjaxRequest) {
        if (http_response_code() !== 403) {
            http_response_code(400);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);
        exit;
    }

    if (http_response_code() !== 403) {
        http_response_code(400);
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

if ($isAjaxRequest) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(store_cart_drawer_payload($conn, store_current_cart_id($conn, false)));
    exit;
}

header('Location: /home');
exit;
