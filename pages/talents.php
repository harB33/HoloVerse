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

<body class="talents-page home-landing">
    <div class="page-shell">
        <?php require __DIR__ . '/../app/views/components/watermark.php'; ?>
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
                </div>
            </section>

            <div class="products-catalog-layout talent-directory-layout">
                <aside class="generation-filter talent-directory-filter" aria-labelledby="generation-filter-title">
                    <form class="catalog-search filter-panel-search talent-search-wrap talent-directory-search" role="search">
                        <label class="sr-only" for="talent-search">Search talents or groups</label>
                        <input class="talent-search-input" id="talent-search" type="search" placeholder="Search talents or groups..." autocomplete="off">
                        <button type="submit" aria-label="Search talents">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m21 21-4.34-4.34" />
                                <circle cx="11" cy="11" r="8" />
                            </svg>
                        </button>
                    </form>
                    <h2 class="generation-filter-title" id="generation-filter-title">Generation</h2>
                    <div class="generation-filter-list explore-filters" role="group" aria-label="Filter talents by generation or group">
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
                                <figure class="talent-poster-card" data-generations="<?= store_escape(implode(' ', $talent['groups'])) ?>">
                                    <div class="talent-poster-media">
                                        <span class="talent-poster-initials" aria-hidden="true"><?= store_escape($talentInitials) ?></span>
                                        <?php if ($portraitUrl !== ''): ?>
                                            <img class="talent-photo talent-poster-photo" src="<?= store_escape($portraitUrl) ?>" alt="<?= store_escape($talent['name']) ?>" loading="lazy" decoding="async">
                                        <?php endif; ?>
                                        <figcaption class="talent-poster-caption">
                                            <span><?= store_escape($talent['group_names'] ?: 'hololive production') ?></span>
                                            <strong><?= store_escape($talent['name']) ?></strong>
                                        </figcaption>
                                    </div>
                                </figure>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <p class="all-products-empty explore-empty hidden" role="status">No talents match your search and selected group.</p>
                    <?php
                    $talentsPerPage = 20;
                    $totalTalentsCount = count($talents);
                    $initialPageCount = max(1, (int) ceil($totalTalentsCount / $talentsPerPage));
                    $initialPage = min($initialPageCount, max(1, (int) ($_GET['page'] ?? 1)));
                    $delta = 1;
                    $range = [];
                    $rangeWithDots = [];
                    for ($i = 1; $i <= $initialPageCount; $i++) {
                        if ($i === 1 || $i === $initialPageCount || ($i >= $initialPage - $delta && $i <= $initialPage + $delta)) {
                            $range[] = $i;
                        }
                    }
                    $lastPageNum = 0;
                    foreach ($range as $pageItem) {
                        if ($lastPageNum > 0 && $pageItem - $lastPageNum > 1) {
                            $rangeWithDots[] = '...';
                        }
                        $rangeWithDots[] = $pageItem;
                        $lastPageNum = $pageItem;
                    }
                    $buildTalentPageUrl = static fn (int $pageNum): string => '/talents' . ($pageNum > 1 ? '?page=' . $pageNum : '');
                    ?>
                    <nav class="all-products-pagination talent-pagination<?= $initialPageCount <= 1 ? ' hidden' : '' ?>" aria-label="Talent pages">
                        <?php if ($initialPage > 1): ?>
                            <a class="all-products-page-button" href="<?= store_escape($buildTalentPageUrl($initialPage - 1)) ?>" data-talent-page="<?= $initialPage - 1 ?>" aria-label="Previous page">Prev</a>
                        <?php else: ?>
                            <span class="all-products-page-button is-disabled" aria-hidden="true">Prev</span>
                        <?php endif; ?>

                        <div class="all-products-page-list">
                            <?php foreach ($rangeWithDots as $pageDot): ?>
                                <?php if ($pageDot === '...'): ?>
                                    <span class="all-products-page-ellipsis" aria-hidden="true">&hellip;</span>
                                <?php elseif ((int) $pageDot === $initialPage): ?>
                                    <span class="all-products-page-number is-active" aria-current="page"><?= $pageDot ?></span>
                                <?php else: ?>
                                    <a class="all-products-page-number" href="<?= store_escape($buildTalentPageUrl((int) $pageDot)) ?>" data-talent-page="<?= (int) $pageDot ?>" aria-label="Page <?= $pageDot ?>"><?= $pageDot ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($initialPage < $initialPageCount): ?>
                            <a class="all-products-page-button" href="<?= store_escape($buildTalentPageUrl($initialPage + 1)) ?>" data-talent-page="<?= $initialPage + 1 ?>" aria-label="Next page">Next</a>
                        <?php else: ?>
                            <span class="all-products-page-button is-disabled" aria-hidden="true">Next</span>
                        <?php endif; ?>
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
        const talentTotalCount = document.querySelector('[data-talent-total-count]');
        const talentsPerPage = 20;
        let currentPage = <?= (int) $initialPage ?>;
        const availableGroups = new Set(
            Array.from(exploreFilters, (filter) => filter.dataset.exploreFilter)
        );
        const requestedGroup = new URLSearchParams(window.location.search).get('group');
        let selectedGeneration = availableGroups.has(requestedGroup) ? requestedGroup : 'all';

        const buildTalentPageHref = (pageNum) => {
            const url = new URL(window.location.href);
            if (pageNum <= 1) {
                url.searchParams.delete('page');
            } else {
                url.searchParams.set('page', String(pageNum));
            }
            if (selectedGeneration === 'all') {
                url.searchParams.delete('group');
            } else {
                url.searchParams.set('group', selectedGeneration);
            }
            return `${url.pathname}${url.search}`;
        };

        const renderTalentPagination = (page, pageCount) => {
            if (!talentPagination) {
                return;
            }
            talentPagination.classList.toggle('hidden', pageCount <= 1);
            if (pageCount <= 1) {
                talentPagination.innerHTML = '';
                return;
            }

            const delta = 1;
            const range = [];
            const rangeWithDots = [];
            for (let i = 1; i <= pageCount; i += 1) {
                if (i === 1 || i === pageCount || (i >= page - delta && i <= page + delta)) {
                    range.push(i);
                }
            }
            let lastPageNum = 0;
            range.forEach((pageItem) => {
                if (lastPageNum > 0 && pageItem - lastPageNum > 1) {
                    rangeWithDots.push('...');
                }
                rangeWithDots.push(pageItem);
                lastPageNum = pageItem;
            });

            const prevHtml = page > 1
                ? `<a class="all-products-page-button" href="${buildTalentPageHref(page - 1)}" data-talent-page="${page - 1}" aria-label="Previous page">Prev</a>`
                : `<span class="all-products-page-button is-disabled" aria-hidden="true">Prev</span>`;

            const listHtml = rangeWithDots.map((pageDot) => {
                if (pageDot === '...') {
                    return `<span class="all-products-page-ellipsis" aria-hidden="true">&hellip;</span>`;
                }
                if (pageDot === page) {
                    return `<span class="all-products-page-number is-active" aria-current="page">${pageDot}</span>`;
                }
                return `<a class="all-products-page-number" href="${buildTalentPageHref(pageDot)}" data-talent-page="${pageDot}" aria-label="Page ${pageDot}">${pageDot}</a>`;
            }).join('');

            const nextHtml = page < pageCount
                ? `<a class="all-products-page-button" href="${buildTalentPageHref(page + 1)}" data-talent-page="${page + 1}" aria-label="Next page">Next</a>`
                : `<span class="all-products-page-button is-disabled" aria-hidden="true">Next</span>`;

            talentPagination.innerHTML = `${prevHtml}<div class="all-products-page-list">${listHtml}</div>${nextHtml}`;
        };

        const updateTalentResults = (resetPage = false, updateHistory = false) => {
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
            renderTalentPagination(currentPage, pageCount);

            if (updateHistory) {
                window.history.pushState({ page: currentPage }, '', buildTalentPageHref(currentPage));
            }
        };

        exploreFilters.forEach((filter) => filter.addEventListener('click', () => {
            selectedGeneration = filter.dataset.exploreFilter;
            exploreFilters.forEach((button) => {
                const active = button === filter;
                button.setAttribute('aria-pressed', String(active));
                button.classList.toggle('is-active', active);
            });
            updateTalentResults(true, true);
        }));
        talentSearch.addEventListener('input', () => updateTalentResults(true, false));
        talentSearchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            updateTalentResults(true, true);
        });
        talentPagination?.addEventListener('click', (event) => {
            const pageLink = event.target instanceof Element ? event.target.closest('a[data-talent-page]') : null;
            if (!pageLink) {
                return;
            }
            event.preventDefault();
            const targetPage = Number(pageLink.dataset.talentPage);
            if (Number.isFinite(targetPage) && targetPage >= 1 && targetPage !== currentPage) {
                currentPage = targetPage;
                updateTalentResults(false, true);
            }
        });
        window.addEventListener('popstate', () => {
            const url = new URL(window.location.href);
            const urlPage = Number(url.searchParams.get('page')) || 1;
            const urlGroup = url.searchParams.get('group');
            selectedGeneration = availableGroups.has(urlGroup) ? urlGroup : 'all';
            exploreFilters.forEach((filter) => {
                const active = filter.dataset.exploreFilter === selectedGeneration;
                filter.setAttribute('aria-pressed', String(active));
                filter.classList.toggle('is-active', active);
            });
            currentPage = Math.max(1, urlPage);
            updateTalentResults(false, false);
        });
        exploreFilters.forEach((filter) => {
            const active = filter.dataset.exploreFilter === selectedGeneration;
            filter.setAttribute('aria-pressed', String(active));
            filter.classList.toggle('is-active', active);
        });
        updateTalentResults(true, false);

        document.querySelectorAll('.talent-photo').forEach((image) => image.addEventListener('error', () => {
            image.classList.add('hidden');
        }, { once: true }));
    </script>
    <?php
    $cartHref = 'cart';
    include __DIR__ . '/../app/views/components/cart-button.php';
    ?>
    <script src="../assets/js/talents-page.js?v=<?= filemtime(__DIR__ . '/../assets/js/talents-page.js') ?>" defer></script>
</body>

</html>
