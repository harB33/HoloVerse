<section class="flash-telemetry" aria-labelledby="flash-telemetry-title">
    <div class="flash-telemetry-heading">
        <h2 id="flash-telemetry-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
            </svg>
            Flash sale status &amp; cart information
        </h2>
        <time data-system-clock data-timezone="<?= store_escape($flashSaleTimezone->getName()) ?>" datetime="<?= store_escape($flashSaleNow->format(DateTimeInterface::ATOM)) ?>">System clock: <?= store_escape($flashSaleNow->format('Y-m-d H:i:s')) ?></time>
    </div>
    <div class="flash-telemetry-grid">
        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label">
                <span>Cart request protection</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" />
                    <path d="m9 12 2 2 4-4" />
                </svg>
            </div>
            <strong>CSRF validation enabled</strong>
            <p>Cart updates require a session token and a valid in-stock product.</p>
            <span class="flash-telemetry-badge">Server checked</span>
        </article>

        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label">
                <span>Your cart</span>
                <svg class="flash-telemetry-icon-red" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 11-1 9" />
                    <path d="m19 11-4-7" />
                    <path d="M2 11h20" />
                    <path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4" />
                    <path d="M4.5 15.5h15" />
                    <path d="m5 11 4-7" />
                    <path d="m9 11 1 9" />
                </svg>
            </div>
            <strong><?= $activeCartQuantity ?> <small>items</small></strong>
            <p>Items in this browser's active cart. Adding to cart does not reserve stock.</p>
            <a class="flash-telemetry-badge no-underline" href="cart">View cart</a>
        </article>

        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label">
                <span>Scheduled drops</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M8 2v4" />
                    <path d="M16 2v4" />
                    <rect width="18" height="18" x="3" y="4" rx="2" />
                    <path d="M3 10h18" />
                    <path d="M8 14h.01" />
                    <path d="M12 14h.01" />
                    <path d="M16 14h.01" />
                    <path d="M8 18h.01" />
                    <path d="M12 18h.01" />
                    <path d="M16 18h.01" />
                </svg>
            </div>
            <strong><?= count($flashSaleProducts['starting'] ?? []) + count($flashSaleProducts['tomorrow'] ?? []) ?> <small>upcoming</small></strong>
            <p>Upcoming sale windows are loaded from the admin schedule.</p>
            <span class="flash-telemetry-footnote"><i aria-hidden="true"></i> No queue simulation</span>
        </article>

        <article class="flash-telemetry-card">
            <div class="flash-telemetry-label">
                <span>Inventory check</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m7.5 4.27 9 5.15" />
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                    <path d="m3.3 7 8.7 5 8.7-5" />
                    <path d="M12 22V12" />
                </svg>
            </div>
            <strong>Checked at add</strong>
            <p>Stock is verified when the cart request is submitted.</p>
            <span class="flash-telemetry-badge">Live catalog</span>
        </article>
    </div>
</section>
