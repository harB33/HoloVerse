<nav class="admin-inventory-pagination" aria-label="Inventory pages">
    <span class="admin-inventory-page-summary">
        <?php if ($inventoryCount > 0): ?>
            <span>Showing <strong><?= number_format($offset + 1) ?>–<?= number_format(min($offset + $pageSize, $inventoryCount)) ?></strong> of <?= number_format($inventoryCount) ?> variants</span>
            <?php if ($pageCount > 1): ?><span class="admin-inventory-page-number">Page <?= number_format($currentPage) ?> of <?= number_format($pageCount) ?></span><?php endif; ?>
        <?php else: ?>
            <span>No variants to display</span>
        <?php endif; ?>
    </span>
    <?php if ($pageCount > 1): ?>
        <div class="admin-inventory-page-links">
            <?php if ($currentPage > 1): ?>
                <a href="?<?= store_escape(http_build_query(['page' => $currentPage - 1, 'search' => $inventorySearch])) ?>" aria-label="Previous inventory page">Previous</a>
            <?php endif; ?>
            <?php if ($currentPage < $pageCount): ?>
                <a href="?<?= store_escape(http_build_query(['page' => $currentPage + 1, 'search' => $inventorySearch])) ?>" aria-label="Next inventory page">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</nav>
