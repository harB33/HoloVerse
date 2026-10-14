<?php
$selectedTalent = isset($_GET['talent']) ? trim($_GET['talent']) : '';

$defaultTalentItems = [
    ['badge' => 'Limited run', 'type' => 'Collector figure', 'title' => 'Deluxe anniversary figure', 'description' => 'Display-ready centerpiece with collector packaging and numbered authentication card.', 'price' => '$68', 'dispatch' => 'Estimated dispatch: Spring 2027'],
    ['badge' => 'Pre-order', 'type' => 'Apparel', 'title' => 'Signature event tee', 'description' => 'A premium unisex shirt featuring tour-inspired artwork and a soft-touch finish.', 'price' => '$32', 'dispatch' => 'Estimated dispatch: Spring 2027'],
    ['badge' => 'Fan favorite', 'type' => 'Plush collection', 'title' => 'Official plush set', 'description' => 'A cozy companion pair sized for desks, shelves, and display shelves.', 'price' => '$46', 'dispatch' => 'Estimated dispatch: Summer 2027'],
];

if ($selectedTalent !== '') {
    $talentItems = [
        ['badge' => 'Collector figure', 'type' => '1/4 scale statue', 'title' => $selectedTalent . ' deluxe figure', 'description' => 'A premium showcase piece inspired by the talent\'s signature look and stage presence.', 'price' => '$89', 'dispatch' => 'Estimated dispatch: Spring 2027'],
        ['badge' => 'Pre-order', 'type' => 'Apparel', 'title' => $selectedTalent . ' event tee', 'description' => 'Official attire crafted with a relaxed fit and high-detail print celebrating the talent.', 'price' => '$34', 'dispatch' => 'Estimated dispatch: Spring 2027'],
        ['badge' => 'Fan favorite', 'type' => 'Plush', 'title' => $selectedTalent . ' mini plush set', 'description' => 'A compact plush set designed for easy display and collector shelf styling.', 'price' => '$48', 'dispatch' => 'Estimated dispatch: Summer 2027'],
    ];
} else {
    $talentItems = $defaultTalentItems;
}

$merchInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $selectedTalent !== '' ? $selectedTalent : 'HoloVerse'), 0, 1));

$pageTitle = 'Pre-Order | hololive merchandise';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell preorder-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>

        <?php
        $activePage = 'pre_order';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="preorder-main">
            <section class="preorder-intro">
                <span class="preorder-eyebrow">Pre-order window open</span>
                <?php if ($selectedTalent !== ''): ?>
                    <h1><?= htmlspecialchars($selectedTalent, ENT_QUOTES, 'UTF-8') ?> merchandise</h1>
                    <p>Browse the official merch collection for <?= htmlspecialchars($selectedTalent, ENT_QUOTES, 'UTF-8') ?> and reserve your favorite pieces before they sell out.</p>
                <?php else: ?>
                    <h1>Get your next favorite before it arrives.</h1>
                    <p>Reserve limited-run collectibles and apparel while they're still in production. Browse the current preview, then check the order guide for what to expect.</p>
                <?php endif; ?>
            </section>

            <section class="preorder-grid" aria-label="Pre-order items">
                <?php foreach ($talentItems as $item): ?>
                    <article class="preorder-card">
                        <div class="preorder-art">
                            <span class="preorder-badge"><?= htmlspecialchars($item['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="preorder-symbol" aria-hidden="true"><?= htmlspecialchars($merchInitial, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="preorder-body">
                            <div class="preorder-meta"><?= htmlspecialchars($item['type'], ENT_QUOTES, 'UTF-8') ?></div>
                            <h2><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p><?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="preorder-footer">
                                <span class="preorder-price"><?= htmlspecialchars($item['price'], ENT_QUOTES, 'UTF-8') ?></span>
                                <a class="preorder-button" href="collector_showcase.php">Order guide</a>
                            </div>
                            <div class="preorder-shipping"><?= htmlspecialchars($item['dispatch'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="preorder-guide">
                <div>
                    <h2>New to pre-orders?</h2>
                    <p>See how reservations, payment, and delivery work before choosing your next collectible.</p>
                </div>
                <a href="collector_showcase.php">Explore the order process <span aria-hidden="true">&#8594;</span></a>
            </aside>
        </main>
    </div>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
</body>

</html>
