<?php
require_once __DIR__ . '/../app/store.php';

$groupOrder = STORE_TALENT_FILTER_GROUP_SLUGS;
$groupArraySql = "ARRAY['" . implode("','", $groupOrder) . "']::text[]";
$groupsResult = $conn->query(
    "SELECT group_slug, group_name
     FROM talent_group_tbl
     ORDER BY array_position({$groupArraySql}, group_slug)"
);
$generationLabels = [];
while ($group = $groupsResult->fetch_assoc()) {
    $generationLabels[$group['group_slug']] = $group['group_name'];
}

$talents = [];
$talentResult = $conn->query(
    "SELECT t.talent_name, t.talent_slug, t.talent_img,
            STRING_AGG(g.group_slug, ' ' ORDER BY array_position({$groupArraySql}, g.group_slug)) AS group_slugs
     FROM talent_tbl t
     LEFT JOIN talent_group_members_tbl gm ON gm.talent_id = t.talent_id
     LEFT JOIN talent_group_tbl g ON g.group_id = gm.group_id
     GROUP BY t.talent_id, t.talent_name, t.talent_slug, t.talent_img
     ORDER BY t.talent_name"
);
while ($talent = $talentResult->fetch_assoc()) {
    $groupSlugs = $talent['group_slugs'] ? explode(' ', $talent['group_slugs']) : [];
    $groupNames = array_map(static fn (string $slug): string => $generationLabels[$slug] ?? $slug, $groupSlugs);
    if ($talent['talent_name'] === 'Watson Amelia') {
        $groupNames[] = 'Affiliate';
    }
    $talents[] = [
        'name' => $talent['talent_name'],
        'slug' => $talent['talent_slug'],
        'groups' => $groupSlugs,
        'group_names' => implode(' · ', $groupNames),
        'portrait' => $talent['talent_img'],
    ];
}

$pageTitle = 'Talents | HoloVerse';
$siteRoot = '../';
require __DIR__ . '/../app/views/layout/head.php';
?>

<body>
    <div class="page-shell">
        <?php
        $activePage = 'talents';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <main class="all-products-main landing-shell talent-directory-main" id="explore" aria-labelledby="explore-title">
            <section class="all-products-intro">
                <div class="all-products-intro-top">
                    <div>
                        <p class="eyebrow">Talent Vault Directory</p>
                        <h1 id="explore-title">Explore merchandise by oshi</h1>
                        <p>Filter official apparel, plushies, voice packs, and figures directly tied to your favorite hololive production talent.</p>
                    </div>
                    <div class="all-products-intro-actions">
                        <span class="all-products-count" data-talent-total-count><?= count($talents) ?> talents</span>
                    </div>
                </div>
            </section>

            <div class="products-catalog-layout talent-directory-layout">
                <aside class="generation-filter talent-directory-filter" aria-label="Filter talents">
                    <form class="catalog-search talent-search-wrap talent-directory-search" role="search">
                        <label class="sr-only" for="talent-search">Search talents or groups</label>
                        <input class="talent-search-input" id="talent-search" type="search" placeholder="Search talents or groups..." autocomplete="off">
                        <button type="submit" aria-label="Search talents">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m21 21-4.34-4.34" />
                                <circle cx="11" cy="11" r="8" />
                            </svg>
                        </button>
                    </form>
                    <h2>Filter by generation</h2>
                    <div class="explore-filters" role="group" aria-label="Filter talents by generation or group">
                        <button class="generation-filter-option explore-filter is-active" type="button" data-explore-filter="all" aria-pressed="true">All Generations</button>
                        <?php foreach ($generationLabels as $slug => $label): ?>
                            <button class="generation-filter-option explore-filter" type="button" data-explore-filter="<?= store_escape($slug) ?>" aria-pressed="false"><?= store_escape($label) ?></button>
                        <?php endforeach; ?>
                    </div>
                </aside>

                <div class="talent-directory-results">
                    <div class="talent-poster-grid" aria-live="polite">
                        <?php foreach ($talents as $talent): ?>
                            <?php
                            $talentLink = '/talent-merch/' . rawurlencode($talent['slug']);
                            $talentInitials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $talent['name']), 0, 2));
                            $portraitUrl = !empty($talent['portrait'])
                                ? store_asset_url((string) $talent['portrait'], $siteRoot)
                                : '';
                            ?>
                            <a class="talent-card-link" href="<?= store_escape($talentLink) ?>" aria-label="View merchandise for <?= store_escape($talent['name']) ?>">
                                <article class="talent-poster-card" data-generations="<?= store_escape(implode(' ', $talent['groups'])) ?>">
                                    <span class="talent-poster-initials" aria-hidden="true"><?= store_escape($talentInitials) ?></span>
                                    <?php if ($portraitUrl !== ''): ?>
                                        <img class="talent-photo talent-poster-photo" src="<?= store_escape($portraitUrl) ?>" alt="<?= store_escape($talent['name']) ?>" loading="lazy" decoding="async">
                                    <?php endif; ?>
                                    <div class="talent-poster-overlay">
                                        <span><?= store_escape($talent['group_names'] ?: 'hololive production') ?></span>
                                        <h3><?= store_escape($talent['name']) ?></h3>
                                        <small>
                                            Shop merch
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                        </small>
                                    </div>
                                </article>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <p class="all-products-empty explore-empty hidden" role="status">No talents match your search and selected group.</p>
                    <nav class="all-products-pagination talent-pagination hidden" aria-label="Talent pages">
                        <button type="button" data-talent-page-previous aria-label="Previous page">Previous</button>
                        <span data-talent-page-status aria-live="polite"></span>
                        <button type="button" data-talent-page-next aria-label="Next page">Next</button>
                    </nav>
                </div>
            </div>
        </main>

        <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    </div>

    <script>
        const exploreFilters = document.querySelectorAll('[data-explore-filter]');
        const talentCards = document.querySelectorAll('.talent-card-link');
        const exploreEmpty = document.querySelector('.explore-empty');
        const talentSearch = document.querySelector('#talent-search');
        const talentSearchForm = document.querySelector('.talent-directory-search');
        const talentPagination = document.querySelector('.talent-pagination');
        const talentPageStatus = document.querySelector('[data-talent-page-status]');
        const previousPageButton = document.querySelector('[data-talent-page-previous]');
        const nextPageButton = document.querySelector('[data-talent-page-next]');
        const talentTotalCount = document.querySelector('[data-talent-total-count]');
        const talentsPerPage = 24;
        let currentPage = 1;
        let selectedGeneration = 'all';
        const updateTalentResults = (resetPage = false) => {
            if (resetPage) {
                currentPage = 1;
            }

            const query = talentSearch.value.trim().toLocaleLowerCase();
            const matchingTalents = [];
            talentCards.forEach((link) => {
                const card = link.querySelector('.talent-poster-card');
                const groups = (card?.dataset.generations || '').split(' ');
                const matchesGeneration = selectedGeneration === 'all' || groups.includes(selectedGeneration);
                const matchesSearch = (card?.textContent || '').toLocaleLowerCase().includes(query);
                if (matchesGeneration && matchesSearch) {
                    matchingTalents.push(link);
                }
                link.classList.add('hidden');
            });

            const pageCount = Math.ceil(matchingTalents.length / talentsPerPage);
            currentPage = Math.min(currentPage, Math.max(pageCount, 1));
            const pageStart = (currentPage - 1) * talentsPerPage;
            matchingTalents.slice(pageStart, pageStart + talentsPerPage).forEach((link) => {
                link.classList.remove('hidden');
            });

            if (talentTotalCount) {
                talentTotalCount.textContent = `${matchingTalents.length} ${matchingTalents.length === 1 ? 'talent' : 'talents'}`;
            }

            exploreEmpty.classList.toggle('hidden', matchingTalents.length > 0);
            talentPagination.classList.toggle('hidden', pageCount <= 1);
            talentPageStatus.textContent = matchingTalents.length > 0
                ? `Page ${currentPage} of ${pageCount}`
                : '';
            previousPageButton.disabled = currentPage <= 1;
            nextPageButton.disabled = currentPage >= pageCount;
        };
        exploreFilters.forEach((filter) => filter.addEventListener('click', () => {
            selectedGeneration = filter.dataset.exploreFilter;
            exploreFilters.forEach((button) => {
                const active = button === filter;
                button.setAttribute('aria-pressed', String(active));
                button.classList.toggle('is-active', active);
            });
            updateTalentResults(true);
        }));
        talentSearch.addEventListener('input', () => updateTalentResults(true));
        talentSearchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            updateTalentResults(true);
        });
        previousPageButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage -= 1;
                updateTalentResults();
            }
        });
        nextPageButton.addEventListener('click', () => {
            currentPage += 1;
            updateTalentResults();
        });
        updateTalentResults();

        document.querySelectorAll('.talent-photo').forEach((image) => image.addEventListener('error', () => {
            image.classList.add('hidden');
        }, { once: true }));
    </script>
    <?php
    $cartHref = 'cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
</body>

</html>
