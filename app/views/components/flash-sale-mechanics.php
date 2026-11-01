<section class="flash-mechanics" aria-labelledby="flash-mechanics-title">
    <div class="section-heading flash-mechanics-heading">
        <div>
            <p class="eyebrow">Good to know &middot; Drop mechanics</p>
            <h2 id="flash-mechanics-title">How flash drops work</h2>
            <p>A few details about availability, checkout speed, and scheduled drop windows.</p>
        </div>
    </div>

    <div class="flash-mechanics-grid">
        <article class="flash-mechanics-card">
            <div class="flash-mechanics-meta">01 // LIVE INVENTORY</div>
            <h3>Current Stock, From Catalog</h3>
            <p>Live listings are built from active discounted variants with available stock. Product names, prices, and quantities come from the store database.</p>
        </article>

        <article class="flash-mechanics-card">
            <div class="flash-mechanics-meta">02 // CART ACTION</div>
            <h3>Add to Cart, Not Reservation</h3>
            <p>Adding a product places it in your active cart. Inventory is checked on submission; the cart does not reserve units or start a hold timer.</p>
        </article>

        <article class="flash-mechanics-card">
            <div class="flash-mechanics-meta">03 // REQUEST VALIDATION</div>
            <h3>Protected Cart Updates</h3>
            <p>Cart submissions include a session CSRF token and are validated against the current variant and its available stock.</p>
        </article>

        <article class="flash-mechanics-card">
            <div class="flash-mechanics-meta">04 // SALE SCHEDULE</div>
            <h3>Scheduled Drops & Archive</h3>
            <p>Administrators schedule future sale windows. Upcoming drops appear in their time category and completed windows move into the archive automatically.</p>
        </article>
    </div>
</section>
