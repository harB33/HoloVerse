<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Order | hololive merchandise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .preorder-page {
            background: linear-gradient(180deg, #fbfdff 0%, #edf9ff 100%);
            min-height: 100vh;
        }

        .preorder-main {
            margin: 0 auto;
            max-width: 1180px;
            padding: 34px 24px 80px;
            position: relative;
            z-index: 1;
        }

        .preorder-intro {
            margin-bottom: 30px;
            max-width: 820px;
        }

        .preorder-eyebrow {
            align-items: center;
            background: rgba(49, 180, 207, 0.12);
            border: 1px solid rgba(36, 126, 202, 0.2);
            border-radius: 999px;
            color: #0a6cae;
            display: inline-flex;
            font-size: 12px;
            font-weight: 800;
            gap: 8px;
            letter-spacing: 0.12em;
            margin-bottom: 18px;
            padding: 8px 14px;
            text-transform: uppercase;
        }

        .preorder-eyebrow::before {
            background: #31b4cf;
            border-radius: 50%;
            content: "";
            height: 8px;
            width: 8px;
        }

        .preorder-intro h1 {
            font-size: clamp(2.6rem, 5vw, 5rem);
            letter-spacing: -0.05em;
            line-height: 0.98;
            margin: 0 0 18px;
        }

        .preorder-intro p {
            color: #2f3e4e;
            font-size: 1.08rem;
            line-height: 1.7;
            margin: 0;
        }

        .preorder-grid {
            display: grid;
            gap: 26px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .preorder-card {
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(49, 180, 207, 0.2);
            border-radius: 26px;
            box-shadow: 0 24px 40px rgba(17, 41, 73, 0.06);
            overflow: hidden;
        }

        .preorder-art {
            align-items: center;
            background: linear-gradient(135deg, #d8f8ff 0%, #bae8ff 38%, #7fcff0 100%);
            display: flex;
            justify-content: center;
            min-height: 220px;
            overflow: hidden;
            position: relative;
        }

        .preorder-art::before {
            background: rgba(12, 77, 120, 0.12);
            border-radius: 32px;
            content: "";
            height: 120px;
            left: 22px;
            position: absolute;
            right: 22px;
            transform: rotate(-8deg);
        }

        .preorder-art::after {
            border: 1px solid rgba(255, 255, 255, 0.55);
            border-radius: 50%;
            content: "";
            height: 172px;
            position: absolute;
            width: 172px;
        }

        .preorder-art-sky {
            background: linear-gradient(135deg, #d3e4ff 0%, #b7d0ff 35%, #8ab0ff 100%);
        }

        .preorder-art-coral {
            background: linear-gradient(135deg, #ffe9d3 0%, #ffd7b0 35%, #f6b673 100%);
        }

        .preorder-art-lilac {
            background: linear-gradient(135deg, #f1e5ff 0%, #d9c6ff 40%, #ac9bf4 100%);
        }

        .preorder-badge {
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
            z-index: 1;
        }

        .preorder-symbol {
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(9, 79, 130, 0.08);
            border-radius: 26px;
            box-shadow: 0 16px 26px rgba(15, 69, 107, 0.12);
            color: #0d5d96;
            display: flex;
            font-size: 2.3rem;
            font-weight: 900;
            height: 92px;
            justify-content: center;
            letter-spacing: -0.06em;
            position: relative;
            width: 92px;
            z-index: 1;
        }

        .preorder-body {
            padding: 22px 20px 24px;
        }

        .preorder-meta {
            color: #0d6bab;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .preorder-card h2 {
            font-size: 1.55rem;
            line-height: 1.2;
            margin: 10px 0 12px;
        }

        .preorder-card p {
            color: #42566d;
            line-height: 1.65;
            margin: 0 0 18px;
        }

        .preorder-shipping {
            border-top: 1px solid rgba(49, 180, 207, 0.16);
            color: #42566d;
            font-size: 0.85rem;
            margin-top: 18px;
            padding-top: 14px;
        }

        .preorder-footer {
            align-items: center;
            display: flex;
            gap: 12px;
            justify-content: space-between;
        }

        .preorder-price {
            color: #0f213d;
            font-size: 1.45rem;
            font-weight: 800;
        }

        .preorder-button {
            background: linear-gradient(135deg, #67d8f1, #1da3d8);
            border-radius: 999px;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            padding: 10px 14px;
            text-align: center;
            text-decoration: none;
            text-transform: uppercase;
        }

        .preorder-guide {
            align-items: center;
            background: linear-gradient(120deg, #101d35 0%, #0d6bab 58%, #31b4cf 100%);
            border-radius: 28px;
            color: #fff;
            display: grid;
            gap: 24px;
            grid-template-columns: 1fr auto;
            margin-top: 32px;
            padding: 30px;
        }

        .preorder-guide h2 {
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            margin: 0 0 8px;
        }

        .preorder-guide p {
            color: rgba(255, 255, 255, 0.84);
            line-height: 1.65;
            margin: 0;
            max-width: 650px;
        }

        .preorder-guide a {
            background: #fff;
            border-radius: 999px;
            color: #0d6bab;
            font-size: 0.82rem;
            font-weight: 800;
            padding: 12px 18px;
            text-decoration: none;
            white-space: nowrap;
        }

        @media (max-width: 920px) {
            .preorder-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .preorder-card:last-child {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 640px) {
            .preorder-grid {
                grid-template-columns: 1fr;
            }

            .preorder-card:last-child {
                grid-column: auto;
            }

            .preorder-guide {
                grid-template-columns: 1fr;
                padding: 24px;
            }

            .preorder-guide a {
                justify-self: start;
            }
        }
    </style>
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
            <a class="brand" href="../index.php" aria-label="hololive home">
                <img class="brand-logo" src="../Assets/hololive.png" alt="hololive production">
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
