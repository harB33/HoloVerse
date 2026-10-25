<?php
require_once __DIR__ . '/../app/store.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

if (!store_has_role('admin')) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Access denied</title></head><body style="font-family: Arial, sans-serif; padding: 48px;">';
    echo '<h1>Access denied</h1><p>You do not have permission to manage inventory.</p>';
    echo '</body></html>';
    exit;
}

$notice = '';
$noticeIsError = false;
$pageSize = 10;
$searchParameter = $_GET['search'] ?? $_POST['search'] ?? '';
$inventorySearch = is_string($searchParameter) ? trim($searchParameter) : '';
$requestedPage = $_GET['page'] ?? $_POST['page'] ?? '1';
$requestedPage = is_string($requestedPage) ? filter_var($requestedPage, FILTER_VALIDATE_INT) : false;
$requestedPage = $requestedPage === false || $requestedPage < 1 ? 1 : $requestedPage;
$historyPageInput = $_GET['history_page'] ?? $_POST['history_page'] ?? '1';
$historyPageInput = is_string($historyPageInput) ? filter_var($historyPageInput, FILTER_VALIDATE_INT) : false;
$historyPage = $historyPageInput === false || $historyPageInput < 1 ? 1 : $historyPageInput;
$flashSaleTimezone = new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila');
$inventoryNow = new DateTimeImmutable('now', $flashSaleTimezone);

$inventoryWhere = "p.status = 'active'";
if ($inventorySearch !== '') {
    $inventoryWhere .= ' AND (
        p.product_name ILIKE ?
        OR v.variant_name ILIKE ?
        OR v.sku ILIKE ?
    )';
    $searchPattern = '%' . $inventorySearch . '%';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && ($_GET['ajax'] ?? '') === 'search') {
    $ajaxPage = max(1, $requestedPage);
    $ajaxOffset = ($ajaxPage - 1) * $pageSize;
    $inventoryStatement = $conn->prepare(
        "SELECT p.product_id, p.product_name, v.variant_id, v.variant_name, v.sku,
                v.stock_quantity, v.price, v.compare_at_price,
                COUNT(*) OVER() AS total_count
         FROM product_tbl p
         INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
         WHERE {$inventoryWhere}
         ORDER BY (v.stock_quantity > 0), p.product_name, v.variant_name, v.variant_id
         LIMIT ? OFFSET ?"
    );
    if ($inventorySearch !== '') {
        $inventoryStatement->bind_param('sssii', $searchPattern, $searchPattern, $searchPattern, $pageSize, $ajaxOffset);
    } else {
        $inventoryStatement->bind_param('ii', $pageSize, $ajaxOffset);
    }
    $inventoryStatement->execute();
    $ajaxInventory = $inventoryStatement->get_result()->fetch_all(PDO::FETCH_ASSOC);
    $inventoryStatement->close();

    $ajaxTotalCount = $ajaxInventory ? (int) $ajaxInventory[0]['total_count'] : 0;
    $inventory = $ajaxInventory;
    $inventoryTotalCount = $ajaxTotalCount;
    $inventoryCount = $ajaxTotalCount;
    $pageCount = max(1, (int) ceil($inventoryCount / $pageSize));
    $currentPage = min($ajaxPage, $pageCount);
    $offset = ($currentPage - 1) * $pageSize;
    ob_start();
    echo '<table>';
    require __DIR__ . '/../app/views/components/inventory-table-body.php';
    echo '</table>';
    require __DIR__ . '/../app/views/components/inventory-pagination.php';
    $resultsHtml = ob_get_clean();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'html' => $resultsHtml,
        'totalCount' => $ajaxTotalCount,
        'currentPage' => $currentPage,
    ], JSON_THROW_ON_ERROR);
    exit;
}

if ($inventorySearch !== '') {
    $countStatement = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM product_tbl p
         INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
         WHERE {$inventoryWhere}"
    );
    $countStatement->bind_param('sss', $searchPattern, $searchPattern, $searchPattern);
    $countStatement->execute();
    $inventoryTotalCount = (int) $countStatement->get_result()->fetch_assoc()['total'];
    $countStatement->close();
} else {
    $inventoryTotalCount = (int) $conn->query(
        "SELECT COUNT(*) AS total
         FROM product_tbl p
         INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
         WHERE {$inventoryWhere}"
    )->fetch_assoc()['total'];
}
$inventoryCount = $inventoryTotalCount;
$pageCount = max(1, (int) ceil($inventoryCount / $pageSize));
$currentPage = min($requestedPage, $pageCount);
$offset = ($currentPage - 1) * $pageSize;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = isset($_POST['action']) && is_string($_POST['action'])
        ? $_POST['action']
        : 'adjust_stock';
    $variantId = isset($_POST['variant_id']) && is_string($_POST['variant_id'])
        ? filter_var($_POST['variant_id'], FILTER_VALIDATE_INT)
        : false;

    if ($action === 'backfill_pricing') {
        $previousPrice = isset($_POST['previous_price']) && is_string($_POST['previous_price'])
            ? trim($_POST['previous_price'])
            : '';
        $previousCompareAtInput = isset($_POST['previous_compare_at_price']) && is_string($_POST['previous_compare_at_price'])
            ? trim($_POST['previous_compare_at_price'])
            : '';
        $newPrice = isset($_POST['new_price']) && is_string($_POST['new_price'])
            ? trim($_POST['new_price'])
            : '';
        $newCompareAtInput = isset($_POST['new_compare_at_price']) && is_string($_POST['new_compare_at_price'])
            ? trim($_POST['new_compare_at_price'])
            : '';
        $changedAtInput = isset($_POST['changed_at']) && is_string($_POST['changed_at'])
            ? trim($_POST['changed_at'])
            : '';
        $changeReason = isset($_POST['change_reason']) && is_string($_POST['change_reason'])
            ? trim($_POST['change_reason'])
            : 'Historical pricing record (backfilled)';
        $validPrice = static fn (string $value): bool =>
            preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $value) === 1 && (float) $value > 0;
        $previousCompareIsValid = $previousCompareAtInput === ''
            || ($validPrice($previousCompareAtInput) && (float) $previousCompareAtInput > (float) $previousPrice);
        $newCompareIsValid = $newCompareAtInput === ''
            || ($validPrice($newCompareAtInput) && (float) $newCompareAtInput > (float) $newPrice);
        $pricesAreDifferent = number_format((float) $previousPrice, 2, '.', '')
                !== number_format((float) $newPrice, 2, '.', '')
            || (
                $previousCompareAtInput === '' ? null : number_format((float) $previousCompareAtInput, 2, '.', '')
            ) !== (
                $newCompareAtInput === '' ? null : number_format((float) $newCompareAtInput, 2, '.', '')
            );
        $changedAt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $changedAtInput, $flashSaleTimezone);
        $changedAtErrors = DateTimeImmutable::getLastErrors();
        $changedAtIsValid = $changedAt !== false
            && ($changedAtErrors === false || ($changedAtErrors['warning_count'] === 0 && $changedAtErrors['error_count'] === 0))
            && $changedAt->format('Y-m-d\TH:i') === $changedAtInput
            && $changedAt <= $inventoryNow;

        if (!store_verify_csrf()) {
            http_response_code(403);
            $notice = 'Your session expired. Refresh the page and try again.';
            $noticeIsError = true;
        } elseif (
            $variantId === false || $variantId < 1
            || !$validPrice($previousPrice) || !$previousCompareIsValid
            || !$validPrice($newPrice) || !$newCompareIsValid
            || !$pricesAreDifferent || !$changedAtIsValid || strlen($changeReason) > 500
        ) {
            $notice = 'Enter a product, different valid before-and-after prices, and a past date and time.';
            $noticeIsError = true;
        } else {
            $statement = $conn->prepare(
                'SELECT p.product_name, v.variant_name, v.sku
                 FROM product_tbl p
                 INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
                 WHERE v.variant_id = ?'
            );
            $statement->bind_param('i', $variantId);
            $statement->execute();
            $variant = $statement->get_result()->fetch_assoc();
            $statement->close();

            if (!$variant) {
                $notice = 'That product variant could not be found.';
                $noticeIsError = true;
            } else {
                $previousCompareAtPrice = $previousCompareAtInput === '' ? null : $previousCompareAtInput;
                $newCompareAtPrice = $newCompareAtInput === '' ? null : $newCompareAtInput;
                $changedAtValue = $changedAt->format(DateTimeInterface::ATOM);
                $userId = (int) $_SESSION['user_id'];

                try {
                    $statement = $conn->prepare(
                        "INSERT INTO inventory_movements_tbl
                            (variant_id, product_name_snapshot, variant_name_snapshot, sku_snapshot,
                             movement_type, quantity_change, previous_quantity, new_quantity, reason, user_id,
                             previous_price, previous_compare_at_price, new_price, new_compare_at_price, created_at)
                         VALUES (?, ?, ?, ?, 'price_update', NULL, NULL, NULL, ?, ?,
                                 ?, ?, ?, ?, ?)
                         ON CONFLICT (variant_id)
                         WHERE movement_type = 'price_update' AND variant_id IS NOT NULL
                         DO UPDATE SET
                             product_name_snapshot = EXCLUDED.product_name_snapshot,
                             variant_name_snapshot = EXCLUDED.variant_name_snapshot,
                             sku_snapshot = EXCLUDED.sku_snapshot,
                             reason = EXCLUDED.reason,
                             user_id = EXCLUDED.user_id,
                             previous_price = EXCLUDED.previous_price,
                             previous_compare_at_price = EXCLUDED.previous_compare_at_price,
                             new_price = EXCLUDED.new_price,
                             new_compare_at_price = EXCLUDED.new_compare_at_price,
                             created_at = EXCLUDED.created_at"
                    );
                    $statement->bind_param(
                        'issssisssss',
                        $variantId,
                        $variant['product_name'],
                        $variant['variant_name'],
                        $variant['sku'],
                        $changeReason,
                        $userId,
                        $previousPrice,
                        $previousCompareAtPrice,
                        $newPrice,
                        $newCompareAtPrice,
                        $changedAtValue
                    );
                    $statement->execute();
                    $statement->close();

                    $_SESSION['inventory_notice'] = 'Historical pricing change added to the audit trail. Current product pricing was not changed.';
                    header('Location: inventory?' . http_build_query([
                        'page' => $currentPage,
                        'search' => $inventorySearch,
                        'history_page' => 1,
                    ]));
                    exit;
                } catch (PDOException $error) {
                    error_log('Historical pricing audit entry failed: ' . $error->getMessage());
                    $notice = 'The historical pricing change could not be added. Apply the inventory audit database migration and try again.';
                    $noticeIsError = true;
                }
            }
        }
    } elseif ($action === 'update_pricing') {
        $price = isset($_POST['price']) && is_string($_POST['price']) ? trim($_POST['price']) : '';
        $compareAtPriceInput = isset($_POST['compare_at_price']) && is_string($_POST['compare_at_price'])
            ? trim($_POST['compare_at_price'])
            : '';
        $priceIsValid = preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $price) === 1 && (float) $price > 0;
        $compareAtPriceIsValid = $compareAtPriceInput === ''
            || (
                preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $compareAtPriceInput) === 1
                && (float) $compareAtPriceInput > (float) $price
            );

        if (!store_verify_csrf()) {
            http_response_code(403);
            $notice = 'Your session expired. Refresh the page and try again.';
            $noticeIsError = true;
        } elseif ($variantId === false || $variantId < 1 || !$priceIsValid || !$compareAtPriceIsValid) {
            $notice = 'Enter a valid sale price and an optional regular price that is higher than the sale price.';
            $noticeIsError = true;
        } else {
            $compareAtPrice = $compareAtPriceInput === '' ? null : $compareAtPriceInput;
            try {
                $conn->begin_transaction();
                $statement = $conn->prepare(
                    "SELECT p.status, p.product_name, v.variant_name, v.sku,
                            v.price, v.compare_at_price
                     FROM product_tbl p
                     INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
                     WHERE v.variant_id = ?
                     FOR UPDATE OF v"
                );
                $statement->bind_param('i', $variantId);
                $statement->execute();
                $variant = $statement->get_result()->fetch_assoc();
                $statement->close();

                if (!$variant || $variant['status'] !== 'active') {
                    $conn->rollback();
                    $notice = 'That active inventory variant could not be found.';
                    $noticeIsError = true;
                } else {
                    $statement = $conn->prepare(
                        'UPDATE product_variants_tbl SET price = ?, compare_at_price = ? WHERE variant_id = ?'
                    );
                    $statement->bind_param('ssi', $price, $compareAtPrice, $variantId);
                    $statement->execute();
                    $statement->close();

                    $productName = (string) $variant['product_name'];
                    $variantName = (string) $variant['variant_name'];
                    $sku = (string) $variant['sku'];
                    $previousPrice = (string) $variant['price'];
                    $previousCompareAtPrice = $variant['compare_at_price'] === null
                        ? null
                        : (string) $variant['compare_at_price'];
                    $userId = (int) $_SESSION['user_id'];
                    $statement = $conn->prepare(
                        "INSERT INTO inventory_movements_tbl
                            (variant_id, product_name_snapshot, variant_name_snapshot, sku_snapshot,
                             movement_type, quantity_change, previous_quantity, new_quantity, reason, user_id,
                             previous_price, previous_compare_at_price, new_price, new_compare_at_price)
                         VALUES (?, ?, ?, ?, 'price_update', NULL, NULL, NULL, 'Product pricing updated', ?,
                                 ?, ?, ?, ?)
                         ON CONFLICT (variant_id)
                         WHERE movement_type = 'price_update' AND variant_id IS NOT NULL
                         DO UPDATE SET
                             product_name_snapshot = EXCLUDED.product_name_snapshot,
                             variant_name_snapshot = EXCLUDED.variant_name_snapshot,
                             sku_snapshot = EXCLUDED.sku_snapshot,
                             reason = EXCLUDED.reason,
                             user_id = EXCLUDED.user_id,
                             previous_price = EXCLUDED.previous_price,
                             previous_compare_at_price = EXCLUDED.previous_compare_at_price,
                             new_price = EXCLUDED.new_price,
                             new_compare_at_price = EXCLUDED.new_compare_at_price,
                             created_at = EXCLUDED.created_at"
                    );
                    $statement->bind_param(
                        'isssissss',
                        $variantId,
                        $productName,
                        $variantName,
                        $sku,
                        $userId,
                        $previousPrice,
                        $previousCompareAtPrice,
                        $price,
                        $compareAtPrice
                    );
                    $statement->execute();
                    $statement->close();
                    $conn->commit();

                    $_SESSION['inventory_notice'] = $compareAtPrice === null
                        ? 'Product pricing updated. No compare-at price is set, so this variant is not discounted.'
                        : 'Product pricing updated. This variant is now eligible for flash sale scheduling when it has available stock.';
                    header('Location: inventory?' . http_build_query([
                        'page' => $currentPage,
                        'search' => $inventorySearch,
                        'history_page' => $historyPage,
                    ]));
                    exit;
                }
            } catch (PDOException $error) {
                $conn->rollback();
                error_log('Inventory pricing update failed: ' . $error->getMessage());
                $notice = 'Product pricing could not be updated. Please try again.';
                $noticeIsError = true;
            }
        }
    } elseif ($action !== 'adjust_stock') {
        $notice = 'Choose a valid inventory action.';
        $noticeIsError = true;
    } else {
    $quantity = isset($_POST['quantity']) && is_string($_POST['quantity'])
        ? filter_var($_POST['quantity'], FILTER_VALIDATE_INT)
        : false;
    $adjustmentType = isset($_POST['adjustment_type']) && is_string($_POST['adjustment_type'])
        ? $_POST['adjustment_type']
        : '';
    $reason = isset($_POST['reason']) && is_string($_POST['reason'])
        ? trim($_POST['reason'])
        : '';

    if (!store_verify_csrf()) {
        http_response_code(403);
        $notice = 'Your session expired. Refresh the page and try again.';
        $noticeIsError = true;
    } elseif (
        $variantId === false
        || $variantId < 1
        || $quantity === false
        || $quantity < 0
        || $quantity > 2147483647
        || !in_array($adjustmentType, ['restock', 'remove', 'count'], true)
        || $reason === ''
        || strlen($reason) > 500
    ) {
        $notice = 'Enter a valid adjustment, quantity, and reason (up to 500 characters).';
        $noticeIsError = true;
    } else {
        try {
            $result = store_adjust_inventory(
                $conn,
                (int) $variantId,
                $adjustmentType,
                (int) $quantity,
                $reason,
                (int) $_SESSION['user_id']
            );
            $_SESSION['inventory_notice'] = sprintf(
                'Inventory updated from %d to %d units (%+d).',
                $result['previous_quantity'],
                $result['new_quantity'],
                $result['quantity_change']
            );
            header('Location: inventory?' . http_build_query([
                'page' => $currentPage,
                'search' => $inventorySearch,
                'history_page' => $historyPage,
            ]));
            exit;
        } catch (PDOException $error) {
            error_log('Inventory adjustment failed: ' . $error->getMessage());
            $notice = 'Inventory could not be updated. Please try again.';
            $noticeIsError = true;
        } catch (InvalidArgumentException | RuntimeException $error) {
            $notice = $error->getMessage();
            $noticeIsError = true;
        }
    }
    }
}

if (isset($_SESSION['inventory_notice']) && $notice === '') {
    $notice = (string) $_SESSION['inventory_notice'];
    unset($_SESSION['inventory_notice']);
}

$inventoryStatement = $conn->prepare(
    "SELECT p.product_id, p.product_name, v.variant_id, v.variant_name, v.sku,
            v.stock_quantity, v.price, v.compare_at_price
     FROM product_tbl p
     INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
     WHERE {$inventoryWhere}
     ORDER BY (v.stock_quantity > 0), p.product_name, v.variant_name, v.variant_id
     LIMIT ? OFFSET ?"
);
if ($inventorySearch !== '') {
    $queryLimit = $pageSize;
    $inventoryStatement->bind_param('sssii', $searchPattern, $searchPattern, $searchPattern, $queryLimit, $offset);
} else {
    $queryLimit = $pageSize;
    $inventoryStatement->bind_param('ii', $queryLimit, $offset);
}
$inventoryStatement->execute();
$inventory = $inventoryStatement->get_result()->fetch_all(PDO::FETCH_ASSOC);
$inventoryStatement->close();

$historyPageSize = 10;
$historyCount = (int) $conn->query('SELECT COUNT(*) AS total FROM inventory_movements_tbl')->fetch_assoc()['total'];
$historyPageCount = max(1, (int) ceil($historyCount / $historyPageSize));
$historyPage = min($historyPage, $historyPageCount);
$historyOffset = ($historyPage - 1) * $historyPageSize;
$historyStatement = $conn->prepare(
    'SELECT m.*, u.username, o.order_number
     FROM inventory_movements_tbl m
     LEFT JOIN user_tbl u ON u.user_id = m.user_id
     LEFT JOIN order_tbl o ON o.order_id = m.order_id
     ORDER BY m.created_at DESC, m.movement_id DESC
     LIMIT ? OFFSET ?'
);
$historyStatement->bind_param('ii', $historyPageSize, $historyOffset);
$historyStatement->execute();
$inventoryMovements = $historyStatement->get_result()->fetch_all(PDO::FETCH_ASSOC);
$historyStatement->close();
$historicalPriceVariants = $conn->query(
    'SELECT v.variant_id, p.product_name, p.status, v.variant_name, v.sku
     FROM product_tbl p
     INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
     WHERE EXISTS (
         SELECT 1
         FROM inventory_movements_tbl price_history
         WHERE price_history.variant_id = v.variant_id
           AND price_history.movement_type = \'price_update\'
     )
     ORDER BY p.product_name, v.variant_name, v.variant_id'
)->fetch_all(PDO::FETCH_ASSOC);
$inventoryOverview = $conn->query(
    "SELECT COUNT(*) AS variant_count,
            COUNT(*) FILTER (WHERE stock_quantity = 0) AS out_of_stock_count,
            COALESCE(SUM(stock_quantity), 0) AS units_on_hand
     FROM product_variants_tbl v
     INNER JOIN product_tbl p ON p.product_id = v.product_id
     WHERE p.status = 'active'"
)->fetch_assoc();

$siteRoot = '../';
$pageTitle = 'Inventory | HoloVerse';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <main class="page-shell admin-dashboard-shell">
        <?php require __DIR__ . '/../app/views/components/site-header.php'; ?>

        <section class="admin-dashboard admin-inventory-dashboard">
            <header class="admin-dashboard-header">
                <div>
                    <p class="admin-kicker">Operations control</p>
                    <h1>Inventory</h1>
                </div>
                <div class="admin-inventory-header-actions">
                    <span class="admin-inventory-count" id="inventory-result-count"><?= number_format($inventoryTotalCount) ?> matching variants</span>
                    <a class="admin-inventory-back" href="admin-dashboard">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 14 4 9l5-5" />
                            <path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5a5.5 5.5 0 0 1-5.5 5.5H11" />
                        </svg>
                        <span>Back to dashboard</span>
                    </a>
                </div>
            </header>

            <?php if ($notice !== ''): ?>
                <p class="admin-notice<?= $noticeIsError ? ' admin-notice--error' : '' ?>" role="<?= $noticeIsError ? 'alert' : 'status' ?>"><?= store_escape($notice) ?></p>
            <?php endif; ?>

            <div class="admin-inventory-overview" aria-label="Inventory overview">
                <article class="admin-inventory-overview-card">
                    <span>Active variants</span>
                    <strong><?= number_format((int) $inventoryOverview['variant_count']) ?></strong>
                </article>
                <article class="admin-inventory-overview-card">
                    <span>Total units on hand</span>
                    <strong><?= number_format((int) $inventoryOverview['units_on_hand']) ?></strong>
                </article>
                <article class="admin-inventory-overview-card<?= (int) $inventoryOverview['out_of_stock_count'] > 0 ? ' is-alert' : '' ?>">
                    <span>Out of stock</span>
                    <strong><?= number_format((int) $inventoryOverview['out_of_stock_count']) ?></strong>
                </article>
            </div>

            <div class="admin-inventory-toolbar">
                <div class="admin-inventory-toolbar-copy">
                    <h2>Stock levels</h2>
                    <p class="admin-panel-description">Set a sale price and a higher regular price under Edit pricing to make an in-stock variant eligible, then <a class="admin-inventory-flash-link" href="flash-sale-admin">schedule it in Flash Sale Manager</a>. Stock adjustments are recorded in the audit trail.</p>
                </div>
                <form class="admin-inventory-search" method="get" action="inventory" role="search">
                    <label class="sr-only" for="inventory-search">Search product, variant, or SKU</label>
                    <span class="admin-inventory-search-field">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m16 16 4 4" />
                        </svg>
                        <input id="inventory-search" name="search" type="search" value="<?= store_escape($inventorySearch) ?>" placeholder="Search by product, variant, or SKU" autocomplete="off">
                        <button type="button" class="admin-inventory-search-clear" data-clear-search aria-label="Clear search"<?= $inventorySearch === '' ? ' hidden' : '' ?>>
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                <path d="m18 6-12 12M6 6l12 12" />
                            </svg>
                        </button>
                    </span>
                    <button type="submit">Search</button>
                </form>
            </div>
            <p id="inventory-search-status" class="sr-only" role="status" aria-live="polite"></p>
            <div class="admin-table-wrap">
                <table class="admin-inventory-table">
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Variant</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Pricing</th>
                            <th scope="col">Current stock</th>
                            <th scope="col">Stock action</th>
                            <th scope="col">Pricing action</th>
                        </tr>
                    </thead>
                    <?php require __DIR__ . '/../app/views/components/inventory-table-body.php'; ?>
                </table>
            </div>
            <?php require __DIR__ . '/../app/views/components/inventory-pagination.php'; ?>

            <section class="admin-inventory-history" aria-labelledby="inventory-history-title">
                <header class="admin-inventory-section-header">
                    <div>
                        <p class="admin-kicker">Audit trail</p>
                        <h2 id="inventory-history-title">Inventory and pricing history</h2>
                    </div>
                    <div class="admin-inventory-history-actions">
                        <p>Stock adjustments and price changes, including who made each update.</p>
                        <details class="admin-price-history-add">
                            <summary>Add past pricing change</summary>
                            <form method="post" action="inventory" class="admin-price-history-form">
                                <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                <input type="hidden" name="action" value="backfill_pricing">
                                <input type="hidden" name="page" value="<?= $currentPage ?>">
                                <input type="hidden" name="search" value="<?= store_escape($inventorySearch) ?>">
                                <input type="hidden" name="history_page" value="<?= $historyPage ?>">
                                <div class="admin-price-history-intro">
                                    <strong>Record an earlier price change</strong>
                                    <span>This adds an audit entry only. It does not change current product prices.</span>
                                </div>
                                <label for="historical-price-variant">Product / variant</label>
                                <select id="historical-price-variant" name="variant_id" required>
                                    <option value="">Choose an audited product variant</option>
                                    <?php foreach ($historicalPriceVariants as $priceVariant): ?>
                                        <option value="<?= (int) $priceVariant['variant_id'] ?>">
                                            <?= store_escape($priceVariant['product_name'] . ' / ' . $priceVariant['variant_name'] . ' · ' . $priceVariant['sku'] . ($priceVariant['status'] === 'active' ? '' : ' · ' . ucfirst($priceVariant['status']))) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (!$historicalPriceVariants): ?>
                                    <small class="admin-price-history-empty">No products with pricing changes in the audit trail are available.</small>
                                <?php endif; ?>
                                <div class="admin-price-history-prices">
                                    <fieldset>
                                        <legend>Previous prices</legend>
                                        <label for="historical-previous-price">Sale price (PHP)</label>
                                        <input id="historical-previous-price" name="previous_price" type="number" min="0.01" max="99999999.99" step="0.01" required>
                                        <label for="historical-previous-compare">Regular price (PHP)</label>
                                        <input id="historical-previous-compare" name="previous_compare_at_price" type="number" min="0.01" max="99999999.99" step="0.01" placeholder="Optional">
                                    </fieldset>
                                    <fieldset>
                                        <legend>Changed-to prices</legend>
                                        <label for="historical-new-price">Sale price (PHP)</label>
                                        <input id="historical-new-price" name="new_price" type="number" min="0.01" max="99999999.99" step="0.01" required>
                                        <label for="historical-new-compare">Regular price (PHP)</label>
                                        <input id="historical-new-compare" name="new_compare_at_price" type="number" min="0.01" max="99999999.99" step="0.01" placeholder="Optional">
                                    </fieldset>
                                </div>
                                <label for="historical-changed-at">Date and time of change (<?= store_escape($flashSaleTimezone->getName()) ?>)</label>
                                <input id="historical-changed-at" name="changed_at" type="datetime-local" max="<?= store_escape($inventoryNow->format('Y-m-d\TH:i')) ?>" required>
                                <label for="historical-change-reason">Note <span>(optional)</span></label>
                                <input id="historical-change-reason" name="change_reason" type="text" maxlength="500" placeholder="e.g. Initial sale price entered before audit tracking">
                                <button type="submit"<?= !$historicalPriceVariants ? ' disabled' : '' ?>>Add to audit trail</button>
                            </form>
                        </details>
                    </div>
                </header>
                <div class="admin-table-wrap">
                    <?php require __DIR__ . '/../app/views/components/inventory-movement-table.php'; ?>
                </div>
                <nav class="admin-inventory-pagination" aria-label="Inventory and pricing audit history pages">
                    <span class="admin-inventory-page-summary">
                        <?php if ($historyCount > 0): ?>
                            Showing <strong><?= number_format($historyOffset + 1) ?>–<?= number_format(min($historyOffset + $historyPageSize, $historyCount)) ?></strong> of <?= number_format($historyCount) ?> audit entries
                            <?php if ($historyPageCount > 1): ?><span class="admin-inventory-page-number">Page <?= number_format($historyPage) ?> of <?= number_format($historyPageCount) ?></span><?php endif; ?>
                        <?php else: ?>
                            No inventory or pricing changes recorded
                        <?php endif; ?>
                    </span>
                    <?php if ($historyPageCount > 1): ?>
                        <div class="admin-inventory-page-links">
                            <?php if ($historyPage > 1): ?>
                                <a href="?<?= store_escape(http_build_query(['page' => $currentPage, 'search' => $inventorySearch, 'history_page' => $historyPage - 1])) ?>">Previous history page</a>
                            <?php endif; ?>
                            <?php if ($historyPage < $historyPageCount): ?>
                                <a href="?<?= store_escape(http_build_query(['page' => $currentPage, 'search' => $inventorySearch, 'history_page' => $historyPage + 1])) ?>">Next history page</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </nav>
            </section>
        </section>
    </main>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    <script>
        (() => {
            const updateDiscountPreview = (form) => {
                const salePriceInput = form.querySelector('[name="price"]');
                const regularPriceInput = form.querySelector('[name="compare_at_price"]');
                const preview = form.querySelector('[data-discount-preview]');
                if (!salePriceInput || !regularPriceInput || !preview) return;

                const salePrice = Number(salePriceInput.value);
                const regularPrice = Number(regularPriceInput.value);
                if (!salePriceInput.value || !regularPriceInput.value || salePrice <= 0 || regularPrice <= salePrice) {
                    preview.textContent = 'Enter a higher regular price to calculate the discount.';
                    preview.classList.remove('is-calculated');
                    return;
                }

                const discountPercent = Math.round((1 - salePrice / regularPrice) * 100);
                preview.textContent = `${discountPercent}% off · saved ₱${(regularPrice - salePrice).toFixed(2)} per unit`;
                preview.classList.add('is-calculated');
            };

            document.querySelectorAll('.admin-price-form').forEach(updateDiscountPreview);
            document.addEventListener('input', (event) => {
                const form = event.target.closest('.admin-price-form');
                if (form) updateDiscountPreview(form);
            });
        })();
    </script>
    <script>
        (() => {
            const form = document.querySelector(".admin-inventory-search");
            const input = document.querySelector("#inventory-search");
            const status = document.querySelector("#inventory-search-status");
            const resultCount = document.querySelector("#inventory-result-count");
            let tableBody = document.querySelector(".admin-inventory-table tbody");
            const pagination = document.querySelector(".admin-inventory-pagination");
            const searchButton = form?.querySelector('button[type="submit"]');
            if (!form || !input || !status || !resultCount || !tableBody || !pagination || !searchButton) {
                return;
            }

            let debounceTimer;
            let activeController;
            let requestId = 0;

            const updateClearLink = (search) => {
                const clearButton = form.querySelector("[data-clear-search]");
                clearButton.hidden = !search;
            };

            const searchInventory = async (search, page = 1) => {
                const currentRequestId = ++requestId;
                if (activeController) {
                    activeController.abort();
                }
                activeController = new AbortController();
                status.className = "sr-only";
                status.setAttribute("role", "status");
                status.textContent = "Updating inventory results…";
                tableBody.setAttribute("aria-busy", "true");
                form.setAttribute("aria-busy", "true");
                searchButton.disabled = true;
                searchButton.textContent = "Searching...";

                const url = new URL(form.action, window.location.href);
                url.searchParams.set("ajax", "search");
                url.searchParams.set("page", page);
                if (search) {
                    url.searchParams.set("search", search);
                } else {
                    url.searchParams.delete("search");
                }

                try {
                    const response = await fetch(url, {
                        cache: "no-store",
                        signal: activeController.signal
                    });
                    if (!response.ok) {
                        throw new Error(`Inventory search failed with status ${response.status}.`);
                    }

                    const data = await response.json();
                    if (currentRequestId !== requestId) {
                        return;
                    }
                    const results = new DOMParser().parseFromString(data.html, "text/html");
                    const nextTableBody = results.querySelector("tbody");
                    const nextPagination = results.querySelector(".admin-inventory-pagination");
                    const currentPagination = document.querySelector(".admin-inventory-pagination");
                    if (!nextTableBody || !nextPagination || !currentPagination) {
                        throw new Error("Inventory search returned incomplete results.");
                    }

                    resultCount.textContent = `${data.totalCount.toLocaleString()} matching variants`;
                    tableBody.replaceWith(nextTableBody);
                    tableBody = nextTableBody;
                    nextTableBody.removeAttribute("aria-busy");
                    currentPagination.replaceWith(nextPagination);

                    updateClearLink(search);
                    const cleanUrl = new URL(window.location.href);
                    cleanUrl.searchParams.delete("ajax");
                    cleanUrl.searchParams.set("page", data.currentPage);
                    if (search) {
                        cleanUrl.searchParams.set("search", search);
                    } else {
                        cleanUrl.searchParams.delete("search");
                    }
                    window.history.replaceState({}, "", cleanUrl);
                    status.textContent = "";
                    tableBody.removeAttribute("aria-busy");
                    form.removeAttribute("aria-busy");
                    searchButton.disabled = false;
                    searchButton.textContent = "Search";
                } catch (error) {
                    if (error.name === "AbortError" || currentRequestId !== requestId) {
                        return;
                    }
                    console.error(error);
                    status.className = "admin-notice admin-notice--error";
                    status.setAttribute("role", "alert");
                    status.textContent = "Search could not be updated. Please try again.";
                    tableBody.removeAttribute("aria-busy");
                    form.removeAttribute("aria-busy");
                    searchButton.disabled = false;
                    searchButton.textContent = "Search";
                }
            };

            form.addEventListener("submit", (event) => {
                event.preventDefault();
                window.clearTimeout(debounceTimer);
                searchInventory(input.value.trim());
            });

            form.addEventListener("input", (event) => {
                if (event.target !== input) {
                    return;
                }
                if (activeController) {
                    activeController.abort();
                    activeController = null;
                    requestId++;
                }
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(() => searchInventory(input.value.trim()), 100);
            });

            form.addEventListener("click", (event) => {
                if (event.target.closest("[data-clear-search]")) {
                    event.preventDefault();
                    window.clearTimeout(debounceTimer);
                    input.value = "";
                    searchInventory("");
                }
            });

            document.addEventListener("click", (event) => {
                const pageLink = event.target.closest(".admin-inventory-pagination a");
                if (!pageLink) {
                    return;
                }
                const pageUrl = new URL(pageLink.href, window.location.href);
                const page = Number(pageUrl.searchParams.get("page"));
                if (Number.isInteger(page) && page > 0) {
                    event.preventDefault();
                    searchInventory(input.value.trim(), page);
                }
            });
        })();
    </script>
</body>

</html>
