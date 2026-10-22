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
        "SELECT p.product_id, p.product_name, v.variant_id, v.variant_name, v.sku, v.stock_quantity,
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
                header('Location: inventory?' . http_build_query([
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
    $queryLimit = $pageSize;
    $inventoryStatement->bind_param('sssii', $searchPattern, $searchPattern, $searchPattern, $queryLimit, $offset);
} else {
    $queryLimit = $pageSize;
    $inventoryStatement->bind_param('ii', $queryLimit, $offset);
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

            <div class="admin-inventory-toolbar">
                <p class="admin-panel-description">Add units to a product variant’s current stock. Sold-out items appear first.</p>
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
                            <th scope="col">Current stock</th>
                            <th scope="col">Restock</th>
                        </tr>
                    </thead>
                    <?php require __DIR__ . '/../app/views/components/inventory-table-body.php'; ?>
                </table>
            </div>
            <?php require __DIR__ . '/../app/views/components/inventory-pagination.php'; ?>
        </section>
    </main>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
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
