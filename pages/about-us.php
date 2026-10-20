<?php
require_once __DIR__ . '/../app/store.php';
$pageTitle = 'About Us | hololive merchandise';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell info-page about-page">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = 'about-us';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="info-main">
            <section class="page-intro">
                <span class="eyebrow">Our story</span>
                <h1>Built for fans who collect memories.</h1>
                <p>holoverse is more than apparel and accessories — it is a celebration of the creators, communities, and moments that make each fan journey unforgettable. Every item is designed to bring a piece of the hololive energy into daily life, from limited-run apparel to display-ready collectibles.</p>
            </section>

            <section class="content-grid">
                <article class="info-card">
                    <span class="badge">Mission</span>
                    <h2>Celebrate creativity</h2>
                    <p>We curate thoughtful pieces inspired by the talent, culture, and community that define hololive. Our goal is to help fans showcase their love in a way that feels premium, personal, and collectible.</p>
                </article>

                <article class="info-card">
                    <span class="badge">Quality</span>
                    <h2>Made to stand out</h2>
                    <p>Each collection balances bold design, comfort, and durable construction. We focus on materials, fit, and attention to detail so your favorite characters are represented with pride.</p>
                </article>

                <article class="info-card">
                    <span class="badge">Community</span>
                    <h2>Created with fans</h2>
                    <ul>
                        <li>Limited runs and themed drops</li>
                        <li>Exclusive apparel and accessories</li>
                        <li>Collector-focused packaging and presentation</li>
                    </ul>
                </article>
            </section>

            <section class="about-team" aria-labelledby="about-team-title">
                <header class="about-team-header">
                    <span class="eyebrow">The people behind HoloVerse</span>
                    <h2 id="about-team-title">Meet the Team</h2>
                    <p>A team working together to bring the HoloVerse store to life.</p>
                </header>
                <article class="about-team-card about-team-lead">
                    <img class="about-team-photo" src="../assets/images/Wamil.png" alt="Jomari Wamil" loading="lazy" decoding="async">
                    <h3>Jomari Wamil</h3>
                    <p>Team Leader</p>
                </article>
                <div class="about-team-grid" aria-label="Development team">
                    <?php foreach ([
                        [
                            'name' => 'Harvy Bautista',
                            'image' => '../Assets/images/harvy.png',
                        ],
                        [
                            'name' => 'Jhon Rick Parica',
                            'image' => '../Assets/images/parica.jpg',
                        ],
                        [
                            'name' => 'John Rommel Patriarca',
                            'image' => '../Assets/images/rommel.jpg',
                        ],
                    ] as $teamMember): ?>
                        <article class="about-team-card">
                            <?php if (!empty($teamMember['image'])): ?>
                                <img class="about-team-photo" src="<?= store_escape($teamMember['image']) ?>" alt="<?= store_escape($teamMember['name']) ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="about-team-photo">Photo coming soon</div>
                            <?php endif; ?>
                            <h3><?= store_escape($teamMember['name']) ?></h3>
                            <p>Developer</p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="about-tech-stack" aria-labelledby="about-tech-stack-title">
                <header class="about-tech-stack-header">
                    <span class="eyebrow">Behind the store</span>
                    <h2 id="about-tech-stack-title">The technology behind HoloVerse</h2>
                    <p>We use a practical, modern web stack to power the catalog, account experience, and responsive storefront.</p>
                </header>
                <div class="about-tech-grid">
                    <article class="about-tech-card">
                        <img src="../assets/images/tech-php.svg" alt="" loading="lazy" decoding="async">
                        <h3>PHP</h3>
                        <p>Server-rendered pages and store workflows.</p>
                    </article>
                    <article class="about-tech-card">
                        <img src="../assets/images/tech-postgresql.svg" alt="" loading="lazy" decoding="async">
                        <h3>PostgreSQL</h3>
                        <p>Supabase-hosted data for the catalog, accounts, carts, and orders.</p>
                    </article>
                    <article class="about-tech-card">
                        <img src="../assets/images/tech-tailwindcss.svg" alt="" loading="lazy" decoding="async">
                        <h3>Tailwind CSS</h3>
                        <p>Responsive styling and shared design utilities.</p>
                    </article>
                    <article class="about-tech-card">
                        <img src="../assets/images/tech-javascript.svg" alt="" loading="lazy" decoding="async">
                        <h3>JavaScript</h3>
                        <p>Interactive features in the browser.</p>
                    </article>
                    <article class="about-tech-card">
                        <img src="../assets/images/tech-html5.svg" alt="" loading="lazy" decoding="async">
                        <h3>HTML5</h3>
                        <p>Accessible structure for every page.</p>
                    </article>
                    <article class="about-tech-card">
                        <img src="../assets/images/tech-css3.svg" alt="" loading="lazy" decoding="async">
                        <h3>CSS3</h3>
                        <p>Layouts, visual details, and responsive polish.</p>
                    </article>
                </div>
            </section>

        </main>
    </div>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
</body>

</html>
