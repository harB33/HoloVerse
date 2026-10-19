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
    <style>
        .cart-fab {
            position: fixed;
            right: 28px;
            bottom: 28px;
            width: 72px;
            height: 72px;
            border-radius: 18px;
            background: linear-gradient(135deg, #6ad6f2, #1ca6db);
            border: 3px solid rgba(255,255,255,0.9);
            box-shadow: 0 10px 24px rgba(14, 110, 157, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            z-index: 1000;
        }

        .cart-fab .cart-badge {
            position: absolute;
            right: -10px;
            top: -10px;
            min-width: 28px;
            height: 28px;
            padding: 0 8px;
            border-radius: 999px;
            background: #0d1b2a;
            border: 2px solid rgba(255,255,255,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            line-height: 1;
            color: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.28);
        }

        .cart-fab svg {
            width: 34px;
            height: 34px;
            stroke: currentColor;
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <div class="watermark" aria-hidden="true">
            <svg viewBox="0 0 900 700" role="presentation">
                <defs>
                    <path id="watermark-circle-outer" d="M 450,350 m -550,0 a 550,550 0 1,1 1100,0 a 550,550 0 1,1 -1100,0" />
                    <path id="watermark-circle-middle" d="M 450,350 m -415,0 a 415,415 0 1,1 830,0 a 415,415 0 1,1 -830,0" />
                    <path id="watermark-circle-inner" d="M 450,350 m -280,0 a 280,280 0 1,1 560,0 a 280,280 0 1,1 -560,0" />
                </defs>
                <g class="watermark-ring watermark-ring-outer">
                    <text>
                    <textPath href="#watermark-circle-outer">hololive production merchandise   hololive production merchandise   hololive production merchandise  hololive production merchandise  hololive production merchandise   </textPath>
                    </text>
                </g>
                <g class="watermark-ring watermark-ring-middle">¬
                    <text>
                    <textPath href="#watermark-circle-middle">hololive production merchandise   hololive production merchandise  hololive production merchandise   hololive production merchandise   </textPath>
                    </text>
                </g>
                <g class="watermark-ring watermark-ring-inner">
                    <text>
                    <textPath href="#watermark-circle-inner">hololive production merchandise   hololive production merchandise   hololive production merchandise   </textPath>
                    </text>
                </g>
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

    <a class="cart-fab" href="pages/cart.php" aria-label="Open cart">
        <span class="cart-badge" aria-live="polite">0</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m15 11-1 9" />
            <path d="m19 11-4-7" />
            <path d="M2 11h20" />
            <path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4" />
            <path d="M4.5 15.5h15" />
            <path d="m5 11 4-7" />
            <path d="m9 11 1 9" />
        </svg>
    </a>

    <script>
        (() => {
            const badge = document.querySelector('.cart-badge');
            if (!badge) return;

            const syncBadge = async () => {
                try {
                    const response = await fetch('pages/cart.php?mode=count', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json().catch(() => ({ count: 5 }));
                    const count = Number(data?.count || 5);
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.hidden = false;
                } catch (error) {
                    console.warn('Unable to load cart count.', error);
                    badge.textContent = '5';
                    badge.hidden = false;
                }
            };

            badge.textContent = '5';
            badge.hidden = false;
            syncBadge();
        })();
    </script>
</body>

</html>