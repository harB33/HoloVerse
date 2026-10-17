<?php
require_once __DIR__ . '/app/store.php';
$generationLabels = [];
$groupsResult = $conn->query(
    "SELECT group_slug, group_name FROM talent_group_tbl
     ORDER BY FIELD(group_slug, 'gen-0', 'gen-1', 'gamers', 'gen-2', 'gen-3', 'gen-4', 'gen-5', 'holox', 'regloss', 'flow-glow', 'myth', 'promise', 'advent', 'justice', 'id-gen-1', 'id-gen-2', 'id-gen-3', 'asobi-mawaritai')"
);
while ($group = $groupsResult->fetch_assoc()) {
    $generationLabels[$group['group_slug']] = $group['group_name'];
}

$talents = [];
$talentStatuses = ['Watson Amelia' => 'Affiliate'];
$talentPortraitOverrides = [
    'Tsunomaki Watame' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Tsunomaki-Watame_talent_list_thumb.png',
    'Momosuzu Nene' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/06/Momosuzu-Nene_talent_list_thumb.png',
    'Kazama Iroha' => 'https://hololive.hololivepro.com/wp-content/uploads/2020/07/Kazama-Iroha_talent_list_thumb.png',
];
$talentResult = $conn->query(
    'SELECT t.talent_name, t.talent_img,
            GROUP_CONCAT(g.group_slug ORDER BY FIELD(g.group_slug, "gen-0", "gen-1", "gamers", "gen-2", "gen-3", "gen-4", "gen-5", "holox", "regloss", "flow-glow", "myth", "promise", "advent", "justice", "id-gen-1", "id-gen-2", "id-gen-3", "asobi-mawaritai") SEPARATOR " ") AS group_slugs
     FROM talent_tbl t
     LEFT JOIN talent_group_members_tbl gm ON gm.talent_id = t.talent_id
     LEFT JOIN talent_group_tbl g ON g.group_id = gm.group_id
     GROUP BY t.talent_id, t.talent_name, t.talent_img
     ORDER BY t.talent_name'
);
while ($talent = $talentResult->fetch_assoc()) {
    $groupSlugs = $talent['group_slugs'] ? explode(' ', $talent['group_slugs']) : [];
    $groupNames = array_map(
        static fn (string $groupSlug): string => $generationLabels[$groupSlug],
        $groupSlugs
    );
    if (isset($talentStatuses[$talent['talent_name']])) {
        $groupNames[] = $talentStatuses[$talent['talent_name']];
    }
    $talents[] = [
        'name' => $talent['talent_name'],
        'groups' => $groupSlugs,
        'group_names' => implode(' ' . "\u{00B7}" . ' ', $groupNames),
        'portrait' => $talentPortraitOverrides[$talent['talent_name']] ?? $talent['talent_img'],
    ];
}
$featuredProducts = store_product_rows($conn, '', 'p.product_id DESC', 4);
$pageTitle = 'Holoverse';
$siteRoot = '';
require __DIR__ . '/app/views/layout/head.php';
?>

<body>
    <main class="page-shell">
        <?php require __DIR__ . '/app/views/components/watermark.php'; ?>
        <?php
        $activePage = 'home';
        require __DIR__ . '/app/views/components/site-header.php';
        ?>

        <div id="top" class="hero">
            <section class="hero-copy" id="new-arrivals">
                <h1>hololive<br>production<br>merchandise</h1>
                <p>Exclusive Apparel, Figures, and Collectibles!</p>
                <p>Limited-Edition items for all hololive fans.</p>
            </section>

            <div class="hero-symbol">
                <img src="assets/images/logo.png" alt="hololive logo">
            </div>
        </div>
        <section class="limited-drops" id="featured" aria-labelledby="limited-drops-title">
            <div class="drops-heading">
                <div>
                    <span class="drops-eyebrow">From the official hololive production shop</span>
                    <h2 id="limited-drops-title">Talent Merch Picks</h2>
                </div>
                <a class="drops-view-all" href="pages/products.php">View all products <span aria-hidden="true">&#8594;</span></a>
            </div>

            <div class="drops-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php $productInitial = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $product['product_name']), 0, 1)); ?>
                    <article class="drop-card">
                        <a class="drop-card-link" href="pages/product.php?id=<?= (int) $product['product_id'] ?>" aria-label="View <?= store_escape($product['product_name']) ?>"></a>
                        <div class="drop-art">
                            <div class="drop-art-labels">
                                <span class="drop-status"><?= (int) $product['stock_quantity'] > 0 ? 'Available now' : 'Sold out' ?></span>
                                <span class="drop-edition"><?= store_escape($product['variant_name']) ?></span>
                            </div>
                            <?php if (!empty($product['image_url'])): ?>
                                <img class="drop-product-image" src="<?= store_escape(store_asset_url($product['image_url'])) ?>" alt="<?= store_escape($product['alt_text'] ?? $product['product_name']) ?>" loading="lazy" decoding="async">
                            <?php endif; ?>
                            <div class="drop-character<?= !empty($product['image_url']) ? ' hidden' : '' ?>"><?= store_escape($productInitial) ?></div>
                            <span class="drop-art-tag"><?= (int) $product['stock_quantity'] ?> in stock</span>
                        </div>
                        <div class="drop-details">
                            <span class="drop-category"><?= store_escape($product['category_name']) ?></span>
                            <h3><?= store_escape($product['product_name']) ?></h3>
                            <p class="drop-description"><?= store_escape($product['description'] ?? '') ?></p>
                            <div class="drop-allocation"><span>Available quantity</span><strong><?= (int) $product['stock_quantity'] ?></strong></div>
                            <div class="drop-purchase">
                                <div><span class="drop-price-label"><?= (int) $product['is_preorder'] === 1 ? 'Pre-order price' : 'Price' ?></span><strong><?= store_escape(store_format_price((float) $product['price'])) ?></strong></div>
                                <?php if ((int) $product['stock_quantity'] > 0): ?>
                                    <form action="pages/cart.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="variant_id" value="<?= (int) $product['variant_id'] ?>">
                                        <button type="submit">Add to cart</button>
                                    </form>
                                <?php else: ?>
                                    <span>Sold out</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <section class="explore" id="explore" aria-labelledby="explore-title">
            <div class="explore-inner">
                <header class="explore-header">
                    <span class="explore-eyebrow">Talent Vault Directory</span>
                    <h2 id="explore-title">Explore Merchandise by Oshi</h2>
                    <p>Filter official apparel, plushies, voice packs, and figures directly tied to your favorite talent.</p>
                </header>

                <div class="explore-filters" role="group" aria-label="Filter talents by generation or group">
                    <button class="explore-filter" type="button" data-explore-filter="all" aria-pressed="true">All Generations (<?= count($talents) ?> Talents)</button>
                    <?php foreach ($generationLabels as $key => $label): ?>
                        <button class="explore-filter" type="button" data-explore-filter="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" aria-pressed="false"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
                    <?php endforeach; ?>
                </div>

                <div class="talent-grid" aria-live="polite">
                    <?php foreach ($talents as $talent): ?>
                        <?php
                        $initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $talent['name']), 0, 2));
                        $groupNames = $talent['group_names'];
                        $talentLink = 'pages/talent-merch.php?talent=' . rawurlencode($talent['name']);
                        ?>
                        <a class="talent-card-link" href="<?= htmlspecialchars($talentLink, ENT_QUOTES, 'UTF-8') ?>" aria-label="View merchandise for <?= htmlspecialchars($talent['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <article class="talent-card" data-generations="<?= htmlspecialchars(implode(' ', $talent['groups']), ENT_QUOTES, 'UTF-8') ?>">
                                <span class="talent-avatar">
                                    <span class="talent-initials" aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                    <img class="talent-photo" src="<?= htmlspecialchars($talent['portrait'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($talent['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" decoding="async">
                                </span>
                                <h3><?= htmlspecialchars($talent['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars($groupNames, ENT_QUOTES, 'UTF-8') ?></p>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </div>
                <p class="explore-empty hidden" role="status">No featured talents in this generation or group yet.</p>
            </div>
        </section>
        <footer class="footer">
            <section class="radar-signup" aria-labelledby="radar-title">
                <span class="radar-eyebrow">Holographic restock radar</span>
                <h2 id="radar-title">Never Miss a 15-Minute Sold-Out Drop</h2>
                <p>Sign up to receive 15-minute SMS &amp; email alerts prior to secret drops, anniversary commemoration<br class="radar-desktop-break"> merchandise releases, and warehouse clearance extras.</p>
                <p class="radar-summary" id="radar-summary">Alerts are ready when you save your email.</p>
                <form class="radar-form" id="radar-form">
                    <label class="sr-only" for="radar-email">Collector email address</label>
                    <input id="radar-email" name="email" type="email" autocomplete="email" placeholder="Enter your collector email address..." required>
                    <button type="submit">Join Radar Free</button>
                </form>
                <fieldset class="radar-preferences">
                    <legend class="sr-only">Choose drop alert categories</legend>
                    <label><input type="checkbox" name="branches[]" value="jp" checked> Hololive JP Drops</label>
                    <label><input type="checkbox" name="branches[]" value="en" checked> Hololive EN Drops</label>
                    <label><input type="checkbox" name="branches[]" value="dev-is"> DEV_IS Drops</label>
                </fieldset>
                <p class="radar-feedback" id="radar-feedback" role="status" aria-live="polite"></p>
            </section>

            <div class="footer-main">
                <div class="footer-about">
                    <a class="footer-brand" href="#top" aria-label="HoloVerse Store home">
                        <img src="assets/images/logo.png" alt="">
                        <span>holo<span>verse</span></span>
                        <small>Store</small>
                    </a>
                    <p>The dedicated international storefront for official Hololive production collectible merchandise. Officially authorized in partnership with COVER Corporation.</p>
                    <span class="footer-hubs">Tokyo&nbsp; | &nbsp;Los Angeles&nbsp; | &nbsp;Singapore Fulfillment Hubs</span>
                </div>

                <nav class="footer-links" aria-label="Merchandise links">
                    <h3>Merchandise</h3>
                    <a href="pages/flash_sale.php">Scale Figures (1/4, 1/7)</a>
                    <a href="pages/flash_sale.php">Commemorative Box Sets</a>
                    <a href="pages/flash_sale.php">Apparel &amp; Sukajan</a>
                    <a href="pages/flash_sale.php">Acrylic Stands &amp; Keychains</a>
                    <a href="pages/flash_sale.php">Official Plushies &amp; Mochiduru</a>
                </nav>

                <nav class="footer-links" aria-label="Customer care links">
                    <h3>Customer Care</h3>
                    <a href="pages/pre_order.php">Pre-Order Batch ETA Tracker</a>
                    <a href="pages/personalized.php">Shipping Rates &amp; Customs Info</a>
                    <a href="pages/personalized.php">Mint Condition Box Guarantee</a>
                    <a href="pages/collector_showcase.php">Anti-Scalper Verification</a>
                    <a href="pages/collector_showcase.php">NFC Chip Mobile Tap Support</a>
                </nav>

                <nav class="footer-links" aria-label="Corporate links">
                    <h3>COVER Corp Portal</h3>
                    <a href="pages/about-us.php">Talent Licensing Guidelines</a>
                    <a href="pages/about-us.php">Authenticity Guarantee</a>
                    <a href="pages/about-us.php">ISO 27001 Security</a>
                    <a href="pages/about-us.php">Privacy Policy &amp; Terms</a>
                    <a href="pages/about-us.php">Wholesale Partner Inquiries</a>
                </nav>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2016-2026 COVER Corp. / HoloVerse Store. All rights reserved.</p>
                <nav aria-label="Legal links">
                    <a href="pages/about-us.php">Terms of Service</a>
                    <span aria-hidden="true">&bull;</span>
                    <a href="pages/about-us.php">Privacy Notice</a>
                    <span aria-hidden="true">&bull;</span>
                    <a href="pages/about-us.php">Cookie Preferences</a>
                </nav>
            </div>
        </footer>
    </main>
    <script>
        const exploreFilters = document.querySelectorAll("[data-explore-filter]");
        const talentCardLinks = document.querySelectorAll(".talent-card-link");
        const exploreEmpty = document.querySelector(".explore-empty");
        const radarForm = document.querySelector("#radar-form");
        const radarEmailInput = document.querySelector("#radar-email");
        const radarFeedback = document.querySelector("#radar-feedback");
        const radarSummary = document.querySelector("#radar-summary");
        const radarStorageKey = "holoverse-radar-signup";

        const getSelectedBranches = () => [...document.querySelectorAll('input[name="branches[]"]:checked')].map((checkbox) => checkbox.value);

        const formatBranchNames = (branches) => {
            const labels = {
                jp: "JP",
                en: "EN",
                "dev-is": "DEV_IS",
            };

            return branches.map((branch) => labels[branch] || branch.toUpperCase());
        };

        const updateRadarSummary = () => {
            const email = radarEmailInput.value.trim();
            const branches = getSelectedBranches();
            const branchNames = formatBranchNames(branches);

            if (!email) {
                radarSummary.textContent = branchNames.length
                    ? `Radar is ready for ${branchNames.join(", ")} alerts.`
                    : "Choose a drop category to activate alerts.";
                return;
            }

            radarSummary.textContent = branchNames.length
                ? `Alerts active for ${branchNames.join(", ")} updates.`
                : "Select at least one alert category.";
        };

        const saveRadarProfile = () => {
            const email = radarEmailInput.value.trim();
            const selectedBranches = getSelectedBranches();

            if (!email || selectedBranches.length === 0) {
                return;
            }

            try {
                localStorage.setItem(radarStorageKey, JSON.stringify({ email, selectedBranches }));
            } catch (error) {
                console.warn("Could not save radar signup profile.", error);
            }
        };

        document.querySelectorAll(".talent-photo").forEach((photo) => {
            photo.addEventListener("error", () => photo.classList.add("hidden"), { once: true });
        });

        document.querySelectorAll(".drop-product-image").forEach((image) => {
            const showInitialFallback = () => {
                image.classList.add("hidden");
                image.nextElementSibling?.classList.remove("hidden");
            };
            image.addEventListener("error", showInitialFallback, { once: true });
            if (image.complete && image.naturalWidth === 0) {
                showInitialFallback();
            }
        });

        exploreFilters.forEach((filter) => {
            filter.addEventListener("click", () => {
                const selectedGeneration = filter.dataset.exploreFilter;
                let visibleTalents = 0;

                exploreFilters.forEach((button) => {
                    button.setAttribute("aria-pressed", String(button === filter));
                });

                talentCardLinks.forEach((cardLink) => {
                    const card = cardLink.querySelector(".talent-card");
                    const isVisible = selectedGeneration === "all" || card.dataset.generations.split(" ").includes(selectedGeneration);
                    cardLink.classList.toggle("hidden", !isVisible);
                    visibleTalents += Number(isVisible);
                });

                exploreEmpty.classList.toggle("hidden", visibleTalents > 0);
            });
        });

        try {
            const savedProfile = JSON.parse(localStorage.getItem(radarStorageKey) || "null");
            if (savedProfile?.email) {
                radarEmailInput.value = savedProfile.email;
                document.querySelectorAll('input[name="branches[]"]').forEach((checkbox) => {
                    checkbox.checked = Array.isArray(savedProfile.selectedBranches)
                        ? savedProfile.selectedBranches.includes(checkbox.value)
                        : checkbox.checked;
                });
                radarFeedback.textContent = `Welcome back! You are already on the radar for ${formatBranchNames(getSelectedBranches()).join(", ")} alerts.`;
            }
        } catch (error) {
            console.warn("Could not load radar signup profile.", error);
        }

        document.querySelectorAll('input[name="branches[]"]').forEach((checkbox) => {
            checkbox.addEventListener("change", () => {
                updateRadarSummary();
                if (radarEmailInput.value.trim()) {
                    saveRadarProfile();
                }
            });
        });

        radarEmailInput.addEventListener("input", () => {
            updateRadarSummary();
        });

        radarForm.addEventListener("submit", (event) => {
            event.preventDefault();
            const email = radarEmailInput.value.trim();
            const branches = getSelectedBranches();

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                radarFeedback.textContent = "Enter a valid collector email address to join the restock radar.";
                return;
            }

            if (branches.length === 0) {
                radarFeedback.textContent = "Select at least one drop category to stay in the loop.";
                return;
            }

            saveRadarProfile();
            radarFeedback.textContent = `You're on the radar! We'll alert you about ${formatBranchNames(branches).join(", ")} drops and restocks.`;
            radarForm.querySelector("button").textContent = "Saved";
            setTimeout(() => {
                radarForm.querySelector("button").textContent = "Join Radar Free";
            }, 2000);
            updateRadarSummary();
        });

        updateRadarSummary();
    </script>
    <?php
    $cartHref = 'pages/cart.php';
    include __DIR__ . '/app/views/components/cart-button.php';
    ?>
</body>

</html>