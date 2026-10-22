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
                    <a class="mt-5 inline-flex rounded-lg bg-slate-800 px-5 py-3 text-sm font-semibold text-white no-underline hover:bg-slate-900" href="flash_sale">Return to flash sales</a>
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
    } elseif ($error === '' && $action === 'schedule') {
        $variantId = filter_var($form['variant_id'], FILTER_VALIDATE_INT);
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

        if (
            $variantId === false || $variantId < 1
            || !$validStart || !$validEnd
            || $startsAt <= $now || $endsAt <= $startsAt
        ) {
            $error = 'Choose a discounted product, a future start time, and an end time after the start.';
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

                        $_SESSION['flash_sale_admin_notice'] = 'The flash sale drop was scheduled.';
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

$eligibleProducts = $conn->query(
    "SELECT v.variant_id, v.variant_name, v.price, v.compare_at_price, v.stock_quantity,
            p.product_name
     FROM product_variants_tbl v
     INNER JOIN product_tbl p ON p.product_id = v.product_id
     WHERE p.status = 'active' AND v.stock_quantity > 0
       AND v.compare_at_price IS NOT NULL AND v.compare_at_price > v.price
     ORDER BY p.product_name, v.variant_name"
)->fetch_all(PDO::FETCH_ASSOC);

$scheduleStatement = $conn->prepare(
    'SELECT fs.flash_sale_id, fs.starts_at, fs.ends_at, fs.status, fs.created_at,
            v.variant_name, v.price, v.compare_at_price, p.product_name
     FROM flash_sale_tbl fs
     INNER JOIN product_variants_tbl v ON v.variant_id = fs.variant_id
     INNER JOIN product_tbl p ON p.product_id = v.product_id
     ORDER BY fs.starts_at DESC, fs.flash_sale_id DESC'
);
$scheduleStatement->execute();
$scheduledDrops = $scheduleStatement->get_result()->fetch_all(PDO::FETCH_ASSOC);
$scheduleStatement->close();

$pageTitle = 'Manage Flash Sales | HoloVerse';
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
        <main class="mx-auto w-full max-w-6xl px-4 pb-16 pt-8 sm:px-8">
            <header class="mb-8">
                <p class="m-0 text-xs font-bold uppercase tracking-[0.15em] text-sky-700">Flash sale operations</p>
                <h1 class="mb-2 mt-2 text-3xl font-bold text-slate-900">Manage scheduled drops</h1>
                <p class="m-0 max-w-2xl text-sm leading-relaxed text-slate-600">Schedule active discounted products. Times use <?= store_escape($timezone->getName()) ?>.</p>
            </header>

            <?php if ($notice !== ''): ?>
                <p class="mb-5 rounded-xl px-4 py-3 text-sm <?= $noticeIsError ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' ?>" role="<?= $noticeIsError ? 'alert' : 'status' ?>"><?= store_escape($notice) ?></p>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
                <p class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert"><?= store_escape($error) ?></p>
            <?php endif; ?>

            <section class="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="schedule-drop-title">
                <h2 id="schedule-drop-title" class="mb-5 mt-0 text-xl font-bold text-slate-900">Schedule a flash sale</h2>
                <?php if (!$eligibleProducts): ?>
                    <p class="m-0 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">No active, discounted, in-stock product variants are available to schedule.</p>
                <?php else: ?>
                    <form method="post" action="flash-sale-admin" class="grid gap-5 md:grid-cols-2">
                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                        <input type="hidden" name="action" value="schedule">
                        <label class="grid gap-2 text-sm font-semibold text-slate-700 md:col-span-2" for="sale-variant">
                            Discounted product
                            <select id="sale-variant" name="variant_id" required class="rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                                <option value="">Choose a product</option>
                                <?php foreach ($eligibleProducts as $product): ?>
                                    <option value="<?= (int) $product['variant_id'] ?>" <?= $form['variant_id'] === (string) $product['variant_id'] ? 'selected' : '' ?>>
                                        <?= store_escape($product['product_name'] . ' / ' . $product['variant_name']) ?>
                                        — <?= store_escape(store_format_price((float) $product['price'])) ?>
                                        (<?= (int) $product['stock_quantity'] ?> in stock)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="grid gap-2 text-sm font-semibold text-slate-700" for="sale-start">
                            Starts at
                            <input id="sale-start" name="starts_at" type="datetime-local" min="<?= store_escape($now->format('Y-m-d\TH:i')) ?>" value="<?= store_escape($form['starts_at']) ?>" required class="rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                        </label>
                        <label class="grid gap-2 text-sm font-semibold text-slate-700" for="sale-end">
                            Ends at
                            <input id="sale-end" name="ends_at" type="datetime-local" min="<?= store_escape($now->format('Y-m-d\TH:i')) ?>" value="<?= store_escape($form['ends_at']) ?>" required class="rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                        </label>
                        <div class="md:col-span-2">
                            <button type="submit" class="rounded-lg bg-sky-600 px-5 py-3 text-sm font-bold text-white hover:bg-sky-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">Schedule drop</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="scheduled-drops-title">
                <h2 id="scheduled-drops-title" class="mb-5 mt-0 text-xl font-bold text-slate-900">Drop schedule</h2>
                <?php if (!$scheduledDrops): ?>
                    <p class="m-0 text-sm text-slate-600">No drops have been scheduled yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[700px] border-collapse text-left text-sm">
                            <thead><tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500"><th class="px-3 py-3">Product</th><th class="px-3 py-3">Starts</th><th class="px-3 py-3">Ends</th><th class="px-3 py-3">State</th><th class="px-3 py-3"><span class="sr-only">Actions</span></th></tr></thead>
                            <tbody>
                                <?php foreach ($scheduledDrops as $drop): ?>
                                    <?php
                                    $dropStart = new DateTimeImmutable($drop['starts_at'], $timezone);
                                    $dropEnd = new DateTimeImmutable($drop['ends_at'], $timezone);
                                    $dropState = $drop['status'] === 'cancelled'
                                        ? 'Cancelled'
                                        : ($dropEnd <= $now ? 'Archived' : ($dropStart <= $now ? 'Live' : 'Scheduled'));
                                    ?>
                                    <tr class="border-b border-slate-100 align-top last:border-0">
                                        <td class="px-3 py-4 font-semibold text-slate-800"><?= store_escape($drop['product_name'] . ' / ' . $drop['variant_name']) ?></td>
                                        <td class="px-3 py-4 text-slate-600"><?= store_escape($dropStart->format('M j, Y g:i a')) ?></td>
                                        <td class="px-3 py-4 text-slate-600"><?= store_escape($dropEnd->format('M j, Y g:i a')) ?></td>
                                        <td class="px-3 py-4 text-slate-600"><?= store_escape($dropState) ?></td>
                                        <td class="px-3 py-4">
                                            <?php if ($drop['status'] === 'scheduled' && $dropEnd > $now): ?>
                                                <form method="post" action="flash-sale-admin">
                                                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="flash_sale_id" value="<?= (int) $drop['flash_sale_id'] ?>">
                                                    <button type="submit" class="rounded-md border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>
</html>
