<?php
require_once __DIR__ . '/../config/dbconnect.php';
$pageTitle = 'About Us | hololive merchandise';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell info-page about-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = 'about-us';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="info-main">
            <section class="page-intro">
                <span class="eyebrow">Our story</span>
                <h1>Built for fans who collect memories.</h1>
                <p>holoverse is more than apparel and accessories — it is a celebration of the creators, communities, and moments that make each fan journey unforgettable. Every item is designed to bring a piece of the hololive energy into daily life, from limited-run apparel to display-ready collectibles.</p>
            </section>

            <section class="content-grid">
                <article class="info-card">
                    <span class="badge">Mission</span>
                    <h2>Celebrate creativity</h2>
                    <p>We curate thoughtful pieces inspired by the talent, culture, and community that define hololive. Our goal is to help fans showcase their love in a way that feels premium, personal, and collectible.</p>
                </article>

                <article class="info-card">
                    <span class="badge">Quality</span>
                    <h2>Made to stand out</h2>
                    <p>Each collection balances bold design, comfort, and durable construction. We focus on materials, fit, and attention to detail so your favorite characters are represented with pride.</p>
                </article>

                <article class="info-card">
                    <span class="badge">Community</span>
                    <h2>Created with fans</h2>
                    <ul>
                        <li>Limited runs and themed drops</li>
                        <li>Exclusive apparel and accessories</li>
                        <li>Collector-focused packaging and presentation</li>
                    </ul>
                </article>
            </section>

            <section class="feature-banner">
                <div>
                    <span class="eyebrow banner-eyebrow">Why fans choose us</span>
                    <h2>Premium pieces, unforgettable energy.</h2>
                    <p>From casual staples to event-exclusive collectibles, our store is built around identity, design, and fandom pride. We aim to keep every release exciting, accessible, and worthy of display.</p>
                </div>
                <div class="stats">
                    <div class="stat-box">
                        <strong>120+</strong>
                        <span>Curated drops</span>
                    </div>
                    <div class="stat-box">
                        <strong>24h</strong>
                        <span>Fast order handling</span>
                    </div>
                    <div class="stat-box">
                        <strong>5/5</strong>
                        <span>Fan rating</span>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
