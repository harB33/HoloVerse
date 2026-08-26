<?php
include 'Database/dbconnect.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>hololive production merchandise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="page-shell">
        <div class="watermark" aria-hidden="true">
            <span class="watermark-left">hololive production</span>
            <span class="watermark-right">hololive production</span>
            <span class="watermark-bottom">merchandise merchandise</span>
        </div>

        <header class="site-header">
            <a class="brand" href="#top" aria-label="hololive home">
                <span class="brand-mark"><span></span></span>
                <span class="brand-copy"><strong>hololive</strong><small>production</small></span>
            </a>

            <nav class="main-nav" aria-label="Main navigation">
                <a href="#new-arrivals">New Arrivals</a>
                <a href="#featured">Featured Items</a>
                <a href="#process">Order Process</a>
                <a href="#shipping">Shipping Info</a>
                <a href="#support">Customer Support</a>
            </nav>

            <div class="header-actions">
                <a class="entry-button" href="#featured">Entry <span aria-hidden="true">&#8594;</span></a>
                <div class="language-switcher" aria-label="Language selection">
                    <button class="language active" type="button">JA</button>
                    <button class="language" type="button">EN</button>
                </div>
            </div>
        </header>

        <main id="top" class="hero">
            <section class="hero-copy" id="new-arrivals">
                <h1>hololive<br>production<br>merchandise</h1>
                <p>Exclusive Apparel, Figures, and Collectibles!</p>
                <p>Limited-Edition items for all hololive fans.</p>
            </section>

            <div class="hero-symbol" aria-label="hololive play symbol">
                <div class="symbol-layer symbol-back"></div>
                <div class="symbol-layer symbol-mid"></div>
                <div class="symbol-layer symbol-front"></div>
            </div>
        </main>
    </div>
</body>

</html>