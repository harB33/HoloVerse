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
        <a href="<?= $pageUrlPrefix ?>pre_order.php"<?= $activePage === 'pre_order' ? ' aria-current="page"' : '' ?>>Pre-Order</a>
        <a href="<?= $pageUrlPrefix ?>flash_sale.php"<?= $activePage === 'flash_sale' ? ' aria-current="page"' : '' ?>>Flash Sales</a>
        <a href="<?= $pageUrlPrefix ?>collector_showcase.php"<?= $activePage === 'collector_showcase' ? ' aria-current="page"' : '' ?>>Collector Showcase</a>
        <a href="<?= $pageUrlPrefix ?>personalized.php"<?= $activePage === 'personalized' ? ' aria-current="page"' : '' ?>>Personalized</a>
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
                            <span class="account-dropdown-section">Administrator tools</span>
                            <a href="<?= $pageUrlPrefix ?>admin-dashboard.php">Admin dashboard</a>
                        <?php endif; ?>
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
                    </div>
                </details>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</header>
