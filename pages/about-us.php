<?php
require_once __DIR__ . '/../app/store.php';
$pageTitle = 'About Us | hololive merchandise';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell info-page about-page">
        <?php
        $activePage = 'about-us';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="info-main about-main landing-shell">
            <section class="about-introduction" aria-labelledby="about-title">
                <div class="about-intro-copy">
                    <span class="about-kicker">About HoloVerse</span>
                    <h1 id="about-title">For the fans who want to <em>keep the moment.</em></h1>
                    <p>We built HoloVerse to make finding official hololive merchandise feel simple. Browse pieces inspired by the talents and moments you love, whether it’s something to wear or something for your shelf.</p>
                    <div class="about-intro-links">
                        <a href="../products">Browse the collection <span aria-hidden="true">↗</span></a>
                        <a href="#about-team">Meet our team <span aria-hidden="true">↓</span></a>
                    </div>
                </div>
                <figure class="about-feature">
                    <img src="../assets/images/suisei2.jpg" alt="Illustration of Hoshimachi Suisei floating above Earth beneath a shooting star" fetchpriority="high">
                    <figcaption><span>HoloVerse favorite</span><strong>Hoshimachi Suisei</strong></figcaption>
                </figure>
            </section>

            <section class="about-story" aria-labelledby="about-story-title">
                <span class="about-section-index">01 <span aria-hidden="true">/</span> WHY WE’RE HERE</span>
                <div class="about-story-copy">
                    <h2 id="about-story-title">A favorite moment deserves a place in your everyday.</h2>
                    <p>A stream, a song, a character you keep coming back to—being a fan is made up of little moments like these. HoloVerse brings official merchandise into one place, so it’s easier to find the piece that feels like yours.</p>
                </div>
            </section>

            <section class="about-interlude" aria-label="Hoshimachi Suisei artwork">
                <img src="../assets/images/suisei-about.png" alt="Blue-haired anime performer reaching toward the viewer among stars and bright light" loading="eager" decoding="async">
            </section>

            <section class="about-principles" aria-label="How we approach the store">
                <article>
                    <span>01</span>
                    <div>
                        <h2>Find what you love</h2>
                        <p>A considered catalog makes it easier to explore merchandise across talents and collections.</p>
                    </div>
                </article>
                <article>
                    <span>02</span>
                    <div>
                        <h2>Keep it close</h2>
                        <p>Wear it, display it, or save it for later—collecting is personal, and there’s no one way to do it.</p>
                    </div>
                </article>
                <article>
                    <span>03</span>
                    <div>
                        <h2>Built by fans</h2>
                        <p>We’re a small team making a storefront for a community we’re glad to be part of.</p>
                    </div>
                </article>
            </section>

            <section class="about-team" id="about-team" aria-labelledby="about-team-title">
                <header class="about-section-heading">
                    <div>
                        <span class="about-section-index">02 <span aria-hidden="true">/</span> THE PEOPLE</span>
                        <h2 id="about-team-title">Meet the team.</h2>
                    </div>
                    <p>Four people working together to bring the HoloVerse store to life.</p>
                </header>
                <div class="about-team-grid">
                    <?php foreach ([
                        ['name' => 'Jomari Wamil', 'role' => 'Team lead', 'image' => '../assets/images/Wamil.png'],
                        ['name' => 'Harvy Bautista', 'role' => 'Developer', 'image' => '../assets/images/harvy.png'],
                        ['name' => 'Jhon Rick Parica', 'role' => 'Developer', 'image' => '../assets/images/parica.jpg'],
                        ['name' => 'John Rommel Patriarca', 'role' => 'Developer', 'image' => '../assets/images/rommel.jpg'],
                    ] as $teamMember): ?>
                        <article class="about-team-member">
                            <img src="<?= store_escape($teamMember['image']) ?>" alt="<?= store_escape($teamMember['name']) ?>" loading="lazy" decoding="async">
                            <h3><?= store_escape($teamMember['name']) ?></h3>
                            <p><?= store_escape($teamMember['role']) ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="about-toolkit" aria-labelledby="about-toolkit-title">
                <div class="about-toolkit-copy">
                    <span class="about-section-index">03 <span aria-hidden="true">/</span> UNDER THE HOOD</span>
                    <h2 id="about-toolkit-title">The tools we build with.</h2>
                    <p>A practical web stack powers the catalog, accounts, carts, and storefront.</p>
                </div>
                <div class="about-tool-list" aria-label="Technologies used">
                    <span><img src="../assets/images/tech-php.svg" alt="" loading="lazy" decoding="async">PHP</span>
                    <span><img src="../assets/images/tech-postgresql.svg" alt="" loading="lazy" decoding="async">PostgreSQL</span>
                    <span><img src="../assets/images/tech-tailwindcss.svg" alt="" loading="lazy" decoding="async">Tailwind CSS</span>
                    <span><img src="../assets/images/tech-javascript.svg" alt="" loading="lazy" decoding="async">JavaScript</span>
                    <span><img src="../assets/images/tech-html5.svg" alt="" loading="lazy" decoding="async">HTML5</span>
                    <span><img src="../assets/images/tech-css3.svg" alt="" loading="lazy" decoding="async">CSS3</span>
                </div>
            </section>
        </main>
    </div>
    <?php
    $cartHref = 'cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    <script src="../assets/js/about-page.js?v=<?= filemtime(__DIR__ . '/../assets/js/about-page.js') ?>" defer></script>
</body>

</html>
