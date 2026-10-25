(() => {
    const cartMain = document.querySelector('.cart-main');
    if (!cartMain) {
        return;
    }

    const cartEndpoint = new URL(window.location.href);
    cartEndpoint.pathname = cartEndpoint.pathname.replace(/\/+$/, '') || '/cart';
    cartEndpoint.search = '';
    cartEndpoint.hash = '';

    const showMessage = (message) => {
        let alert = cartMain.querySelector('.cart-message');
        if (!alert) {
            alert = document.createElement('p');
            alert.className = 'cart-message';
            alert.setAttribute('role', 'alert');
            cartMain.querySelector('.cart-list-panel, .cart-panel')?.prepend(alert);
        }
        alert.textContent = message;
    };

    const showEmptyCart = () => {
        const layout = cartMain.querySelector('[data-cart-layout]');
        const emptyState = cartMain.querySelector('[data-cart-empty]');
        if (layout && emptyState) {
            layout.style.display = 'none';
            emptyState.style.removeProperty('display');
        }
    };

    const updateSummary = (summaries) => {
        const summaryList = cartMain.querySelector('[data-cart-summary-list]');
        const summaryTotals = cartMain.querySelector('[data-cart-summary-totals]');
        if (!summaryList || !summaryTotals) {
            return;
        }

        summaryList.replaceChildren();
        summaryTotals.replaceChildren();
        summaries.forEach((summary) => {
            const subtotalRow = document.createElement('div');
            subtotalRow.className = 'cart-summary-row';
            const subtotalLabel = document.createElement('span');
            subtotalLabel.textContent = summary.subtotal_label;
            const subtotalAmount = document.createElement('strong');
            subtotalAmount.textContent = summary.subtotal;
            subtotalRow.append(subtotalLabel, subtotalAmount);
            summaryList.append(subtotalRow);

            const taxRow = document.createElement('div');
            taxRow.className = 'cart-summary-row';
            const taxLabel = document.createElement('span');
            taxLabel.textContent = 'Tax (12%)';
            const taxAmount = document.createElement('strong');
            taxAmount.textContent = summary.tax;
            taxRow.append(taxLabel, taxAmount);
            summaryList.append(taxRow);

            const totalRow = document.createElement('div');
            totalRow.className = 'cart-summary-total';
            const totalLabel = document.createElement('span');
            totalLabel.textContent = 'Total';
            const totalAmount = document.createElement('strong');
            totalAmount.textContent = summary.total;
            totalRow.append(totalLabel, totalAmount);
            summaryTotals.append(totalRow);
        });

        const shippingRow = document.createElement('div');
        shippingRow.className = 'cart-summary-row';
        const shippingLabel = document.createElement('span');
        shippingLabel.textContent = 'Shipping Fee';
        const shippingAmount = document.createElement('strong');
        shippingAmount.textContent = 'Free';
        shippingRow.append(shippingLabel, shippingAmount);
        summaryList.append(shippingRow);
    };

    const applyCartState = (payload) => {
        if (!Array.isArray(payload.items) || !Array.isArray(payload.summaries)) {
            throw new Error('The cart response was incomplete. Please try again.');
        }
        if (payload.items.length === 0) {
            showEmptyCart();
            return;
        }

        const itemsById = new Map(payload.items.map((item) => [String(item.id), item]));
        cartMain.querySelectorAll('[data-cart-item-id]').forEach((element) => {
            const item = itemsById.get(element.dataset.cartItemId);
            if (!item) {
                element.remove();
                return;
            }

            const quantityInput = element.querySelector('input[name="quantity"]');
            if (quantityInput) {
                quantityInput.value = String(item.quantity);
                quantityInput.max = String(item.stock_quantity);
            }
            const price = element.querySelector('.cart-item-price');
            if (price) {
                price.textContent = item.line_total;
            }
        });

        updateSummary(payload.summaries);
    };

    cartMain.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('.quantity-picker, .cart-remove-form')) {
            return;
        }
        event.preventDefault();
        if (form.dataset.cartSubmitting === 'true') {
            return;
        }

        form.dataset.cartSubmitting = 'true';
        form.setAttribute('aria-busy', 'true');
        const submitter = event.submitter;
        if (submitter instanceof HTMLButtonElement) {
            submitter.disabled = true;
        }
        const formData = new FormData(form);
        if (submitter instanceof HTMLButtonElement && submitter.name) {
            formData.set(submitter.name, submitter.value);
        }

        try {
            const response = await fetch(cartEndpoint, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const responseBody = await response.text();
            let payload;
            try {
                payload = JSON.parse(responseBody);
            } catch {
                throw new Error(
                    response.ok
                        ? 'The server returned an unexpected response while updating your cart.'
                        : `Unable to update your cart (HTTP ${response.status}). Please reload the page and try again.`
                );
            }
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Unable to update your cart. Please try again.');
            }
            cartMain.querySelector('.cart-message')?.remove();
            applyCartState(payload);
        } catch (error) {
            showMessage(error instanceof Error ? error.message : 'Unable to update your cart. Please try again.');
        } finally {
            form.dataset.cartSubmitting = 'false';
            form.removeAttribute('aria-busy');
            if (submitter instanceof HTMLButtonElement) {
                submitter.disabled = false;
            }
        }
    });
})();
