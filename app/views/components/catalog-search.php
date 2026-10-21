<?php
$catalogSearchHiddenFields = $catalogSearchHiddenFields ?? [];
$catalogSearchShowClear = $catalogSearchShowClear ?? true;
$catalogSearchClearHref = $catalogSearchAction;
$catalogSearchClassName = isset($catalogSearchClass) && is_string($catalogSearchClass)
    ? $catalogSearchClass
    : '';
$catalogSearchShowSuggestions = $catalogSearchClassName === 'site-header-search';
$catalogSearchIsFilter = in_array($catalogSearchClassName, ['filter-panel-search', 'talent-merch-filter-search'], true);
$catalogSearchClass = $catalogSearchClassName !== ''
    ? ' ' . store_escape($catalogSearchClassName)
    : '';
if ($catalogSearchHiddenFields) {
    $catalogSearchClearHref .= '?' . http_build_query($catalogSearchHiddenFields);
}
?>
<form class="catalog-search<?= $catalogSearchClass ?>" method="get" action="<?= store_escape($catalogSearchAction) ?>" role="search">
    <label class="sr-only" for="<?= store_escape($catalogSearchId) ?>"><?= store_escape($catalogSearchLabel) ?></label>
    <input id="<?= store_escape($catalogSearchId) ?>" name="search" type="search" value="<?= store_escape($catalogSearchValue) ?>" placeholder="<?= store_escape($catalogSearchPlaceholder) ?>"<?= $catalogSearchShowSuggestions ? ' aria-controls="' . store_escape($catalogSearchId) . '-suggestions" aria-expanded="false" autocomplete="off" data-catalog-search-input' : '' ?>>
    <?php if ($catalogSearchShowSuggestions): ?>
        <div class="catalog-search-suggestions" id="<?= store_escape($catalogSearchId) ?>-suggestions" aria-label="Search suggestions" hidden>
            <div class="catalog-search-suggestion-group" data-recent-searches hidden>
                <span class="catalog-search-suggestions-title">Recent searches</span>
                <div class="catalog-search-suggestion-list" data-recent-search-list></div>
            </div>
            <div class="catalog-search-suggestion-group" data-popular-searches>
                <span class="catalog-search-suggestions-title">Popular searches</span>
                <div class="catalog-search-suggestion-list">
                    <?php foreach (['plush', 'figure', 'T-shirt', 'keychain', 'acrylic stand'] as $suggestion): ?>
                        <button type="button" data-search-suggestion="<?= store_escape($suggestion) ?>"><?= store_escape($suggestion) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php foreach ($catalogSearchHiddenFields as $name => $value): ?>
        <input type="hidden" name="<?= store_escape((string) $name) ?>" value="<?= store_escape((string) $value) ?>">
    <?php endforeach; ?>
    <button type="submit" aria-label="<?= $catalogSearchIsFilter ? 'Filter products' : 'Search products' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m21 21-4.34-4.34" />
            <circle cx="11" cy="11" r="8" />
        </svg>
    </button>
    <?php if ($catalogSearchShowClear && $catalogSearchValue !== ''): ?>
        <a href="<?= store_escape($catalogSearchClearHref) ?>">Clear</a>
    <?php endif; ?>
</form>
