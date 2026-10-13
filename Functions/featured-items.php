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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .info-page {
            background: linear-gradient(180deg, #fbfdff 0%, #edf9ff 100%);
            min-height: 100vh;
        }

        .info-main {
            margin: 0 auto;
            max-width: 1180px;
            padding: 34px 24px 80px;
            position: relative;
            z-index: 1;
        }

        .page-intro {
            margin-bottom: 30px;
            max-width: 820px;
        }

        .eyebrow {
            display: inline-block;
            background: rgba(49, 180, 207, 0.12);
            border: 1px solid rgba(36, 126, 202, 0.2);
            border-radius: 999px;
            color: #0a6cae;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            margin-bottom: 18px;
            padding: 8px 14px;
            text-transform: uppercase;
        }

        .page-intro h1 {
            font-size: clamp(2.6rem, 5vw, 5rem);
            letter-spacing: -0.05em;
            line-height: 0.98;
            margin: 0 0 18px;
        }

        .page-intro p {
            color: #2f3e4e;
            font-size: 1.08rem;
            line-height: 1.7;
            margin: 0;
        }

        .product-grid {
            display: grid;
            gap: 26px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .product-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(49, 180, 207, 0.2);
            border-radius: 26px;
            box-shadow: 0 24px 40px rgba(17, 41, 73, 0.06);
            overflow: hidden;
        }

        .product-thumb {
            align-items: center;
            background: linear-gradient(135deg, #d8f8ff 0%, #bae8ff 38%, #7fcff0 100%);
            display: flex;
            justify-content: center;
            min-height: 220px;
            position: relative;
        }

        .product-thumb::before {
            background: rgba(12, 77, 120, 0.12);
            border-radius: 32px;
            content: "";
            height: 120px;
            left: 22px;
            position: absolute;
            width: calc(100% - 44px);
        }

        .product-badge {
            background: linear-gradient(135deg, #67d8f1, #1da3d8);
            border-radius: 999px;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            padding: 8px 12px;
            position: absolute;
            right: 18px;
            text-transform: uppercase;
            top: 18px;
        }

        .product-symbol {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(9, 79, 130, 0.08);
            border-radius: 22px;
            color: #0d5d96;
            font-size: 2.3rem;
            font-weight: 900;
            height: 92px;
            letter-spacing: -0.06em;
            line-height: 92px;
            position: relative;
            text-align: center;
            width: 92px;
            z-index: 1;
        }

        .product-body {
            padding: 22px 20px 24px;
        }

        .product-meta {
            color: #0d6bab;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .product-card h2 {
            font-size: 1.7rem;
            margin: 10px 0 12px;
        }

        .product-card p {
            color: #42566d;
            line-height: 1.65;
            margin: 0 0 18px;
        }

        .product-footer {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        .price {
            color: #0f213d;
            font-size: 1.45rem;
            font-weight: 800;
        }

        .shop-button {
            background: linear-gradient(135deg, #67d8f1, #1da3d8);
            border-radius: 999px;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            padding: 10px 14px;
            text-decoration: none;
            text-transform: uppercase;
        }

        @media (max-width: 920px) {
            .product-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 640px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="page-shell info-page">
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
                <a class="entry-button" href="shipping-info.php">Order Guide<span aria-hidden="true">&#8594;</span></a>
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
                    <div class="product-thumb" style="background: linear-gradient(135deg, #d3e4ff 0%, #b7d0ff 35%, #8ab0ff 100%);">
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
                    <div class="product-thumb" style="background: linear-gradient(135deg, #ffe9d3 0%, #ffd7b0 35%, #f6b673 100%);">
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
