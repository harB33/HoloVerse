<?php
require_once __DIR__ . '/../app/store.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

if (!store_has_role('admin')) {
    http_response_code(403);
    exit('Administrator access required.');
}

$timezone = new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila');
$now = new DateTimeImmutable('now', $timezone);
if (!store_flash_sale_table_exists($conn)) {
    $pageTitle = 'Set Up Flash Sale Scheduling | HoloVerse';
    $siteRoot = '../';
    require __DIR__ . '/../app/views/layout/head.php';
    ?>
    <body>
        <div class="page-shell">
            <?php
            $activePage = 'flash_sale';
            require __DIR__ . '/../app/views/components/watermark.php';
            require __DIR__ . '/../app/views/components/site-header.php';
            ?>
            <main class="mx-auto w-full max-w-4xl px-4 pb-16 pt-8 sm:px-8">
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 sm:p-8" aria-labelledby="flash-sale-setup-title">
                    <p class="m-0 text-xs font-bold uppercase tracking-[0.15em] text-amber-800">Database setup required</p>
                    <h1 id="flash-sale-setup-title" class="mb-3 mt-2 text-2xl font-bold text-slate-900">Enable scheduled flash sales</h1>
                    <p class="text-sm leading-relaxed text-slate-700">The current Supabase database does not have the <code>flash_sale_tbl</code> table. Live discounted products remain available, but upcoming drops cannot be scheduled until the table is restored.</p>
                    <ol class="list-decimal space-y-2 pl-5 text-sm leading-relaxed text-slate-700">
                        <li>Ask the database administrator to restore the table using the schema in <code>Database/holoverse.sql</code>.</li>
                        <li>Refresh this page after the table has been restored.</li>
                    </ol>
                    <a class="mt-5 inline-flex rounded-lg bg-slate-800 px-5 py-3 text-sm font-semibold text-white no-underline hover:bg-slate-900" href="flash-sale">Return to flash sales</a>
                </section>
            </main>
        </div>
        <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    </body>
    </html>
    <?php
    exit;
}

$notice = (string) ($_SESSION['flash_sale_admin_notice'] ?? '');
$noticeIsError = (bool) ($_SESSION['flash_sale_admin_error'] ?? false);
unset($_SESSION['flash_sale_admin_notice'], $_SESSION['flash_sale_admin_error']);
$form = ['variant_id' => '', 'starts_at' => '', 'ends_at' => ''];
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    foreach ($form as $key => $default) {
        if (isset($_POST[$key]) && !is_string($_POST[$key])) {
            $error = 'Submit each scheduling field as a single value.';
            break;
        }
        $form[$key] = trim((string) ($_POST[$key] ?? $default));
    }

    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    if (!store_verify_csrf()) {
        http_response_code(403);
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($error === '' && $action === 'cancel') {
        $saleId = isset($_POST['flash_sale_id']) && is_string($_POST['flash_sale_id'])
            ? filter_var($_POST['flash_sale_id'], FILTER_VALIDATE_INT)
            : false;
        if ($saleId === false || $saleId < 1) {
            $error = 'Choose a valid scheduled drop to cancel.';
        } else {
            $statement = $conn->prepare("UPDATE flash_sale_tbl SET status = 'cancelled' WHERE flash_sale_id = ? AND status = 'scheduled'");
            $statement->bind_param('i', $saleId);
            $statement->execute();
            $wasCancelled = $statement->affected_rows === 1;
            $statement->close();

            $_SESSION['flash_sale_admin_notice'] = $wasCancelled
                ? 'The scheduled drop was cancelled.'
                : 'That drop was already cancelled or could not be found.';
            $_SESSION['flash_sale_admin_error'] = !$wasCancelled;
            header('Location: flash-sale-admin');
            exit;
        }
    } elseif ($error === '' && in_array($action, ['schedule', 'flash_sale_now'], true)) {
        $variantId = filter_var($form['variant_id'], FILTER_VALIDATE_INT);
        if ($action === 'flash_sale_now') {
            $startsAt = $now;
            $endsAt = $now->modify('+1 hour');
            $validWindow = true;
        } else {
            $startsAt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $form['starts_at'], $timezone);
            $startsAtErrors = DateTimeImmutable::getLastErrors();
            $endsAt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $form['ends_at'], $timezone);
            $endsAtErrors = DateTimeImmutable::getLastErrors();
            $validStart = $startsAt !== false
                && ($startsAtErrors === false || ($startsAtErrors['warning_count'] === 0 && $startsAtErrors['error_count'] === 0))
                && $startsAt->format('Y-m-d\TH:i') === $form['starts_at'];
            $validEnd = $endsAt !== false
                && ($endsAtErrors === false || ($endsAtErrors['warning_count'] === 0 && $endsAtErrors['error_count'] === 0))
                && $endsAt->format('Y-m-d\TH:i') === $form['ends_at'];
            $validWindow = $validStart && $validEnd && $startsAt > $now && $endsAt > $startsAt;
        }

        if ($variantId === false || $variantId < 1 || !$validWindow) {
            $error = $action === 'flash_sale_now'
                ? 'Choose an eligible discounted product to start a flash sale.'
                : 'Choose a discounted product, a future start time, and an end time after the start.';
        } else {
            $startValue = $startsAt->format('Y-m-d H:i:s');
            $endValue = $endsAt->format('Y-m-d H:i:s');
            $createdBy = (int) $_SESSION['user_id'];
            try {
                $conn->begin_transaction();

                $statement = $conn->prepare(
                    "SELECT v.variant_id
                     FROM product_variants_tbl v
                     INNER JOIN product_tbl p ON p.product_id = v.product_id
                     WHERE v.variant_id = ? AND p.status = 'active'
                       AND v.stock_quantity > 0
                       AND v.compare_at_price IS NOT NULL AND v.compare_at_price > v.price
                     FOR UPDATE"
                );
                $statement->bind_param('i', $variantId);
                $statement->execute();
                $validVariant = $statement->get_result()->fetch_assoc();
                $statement->close();

                if (!$validVariant) {
                    $conn->rollback();
                    $error = 'That product is no longer active, discounted, or in stock.';
                } else {
                    $statement = $conn->prepare(
                        "SELECT flash_sale_id FROM flash_sale_tbl
                         WHERE variant_id = ? AND status = 'scheduled'
                           AND starts_at < ? AND ends_at > ?
                         LIMIT 1 FOR UPDATE"
                    );
                    $statement->bind_param('iss', $variantId, $endValue, $startValue);
                    $statement->execute();
                    $overlap = $statement->get_result()->fetch_assoc();
                    $statement->close();

                    if ($overlap) {
                        $conn->rollback();
                        $error = 'This product already has a scheduled drop that overlaps that time.';
                    } else {
                        $statement = $conn->prepare(
                            "INSERT INTO flash_sale_tbl (variant_id, starts_at, ends_at, status, created_by)
                             VALUES (?, ?, ?, 'scheduled', ?)"
                        );
                        $statement->bind_param('issi', $variantId, $startValue, $endValue, $createdBy);
                        $statement->execute();
                        $statement->close();
                        $conn->commit();

                        $_SESSION['flash_sale_admin_notice'] = $action === 'flash_sale_now'
                            ? 'The flash sale is live now and will end in one hour.'
                            : 'The flash sale drop was scheduled.';
                        header('Location: flash-sale-admin');
                        exit;
                    }
                }
            } catch (PDOException $databaseError) {
                $conn->rollback();
                error_log('Flash sale scheduling failed: ' . $databaseError->getMessage());
                $error = 'The drop could not be scheduled. Please try again.';
            }
        }
    } elseif ($error === '') {
        $error = 'Choose a supported flash sale action.';
    }
}

$availableStock = store_available_stock_sql();
$eligibleProducts = $conn->query(
    "SELECT v.variant_id, v.variant_name, v.price, v.compare_at_price, v.currency,
            {$availableStock} AS stock_quantity, p.product_name
     FROM product_variants_tbl v
     INNER JOIN product_tbl p ON p.product_id = v.product_id
     WHERE p.status = 'active' AND {$availableStock} > 0
       AND v.compare_at_price IS NOT NULL AND v.compare_at_price > v.price
     ORDER BY p.product_name, v.variant_name"
)->fetch_all(PDO::FETCH_ASSOC);

$scheduleStatement = $conn->prepare(
    "SELECT fs.flash_sale_id, fs.starts_at, fs.ends_at, fs.status, fs.created_at,
            v.variant_id, v.variant_name, v.price, v.compare_at_price, v.currency,
            {$availableStock} AS stock_quantity, p.product_name
     FROM flash_sale_tbl fs
     INNER JOIN product_variants_tbl v ON v.variant_id = fs.variant_id
     INNER JOIN product_tbl p ON p.product_id = v.product_id
     ORDER BY fs.starts_at DESC, fs.flash_sale_id DESC"
);
$scheduleStatement->execute();
$scheduledDrops = $scheduleStatement->get_result()->fetch_all(PDO::FETCH_ASSOC);
$scheduleStatement->close();

$flashSaleCounts = ['live' => 0, 'upcoming' => 0, 'ended' => 0, 'cancelled' => 0];
foreach ($scheduledDrops as &$drop) {
    $dropStart = new DateTimeImmutable($drop['starts_at'], $timezone);
    $dropEnd = new DateTimeImmutable($drop['ends_at'], $timezone);
    if ($drop['status'] === 'cancelled') {
        $drop['state_key'] = 'cancelled';
        $drop['state_label'] = 'Cancelled';
    } elseif ($dropEnd <= $now) {
        $drop['state_key'] = 'ended';
        $drop['state_label'] = 'Ended';
    } elseif ($dropStart <= $now) {
        $drop['state_key'] = 'live';
        $drop['state_label'] = 'Live now';
    } else {
        $drop['state_key'] = 'upcoming';
        $drop['state_label'] = 'Scheduled';
    }
    $flashSaleCounts[$drop['state_key']]++;
}
unset($drop);

$stateOrder = ['live' => 0, 'upcoming' => 1, 'ended' => 2, 'cancelled' => 3];
usort($scheduledDrops, static function (array $left, array $right) use ($stateOrder): int {
    $stateComparison = $stateOrder[$left['state_key']] <=> $stateOrder[$right['state_key']];
    if ($stateComparison !== 0) {
        return $stateComparison;
    }

    return in_array($left['state_key'], ['ended', 'cancelled'], true)
        ? strcmp($right['starts_at'], $left['starts_at'])
        : strcmp($left['starts_at'], $right['starts_at']);
});

$pageTitle = 'Manage Flash Sales | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell admin-dashboard-shell flash-sale-admin-shell">
        <?php
        $activePage = 'flash_sale';
        require __DIR__ . '/../app/views/components/watermark.php';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>
        <main class="admin-dashboard flash-sale-admin">
            <header class="admin-dashboard-header flash-sale-admin-header">
                <div>
                    <p class="admin-kicker">Store promotions</p>
                    <h1>Flash sale manager</h1>
                    <p class="flash-sale-admin-subtitle">Plan limited-time drops, monitor their status, and keep every offer on schedule.</p>
                </div>
                <div class="flash-sale-admin-header-actions">
                    <span class="admin-badge"><?= store_escape($timezone->getName()) ?> time</span>
                    <a class="admin-inventory-back" href="admin-dashboard">Admin dashboard</a>
                </div>
            </header>

            <?php if ($notice !== ''): ?>
                <p class="admin-notice<?= $noticeIsError ? ' admin-notice--error' : '' ?>" role="<?= $noticeIsError ? 'alert' : 'status' ?>"><?= store_escape($notice) ?></p>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <p class="admin-notice admin-notice--error" role="alert"><?= store_escape($error) ?></p>
            <?php endif; ?>

            <section class="flash-sale-overview" aria-label="Flash sale overview">
                <article class="flash-sale-overview-card flash-sale-overview-card--live">
                    <span class="flash-sale-overview-icon" aria-hidden="true">●</span>
                    <div><p>Live now</p><strong><?= (int) $flashSaleCounts['live'] ?></strong><small>Offers customers can shop</small></div>
                </article>
                <article class="flash-sale-overview-card">
                    <span class="flash-sale-overview-icon" aria-hidden="true">◷</span>
                    <div><p>Upcoming</p><strong><?= (int) $flashSaleCounts['upcoming'] ?></strong><small>Scheduled to start later</small></div>
                </article>
                <article class="flash-sale-overview-card">
                    <span class="flash-sale-overview-icon" aria-hidden="true">✓</span>
                    <div><p>Ended</p><strong><?= (int) $flashSaleCounts['ended'] ?></strong><small>Completed drops</small></div>
                </article>
                <article class="flash-sale-overview-card">
                    <span class="flash-sale-overview-icon" aria-hidden="true">%</span>
                    <div><p>Ready to schedule</p><strong><?= count($eligibleProducts) ?></strong><small>Discounted variants in stock</small></div>
                </article>
            </section>

            <section class="flash-sale-panel flash-sale-create-panel" aria-labelledby="schedule-drop-title" id="create-drop">
                <div class="flash-sale-panel-heading">
                    <div>
                        <p class="flash-sale-section-kicker">Create campaign</p>
                        <h2 id="schedule-drop-title">Schedule a flash sale</h2>
                        <p>Choose a discounted, in-stock variant and set when its drop begins and ends. Updating a product price alone does not publish it as a flash sale.</p>
                    </div>
                    <span class="flash-sale-step-mark" aria-hidden="true">01</span>
                </div>
                <?php if (!$eligibleProducts): ?>
                    <p class="flash-sale-empty-notice">No active, discounted, in-stock product variants are available to schedule. Set a sale price and stock on a product first.</p>
                <?php else: ?>
                    <form method="post" action="flash-sale-admin" class="flash-sale-schedule-form">
                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                        <label class="flash-sale-field flash-sale-field--product" for="sale-variant">
                            <span>Product and variant</span>
                            <select id="sale-variant" name="variant_id" required>
                                <option value="">Select an eligible product</option>
                                <?php foreach ($eligibleProducts as $product): ?>
                                    <?php
                                    $productPrice = (float) $product['price'];
                                    $productComparePrice = (float) $product['compare_at_price'];
                                    $productDiscountPercent = $productComparePrice > 0
                                        ? (int) round((1 - $productPrice / $productComparePrice) * 100)
                                        : 0;
                                    ?>
                                    <option value="<?= (int) $product['variant_id'] ?>" data-price="<?= store_escape((string) $product['price']) ?>" data-compare-price="<?= store_escape((string) $product['compare_at_price']) ?>" data-stock="<?= (int) $product['stock_quantity'] ?>" data-discount="<?= $productDiscountPercent ?>" <?= $form['variant_id'] === (string) $product['variant_id'] ? 'selected' : '' ?>>
                                        <?= store_escape($product['product_name'] . ' / ' . $product['variant_name']) ?> — <?= store_escape(store_format_price($productPrice)) ?> (<?= (int) $product['stock_quantity'] ?> in stock)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="flash-sale-field" for="sale-start">
                            <span>Starts at</span>
                            <input id="sale-start" name="starts_at" type="datetime-local" min="<?= store_escape($now->format('Y-m-d\TH:i')) ?>" value="<?= store_escape($form['starts_at']) ?>" required>
                        </label>
                        <label class="flash-sale-field" for="sale-end">
                            <span>Ends at</span>
                            <input id="sale-end" name="ends_at" type="datetime-local" min="<?= store_escape($now->format('Y-m-d\TH:i')) ?>" value="<?= store_escape($form['ends_at']) ?>" required>
                        </label>
                        <div class="flash-sale-product-summary" id="sale-product-summary" aria-live="polite">
                            <span class="flash-sale-summary-label">Offer preview</span>
                            <strong id="sale-preview-price">Select a product to preview its offer</strong>
                            <span id="sale-preview-details">The discounted price and available stock will appear here.</span>
                        </div>
                        <div class="flash-sale-form-footer">
                            <p>All dates and times use <?= store_escape($timezone->getName()) ?>.</p>
                            <button type="submit" name="action" value="schedule">Schedule drop <span aria-hidden="true">→</span></button>
                            <button type="submit" name="action" value="flash_sale_now" formnovalidate>Flash sale now · 1 hour <span aria-hidden="true">→</span></button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="flash-sale-panel flash-sale-campaigns-panel" aria-labelledby="scheduled-drops-title" id="campaigns">
                <div class="flash-sale-panel-heading flash-sale-campaign-heading">
                    <div>
                        <p class="flash-sale-section-kicker">Campaign control</p>
                        <h2 id="scheduled-drops-title">All flash sales</h2>
                        <p>Review pricing, inventory, timing, and status. Scheduled drops can be cancelled before they end.</p>
                    </div>
                    <a class="flash-sale-secondary-action" href="flash-sale">View storefront <span aria-hidden="true">↗</span></a>
                </div>
                <div class="flash-sale-toolbar">
                    <label class="flash-sale-search" for="sale-search">
                        <span class="sr-only">Search flash sales</span>
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
                        <input id="sale-search" type="search" placeholder="Search products or variants">
                    </label>
                    <label class="flash-sale-filter" for="sale-status-filter">
                        <span>Status</span>
                        <select id="sale-status-filter">
                            <option value="all">All campaigns (<?= count($scheduledDrops) ?>)</option>
                            <option value="live">Live now (<?= (int) $flashSaleCounts['live'] ?>)</option>
                            <option value="upcoming">Upcoming (<?= (int) $flashSaleCounts['upcoming'] ?>)</option>
                            <option value="ended">Ended (<?= (int) $flashSaleCounts['ended'] ?>)</option>
                            <option value="cancelled">Cancelled (<?= (int) $flashSaleCounts['cancelled'] ?>)</option>
                        </select>
                    </label>
                    <a class="flash-sale-create-link" href="#create-drop">+ New drop</a>
                </div>
                <?php if (!$scheduledDrops): ?>
                    <div class="flash-sale-empty-state">
                        <span aria-hidden="true">✦</span>
                        <h3>No campaigns yet</h3>
                        <p>Your scheduled, live, and completed drops will appear here.</p>
                        <?php if ($eligibleProducts): ?><a href="#create-drop">Schedule your first drop</a><?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="flash-sale-table-wrap">
                        <table class="flash-sale-table">
                            <thead><tr><th>Product</th><th>Offer</th><th>Inventory</th><th>Schedule</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
                            <tbody>
                                <?php foreach ($scheduledDrops as $drop): ?>
                                    <?php
                                    $dropStart = new DateTimeImmutable($drop['starts_at'], $timezone);
                                    $dropEnd = new DateTimeImmutable($drop['ends_at'], $timezone);
                                    $dropComparePrice = (float) ($drop['compare_at_price'] ?? 0);
                                    $dropPrice = (float) $drop['price'];
                                    $dropDiscountPercent = $dropComparePrice > 0 && $dropComparePrice > $dropPrice
                                        ? (int) round((1 - $dropPrice / $dropComparePrice) * 100)
                                        : 0;
                                    ?>
                                    <tr data-campaign-row data-state="<?= store_escape($drop['state_key']) ?>" data-search="<?= store_escape(mb_strtolower($drop['product_name'] . ' ' . $drop['variant_name'])) ?>">
                                        <td data-label="Product">
                                            <span class="flash-sale-product-name"><?= store_escape($drop['product_name']) ?></span>
                                            <span class="flash-sale-variant-name"><?= store_escape($drop['variant_name']) ?></span>
                                        </td>
                                        <td data-label="Offer">
                                            <?php if ($dropDiscountPercent > 0): ?>
                                                <span class="flash-sale-price"><?= store_escape(store_format_price($dropPrice)) ?></span>
                                                <del><?= store_escape(store_format_price($dropComparePrice)) ?></del>
                                                <span class="flash-sale-discount">−<?= $dropDiscountPercent ?>%</span>
                                            <?php else: ?>
                                                <span class="flash-sale-price"><?= store_escape(store_format_price($dropPrice)) ?></span>
                                                <span class="flash-sale-no-discount">Price updated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Inventory">
                                            <span class="flash-sale-stock<?= (int) $drop['stock_quantity'] < 6 ? ' is-low' : '' ?>"><?= (int) $drop['stock_quantity'] ?> available</span>
                                        </td>
                                        <td data-label="Schedule">
                                            <span class="flash-sale-date-label">Starts</span><time datetime="<?= store_escape($dropStart->format(DateTimeInterface::ATOM)) ?>"><?= store_escape($dropStart->format('M j, Y · g:i a')) ?></time>
                                            <span class="flash-sale-date-label">Ends</span><time datetime="<?= store_escape($dropEnd->format(DateTimeInterface::ATOM)) ?>"><?= store_escape($dropEnd->format('M j, Y · g:i a')) ?></time>
                                        </td>
                                        <td data-label="Status"><span class="flash-sale-status flash-sale-status--<?= store_escape($drop['state_key']) ?>"><?= store_escape($drop['state_label']) ?></span></td>
                                        <td data-label="Actions">
                                            <?php if ($drop['status'] === 'scheduled' && $dropEnd > $now): ?>
                                                <form method="post" action="flash-sale-admin" onsubmit="return confirm('Cancel this flash sale drop? Customers will no longer see it as a scheduled offer.');">
                                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="flash_sale_id" value="<?= (int) $drop['flash_sale_id'] ?>">
                                                    <button class="flash-sale-cancel-button" type="submit">Cancel drop</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="flash-sale-no-action">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="flash-sale-filter-empty" id="sale-filter-empty" hidden>No campaigns match those filters.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    <script>
        (() => {
            const productSelect = document.getElementById('sale-variant');
            const previewPrice = document.getElementById('sale-preview-price');
            const previewDetails = document.getElementById('sale-preview-details');
            const scheduleForm = productSelect?.form;
            const startInput = document.getElementById('sale-start');
            const endInput = document.getElementById('sale-end');
            const searchInput = document.getElementById('sale-search');
            const statusFilter = document.getElementById('sale-status-filter');
            const campaignRows = [...document.querySelectorAll('[data-campaign-row]')];
            const filterEmpty = document.getElementById('sale-filter-empty');

            const updateProductPreview = () => {
                const option = productSelect?.selectedOptions[0];
                if (!option || !previewPrice || !previewDetails) return;
                if (!option.value) {
                    previewPrice.textContent = 'Select a product to preview its offer';
                    previewDetails.textContent = 'The discounted price and available stock will appear here.';
                    return;
                }
                const price = Number(option.dataset.price);
                const comparePrice = Number(option.dataset.comparePrice);
                const discount = Number(option.dataset.discount);
                const stock = Number(option.dataset.stock);
                previewPrice.textContent = `${discount}% off · ${new Intl.NumberFormat(undefined, { style: 'currency', currency: 'PHP' }).format(price)}`;
                previewDetails.textContent = `Was ${new Intl.NumberFormat(undefined, { style: 'currency', currency: 'PHP' }).format(comparePrice)} · ${stock} units available`;
            };

            const validateScheduleWindow = () => {
                if (!startInput || !endInput) return true;
                const invalidWindow = new Date(endInput.value) <= new Date(startInput.value);
                endInput.setCustomValidity(invalidWindow ? 'The end time must be after the start time.' : '');
                return !invalidWindow;
            };

            const filterCampaigns = () => {
                const query = (searchInput?.value ?? '').trim().toLocaleLowerCase();
                const status = statusFilter?.value ?? 'all';
                let visibleCount = 0;
                campaignRows.forEach((row) => {
                    const matchesSearch = row.dataset.search.includes(query);
                    const matchesStatus = status === 'all' || row.dataset.state === status;
                    row.hidden = !matchesSearch || !matchesStatus;
                    if (!row.hidden) visibleCount++;
                });
                if (filterEmpty) filterEmpty.hidden = visibleCount > 0;
            };

            productSelect?.addEventListener('change', updateProductPreview);
            startInput?.addEventListener('change', validateScheduleWindow);
            endInput?.addEventListener('change', validateScheduleWindow);
            scheduleForm?.addEventListener('submit', (event) => {
                if (event.submitter?.value === 'flash_sale_now') return;
                if (!validateScheduleWindow()) {
                    event.preventDefault();
                    endInput?.reportValidity();
                }
            });
            searchInput?.addEventListener('input', filterCampaigns);
            statusFilter?.addEventListener('change', filterCampaigns);
            updateProductPreview();
        })();
    </script>
</body>
</html>
