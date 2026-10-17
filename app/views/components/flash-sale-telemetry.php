<section class="flash-telemetry" aria-labelledby="flash-telemetry-title">
    <div class="flash-telemetry-heading">
        <h2 id="flash-telemetry-title"><span aria-hidden="true"></span> Flash sale status &amp; cart information</h2>
        <span>System clock: <?= store_escape($flashSaleNow->format(DateTimeInterface::ATOM)) ?></span>
    </div>
    <div class="flash-telemetry-grid">
        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label"><span>Cart request protection</span><span aria-hidden="true">♢</span></div>
            <strong>CSRF validation enabled</strong>
            <p>Cart updates require a session token and a valid in-stock product.</p>
            <span class="flash-telemetry-badge">Server checked</span>
        </article>

        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label"><span>Your cart</span><span class="flash-telemetry-icon-red" aria-hidden="true">◷</span></div>
            <strong><?= $activeCartQuantity ?> <small>items</small></strong>
            <p>Items in this browser's active cart. Adding to cart does not reserve stock.</p>
            <a class="flash-telemetry-badge no-underline" href="cart.php">View cart</a>
        </article>

        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label"><span>Scheduled drops</span><span aria-hidden="true">♙</span></div>
            <strong><?= count($flashSaleProducts['starting']) + count($flashSaleProducts['tomorrow']) ?> <small>upcoming</small></strong>
            <p>Upcoming sale windows are loaded from the admin schedule.</p>
            <span class="flash-telemetry-footnote"><i aria-hidden="true"></i> No queue simulation</span>
        </article>

        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label"><span>Inventory check</span><span aria-hidden="true">♢</span></div>
            <strong>Checked at add</strong>
            <p>Stock is verified when the cart request is submitted.</p>
            <span class="flash-telemetry-badge">Live catalog</span>
        </article>
    </div>
</section>
