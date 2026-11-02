<?php
require_once __DIR__ . '/../app/store.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login');
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
$createdProductName = '';
$createdProductStatus = null;
$invalidPostData = false;
$additionalImageAltTexts = [''];
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
        ->fetch_all(PDO::FETCH_ASSOC);
    $talents = $conn->query('SELECT talent_id, talent_name FROM talent_tbl ORDER BY talent_name')
        ->fetch_all(PDO::FETCH_ASSOC);

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        foreach ($form as $key => $default) {
            if (array_key_exists($key, $_POST) && !is_string($_POST[$key])) {
                $invalidPostData = true;
                continue;
            }
            $form[$key] = trim($_POST[$key] ?? $default);
        }

        $readImageInputs = static function (mixed $input) use (&$invalidPostData): array {
            if (!is_array($input) || count($input) > 9) {
                $invalidPostData = true;
                return [];
            }

            $values = [];
            foreach ($input as $value) {
                if (!is_string($value)) {
                    $invalidPostData = true;
                    $values[] = '';
                    continue;
                }
                $values[] = trim($value);
            }

            return $values;
        };
        $additionalImageAltTexts = $readImageInputs($_POST['additional_image_alt_texts'] ?? []);
        $normalizeUploads = static function (mixed $fileBag, bool $multiple) use (&$invalidPostData): array {
            $fields = ['name', 'tmp_name', 'error', 'size'];
            if (!is_array($fileBag)) {
                $invalidPostData = true;
                return [];
            }

            if (!$multiple) {
                foreach ($fields as $field) {
                    if (!array_key_exists($field, $fileBag) || is_array($fileBag[$field])) {
                        $invalidPostData = true;
                        return [];
                    }
                }
                return [$fileBag];
            }

            if (!isset($fileBag['name']) || !is_array($fileBag['name']) || count($fileBag['name']) > 9) {
                $invalidPostData = true;
                return [];
            }

            $entries = [];
            foreach ($fileBag['name'] as $index => $name) {
                $entry = [];
                foreach ($fields as $field) {
                    if (!isset($fileBag[$field]) || !is_array($fileBag[$field]) || !array_key_exists($index, $fileBag[$field])) {
                        $invalidPostData = true;
                        continue 2;
                    }
                    $entry[$field] = $fileBag[$field][$index];
                }
                $entries[] = $entry;
            }

            return $entries;
        };
        $emptyUpload = ['name' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0];
        $mainImageUploads = $normalizeUploads($_FILES['main_image'] ?? $emptyUpload, false);
        $additionalImageUploads = $normalizeUploads(
            $_FILES['additional_images'] ?? ['name' => [], 'tmp_name' => [], 'error' => [], 'size' => []],
            true
        );

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
            $imageUploads = [];
            $imageUploadsAreValid = true;
            $allowedImageMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];
            $imageEntries = [];
            foreach ($mainImageUploads as $upload) {
                $imageEntries[] = ['upload' => $upload, 'alt_text' => $form['alt_text']];
            }
            foreach ($additionalImageUploads as $index => $upload) {
                $imageEntries[] = ['upload' => $upload, 'alt_text' => $additionalImageAltTexts[$index] ?? ''];
            }
            $imageInfo = new finfo(FILEINFO_MIME_TYPE);
            foreach ($imageEntries as $imageEntry) {
                $upload = $imageEntry['upload'];
                $imageAltText = $imageEntry['alt_text'];
                $uploadError = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($uploadError === UPLOAD_ERR_NO_FILE) {
                    $imageUploadsAreValid = $imageUploadsAreValid && $imageAltText === '';
                    continue;
                }

                $temporaryPath = $upload['tmp_name'] ?? '';
                $uploadSize = (int) ($upload['size'] ?? 0);
                if (
                    $uploadError !== UPLOAD_ERR_OK
                    || !is_string($temporaryPath)
                    || !is_uploaded_file($temporaryPath)
                    || $uploadSize < 1
                    || $uploadSize > 8 * 1024 * 1024
                    || strlen($imageAltText) > 255
                ) {
                    $imageUploadsAreValid = false;
                    continue;
                }

                $mimeType = $imageInfo->file($temporaryPath);
                $imageDimensions = getimagesize($temporaryPath);
                if (!isset($allowedImageMimeTypes[$mimeType]) || $imageDimensions === false) {
                    $imageUploadsAreValid = false;
                    continue;
                }

                $imageUploads[] = [
                    'tmp_path' => $temporaryPath,
                    'extension' => $allowedImageMimeTypes[$mimeType],
                    'alt_text' => $imageAltText,
                ];
            }

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
                || !$imageUploadsAreValid
            ) {
                $message = !$imageUploadsAreValid
                    ? 'Images must be JPG, PNG, GIF, or WebP files no larger than 8 MB each.'
                    : 'Check the required fields and make sure each value is valid.';
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
                $storedImagePaths = [];

                try {
                    $conn->begin_transaction();

                    $statement = $conn->prepare(
                        'INSERT INTO product_tbl (category_id, product_name, description, status, is_preorder, expected_ship_date)
                         VALUES (?, ?, ?, ?, ?, ?) RETURNING product_id'
                    );
                    $categoryId = (int) $categoryId;
                    $statement->bind_param('isssis', $categoryId, $productName, $description, $status, $isPreorder, $expectedShipDate);
                    $statement->execute();
                    $createdProduct = $statement->get_result()->fetch_assoc();
                    $createdProductId = (int) ($createdProduct['product_id'] ?? 0);
                    if ($createdProductId < 1) {
                        throw new RuntimeException('PostgreSQL did not return the new product ID.');
                    }
                    $statement->close();

                    $currency = 'PHP';
                    $statement = $conn->prepare(
                        'INSERT INTO product_variants_tbl (product_id, variant_name, sku, price, compare_at_price, currency, stock_quantity)
                         VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING variant_id'
                    );
                    $statement->bind_param('isssssi', $createdProductId, $variantName, $sku, $price, $compareAtPrice, $currency, $stockQuantity);
                    $statement->execute();
                    $createdVariant = $statement->get_result()->fetch_assoc();
                    $createdVariantId = (int) ($createdVariant['variant_id'] ?? 0);
                    $statement->close();
                    if ($createdVariantId < 1) {
                        throw new RuntimeException('PostgreSQL did not return the new product variant ID.');
                    }
                    if ($stockQuantity > 0) {
                        store_record_inventory_movement(
                            $conn,
                            $createdVariantId,
                            $productName,
                            $variantName,
                            $sku,
                            'initial_stock',
                            $stockQuantity,
                            0,
                            $stockQuantity,
                            'Initial stock entered during product creation',
                            (int) $_SESSION['user_id']
                        );
                    }

                    if ($imageUploads) {
                        $uploadDirectory = dirname(__DIR__) . '/Assets/images/products';
                        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                            throw new RuntimeException('The product image upload folder could not be created.');
                        }
                        if (!is_writable($uploadDirectory)) {
                            throw new RuntimeException('The product image upload folder is not writable.');
                        }
                    }

                    foreach ($imageUploads as $sortOrder => $image) {
                        $imageFilename = bin2hex(random_bytes(16)) . '.' . $image['extension'];
                        $destinationPath = $uploadDirectory . '/' . $imageFilename;
                        if (!move_uploaded_file($image['tmp_path'], $destinationPath)) {
                            throw new RuntimeException('An image could not be saved. Check the upload folder permissions and try again.');
                        }
                        $storedImagePaths[] = $destinationPath;
                        $imageUrl = 'Assets/images/products/' . $imageFilename;
                        $altText = $image['alt_text'] !== ''
                            ? $image['alt_text']
                            : $productName . ($sortOrder === 0 ? '' : ' image ' . ($sortOrder + 1));
                        $statement = $conn->prepare(
                            'INSERT INTO product_image_tbl (product_id, image_url, alt_text, sort_order)
                             VALUES (?, ?, ?, ?)'
                        );
                        $statement->bind_param('issi', $createdProductId, $imageUrl, $altText, $sortOrder);
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
                    header('Location: product-input?created=' . $createdProductId);
                    exit;
                } catch (PDOException $error) {
                    $conn->rollback();
                    foreach ($storedImagePaths as $storedImagePath) {
                        if (is_file($storedImagePath)) {
                            unlink($storedImagePath);
                        }
                    }
                    if ($error->getCode() !== '23505') {
                        throw $error;
                    }
                    $message = 'That SKU is already in use. Enter a unique SKU and try again.';
                    $messageIsError = true;
                } catch (Throwable $error) {
                    $conn->rollback();
                    foreach ($storedImagePaths as $storedImagePath) {
                        if (is_file($storedImagePath)) {
                            unlink($storedImagePath);
                        }
                    }
                    throw $error;
                }
            }
        }
    } else {
        $createdProductId = filter_input(INPUT_GET, 'created', FILTER_VALIDATE_INT) ?: 0;
        if ($createdProductId > 0) {
            $message = $_SESSION['product_created_message'] ?? 'Product saved successfully.';
            $createdProductStatus = $_SESSION['product_created_status'] ?? null;
            $statement = $conn->prepare('SELECT product_name FROM product_tbl WHERE product_id = ? LIMIT 1');
            $statement->bind_param('i', $createdProductId);
            $statement->execute();
            $createdProduct = $statement->get_result()->fetch_assoc();
            $statement->close();
            $createdProductName = is_string($createdProduct['product_name'] ?? null)
                ? $createdProduct['product_name']
                : '';
            unset($_SESSION['product_created_message']);
            unset($_SESSION['product_created_status']);
        }
    }
}

$pageTitle = $accessDenied ? 'Access denied | HoloVerse' : 'Add a product | HoloVerse';
$siteRoot = '../';
$pageStylesheet = $siteRoot . 'assets/css/admin-management.css?v=' . filemtime(__DIR__ . '/../assets/css/admin-management.css');
require __DIR__ . '/../app/views/layout/head.php';
?>

<body class="product-input-page admin-management-page">
    <?php
    $activePage = '';
    require __DIR__ . '/../app/views/components/site-header.php';
    ?>

    <main class="product-input-main">
        <?php if ($accessDenied): ?>
            <section class="product-input-denied">
                <h1 class="text-2xl font-bold">Administrator access required</h1>
                <p class="mt-3 text-slate-600">Your account is not allowed to add catalog products.</p>
                <a class="mt-6 inline-flex rounded-lg bg-slate-800 px-5 py-3 font-medium text-white" href="../home">Return to the shop</a>
            </section>
        <?php else: ?>
            <header class="product-input-heading">
                <div>
                    <span>Catalog management</span>
                    <h1>Add a product</h1>
                    <p>Enter product details, inventory, and images.</p>
                </div>
                <a href="admin-dashboard">Back to dashboard</a>
            </header>

            <section class="product-input-panel">
                <div class="product-input-panel-heading">
                    <h2>Product information</h2>
                    <span>All fields marked optional can be left blank.</span>
                </div>

                <?php if ($message !== ''): ?>
                    <p class="product-input-message<?= $messageIsError ? ' is-error' : '' ?>" role="<?= $messageIsError ? 'alert' : 'status' ?>">
                        <?= store_escape($message) ?>
                        <?php if (!$messageIsError && $createdProductId > 0 && $createdProductStatus === 'active'): ?>
                            <a href="<?= store_escape(store_product_url($createdProductName)) ?>">View product</a>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if (!$categories): ?>
                    <p class="product-input-warning" role="alert">No product categories are set up. Add categories to the database before creating a product.</p>
                <?php else: ?>
                    <form method="post" action="product-input" enctype="multipart/form-data" class="product-input-form">
                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">

                        <fieldset class="product-input-section product-input-section--details grid gap-5 sm:grid-cols-2">
                            <legend>Product details</legend>
                            <div class="sm:col-span-2">
                                <label for="product-name">Product name</label>
                                <input id="product-name" name="product_name" type="text" maxlength="255" required value="<?= store_escape($form['product_name']) ?>">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="description">Description <span>(optional)</span></label>
                                <textarea id="description" name="description" rows="4"><?= store_escape($form['description']) ?></textarea>
                            </div>
                            <div>
                                <label for="category-id">Category</label>
                                <p class="m-0 text-xs text-slate-500">Choose the product type, such as Acrylic, Album/CD, Figure, or Plush. Pre-order status is set separately.</p>
                                <select id="category-id" name="category_id" required>
                                    <option value="">Choose a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= (int) $category['category_id'] ?>" <?= $form['category_id'] === (string) $category['category_id'] ? 'selected' : '' ?>>
                                            <?= store_escape($category['category_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="status">Catalog status</label>
                                <select id="status" name="status">
                                    <option value="draft" <?= $form['status'] === 'draft' ? 'selected' : '' ?>>Draft (not visible in shop)</option>
                                    <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active (publish now)</option>
                                </select>
                            </div>
                            <div>
                                <label for="talent-id">Associated talent <span>(optional)</span></label>
                                <select id="talent-id" name="talent_id">
                                    <option value="">No talent association</option>
                                    <?php foreach ($talents as $talent): ?>
                                        <option value="<?= (int) $talent['talent_id'] ?>" <?= $form['talent_id'] === (string) $talent['talent_id'] ? 'selected' : '' ?>>
                                            <?= store_escape($talent['talent_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <label class="product-input-toggle" for="is-preorder">
                                <input id="is-preorder" name="is_preorder" type="checkbox" value="1" <?= $form['is_preorder'] === '1' ? 'checked' : '' ?>>
                                <span>This product is a pre-order</span>
                            </label>
                            <div class="sm:col-span-2">
                                <label for="expected-ship-date">Expected ship date <span>(optional)</span></label>
                                <input id="expected-ship-date" name="expected_ship_date" type="date" value="<?= store_escape($form['expected_ship_date']) ?>">
                            </div>
                        </fieldset>

                        <fieldset class="product-input-section grid gap-5 sm:grid-cols-2">
                            <legend>Variant and inventory</legend>
                            <div>
                                <label for="variant-name">Variant name</label>
                                <input id="variant-name" name="variant_name" type="text" maxlength="100" required value="<?= store_escape($form['variant_name']) ?>">
                            </div>
                            <div>
                                <label for="sku">SKU</label>
                                <input id="sku" name="sku" type="text" maxlength="50" required value="<?= store_escape($form['sku']) ?>">
                            </div>
                            <div>
                                <label for="price">Price (PHP)</label>
                                <input id="price" name="price" type="number" min="0.01" max="99999999.99" step="0.01" required value="<?= store_escape($form['price']) ?>">
                            </div>
                            <div>
                                <label for="compare-at-price">Original price <span>(optional)</span></label>
                                <input id="compare-at-price" name="compare_at_price" type="number" min="0.01" max="99999999.99" step="0.01" value="<?= store_escape($form['compare_at_price']) ?>">
                            </div>
                            <div>
                                <label for="stock-quantity">Stock quantity</label>
                                <input id="stock-quantity" name="stock_quantity" type="number" min="0" max="2147483647" step="1" required value="<?= store_escape($form['stock_quantity']) ?>">
                            </div>
                        </fieldset>

                        <fieldset class="product-input-section product-input-images">
                            <legend>Product images</legend>
                            <p class="product-input-section-description">Upload up to 10 images. The first is used as the main product image.</p>
                            <div class="product-input-file-row" data-image-drop-zone>
                                <label for="main-image">Main image file <span>(optional)</span></label>
                                <input id="main-image" name="main_image" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                                <label for="alt-text">Image description <span>(optional)</span></label>
                                <input id="alt-text" name="alt_text" type="text" maxlength="255" value="<?= store_escape($form['alt_text']) ?>">
                                <p class="product-input-drop-hint" data-drop-hint>Drop image files here, or choose files above.</p>
                                <p class="product-input-drop-status" data-drop-status role="status" aria-live="polite"></p>
                            </div>
                            <div id="additional-product-images" class="product-input-additional-images">
                                <?php foreach ($additionalImageAltTexts as $index => $imageAltText): ?>
                                    <div class="product-input-file-row" data-product-image-row data-image-drop-zone>
                                        <label for="additional-image-file-<?= $index ?>">Additional image file</label>
                                        <input id="additional-image-file-<?= $index ?>" name="additional_images[]" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                                        <label for="additional-image-alt-<?= $index ?>">Image description <span>(optional)</span></label>
                                        <input id="additional-image-alt-<?= $index ?>" name="additional_image_alt_texts[]" type="text" maxlength="255" value="<?= store_escape($imageAltText) ?>">
                                        <button type="button" data-remove-product-image>Remove image</button>
                                        <p class="product-input-drop-hint" data-drop-hint>Drop one image here, or choose a file above.</p>
                                        <p class="product-input-drop-status" data-drop-status role="status" aria-live="polite"></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-product-image">Add another image</button>
                            <template id="product-image-row-template">
                                <div class="product-input-file-row" data-product-image-row data-image-drop-zone>
                                    <label>Additional image file
                                        <input name="additional_images[]" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                                    </label>
                                    <label>Image description <span>(optional)</span>
                                        <input name="additional_image_alt_texts[]" type="text" maxlength="255">
                                    </label>
                                    <button type="button" data-remove-product-image>Remove image</button>
                                    <p class="product-input-drop-hint" data-drop-hint>Drop one image here, or choose a file above.</p>
                                    <p class="product-input-drop-status" data-drop-status role="status" aria-live="polite"></p>
                                </div>
                            </template>
                        </fieldset>

                        <div class="product-input-actions">
                            <button type="submit" class="product-input-submit">Save product</button>
                            <a href="admin-dashboard">Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    <script>
        (() => {
            const imageList = document.querySelector('#additional-product-images');
            const addImageButton = document.querySelector('#add-product-image');
            const imageRowTemplate = document.querySelector('#product-image-row-template');
            const imageFieldset = document.querySelector('.product-input-images');
            if (!imageList || !addImageButton || !imageRowTemplate || !imageFieldset) {
                return;
            }

            const updateAddImageButton = () => {
                addImageButton.disabled = imageList.querySelectorAll('[data-product-image-row]').length >= 9;
            };

            const setUploadFile = (input, file) => {
                const transfer = new DataTransfer();
                transfer.items.add(file);
                input.files = transfer.files;
            };

            const updateDropStatus = (zone, message, isError = false) => {
                const status = zone.querySelector('[data-drop-status]');
                if (status) {
                    status.textContent = message;
                    status.classList.toggle('is-error', isError);
                }
            };

            const appendImageRow = () => {
                if (imageList.querySelectorAll('[data-product-image-row]').length >= 9) {
                    return null;
                }
                imageList.append(imageRowTemplate.content.cloneNode(true));
                updateAddImageButton();
                return imageList.lastElementChild;
            };

            addImageButton.addEventListener('click', () => {
                if (imageList.querySelectorAll('[data-product-image-row]').length >= 9) {
                    return;
                }
                imageList.append(imageRowTemplate.content.cloneNode(true));
                updateAddImageButton();
            });
            imageList.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-product-image]');
                removeButton?.closest('[data-product-image-row]')?.remove();
                updateAddImageButton();
            });
            imageFieldset.addEventListener('change', (event) => {
                const input = event.target.closest('input[type="file"]');
                const zone = input?.closest('[data-image-drop-zone]');
                if (input?.files.length && zone) {
                    updateDropStatus(zone, input.files.length === 1 ? input.files[0].name : `${input.files.length} images selected`);
                }
            });
            imageFieldset.addEventListener('dragover', (event) => {
                const zone = event.target.closest('[data-image-drop-zone]');
                if (!zone || !event.dataTransfer?.types.includes('Files')) {
                    return;
                }
                event.preventDefault();
                zone.classList.add('is-dragover');
            });
            imageFieldset.addEventListener('dragleave', (event) => {
                const zone = event.target.closest('[data-image-drop-zone]');
                if (zone && !zone.contains(event.relatedTarget)) {
                    zone.classList.remove('is-dragover');
                }
            });
            imageFieldset.addEventListener('drop', (event) => {
                const zone = event.target.closest('[data-image-drop-zone]');
                if (!zone || !event.dataTransfer?.files.length) {
                    return;
                }
                event.preventDefault();
                zone.classList.remove('is-dragover');

                const droppedFiles = Array.from(event.dataTransfer.files);
                const validFiles = droppedFiles.filter((file) =>
                    ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)
                    && file.size > 0
                    && file.size <= 8 * 1024 * 1024
                );
                if (validFiles.length === 0) {
                    updateDropStatus(zone, 'Use JPG, PNG, GIF, or WebP files up to 8 MB.', true);
                    return;
                }

                const targetInput = zone.querySelector('input[type="file"]');
                setUploadFile(targetInput, validFiles[0]);
                updateDropStatus(zone, validFiles[0].name);

                let added = 1;
                let lastZone = zone;
                for (const file of validFiles.slice(1)) {
                    const nextZone = appendImageRow();
                    if (!nextZone) {
                        break;
                    }
                    setUploadFile(nextZone.querySelector('input[type="file"]'), file);
                    updateDropStatus(nextZone, file.name);
                    lastZone = nextZone;
                    added += 1;
                }

                const hasInvalidFiles = validFiles.length !== droppedFiles.length;
                const couldNotAddAll = added !== validFiles.length;
                if (hasInvalidFiles || couldNotAddAll) {
                    updateDropStatus(lastZone, `${added} image${added === 1 ? '' : 's'} added. Some files were skipped.`, true);
                }
            });
            updateAddImageButton();
        })();
    </script>
</body>

</html>
