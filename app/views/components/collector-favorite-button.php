<?php
$favoriteButtonProduct = is_array($favoriteButtonProduct ?? null) ? $favoriteButtonProduct : $product;
$favoriteProductId = (int) $favoriteButtonProduct['product_id'];
$isFavorite = in_array($favoriteProductId, $favoriteProductIds ?? [], true);
$favoriteFormModifier = is_string($favoriteFormModifier ?? null) ? $favoriteFormModifier : '';
$favoriteShowcaseUrl = store_collector_showcase_url();
?>
<form class="collector-favorite-form<?= $favoriteFormModifier !== '' ? ' ' . store_escape($favoriteFormModifier) : '' ?>" action="<?= store_escape($favoriteShowcaseUrl) ?>" method="post" data-favorite-form>
    <input type="hidden" name="csrf_token" value="<?= store_escape(store_csrf_token()) ?>">
    <input type="hidden" name="product_id" value="<?= $favoriteProductId ?>">
    <input type="hidden" name="action" value="<?= $isFavorite ? 'remove' : 'save' ?>">
    <button class="collector-favorite-button<?= $isFavorite ? ' is-favorite' : '' ?>" type="submit" aria-label="<?= $isFavorite ? 'Remove ' : 'Add ' ?><?= store_escape($favoriteButtonProduct['product_name']) ?> <?= $isFavorite ? 'from' : 'to' ?> your Collector Showcase" aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>" title="<?= $isFavorite ? 'Remove from Collector Showcase' : 'Save to Collector Showcase' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z" />
        </svg>
    </button>
</form>
