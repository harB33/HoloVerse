<?php
require_once __DIR__ . '/../app/store.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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

$inventoryWhere = "p.status = 'active'";
if ($inventorySearch !== '') {
    $inventoryWhere .= ' AND (
        p.product_name ILIKE ?
        OR v.variant_name ILIKE ?
        OR v.sku ILIKE ?
    )';
    $searchPattern = '%' . $inventorySearch . '%';
    $countStatement = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM product_tbl p
         INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
         WHERE {$inventoryWhere}"
    );
    $countStatement->bind_param('sss', $searchPattern, $searchPattern, $searchPattern);
    $countStatement->execute();
    $inventoryCount = (int) $countStatement->get_result()->fetch_assoc()['total'];
    $countStatement->close();
} else {
    $inventoryCount = (int) $conn->query(
        "SELECT COUNT(*) AS total
         FROM product_tbl p
         INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
         WHERE {$inventoryWhere}"
    )->fetch_assoc()['total'];
}
$pageCount = max(1, (int) ceil($inventoryCount / $pageSize));
$currentPage = min($requestedPage, $pageCount);
$offset = ($currentPage - 1) * $pageSize;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $variantId = isset($_POST['variant_id']) && is_string($_POST['variant_id'])
        ? filter_var($_POST['variant_id'], FILTER_VALIDATE_INT)
        : false;
    $restockQuantity = isset($_POST['restock_quantity']) && is_string($_POST['restock_quantity'])
        ? filter_var($_POST['restock_quantity'], FILTER_VALIDATE_INT)
        : false;

    if (!store_verify_csrf()) {
        http_response_code(403);
        $notice = 'Your session expired. Refresh the page and try again.';
        $noticeIsError = true;
    } elseif ($variantId === false || $variantId < 1 || $restockQuantity === false || $restockQuantity < 1 || $restockQuantity > 2147483647) {
        $notice = 'Enter a valid item and a restock quantity greater than zero.';
        $noticeIsError = true;
    } else {
        try {
            $statement = $conn->prepare(
                "UPDATE product_variants_tbl
                 SET stock_quantity = stock_quantity + ?
                 WHERE variant_id = ?
                   AND stock_quantity <= 2147483647 - ?
                   AND EXISTS (
                       SELECT 1 FROM product_tbl p
                       WHERE p.product_id = product_variants_tbl.product_id AND p.status = 'active'
                   )"
            );
            $variantId = (int) $variantId;
            $restockQuantity = (int) $restockQuantity;
            $statement->bind_param('iii', $restockQuantity, $variantId, $restockQuantity);
            $statement->execute();
            $updated = $statement->affected_rows > 0;
            $statement->close();

            if ($updated) {
                $_SESSION['inventory_notice'] = 'Inventory restocked successfully.';
                header('Location: inventory.php?' . http_build_query([
                    'page' => $currentPage,
                    'search' => $inventorySearch,
                ]));
                exit;
            }

            $notice = 'This active item could not be restocked. It may have been removed or reached the maximum stock quantity.';
            $noticeIsError = true;
        } catch (PDOException $error) {
            error_log('Inventory restock failed: ' . $error->getMessage());
            $notice = 'Inventory could not be updated. Please try again.';
            $noticeIsError = true;
        }
    }
}

if (isset($_SESSION['inventory_notice']) && $notice === '') {
    $notice = (string) $_SESSION['inventory_notice'];
    unset($_SESSION['inventory_notice']);
}

$inventoryStatement = $conn->prepare(
    "SELECT p.product_id, p.product_name, v.variant_id, v.variant_name, v.sku, v.stock_quantity
     FROM product_tbl p
     INNER JOIN product_variants_tbl v ON v.product_id = p.product_id
     WHERE {$inventoryWhere}
     ORDER BY (v.stock_quantity > 0), p.product_name, v.variant_name, v.variant_id
     LIMIT ? OFFSET ?"
);
if ($inventorySearch !== '') {
    $inventoryStatement->bind_param('sssii', $searchPattern, $searchPattern, $searchPattern, $pageSize, $offset);
} else {
    $inventoryStatement->bind_param('ii', $pageSize, $offset);
}
$inventoryStatement->execute();
$inventory = $inventoryStatement->get_result()->fetch_all(PDO::FETCH_ASSOC);
$inventoryStatement->close();

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
                    <span class="admin-inventory-count"><?= $inventoryCount ?> variants</span>
                    <a class="admin-inventory-back" href="admin-dashboard.php">Back to dashboard</a>
                </div>
            </header>

            <?php if ($notice !== ''): ?>
                <p class="admin-notice<?= $noticeIsError ? ' admin-notice--error' : '' ?>" role="<?= $noticeIsError ? 'alert' : 'status' ?>"><?= store_escape($notice) ?></p>
            <?php endif; ?>

            <div class="admin-inventory-toolbar">
                <p class="admin-panel-description">Add units to a product variant’s current stock. Sold-out items appear first.</p>
                <form class="admin-inventory-search" method="get" action="inventory.php" role="search">
                    <label class="sr-only" for="inventory-search">Search product, variant, or SKU</label>
                    <input id="inventory-search" name="search" type="search" value="<?= store_escape($inventorySearch) ?>" placeholder="Search product, variant, or SKU">
                    <button type="submit">Search</button>
                    <?php if ($inventorySearch !== ''): ?>
                        <a href="inventory.php">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-inventory-table">
                    <thead>
                        <tr>
                            <th scope="col">Product</th>
                            <th scope="col">Variant</th>
                            <th scope="col">SKU</th>
                            <th scope="col">Current stock</th>
                            <th scope="col">Restock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($inventory): ?>
                            <?php foreach ($inventory as $item): ?>
                                <tr>
                                    <td data-label="Product"><span class="admin-inventory-product"><?= store_escape($item['product_name']) ?></span></td>
                                    <td data-label="Variant"><?= store_escape($item['variant_name']) ?></td>
                                    <td data-label="SKU"><span class="admin-inventory-sku"><?= store_escape($item['sku']) ?></span></td>
                                    <td data-label="Current stock">
                                        <span class="admin-status<?= (int) $item['stock_quantity'] > 0 ? ' admin-status--active' : ' admin-status--draft' ?>">
                                            <?= (int) $item['stock_quantity'] > 0 ? (int) $item['stock_quantity'] : 'Sold out' ?>
                                        </span>
                                    </td>
                                    <td data-label="Restock">
                                        <form class="admin-restock-form" method="post" action="inventory.php">
                                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                            <input type="hidden" name="variant_id" value="<?= (int) $item['variant_id'] ?>">
                                            <input type="hidden" name="page" value="<?= $currentPage ?>">
                                            <input type="hidden" name="search" value="<?= store_escape($inventorySearch) ?>">
                                            <label class="sr-only" for="restock-<?= (int) $item['variant_id'] ?>">Units to add for <?= store_escape($item['product_name'] . ' ' . $item['variant_name']) ?></label>
                                            <input id="restock-<?= (int) $item['variant_id'] ?>" name="restock_quantity" type="number" min="1" max="2147483647" step="1" value="1" required>
                                            <button type="submit">Add stock</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="admin-inventory-empty" colspan="5"><?= $inventorySearch !== '' ? 'No inventory variants match your search.' : 'There are no active product variants to manage.' ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <nav class="admin-inventory-pagination" aria-label="Inventory pages">
                <span class="admin-inventory-page-summary">
                    <?= $inventoryCount > 0 ? 'Showing ' . ($offset + 1) . '-' . min($offset + $pageSize, $inventoryCount) . ' of ' . $inventoryCount . ' variants' : 'No variants to display' ?>
                    <?php if ($pageCount > 1): ?> · Page <?= $currentPage ?> of <?= $pageCount ?><?php endif; ?>
                </span>
                <?php if ($pageCount > 1): ?>
                    <div class="admin-inventory-page-links">
                        <?php if ($currentPage > 1): ?>
                            <a href="?<?= store_escape(http_build_query(['page' => $currentPage - 1, 'search' => $inventorySearch])) ?>" aria-label="Previous inventory page">Previous</a>
                        <?php endif; ?>
                        <?php if ($currentPage < $pageCount): ?>
                            <a href="?<?= store_escape(http_build_query(['page' => $currentPage + 1, 'search' => $inventorySearch])) ?>" aria-label="Next inventory page">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </nav>
        </section>
    </main>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
