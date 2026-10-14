<?php
$pageTitle = 'Your Cart | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell cart-page">
        <?php
        $activePage = '';
        $showLogin = false;
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="cart-main">
            <section class="cart-panel" aria-labelledby="cart-title">
                <svg class="cart-panel-icon" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 11-1 9" />
                    <path d="m19 11-4-7" />
                    <path d="M2 11h20" />
                    <path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4" />
                    <path d="M4.5 15.5h15" />
                    <path d="m5 11 4-7" />
                    <path d="m9 11 1 9" />
                </svg>
                <span class="cart-eyebrow">HoloVerse checkout</span>
                <h1 id="cart-title">Your cart is empty</h1>
                <p>The cart and checkout are not connected yet. Browse the current drops to find your next favorite.</p>
                <a class="cart-continue-button" href="flash_sale.php">Browse flash sales <span aria-hidden="true">→</span></a>
            </section>
        </main>
    </div>
</body>

</html>
