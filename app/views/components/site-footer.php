<?php
$siteRoot = $siteRoot ?? '';
?>
<footer class="footer site-footer">
    <div class="footer-main landing-shell">
        <div class="footer-brand-column">
            <a class="footer-brand" href="<?= store_escape($siteRoot . 'home#top') ?>" aria-label="HoloVerse Store home">
                <img src="<?= store_escape($siteRoot . 'assets/images/logo.png') ?>" alt="">
                <span>holo<span>verse</span></span>
                <small>Official Shop</small>
            </a>
            <p class="footer-tagline">Authentic apparel, plushies, scale figures, and collectibles from hololive production, delivered to fans around the world.</p>
            <span class="footer-hubs">Tokyo &bull; Los Angeles &bull; Manila &bull; Singapore Fulfillment Hubs</span>
        </div>

        <div>
            <h3>Shop</h3>
            <a href="<?= store_escape($siteRoot . 'products') ?>">New arrivals</a>
            <a href="<?= store_escape($siteRoot . 'flash-sale') ?>">Flash drop</a>
            <a href="<?= store_escape($siteRoot . 'pre_order') ?>">Pre-orders</a>
            <a href="<?= store_escape($siteRoot . 'products/category/plush') ?>">Plushies</a>
            <a href="<?= store_escape($siteRoot . 'products/category/apparel') ?>">Apparel</a>
            <a href="<?= store_escape($siteRoot . 'products/category/acrylic') ?>">Acrylics</a>
        </div>

        <div>
            <h3>Customer care</h3>
            <a href="<?= store_escape($siteRoot . 'pre_order') ?>">Pre-Order Batch ETA Tracker</a>
            <a href="<?= store_escape($siteRoot . 'personalized') ?>">International shipping &amp; customs</a>
            <a href="<?= store_escape($siteRoot . 'collector-showcase') ?>">Collector Showcase</a>
            <a href="<?= store_escape($siteRoot . 'purchase') ?>">My Purchases</a>
            <a href="<?= store_escape($siteRoot . 'cart') ?>">Shopping Bag</a>
        </div>

        <div>
            <h3>Official &amp; info</h3>
            <a href="<?= store_escape($siteRoot . 'talents') ?>">Talent directory</a>
            <a href="<?= store_escape($siteRoot . 'about-us') ?>">About HoloVerse</a>
            <a href="https://hololive.hololivepro.com/en/" target="_blank" rel="noreferrer">hololive production</a>
            <a href="https://shop.hololivepro.com/en/" target="_blank" rel="noreferrer">Official hololive shop</a>
        </div>
    </div>

    <div class="footer-bottom landing-shell">
        <p>&copy; 2016-2026 COVER Corp. / HoloVerse Store. Official artwork copyright COVER Corp.</p>
        <nav aria-label="Legal links">
            <a href="<?= store_escape($siteRoot . 'about-us') ?>">Terms of Service</a>
            <span aria-hidden="true">&bull;</span>
            <a href="<?= store_escape($siteRoot . 'about-us') ?>">Privacy Notice</a>
            <span aria-hidden="true">&bull;</span>
            <a href="<?= store_escape($siteRoot . 'about-us') ?>">Authenticity Guarantee</a>
        </nav>
    </div>
</footer>
