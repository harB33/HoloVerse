<?php
if (file_exists(__DIR__ . '/../Database/dbconnect.php')) {
    include __DIR__ . '/../Database/dbconnect.php';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Process | hololive merchandise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800;900&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/tailwind.css">
</head>

<body>
    <div class="page-shell info-page order-page">
        <div class="watermark" aria-hidden="true">
            <svg viewBox="0 0 900 700" role="presentation">
                <defs>
                    <path id="watermark-circle-outer" d="M 450,350 m -525,0 a 525,525 0 1,1 1050,0 a 525,525 0 1,1 -1050,0" />
                    <path id="watermark-circle-middle" d="M 450,350 m -360,0 a 360,360 0 1,1 720,0 a 360,360 0 1,1 -720,0" />
                    <path id="watermark-circle-inner" d="M 450,350 m -200,0 a 200,200 0 1,1 400,0 a 200,200 0 1,1 -400,0" />
                </defs>
                <g class="watermark-ring watermark-ring-outer">
                    <text>
                        <textPath href="#watermark-circle-outer">hololive production merchandise hololive production merchandise hololive production merchandise hololive production merchandise</textPath>
                    </text>
                </g>
                <g class="watermark-ring watermark-ring-middle">
                    <text>
                        <textPath href="#watermark-circle-middle">hololive production merchandise hololive production merchandise hololive production merchandise</textPath>
                    </text>
                </g>
                <g class="watermark-ring watermark-ring-inner">
                    <text>
                        <textPath href="#watermark-circle-inner">hololive production merchandise hololive production</textPath>
                    </text>
                </g>
            </svg>
        </div>

        <header class="site-header">
            <a class="brand" href="../index.php" aria-label="hololive home">
                <img class="brand-logo" src="../Assets/hololive.png" alt="hololive production">
            </a>

            <nav class="main-nav" aria-label="Main navigation">
                <a href="pre_order.php">Pre-Order</a>
                <a href="featured-items.php">Flash Sales</a>
                <a href="order-process.php">Collector Showcase</a>
                <a href="shipping-info.php">Personalized</a>
                <a href="about-us.php">About Us</a>
            </nav>

            <div class="header-actions">
                <a class="entry-button" href="featured-items.php">Shop Now<span aria-hidden="true">&#8594;</span></a>
            </div>
        </header>

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
</body>

</html>
