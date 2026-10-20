<?php
require_once __DIR__ . '/../../store.php';
$siteRoot = $siteRoot ?? '';
$activePage = $activePage ?? '';
$showLogin = $showLogin ?? true;
$pageUrlPrefix = $siteRoot . 'pages/';
$catalogSearchAction = $pageUrlPrefix . 'products.php';
$catalogSearchId = 'site-catalog-search';
$catalogSearchClass = 'site-header-search';
$catalogSearchLabel = 'Search products, categories, or talents';
$catalogSearchPlaceholder = 'Search products, categories, or talents';
$catalogSearchShowClear = false;
$catalogSearchValue = $activePage === 'products' && isset($productSearch) && is_string($productSearch)
    ? $productSearch
    : '';
$headerFlashSaleTimezone = new DateTimeZone(getenv('HOLOVERSE_TIMEZONE') ?: 'Asia/Manila');
$headerFlashSaleNow = new DateTimeImmutable('now', $headerFlashSaleTimezone);
$headerLiveFlashSaleProducts = isset($flashSaleProducts['live']) && is_array($flashSaleProducts['live'])
    ? $flashSaleProducts['live']
    : (store_flash_sale_table_exists($conn)
        ? store_flash_sale_rows($conn, 'live', $headerFlashSaleNow)
        : store_product_rows(
            $conn,
            'AND v.compare_at_price IS NOT NULL AND v.compare_at_price > v.price AND ' . store_available_stock_sql() . ' > 0',
            'p.product_id DESC'
        ));
$headerLiveFlashSaleCount = count($headerLiveFlashSaleProducts);
?>
<div class="site-header-wrap">
    <div class="site-flash-notice<?= $headerLiveFlashSaleCount > 0 ? '' : ' hidden' ?>" role="status" aria-hidden="<?= $headerLiveFlashSaleCount > 0 ? 'false' : 'true' ?>" data-live-flash-notice data-status-url="<?= store_escape($pageUrlPrefix . 'flash-sale-status.php') ?>">
        <svg class="site-flash-notice-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m13 2-3 8h8L9 22l3-9H5l8-11Z" />
        </svg>
        <span class="site-flash-notice-copy">
            <strong>Flash sale live</strong>
            <span data-live-flash-count><?= $headerLiveFlashSaleCount ?> discounted <?= $headerLiveFlashSaleCount === 1 ? 'deal' : 'deals' ?> available now</span>
        </span>
        <a class="site-flash-notice-link" href="<?= $pageUrlPrefix ?>flash_sale.php">
            Shop the sale
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14" />
                <path d="m12 5 7 7-7 7" />
            </svg>
        </a>
    </div>
<header class="site-header">
    <a class="brand" href="<?= $siteRoot ?>index.php" aria-label="HoloVerse Store home">
        <img class="brand-logo" src="<?= $siteRoot ?>assets/images/logo.png" alt="">
        <span class="brand-wordmark">holo<span>verse</span></span>
    </a>

    <nav class="main-nav" aria-label="Main navigation">
        <a href="<?= $siteRoot ?>index.php"<?= $activePage === 'home' ? ' aria-current="page"' : '' ?>>Home</a>
        <a href="<?= $pageUrlPrefix ?>products.php"<?= $activePage === 'products' ? ' aria-current="page"' : '' ?>>Products</a>
        <a href="<?= $pageUrlPrefix ?>talents.php"<?= $activePage === 'talents' ? ' aria-current="page"' : '' ?>>Talent</a>
        <a href="<?= $pageUrlPrefix ?>about-us.php"<?= $activePage === 'about-us' ? ' aria-current="page"' : '' ?>>About Us</a>
    </nav>

    <?php require __DIR__ . '/catalog-search.php'; ?>

    <div class="header-actions">
        <?php if ($showLogin): ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <details class="account-menu">
                    <summary class="entry-button" aria-label="Open account menu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <circle cx="12" cy="10" r="3" />
                            <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662" />
                        </svg>
                    </summary>
                    <div class="account-dropdown">
                        <div class="account-dropdown-identity">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            <div class="account-dropdown-user">
                                <span class="account-dropdown-name"><?= store_escape((string) ($_SESSION['username'] ?? 'Account')) ?></span>
                                <span class="account-dropdown-email"><?= store_escape((string) ($_SESSION['user_email'] ?? '')) ?></span>
                            </div>
                        </div>
                        <?php if (store_has_role('admin')): ?>
                            <a class="account-dropdown-action" href="<?= $pageUrlPrefix ?>admin-dashboard.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check preview-icon" aria-hidden="true">
                                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                                <path d="m9 12 2 2 4-4" />
                            </svg>Admin dashboard</a>
                        <?php endif; ?>
                        <a class="account-dropdown-action" href="<?= $pageUrlPrefix ?>collector_showcase.php"<?= $activePage === 'collector_showcase' ? ' aria-current="page"' : '' ?>><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-playing-cards-fan preview-icon"><path d="M12.65 7.65a2 2 0 012.629-1.046l5.51 2.374a2 2 0 011.046 2.628l-3.957 9.184a2 2 0 01-2.628 1.046l-5.51-2.374a2 2 0 01-1.046-2.628z"/><path d="M18 7.777V4a2 2 0 00-2-2h-6a2 2 0 00-2 2v10a2 2 0 001.137 1.805"/><path d="m8 4.389-4.364.809a2 2 0 00-1.602 2.33l1.822 9.833a2 2 0 002.331 1.602l2.542-.47"/></svg> Collector Showcase</a>
                        <button class="account-dropdown-action" type="button" data-accessibility-dialog-open><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-palette preview-icon"><path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/></svg>Accessibility</button>
                        <a class="account-dropdown-action" href="<?= $pageUrlPrefix ?>cart.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart preview-icon">
                            <path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"/>
                            <path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"/>
                            <circle cx="18" cy="20" r="2"/><circle cx="8" cy="20" r="2"/></svg>Cart</a>
                        <button class="account-dropdown-action" type="button" data-logout-dialog-open><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out preview-icon">
                            <path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>Log out</button>
                    </div>
                </details>
                <dialog class="logout-dialog" data-logout-dialog aria-labelledby="logout-dialog-title" aria-describedby="logout-dialog-description">
                    <div class="logout-dialog-content">
                        <h2 id="logout-dialog-title">Log out of HoloVerse?</h2>
                        <p id="logout-dialog-description">You can sign back in anytime to continue shopping.</p>
                        <form action="<?= $pageUrlPrefix ?>logout.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                            <div class="logout-dialog-actions">
                                <button class="logout-dialog-cancel" type="button" data-logout-dialog-close>Stay signed in</button>
                                <button class="logout-dialog-confirm" type="submit">Yes, log out</button>
                            </div>
                        </form>
                    </div>
                </dialog>
                <script>
                    (() => {
                        const dialog = document.querySelector("[data-logout-dialog]");
                        const openButton = document.querySelector("[data-logout-dialog-open]");
                        const closeButton = document.querySelector("[data-logout-dialog-close]");

                        if (!dialog || !openButton || !closeButton) {
                            return;
                        }

                        openButton.addEventListener("click", () => dialog.showModal());
                        closeButton.addEventListener("click", () => dialog.close());
                    })();
                </script>
            <?php else: ?>
                <details class="account-menu">
                    <summary class="entry-button" aria-label="Open guest menu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <circle cx="12" cy="10" r="3" />
                            <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662" />
                        </svg>
                    </summary>
                    <div class="account-dropdown">
                        <div class="account-dropdown-identity">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <circle cx="12" cy="10" r="3" />
                                <path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662" />
                            </svg>
                            <span class="account-dropdown-name">Guest mode</span>
                        </div>
                        <a class="account-dropdown-action" href="<?= $pageUrlPrefix ?>collector_showcase.php"<?= $activePage === 'collector_showcase' ? ' aria-current="page"' : '' ?>><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-playing-cards-fan preview-icon" aria-hidden="true"><path d="M12.65 7.65a2 2 0 012.629-1.046l5.51 2.374a2 2 0 011.046 2.628l-3.957 9.184a2 2 0 01-2.628 1.046l-5.51-2.374a2 2 0 01-1.046-2.628z"/><path d="M18 7.777V4a2 2 0 0 0-2-2h-6a2 2 0 0 0-2 2v10a2 2 0 0 0 1.137 1.805"/><path d="m8 4.389-4.364.809a2 2 0 0 0-1.602 2.33l1.822 9.833a2 2 0 0 0 2.331 1.602l2.542-.47"/></svg>Collector Showcase</a>
                        <a class="account-dropdown-action" href="<?= $pageUrlPrefix ?>personalized.php"<?= $activePage === 'personalized' ? ' aria-current="page"' : '' ?>><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-palette preview-icon" aria-hidden="true"><path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/></svg>Accessibility</a>
                        <a class="account-dropdown-action" href="<?= $pageUrlPrefix ?>login.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-in preview-icon" aria-hidden="true">
                            <path d="m10 17 5-5-5-5" />
                            <path d="M15 12H3" />
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                        </svg>Log in</a>
                    </div>
                </details>
            <?php endif; ?>
        <?php endif; ?>
        <dialog class="accessibility-dialog" data-accessibility-dialog aria-labelledby="accessibility-dialog-title" aria-describedby="accessibility-dialog-description">
            <div class="accessibility-dialog-content">
                <header class="accessibility-dialog-header">
                    <div>
                        <h2 id="accessibility-dialog-title">Accessibility Options</h2>
                        <p id="accessibility-dialog-description">Customize your experience to make the application easier to see and use.</p>
                    </div>
                    <button class="accessibility-dialog-close" type="button" data-accessibility-dialog-close aria-label="Close accessibility options">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>
                    </button>
                </header>

                <section class="accessibility-section" aria-labelledby="accessibility-theme-title">
                    <h3 id="accessibility-theme-title">Theme Preference</h3>
                    <div class="accessibility-theme-options" role="group" aria-label="Theme preference">
                        <button class="accessibility-theme-option" type="button" data-accessibility-theme="light" aria-pressed="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                            <span>Light Mode</span>
                        </button>
                        <button class="accessibility-theme-option" type="button" data-accessibility-theme="dark" aria-pressed="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9 7 7 0 0 1-9-9Z"/></svg>
                            <span>Dark Mode</span>
                        </button>
                        <button class="accessibility-theme-option" type="button" data-accessibility-theme="system" aria-pressed="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                            <span>System Default</span>
                        </button>
                    </div>
                </section>

                <section class="accessibility-section" aria-labelledby="accessibility-display-title">
                    <h3 id="accessibility-display-title">Display &amp; Text</h3>
                    <div class="accessibility-setting-heading">
                        <div>
                            <label for="accessibility-scale">Interface Scaling</label>
                            <p>Adjust the size of text and UI elements globally.</p>
                        </div>
                        <output class="accessibility-scale-value" for="accessibility-scale" data-accessibility-scale-value>90%</output>
                    </div>
                    <div class="accessibility-scale-control">
                        <span aria-hidden="true">A</span>
                        <input id="accessibility-scale" type="range" min="80" max="120" step="5" value="90" data-accessibility-scale>
                        <span class="accessibility-large-a" aria-hidden="true">A</span>
                    </div>
                </section>

                <section class="accessibility-section accessibility-motion-section" aria-labelledby="accessibility-motion-title">
                    <h3 id="accessibility-motion-title">Motion</h3>
                    <label class="accessibility-motion-setting" for="accessibility-reduce-motion">
                        <span>
                            <strong>Reduce Motion</strong>
                            <span>Disable background animations and page transitions.</span>
                        </span>
                        <input id="accessibility-reduce-motion" type="checkbox" data-accessibility-reduce-motion>
                        <span class="accessibility-toggle" aria-hidden="true"></span>
                    </label>
                </section>
            </div>
        </dialog>
        <script>
            (() => {
                const dialog = document.querySelector("[data-accessibility-dialog]");
                const openButtons = document.querySelectorAll("[data-accessibility-dialog-open]");
                const closeButton = document.querySelector("[data-accessibility-dialog-close]");
                const themeButtons = document.querySelectorAll("[data-accessibility-theme]");
                const scaleInput = document.querySelector("[data-accessibility-scale]");
                const scaleValue = document.querySelector("[data-accessibility-scale-value]");
                const reduceMotionInput = document.querySelector("[data-accessibility-reduce-motion]");
                const storageKey = "holoverse-accessibility-settings";

                if (!dialog || !closeButton || !scaleInput || !scaleValue || !reduceMotionInput) {
                    return;
                }

                let settings = { theme: "light", scale: 90, reduceMotion: false };
                try {
                    settings = { ...settings, ...JSON.parse(localStorage.getItem(storageKey) || "{}") };
                } catch (error) {
                    console.warn("Could not load accessibility settings.", error);
                }

                const applySettings = () => {
                    const scale = Math.min(120, Math.max(80, Number(settings.scale) || 90));
                    const theme = ["light", "dark", "system"].includes(settings.theme) ? settings.theme : "light";
                    settings = { ...settings, scale, theme, reduceMotion: Boolean(settings.reduceMotion) };
                    document.documentElement.dataset.theme = theme;
                    document.documentElement.style.zoom = String(scale / 100);
                    document.documentElement.dataset.reduceMotion = String(settings.reduceMotion);
                    scaleInput.value = String(scale);
                    const scaleRange = Number(scaleInput.max) - Number(scaleInput.min);
                    const scaleProgress = ((scale - Number(scaleInput.min)) / scaleRange) * 100;
                    scaleInput.style.setProperty("--range-progress", `${scaleProgress}%`);
                    scaleValue.value = `${scale}%`;
                    scaleValue.textContent = `${scale}%`;
                    reduceMotionInput.checked = settings.reduceMotion;
                    themeButtons.forEach((button) => {
                        button.setAttribute("aria-pressed", String(button.dataset.accessibilityTheme === theme));
                    });
                    try {
                        localStorage.setItem(storageKey, JSON.stringify(settings));
                    } catch (error) {
                        console.warn("Could not save accessibility settings.", error);
                    }
                };

                openButtons.forEach((button) => button.addEventListener("click", () => {
                    document.querySelector(".account-menu")?.removeAttribute("open");
                    dialog.showModal();
                }));
                closeButton.addEventListener("click", () => dialog.close());
                dialog.addEventListener("click", (event) => {
                    if (event.target === dialog) dialog.close();
                });
                themeButtons.forEach((button) => button.addEventListener("click", () => {
                    settings.theme = button.dataset.accessibilityTheme;
                    applySettings();
                }));
                scaleInput.addEventListener("input", () => {
                    settings.scale = scaleInput.value;
                    applySettings();
                });
                reduceMotionInput.addEventListener("change", () => {
                    settings.reduceMotion = reduceMotionInput.checked;
                    applySettings();
                });

                applySettings();
            })();
        </script>
    </div>
</header>
</div>
<script src="<?= $siteRoot ?>Assets/js/live-flash-notice.js" defer></script>
