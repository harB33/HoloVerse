<?php
require_once __DIR__ . '/../../store.php';
$siteRoot = $siteRoot ?? '';
$activePage = $activePage ?? '';
$showLogin = $showLogin ?? true;
$pageUrlPrefix = $siteRoot;
$catalogSearchValue = $activePage === 'products' && isset($generalProductSearch) && is_string($generalProductSearch)
    ? $generalProductSearch
    : '';
$headerCartId = store_current_cart_id($conn, false);
$headerCartPayload = store_cart_drawer_payload($conn, $headerCartId);
$headerCartCount = (int) ($headerCartPayload['count'] ?? 0);
$headerCsrfToken = store_csrf_token();
?>
<div class="site-header-wrap">
    <header class="site-header">
        <button class="mobile-icon-button" type="button" data-mobile-menu-open aria-label="Open menu" aria-expanded="false" aria-controls="site-mobile-menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="4" x2="20" y1="12" y2="12" />
                <line x1="4" x2="20" y1="6" y2="6" />
                <line x1="4" x2="20" y1="18" y2="18" />
            </svg>
        </button>

        <a class="brand" href="<?= store_escape($siteRoot . 'home') ?>" aria-label="HoloVerse Store home">
            <img class="brand-logo" src="<?= store_escape($siteRoot . 'assets/images/logo.png') ?>" alt="">
            <span class="brand-wordmark">holo<span>verse</span></span>
        </a>

        <nav class="main-nav" aria-label="Main navigation">
            <a href="<?= store_escape($siteRoot . 'home') ?>"<?= $activePage === 'home' ? ' aria-current="page"' : '' ?>>Home</a>
            <a href="<?= store_escape($pageUrlPrefix . 'products') ?>"<?= $activePage === 'products' ? ' aria-current="page"' : '' ?>>Product</a>
            <a href="<?= store_escape($pageUrlPrefix . 'talents') ?>"<?= $activePage === 'talents' ? ' aria-current="page"' : '' ?>>Talent</a>
            <a href="<?= store_escape($pageUrlPrefix . 'about-us') ?>"<?= $activePage === 'about-us' ? ' aria-current="page"' : '' ?>>About Us</a>
        </nav>

        <div class="header-actions">
            <span class="currency-button" aria-label="Store currency: Philippine Peso (PHP)">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                    <path d="M2 12h20" />
                </svg>
                <span>PHP</span>
            </span>

            <button class="icon-button" type="button" data-search-overlay-open aria-label="Search store" aria-expanded="false" aria-controls="site-search-overlay">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
            </button>

            <?php if ($showLogin): ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <details class="account-menu">
                        <summary class="icon-button account-button" aria-label="Open account menu">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="8" r="5" />
                                <path d="M20 21a8 8 0 0 0-16 0" />
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
                                <a class="account-dropdown-action" href="<?= store_escape($pageUrlPrefix . 'admin-dashboard') ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check preview-icon" aria-hidden="true">
                                    <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
                                    <path d="m9 12 2 2 4-4" />
                                </svg>Admin dashboard</a>
                            <?php endif; ?>
                            <a class="account-dropdown-action" href="<?= store_escape($pageUrlPrefix . 'collector-showcase') ?>"<?= $activePage === 'collector_showcase' ? ' aria-current="page"' : '' ?>><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-playing-cards-fan preview-icon"><path d="M12.65 7.65a2 2 0 012.629-1.046l5.51 2.374a2 2 0 011.046 2.628l-3.957 9.184a2 2 0 01-2.628 1.046l-5.51-2.374a2 2 0 01-1.046-2.628z"/><path d="M18 7.777V4a2 2 0 00-2-2h-6a2 2 0 00-2 2v10a2 2 0 001.137 1.805"/><path d="m8 4.389-4.364.809a2 2 0 00-1.602 2.33l1.822 9.833a2 2 0 002.331 1.602l2.542-.47"/></svg> Collector Showcase</a>
                            <button class="account-dropdown-action" type="button" data-accessibility-dialog-open><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-palette preview-icon" aria-hidden="true"><path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/></svg>Accessibility</button>
                            <a class="account-dropdown-action" href="<?= store_escape($pageUrlPrefix . 'cart') ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart preview-icon">
                                <path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18"/>
                                <path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25"/>
                                <circle cx="18" cy="20" r="2"/><circle cx="8" cy="20" r="2"/></svg>Cart</a>
                            <a class="account-dropdown-action" href="<?= store_escape($pageUrlPrefix . 'purchase') ?>"<?= $activePage === 'orders' ? ' aria-current="page"' : '' ?>><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="preview-icon" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8l-5-5H8Z"/><path d="M14 3v6h6"/><path d="M8 13h8"/><path d="M8 17h8"/></svg>My Purchase</a>
                            <button class="account-dropdown-action" type="button" data-logout-dialog-open><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out preview-icon">
                                <path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg>Log out</button>
                        </div>
                    </details>
                    <dialog class="logout-dialog" data-logout-dialog aria-labelledby="logout-dialog-title" aria-describedby="logout-dialog-description">
                        <div class="logout-dialog-content">
                            <h2 id="logout-dialog-title">Log out of HoloVerse?</h2>
                            <p id="logout-dialog-description">You can sign back in anytime to continue shopping.</p>
                            <form action="<?= store_escape($pageUrlPrefix . 'logout') ?>" method="post">
                                <input type="hidden" name="csrf_token" value="<?= store_escape($headerCsrfToken) ?>">
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
                        <summary class="icon-button account-button" aria-label="Open guest menu">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="8" r="5" />
                                <path d="M20 21a8 8 0 0 0-16 0" />
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
                            <a class="account-dropdown-action" href="<?= store_escape($pageUrlPrefix . 'collector-showcase') ?>"<?= $activePage === 'collector_showcase' ? ' aria-current="page"' : '' ?>><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-playing-cards-fan preview-icon" aria-hidden="true"><path d="M12.65 7.65a2 2 0 012.629-1.046l5.51 2.374a2 2 0 011.046 2.628l-3.957 9.184a2 2 0 01-2.628 1.046l-5.51-2.374a2 2 0 01-1.046-2.628z"/><path d="M18 7.777V4a2 2 0 0 0-2-2h-6a2 2 0 0 0-2 2v10a2 2 0 0 0 1.137 1.805"/><path d="m8 4.389-4.364.809a2 2 0 0 0-1.602 2.33l1.822 9.833a2 2 0 0 0 2.331 1.602l2.542-.47"/></svg>Collector Showcase</a>
                            <button class="account-dropdown-action" type="button" data-accessibility-dialog-open><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-palette preview-icon" aria-hidden="true"><path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/></svg>Accessibility</button>
                            <a class="account-dropdown-action" href="<?= store_escape($pageUrlPrefix . 'login') ?>"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-in preview-icon" aria-hidden="true">
                                <path d="m10 17 5-5-5-5" />
                                <path d="M15 12H3" />
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                            </svg>Log in</a>
                        </div>
                    </details>
                <?php endif; ?>
            <?php endif; ?>

            <button class="icon-button bag-button" type="button" data-cart-drawer-open aria-label="Open shopping bag" aria-expanded="false" aria-controls="site-cart-drawer">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                    <path d="M3 6h18" />
                    <path d="M16 10a4 4 0 0 1-8 0" />
                </svg>
                <span data-cart-badge<?= $headerCartCount > 0 ? '' : ' hidden' ?>><?= $headerCartCount > 99 ? '99+' : $headerCartCount ?></span>
            </button>

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

<button class="drawer-backdrop" type="button" data-mobile-menu-backdrop hidden aria-label="Close menu"></button>
<aside id="site-mobile-menu" class="mobile-menu" data-mobile-menu hidden aria-label="Mobile navigation">
    <div class="mobile-menu-head">
        <a class="brand" href="<?= store_escape($siteRoot . 'home') ?>" aria-label="HoloVerse Store home">
            <img class="brand-logo" src="<?= store_escape($siteRoot . 'assets/images/logo.png') ?>" alt="">
            <span class="brand-wordmark">holo<span>verse</span></span>
        </a>
        <button type="button" data-mobile-menu-close aria-label="Close menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
    <nav aria-label="Mobile menu links">
        <a href="<?= store_escape($siteRoot . 'home') ?>">Home <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
        <a href="<?= store_escape($pageUrlPrefix . 'products') ?>">Product <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
        <a href="<?= store_escape($pageUrlPrefix . 'talents') ?>">Talent <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
        <a href="<?= store_escape($pageUrlPrefix . 'about-us') ?>">About Us <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
    </nav>
    <p>Authentic hololive production merchandise, shipped worldwide.</p>
</aside>

<div id="site-search-overlay" class="search-overlay" data-search-overlay hidden>
    <div class="search-panel">
        <div class="search-panel-head">
            <p>Search the official shop</p>
            <button type="button" data-search-overlay-close aria-label="Close search">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form class="search-overlay-form" action="<?= store_escape($pageUrlPrefix . 'products') ?>" method="get" data-search-overlay-form>
            <div class="search-input-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
                <input
                    type="search"
                    name="global_search"
                    value="<?= store_escape($catalogSearchValue) ?>"
                    placeholder="Search a talent, plushie, apparel..."
                    autocomplete="off"
                    data-search-overlay-input
                    aria-label="Search products, categories, or talents"
                >
            </div>
        </form>
        <div class="search-suggestions">
            <span>Popular:</span>
            <?php foreach (['Gigi Murin', 'Hoshimachi Suisei', 'Mori Calliope', 'Plush', 'Apparel', 'Acrylic', 'hololive English'] as $popularTerm): ?>
                <button type="button" data-search-suggestion="<?= store_escape($popularTerm) ?>"><?= store_escape($popularTerm) ?></button>
            <?php endforeach; ?>
        </div>
        <p class="search-result-count" data-search-overlay-count hidden></p>
    </div>
</div>

<button class="drawer-backdrop" type="button" data-cart-drawer-backdrop hidden aria-label="Dismiss shopping bag overlay"></button>
<aside id="site-cart-drawer" class="cart-drawer" data-cart-drawer hidden aria-label="Shopping bag">
    <div class="cart-head">
        <div>
            <p>Your bag</p>
            <span data-cart-drawer-count><?= $headerCartCount ?> <?= $headerCartCount === 1 ? 'item' : 'items' ?></span>
        </div>
        <button type="button" data-cart-drawer-close aria-label="Close shopping bag">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    </div>
    <div class="cart-lines" data-cart-drawer-lines></div>
    <div class="cart-footer" data-cart-drawer-footer hidden>
        <div class="cart-preorder-note" data-cart-drawer-preorder-note hidden>
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m16 16 2 2 4-4" />
                <path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14" />
                <path d="m7.5 4.27 9 5.15" />
                <polyline points="3.29 7 12 12 20.71 7" />
                <line x1="12" x2="12" y1="22" y2="12" />
            </svg>
            <p data-cart-drawer-preorder-copy></p>
        </div>
        <div>
            <span>Subtotal</span>
            <strong data-cart-drawer-subtotal><?= store_escape((string) ($headerCartPayload['subtotal'] ?? store_format_price(0))) ?></strong>
        </div>
        <p>Shipping and taxes are calculated at checkout.</p>
        <form action="<?= store_escape($pageUrlPrefix . 'cart') ?>" method="post" class="cart-drawer-checkout-form">
            <input type="hidden" name="csrf_token" value="<?= store_escape($headerCsrfToken) ?>">
            <input type="hidden" name="action" value="checkout">
            <input type="hidden" name="checkout_scope" value="cart">
            <button type="submit" class="cart-drawer-checkout-btn">
                <span>Checkout</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
        </form>
        <a class="cart-drawer-view-link" href="<?= store_escape($pageUrlPrefix . 'cart') ?>">View full cart</a>
    </div>
</aside>

<div class="toast" data-site-toast role="status" aria-live="polite" aria-atomic="true" hidden>
    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M20 6 9 17l-5-5" />
    </svg>
    <span data-site-toast-message></span>
</div>

<script>
    (() => {
        if (window.__holoverseHeaderInitialized) {
            return;
        }
        window.__holoverseHeaderInitialized = true;

        const siteRoot = <?= json_encode($siteRoot, JSON_UNESCAPED_SLASHES) ?>;
        const csrfTokenValue = <?= json_encode($headerCsrfToken, JSON_UNESCAPED_SLASHES) ?>;
        const initialCartState = <?= json_encode($headerCartPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
        const isHomePage = <?= json_encode($activePage === 'home') ?>;

        const accountMenus = document.querySelectorAll(".account-menu");
        document.addEventListener("click", (event) => {
            if (!(event.target instanceof Node)) {
                return;
            }
            accountMenus.forEach((menu) => {
                if (menu.open && !menu.contains(event.target)) {
                    menu.removeAttribute("open");
                }
            });
        });

        const resolveAssetUrl = (path) => {
            if (!path) {
                return `${siteRoot}assets/images/logo.png`;
            }
            if (/^(?:https?:)?\/\//i.test(path)) {
                return path;
            }
            return `${siteRoot}${String(path).replace(/^\/+/, "")}`;
        };

        const cartBaseUrl = (() => {
            const endpoint = new URL(`${siteRoot}cart`, document.baseURI);
            endpoint.pathname = endpoint.pathname.replace(/\/+$/, "");
            endpoint.search = "";
            endpoint.hash = "";
            return endpoint;
        })();

        const toastElement = document.querySelector("[data-site-toast]");
        const toastMessageElement = document.querySelector("[data-site-toast-message]");
        let toastTimeout = null;

        const showToast = (message, isError = false) => {
            if (typeof window.showCollectorFeedback === "function") {
                window.showCollectorFeedback(message, "", isError ? "error" : "success");
                return;
            }
            if (!toastElement || !toastMessageElement) {
                return;
            }
            window.clearTimeout(toastTimeout);
            toastElement.classList.toggle("is-error", Boolean(isError));
            toastElement.setAttribute("role", isError ? "alert" : "status");
            toastMessageElement.textContent = message;
            toastElement.hidden = false;
            toastTimeout = window.setTimeout(() => {
                toastElement.hidden = true;
            }, isError ? 5000 : 2800);
        };
        window.showHoloVerseToast = showToast;

        // Mobile menu drawer
        const mobileMenu = document.querySelector("[data-mobile-menu]");
        const mobileBackdrop = document.querySelector("[data-mobile-menu-backdrop]");
        const mobileOpenButton = document.querySelector("[data-mobile-menu-open]");
        const mobileCloseButton = document.querySelector("[data-mobile-menu-close]");

        const setMobileMenuOpen = (open) => {
            if (!mobileMenu || !mobileBackdrop) {
                return;
            }
            mobileMenu.hidden = !open;
            mobileBackdrop.hidden = !open;
            mobileOpenButton?.setAttribute("aria-expanded", String(open));
        };

        mobileOpenButton?.addEventListener("click", () => setMobileMenuOpen(true));
        mobileCloseButton?.addEventListener("click", () => setMobileMenuOpen(false));
        mobileBackdrop?.addEventListener("click", () => setMobileMenuOpen(false));
        mobileMenu?.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => setMobileMenuOpen(false));
        });

        // Search overlay
        const searchOverlay = document.querySelector("[data-search-overlay]");
        const searchOpenButtons = document.querySelectorAll("[data-search-overlay-open]");
        const searchCloseButton = document.querySelector("[data-search-overlay-close]");
        const searchInput = document.querySelector("[data-search-overlay-input]");
        const searchForm = document.querySelector("[data-search-overlay-form]");
        const searchCountLabel = document.querySelector("[data-search-overlay-count]");

        const setSearchOpen = (open) => {
            if (!searchOverlay) {
                return;
            }
            searchOverlay.hidden = !open;
            searchOpenButtons.forEach((btn) => btn.setAttribute("aria-expanded", String(open)));
            if (open) {
                window.setTimeout(() => searchInput?.focus(), 20);
            }
        };
        window.openHoloVerseSearch = () => setSearchOpen(true);

        searchOpenButtons.forEach((button) => {
            button.addEventListener("click", () => setSearchOpen(true));
        });
        searchCloseButton?.addEventListener("click", () => setSearchOpen(false));
        searchOverlay?.addEventListener("click", (event) => {
            if (event.target === searchOverlay) {
                setSearchOpen(false);
            }
        });

        document.querySelectorAll("[data-search-suggestion]").forEach((button) => {
            button.addEventListener("click", () => {
                const term = button.getAttribute("data-search-suggestion") || "";
                if (!searchInput) {
                    return;
                }
                searchInput.value = term;
                searchInput.dispatchEvent(new Event("input", { bubbles: true }));
                searchInput.focus();
            });
        });

        searchInput?.addEventListener("input", () => {
            const query = searchInput.value.trim();
            if (isHomePage && typeof window.filterHoloVerseHomeShop === "function") {
                const matchCount = window.filterHoloVerseHomeShop({ query });
                if (searchCountLabel) {
                    if (query) {
                        searchCountLabel.hidden = false;
                        searchCountLabel.textContent = `${matchCount} ${matchCount === 1 ? "result" : "results"} on Home · Press Enter to view all in catalog.`;
                    } else {
                        searchCountLabel.hidden = true;
                    }
                }
            } else if (searchCountLabel) {
                if (query) {
                    searchCountLabel.hidden = false;
                    searchCountLabel.textContent = `Press Enter to search "${query}" in the catalog.`;
                } else {
                    searchCountLabel.hidden = true;
                }
            }
        });

        searchForm?.addEventListener("submit", (event) => {
            const query = (searchInput?.value || "").trim();
            if (isHomePage && typeof window.filterHoloVerseHomeShop === "function") {
                const matchCount = window.filterHoloVerseHomeShop({ query });
                if (matchCount > 0) {
                    event.preventDefault();
                    setSearchOpen(false);
                    document.querySelector("#shop")?.scrollIntoView({ behavior: "smooth" });
                    return;
                }
            }
        });

        // Cart drawer
        const cartDrawer = document.querySelector("[data-cart-drawer]");
        const cartBackdrop = document.querySelector("[data-cart-drawer-backdrop]");
        const cartOpenButtons = document.querySelectorAll("[data-cart-drawer-open]");
        const cartCloseButton = document.querySelector("[data-cart-drawer-close]");
        const cartBadges = document.querySelectorAll("[data-cart-badge]");
        const cartCountLabel = document.querySelector("[data-cart-drawer-count]");
        const cartLinesContainer = document.querySelector("[data-cart-drawer-lines]");
        const cartFooter = document.querySelector("[data-cart-drawer-footer]");
        const cartSubtotalElement = document.querySelector("[data-cart-drawer-subtotal]");
        const cartPreorderNote = document.querySelector("[data-cart-drawer-preorder-note]");
        const cartPreorderCopy = document.querySelector("[data-cart-drawer-preorder-copy]");

        const setCartDrawerOpen = (open) => {
            if (!cartDrawer || !cartBackdrop) {
                return;
            }
            cartDrawer.hidden = !open;
            cartBackdrop.hidden = !open;
            cartOpenButtons.forEach((btn) => btn.setAttribute("aria-expanded", String(open)));
        };
        window.openHoloVerseCartDrawer = () => setCartDrawerOpen(true);

        cartOpenButtons.forEach((button) => {
            button.addEventListener("click", () => setCartDrawerOpen(true));
        });
        cartCloseButton?.addEventListener("click", () => setCartDrawerOpen(false));
        cartBackdrop?.addEventListener("click", () => setCartDrawerOpen(false));

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                setMobileMenuOpen(false);
                setSearchOpen(false);
                setCartDrawerOpen(false);
            }
        });

        const mutateCartItem = async (action, itemId, quantity = 1) => {
            const formData = new FormData();
            formData.set("csrf_token", csrfTokenValue);
            formData.set("action", action);
            formData.set("cart_item_id", String(itemId));
            if (action === "update") {
                formData.set("quantity", String(quantity));
            }
            try {
                const response = await fetch(cartBaseUrl, {
                    method: "POST",
                    body: formData,
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const payload = await response.json();
                if (!response.ok || !payload?.success) {
                    throw new Error(payload?.message || "Unable to update your bag.");
                }
                renderCartState(payload);
                if (document.querySelector(".cart-main") && payload.items?.length === 0) {
                    window.location.reload();
                }
            } catch (error) {
                showToast(error instanceof Error ? error.message : "Unable to update your bag.", true);
            }
        };

        const renderCartState = (payload) => {
            if (!payload || typeof payload !== "object") {
                return;
            }
            const count = Number(payload.count || 0);
            const items = Array.isArray(payload.items) ? payload.items : [];

            cartBadges.forEach((badge) => {
                badge.textContent = count > 99 ? "99+" : String(count);
                badge.hidden = count <= 0;
            });

            if (cartCountLabel) {
                cartCountLabel.textContent = `${count} ${count === 1 ? "item" : "items"}`;
            }

            if (!cartLinesContainer || !cartFooter) {
                return;
            }

            cartLinesContainer.replaceChildren();

            if (items.length === 0) {
                cartFooter.hidden = true;
                const emptyWrap = document.createElement("div");
                emptyWrap.className = "empty-cart";
                emptyWrap.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                        <path d="M3 6h18" />
                        <path d="M16 10a4 4 0 0 1-8 0" />
                    </svg>
                    <h3>Your bag is waiting</h3>
                    <p>Add official merch from your favorite talents.</p>
                `;
                const startButton = document.createElement("button");
                startButton.type = "button";
                startButton.className = "button-primary dark";
                startButton.textContent = "Start shopping";
                startButton.addEventListener("click", () => {
                    setCartDrawerOpen(false);
                    const shopSection = document.querySelector("#shop");
                    if (shopSection) {
                        shopSection.scrollIntoView({ behavior: "smooth" });
                    } else {
                        window.location.href = `${siteRoot}products`;
                    }
                });
                emptyWrap.append(startButton);
                cartLinesContainer.append(emptyWrap);
                return;
            }

            items.forEach((item) => {
                const line = document.createElement("div");
                line.className = "cart-line";

                const image = document.createElement("img");
                image.src = resolveAssetUrl(item.image_url);
                image.alt = item.product_name || "";
                image.loading = "lazy";

                const details = document.createElement("div");

                const talent = document.createElement("p");
                talent.textContent = item.talent || item.variant_name || "Official Merch";

                const title = document.createElement("h4");
                const titleLink = document.createElement("a");
                titleLink.href = item.product_url || `${siteRoot}products`;
                titleLink.textContent = item.product_name || "";
                title.append(titleLink);

                const statusSpan = document.createElement("span");
                statusSpan.className = `cart-product-status status-${item.status || "ready"}`;
                statusSpan.textContent = `${item.status_label || "Ready to ship"}${item.ship_window ? ` · ${item.ship_window}` : ""}`;

                const priceStrong = document.createElement("strong");
                priceStrong.textContent = item.line_total || item.unit_price || "";

                const quantityControl = document.createElement("div");
                quantityControl.className = "quantity-control";

                const minusBtn = document.createElement("button");
                minusBtn.type = "button";
                minusBtn.setAttribute("aria-label", `Remove one ${item.product_name}`);
                minusBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/></svg>`;
                minusBtn.addEventListener("click", () => {
                    if (Number(item.quantity) <= 1) {
                        mutateCartItem("remove", item.id);
                    } else {
                        mutateCartItem("update", item.id, Number(item.quantity) - 1);
                    }
                });

                const qtySpan = document.createElement("span");
                qtySpan.textContent = String(item.quantity);

                const plusBtn = document.createElement("button");
                plusBtn.type = "button";
                plusBtn.setAttribute("aria-label", `Add one ${item.product_name}`);
                plusBtn.disabled = Number(item.quantity) >= Number(item.stock_quantity || 99);
                plusBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>`;
                plusBtn.addEventListener("click", () => {
                    mutateCartItem("update", item.id, Number(item.quantity) + 1);
                });

                const removeBtn = document.createElement("button");
                removeBtn.type = "button";
                removeBtn.setAttribute("aria-label", `Remove ${item.product_name}`);
                removeBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>`;
                removeBtn.addEventListener("click", () => {
                    mutateCartItem("remove", item.id);
                });

                quantityControl.append(minusBtn, qtySpan, plusBtn, removeBtn);
                details.append(talent, title, statusSpan, priceStrong, quantityControl);
                line.append(image, details);
                cartLinesContainer.append(line);
            });

            cartFooter.hidden = false;
            if (cartSubtotalElement && payload.subtotal) {
                cartSubtotalElement.textContent = payload.subtotal;
            }
            if (cartPreorderNote && cartPreorderCopy) {
                if (payload.has_preorder) {
                    cartPreorderNote.hidden = false;
                    cartPreorderCopy.textContent = payload.has_non_preorder
                        ? "Pre-orders and in-stock items may ship separately."
                        : "Pre-order items ship around the estimate shown above.";
                } else {
                    cartPreorderNote.hidden = true;
                }
            }
        };

        renderCartState(initialCartState);

        window.addEventListener("holoverse:cart-updated", (event) => {
            if (event instanceof CustomEvent && event.detail) {
                renderCartState(event.detail);
            }
        });

        const syncCartState = async () => {
            try {
                const drawerUrl = new URL(cartBaseUrl);
                drawerUrl.searchParams.set("mode", "drawer");
                const response = await fetch(drawerUrl, {
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                if (!response.ok) {
                    return;
                }
                const payload = await response.json();
                renderCartState(payload);
            } catch (error) {
                console.warn("Unable to sync shopping bag.", error);
            }
        };

        const matchesAddCartForm = (form) => {
            if (form.classList.contains("cart-drawer-checkout-form")) {
                return false;
            }
            const formFields = [...form.querySelectorAll("button, input")];
            return formFields.some((field) => {
                const fieldName = (field.getAttribute("name") || "").toLowerCase();
                const fieldValue = (field.getAttribute("value") || "").toLowerCase();
                const textValue = (field.textContent || "").toLowerCase();
                return (fieldName === "action" && fieldValue === "add")
                    || (field.type === "submit" && (fieldValue === "add" || textValue.includes("add to cart") || textValue.includes("add to bag") || textValue.includes("reserve pre-order") || textValue.includes("claim flash price") || textValue.includes("get the drop")))
                    || (fieldName === "action" && fieldValue.includes("add"));
            });
        };

        const ensureAddCartFields = (form) => {
            const ensureHiddenInput = (name, value) => {
                let input = form.querySelector(`input[name="${name}"]`);
                if (!input) {
                    input = document.createElement("input");
                    input.type = "hidden";
                    input.name = name;
                    form.appendChild(input);
                }
                input.value = value;
            };
            ensureHiddenInput("action", "add");
            if (!form.querySelector('input[name="csrf_token"]')) {
                ensureHiddenInput("csrf_token", csrfTokenValue);
            }
        };

        document.addEventListener("submit", async (event) => {
            const form = event.target instanceof Element ? event.target.closest("form") : null;
            if (!(form instanceof HTMLFormElement) || !matchesAddCartForm(form)) {
                return;
            }
            event.preventDefault();

            if (form.dataset.cartSubmitting === "true") {
                return;
            }

            ensureAddCartFields(form);
            const submitButton = event.submitter instanceof HTMLButtonElement
                ? event.submitter
                : form.querySelector('button[type="submit"]') || form.querySelector("button");
            if (!submitButton) {
                return;
            }

            form.dataset.cartSubmitting = "true";
            const previousHtml = submitButton.innerHTML;
            const customToastMessage = form.dataset.toastMessage || "";
            submitButton.disabled = true;

            try {
                const formData = new FormData(form);
                if (submitButton.name) {
                    formData.set(submitButton.name, submitButton.value);
                }
                const response = await fetch(cartBaseUrl, {
                    method: "POST",
                    body: formData,
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                });
                const responseText = await response.text();
                let payload;
                try {
                    payload = JSON.parse(responseText);
                } catch {
                    throw new Error(
                        response.ok
                            ? "The server returned an unexpected response while adding to your bag."
                            : `Unable to add item to bag (HTTP ${response.status}). Please try again.`
                    );
                }

                if (!response.ok || !payload?.success) {
                    throw new Error(payload?.message || "Unable to add item to bag.");
                }

                if (Array.isArray(payload.items)) {
                    renderCartState(payload);
                } else {
                    await syncCartState();
                }

                window.dispatchEvent(new CustomEvent("holoverse:item-added", {
                    detail: {
                        variantId: Number(formData.get("variant_id") || 0),
                        payload,
                    },
                }));

                showToast("Successfully added to your bag");
            } catch (error) {
                console.error("Unable to add item to bag.", error);
                showToast(error instanceof Error ? error.message : "Unable to add item to bag.", true);
            } finally {
                form.dataset.cartSubmitting = "false";
                submitButton.disabled = false;
                submitButton.innerHTML = previousHtml;
            }
        });
    })();
</script>
<script src="<?= store_escape($siteRoot . 'Assets/js/live-flash-notice.js') ?>" defer></script>
