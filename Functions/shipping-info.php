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
    <title>Shipping Info | hololive merchandise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .info-page {
            background: linear-gradient(180deg, #ffffff 0%, #ecfbff 100%);
            min-height: 100vh;
        }

        .info-main {
            margin: 0 auto;
            max-width: 1160px;
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
            font-size: clamp(2.5rem, 4.8vw, 4.8rem);
            letter-spacing: -0.05em;
            margin: 0 0 18px;
        }

        .page-intro p {
            color: #2f3e4e;
            font-size: 1.08rem;
            line-height: 1.7;
            margin: 0;
        }

        .shipping-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 32px 0;
        }

        .shipping-card {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(49, 180, 207, 0.2);
            border-radius: 24px;
            box-shadow: 0 24px 32px rgba(15, 36, 65, 0.05);
            padding: 26px 22px;
        }

        .shipping-card h2 {
            font-size: 1.7rem;
            margin: 0 0 12px;
        }

        .shipping-card p, .shipping-card li {
            color: #42566d;
            line-height: 1.7;
        }

        .shipping-card ul {
            margin: 14px 0 0;
            padding-left: 18px;
        }

        .rate {
            background: linear-gradient(135deg, #67d8f1, #1da3d8);
            border-radius: 999px;
            color: white;
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            margin-bottom: 16px;
            padding: 8px 12px;
            text-transform: uppercase;
        }

        .detail-layout {
            display: grid;
            gap: 24px;
            grid-template-columns: 1.2fr 0.8fr;
            margin-top: 22px;
        }

        .panel-box {
            background: #fff;
            border: 1px solid rgba(49, 180, 207, 0.2);
            border-radius: 24px;
            box-shadow: 0 22px 32px rgba(15, 36, 65, 0.05);
            padding: 26px 24px;
        }

        .panel-box h3 {
            font-size: 1.5rem;
            margin: 0 0 18px;
        }

        .faq-list {
            display: grid;
            gap: 14px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .faq-list li {
            background: rgba(49, 180, 207, 0.08);
            border-radius: 14px;
            color: #23374d;
            font-weight: 600;
            padding: 12px 14px;
        }

        .notice {
            background: linear-gradient(180deg, #121e3d 0%, #0f719f 100%);
            border-radius: 24px;
            color: #fff;
            padding: 28px 22px;
        }

        .notice h3 {
            font-size: 1.5rem;
            margin: 0 0 12px;
        }

        .notice p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.7;
            margin: 0;
        }

        @media (max-width: 900px) {
            .shipping-grid {
                grid-template-columns: 1fr;
            }

            .detail-layout {
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
                <span class="eyebrow">Shipping guide</span>
                <h1>Delivery details made easy.</h1>
                <p>Choose the option that suits your timeline and region. We ship carefully packed orders with transparent handling times and tracking updates for every step.</p>
            </section>

            <section class="shipping-grid">
                <article class="shipping-card">
                    <div class="rate">Standard</div>
                    <h2>Regular shipping</h2>
                    <p>Best for everyday orders and flexible delivery windows.</p>
                    <ul>
                        <li>3–7 business days</li>
                        <li>Tracked package</li>
                        <li>Cost varies by region</li>
                    </ul>
                </article>

                <article class="shipping-card">
                    <div class="rate">Express</div>
                    <h2>Priority delivery</h2>
                    <p>Fast shipping for event drops and collector releases.</p>
                    <ul>
                        <li>2–4 business days</li>
                        <li>Priority handling</li>
                        <li>Enhanced tracking updates</li>
                    </ul>
                </article>

                <article class="shipping-card">
                    <div class="rate">Global</div>
                    <h2>International</h2>
                    <p>Available for select regions with customs and import information included.</p>
                    <ul>
                        <li>5–12 business days</li>
                        <li>Customs fees may apply</li>
                        <li>Regional carrier selection</li>
                    </ul>
                </article>
            </section>

            <section class="detail-layout">
                <div class="panel-box">
                    <h3>Frequently asked questions</h3>
                    <ul class="faq-list">
                        <li>When do you ship orders? Orders are packed within 1–2 business days after confirmation.</li>
                        <li>Do you offer tracking? Yes, every shipment includes status updates and a tracking number.</li>
                        <li>Can I change my shipping address? Address updates can be requested before dispatch is finalized.</li>
                        <li>Are customs fees included? International duties and taxes may be billed separately depending on destination rules.</li>
                    </ul>
                </div>

                <aside class="notice">
                    <h3>Returns & exchanges</h3>
                    <p>We accept returns for damaged or incorrect items within the review window. Please contact support with your order number and product details.</p>
                </aside>
            </section>
        </main>
    </div>
</body>

</html>
