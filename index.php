<?php
require_once __DIR__ . '/app/store.php';
$generationLabels = [];
$groupsResult = $conn->query(
    "SELECT group_slug, group_name FROM talent_group_tbl
     ORDER BY array_position(
         ARRAY['gen-0', 'gen-1', 'gamers', 'gen-2', 'gen-3', 'gen-4', 'gen-5', 'holox', 'regloss', 'flow-glow', 'myth', 'promise', 'advent', 'justice', 'id-gen-1', 'id-gen-2', 'id-gen-3', 'asobi-mawaritai']::text[],
         group_slug
     )"
);
while ($group = $groupsResult->fetch_assoc()) {
    $generationLabels[$group['group_slug']] = $group['group_name'];
}

$talents = [];
$talentStatuses = ['Watson Amelia' => 'Affiliate'];
$talentResult = $conn->query(
    "SELECT t.talent_name, t.talent_img,
            STRING_AGG(
                g.group_slug,
                ' ' ORDER BY array_position(
                    ARRAY['gen-0', 'gen-1', 'gamers', 'gen-2', 'gen-3', 'gen-4', 'gen-5', 'holox', 'regloss', 'flow-glow', 'myth', 'promise', 'advent', 'justice', 'id-gen-1', 'id-gen-2', 'id-gen-3', 'asobi-mawaritai']::text[],
                    g.group_slug
                )
            ) AS group_slugs
     FROM talent_tbl t
     LEFT JOIN talent_group_members_tbl gm ON gm.talent_id = t.talent_id
     LEFT JOIN talent_group_tbl g ON g.group_id = gm.group_id
     GROUP BY t.talent_id, t.talent_name, t.talent_img
     ORDER BY t.talent_name"
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
        'portrait' => $talent['talent_img'],
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
                <a class="drops-view-all" href="pages/products.php">
                    View all products
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                        <path d="m12 5 7 7-7 7" />
                    </svg>
                </a>
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
                                        <input type="hidden" name="variant_id" value="<?= (int) $product['variant_id'] ?>">
                                        <button type="submit" name="action" value="add">Add to cart</button>
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
        <?php $showRadarSignup = true; require __DIR__ . '/app/views/components/site-footer.php'; ?>
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
            const handlePortraitError = () => {
                const fallbackSrc = photo.src.replace(/_list_thumb(?=\.png(?:[?#]|$))/, "_talent_list_thumb");
                if (!photo.dataset.fallbackAttempted && fallbackSrc !== photo.src) {
                    photo.dataset.fallbackAttempted = "true";
                    photo.src = fallbackSrc;
                    return;
                }
                photo.classList.add("hidden");
            };
            photo.addEventListener("error", handlePortraitError);
            if (photo.complete && photo.naturalWidth === 0) {
                handlePortraitError();
            }
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