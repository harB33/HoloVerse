<?php
$siteRoot = $siteRoot ?? '';
?>
<footer class="footer">
    <div class="footer-main">
        <div class="footer-about">
            <a class="footer-brand" href="<?= $siteRoot ?>home#top" aria-label="HoloVerse Store home">
                <img src="<?= $siteRoot ?>assets/images/logo.png" alt="">
                <span>holo<span>verse</span></span>
                <small>Store</small>
            </a>
            <p>The dedicated international storefront for official Hololive production collectible merchandise. Officially authorized in partnership with COVER Corporation.</p>
            <span class="footer-hubs">Tokyo&nbsp; | &nbsp;Los Angeles&nbsp; | &nbsp;Singapore Fulfillment Hubs</span>
        </div>

        <nav class="footer-links" aria-label="Merchandise links">
            <h3>Merchandise</h3>
            <a href="<?= $siteRoot ?>flash-sale">Scale Figures (1/4, 1/7)</a>
            <a href="<?= $siteRoot ?>flash-sale">Commemorative Box Sets</a>
            <a href="<?= $siteRoot ?>flash-sale">Apparel &amp; Sukajan</a>
            <a href="<?= $siteRoot ?>flash-sale">Acrylic Stands &amp; Keychains</a>
            <a href="<?= $siteRoot ?>flash-sale">Official Plushies &amp; Mochiduru</a>
        </nav>

        <nav class="footer-links" aria-label="Customer care links">
            <h3>Customer Care</h3>
            <a href="<?= $siteRoot ?>pre_order">Pre-Order Batch ETA Tracker</a>
            <a href="<?= $siteRoot ?>personalized">Shipping Rates &amp; Customs Info</a>
            <a href="<?= $siteRoot ?>personalized">Mint Condition Box Guarantee</a>
            <a href="<?= $siteRoot ?>collector_showcase">Anti-Scalper Verification</a>
            <a href="<?= $siteRoot ?>collector_showcase">NFC Chip Mobile Tap Support</a>
        </nav>

        <nav class="footer-links" aria-label="Corporate links">
            <h3>COVER Corp Portal</h3>
            <a href="<?= $siteRoot ?>about-us">Talent Licensing Guidelines</a>
            <a href="<?= $siteRoot ?>about-us">Authenticity Guarantee</a>
            <a href="<?= $siteRoot ?>about-us">ISO 27001 Security</a>
            <a href="<?= $siteRoot ?>about-us">Privacy Policy &amp; Terms</a>
            <a href="<?= $siteRoot ?>about-us">Wholesale Partner Inquiries</a>
        </nav>
    </div>

    <div class="footer-bottom">
        <p>&copy; 2016-2026 COVER Corp. / HoloVerse Store. All rights reserved.</p>
        <nav aria-label="Legal links">
            <a href="<?= $siteRoot ?>about-us">Terms of Service</a>
            <span aria-hidden="true">&bull;</span>
            <a href="<?= $siteRoot ?>about-us">Privacy Notice</a>
            <span aria-hidden="true">&bull;</span>
            <a href="<?= $siteRoot ?>about-us">Cookie Preferences</a>
        </nav>
    </div>
</footer>
