<?php
require_once __DIR__ . '/../../store.php';
$siteRoot = $siteRoot ?? '';
$activePage = $activePage ?? '';
$showLogin = $showLogin ?? true;
$pageUrlPrefix = $siteRoot . 'pages/';
?>
<header class="site-header">
    <a class="brand" href="<?= $siteRoot ?>index.php" aria-label="HoloVerse Store home">
        <img class="brand-logo" src="<?= $siteRoot ?>assets/images/logo.png" alt="">
        <span class="brand-wordmark">holo<span>verse</span></span>
    </a>

    <nav class="main-nav" aria-label="Main navigation">
        <a href="<?= $siteRoot ?>index.php">Home</a>
        <a href="<?= $pageUrlPrefix ?>products.php"<?= $activePage === 'products' ? ' aria-current="page"' : '' ?>>Products</a>
        <a href="<?= $pageUrlPrefix ?>flash_sale.php"<?= $activePage === 'flash_sale' ? ' aria-current="page"' : '' ?>>Flash Sales</a>
        <a href="<?= $pageUrlPrefix ?>about-us.php"<?= $activePage === 'about-us' ? ' aria-current="page"' : '' ?>>About Us</a>
    </nav>

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
                        <a class="account-dropdown-action" href="<?= $pageUrlPrefix ?>personalized.php"<?= $activePage === 'personalized' ? ' aria-current="page"' : '' ?>><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-palette preview-icon"><path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z"/><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/></svg> Accessibility</a>
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
                        <a class="account-dropdown-action" href="<?= $pageUrlPrefix ?>login.php"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-in preview-icon" aria-hidden="true">
                            <path d="m10 17 5-5-5-5" />
                            <path d="M15 12H3" />
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                        </svg>Log in</a>
                        <a class="account-dropdown-action" href="<?= $pageUrlPrefix ?>collector_showcase.php"<?= $activePage === 'collector_showcase' ? ' aria-current="page"' : '' ?>>Collector Showcase</a>
                        <a class="account-dropdown-action" href="<?= $pageUrlPrefix ?>personalized.php"<?= $activePage === 'personalized' ? ' aria-current="page"' : '' ?>>Personalized</a>
                    </div>
                </details>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</header>
