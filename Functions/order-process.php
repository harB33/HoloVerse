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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .info-page {
            background: linear-gradient(180deg, #fbfdff 0%, #eefbff 100%);
            min-height: 100vh;
        }

        .info-main {
            margin: 0 auto;
            max-width: 1120px;
            padding: 34px 24px 80px;
            position: relative;
            z-index: 1;
        }

        .page-intro {
            margin-bottom: 28px;
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
            letter-spacing: 0.12em;
            margin-bottom: 18px;
            padding: 8px 14px;
            text-transform: uppercase;
        }

        .page-intro h1 {
            font-size: clamp(2.6rem, 4.8vw, 4.8rem);
            letter-spacing: -0.05em;
            margin: 0 0 18px;
        }

        .page-intro p {
            color: #2f3e4e;
            font-size: 1.06rem;
            line-height: 1.7;
            margin: 0;
        }

        .timeline {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin: 36px 0 30px;
        }

        .step-card {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(49, 180, 207, 0.2);
            border-radius: 24px;
            box-shadow: 0 22px 32px rgba(16, 40, 68, 0.06);
            padding: 24px 20px;
        }

        .step-number {
            align-items: center;
            background: linear-gradient(135deg, #67d8f1, #1da3d8);
            border-radius: 18px;
            color: #fff;
            display: inline-flex;
            font-size: 1.3rem;
            font-weight: 800;
            height: 52px;
            justify-content: center;
            margin-bottom: 18px;
            width: 52px;
        }

        .step-card h2 {
            font-size: 1.5rem;
            margin: 0 0 12px;
        }

        .step-card p, .step-card li {
            color: #42566d;
            line-height: 1.7;
        }

        .step-card ul {
            margin: 0;
            padding-left: 18px;
        }

        .info-panel {
            align-items: stretch;
            display: grid;
            gap: 24px;
            grid-template-columns: 1.3fr 0.7fr;
        }

        .panel-box {
            background: #fff;
            border: 1px solid rgba(49, 180, 207, 0.2);
            border-radius: 24px;
            box-shadow: 0 22px 32px rgba(16, 40, 68, 0.05);
            padding: 26px 24px;
        }

        .panel-box h3 {
            font-size: 1.55rem;
            margin: 0 0 18px;
        }

        .check-list {
            display: grid;
            gap: 14px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .check-list li {
            background: rgba(49, 180, 207, 0.08);
            border-radius: 14px;
            color: #23374d;
            font-weight: 600;
            padding: 12px 14px;
        }

        .side-box {
            background: linear-gradient(180deg, #0f233f 0%, #0d6bab 100%);
            border-radius: 24px;
            color: #fff;
            padding: 28px 22px;
        }

        .side-box h3 {
            font-size: 1.5rem;
            margin: 0 0 16px;
        }

        .side-box p {
            color: rgba(255, 255, 255, 0.82);
            line-height: 1.7;
            margin: 0 0 18px;
        }

        .side-box a {
            color: #fff;
            font-weight: 800;
            text-decoration: none;
        }

        @media (max-width: 900px) {
            .timeline {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .info-panel {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .timeline {
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
                <a href="../index.php">Home</a>
                <a href="featured-items.php">Featured Items</a>
                <a href="order-process.php">Order Process</a>
                <a href="shipping-info.php">Shipping Info</a>
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
