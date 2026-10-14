<?php
require_once __DIR__ . '/../config/dbconnect.php';
$pageTitle = 'Shipping Info | hololive merchandise';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell info-page shipping-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>

        <?php
        $activePage = 'personalized';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="info-main">
            <section class="page-intro">
                <span class="eyebrow">Shipping guide</span>
                <h1>Delivery details made easy.</h1>
                <p>Choose the option that suits your timeline and region. We ship carefully packed orders with transparent handling times and tracking updates for every step.</p>
            </section>

            <section class="shipping-grid">
                <article class="shipping-card">
                    <div class="rate">Standard</div>
                    <h2>Regular shipping</h2>
                    <p>Best for everyday orders and flexible delivery windows.</p>
                    <ul>
                        <li>3–7 business days</li>
                        <li>Tracked package</li>
                        <li>Cost varies by region</li>
                    </ul>
                </article>

                <article class="shipping-card">
                    <div class="rate">Express</div>
                    <h2>Priority delivery</h2>
                    <p>Fast shipping for event drops and collector releases.</p>
                    <ul>
                        <li>2–4 business days</li>
                        <li>Priority handling</li>
                        <li>Enhanced tracking updates</li>
                    </ul>
                </article>

                <article class="shipping-card">
                    <div class="rate">Global</div>
                    <h2>International</h2>
                    <p>Available for select regions with customs and import information included.</p>
                    <ul>
                        <li>5–12 business days</li>
                        <li>Customs fees may apply</li>
                        <li>Regional carrier selection</li>
                    </ul>
                </article>
            </section>

            <section class="detail-layout">
                <div class="panel-box">
                    <h3>Frequently asked questions</h3>
                    <ul class="faq-list">
                        <li>When do you ship orders? Orders are packed within 1–2 business days after confirmation.</li>
                        <li>Do you offer tracking? Yes, every shipment includes status updates and a tracking number.</li>
                        <li>Can I change my shipping address? Address updates can be requested before dispatch is finalized.</li>
                        <li>Are customs fees included? International duties and taxes may be billed separately depending on destination rules.</li>
                    </ul>
                </div>

                <aside class="notice">
                    <h3>Returns & exchanges</h3>
                    <p>We accept returns for damaged or incorrect items within the review window. Please contact support with your order number and product details.</p>
                </aside>
            </section>
        </main>
    </div>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
</body>

</html>
