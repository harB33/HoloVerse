<?php
require_once __DIR__ . '/../config/dbconnect.php';
$pageTitle = 'Flash Sale | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell info-page flash-sale-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>

        <?php
        $activePage = 'flash_sale';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

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
                        <img src="../assets/images/suisei2.jpg" alt="Hoshimachi Suisei in her blue and white anniversary outfit">
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
                        <button class="flash-thumbnail is-selected" type="button" data-gallery-src="../assets/images/suisei2.jpg" data-gallery-alt="Hoshimachi Suisei in her blue and white anniversary outfit" aria-label="Show anniversary outfit image" aria-pressed="true">
                            <img src="../assets/images/suisei2.jpg" alt="">
                        </button>
                        <button class="flash-thumbnail" type="button" data-gallery-src="../assets/images/suisei.jpg" data-gallery-alt="Suisei artwork detail" aria-label="Show Suisei artwork detail" aria-pressed="false">
                            <img src="../assets/images/suisei.jpg" alt="">
                        </button>
                        <button class="flash-thumbnail" type="button" data-gallery-src="../assets/images/hololive.png" data-gallery-alt="hololive production logo" aria-label="Show hololive production logo" aria-pressed="false">
                            <img src="../assets/images/hololive.png" alt="">
                        </button>
                        <button class="flash-thumbnail" type="button" data-gallery-src="../assets/images/logo.png" data-gallery-alt="HoloVerse product mark" aria-label="Show HoloVerse product mark" aria-pressed="false">
                            <img src="../assets/images/logo.png" alt="">
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

            <section class="flash-telemetry" aria-labelledby="flash-telemetry-title">
                <div class="flash-telemetry-heading">
                    <h2 id="flash-telemetry-title"><span aria-hidden="true"></span> Active queue &amp; anti-snipe telemetry engine</h2>
                    <span>System clock: <?= date('Y-m-d\TH:i:sP') ?></span>
                </div>
                <div class="flash-telemetry-grid">
                    <article class="flash-telemetry-card">
                        <div class="flash-telemetry-label"><span>Gatekeeper pass</span><span aria-hidden="true">♢</span></div>
                        <strong>Passed / verified</strong>
                        <p>Hardware attestation cleared. Zero captcha required during lock.</p>
                        <span class="flash-telemetry-badge">Hardware encrypted</span>
                    </article>

                    <article class="flash-telemetry-card">
                        <div class="flash-telemetry-label"><span>Your cart lock monitor</span><span class="flash-telemetry-icon-red" aria-hidden="true">◷</span></div>
                        <strong class="flash-telemetry-countdown">01:59 <small>remaining</small></strong>
                        <p>Active cart reservation lock protects against out-of-stock slips.</p>
                        <div class="flash-telemetry-progress" role="progressbar" aria-label="Cart lock time remaining" aria-valuenow="66" aria-valuemin="0" aria-valuemax="100"><span></span></div>
                    </article>

                    <article class="flash-telemetry-card">
                        <div class="flash-telemetry-label"><span>Standby queue position</span><span aria-hidden="true">♙</span></div>
                        <strong>#14 <small>est. wait: 12 sec</small></strong>
                        <p>Dynamic pool reallocates unsold slots in real time.</p>
                        <span class="flash-telemetry-footnote"><i aria-hidden="true"></i> 42 releases in past 60s</span>
                    </article>

                    <article class="flash-telemetry-card">
                        <div class="flash-telemetry-label"><span>Fulfillment SLA</span><span aria-hidden="true">♢</span></div>
                        <strong>100% secured</strong>
                        <p>Allocation locked once button clicked. No oversell guarantee.</p>
                        <span class="flash-telemetry-badge">ISO 27001 vault</span>
                    </article>
                </div>
            </section>
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
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
</body>

</html>
