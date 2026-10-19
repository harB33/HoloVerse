<?php
$catalogSearchHiddenFields = $catalogSearchHiddenFields ?? [];
$catalogSearchShowClear = $catalogSearchShowClear ?? true;
$catalogSearchClearHref = $catalogSearchAction;
$catalogSearchClass = isset($catalogSearchClass) && is_string($catalogSearchClass)
    ? ' ' . store_escape($catalogSearchClass)
    : '';
if ($catalogSearchHiddenFields) {
    $catalogSearchClearHref .= '?' . http_build_query($catalogSearchHiddenFields);
}
?>
<form class="catalog-search<?= $catalogSearchClass ?>" method="get" action="<?= store_escape($catalogSearchAction) ?>" role="search">
    <label class="sr-only" for="<?= store_escape($catalogSearchId) ?>"><?= store_escape($catalogSearchLabel) ?></label>
    <input id="<?= store_escape($catalogSearchId) ?>" name="search" type="search" value="<?= store_escape($catalogSearchValue) ?>" placeholder="<?= store_escape($catalogSearchPlaceholder) ?>">
    <?php foreach ($catalogSearchHiddenFields as $name => $value): ?>
        <input type="hidden" name="<?= store_escape((string) $name) ?>" value="<?= store_escape((string) $value) ?>">
    <?php endforeach; ?>
    <button type="submit" aria-label="Search products">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m21 21-4.34-4.34" />
            <circle cx="11" cy="11" r="8" />
        </svg>
    </button>
    <?php if ($catalogSearchShowClear && $catalogSearchValue !== ''): ?>
        <a href="<?= store_escape($catalogSearchClearHref) ?>">Clear</a>
    <?php endif; ?>
</form>
