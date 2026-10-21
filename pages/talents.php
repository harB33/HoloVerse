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
    "SELECT t.talent_name, t.talent_img,
            STRING_AGG(g.group_slug, ' ' ORDER BY array_position({$groupArraySql}, g.group_slug)) AS group_slugs
     FROM talent_tbl t
     LEFT JOIN talent_group_members_tbl gm ON gm.talent_id = t.talent_id
     LEFT JOIN talent_group_tbl g ON g.group_id = gm.group_id
     GROUP BY t.talent_id, t.talent_name, t.talent_img
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
    <main class="page-shell">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
        <?php
        $activePage = 'talents';
        require __DIR__ . '/../app/views/components/site-header.php';
        ?>

        <section class="explore talent-directory-explore" id="explore" aria-labelledby="explore-title">
            <div class="explore-inner">
                <header class="explore-header">
                    <span class="explore-eyebrow">Talent Vault Directory</span>
                    <h1 id="explore-title">Explore Merchandise by Oshi</h1>
                    <p>Filter official apparel, plushies, voice packs, and figures directly tied to your favorite talent.</p>
                </header>
                <div class="talent-directory-layout">
                    <aside class="generation-filter talent-directory-filter" aria-label="Filter talents">
                        <form class="catalog-search talent-search-wrap talent-directory-search" role="search">
                            <label class="sr-only" for="talent-search">Search talents or groups</label>
                            <input class="talent-search-input" id="talent-search" type="search" placeholder="Search talents or groups" autocomplete="off">
                            <button type="submit" aria-label="Search talents">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m21 21-4.34-4.34" />
                                    <circle cx="11" cy="11" r="8" />
                                </svg>
                            </button>
                        </form>
                        <h2>Filter by generation</h2>
                        <div class="explore-filters" role="group" aria-label="Filter talents by generation or group">
                            <button class="explore-filter" type="button" data-explore-filter="all" aria-pressed="true">All Generations</button>
                            <?php foreach ($generationLabels as $slug => $label): ?>
                                <button class="explore-filter" type="button" data-explore-filter="<?= store_escape($slug) ?>" aria-pressed="false"><?= store_escape($label) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                    <div class="talent-directory-results">
                        <div class="talent-grid" aria-live="polite">
                            <?php foreach ($talents as $talent): ?>
                                <?php $talentLink = 'talent-merch.php?talent=' . rawurlencode($talent['name']); ?>
                                <a class="talent-card-link" href="<?= store_escape($talentLink) ?>" aria-label="View merchandise for <?= store_escape($talent['name']) ?>">
                                    <article class="talent-card" data-generations="<?= store_escape(implode(' ', $talent['groups'])) ?>">
                                        <span class="talent-avatar">
                                            <span class="talent-initials" aria-hidden="true"><?= store_escape(strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $talent['name']), 0, 2))) ?></span>
                                            <img class="talent-photo talent-photo-backdrop" src="<?= store_escape($talent['portrait']) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
                                            <img class="talent-photo talent-photo-foreground" src="<?= store_escape($talent['portrait']) ?>" alt="<?= store_escape($talent['name']) ?>" loading="lazy" decoding="async">
                                        </span>
                                        <h3><?= store_escape($talent['name']) ?></h3>
                                        <p><?= store_escape($talent['group_names']) ?></p>
                                    </article>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <p class="explore-empty hidden" role="status">No talents match your search and selected group.</p>
                    </div>
                </div>
            </div>
        </section>
        <?php require __DIR__ . '/../app/views/components/site-footer.php'; ?>
    </main>

    <script>
        const exploreFilters = document.querySelectorAll('[data-explore-filter]');
        const talentCards = document.querySelectorAll('.talent-card-link');
        const exploreEmpty = document.querySelector('.explore-empty');
        const talentSearch = document.querySelector('#talent-search');
        const talentSearchForm = document.querySelector('.talent-directory-search');
        let selectedGeneration = 'all';
        const updateTalentResults = () => {
            const query = talentSearch.value.trim().toLocaleLowerCase();
            let visible = 0;
            talentCards.forEach((link) => {
                const card = link.querySelector('.talent-card');
                const groups = card.dataset.generations.split(' ');
                const matchesGeneration = selectedGeneration === 'all' || groups.includes(selectedGeneration);
                const matchesSearch = card.textContent.toLocaleLowerCase().includes(query);
                const show = matchesGeneration && matchesSearch;
                link.classList.toggle('hidden', !show);
                visible += Number(show);
            });
            exploreEmpty.classList.toggle('hidden', visible > 0);
        };
        exploreFilters.forEach((filter) => filter.addEventListener('click', () => {
            selectedGeneration = filter.dataset.exploreFilter;
            exploreFilters.forEach((button) => button.setAttribute('aria-pressed', String(button === filter)));
            updateTalentResults();
        }));
        talentSearch.addEventListener('input', updateTalentResults);
        talentSearchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            updateTalentResults();
        });

        document.querySelectorAll('.talent-photo').forEach((image) => image.addEventListener('error', () => {
            image.classList.add('hidden');
        }, { once: true }));
    </script>
    <?php
    $cartHref = 'cart.php';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
</body>

</html>
