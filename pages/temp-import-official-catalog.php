<?php
require_once __DIR__ . '/../app/store.php';

if (!store_has_role('admin')) {
    http_response_code(403);
    exit('Administrator access required.');
}

set_time_limit(0);
ignore_user_abort(true);
ini_set('memory_limit', '512M');

$stageDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'hololive-catalog-import-20261019';
$backupPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'holoverse-product-catalog-backup-' . date('Ymd-His') . '.json';
$message = '';
$messageIsError = false;
$importSummary = [];
$productRows = [];
$variantRows = [];
$imageRows = [];
$productTalentRows = [];
$usedSkus = [];
$skuAdjustmentCount = 0;
$longVariantNameCount = 0;

function catalog_import_batch(DatabaseConnection $conn, string $table, array $columns, string $rowTypes, array $rows, int $batchSize = 300): int
{
    if (!$rows) {
        return 0;
    }

    $inserted = 0;
    $rowPlaceholder = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
    foreach (array_chunk($rows, $batchSize) as $batch) {
        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES '
            . implode(', ', array_fill(0, count($batch), $rowPlaceholder));
        $statement = $conn->prepare($sql);
        $types = str_repeat($rowTypes, count($batch));
        $arguments = [$types];
        foreach ($batch as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $batch[$rowIndex][$columnIndex] = $value;
                $arguments[] = &$batch[$rowIndex][$columnIndex];
            }
        }
        call_user_func_array([$statement, 'bind_param'], $arguments);
        $statement->execute();
        $inserted += $statement->affected_rows;
        $statement->close();
    }

    return $inserted;
}

function catalog_import_load(string $directory): array
{
    $files = glob($directory . DIRECTORY_SEPARATOR . 'page-*.json') ?: [];
    sort($files, SORT_STRING);
    if (count($files) !== 11) {
        throw new RuntimeException('Expected all 11 staged shop pages; found ' . count($files) . '.');
    }

    $products = [];
    foreach ($files as $file) {
        $page = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($page)) {
            throw new RuntimeException('A staged shop page is not a JSON array.');
        }
        foreach ($page as $product) {
            if (
                !is_array($product)
                || empty($product['title'])
                || empty($product['variants'])
                || !is_array($product['variants'])
                || empty($product['images'])
                || !is_array($product['images'])
            ) {
                throw new RuntimeException('A shop product is missing its title, variants, or images.');
            }
            $products[] = $product;
        }
    }
    if (count($products) < 2500 || count($products) > 2600) {
        throw new RuntimeException('Unexpected staged product count: ' . count($products) . '.');
    }

    return $products;
}

function catalog_import_description(string $html): string
{
    $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $html) ?? $html;
    $text = preg_replace('/<\/(?:p|div|li|h[1-6])\s*>/i', "\n", $text) ?? $text;
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/[\p{Z}\s]+/u', ' ', $text) ?? $text;

    return trim($text);
}

function catalog_import_product_type(string $classificationText, bool $isDigital): string
{
    if ($isDigital) {
        return 'Digital Content';
    }

    $productTypes = [
        'Box Set' => '/memorial box|collector(?:\'s)? box|box set|complete set/iu',
        'Acrylic' => '/acrylic|アクリル/iu',
        'Figure' => '/figure|フィギュア|nendoroid|ねんどろいど/iu',
        'Plush' => '/plush|ぬいぐるみ/iu',
        'Album/CD' => '/\b(?:album|cd|ep|vinyl|record|single)\b|アルバム|シングル/iu',
        'Blu-ray/DVD' => '/blu[\s-]?ray|\bdvd\b|ブルーレイ/iu',
        'Apparel' => '/apparel|clothing|t-?shirt|hoodie|sweatshirt|jacket|sukajan|shirt|cap|hat|wear|パーカー|シャツ|衣装/iu',
        'Blanket' => '/blanket|ブランケット/iu',
        'Curtain' => '/curtain|カーテン/iu',
        'Mug' => '/\bmug\b|マグカップ/iu',
        'Keychain' => '/key ?chain|キー?ホルダー/iu',
        'Badge' => '/badge|button badge|缶バッジ/iu',
        'Coaster' => '/coaster|コースター/iu',
        'Book/Pamphlet' => '/book|pamphlet|photobook|artbook|冊子|写真集/iu',
        'Poster/Print' => '/poster|print|ポスター/iu',
    ];

    foreach ($productTypes as $productType => $pattern) {
        if (preg_match($pattern, $classificationText) === 1) {
            return $productType;
        }
    }

    return 'Other';
}

function catalog_import_variant_matches_talent(string $variantTitle, array $talents): bool
{
    $normalizedVariantTitle = preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($variantTitle, 'UTF-8'));
    $normalizedVariantTitle = is_string($normalizedVariantTitle) ? $normalizedVariantTitle : '';
    $talentAliases = [
        'kureijiollie' => ['kureiijiollie'],
        'rindochihaya' => ['rindouchihaya'],
    ];
    foreach ($talents as $talent) {
        $talentName = trim((string) $talent['talent_name']);
        $normalizedTalentName = preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($talentName, 'UTF-8'));
        $talentSlug = preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower((string) ($talent['talent_slug'] ?? ''), 'UTF-8'));
        if ($talentName !== '' && (
            mb_stripos($variantTitle, $talentName, 0, 'UTF-8') !== false
            || (is_string($normalizedTalentName)
                && $normalizedTalentName !== ''
                && (
                    mb_stripos($normalizedVariantTitle, $normalizedTalentName, 0, 'UTF-8') !== false
                    || array_filter(
                        $talentAliases[$normalizedTalentName] ?? [],
                        static fn (string $alias): bool => mb_stripos($normalizedVariantTitle, $alias, 0, 'UTF-8') !== false
                    ) !== []
                ))
            || (is_string($talentSlug)
                && $talentSlug !== ''
                && mb_stripos($normalizedVariantTitle, $talentSlug, 0, 'UTF-8') !== false)
        )) {
            return true;
        }
    }

    return false;
}

function catalog_import_variant_group_name(string $variantTitle): ?string
{
    $separatorPosition = mb_strrpos($variantTitle, '/', 0, 'UTF-8');
    if ($separatorPosition === false) {
        return null;
    }

    $groupName = trim(mb_substr($variantTitle, 0, $separatorPosition, 'UTF-8'));
    if (preg_match(
        '/^(?:holostars(?:\s+english|\s+generation\s+\d+)?|hololive(?:\s+(?:dev_is|indonesia|gamers|stage[1-4]|english(?:\s+(?:-[^-]+-|project:\s*hope))?))?)$/i',
        $groupName
    ) !== 1) {
        return null;
    }

    return $groupName;
}

function catalog_import_variant_option_name(string $variantTitle): string
{
    $separatorPosition = mb_strrpos($variantTitle, '/', 0, 'UTF-8');
    if ($separatorPosition === false) {
        return '';
    }

    $optionName = trim(mb_substr($variantTitle, $separatorPosition + 1, null, 'UTF-8'));
    $optionName = preg_replace('/\s*\((?:Japanese|English|Indonesian|Chinese|Korean|French|Spanish)\)\s*$/iu', '', $optionName) ?? $optionName;
    $optionName = preg_replace('/^(?:Personalized Set)\s+/iu', '', $optionName) ?? $optionName;
    $optionName = preg_replace('/\s+SP Voice(?:\s+Pack)?\s*$/iu', '', $optionName) ?? $optionName;

    return trim($optionName);
}

function catalog_import_normalize_talent_variant(string $variantTitle, array $talents): string
{
    if (catalog_import_variant_group_name($variantTitle) === null) {
        return $variantTitle;
    }

    $qualifier = '';
    if (preg_match('/\s+(SP Voice(?: Pack)?)\s*(\((?:Japanese|English|Indonesian|Chinese|Korean|French|Spanish)\))?\s*$/iu', $variantTitle, $qualifierMatch) === 1) {
        $qualifier = ' ' . $qualifierMatch[1] . ($qualifierMatch[2] ?? '');
    } elseif (preg_match('/\s*(\((?:Japanese|English|Indonesian|Chinese|Korean|French|Spanish)\))\s*$/iu', $variantTitle, $qualifierMatch) === 1) {
        $qualifier = ' ' . $qualifierMatch[1];
    }
    $optionName = catalog_import_variant_option_name($variantTitle);
    $normalizedOptionName = preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($optionName, 'UTF-8'));
    foreach ($talents as $talent) {
        $talentName = trim((string) $talent['talent_name']);
        $normalizedTalentName = preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower($talentName, 'UTF-8'));
        $talentSlug = preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower((string) ($talent['talent_slug'] ?? ''), 'UTF-8'));
        $aliases = [
            'kureijiollie' => ['kureiijiollie'],
            'rindochihaya' => ['rindouchihaya'],
        ];
        if ($normalizedOptionName === $normalizedTalentName
            || (is_string($talentSlug) && $talentSlug !== '' && $normalizedOptionName === $talentSlug)
            || in_array($normalizedOptionName, $aliases[$normalizedTalentName] ?? [], true)) {
            $generationName = $talent['generation_name'] ?? '';
            if (is_string($generationName) && $generationName !== '') {
                return $generationName . ' / ' . $talentName . $qualifier;
            }
        }
    }

    return $variantTitle;
}

function catalog_import_variant_is_unlisted_talent_option(string $variantTitle, array $talents): bool
{
    if (catalog_import_variant_group_name($variantTitle) === null) {
        return false;
    }

    $optionName = catalog_import_variant_option_name($variantTitle);
    if (catalog_import_variant_matches_talent($optionName, $talents)) {
        return false;
    }

    return preg_match('/^\p{Lu}[\p{L}\p{M}\'’.-]*(?:\s+\p{Lu}[\p{L}\p{M}\'’.-]*){0,3}$/u', $optionName) === 1;
}

function catalog_import_build_rows(array $products, array $talents, string $localImageDirectory): array
{
    $productRows = [];
    $variantRows = [];
    $imageRows = [];
    $productTalentRows = [];
    $usedSkus = [];
    $skuAdjustmentCount = 0;
    $longVariantNameCount = 0;
    $availableVariantCount = 0;
    $soldOutVariantCount = 0;
    $freeVariantCount = 0;
    $matchedTalentCount = 0;
    $targetSkuCount = 0;
    $filteredTalentVariantCount = 0;
    $filteredTalentProductCount = 0;
    $nextProductId = 1;
    $nextVariantId = 1;
    $nextImageId = 1;

    $localXRechordImages = [
        ['regloss-x-rechord-live-promo.png', 'ReGLOSS AR LIVE X-ReCHORD concert artwork'],
        ['d93f08eb4bb0359e3f39f5bb2fccb7f2.png', 'ReGLOSS AR LIVE X-ReCHORD sticker set'],
        ['regloss-x-rechord-acrylic-stand.png', 'ReGLOSS AR LIVE X-ReCHORD acrylic stands'],
        ['regloss-x-rechord-button-badges.png', 'ReGLOSS AR LIVE X-ReCHORD holographic button badges'],
        ['regloss-x-rechord-rubber-coasters.png', 'ReGLOSS AR LIVE X-ReCHORD rubber coasters'],
        ['regloss-x-rechord-acrylic-panel.png', 'ReGLOSS AR LIVE X-ReCHORD acrylic panel'],
        ['regloss-x-rechord-tshirt.png', 'ReGLOSS AR LIVE X-ReCHORD concert T-shirt'],
        ['regloss-x-rechord-mug.png', 'ReGLOSS AR LIVE X-ReCHORD mug'],
        ['regloss-x-rechord-keychain.png', 'ReGLOSS AR LIVE X-ReCHORD keychain'],
        ['regloss-x-rechord-pamphlet.png', 'ReGLOSS AR LIVE X-ReCHORD pamphlet'],
    ];

    foreach ($products as $product) {
        $sourceVariants = $product['variants'];
        $sourceVariants = array_values(array_filter(
            $sourceVariants,
            static fn (array $variant): bool => !catalog_import_variant_is_unlisted_talent_option(
                (string) ($variant['title'] ?? ''),
                $talents
            )
        ));
        $filteredTalentVariantCount += count($product['variants']) - count($sourceVariants);
        if (!$sourceVariants) {
            $filteredTalentProductCount++;
            continue;
        }

        $productId = $nextProductId++;
        $productTitle = trim((string) $product['title']);
        $productTags = implode(' ', array_map('strval', $product['tags'] ?? []));
        $variantTitles = array_map(
            static fn (array $variant): string => (string) ($variant['title'] ?? ''),
            $sourceVariants
        );
        $classificationText = $productTitle . ' ' . $productTags . ' ' . implode(' ', $variantTitles);
        $shippingVariants = array_filter(
            $sourceVariants,
            static fn (array $variant): bool => !empty($variant['requires_shipping'])
        );
        $isDigital = count($shippingVariants) === 0
            || preg_match('/digital|デジタル|電子/u', $classificationText) === 1;
        $isPreorder = preg_match('/made[\s-]*to[\s-]*order|pre[\s-]*order|advanced shipping|secondary order|受注/u', $classificationText) === 1;
        $categoryName = catalog_import_product_type($classificationText, $isDigital);
        $description = catalog_import_description((string) ($product['body_html'] ?? ''));
        $productRows[] = [
            $productId,
            $categoryName,
            mb_substr($productTitle, 0, 255, 'UTF-8'),
            mb_substr($description, 0, 20000, 'UTF-8'),
            'active',
            (int) $isPreorder,
            null,
        ];

        $variantNames = [];
        foreach ($sourceVariants as $variant) {
            $variantId = $nextVariantId++;
            $variantTitle = trim((string) ($variant['title'] ?? ''));
            if ($variantTitle === '' || $variantTitle === 'Default Title') {
                $variantTitle = 'Default';
            }
            $variantTitle = catalog_import_normalize_talent_variant($variantTitle, $talents);
            if (mb_strlen($variantTitle, 'UTF-8') > 100) {
                $suffix = ' [' . (string) $variantId . ']';
                $variantTitle = mb_substr($variantTitle, 0, 100 - mb_strlen($suffix, 'UTF-8'), 'UTF-8') . $suffix;
                $longVariantNameCount++;
            }
            $nameKey = mb_strtolower($variantTitle, 'UTF-8');
            if (isset($variantNames[$nameKey])) {
                $suffix = ' [' . (string) $variantId . ']';
                $variantTitle = mb_substr($variantTitle, 0, 100 - mb_strlen($suffix, 'UTF-8'), 'UTF-8') . $suffix;
                $nameKey = mb_strtolower($variantTitle, 'UTF-8');
                $longVariantNameCount++;
            }
            $variantNames[$nameKey] = true;

            $sourceSku = trim((string) ($variant['sku'] ?? ''));
            $sku = $sourceSku !== '' ? $sourceSku : 'SHOP-' . $variantId;
            $skuKey = mb_strtolower($sku, 'UTF-8');
            if ($sourceSku === '' || isset($usedSkus[$skuKey])) {
                $sku = 'SHOP-' . $variantId;
                $skuKey = mb_strtolower($sku, 'UTF-8');
                $suffix = 1;
                while (isset($usedSkus[$skuKey])) {
                    $sku = 'SHOP-' . $variantId . '-' . $suffix++;
                    $skuKey = mb_strtolower($sku, 'UTF-8');
                }
                $skuAdjustmentCount++;
            }
            $usedSkus[$skuKey] = true;

            $price = number_format((float) $variant['price'], 2, '.', '');
            $compareAtPrice = $variant['compare_at_price'] ?? null;
            $compareAtPrice = $compareAtPrice !== null && (float) $compareAtPrice > (float) $price
                ? number_format((float) $compareAtPrice, 2, '.', '')
                : null;
            $available = !empty($variant['available']);
            $stock = $available ? 1 : 0;
            if ($sku === '26000225021') {
                $stock = 245;
                $targetSkuCount++;
            }
            if ($available) {
                $availableVariantCount++;
            } else {
                $soldOutVariantCount++;
            }
            if ((float) $price === 0.0) {
                $freeVariantCount++;
            }
            $variantRows[] = [$variantId, $productId, $variantTitle, $sku, $price, $compareAtPrice, 'PHP', $stock];
        }

        $sourceImages = $product['images'];
        if ($productTitle === 'ReGLOSS AR LIVE "X-ReCHORD"') {
            $sourceImages = [];
            foreach ($localXRechordImages as [$filename, $altText]) {
                $sourceImages[] = [
                    'src' => 'Assets/images/products/' . $filename,
                    'alt' => $altText,
                ];
            }
        }
        foreach ($sourceImages as $imageIndex => $image) {
            $imageUrl = is_array($image) ? (string) ($image['src'] ?? '') : (string) $image;
            if ($imageUrl === '') {
                continue;
            }
            if (str_starts_with($imageUrl, '//')) {
                $imageUrl = 'https:' . $imageUrl;
            }
            $altText = trim((string) (is_array($image) ? ($image['alt'] ?? '') : ''));
            if ($altText === '') {
                $altText = $productTitle . ' image ' . ($imageIndex + 1);
            }
            $imageRows[] = [$nextImageId++, $productId, $imageUrl, mb_substr($altText, 0, 255, 'UTF-8'), $imageIndex];
        }

        $searchText = $productTitle . ' ' . implode(' ', $variantTitles);
        foreach ($talents as $talent) {
            $talentName = trim((string) $talent['talent_name']);
            if (mb_strlen($talentName, 'UTF-8') < 4 || mb_stripos($searchText, $talentName, 0, 'UTF-8') === false) {
                continue;
            }
            $productTalentRows[] = [$productId, (int) $talent['talent_id']];
            $matchedTalentCount++;
        }
    }

    return [
        'products' => $productRows,
        'variants' => $variantRows,
        'images' => $imageRows,
        'talents' => $productTalentRows,
        'sku_adjustments' => $skuAdjustmentCount,
        'long_variant_names' => $longVariantNameCount,
        'available_variants' => $availableVariantCount,
        'sold_out_variants' => $soldOutVariantCount,
        'free_variants' => $freeVariantCount,
        'matched_talents' => $matchedTalentCount,
        'target_sku_count' => $targetSkuCount,
        'filtered_talent_variants' => $filteredTalentVariantCount,
        'filtered_talent_products' => $filteredTalentProductCount,
    ];
}

$products = catalog_import_load($stageDirectory);
$eligibleGroupSlugs = store_talent_filter_group_slugs_sql();
$talentRows = $conn->query(
    "SELECT t.talent_id, t.talent_name, t.talent_slug, tg.group_name
     FROM talent_tbl t
     INNER JOIN talent_group_members_tbl gm ON gm.talent_id = t.talent_id
     INNER JOIN talent_group_tbl tg ON tg.group_id = gm.group_id
     WHERE tg.group_slug IN ({$eligibleGroupSlugs})
     ORDER BY t.talent_id, array_position(ARRAY[" . implode(',', array_map(
        static fn (string $slug): string => "'" . str_replace("'", "''", $slug) . "'",
        STORE_TALENT_FILTER_GROUP_SLUGS
    )) . "]::text[], tg.group_slug)"
)->fetch_all(PDO::FETCH_ASSOC);
$talentsById = [];
foreach ($talentRows as $talentRow) {
    $talentId = (int) $talentRow['talent_id'];
    if (!isset($talentsById[$talentId])) {
        $talentsById[$talentId] = [
            'talent_id' => $talentId,
            'talent_name' => $talentRow['talent_name'],
            'talent_slug' => $talentRow['talent_slug'],
            'generation_name' => $talentRow['group_name'],
        ];
    }
}
$talents = array_values($talentsById);
$catalog = catalog_import_build_rows($products, $talents, __DIR__ . '/../Assets/images/products');
$existingCounts = [
    'products' => (int) $conn->query("SELECT COUNT(*) AS row_count FROM product_tbl")->fetch_assoc()['row_count'],
    'variants' => (int) $conn->query('SELECT COUNT(*) AS row_count FROM product_variants_tbl')->fetch_assoc()['row_count'],
    'images' => (int) $conn->query('SELECT COUNT(*) AS row_count FROM product_image_tbl')->fetch_assoc()['row_count'],
    'cart_items' => (int) $conn->query('SELECT COUNT(*) AS row_count FROM cart_items_tbl')->fetch_assoc()['row_count'],
    'order_items' => (int) $conn->query('SELECT COUNT(*) AS row_count FROM order_items_tbl')->fetch_assoc()['row_count'],
    'flash_sales' => (int) $conn->query('SELECT COUNT(*) AS row_count FROM flash_sale_tbl')->fetch_assoc()['row_count'],
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!store_verify_csrf()) {
        http_response_code(403);
        $message = 'Your session expired. Reload this preview and try again.';
        $messageIsError = true;
    } else {
        $backup = [
            'created_at' => date(DateTimeInterface::ATOM),
            'products' => $conn->query('SELECT * FROM product_tbl ORDER BY product_id')->fetch_all(PDO::FETCH_ASSOC),
            'variants' => $conn->query('SELECT * FROM product_variants_tbl ORDER BY variant_id')->fetch_all(PDO::FETCH_ASSOC),
            'images' => $conn->query('SELECT * FROM product_image_tbl ORDER BY image_id')->fetch_all(PDO::FETCH_ASSOC),
            'product_talents' => $conn->query('SELECT * FROM product_talents_tbl ORDER BY product_id, talent_id')->fetch_all(PDO::FETCH_ASSOC),
            'cart_items' => $conn->query('SELECT * FROM cart_items_tbl ORDER BY cart_item_id')->fetch_all(PDO::FETCH_ASSOC),
            'flash_sales' => $conn->query('SELECT * FROM flash_sale_tbl ORDER BY flash_sale_id')->fetch_all(PDO::FETCH_ASSOC),
        ];
        $backupJson = json_encode($backup, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($backupPath, $backupJson, LOCK_EX) === false) {
            throw new RuntimeException('Could not back up the existing catalog to the temporary directory. No database rows were changed.');
        }

        $conn->begin_transaction();
        try {
            $conn->query('LOCK TABLE cart_items_tbl, flash_sale_tbl, order_items_tbl, product_talents_tbl, product_image_tbl, product_variants_tbl, product_tbl, category_tbl IN ACCESS EXCLUSIVE MODE');
            $conn->query('ALTER TABLE public.product_image_tbl ALTER COLUMN image_url TYPE TEXT');
            $conn->query('DELETE FROM cart_items_tbl');
            $conn->query('DELETE FROM product_tbl');

            $categoryIds = [];
            foreach ($conn->query('SELECT category_id, category_name FROM category_tbl')->fetch_all(PDO::FETCH_ASSOC) as $category) {
                $categoryIds[$category['category_name']] = (int) $category['category_id'];
            }

            foreach (array_unique(array_column($catalog['products'], 1)) as $categoryName) {
                if (!isset($categoryIds[$categoryName])) {
                    $categoryStatement = $conn->prepare('INSERT INTO category_tbl (category_name) VALUES (?) RETURNING category_id');
                    $categoryStatement->bind_param('s', $categoryName);
                    $categoryStatement->execute();
                    $categoryIds[$categoryName] = (int) $categoryStatement->get_result()->fetch_assoc()['category_id'];
                    $categoryStatement->close();
                }
            }

            foreach ($catalog['products'] as &$productRow) {
                $productRow[1] = $categoryIds[$productRow[1]];
            }
            unset($productRow);

            $importedProducts = catalog_import_batch($conn, 'product_tbl', ['product_id', 'category_id', 'product_name', 'description', 'status', 'is_preorder', 'expected_ship_date'], 'iisssis', $catalog['products']);
            $importedVariants = catalog_import_batch($conn, 'product_variants_tbl', ['variant_id', 'product_id', 'variant_name', 'sku', 'price', 'compare_at_price', 'currency', 'stock_quantity'], 'iisssssi', $catalog['variants']);
            $importedImages = catalog_import_batch($conn, 'product_image_tbl', ['image_id', 'product_id', 'image_url', 'alt_text', 'sort_order'], 'iissi', $catalog['images']);
            $importedTalentLinks = catalog_import_batch($conn, 'product_talents_tbl', ['product_id', 'talent_id'], 'ii', $catalog['talents']);

            $lastProductId = count($catalog['products']);
            $lastVariantId = count($catalog['variants']);
            $lastImageId = count($catalog['images']);
            $conn->query("SELECT setval(pg_get_serial_sequence('public.product_tbl', 'product_id'), {$lastProductId}, true)");
            $conn->query("SELECT setval(pg_get_serial_sequence('public.product_variants_tbl', 'variant_id'), {$lastVariantId}, true)");
            $conn->query("SELECT setval(pg_get_serial_sequence('public.product_image_tbl', 'image_id'), {$lastImageId}, true)");

            $conn->commit();
            $message = "Catalog replaced successfully: {$importedProducts} products, {$importedVariants} variants, {$importedImages} images, and {$importedTalentLinks} talent links. Old cart items were cleared; order snapshots were preserved. Backup: {$backupPath}";
        } catch (Throwable $error) {
            $conn->rollback();
            http_response_code(500);
            $message = 'Catalog replacement rolled back: ' . $error->getMessage() . ' Backup: ' . $backupPath;
            $messageIsError = true;
        }
    }
}

$pageTitle = 'Official catalog replacement preview';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>
<body>
    <div class="page-shell">
        <?php
        $activePage = 'products';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>
        <main class="admin-dashboard max-w-[900px]">
            <header class="admin-dashboard-header">
                <div>
                    <p class="admin-kicker">Catalog import preview</p>
                    <h1>Replace product catalog</h1>
                </div>
            </header>
            <?php if ($message !== ''): ?>
                <p class="admin-notice<?= $messageIsError ? ' admin-notice--error' : '' ?>" role="<?= $messageIsError ? 'alert' : 'status' ?>"><?= store_escape($message) ?></p>
                <?php if (!$messageIsError): ?>
                    <p><a href="products">Open the imported catalog</a></p>
                <?php endif; ?>
            <?php else: ?>
                <p>Source: official hololive production English storefront. Talent variants are limited to the talent groups available on HoloVerse; sold-out and digital products remain included.</p>
                <p>Current catalog: <?= $existingCounts['products'] ?> products, <?= $existingCounts['variants'] ?> variants, <?= $existingCounts['images'] ?> images.</p>
                <p>New catalog: <?= count($catalog['products']) ?> products, <?= count($catalog['variants']) ?> variants, <?= count($catalog['images']) ?> images. <?= $catalog['available_variants'] ?> available variants receive stock 1 because exact inventory is not public; <?= $catalog['sold_out_variants'] ?> sold-out variants receive 0. Sticker Set SKU 26000225021 keeps stock 245. <?= $catalog['free_variants'] ?> zero-price digital variants are included.</p>
                <p><?= $catalog['filtered_talent_variants'] ?> talent variants outside the website's talent list and <?= $catalog['filtered_talent_products'] ?> products with no remaining supported talent variants will be excluded.</p>
                <p><?= $catalog['sku_adjustments'] ?> blank or duplicate SKUs will receive unique HoloVerse SKUs; <?= $catalog['long_variant_names'] ?> long or duplicate variant labels will be shortened to fit local limits. <?= $catalog['matched_talents'] ?> title-matched talent links will be created.</p>
                <p>Import will clear <?= $existingCounts['cart_items'] ?> cart items, replace product-linked rows and flash-sale schedules, and leave <?= $existingCounts['order_items'] ?> order item snapshots intact. A backup of products, variants, images, talent links, cart items, and flash-sale schedules will be saved to the temporary directory.</p>
                <p>Product images use local X-ReCHORD assets for that concert item and official Shopify CDN images for the rest of the catalog.</p>
                <form method="post" class="mt-6">
                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                    <button type="submit" class="product-input-submit">Replace catalog with official shop data</button>
                </form>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
