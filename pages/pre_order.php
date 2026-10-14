<?php
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
                <h1>Get your next favorite before it arrives.</h1>
                <p>Reserve limited-run collectibles and apparel while they're still in production. Browse the current preview, then check the order guide for what to expect.</p>
            </section>

            <section class="preorder-grid" aria-label="Pre-order items">
                <article class="preorder-card">
                    <div class="preorder-art">
                        <span class="preorder-badge">Limited run</span>
                        <span class="preorder-symbol" aria-hidden="true">H</span>
                    </div>
                    <div class="preorder-body">
                        <div class="preorder-meta">Collector figure</div>
                        <h2>Oceanlight Stage Figure</h2>
                        <p>A display-ready centerpiece with a sparkling stage-inspired finish and collector packaging.</p>
                        <div class="preorder-footer">
                            <span class="preorder-price">$68</span>
                            <a class="preorder-button" href="collector_showcase.php">Order guide</a>
                        </div>
                        <div class="preorder-shipping">Estimated dispatch: Spring 2027</div>
                    </div>
                </article>

                <article class="preorder-card">
                    <div class="preorder-art preorder-art-sky">
                        <span class="preorder-badge">Pre-order</span>
                        <span class="preorder-symbol" aria-hidden="true">S</span>
                    </div>
                    <div class="preorder-body">
                        <div class="preorder-meta">Apparel</div>
                        <h2>Starlight Tour Tee</h2>
                        <p>A soft, everyday tee with a tour-inspired graphic and a relaxed unisex fit.</p>
                        <div class="preorder-footer">
                            <span class="preorder-price">$32</span>
                            <a class="preorder-button" href="collector_showcase.php">Order guide</a>
                        </div>
                        <div class="preorder-shipping">Estimated dispatch: Spring 2027</div>
                    </div>
                </article>

                <article class="preorder-card">
                    <div class="preorder-art preorder-art-coral">
                        <span class="preorder-badge">Fan favorite</span>
                        <span class="preorder-symbol" aria-hidden="true">P</span>
                    </div>
                    <div class="preorder-body">
                        <div class="preorder-meta">Plush collection</div>
                        <h2>Holo Friends Plush Set</h2>
                        <p>A cozy collectible duo sized for desks, shelves, and adventures on the go.</p>
                        <div class="preorder-footer">
                            <span class="preorder-price">$46</span>
                            <a class="preorder-button" href="collector_showcase.php">Order guide</a>
                        </div>
                        <div class="preorder-shipping">Estimated dispatch: Summer 2027</div>
                    </div>
                </article>
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
