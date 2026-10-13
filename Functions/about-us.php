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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .info-page {
            background: linear-gradient(180deg, #ffffff 0%, #f3fbff 100%);
            min-height: 100vh;
        }

        .info-main {
            margin: 0 auto;
            max-width: 1200px;
            padding: 34px 24px 80px;
            position: relative;
            z-index: 1;
        }

        .page-intro {
            margin-bottom: 26px;
            max-width: 760px;
        }

        .eyebrow {
            display: inline-block;
            background: rgba(49, 180, 207, 0.12);
            border: 1px solid rgba(36, 126, 202, 0.2);
            border-radius: 999px;
            color: #0a6cae;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.14em;
            margin-bottom: 18px;
            padding: 8px 14px;
            text-transform: uppercase;
        }

        .page-intro h1 {
            font-size: clamp(2.5rem, 5vw, 5rem);
            letter-spacing: -0.05em;
            line-height: 0.96;
            margin: 0 0 18px;
        }

        .page-intro p {
            color: #2f3e4e;
            font-size: 1.08rem;
            line-height: 1.7;
            margin: 0;
        }

        .content-grid {
            display: grid;
            gap: 24px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 32px 0 28px;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(49, 180, 207, 0.2);
            border-radius: 24px;
            box-shadow: 0 22px 40px rgba(15, 37, 71, 0.06);
            overflow: hidden;
            padding: 26px 22px;
        }

        .info-card .badge {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #67d8f1, #1da3d8);
            border-radius: 999px;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            margin-bottom: 16px;
            padding: 7px 12px;
            text-transform: uppercase;
        }

        .info-card h2 {
            font-size: 1.55rem;
            margin: 0 0 12px;
        }

        .info-card p,
        .info-card li {
            color: #42566d;
            font-size: 1rem;
            line-height: 1.7;
        }

        .info-card ul {
            margin: 18px 0 0;
            padding-left: 18px;
        }

        .feature-banner {
            align-items: center;
            background: linear-gradient(120deg, #101d35 0%, #0d6bab 55%, #31b4cf 100%);
            border-radius: 28px;
            color: #fff;
            display: grid;
            gap: 18px;
            grid-template-columns: 1.3fr 1fr;
            margin-top: 20px;
            padding: 32px 28px;
        }

        .feature-banner h2 {
            font-size: clamp(1.6rem, 2vw, 2.4rem);
            line-height: 1.1;
            margin: 0 0 12px;
        }

        .feature-banner p {
            color: rgba(255, 255, 255, 0.84);
            line-height: 1.7;
            margin: 0;
        }

        .stats {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 18px;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 18px;
            padding: 16px 14px;
            text-align: center;
        }

        .stat-box strong {
            display: block;
            font-size: 1.8rem;
            margin-bottom: 4px;
        }

        .stat-box span {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.82rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .feature-banner {
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
                <a class="entry-button" href="featured-items.php">Shop Now<span aria-hidden="true">&#8594;</span></a>
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
                    <span class="eyebrow" style="background: rgba(255,255,255,0.08); color: #e9faff; border-color: rgba(255,255,255,0.2);">Why fans choose us</span>
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
