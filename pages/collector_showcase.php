<?php
require_once __DIR__ . '/../app/store.php';
$pageTitle = 'Order Process | hololive merchandise';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell info-page order-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>

        <?php
        $activePage = 'collector_showcase';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="info-main">
            <section class="page-intro">
                <span class="eyebrow">How it works</span>
                <h1>Simple steps from cart to your doorstep.</h1>
                <p>We keep the shopping flow clear and easy so you can focus on your favorite items, not the details. Every order is reviewed, packed, and shipped with care.</p>
            </section>

            <section class="timeline">
                <article class="step-card">
                    <div class="step-number">1</div>
                    <h2>Choose your item</h2>
                    <p>Browse featured collections, compare sizes, and confirm the exact pieces you want before checkout.</p>
                </article>

                <article class="step-card">
                    <div class="step-number">2</div>
                    <h2>Secure checkout</h2>
                    <p>Select your shipping option and complete payment through a secure order form.</p>
                </article>

                <article class="step-card">
                    <div class="step-number">3</div>
                    <h2>Pack & prepare</h2>
                    <p>Our team reviews the order, prepares the packaging, and verifies the details before dispatch.</p>
                </article>

                <article class="step-card">
                    <div class="step-number">4</div>
                    <h2>Track delivery</h2>
                    <p>Once shipped, you receive tracking information and can monitor the journey to your address.</p>
                </article>
            </section>

            <section class="info-panel">
                <div class="panel-box">
                    <h3>What happens after checkout</h3>
                    <ul class="check-list">
                        <li>Order confirmation email with summary and timing</li>
                        <li>Packaging review for quality control and item verification</li>
                        <li>Shipment created and tracking information sent promptly</li>
                        <li>Support available for size, shipping, or order updates</li>
                    </ul>
                </div>

                <aside class="side-box">
                    <h3>Need help?</h3>
                    <p>Our support team can assist with checkout questions, shipping updates, and order adjustments before dispatch.</p>
                    <a href="#">support@hololive-merch.example</a>
                </aside>
            </section>
        </main>
    </div>
    <?php
    $cartHref = 'cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
