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
    <title>Flash Sale | HoloVerse</title>
    <link rel="icon" type="image/png" href="../Assets/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800;900&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/tailwind.css">
</head>

<body>
    <div class="page-shell info-page flash-sale-page">
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
                <a href="flash_sale.php" aria-current="page">Flash Sales</a>
                <a href="collector_showcase.php">Collector Showcase</a>
                <a href="personalized.php">Personalized</a>
                <a href="about-us.php">About Us</a>
            </nav>

            <div class="header-actions">
                <a class="entry-button" href="login.php" aria-label="Log In"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></a>
            </div>
        </header>

        <main class="flash-sale-main">
            <div class="flash-status-bar" aria-label="Flash sale system status">
                <span><i aria-hidden="true"></i> Vault state: live (stage 04)</span>
                <span>Protocol v4.12: anti-bot hardware attestation active</span>
                <span class="flash-status-right"><span>◎ CF-turnstile: validated</span><span>◷ Latency: 18ms (Tokyo-HQ)</span></span>
            </div>

            <section class="flash-sale-heading" aria-labelledby="flash-sale-title">
                <div>
                    <div class="flash-heading-meta">
                        <span class="flash-mode-pill">Flash drop mode #7401</span>
                        <span>[01 per-household enforced]</span>
                    </div>
                    <h1 id="flash-sale-title">Hyper-drop vault // Live flash sale</h1>
                </div>
                <div class="flash-countdown">
                    <span class="flash-countdown-label"><span aria-hidden="true">◷</span> Allocation window closes in:</span>
                    <div class="flash-countdown-clock" aria-label="2 days, 14 hours, 0 minutes, 19 seconds remaining">
                        <span>02</span><b>:</b><span>14</span><b>:</b><span>00</span><b>:</b><small>19</small>
                    </div>
                </div>
            </section>

            <article class="flash-sale-card">
                <section class="flash-gallery" aria-label="Product gallery">
                    <div class="flash-product-image">
                        <img src="../Assets/suisei2.jpg" alt="Hoshimachi Suisei in her blue and white anniversary outfit">
                        <div class="flash-image-badges">
                            <span>Limited 300 units worldwide</span>
                            <span><b aria-hidden="true">✓</b> Verified osh(i) asset</span>
                        </div>
                        <div class="flash-image-caption">
                            <span>Scale: 1/4 deluxe resin</span>
                            <span>Sculptor: Studio Apex</span>
                            <span>Box cert: encrypted NFC</span>
                        </div>
                    </div>
                    <div class="flash-thumbnails" aria-label="More product images">
                        <button class="flash-thumbnail is-selected" type="button" data-gallery-src="../Assets/suisei2.jpg" data-gallery-alt="Hoshimachi Suisei in her blue and white anniversary outfit" aria-label="Show anniversary outfit image" aria-pressed="true">
                            <img src="../Assets/suisei2.jpg" alt="">
                        </button>
                        <button class="flash-thumbnail" type="button" data-gallery-src="../Assets/suisei.jpg" data-gallery-alt="Suisei artwork detail" aria-label="Show Suisei artwork detail" aria-pressed="false">
                            <img src="../Assets/suisei.jpg" alt="">
                        </button>
                        <button class="flash-thumbnail" type="button" data-gallery-src="../Assets/hololive.png" data-gallery-alt="hololive production logo" aria-label="Show hololive production logo" aria-pressed="false">
                            <img src="../Assets/hololive.png" alt="">
                        </button>
                        <button class="flash-thumbnail" type="button" data-gallery-src="../Assets/logo.png" data-gallery-alt="HoloVerse product mark" aria-label="Show HoloVerse product mark" aria-pressed="false">
                            <img src="../Assets/logo.png" alt="">
                        </button>
                    </div>
                </section>

                <section class="flash-product-details" aria-labelledby="flash-product-title">
                    <div class="flash-breadcrumb">Hololive JP / Gamers Generation / Series Special Edition</div>
                    <h2 id="flash-product-title">Hoshimachi Suisei 1/4 scale anniversary edition</h2>
                    <p class="flash-product-description">A limited collector release inspired by Suisei’s signature stage look, with a detailed display base and individually numbered authenticity card.</p>

                    <div class="flash-price-panel">
                        <div class="flash-price-line">
                            <strong>$224.00</strong>
                            <del>$280.00</del>
                            <span>20% flash discount</span>
                        </div>
                        <small>+1,200 holopoints earned</small>
                    </div>

                    <div class="flash-stock-panel">
                        <div class="flash-stock-top">
                            <span><b aria-hidden="true">♨</b> Critical stock: only 22 units left</span>
                            <span>278 / 300 claimed (92.6%)</span>
                        </div>
                        <div class="flash-stock-track" role="progressbar" aria-label="Units claimed" aria-valuenow="93" aria-valuemin="0" aria-valuemax="100"><span></span></div>
                        <div class="flash-stock-bottom">
                            <span>Reservation grace: 300 seconds</span>
                            <span>Est. exhaustion: &lt; 8 minutes</span>
                        </div>
                    </div>

                    <a class="flash-reserve-button" href="pre_order.php">
                        <span aria-hidden="true">♙</span> Enter instant allocation lock <span aria-hidden="true">→</span>
                    </a>
                    <p class="flash-checkout-note"><span aria-hidden="true">ϟ</span> 5-minute checkout hold initiates instantly upon lock. Prevents cart sniping.</p>
                </section>
            </article>
        </main>
    </div>
    <script>
        const galleryImage = document.querySelector(".flash-product-image > img");
        const galleryThumbnails = document.querySelectorAll(".flash-thumbnail");

        galleryThumbnails.forEach((thumbnail) => {
            thumbnail.addEventListener("click", () => {
                galleryImage.src = thumbnail.dataset.gallerySrc;
                galleryImage.alt = thumbnail.dataset.galleryAlt;

                galleryThumbnails.forEach((item) => {
                    const isSelected = item === thumbnail;
                    item.classList.toggle("is-selected", isSelected);
                    item.setAttribute("aria-pressed", String(isSelected));
                });
            });
        });
    </script>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/cart-button.php';
    ?>
</body>

</html>
