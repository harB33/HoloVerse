<?php
$catalogSearchHiddenFields = $catalogSearchHiddenFields ?? [];
$catalogSearchClearHref = $catalogSearchAction;
if ($catalogSearchHiddenFields) {
    $catalogSearchClearHref .= '?' . http_build_query($catalogSearchHiddenFields);
}
?>
<form class="catalog-search" method="get" action="<?= store_escape($catalogSearchAction) ?>" role="search">
    <label class="sr-only" for="<?= store_escape($catalogSearchId) ?>"><?= store_escape($catalogSearchLabel) ?></label>
    <input id="<?= store_escape($catalogSearchId) ?>" name="search" type="search" value="<?= store_escape($catalogSearchValue) ?>" placeholder="<?= store_escape($catalogSearchPlaceholder) ?>">
    <?php foreach ($catalogSearchHiddenFields as $name => $value): ?>
        <input type="hidden" name="<?= store_escape((string) $name) ?>" value="<?= store_escape((string) $value) ?>">
    <?php endforeach; ?>
    <button type="submit">Search</button>
    <?php if ($catalogSearchValue !== ''): ?>
        <a href="<?= store_escape($catalogSearchClearHref) ?>">Clear</a>
    <?php endif; ?>
</form>
