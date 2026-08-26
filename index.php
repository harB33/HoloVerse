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
            <svg viewBox="0 0 900 700" role="presentation">
                <defs>
                    <path id="watermark-circle-outer" d="M 450,350 m -420,0 a 420,420 0 1,1 840,0 a 420,420 0 1,1 -840,0" />
                    <path id="watermark-circle-middle" d="M 450,350 m -285,0 a 285,285 0 1,1 570,0 a 285,285 0 1,1 -570,0" />
                    <path id="watermark-circle-inner" d="M 450,350 m -150,0 a 150,150 0 1,1 300,0 a 150,150 0 1,1 -300,0" />
                </defs>
                <text>
                    <textPath href="#watermark-circle-outer">hololive production merchandise   hololive production merchandise   </textPath>
                </text>
                <text>
                    <textPath href="#watermark-circle-middle">hololive production merchandise   hololive production merchandise   </textPath>
                </text>
                <text>
                    <textPath href="#watermark-circle-inner">hololive production merchandise   hololive production merchandise   </textPath>
                </text>
            </svg>
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
                <a class="entry-button" href="#featured">Sign Up<span aria-hidden="true">&#8594;</span></a>
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