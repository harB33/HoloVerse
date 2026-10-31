<?php
$siteRoot = $siteRoot ?? '';
$footerCartAction = $siteRoot === '/' ? '/cart' : ($siteRoot . 'cart');
$footerLogoUrl = $siteRoot === '/' ? '/assets/images/logo.png' : ($siteRoot . 'assets/images/logo.png');
$footerProductsUrl = $siteRoot === '/' ? '/products' : ($siteRoot . 'products');
?>
<footer class="footer site-footer">
    <div class="footer-main landing-shell">
        <div class="footer-brand-column">
            <a class="footer-brand" href="<?= store_escape(($siteRoot === '/' ? '/home' : $siteRoot . 'home') . '#top') ?>" aria-label="HoloVerse Store home">
                <img src="<?= store_escape($footerLogoUrl) ?>" alt="">
                <span>holo<span>verse</span></span>
                <small>Official Shop</small>
            </a>
            <p class="footer-tagline">Authentic apparel, plushies, scale figures, and collectibles from hololive production, delivered to fans around the world.</p>
            <span class="footer-hubs">Tokyo &bull; Los Angeles &bull; Manila &bull; Singapore Fulfillment Hubs</span>
        </div>

        <div>
            <h3>Shop</h3>
            <a href="<?= store_escape($footerProductsUrl) ?>">New arrivals</a>
            <a href="<?= store_escape($siteRoot === '/' ? '/flash-sale' : $siteRoot . 'flash-sale') ?>">Flash drop</a>
            <a href="<?= store_escape($siteRoot === '/' ? '/pre_order' : $siteRoot . 'pre_order') ?>">Pre-orders</a>
            <a href="<?= store_escape($siteRoot === '/' ? '/products/category/plush' : $siteRoot . 'products/category/plush') ?>">Plushies</a>
            <a href="<?= store_escape($siteRoot === '/' ? '/products/category/apparel' : $siteRoot . 'products/category/apparel') ?>">Apparel</a>
            <a href="<?= store_escape($siteRoot === '/' ? '/products/category/acrylic' : $siteRoot . 'products/category/acrylic') ?>">Acrylics</a>
        </div>

        <div>
            <h3>Customer care</h3>
            <a href="<?= store_escape($siteRoot === '/' ? '/pre_order' : $siteRoot . 'pre_order') ?>">Pre-Order Batch ETA Tracker</a>
            <a href="<?= store_escape($siteRoot === '/' ? '/personalized' : $siteRoot . 'personalized') ?>">International shipping &amp; customs</a>
            <a href="<?= store_escape($siteRoot === '/' ? '/collector-showcase' : $siteRoot . 'collector-showcase') ?>">Collector Showcase</a>
            <a href="<?= store_escape($siteRoot === '/' ? '/purchase' : $siteRoot . 'purchase') ?>">My Purchases</a>
            <a href="<?= store_escape($footerCartAction) ?>">Shopping Bag</a>
        </div>

        <div>
            <h3>Official &amp; info</h3>
            <a href="<?= store_escape($siteRoot === '/' ? '/talents' : $siteRoot . 'talents') ?>">Talent directory</a>
            <a href="<?= store_escape($siteRoot === '/' ? '/about-us' : $siteRoot . 'about-us') ?>">About HoloVerse</a>
            <a href="https://hololive.hololivepro.com/en/" target="_blank" rel="noreferrer">hololive production</a>
            <a href="https://shop.hololivepro.com/en/" target="_blank" rel="noreferrer">Official hololive shop</a>
        </div>
    </div>

    <div class="footer-bottom landing-shell">
        <p>&copy; 2016-2026 COVER Corp. / HoloVerse Store. Official artwork copyright COVER Corp.</p>
        <nav aria-label="Legal links">
            <a href="<?= store_escape($siteRoot === '/' ? '/about-us' : $siteRoot . 'about-us') ?>">Terms of Service</a>
            <span aria-hidden="true">&bull;</span>
            <a href="<?= store_escape($siteRoot === '/' ? '/about-us' : $siteRoot . 'about-us') ?>">Privacy Notice</a>
            <span aria-hidden="true">&bull;</span>
            <a href="<?= store_escape($siteRoot === '/' ? '/about-us' : $siteRoot . 'about-us') ?>">Authenticity Guarantee</a>
        </nav>
    </div>
</footer>

<?php if (($activePage ?? '') !== 'home' && function_exists('store_csrf_token')): ?>
<div class="modal-wrap" data-quick-view-modal hidden role="dialog" aria-modal="true" aria-labelledby="global-quick-view-title">
    <button class="modal-backdrop" type="button" data-quick-view-close aria-label="Close quick view"></button>
    <div class="quick-modal">
        <button class="modal-close" type="button" data-quick-view-close aria-label="Close quick view">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
        <div class="quick-modal-image">
            <img data-quick-view-image src="<?= store_escape($footerLogoUrl) ?>" alt="">
        </div>
        <div class="quick-modal-copy">
            <p class="eyebrow" data-quick-view-talent></p>
            <h2 id="global-quick-view-title" data-quick-view-name></h2>
            <strong data-quick-view-price></strong>
            <del class="quick-compare-price" data-quick-view-compare hidden></del>
            <p data-quick-view-description></p>
            <div class="availability" data-quick-view-availability>
                <span></span>
                <p data-quick-view-status></p>
            </div>
            <div class="quick-modal-actions">
                <form action="<?= store_escape($footerCartAction) ?>" method="post" data-quick-view-form>
                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                    <input type="hidden" name="variant_id" value="" data-quick-view-variant>
                    <button class="button-primary dark" type="submit" name="action" value="add" data-quick-view-submit>
                        <span data-quick-view-submit-label>Add to bag</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                    </button>
                </form>
                <a class="button-text" href="<?= store_escape($footerProductsUrl) ?>" data-quick-view-link>
                    <span>View full product page</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
</div>
<script>
    (() => {
        if (window.__holoverseGlobalQuickViewReady) {
            return;
        }
        window.__holoverseGlobalQuickViewReady = true;

        const quickModal = document.querySelector("[data-quick-view-modal]");
        if (!quickModal) {
            return;
        }
        const quickCloseBtns = quickModal.querySelectorAll("[data-quick-view-close]");
        const quickImage = quickModal.querySelector("[data-quick-view-image]");
        const quickTalent = quickModal.querySelector("[data-quick-view-talent]");
        const quickName = quickModal.querySelector("[data-quick-view-name]");
        const quickPrice = quickModal.querySelector("[data-quick-view-price]");
        const quickCompare = quickModal.querySelector("[data-quick-view-compare]");
        const quickDesc = quickModal.querySelector("[data-quick-view-description]");
        const quickAvailability = quickModal.querySelector("[data-quick-view-availability]");
        const quickStatus = quickModal.querySelector("[data-quick-view-status]");
        const quickVariantInput = quickModal.querySelector("[data-quick-view-variant]");
        const quickSubmitBtn = quickModal.querySelector("[data-quick-view-submit]");
        const quickSubmitLabel = quickModal.querySelector("[data-quick-view-submit-label]");
        const quickForm = quickModal.querySelector("[data-quick-view-form]");
        const quickLink = quickModal.querySelector("[data-quick-view-link]");

        const closeQuickView = () => {
            quickModal.hidden = true;
        };

        document.addEventListener("click", (event) => {
            const target = event.target instanceof Element ? event.target.closest("[data-quick-view-open]") : null;
            if (!target) {
                return;
            }
            const card = target.closest("[data-product-card]");
            if (!card) {
                return;
            }
            let data;
            try {
                data = JSON.parse(card.dataset.quickView || "{}");
            } catch {
                return;
            }
            if (quickImage) {
                quickImage.src = data.image || "<?= store_escape($footerLogoUrl) ?>";
                quickImage.alt = data.name || "";
            }
            if (quickTalent) quickTalent.textContent = data.talent || "";
            if (quickName) quickName.textContent = data.name || "";
            if (quickPrice) quickPrice.textContent = data.priceFormatted || "";
            if (quickCompare) {
                if (data.compareAtFormatted) {
                    quickCompare.hidden = false;
                    quickCompare.textContent = data.compareAtFormatted;
                } else {
                    quickCompare.hidden = true;
                }
            }
            if (quickDesc) {
                quickDesc.textContent = `${data.description || ""}${data.shipWindow ? ` ${data.shipWindow}.` : ""}`;
            }
            if (quickAvailability) {
                quickAvailability.className = `availability availability-${data.status || "ready"}`;
            }
            if (quickStatus) {
                quickStatus.textContent = `${data.statusLabel || "Ready to ship"}${data.shipWindow ? ` · ${data.shipWindow}` : ""}`;
            }
            if (quickVariantInput) {
                quickVariantInput.value = String(data.variantId || "");
            }
            if (quickForm) {
                quickForm.dataset.toastMessage = `${data.name} ${data.status === "preorder" ? "reserved in your bag" : "added to your bag"}`;
            }
            if (quickSubmitBtn && quickSubmitLabel) {
                const inStock = Number(data.stockQuantity || 0) > 0;
                quickSubmitBtn.disabled = !inStock;
                quickSubmitLabel.textContent = !inStock
                    ? "Sold out"
                    : (data.status === "preorder" ? "Reserve pre-order" : (data.status === "flash" ? "Claim flash price" : "Add to bag"));
            }
            if (quickLink) {
                quickLink.href = data.productUrl || "<?= store_escape($footerProductsUrl) ?>";
            }
            quickModal.hidden = false;
        });

        quickCloseBtns.forEach((btn) => btn.addEventListener("click", closeQuickView));
        window.addEventListener("holoverse:item-added", closeQuickView);
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                closeQuickView();
            }
        });
    })();
</script>
<?php endif; ?>
