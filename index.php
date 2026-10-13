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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800;900&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Assets/tailwind.css">
</head>

<body>
    <main class="page-shell">
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
            <a class="brand" href="#top" aria-label="hololive home">
                <img class="brand-logo" src="Assets/hololive.png" alt="hololive production">
            </a>

            <nav class="main-nav" aria-label="Main navigation">
                <a href="#new-arrivals">New Arrivals</a>
                <a href="../Functions/featured-items.php">Featured Items</a>
                <a href="../Functions/order-process.php">Order Process</a>
                <a href="../Functions/shipping-info.php">Shipping Info</a>
                <a href="../Functions/about-us.php">About Us</a>
            </nav>

            <div class="header-actions">
                <a class="entry-button" href="#featured">Sign Up<span aria-hidden="true">&#8594;</span></a>

            </div>
        </header>

        <div id="top" class="hero">
            <section class="hero-copy" id="new-arrivals">
                <h1>hololive<br>production<br>merchandise</h1>
                <p>Exclusive Apparel, Figures, and Collectibles!</p>
                <p>Limited-Edition items for all hololive fans.</p>
            </section>

            <div class="hero-symbol">
                <img src="Assets/logo.png" alt="hololive logo">
            </div>
        </div>
        <section class="limited-drops" id="featured" aria-labelledby="limited-drops-title">
            <div class="drops-heading">
                <div>
                    <span class="drops-eyebrow">Tier 1 priority pre-orders</span>
                    <h2 id="limited-drops-title">Active Limited Drops &amp; Statues</h2>
                </div>
                <a class="drops-view-all" href="Functions/pre_order.php">Explore pre-orders <span aria-hidden="true">&#8594;</span></a>
            </div>

            <div class="drops-grid">
                <article class="drop-card drop-card-korone">
                    <div class="drop-art">
                        <div class="drop-art-labels"><span class="drop-status">Closes in 18h</span><span class="drop-edition">1/4 scale</span></div>
                        <div class="drop-character">Korone</div>
                        <p class="drop-art-caption">Bakuretsu God Box Ed.</p>
                        <span class="drop-art-tag">With Acrylic Diorama</span>
                    </div>
                    <div class="drop-details">
                        <span class="drop-category">Hololive Gamers</span>
                        <h3>Inugami Korone 1/4 Scale “Doog Champion” Figure</h3>
                        <p class="drop-description">Batch ETA: November 2026. Includes signed cover card.</p>
                        <div class="drop-allocation"><span>Allocation claimed</span><strong>88% (440/500)</strong></div>
                        <div class="drop-progress" role="progressbar" aria-label="Korone allocation claimed" aria-valuenow="88" aria-valuemin="0" aria-valuemax="100"><span style="width: 88%"></span></div>
                        <div class="drop-purchase"><div><span class="drop-price-label">Factory deposit</span><strong>$289.00</strong></div><a href="Functions/pre_order.php">Reserve slot</a></div>
                    </div>
                </article>

                <article class="drop-card drop-card-suisei">
                    <div class="drop-art">
                        <div class="drop-art-labels"><span class="drop-status">New arrival</span><span class="drop-edition">Memorial box</span></div>
                        <div class="drop-character">Suisei</div>
                        <p class="drop-art-caption">Stellar into the Galaxy</p>
                        <span class="drop-art-tag">Master 180g Vinyl + Beret</span>
                    </div>
                    <div class="drop-details">
                        <span class="drop-category">Hololive 0th Gen</span>
                        <h3>Hoshimachi Suisei 5th Anniversary Memorial Box</h3>
                        <p class="drop-description">Complete vinyl set, bespoke star pin, and replica beret.</p>
                        <div class="drop-allocation"><span>Allocation claimed</span><strong>64% (640/1000)</strong></div>
                        <div class="drop-progress" role="progressbar" aria-label="Suisei allocation claimed" aria-valuenow="64" aria-valuemin="0" aria-valuemax="100"><span style="width: 64%"></span></div>
                        <div class="drop-purchase"><div><span class="drop-price-label">Total price</span><strong>$175.00</strong></div><a href="Functions/pre_order.php">Reserve slot</a></div>
                    </div>
                </article>

                <article class="drop-card drop-card-gura">
                    <div class="drop-art">
                        <div class="drop-art-labels"><span class="drop-status">High demand</span><span class="drop-edition">1/7 scale</span></div>
                        <div class="drop-character">Gura</div>
                        <p class="drop-art-caption">Atlantis Hydro Trident Ed.</p>
                        <span class="drop-art-tag">Bloop Mascot Companion</span>
                    </div>
                    <div class="drop-details">
                        <span class="drop-category">Hololive English -Myth-</span>
                        <h3>Gawr Gura 1/7 Scale “Apex Predator” Figure</h3>
                        <p class="drop-description">Sculpted by Design COCO. Removable hoodie feature.</p>
                        <div class="drop-allocation"><span>Allocation claimed</span><strong>94% (940/1000)</strong></div>
                        <div class="drop-progress" role="progressbar" aria-label="Gura allocation claimed" aria-valuenow="94" aria-valuemin="0" aria-valuemax="100"><span style="width: 94%"></span></div>
                        <div class="drop-purchase"><div><span class="drop-price-label">Factory deposit</span><strong>$215.00</strong></div><a href="Functions/pre_order.php">Reserve slot</a></div>
                    </div>
                </article>

                <article class="drop-card drop-card-calliope">
                    <div class="drop-art">
                        <div class="drop-art-labels"><span class="drop-status">Ready to ship</span><span class="drop-edition">Streetwear</span></div>
                        <div class="drop-character">Calliope</div>
                        <p class="drop-art-caption">Reaper Sukajan Bomber</p>
                        <span class="drop-art-tag">Embroidered Satin</span>
                    </div>
                    <div class="drop-details">
                        <span class="drop-category">Hololive English -Myth-</span>
                        <h3>Mori Calliope “SINDERELLA” Tour Souvenir Sukajan</h3>
                        <p class="drop-description">Reversible high-thread embroidery. Limited run of 750.</p>
                        <div class="drop-allocation"><span>Allocation claimed</span><strong>45% (337/750)</strong></div>
                        <div class="drop-progress" role="progressbar" aria-label="Calliope allocation claimed" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"><span style="width: 45%"></span></div>
                        <div class="drop-purchase"><div><span class="drop-price-label">Retail price</span><strong>$190.00</strong></div><a href="Functions/pre_order.php">Buy now</a></div>
                    </div>
                </article>
            </div>
        </section>
        <section class="explore" aria-labelledby="explore-title">
            <div class="explore-inner">
                <header class="explore-header">
                    <span class="explore-eyebrow">Talent Vault Directory</span>
                    <h2 id="explore-title">Explore Merchandise by Oshi</h2>
                    <p>Filter official apparel, plushies, voice packs, and figures directly tied to your favorite talent.</p>
                </header>

                <div class="explore-filters" role="group" aria-label="Filter talents by branch">
                    <button class="explore-filter" type="button" data-explore-filter="all" aria-pressed="true">All Branches (78 Talents)</button>
                    <button class="explore-filter" type="button" data-explore-filter="jp" aria-pressed="false">Hololive JP (Gen 0-6, Gamers)</button>
                    <button class="explore-filter" type="button" data-explore-filter="en" aria-pressed="false">Hololive English (-Myth-, Promise, Advent, Justice)</button>
                    <button class="explore-filter" type="button" data-explore-filter="id" aria-pressed="false">Hololive Indonesia (Gen 1-3)</button>
                    <button class="explore-filter" type="button" data-explore-filter="dev-is" aria-pressed="false">DEV_IS (ReGLOSS, FLOW GLOW)</button>
                </div>

                <div class="talent-grid" aria-live="polite">
                    <article class="talent-card talent-gura" data-branch="en">
                        <span class="talent-avatar" aria-hidden="true">GW</span>
                        <h3>Gawr Gura</h3>
                        <p>14 Active Items</p>
                    </article>
                    <article class="talent-card talent-korone" data-branch="jp">
                        <span class="talent-avatar" aria-hidden="true">IK</span>
                        <h3>Inugami Korone</h3>
                        <p>9 Active Items</p>
                    </article>
                    <article class="talent-card talent-suisei" data-branch="jp">
                        <span class="talent-avatar" aria-hidden="true">HS</span>
                        <h3>Hoshimachi Suisei</h3>
                        <p>22 Active Items</p>
                    </article>
                    <article class="talent-card talent-calliope" data-branch="en">
                        <span class="talent-avatar" aria-hidden="true">MC</span>
                        <h3>Mori Calliope</h3>
                        <p>11 Active Items</p>
                    </article>
                    <article class="talent-card talent-pekora" data-branch="jp">
                        <span class="talent-avatar" aria-hidden="true">UP</span>
                        <h3>Usada Pekora</h3>
                        <p>18 Active Items</p>
                    </article>
                    <article class="talent-card talent-fuwamoco" data-branch="en">
                        <span class="talent-avatar" aria-hidden="true">FW</span>
                        <h3>FUWAMOCO</h3>
                        <p>8 Active Items</p>
                    </article>
                </div>
                <p class="explore-empty hidden" role="status">No featured talents in this branch yet.</p>
            </div>
        </section>
        <footer class="footer">
            <section class="radar-signup" aria-labelledby="radar-title">
                <span class="radar-eyebrow">Holographic restock radar</span>
                <h2 id="radar-title">Never Miss a 15-Minute Sold-Out Drop</h2>
                <p>Sign up to receive 15-minute SMS &amp; email alerts prior to secret drops, anniversary commemoration<br class="radar-desktop-break"> merchandise releases, and warehouse clearance extras.</p>
                <form class="radar-form" id="radar-form">
                    <label class="sr-only" for="radar-email">Collector email address</label>
                    <input id="radar-email" name="email" type="email" autocomplete="email" placeholder="Enter your collector email address..." required>
                    <button type="submit">Join Radar Free</button>
                </form>
                <fieldset class="radar-preferences">
                    <legend class="sr-only">Choose drop alert categories</legend>
                    <label><input type="checkbox" name="branches[]" value="jp" checked> Hololive JP Drops</label>
                    <label><input type="checkbox" name="branches[]" value="en" checked> Hololive EN Drops</label>
                    <label><input type="checkbox" name="branches[]" value="dev-is"> DEV_IS Drops</label>
                </fieldset>
                <p class="radar-feedback" id="radar-feedback" role="status" aria-live="polite"></p>
            </section>

            <div class="footer-main">
                <div class="footer-about">
                    <a class="footer-brand" href="#top" aria-label="HoloVerse Store home">
                        <img src="Assets/logo.png" alt="">
                        <span>holo<span>verse</span></span>
                        <small>Store</small>
                    </a>
                    <p>The dedicated international storefront for official Hololive production collectible merchandise. Officially authorized in partnership with COVER Corporation.</p>
                    <span class="footer-hubs">Tokyo&nbsp; | &nbsp;Los Angeles&nbsp; | &nbsp;Singapore Fulfillment Hubs</span>
                </div>

                <nav class="footer-links" aria-label="Merchandise links">
                    <h3>Merchandise</h3>
                    <a href="Functions/featured-items.php">Scale Figures (1/4, 1/7)</a>
                    <a href="Functions/featured-items.php">Commemorative Box Sets</a>
                    <a href="Functions/featured-items.php">Apparel &amp; Sukajan</a>
                    <a href="Functions/featured-items.php">Acrylic Stands &amp; Keychains</a>
                    <a href="Functions/featured-items.php">Official Plushies &amp; Mochiduru</a>
                </nav>

                <nav class="footer-links" aria-label="Customer care links">
                    <h3>Customer Care</h3>
                    <a href="Functions/pre_order.php">Pre-Order Batch ETA Tracker</a>
                    <a href="Functions/shipping-info.php">Shipping Rates &amp; Customs Info</a>
                    <a href="Functions/shipping-info.php">Mint Condition Box Guarantee</a>
                    <a href="Functions/order-process.php">Anti-Scalper Verification</a>
                    <a href="Functions/order-process.php">NFC Chip Mobile Tap Support</a>
                </nav>

                <nav class="footer-links" aria-label="Corporate links">
                    <h3>COVER Corp Portal</h3>
                    <a href="Functions/about-us.php">Talent Licensing Guidelines</a>
                    <a href="Functions/about-us.php">Authenticity Guarantee</a>
                    <a href="Functions/about-us.php">ISO 27001 Security</a>
                    <a href="Functions/about-us.php">Privacy Policy &amp; Terms</a>
                    <a href="Functions/about-us.php">Wholesale Partner Inquiries</a>
                </nav>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2016-2026 COVER Corp. / HoloVerse Store. All rights reserved.</p>
                <nav aria-label="Legal links">
                    <a href="Functions/about-us.php">Terms of Service</a>
                    <span aria-hidden="true">&bull;</span>
                    <a href="Functions/about-us.php">Privacy Notice</a>
                    <span aria-hidden="true">&bull;</span>
                    <a href="Functions/about-us.php">Cookie Preferences</a>
                </nav>
            </div>
        </footer>
    </main>
    <script>
        const exploreFilters = document.querySelectorAll("[data-explore-filter]");
        const talentCards = document.querySelectorAll(".talent-card");
        const exploreEmpty = document.querySelector(".explore-empty");

        exploreFilters.forEach((filter) => {
            filter.addEventListener("click", () => {
                const selectedBranch = filter.dataset.exploreFilter;
                let visibleTalents = 0;

                exploreFilters.forEach((button) => {
                    button.setAttribute("aria-pressed", String(button === filter));
                });

                talentCards.forEach((card) => {
                    const isVisible = selectedBranch === "all" || card.dataset.branch === selectedBranch;
                    card.classList.toggle("hidden", !isVisible);
                    visibleTalents += Number(isVisible);
                });

                exploreEmpty.classList.toggle("hidden", visibleTalents > 0);
            });
        });

        document.querySelector("#radar-form").addEventListener("submit", (event) => {
            event.preventDefault();
            document.querySelector("#radar-feedback").textContent = "Alert signup is a preview; email notifications are not connected yet.";
        });
    </script>
</body>

</html>