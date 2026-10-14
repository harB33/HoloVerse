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
    <title>Featured Items | hololive merchandise</title>
    <link rel="icon" type="image/png" href="../Assets/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800;900&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/tailwind.css">
</head>

<body>
    <div class="page-shell info-page featured-page">
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
            <a class="brand" href="../index.php" aria-label="HoloVerse Store home">
                <img class="brand-logo" src="../Assets/logo.png" alt="">
                <span class="brand-wordmark">holo<span>verse</span></span>
            </a>

            <nav class="main-nav" aria-label="Main navigation">
                <a href="pre_order.php">Pre-Order</a>
                <a href="flash_sale.php" aria-current="page">Flash Sales</a>
                <a href="collector_showcase.php">Collector Showcase</a>
                <a href="personalized.php">Personalized</a>
                <a href="about-us.php">About Us</a>
            </nav>

            <div class="header-actions">
                <a class="entry-button" href="login.php" aria-label="Log In"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></a>
            </div>
        </header>

        <main class="info-main">
            <section class="page-intro">
                <span class="eyebrow">Featured drops</span>
                <h1>Collect the looks fans love most.</h1>
                <p>Freshly curated pieces inspired by the energy of holo fans — from statement apparel and cozy essentials to limited-run accessories that make every collection feel memorable.</p>
            </section>

            <section class="product-grid">
                <article class="product-card">
                    <div class="product-thumb">
                        <span class="product-badge">New</span>
                        <div class="product-symbol">H</div>
                    </div>
                    <div class="product-body">
                        <div class="product-meta">Apparel</div>
                        <h2>Holo Wave Hoodie</h2>
                        <p>Soft fleece layering with a graphic front print and oversized fit for daily comfort.</p>
                        <div class="product-footer">
                            <span class="price">$42</span>
                            <a class="shop-button" href="#">Add to cart</a>
                        </div>
                    </div>
                </article>

                <article class="product-card">
                    <div class="product-thumb bg-[linear-gradient(135deg,#d3e4ff_0%,#b7d0ff_35%,#8ab0ff_100%)]">
                        <span class="product-badge">Best Seller</span>
                        <div class="product-symbol">A</div>
                    </div>
                    <div class="product-body">
                        <div class="product-meta">Accessories</div>
                        <h2>Dreamline Cap</h2>
                        <p>Structured cap with breathable cotton panels and a clean fan-favorite emblem.</p>
                        <div class="product-footer">
                            <span class="price">$24</span>
                            <a class="shop-button" href="#">Add to cart</a>
                        </div>
                    </div>
                </article>

                <article class="product-card">
                    <div class="product-thumb bg-[linear-gradient(135deg,#ffe9d3_0%,#ffd7b0_35%,#f6b673_100%)]">
                        <span class="product-badge">Limited</span>
                        <div class="product-symbol">S</div>
                    </div>
                    <div class="product-body">
                        <div class="product-meta">Collector</div>
                        <h2>Starburst Stand</h2>
                        <p>Decorative display piece crafted for shelves, desks, and fan collections.</p>
                        <div class="product-footer">
                            <span class="price">$36</span>
                            <a class="shop-button" href="#">Add to cart</a>
                        </div>
                    </div>
                </article>
            </section>
        </main>
    </div>
</body>

</html>
