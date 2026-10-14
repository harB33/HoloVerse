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
                <span class="account-name"><?= store_escape((string) ($_SESSION['username'] ?? 'Account')) ?></span>
                <form action="<?= $pageUrlPrefix ?>logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                    <button class="entry-button" type="submit" aria-label="Log out">Log out</button>
                </form>
            <?php else: ?>
                <span class="account-name">Guest mode</span>
                <a class="entry-button" href="<?= $pageUrlPrefix ?>login.php" aria-label="Log In"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</header>
