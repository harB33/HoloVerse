<section class="flash-mechanics" aria-labelledby="flash-mechanics-title">
    <div class="flash-mechanics-header">
        <span class="flash-mechanics-pill">Platform integrity</span>
        <h2 id="flash-mechanics-title">Flash Sale Mechanics</h2>
        <p>Flash sale listings use live catalog inventory, scheduled sale windows, session-protected cart actions, and stock validation.</p>
    </div>

    <div class="flash-mechanics-grid">
        <article class="flash-mechanics-card">
            <div class="flash-mechanics-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m7.5 4.27 9 5.15" />
                    <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                    <path d="m3.3 7 8.7 5 8.7-5" />
                    <path d="M12 22V12" />
                </svg>
            </div>
            <div class="flash-mechanics-meta">01 // LIVE INVENTORY</div>
            <h3>Current Stock, From the Catalog</h3>
            <p>Live listings are built from active discounted variants with available stock. Product names, prices, and quantities come from the store database.</p>
        </article>

        <article class="flash-mechanics-card">
            <div class="flash-mechanics-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="8" cy="21" r="1" />
                    <circle cx="19" cy="21" r="1" />
                    <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57L22 8H5" />
                </svg>
            </div>
            <div class="flash-mechanics-meta">02 // CART ACTION</div>
            <h3>Add to Cart, Not a Reservation</h3>
            <p>Adding a product places it in your active cart. Inventory is checked on submission; the cart does not reserve units or start a hold timer.</p>
        </article>

        <article class="flash-mechanics-card">
            <div class="flash-mechanics-icon icon-alert" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" />
                    <path d="m9 12 2 2 4-4" />
                </svg>
            </div>
            <div class="flash-mechanics-meta">03 // REQUEST VALIDATION</div>
            <h3>Protected Cart Updates</h3>
            <p>Cart submissions include a session CSRF token and are validated against the current variant and its available stock.</p>
        </article>

        <article class="flash-mechanics-card">
            <div class="flash-mechanics-icon icon-cycle" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
            <div class="flash-mechanics-meta">04 // SALE SCHEDULE</div>
            <h3>Scheduled Drops and Archive</h3>
            <p>Administrators schedule future sale windows. Upcoming drops appear in their time category and completed windows move into the archive automatically.</p>
        </article>
    </div>
</section>
