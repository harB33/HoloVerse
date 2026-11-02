<?php
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
$requestPath = '/' . ltrim($requestPath, '/');

if (preg_match('~(?:^|/)\.[^/]*~', $requestPath) === 1 && $requestPath !== '/index.php') {
    http_response_code(404);
    exit('Not Found');
}

if ($requestPath === '/index.php') {
    header('Location: /home', true, 308);
    exit;
}

if ($requestPath === '/home/') {
    header('Location: /home', true, 308);
    exit;
}

if ($requestPath === '/flash_sale' || $requestPath === '/flash_sale/') {
    $query = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    $location = '/flash-sale' . (is_string($query) && $query !== '' ? '?' . $query : '');
    header('Location: ' . $location, true, 308);
    exit;
}

if ($requestPath === '/flash-sale' || $requestPath === '/flash-sale/') {
    $_SERVER['SCRIPT_NAME'] = '/pages/flash_sale.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/flash_sale.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if ($requestPath === '/collector_showcase' || $requestPath === '/collector_showcase/') {
    $query = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
    $location = '/collector-showcase' . (is_string($query) && $query !== '' ? '?' . $query : '');
    header('Location: ' . $location, true, 308);
    exit;
}

if ($requestPath === '/collector-showcase' || $requestPath === '/collector-showcase/') {
    $_SERVER['SCRIPT_NAME'] = '/pages/collector_showcase.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/collector_showcase.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/product/([^/]+)$~', $requestPath, $productRoute) === 1) {
    $_GET['slug'] = $productRoute[1];
    $_SERVER['SCRIPT_NAME'] = '/pages/product.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/product.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/category/([^/]+)/?$~', $requestPath, $categoryRoute) === 1) {
    $_GET['category_slug'] = $categoryRoute[1];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/generation/([A-Za-z0-9_-]+)/category/([^/]+)/?$~', $requestPath, $generationCategoryRoute) === 1) {
    $_GET['generation'] = $generationCategoryRoute[1];
    $_GET['category_slug'] = $generationCategoryRoute[2];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/generation/([A-Za-z0-9_-]+)/?$~', $requestPath, $generationRoute) === 1) {
    $_GET['generation'] = $generationRoute[1];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/([A-Za-z0-9_-]+)/category/([^/]+)/?$~', $requestPath, $generationCategoryRoute) === 1) {
    $_GET['generation'] = $generationCategoryRoute[1];
    $_GET['category_slug'] = $generationCategoryRoute[2];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/([A-Za-z0-9_-]+)/([A-Za-z0-9_-]+)/?$~', $requestPath, $generationCategoryRoute) === 1) {
    $_GET['generation'] = $generationCategoryRoute[1];
    $_GET['category_slug'] = $generationCategoryRoute[2];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/products/([^/]+)/?$~', $requestPath, $generationRoute) === 1) {
    $_GET['route_segment'] = $generationRoute[1];
    $_SERVER['SCRIPT_NAME'] = '/pages/products.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/pages/products.php';
    require $_SERVER['SCRIPT_FILENAME'];
    exit;
}

if (preg_match('~^/(?:pages/|Functions/)?([A-Za-z0-9_-]+)\.php$~i', $requestPath, $legacyPage) === 1
    && is_file(__DIR__ . '/pages/' . $legacyPage[1] . '.php')) {
    header('Location: /' . $legacyPage[1], true, 308);
    exit;
}

if (preg_match('~^/([A-Za-z0-9_-]+)/$~', $requestPath, $trailingSlashPage) === 1
    && is_file(__DIR__ . '/pages/' . $trailingSlashPage[1] . '.php')) {
    header('Location: /' . $trailingSlashPage[1], true, 308);
    exit;
}

if (preg_match('~^/([A-Za-z0-9_-]+)$~', $requestPath, $cleanPage) === 1
    && is_file(__DIR__ . '/pages/' . $cleanPage[1] . '.php')) {
    $pageScript = __DIR__ . '/pages/' . $cleanPage[1] . '.php';
    $_SERVER['SCRIPT_NAME'] = '/pages/' . $cleanPage[1] . '.php';
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
    $_SERVER['SCRIPT_FILENAME'] = $pageScript;
    require $pageScript;
    exit;
}

if ($requestPath !== '/' && $requestPath !== '/home') {
    $requestedFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($requestPath, '/')));
    $projectRoot = realpath(__DIR__);
    if ($requestedFile !== false
        && $projectRoot !== false
        && str_starts_with($requestedFile, $projectRoot . DIRECTORY_SEPARATOR)
        && is_file($requestedFile)) {
        return false;
    }

    http_response_code(404);
    exit('Not Found');
}

require_once __DIR__ . '/app/store.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$favoriteProductIds = store_collector_favorite_ids($conn, $userId);
$csrfToken = store_csrf_token();
$favoriteShowcaseUrl = store_collector_showcase_url();

$flashSaleTimezone = new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila');
$flashSaleNow = new DateTimeImmutable('now', $flashSaleTimezone);
$flashSaleAvailable = store_flash_sale_table_exists($conn);
$liveFlashSales = $flashSaleAvailable ? store_flash_sale_rows($conn, 'live', $flashSaleNow) : [];

$liveFlashProductIds = [];
foreach ($liveFlashSales as $saleRow) {
    $liveFlashProductIds[(int) $saleRow['product_id']] = $saleRow;
}

// Determine spotlight flash drop product (live flash sale or fallback discounted/featured item from DB)
$flashDropProduct = $liveFlashSales[0] ?? null;

if ($flashDropProduct === null) {
    $discountedRows = store_product_rows(
        $conn,
        'AND v.compare_at_price IS NOT NULL AND v.compare_at_price > v.price AND v.stock_quantity > 0',
        'v.compare_at_price - v.price DESC, p.product_id DESC',
        1
    );
    if ($discountedRows !== []) {
        $flashDropProduct = $discountedRows[0];
    } else {
        $fallbackRows = store_catalog_product_rows($conn, limit: 1);
        $flashDropProduct = $fallbackRows[0] ?? null;
    }
    if ($flashDropProduct !== null) {
        $liveFlashProductIds[(int) $flashDropProduct['product_id']] = $flashDropProduct;
    }
}

if ($flashDropProduct !== null && empty($flashDropProduct['talent_name'])) {
    $talentStmt = $conn->prepare(
        'SELECT t.talent_name
         FROM product_talents_tbl pt
         INNER JOIN talent_tbl t ON t.talent_id = pt.talent_id
         WHERE pt.product_id = ?
         ORDER BY t.talent_id ASC
         LIMIT 1'
    );
    $flashProdId = (int) $flashDropProduct['product_id'];
    $talentStmt->bind_param('i', $flashProdId);
    $talentStmt->execute();
    $talentRow = $talentStmt->get_result()->fetch_assoc();
    $talentStmt->close();
    $flashDropProduct['talent_name'] = $talentRow['talent_name'] ?? $flashDropProduct['category_name'] ?? 'hololive production';
}

// Fetch categories with active products
$categoryRows = $conn->query(
    "SELECT c.category_id, c.category_name, COUNT(DISTINCT p.product_id) AS product_count
     FROM category_tbl c
     INNER JOIN product_tbl p ON p.category_id = c.category_id AND p.status = 'active'
     GROUP BY c.category_id, c.category_name
     ORDER BY product_count DESC, c.category_name ASC"
)->fetch_all(PDO::FETCH_ASSOC);

// Build featured category tabs (All merch + top categories)
$preferredCategoryOrder = ['Plush', 'Apparel', 'Acrylic', 'Figure', 'Album / CD', 'Keychain', 'Badge', 'Poster / Print', 'Mug'];
usort($categoryRows, static function (array $a, array $b) use ($preferredCategoryOrder): int {
    $posA = array_search($a['category_name'], $preferredCategoryOrder, true);
    $posB = array_search($b['category_name'], $preferredCategoryOrder, true);
    $posA = $posA === false ? 999 : $posA;
    $posB = $posB === false ? 999 : $posB;
    if ($posA !== $posB) {
        return $posA <=> $posB;
    }
    return strcmp((string) $a['category_name'], (string) $b['category_name']);
});

$tabCategories = array_slice(array_column($categoryRows, 'category_name'), 0, 5);
$talentJapaneseNames = [
    'Hoshimachi Suisei' => '星街すいせい',
    'Inugami Korone' => '戌神ころね',
    'Gawr Gura' => 'がうる・ぐら',
    'Mori Calliope' => '森カリオペ',
    'Usada Pekora' => '兎田ぺこら',
    'Tokino Sora' => 'ときのそら',
    'Roboco-san' => 'ロボ子さん',
    'AZKi' => 'あずき',
    'Sakura Miko' => 'さくらみこ',
    'Aki Rosenthal' => 'アキ・ローゼンタール',
    'Akai Haato' => '赤井はあと',
    'Shirakami Fubuki' => '白上フブキ',
    'Natsuiro Matsuri' => '夏色まつり',
    'Ookami Mio' => '大神ミオ',
    'Nekomata Okayu' => '猫又おかゆ',
    'Nakiri Ayame' => '百鬼あやめ',
    'Yuzuki Choco' => '癒月ちょこ',
    'Oozora Subaru' => '大空スバル',
    'Shiranui Flare' => '不知火フレア',
    'Shirogane Noel' => '白銀ノエル',
    'Houshou Marine' => '宝鐘マリン',
    'Tsunomaki Watame' => '角巻わため',
    'Tokoyami Towa' => '常闇トワ',
    'Himemori Luna' => '姫森ルーナ',
    'Yukihana Lamy' => '雪花ラミィ',
    'Momosuzu Nene' => '桃鈴ねね',
    'Shishiro Botan' => '獅白ぼたん',
    'Omaru Polka' => '尾丸ポルカ',
    'La+ Darknesss' => 'ラプラス・ダークネス',
    'Takane Lui' => '鷹嶺ルイ',
    'Hakui Koyori' => '博衣こより',
    'Kazama Iroha' => '風真いろは',
    'Otonose Kanade' => '音乃瀬奏',
    'Ichijou Ririka' => '一条莉々華',
    'Juufuutei Raden' => '儒烏風亭らでん',
    'Todoroki Hajime' => '轟はじめ',
    'Isaki Riona' => '響咲リオナ',
    'Koganei Niko' => '虎金妃笑虎',
    'Mizumiya Su' => '水宮枢',
    'Rindo Chihaya' => '輪堂千速',
    'Kikirara Vivi' => '綺々羅々ヴィヴィ',
    'Takanashi Kiara' => '小鳥遊キアラ',
    'Ninomae Ina’nis' => '一伊那尓栖',
    'Watson Amelia' => 'ワトソン・アメリア',
    'IRyS' => 'アイリス',
    'Ouro Kronii' => 'オーロ・クロニー',
    'Hakos Baelz' => 'ハコス・ベールズ',
    'Shiori Novella' => 'シオリ・ノヴェラ',
    'Koseki Bijou' => '古石ビジュー',
    'Nerissa Ravencroft' => 'ネリッサ・レイヴンクロフト',
    'Fuwawa Abyssgard' => 'フワワ・アビスガード',
    'Mococo Abyssgard' => 'モココ・アビスガード',
    'Elizabeth Rose Bloodflame' => 'エリザベス・ローズ・ブラッドフレイム',
    'Gigi Murin' => 'ジジ・ムリン',
    'Cecilia Immergreen' => 'セシリア・イマーグリーン',
    'Raora Panthera' => 'ラオーラ・パンテーラ',
    'Ayunda Risu' => 'アユンダ・リス',
    'Moona Hoshinova' => 'ムーナ・ホシノヴァ',
    'Airani Iofifteen' => 'アイラニ・イオフィフティーン',
    'Kureiji Ollie' => 'クレイジー・オリー',
    'Anya Melfissa' => 'アーニャ・メルフィッサ',
    'Pavolia Reine' => 'パヴォリア・レイネ',
    'Vestia Zeta' => 'ベスティア・ゼータ',
    'Kaela Kovalskia' => 'カエラ・コヴァルスキア',
    'Kobo Kanaeru' => 'こぼ・かなえる',
    'Hyakuto Kyoko' => 'ヒャクト・キョウコ',
    'Achichi Mela' => 'アチチ・メラ',
    'Suzuna Tsuzuri' => 'スズナ・ツズリ',
    'Sorashina Sopia' => 'ソラシナ・ソピア',
];
$talentTickerItems = array_map(
    static function (array $talent) use ($talentJapaneseNames): array {
        $name = (string) $talent['talent_name'];
        return [
            'name' => $name,
            'slug' => (string) $talent['talent_slug'],
            'japanese' => $talentJapaneseNames[$name] ?? $name,
        ];
    },
    $conn->query('SELECT talent_name, talent_slug FROM talent_tbl ORDER BY talent_name ASC')->fetch_all(PDO::FETCH_ASSOC)
);
$talentGroups = $conn->query(
    'SELECT group_name, group_slug FROM talent_group_tbl ORDER BY group_id ASC'
)->fetch_all(PDO::FETCH_ASSOC);
$talentGroupNames = array_map(
    static fn (array $group): array => [
        'name' => (string) $group['group_name'],
        'slug' => (string) $group['group_slug'],
    ],
    $talentGroups
);

// Build diverse shop product list: regular products, pre-orders, flash drop, and user favorites
$shopProductsById = [];

if ($flashDropProduct !== null) {
    $shopProductsById[(int) $flashDropProduct['product_id']] = $flashDropProduct;
}

// Include products from each top category so every category tab has items
foreach (array_slice($categoryRows, 0, 5) as $catRow) {
    $catProducts = store_catalog_product_rows($conn, limit: 4, categoryId: (int) $catRow['category_id']);
    foreach ($catProducts as $prod) {
        $pid = (int) $prod['product_id'];
        if (!isset($shopProductsById[$pid])) {
            $shopProductsById[$pid] = $prod;
        }
    }
}

// Include pre-order products so Pre-order availability filter and #preorders section are populated
$preorderRows = store_product_rows(
    $conn,
    'AND p.is_preorder = 1',
    '(v.stock_quantity > 0) DESC, p.product_id DESC',
    6
);
foreach ($preorderRows as $prod) {
    $pid = (int) $prod['product_id'];
    if (!isset($shopProductsById[$pid])) {
        $shopProductsById[$pid] = $prod;
    }
}

// Include any favorited products of the signed-in user so the Saved filter works seamlessly
if ($favoriteProductIds !== []) {
    $favIdList = implode(', ', array_map('intval', array_slice($favoriteProductIds, 0, 12)));
    $favRows = store_product_rows($conn, "AND p.product_id IN ({$favIdList})", 'p.product_id DESC', 12);
    foreach ($favRows as $prod) {
        $pid = (int) $prod['product_id'];
        if (!isset($shopProductsById[$pid])) {
            $shopProductsById[$pid] = $prod;
        }
    }
}

// Fill up to at least 12 items if needed
if (count($shopProductsById) < 12) {
    $extraRows = store_catalog_product_rows($conn, limit: 16);
    foreach ($extraRows as $prod) {
        $pid = (int) $prod['product_id'];
        if (!isset($shopProductsById[$pid])) {
            $shopProductsById[$pid] = $prod;
        }
    }
}

$shopProducts = array_slice(array_values($shopProductsById), 0, 16);

$preorderCountInShop = 0;
foreach ($shopProducts as $prod) {
    $pid = (int) $prod['product_id'];
    $isFlashItem = isset($liveFlashProductIds[$pid]);
    if (!$isFlashItem && (int) ($prod['is_preorder'] ?? 0) === 1) {
        $preorderCountInShop++;
    }
}

$plushCategoryName = 'Plush';
foreach ($tabCategories as $catName) {
    if (stripos($catName, 'plush') !== false) {
        $plushCategoryName = $catName;
        break;
    }
}
$apparelCategoryName = 'Apparel';
foreach ($tabCategories as $catName) {
    if (stripos($catName, 'apparel') !== false) {
        $apparelCategoryName = $catName;
        break;
    }
}
$acrylicCategoryName = 'Acrylic';
foreach ($tabCategories as $catName) {
    if (stripos($catName, 'acrylic') !== false) {
        $acrylicCategoryName = $catName;
        break;
    }
}

$pageTitle = 'HoloVerse | Official hololive production Shop';
$siteRoot = '';
require __DIR__ . '/app/views/layout/head.php';
?>

<body class="home-page home-landing">
    <div class="page-shell home-landing">
        <?php require __DIR__ . '/app/views/components/watermark.php'; ?>
        <?php
        $activePage = 'home';
        require __DIR__ . '/app/views/components/site-header.php';
        ?>

        <main id="top">
            <section class="hero" aria-label="HoloVerse official shop introduction">
                <div class="hero-image" data-image-slideshow data-slideshow-images="/Assets/images/banner/landing-allforone.webp|/Assets/images/banner/landing-flowglow.webp|/Assets/images/banner/landing-justice.png|/Assets/images/banner/landing-compas-horizon.webp|/Assets/images/banner/landing-regloss-ar-live.webp|/Assets/images/banner/landing-travel-to-indo.webp" aria-hidden="true">
                    <div class="hero-slide is-active" data-slideshow-layer style="background-image: url('/Assets/images/banner/landing-allforone.webp');"></div>
                    <div class="hero-slide" data-slideshow-layer></div>
                </div>
                <div class="hero-shade" data-home-reveal="shade"></div>
                <div class="hero-copy" data-home-reveal>
                    <p class="hero-kicker">Your oshi. Your collection.</p>
                    <h1>
                        <span>hololive</span>
                        official shop
                    </h1>
                    <p class="hero-description">
                        Authentic apparel, plushies and collectibles from hololive production, delivered to fans around the world.
                    </p>
                    <div class="hero-actions">
                        <button class="button-primary" type="button" data-home-jump="All merch|all">
                            <span>Shop new arrivals</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </button>
                        <button class="button-text light" type="button" data-home-jump="<?= store_escape($plushCategoryName) ?>|all">
                            Explore plushies
                        </button>
                    </div>
                </div>
                <button class="hero-scroll" type="button" data-home-jump="All merch|all" aria-label="Scroll to shop">
                    <span>Scroll to discover</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
            </section>

            <section class="talent-marquee" aria-label="Featured hololive production talents">
                <div class="talent-marquee-track">
                    <ul class="talent-marquee-group" aria-label="Talent names">
                        <?php foreach ($talentTickerItems as $talent): ?>
                            <li>
                                <span class="talent-marquee-dot" aria-hidden="true"></span>
                                <a class="talent-marquee-link" href="/products/<?= rawurlencode($talent['slug']) ?>">
                                    <span class="talent-marquee-name"><?= store_escape($talent['name']) ?></span>
                                    <span class="talent-marquee-japanese" lang="ja"><?= store_escape($talent['japanese']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <ul class="talent-marquee-group" aria-hidden="true">
                        <?php foreach ($talentTickerItems as $talent): ?>
                            <li>
                                <span class="talent-marquee-dot" aria-hidden="true"></span>
                                <a class="talent-marquee-link" href="/products/<?= rawurlencode($talent['slug']) ?>" tabindex="-1">
                                    <span class="talent-marquee-name"><?= store_escape($talent['name']) ?></span>
                                    <span class="talent-marquee-japanese" lang="ja"><?= store_escape($talent['japanese']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <section id="shop" class="shop-section landing-shell">
                <div class="section-heading" data-home-reveal>
                    <div>
                        <p class="eyebrow">Latest from the official shop</p>
                        <h2>New arrivals</h2>
                        <p class="shop-section-description">Fresh merch from across hololive production. Quantities may be limited.</p>
                    </div>
                </div>

                <div class="product-toolbar" data-home-reveal>
                    <div class="filter-tabs" role="tablist" aria-label="Product categories">
                        <button
                            type="button"
                            class="active"
                            role="tab"
                            aria-selected="true"
                            data-category-tab="All merch"
                        >
                            All merch
                        </button>
                        <?php foreach ($tabCategories as $categoryName): ?>
                            <button
                                type="button"
                                role="tab"
                                aria-selected="false"
                                data-category-tab="<?= store_escape((string) $categoryName) ?>"
                            >
                                <?= store_escape((string) $categoryName) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <div class="section-heading-aside">
                        <a class="button-text" href="products">
                            View all products
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>

                <p class="results-caption" data-home-reveal data-shop-results-caption>
                    <?= count($shopProducts) ?> <?= count($shopProducts) === 1 ? 'piece' : 'pieces' ?> &middot; Official character goods
                </p>

                <div class="product-grid" data-home-reveal data-shop-grid>
                    <?php foreach ($shopProducts as $index => $product): ?>
                        <?php
                        $productId = (int) $product['product_id'];
                        $variantId = (int) $product['variant_id'];
                        $productName = (string) $product['product_name'];
                        $productUrl = store_product_url($productName);
                        $categoryName = (string) ($product['category_name'] ?? 'Merch');
                        $talentName = (string) ($product['talent_name'] ?? '');
                        if ($talentName === '') {
                            $talentName = $categoryName;
                        }
                        $price = (float) ($product['price'] ?? 0);
                        $compareAtPrice = !empty($product['compare_at_price']) && (float) $product['compare_at_price'] > $price
                            ? (float) $product['compare_at_price']
                            : 0.0;
                        $stockQuantity = max(0, (int) ($product['stock_quantity'] ?? 0));
                        $isPreorder = (int) ($product['is_preorder'] ?? 0) === 1;
                        $isFlash = isset($liveFlashProductIds[$productId]);
                        $status = $isFlash ? 'flash' : ($isPreorder ? 'preorder' : 'ready');
                        $statusLabel = $isFlash ? 'Flash drop' : ($isPreorder ? 'Pre-order' : 'Ready to ship');
                        $shipWindow = $isPreorder
                            ? (!empty($product['expected_ship_date'])
                                ? 'Expected ' . date('F Y', strtotime((string) $product['expected_ship_date']))
                                : 'Expected Spring 2027')
                            : '';
                        $badge = '';
                        if ($stockQuantity <= 0) {
                            $badge = 'Sold out';
                        } elseif ($isFlash) {
                            $badge = 'New';
                        } elseif ($isPreorder) {
                            $badge = 'Limited';
                        } elseif ($index === 0) {
                            $badge = 'Fan favorite';
                        }
                        $imageUrl = store_asset_url((string) ($product['image_url'] ?: 'assets/images/logo.png'));
                        $isFavorite = in_array($productId, $favoriteProductIds, true);
                        $variantNote = (string) ($product['variant_name'] ?? '');
                        if ($variantNote === '' || strtolower($variantNote) === 'default' || strtolower($variantNote) === 'standard') {
                            $variantNote = $categoryName;
                        }
                        $quickViewPayload = [
                            'productId' => $productId,
                            'variantId' => $variantId,
                            'name' => $productName,
                            'talent' => $talentName,
                            'category' => $categoryName,
                            'priceFormatted' => store_format_price($price),
                            'compareAtFormatted' => $compareAtPrice > 0 ? store_format_price($compareAtPrice) : '',
                            'description' => trim((string) ($product['description'] ?? '')) ?: 'Official hololive production merchandise. Designed for fans and produced with authentic character artwork.',
                            'image' => $imageUrl,
                            'status' => $status,
                            'statusLabel' => $statusLabel,
                            'shipWindow' => $shipWindow,
                            'stockQuantity' => $stockQuantity,
                            'productUrl' => $productUrl,
                        ];
                        ?>
                        <article
                            class="product-card home-product-card"
                            data-product-card
                            data-product-id="<?= $productId ?>"
                            data-category="<?= store_escape($categoryName) ?>"
                            data-status="<?= store_escape($status) ?>"
                            data-price="<?= $price ?>"
                            data-name="<?= store_escape(strtolower($productName)) ?>"
                            data-talent="<?= store_escape(strtolower($talentName)) ?>"
                            data-featured-index="<?= $index ?>"
                            data-quick-view="<?= store_escape((string) json_encode($quickViewPayload, JSON_UNESCAPED_SLASHES)) ?>"
                        >
                            <div class="product-image-wrap">
                                <img src="<?= store_escape($imageUrl) ?>" alt="<?= store_escape((string) ($product['alt_text'] ?: $productName)) ?>" loading="lazy">
                                <button class="home-product-quickview" type="button" data-quick-view-open aria-label="Quick view <?= store_escape($productName) ?>">
                                    <span class="quick-view">Quick view</span>
                                </button>
                                <?php if ($badge !== ''): ?>
                                    <span class="product-badge"><?= store_escape($badge) ?></span>
                                <?php endif; ?>

                                <form class="collector-favorite-form home-product-favorite-form" action="<?= store_escape($favoriteShowcaseUrl) ?>" method="post" data-favorite-form>
                                    <input type="hidden" name="csrf_token" value="<?= store_escape($csrfToken) ?>">
                                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                                    <input type="hidden" name="action" value="<?= $isFavorite ? 'remove' : 'save' ?>">
                                    <button
                                        class="collector-favorite-button favorite-button<?= $isFavorite ? ' is-favorite active' : '' ?>"
                                        type="submit"
                                        aria-label="<?= $isFavorite ? 'Remove ' : 'Add ' ?><?= store_escape($productName) ?> <?= $isFavorite ? 'from' : 'to' ?> your Collector Showcase"
                                        aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
                                        title="<?= $isFavorite ? 'Remove from Collector Showcase' : 'Save to Collector Showcase' ?>"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="<?= $isFavorite ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
                                        </svg>
                                    </button>
                                </form>

                                <?php if ($stockQuantity > 0): ?>
                                    <form class="home-product-cart-form" action="cart" method="post" data-toast-message="<?= store_escape($productName . ($status === 'preorder' ? ' reserved in your bag' : ' added to your bag')) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= store_escape($csrfToken) ?>">
                                        <input type="hidden" name="variant_id" value="<?= $variantId ?>">
                                        <button class="add-button<?= $status === 'preorder' ? ' preorder-add' : '' ?>" type="submit" name="action" value="add">
                                            <span><?= $status === 'preorder' ? 'Reserve pre-order' : ($status === 'flash' ? 'Claim flash price' : 'Add to bag') ?></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="home-product-cart-form">
                                        <button class="add-button" type="button" disabled>
                                            <span>Sold out</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="product-info">
                                <p><?= store_escape($categoryName) ?></p>
                                <div class="home-product-title-row">
                                    <a class="product-title-link" href="<?= store_escape($productUrl) ?>"><?= store_escape($productName) ?></a>
                                    <div class="home-product-price">
                                        <strong><?= store_escape(store_format_price($price)) ?></strong>
                                        <?php if ($compareAtPrice > 0): ?>
                                            <del><?= store_escape(store_format_price($compareAtPrice)) ?></del>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="product-meta">
                                    <span class="merch-status status-<?= store_escape($status) ?>">
                                        <?php if ($status === 'flash'): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 1 11 14z"/></svg>
                                        <?php elseif ($status === 'preorder'): ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                                        <?php endif; ?>
                                        <?= store_escape($statusLabel) ?>
                                    </span>
                                    <span><?= store_escape($variantNote) ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <nav class="all-products-pagination home-shop-pagination hidden" aria-label="New arrival product pages" data-home-reveal data-shop-pagination hidden>
                    <button class="all-products-page-button" type="button" data-shop-page-previous aria-label="Previous page">Prev</button>
                    <div class="all-products-page-list" data-shop-page-list></div>
                    <button class="all-products-page-button" type="button" data-shop-page-next aria-label="Next page">Next</button>
                </nav>

                <div class="empty-search" data-shop-empty hidden>
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <h3>No merch found</h3>
                    <p>Try another talent, item, or category.</p>
                    <button class="button-primary dark" type="button" data-shop-reset>
                        Reset search
                    </button>
                </div>
            </section>

            <section class="talent-marquee" aria-label="Hololive talent groups">
                <div class="talent-marquee-track">
                    <ul class="talent-marquee-group" aria-label="Talent group names">
                        <?php foreach ($talentGroupNames as $group): ?>
                            <li>
                                <span class="talent-marquee-dot" aria-hidden="true"></span>
                                <a class="talent-marquee-link" href="/talents?group=<?= rawurlencode($group['slug']) ?>">
                                    <span class="talent-marquee-name"><?= store_escape($group['name']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <ul class="talent-marquee-group" aria-hidden="true">
                        <?php foreach ($talentGroupNames as $group): ?>
                            <li>
                                <span class="talent-marquee-dot" aria-hidden="true"></span>
                                <a class="talent-marquee-link" href="/talents?group=<?= rawurlencode($group['slug']) ?>" tabindex="-1">
                                    <span class="talent-marquee-name"><?= store_escape($group['name']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <section class="home-video-section" data-home-reveal aria-label="Hololive video feature">
                <div class="home-video-frame">
                    <video autoplay muted loop playsinline preload="metadata" aria-label="Hololive featured video">
                        <source src="assets/images/design/Video%20Project%203.mp4" type="video/mp4">
                        Your browser does not support embedded videos.
                    </video>
                </div>
            </section>

            <section class="home-faq-section" aria-labelledby="home-faq-title" data-home-reveal>
                <div class="home-faq-layout landing-shell">
                    <div class="home-faq-intro" data-home-reveal>
                        <p class="home-faq-eyebrow">The little details</p>
                        <h2 id="home-faq-title">Good to<br><em>know.</em></h2>
                        <p class="home-faq-description">Wondering about something? Here are a few helpful notes before you find your new favorite.</p>
                        <a class="home-faq-contact" href="about-us">
                            <span>Our Team</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>

                    <div class="home-faq-list" data-home-reveal>
                        <details class="home-faq-item" open>
                            <summary>Where can I find shipping details for my order?</summary>
                            <div class="home-faq-answer">
                                <p>Shipping options, delivery estimates, and any applicable fees are shown at checkout for your destination. Product-specific notes are listed with each item.</p>
                            </div>
                        </details>
                        <details class="home-faq-item">
                            <summary>Are all items available everywhere?</summary>
                            <div class="home-faq-answer">
                                <p>Availability and shipping options can vary by destination. Enter your shipping address at checkout to see which options are available for your order.</p>
                            </div>
                        </details>
                        <details class="home-faq-item">
                            <summary>Can I change or cancel an order?</summary>
                            <div class="home-faq-answer">
                                <p>Please contact our team as soon as possible through the <a href="about-us">About Us page</a>. Changes may not be possible once an order has entered processing or shipped.</p>
                            </div>
                        </details>
                        <details class="home-faq-item">
                            <summary>How do I hear about new collections?</summary>
                            <div class="home-faq-answer">
                                <p>Browse the <a href="products">latest arrivals</a> and featured collections, or sign up for the newsletter below to hear about new drops.</p>
                            </div>
                        </details>
                    </div>
                </div>
            </section>

            <section id="collections" class="street-feature" data-home-reveal style="background-image: linear-gradient(90deg, rgba(4, 8, 15, 0.97), rgba(4, 8, 15, 0.75) 32%, rgba(4, 8, 15, 0.08) 63%), url('assets/images/design/collection.png');">
                <div class="street-copy">
                    <p class="eyebrow">Seasonal drop</p>
                    <h2>Autumn Street</h2>
                    <p>Five talents. One sharp new look. Discover official acrylic stands, apparel and accessories from the seasonal collection.</p>
                    <button class="button-primary" type="button" data-home-jump="<?= store_escape($apparelCategoryName) ?>|all">
                        <span>Shop the collection</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>
                </div>
            </section>

            <section id="talents" class="talent-feature landing-shell" data-home-reveal>
                <div class="talent-art">
                    <img src="assets/images/design/talents.png" alt="hololive English Advent and Justice official artwork" loading="lazy">
                </div>
                <div class="talent-copy">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg>
                    <p class="eyebrow">Featured talents</p>
                    <h2>Meet Advent and Justice</h2>
                    <p>
                        Bring home official goods inspired by hololive English. Explore character acrylics, collectible art and fan-ready apparel.
                    </p>
                    <div class="talent-actions">
                        <button class="button-text" type="button" data-home-jump="<?= store_escape($acrylicCategoryName) ?>|all">
                            <span>Shop hololive English</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </button>
                        <a class="button-text" href="talents">
                            <span>Explore all talents</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </section>

            <section class="newsletter">
                <div data-home-reveal>
                    <p class="eyebrow">Never miss a drop</p>
                    <h2>Merch news, direct from hololive.</h2>
                </div>
                <form data-home-reveal data-newsletter-form>
                    <label for="newsletter-email">Email address</label>
                    <div class="email-field">
                        <input id="newsletter-email" type="email" placeholder="you@example.com" required>
                        <button type="submit" aria-label="Subscribe to newsletter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </button>
                    </div>
                    <p class="newsletter-success" data-newsletter-success hidden>
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <span>You are on the list. Watch your inbox for merch drops.</span>
                    </p>
                </form>
            </section>
        </main>

        <?php require __DIR__ . '/app/views/components/site-footer.php'; ?>

        <div class="modal-wrap" data-quick-view-modal hidden role="dialog" aria-modal="true" aria-labelledby="quick-view-title">
            <button class="modal-backdrop" type="button" data-quick-view-close aria-label="Close quick view"></button>
            <div class="quick-modal">
                <button class="modal-close" type="button" data-quick-view-close aria-label="Close quick view">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
                <div class="quick-modal-image">
                    <img data-quick-view-image src="assets/images/logo.png" alt="">
                </div>
                <div class="quick-modal-copy">
                    <p class="eyebrow" data-quick-view-talent></p>
                    <h2 id="quick-view-title" data-quick-view-name></h2>
                    <strong data-quick-view-price></strong>
                    <del class="quick-compare-price" data-quick-view-compare hidden></del>
                    <p data-quick-view-description></p>
                    <div class="availability" data-quick-view-availability>
                        <span></span>
                        <p data-quick-view-status></p>
                    </div>
                    <div class="quick-modal-actions">
                        <form action="cart" method="post" data-quick-view-form>
                            <input type="hidden" name="csrf_token" value="<?= store_escape($csrfToken) ?>">
                            <input type="hidden" name="variant_id" value="" data-quick-view-variant>
                            <button class="button-primary dark" type="submit" name="action" value="add" data-quick-view-submit>
                                <span data-quick-view-submit-label>Add to bag</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                                    <path d="M3 6h18" />
                                    <path d="M16 10a4 4 0 0 1-8 0" />
                                </svg>
                            </button>
                        </form>
                        <a class="button-text" href="products" data-quick-view-link>
                            <span>View full product page</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            // Shop grid filtering, sorting, and jumping
            const shopGrid = document.querySelector("[data-shop-grid]");
            const productCards = shopGrid ? Array.from(shopGrid.querySelectorAll("[data-product-card]")) : [];
            const categoryTabs = Array.from(document.querySelectorAll("[data-category-tab]"));
            const resultsCaption = document.querySelector("[data-shop-results-caption]");
            const emptyState = document.querySelector("[data-shop-empty]");
            const resetBtn = document.querySelector("[data-shop-reset]");
            const shopPagination = document.querySelector("[data-shop-pagination]");
            const shopPageList = document.querySelector("[data-shop-page-list]");
            const previousShopPage = document.querySelector("[data-shop-page-previous]");
            const nextShopPage = document.querySelector("[data-shop-page-next]");

            let activeCategory = "All merch";
            let availabilityFilter = "all";
            let searchQuery = "";
            let currentShopPage = 1;
            const shopPageSize = 8;

            const applyShopFilters = (resetPage = true) => {
                if (!shopGrid) {
                    return 0;
                }
                const normalizedQuery = searchQuery.trim().toLowerCase();

                const matchingCards = productCards.filter((card) => {
                    const cardCategory = card.dataset.category || "";
                    const cardStatus = card.dataset.status || "ready";
                    const cardName = card.dataset.name || "";
                    const cardTalent = card.dataset.talent || "";

                    const matchesCategory = activeCategory === "All merch"
                        || cardCategory.toLowerCase() === activeCategory.toLowerCase()
                        || cardCategory.toLowerCase().includes(activeCategory.toLowerCase());
                    const matchesAvailability = availabilityFilter === "all" || cardStatus === availabilityFilter;
                    const matchesSearch = !normalizedQuery
                        || cardName.includes(normalizedQuery)
                        || cardTalent.includes(normalizedQuery)
                        || cardCategory.toLowerCase().includes(normalizedQuery);

                    return matchesCategory && matchesAvailability && matchesSearch;
                });

                matchingCards.sort((a, b) => Number(a.dataset.featuredIndex || 0) - Number(b.dataset.featuredIndex || 0));

                const pageCount = Math.ceil(matchingCards.length / shopPageSize);
                currentShopPage = resetPage
                    ? 1
                    : Math.min(currentShopPage, Math.max(pageCount, 1));
                const pageStart = (currentShopPage - 1) * shopPageSize;
                const visibleCards = matchingCards.slice(pageStart, pageStart + shopPageSize);
                const visibleSet = new Set(visibleCards);
                productCards.forEach((card) => {
                    card.hidden = !visibleSet.has(card);
                });
                matchingCards.forEach((card) => {
                    shopGrid.appendChild(card);
                });

                if (shopPagination && shopPageList && previousShopPage && nextShopPage) {
                    shopPagination.hidden = pageCount <= 1;
                    shopPagination.classList.toggle("hidden", pageCount <= 1);
                    previousShopPage.disabled = currentShopPage <= 1;
                    previousShopPage.classList.toggle("is-disabled", previousShopPage.disabled);
                    nextShopPage.disabled = currentShopPage >= pageCount;
                    nextShopPage.classList.toggle("is-disabled", nextShopPage.disabled);
                    shopPageList.replaceChildren();

                    for (let page = 1; page <= pageCount; page += 1) {
                        const pageButton = document.createElement("button");
                        pageButton.type = "button";
                        pageButton.className = `all-products-page-number${page === currentShopPage ? " is-active" : ""}`;
                        pageButton.dataset.shopPage = String(page);
                        pageButton.textContent = String(page);
                        if (page === currentShopPage) {
                            pageButton.setAttribute("aria-current", "page");
                            pageButton.disabled = true;
                        } else {
                            pageButton.setAttribute("aria-label", `Page ${page}`);
                        }
                        shopPageList.appendChild(pageButton);
                    }
                }

                if (emptyState) {
                    emptyState.hidden = matchingCards.length > 0;
                }

                if (resultsCaption) {
                    const suffix = availabilityFilter !== "all"
                        ? (availabilityFilter === "ready" ? "In stock" : (availabilityFilter === "preorder" ? "Pre-orders" : "Flash drop"))
                        : "Official character goods";
                    resultsCaption.textContent = `${matchingCards.length} ${matchingCards.length === 1 ? "piece" : "pieces"} · ${suffix}`;
                }

                categoryTabs.forEach((tab) => {
                    const isCurrent = (tab.dataset.categoryTab || "") === activeCategory;
                    tab.classList.toggle("active", isCurrent);
                    tab.setAttribute("aria-selected", String(isCurrent));
                });

                return matchingCards.length;
            };

            shopPagination?.addEventListener("click", (event) => {
                const target = event.target instanceof Element ? event.target.closest("button") : null;
                if (!target || target.disabled) {
                    return;
                }

                const requestedPage = target.dataset.shopPage
                    ? Number(target.dataset.shopPage)
                    : target === previousShopPage
                        ? currentShopPage - 1
                        : target === nextShopPage
                            ? currentShopPage + 1
                            : currentShopPage;
                if (!Number.isInteger(requestedPage) || requestedPage === currentShopPage) {
                    return;
                }

                currentShopPage = requestedPage;
                applyShopFilters(false);
                shopGrid?.scrollIntoView({ behavior: "smooth", block: "start" });
                shopPageList?.querySelector(`[data-shop-page="${currentShopPage}"]`)?.focus({ preventScroll: true });
            });

            window.filterHoloVerseHomeShop = ({ category, status, query }) => {
                if (typeof category === "string") {
                    activeCategory = category;
                }
                if (typeof status === "string") {
                    availabilityFilter = status;
                }
                if (typeof query === "string") {
                    searchQuery = query;
                }
                return applyShopFilters();
            };

            categoryTabs.forEach((tab) => {
                tab.addEventListener("click", () => {
                    activeCategory = tab.dataset.categoryTab || "All merch";
                    applyShopFilters();
                });
            });

            resetBtn?.addEventListener("click", () => {
                searchQuery = "";
                activeCategory = "All merch";
                availabilityFilter = "all";
                const searchOverlayInput = document.querySelector("[data-search-overlay-input]");
                if (searchOverlayInput) {
                    searchOverlayInput.value = "";
                }
                applyShopFilters();
            });

            document.querySelectorAll("[data-home-jump]").forEach((trigger) => {
                trigger.addEventListener("click", (event) => {
                    const shopElement = document.querySelector("#shop");
                    if (!shopElement) {
                        return;
                    }
                    event.preventDefault();
                    const [targetCategory = "All merch", targetStatus = "all"] = (trigger.getAttribute("data-home-jump") || "All merch|all").split("|");
                    const hasMatchingTab = categoryTabs.some((tab) => (tab.dataset.categoryTab || "").toLowerCase() === targetCategory.toLowerCase());
                    activeCategory = hasMatchingTab
                        ? (categoryTabs.find((tab) => (tab.dataset.categoryTab || "").toLowerCase() === targetCategory.toLowerCase())?.dataset.categoryTab || "All merch")
                        : "All merch";
                    availabilityFilter = targetStatus || "all";
                    applyShopFilters();
                    shopElement.scrollIntoView({ behavior: "smooth" });
                });
            });

            applyShopFilters();

            // Quick View Modal
            const quickModal = document.querySelector("[data-quick-view-modal]");
            const quickCloseBtns = document.querySelectorAll("[data-quick-view-close]");
            const quickImage = document.querySelector("[data-quick-view-image]");
            const quickTalent = document.querySelector("[data-quick-view-talent]");
            const quickName = document.querySelector("[data-quick-view-name]");
            const quickPrice = document.querySelector("[data-quick-view-price]");
            const quickCompare = document.querySelector("[data-quick-view-compare]");
            const quickDesc = document.querySelector("[data-quick-view-description]");
            const quickAvailability = document.querySelector("[data-quick-view-availability]");
            const quickStatus = document.querySelector("[data-quick-view-status]");
            const quickVariantInput = document.querySelector("[data-quick-view-variant]");
            const quickSubmitBtn = document.querySelector("[data-quick-view-submit]");
            const quickSubmitLabel = document.querySelector("[data-quick-view-submit-label]");
            const quickForm = document.querySelector("[data-quick-view-form]");
            const quickLink = document.querySelector("[data-quick-view-link]");

            const closeQuickView = () => {
                if (quickModal) {
                    quickModal.hidden = true;
                }
            };

            document.querySelectorAll("[data-quick-view-open]").forEach((btn) => {
                btn.addEventListener("click", () => {
                    const card = btn.closest("[data-product-card]");
                    if (!card || !quickModal) {
                        return;
                    }
                    let data;
                    try {
                        data = JSON.parse(card.dataset.quickView || "{}");
                    } catch {
                        return;
                    }
                    if (quickImage) {
                        quickImage.src = data.image || "assets/images/logo.png";
                        quickImage.alt = data.name || "";
                    }
                    if (quickTalent) quickTalent.textContent = data.talent || "";
                    if (quickName) quickName.textContent = data.name || "";
                    if (quickPrice) quickPrice.textContent = data.priceFormatted || "";
                    if (quickCompare) {
                        if (data.compareAtFormatted) {
                            quickCompare.hidden = false;
                            quickCompare.textContent = data.compareAtFormatted;
                        } else {
                            quickCompare.hidden = true;
                        }
                    }
                    if (quickDesc) {
                        quickDesc.textContent = `${data.description || ""}${data.shipWindow ? ` ${data.shipWindow}.` : ""}`;
                    }
                    if (quickAvailability) {
                        quickAvailability.className = `availability availability-${data.status || "ready"}`;
                    }
                    if (quickStatus) {
                        quickStatus.textContent = `${data.statusLabel || "Ready to ship"}${data.shipWindow ? ` · ${data.shipWindow}` : ""}`;
                    }
                    if (quickVariantInput) {
                        quickVariantInput.value = String(data.variantId || "");
                    }
                    if (quickForm) {
                        quickForm.dataset.toastMessage = `${data.name} ${data.status === "preorder" ? "reserved in your bag" : "added to your bag"}`;
                    }
                    if (quickSubmitBtn && quickSubmitLabel) {
                        const inStock = Number(data.stockQuantity || 0) > 0;
                        quickSubmitBtn.disabled = !inStock;
                        quickSubmitLabel.textContent = !inStock
                            ? "Sold out"
                            : (data.status === "preorder" ? "Reserve pre-order" : (data.status === "flash" ? "Claim flash price" : "Add to bag"));
                    }
                    if (quickLink) {
                        quickLink.href = data.productUrl || "products";
                    }
                    quickModal.hidden = false;
                });
            });

            quickCloseBtns.forEach((btn) => btn.addEventListener("click", closeQuickView));
            window.addEventListener("holoverse:item-added", closeQuickView);
            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape") {
                    closeQuickView();
                }
            });

            // Newsletter form
            const newsletterForm = document.querySelector("[data-newsletter-form]");
            const newsletterSuccess = document.querySelector("[data-newsletter-success]");
            newsletterForm?.addEventListener("submit", (event) => {
                event.preventDefault();
                if (newsletterSuccess) {
                    newsletterSuccess.hidden = false;
                }
                newsletterForm.reset();
            });
        })();
    </script>
    <script src="assets/js/art-slideshow.js?v=<?= filemtime(__DIR__ . '/assets/js/art-slideshow.js') ?>" defer></script>
    <script src="assets/js/home-page.js?v=<?= filemtime(__DIR__ . '/assets/js/home-page.js') ?>" defer></script>
    <?php
    $cartHref = 'cart';
    include __DIR__ . '/app/views/components/cart-button.php';
    ?>
</body>

</html>
