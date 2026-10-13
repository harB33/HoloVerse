<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Order | hololive merchandise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800;900&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/tailwind.css">
</head>

<body>
    <div class="page-shell preorder-page">
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
                <a href="pre_order.php" aria-current="page">Pre-Order</a>
                <a href="featured-items.php">Flash Sales</a>
                <a href="order-process.php">Collector Showcase</a>
                <a href="shipping-info.php">Personalized</a>
                <a href="about-us.php">About Us</a>
            </nav>

            <div class="header-actions">
                <a class="entry-button" href="order-process.php">Order Guide<span aria-hidden="true">&#8594;</span></a>
            </div>
        </header>

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
                            <a class="preorder-button" href="order-process.php">Order guide</a>
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
                            <a class="preorder-button" href="order-process.php">Order guide</a>
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
                            <a class="preorder-button" href="order-process.php">Order guide</a>
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
                <a href="order-process.php">Explore the order process <span aria-hidden="true">&#8594;</span></a>
            </aside>
        </main>
    </div>
</body>

</html>
