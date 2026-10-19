<?php
require_once __DIR__ . '/app/store.php';

$groupOrder = ['gen-0', 'gen-1', 'gamers', 'gen-2', 'gen-3', 'gen-4', 'gen-5', 'holox', 'regloss', 'flow-glow', 'myth', 'promise', 'advent', 'justice', 'id-gen-1', 'id-gen-2', 'id-gen-3', 'asobi-mawaritai'];
$groupsResult = $conn->query(
    "SELECT group_slug, group_name FROM talent_group_tbl
     ORDER BY array_position(" . implode(',', [
        "ARRAY['" . implode("','", $groupOrder) . "']::text[]",
        'group_slug',
    ]) . ')'
);
$generationLabels = [];
while ($group = $groupsResult->fetch_assoc()) {
    $generationLabels[$group['group_slug']] = $group['group_name'];
}

$talents = [];
$talentResult = $conn->query(
    "SELECT t.talent_name, t.talent_img,
            STRING_AGG(g.group_slug, ' ' ORDER BY array_position(ARRAY['" . implode("','", $groupOrder) . "']::text[], g.group_slug)) AS group_slugs
     FROM talent_tbl t
     LEFT JOIN talent_group_members_tbl gm ON gm.talent_id = t.talent_id
     LEFT JOIN talent_group_tbl g ON g.group_id = gm.group_id
     GROUP BY t.talent_id, t.talent_name, t.talent_img
     ORDER BY t.talent_name"
);
while ($talent = $talentResult->fetch_assoc()) {
    $groupSlugs = $talent['group_slugs'] ? explode(' ', $talent['group_slugs']) : [];
    $groupNames = array_map(static fn (string $slug): string => $generationLabels[$slug] ?? $slug, $groupSlugs);
    if ($talent['talent_name'] === 'Watson Amelia') {
        $groupNames[] = 'Affiliate';
    }
    $talents[] = [
        'name' => $talent['talent_name'],
        'groups' => $groupSlugs,
        'group_names' => implode(' · ', $groupNames),
        'portrait' => $talent['talent_img'],
    ];
}

$featuredProducts = store_product_rows($conn, '', 'p.product_id DESC', 4);
$pageTitle = 'HoloVerse';
$siteRoot = '';
require __DIR__ . '/app/views/layout/head.php';
?>

<body>
    <main class="page-shell">
        <?php require __DIR__ . '/app/views/components/watermark.php'; ?>
        <?php
        $activePage = 'home';
        require __DIR__ . '/app/views/components/site-header.php';
        ?>

        <div id="top" class="hero">
            <section class="hero-copy" id="new-arrivals">
                <h1>hololive<br>production<br>merchandise</h1>
                <p>Exclusive Apparel, Figures, and Collectibles!</p>
                <p>Limited-Edition items for all hololive fans.</p>
            </section>
            <div class="hero-symbol">
                <img src="assets/images/logo.png" alt="hololive logo" fetchpriority="high">
            </div>
        </div>

        <section class="limited-drops" id="featured" aria-labelledby="limited-drops-title">
            <div class="drops-heading">
                <div>
                    <span class="drops-eyebrow">From the official hololive production shop</span>
                    <h2 id="limited-drops-title">Talent Merch Picks</h2>
                </div>
                <a class="drops-view-all" href="pages/products.php">View all products</a>
            </div>
            <div class="drops-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php $productInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product['product_name']), 0, 1)); ?>
                    <article class="drop-card">
                        <a class="drop-card-link" href="pages/product.php?id=<?= (int) $product['product_id'] ?>" aria-label="View <?= store_escape($product['product_name']) ?>"></a>
                        <div class="drop-art">
                            <div class="drop-art-labels">
                                <span class="drop-status"><?= (int) $product['stock_quantity'] > 0 ? 'Available now' : 'Sold out' ?></span>
                                <span class="drop-edition"><?= store_escape($product['variant_name']) ?></span>
                            </div>
                            <?php if (!empty($product['image_url'])): ?>
                                <img class="drop-product-image-backdrop" src="<?= store_escape(store_asset_url($product['image_url'])) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                <img class="drop-product-image" src="<?= store_escape(store_asset_url($product['image_url'])) ?>" alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>" loading="lazy" decoding="async">
                            <?php endif; ?>
                            <div class="drop-character<?= !empty($product['image_url']) ? ' hidden' : '' ?>"><?= store_escape($productInitial) ?></div>
                            <span class="drop-art-tag"><?= (int) $product['stock_quantity'] ?> in stock</span>
                        </div>
                        <div class="drop-details">
                            <span class="drop-category"><?= store_escape($product['category_name']) ?></span>
                            <h3><?= store_escape($product['product_name']) ?></h3>
                            <p class="drop-description"><?= store_escape($product['description'] ?? '') ?></p>
                            <div class="drop-allocation"><span>Available quantity</span><strong><?= (int) $product['stock_quantity'] ?></strong></div>
                            <div class="drop-purchase">
                                <div><span class="drop-price-label"><?= (int) $product['is_preorder'] === 1 ? 'Pre-order price' : 'Price' ?></span><strong><?= store_escape(store_format_price((float) $product['price'])) ?></strong></div>
                                <?php if ((int) $product['stock_quantity'] > 0): ?>
                                    <form action="pages/cart.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                        <input type="hidden" name="variant_id" value="<?= (int) $product['variant_id'] ?>">
                                        <button type="submit" name="action" value="add">Add to cart</button>
                                    </form>
                                <?php else: ?>
                                    <span>Sold out</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="explore" id="explore" aria-labelledby="explore-title">
            <div class="explore-inner">
                <header class="explore-header">
                    <span class="explore-eyebrow">Talent Vault Directory</span>
                    <h2 id="explore-title">Explore Merchandise by Oshi</h2>
                    <p>Filter official apparel, plushies, voice packs, and figures directly tied to your favorite talent.</p>
                </header>
                <div class="explore-filters" role="group" aria-label="Filter talents by generation or group">
                    <button class="explore-filter" type="button" data-explore-filter="all" aria-pressed="true">All Generations (<?= count($talents) ?> Talents)</button>
                    <?php foreach ($generationLabels as $slug => $label): ?>
                        <button class="explore-filter" type="button" data-explore-filter="<?= store_escape($slug) ?>" aria-pressed="false"><?= store_escape($label) ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="talent-grid" aria-live="polite">
                    <?php foreach ($talents as $talent): ?>
                        <?php $talentLink = 'pages/talent-merch.php?talent=' . rawurlencode($talent['name']); ?>
                        <a class="talent-card-link" href="<?= store_escape($talentLink) ?>" aria-label="View merchandise for <?= store_escape($talent['name']) ?>">
                            <article class="talent-card" data-generations="<?= store_escape(implode(' ', $talent['groups'])) ?>">
                                <span class="talent-avatar">
                                    <span class="talent-initials" aria-hidden="true"><?= store_escape(strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $talent['name']), 0, 2))) ?></span>
                                    <img class="talent-photo talent-photo-backdrop" src="<?= store_escape($talent['portrait']) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                    <img class="talent-photo talent-photo-foreground" src="<?= store_escape($talent['portrait']) ?>" alt="<?= store_escape($talent['name']) ?>" loading="lazy" decoding="async">
                                </span>
                                <h3><?= store_escape($talent['name']) ?></h3>
                                <p><?= store_escape($talent['group_names']) ?></p>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </div>
                <p class="explore-empty hidden" role="status">No featured talents in this generation or group yet.</p>
            </div>
        </section>
        <?php $showRadarSignup = true; require __DIR__ . '/app/views/components/site-footer.php'; ?>
    </main>

    <script>
        const exploreFilters = document.querySelectorAll('[data-explore-filter]');
        const talentCards = document.querySelectorAll('.talent-card-link');
        const exploreEmpty = document.querySelector('.explore-empty');
        exploreFilters.forEach((filter) => filter.addEventListener('click', () => {
            const selected = filter.dataset.exploreFilter;
            let visible = 0;
            exploreFilters.forEach((button) => button.setAttribute('aria-pressed', String(button === filter)));
            talentCards.forEach((link) => {
                const groups = link.querySelector('.talent-card').dataset.generations.split(' ');
                const show = selected === 'all' || groups.includes(selected);
                link.classList.toggle('hidden', !show);
                visible += Number(show);
            });
            exploreEmpty.classList.toggle('hidden', visible > 0);
        }));

        document.querySelectorAll('.drop-product-image').forEach((image) => image.addEventListener('error', () => {
            image.classList.add('hidden');
            image.nextElementSibling?.classList.remove('hidden');
        }, { once: true }));
        document.querySelectorAll('.talent-photo').forEach((image) => image.addEventListener('error', () => image.classList.add('hidden'), { once: true }));
    </script>
    <?php
    $cartHref = 'pages/cart.php';
    include __DIR__ . '/app/views/components/cart-button.php';
    ?>
</body>

</html>