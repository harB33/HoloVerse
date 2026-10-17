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
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </summary>
                    <div class="account-dropdown">
                        <span class="account-dropdown-name"><?= store_escape((string) ($_SESSION['username'] ?? 'Account')) ?></span>
                        <?php if (store_has_role('admin')): ?>
                            <span class="account-dropdown-section">Administrator tools</span>
                            <a href="<?= $pageUrlPrefix ?>admin-dashboard.php">Admin dashboard</a>
                        <?php endif; ?>
                        <a href="<?= $pageUrlPrefix ?>cart.php">My cart</a>
                        <button type="button" data-logout-dialog-open>Log out</button>
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
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                    </summary>
                    <div class="account-dropdown">
                        <span class="account-dropdown-name">Guest mode</span>
                        <a href="<?= $pageUrlPrefix ?>login.php">Log in</a>
                    </div>
                </details>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</header>
