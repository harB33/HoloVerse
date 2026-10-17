<?php
require_once __DIR__ . '/../app/store.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!store_has_role('admin')) {
    http_response_code(403);
    $accessDenied = true;
} else {
    $accessDenied = false;
}

$categories = [];
$talents = [];
$message = '';
$messageIsError = false;
$createdProductId = 0;
$createdProductStatus = null;
$invalidPostData = false;
$form = [
    'product_name' => '',
    'description' => '',
    'category_id' => '',
    'status' => 'draft',
    'is_preorder' => '0',
    'expected_ship_date' => '',
    'variant_name' => 'Default',
    'sku' => '',
    'price' => '',
    'compare_at_price' => '',
    'stock_quantity' => '0',
    'image_url' => '',
    'alt_text' => '',
    'talent_id' => '',
];

if (!$accessDenied) {
    $categories = $conn->query('SELECT category_id, category_name FROM category_tbl ORDER BY category_name')
        ->fetch_all(MYSQLI_ASSOC);
    $talents = $conn->query('SELECT talent_id, talent_name FROM talent_tbl ORDER BY talent_name')
        ->fetch_all(MYSQLI_ASSOC);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($form as $key => $default) {
            if (array_key_exists($key, $_POST) && !is_string($_POST[$key])) {
                $invalidPostData = true;
                continue;
            }
            $value = $_POST[$key] ?? $default;
            $form[$key] = trim($value);
        }

        if (!store_verify_csrf()) {
            http_response_code(403);
            $message = 'Your session expired. Refresh the page and try again.';
            $messageIsError = true;
        } elseif ($invalidPostData) {
            $message = 'Submit each product field as a single value.';
            $messageIsError = true;
        } else {
            $categoryId = filter_var($form['category_id'], FILTER_VALIDATE_INT);
            $talentId = $form['talent_id'] === '' ? null : filter_var($form['talent_id'], FILTER_VALIDATE_INT);
            $stockQuantity = filter_var($form['stock_quantity'], FILTER_VALIDATE_INT);
            $priceIsValid = preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $form['price']) === 1
                && (float) $form['price'] > 0;
            $comparePriceIsValid = $form['compare_at_price'] === ''
                || (
                    preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $form['compare_at_price']) === 1
                    && (float) $form['compare_at_price'] >= (float) $form['price']
                );
            $categoryIds = array_map(static fn (array $category): int => (int) $category['category_id'], $categories);
            $talentIds = array_map(static fn (array $talent): int => (int) $talent['talent_id'], $talents);
            $validShipDate = $form['expected_ship_date'] === '';
            if (!$validShipDate) {
                $shipDate = DateTimeImmutable::createFromFormat('!Y-m-d', $form['expected_ship_date']);
                $validShipDate = $shipDate !== false && $shipDate->format('Y-m-d') === $form['expected_ship_date'];
            }
            $imageUrl = $form['image_url'];
            $imageUrlIsValid = $imageUrl === ''
                || (
                    filter_var($imageUrl, FILTER_VALIDATE_URL)
                    && in_array(strtolower((string) parse_url($imageUrl, PHP_URL_SCHEME)), ['http', 'https'], true)
                )
                || preg_match('~^(?!/)(?!.*(?:^|/)\.\.(?:/|$))[A-Za-z0-9][A-Za-z0-9._/-]{0,254}$~D', $imageUrl) === 1;

            if (
                $form['product_name'] === ''
                || strlen($form['product_name']) > 255
                || $categoryId === false
                || !in_array((int) $categoryId, $categoryIds, true)
                || !in_array($form['status'], ['draft', 'active'], true)
                || !in_array($form['is_preorder'], ['0', '1'], true)
                || ($form['is_preorder'] === '1' && !$validShipDate)
                || strlen($form['variant_name']) < 1
                || strlen($form['variant_name']) > 100
                || strlen($form['sku']) < 1
                || strlen($form['sku']) > 50
                || !$priceIsValid
                || !$comparePriceIsValid
                || $stockQuantity === false
                || $stockQuantity < 0
                || ($talentId !== null && ($talentId === false || !in_array((int) $talentId, $talentIds, true)))
                || strlen($imageUrl) > 255
                || !$imageUrlIsValid
                || strlen($form['alt_text']) > 255
            ) {
                $message = 'Check the required fields and make sure each value is valid.';
                $messageIsError = true;
            } else {
                $productName = $form['product_name'];
                $description = $form['description'];
                $status = $form['status'];
                $isPreorder = (int) $form['is_preorder'];
                $expectedShipDate = $isPreorder === 1 && $form['expected_ship_date'] !== ''
                    ? $form['expected_ship_date']
                    : null;
                $variantName = $form['variant_name'];
                $sku = $form['sku'];
                $price = $form['price'];
                $compareAtPrice = $form['compare_at_price'] === '' ? null : $form['compare_at_price'];
                $stockQuantity = (int) $stockQuantity;
                $altText = $form['alt_text'] !== '' ? $form['alt_text'] : $productName;

                try {
                    $conn->begin_transaction();

                    $statement = $conn->prepare(
                        'INSERT INTO product_tbl (category_id, product_name, description, status, is_preorder, expected_ship_date)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $categoryId = (int) $categoryId;
                    $statement->bind_param('isssis', $categoryId, $productName, $description, $status, $isPreorder, $expectedShipDate);
                    $statement->execute();
                    $createdProductId = (int) $conn->insert_id;
                    $statement->close();

                    $currency = 'PHP';
                    $statement = $conn->prepare(
                        'INSERT INTO product_variants_tbl (product_id, variant_name, sku, price, compare_at_price, currency, stock_quantity)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $statement->bind_param('isssssi', $createdProductId, $variantName, $sku, $price, $compareAtPrice, $currency, $stockQuantity);
                    $statement->execute();
                    $statement->close();

                    if ($imageUrl !== '') {
                        $statement = $conn->prepare(
                            'INSERT INTO product_image_tbl (product_id, image_url, alt_text, sort_order)
                             VALUES (?, ?, ?, 0)'
                        );
                        $statement->bind_param('iss', $createdProductId, $imageUrl, $altText);
                        $statement->execute();
                        $statement->close();
                    }

                    if ($talentId !== null) {
                        $talentId = (int) $talentId;
                        $statement = $conn->prepare('INSERT INTO product_talents_tbl (product_id, talent_id) VALUES (?, ?)');
                        $statement->bind_param('ii', $createdProductId, $talentId);
                        $statement->execute();
                        $statement->close();
                    }

                    $conn->commit();
                    $_SESSION['product_created_message'] = 'Product saved successfully.';
                    $_SESSION['product_created_status'] = $status;
                    header('Location: product-input.php?created=' . $createdProductId);
                    exit;
                } catch (mysqli_sql_exception $error) {
                    $conn->rollback();
                    if ($error->getCode() !== 1062) {
                        throw $error;
                    }
                    $message = 'That SKU is already in use. Enter a unique SKU and try again.';
                    $messageIsError = true;
                } catch (Throwable $error) {
                    $conn->rollback();
                    throw $error;
                }
            }
        }
    } else {
        $createdProductId = filter_input(INPUT_GET, 'created', FILTER_VALIDATE_INT) ?: 0;
        if ($createdProductId > 0) {
            $message = $_SESSION['product_created_message'] ?? 'Product saved successfully.';
            $createdProductStatus = $_SESSION['product_created_status'] ?? null;
            unset($_SESSION['product_created_message']);
            unset($_SESSION['product_created_status']);
        }
    }
}

$pageTitle = $accessDenied ? 'Access denied | HoloVerse' : 'Add a product | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="min-h-screen border-t-2 border-sky-500 bg-slate-100 font-['Kanit'] text-slate-800">
    <?php
    $activePage = '';
    require __DIR__ . '/../app/views/components/site-header.php';
    ?>

    <main class="mx-auto w-full max-w-5xl px-4 pb-8 pt-8 sm:px-8 lg:px-12">
        <?php if ($accessDenied): ?>
            <section class="mx-auto mt-12 max-w-xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <h1 class="text-2xl font-bold">Administrator access required</h1>
                <p class="mt-3 text-slate-600">Your account is not allowed to add catalog products.</p>
                <a class="mt-6 inline-flex rounded-lg bg-slate-800 px-5 py-3 font-medium text-white" href="../index.php">Return to the shop</a>
            </section>
        <?php else: ?>
            <section class="mx-auto mt-8 max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-300/30 sm:p-9">
                <header class="mb-8">
                    <span class="text-sm font-semibold uppercase tracking-wider text-sky-700">Catalog management</span>
                    <h1 class="mt-2 text-3xl font-bold">Add a product</h1>
                    <p class="mt-3 text-slate-600">Product details and inventory are saved directly to the HoloVerse catalog.</p>
                </header>

                <?php if ($message !== ''): ?>
                    <p class="mb-6 rounded-lg px-4 py-3 text-sm <?= $messageIsError ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' ?>" role="<?= $messageIsError ? 'alert' : 'status' ?>">
                        <?= store_escape($message) ?>
                        <?php if (!$messageIsError && $createdProductId > 0 && $createdProductStatus === 'active'): ?>
                            <a class="ml-2 font-semibold underline" href="product.php?id=<?= $createdProductId ?>">View product</a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if (!$categories): ?>
                    <p class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">No product categories are set up. Add categories to the database before creating a product.</p>
                <?php else: ?>
                    <form method="post" action="product-input.php" class="space-y-7">
                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">

                        <fieldset class="grid gap-5 sm:grid-cols-2">
                            <legend class="mb-4 text-lg font-semibold">Product details</legend>
                            <div class="sm:col-span-2">
                                <label for="product-name" class="mb-2 block text-sm font-medium">Product name</label>
                                <input id="product-name" name="product_name" type="text" maxlength="255" required value="<?= store_escape($form['product_name']) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="description" class="mb-2 block text-sm font-medium">Description <span class="font-normal text-slate-500">(optional)</span></label>
                                <textarea id="description" name="description" rows="4"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 outline-none focus:border-sky-400 focus:ring-4 focus:ring-sky-100"><?= store_escape($form['description']) ?></textarea>
                            </div>
                            <div>
                                <label for="category-id" class="mb-2 block text-sm font-medium">Category</label>
                                <select id="category-id" name="category_id" required class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                    <option value="">Choose a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= (int) $category['category_id'] ?>" <?= $form['category_id'] === (string) $category['category_id'] ? 'selected' : '' ?>>
                                            <?= store_escape($category['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="status" class="mb-2 block text-sm font-medium">Catalog status</label>
                                <select id="status" name="status" class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                    <option value="draft" <?= $form['status'] === 'draft' ? 'selected' : '' ?>>Draft (not visible in shop)</option>
                                    <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active (publish now)</option>
                                </select>
                            </div>
                            <div>
                                <label for="talent-id" class="mb-2 block text-sm font-medium">Associated talent <span class="font-normal text-slate-500">(optional)</span></label>
                                <select id="talent-id" name="talent_id" class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                    <option value="">No talent association</option>
                                    <?php foreach ($talents as $talent): ?>
                                        <option value="<?= (int) $talent['talent_id'] ?>" <?= $form['talent_id'] === (string) $talent['talent_id'] ? 'selected' : '' ?>>
                                            <?= store_escape($talent['talent_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex items-center gap-3 self-end pb-3">
                                <input id="is-preorder" name="is_preorder" type="checkbox" value="1" <?= $form['is_preorder'] === '1' ? 'checked' : '' ?> class="size-4 accent-sky-600">
                                <label for="is-preorder" class="text-sm font-medium">This product is a pre-order</label>
                            </div>
                            <div class="sm:col-span-2">
                                <label for="expected-ship-date" class="mb-2 block text-sm font-medium">Expected ship date <span class="font-normal text-slate-500">(optional)</span></label>
                                <input id="expected-ship-date" name="expected_ship_date" type="date" value="<?= store_escape($form['expected_ship_date']) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 sm:max-w-xs">
                            </div>
                        </fieldset>

                        <fieldset class="grid gap-5 border-t border-slate-100 pt-6 sm:grid-cols-2">
                            <legend class="text-lg font-semibold">Variant and inventory</legend>
                            <div>
                                <label for="variant-name" class="mb-2 block text-sm font-medium">Variant name</label>
                                <input id="variant-name" name="variant_name" type="text" maxlength="100" required value="<?= store_escape($form['variant_name']) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            </div>
                            <div>
                                <label for="sku" class="mb-2 block text-sm font-medium">SKU</label>
                                <input id="sku" name="sku" type="text" maxlength="50" required value="<?= store_escape($form['sku']) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            </div>
                            <div>
                                <label for="price" class="mb-2 block text-sm font-medium">Price (PHP)</label>
                                <input id="price" name="price" type="number" min="0.01" max="99999999.99" step="0.01" required value="<?= store_escape($form['price']) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            </div>
                            <div>
                                <label for="compare-at-price" class="mb-2 block text-sm font-medium">Original price <span class="font-normal text-slate-500">(optional)</span></label>
                                <input id="compare-at-price" name="compare_at_price" type="number" min="0.01" max="99999999.99" step="0.01" value="<?= store_escape($form['compare_at_price']) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            </div>
                            <div>
                                <label for="stock-quantity" class="mb-2 block text-sm font-medium">Stock quantity</label>
                                <input id="stock-quantity" name="stock_quantity" type="number" min="0" max="2147483647" step="1" required value="<?= store_escape($form['stock_quantity']) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            </div>
                        </fieldset>

                        <fieldset class="grid gap-5 border-t border-slate-100 pt-6">
                            <legend class="text-lg font-semibold">Product image</legend>
                            <div>
                                <label for="image-url" class="mb-2 block text-sm font-medium">Image URL or local asset path <span class="font-normal text-slate-500">(optional)</span></label>
                                <input id="image-url" name="image_url" type="text" maxlength="255" placeholder="assets/images/product.jpg" value="<?= store_escape($form['image_url']) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            </div>
                            <div>
                                <label for="alt-text" class="mb-2 block text-sm font-medium">Image description <span class="font-normal text-slate-500">(optional)</span></label>
                                <input id="alt-text" name="alt_text" type="text" maxlength="255" value="<?= store_escape($form['alt_text']) ?>"
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            </div>
                        </fieldset>

                        <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 pt-6">
                            <button type="submit" class="rounded-lg bg-slate-800 px-6 py-3 font-semibold text-white transition hover:bg-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                Save product
                            </button>
                            <a href="../index.php" class="text-sm font-medium text-slate-600 underline">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
