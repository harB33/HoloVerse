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
    <title>About Us | hololive merchandise</title>
    <link rel="icon" type="image/png" href="../Assets/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800;900&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/tailwind.css">
</head>

<body>
    <div class="page-shell info-page about-page">
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
                <a href="flash_sale.php">Flash Sales</a>
                <a href="collector_showcase.php">Collector Showcase</a>
                <a href="personalized.php">Personalized</a>
                <a href="about-us.php" aria-current="page">About Us</a>
            </nav>

            <div class="header-actions">
                <a class="entry-button" href="login.php" aria-label="Log In"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></a>
            </div>
        </header>

        <main class="info-main">
            <section class="page-intro">
                <span class="eyebrow">Our story</span>
                <h1>Built for fans who collect memories.</h1>
                <p>hololive production merchandise is more than apparel and accessories — it is a celebration of the creators, communities, and moments that make each fan journey unforgettable. Every item is designed to bring a piece of the hololive energy into daily life, from limited-run apparel to display-ready collectibles.</p>
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
                        <strong>4.9/5</strong>
                        <span>Fan rating</span>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>

</html>
