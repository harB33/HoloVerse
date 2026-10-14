<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | HoloVerse</title>
    <link rel="icon" type="image/png" href="../Assets/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&family=Montserrat:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Assets/tailwind.css">
</head>

<body>
    <div class="page-shell cart-page">
        <header class="site-header">
            <a class="brand" href="../index.php" aria-label="HoloVerse Store home">
                <img class="brand-logo" src="../Assets/logo.png" alt="">
                <span class="brand-wordmark">holo<span>verse</span></span>
            </a>
            <nav class="main-nav" aria-label="Main navigation">
                <a href="pre_order.php">Pre-Order</a>
                <a href="flash_sale.php">Flash Sales</a>
                <a href="collector_showcase.php">Collector Showcase</a>
                <a href="personalized.php">Personalized</a>
                <a href="about-us.php">About Us</a>
            </nav>
            <div class="header-actions"></div>
        </header>

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
