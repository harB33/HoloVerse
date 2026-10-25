<a class="cart-fab" href="<?= htmlspecialchars($cartHref, ENT_QUOTES, 'UTF-8') ?>" aria-label="Open cart">
    <span class="cart-badge" aria-live="polite" hidden>0</span>
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-cart" aria-hidden="true">
        <path d="m2.05 2.05 1.099-.028a1 1 0 0 1 1.008.815l2.69 14.347A1 1 0 0 0 7.83 18H18" />
        <path d="M4.563 5h16.435a1 1 0 0 1 .981 1.204l-1.026 6.226A2 2 0 0 1 18.962 14H6.25" />
        <circle cx="18" cy="20" r="2" />
        <circle cx="8" cy="20" r="2" />
    </svg>
</a>
<div class="cart-toast" role="status" aria-live="polite" aria-atomic="true" hidden>
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="10" />
        <path d="m8 12 2.5 2.5L16 9" />
    </svg>
    <span>Added to cart.</span>
    <button type="button" aria-label="Dismiss notification">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m18 6-12 12" />
            <path d="m6 6 12 12" />
        </svg>
    </button>
</div>

<style>
    .cart-fab {
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 50;
        display: flex;
        width: 56px;
        height: 56px;
        align-items: center;
        justify-content: center;
        overflow: visible;
        border-radius: 50%;
        border: 2px solid #fff;
        background: linear-gradient(135deg, #67d8f1, #1da3d8);
        color: #fff;
        text-decoration: none;
        box-shadow: 0 0 0 2px rgba(88, 190, 245, 0.2);
        transition: background-color 0.2s ease;
    }

    .cart-fab:hover {
        box-shadow: 0 0 0 4px rgba(88, 190, 245, 0.24);
    }

    .cart-fab .cart-badge {
        position: absolute;
        top: 0;
        right: 0;
        display: flex;
        min-width: 24px;
        height: 24px;
        align-items: center;
        justify-content: center;
        padding: 0 6px;
        transform: translate(15%, -15%);
        border-radius: 999px;
        background: #e43d58;
        color: #fff;
        font: 700 12px/1 Arial, sans-serif;
    }

    .cart-fab .cart-badge[hidden] {
        display: none;
    }

    .cart-fab svg {
        width: 24px;
        height: 24px;
    }

    .cart-toast {
        position: fixed;
        right: 92px;
        bottom: 28px;
        z-index: 60;
        display: flex;
        min-height: 48px;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border: 1px solid #d9f2e2;
        border-radius: 8px;
        background: #f3fcf6;
        color: #40564a;
        box-shadow: 0 8px 24px rgba(16, 24, 40, 0.12);
        font: 500 14px/1.4 Arial, sans-serif;
    }

    .cart-toast[hidden] {
        display: none;
    }

    .cart-toast > svg {
        flex: 0 0 auto;
        color: #35d981;
    }

    .cart-toast.is-error {
        border-color: #f5c2c7;
        background: #fff4f4;
        color: #9f2634;
    }

    .cart-toast.is-error > svg {
        color: #d94352;
    }

    .cart-toast button {
        display: inline-flex;
        width: 28px;
        height: 28px;
        align-items: center;
        justify-content: center;
        margin-left: 4px;
        padding: 0;
        border: 0;
        border-radius: 4px;
        background: transparent;
        color: #647568;
        cursor: pointer;
    }

    .cart-toast button:hover {
        background: #e4f5e9;
    }

    .cart-toast button svg {
        width: 18px;
        height: 18px;
    }

    @media (max-width: 480px) {
        .cart-toast {
            right: 88px;
            bottom: 28px;
            left: auto;
            max-width: calc(100vw - 112px);
        }
    }
</style>

<script>
    (() => {
        const cartButton = document.querySelector('.cart-fab');
        if (!cartButton) {
            return;
        }

        const badge = cartButton.querySelector('.cart-badge');
        if (!badge) {
            return;
        }
        const cartToast = document.querySelector('.cart-toast');
        const cartToastCloseButton = cartToast?.querySelector('button');
        const cartToastMessage = cartToast?.querySelector('span');
        let cartToastTimeout;

        const showCartToast = (message, isError = false) => {
            if (!cartToast) {
                return;
            }

            window.clearTimeout(cartToastTimeout);
            cartToast.classList.toggle('is-error', isError);
            cartToast.setAttribute('role', isError ? 'alert' : 'status');
            if (cartToastMessage) {
                cartToastMessage.textContent = message;
            }
            cartToast.hidden = false;
            cartToastTimeout = window.setTimeout(() => {
                cartToast.hidden = true;
            }, isError ? 6000 : 4000);
        };

        cartToastCloseButton?.addEventListener('click', () => {
            window.clearTimeout(cartToastTimeout);
            cartToast.hidden = true;
        });

        const cartBaseUrl = (() => {
            const endpoint = new URL(<?= json_encode($siteRoot . 'cart', JSON_UNESCAPED_SLASHES) ?>, document.baseURI);
            endpoint.pathname = endpoint.pathname.replace(/\/+$/, '');
            endpoint.search = '';
            endpoint.hash = '';
            return endpoint;
        })();

        const formatCount = (count) => {
            const numericCount = Number(count) || 0;
            return numericCount > 99 ? '99+' : String(numericCount);
        };

        const syncCartBadge = async () => {
            try {
                const countUrl = new URL(cartBaseUrl);
                countUrl.searchParams.set('mode', 'count');
                const response = await fetch(countUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                const count = Number(payload?.count ?? 0);
                const isVisible = count > 0;

                badge.textContent = formatCount(count);
                badge.hidden = !isVisible;
                cartButton.classList.toggle('has-count', isVisible);
            } catch (error) {
                console.warn('Unable to refresh cart count.', error);
            }
        };

        const matchesAddCartForm = (form) => {
            const formFields = [...form.querySelectorAll('button, input')];
            return formFields.some((field) => {
                const fieldName = (field.getAttribute('name') || '').toLowerCase();
                const fieldValue = (field.getAttribute('value') || '').toLowerCase();
                const textValue = (field.textContent || '').toLowerCase();
                return (fieldName === 'action' && fieldValue === 'add')
                    || (field.type === 'submit' && (fieldValue === 'add' || textValue.includes('add to cart')))
                    || (fieldName === 'action' && fieldValue.includes('add'));
            });
        };

        const ensureAddCartFields = (form) => {
            const ensureHiddenInput = (name, value) => {
                let input = form.querySelector(`input[name="${name}"]`);
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    form.appendChild(input);
                }
                input.value = value;
            };

            ensureHiddenInput('action', 'add');

            const csrfToken = document.querySelector('input[name="csrf_token"]');
            if (csrfToken && !form.querySelector('input[name="csrf_token"]')) {
                const clonedToken = csrfToken.cloneNode(true);
                form.appendChild(clonedToken);
            }
        };

        const updateCartBadge = (count) => {
            const numericCount = Number(count) || 0;
            const isVisible = numericCount > 0;
            badge.textContent = formatCount(numericCount);
            badge.hidden = !isVisible;
            cartButton.classList.toggle('has-count', isVisible);
        };

        document.addEventListener('submit', async (event) => {
            const form = event.target instanceof Element ? event.target.closest('form') : null;
            if (!(form instanceof HTMLFormElement) || !matchesAddCartForm(form)) {
                return;
            }
            event.preventDefault();

            if (form.dataset.cartSubmitting === 'true') {
                return;
            }

            ensureAddCartFields(form);
            const submitButton = event.submitter instanceof HTMLButtonElement
                ? event.submitter
                : form.querySelector('button[type="submit"]') || form.querySelector('button');
            if (!submitButton) {
                return;
            }

            form.dataset.cartSubmitting = 'true';
            const previousLabel = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Adding...';

            try {
                const formData = new FormData(form);
                if (submitButton.name) {
                    formData.set(submitButton.name, submitButton.value);
                }
                const response = await fetch(cartBaseUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const responseText = await response.text();
                let payload;
                try {
                    payload = JSON.parse(responseText);
                } catch {
                    throw new Error(
                        response.ok
                            ? 'The server returned an unexpected response while adding to your cart.'
                            : `Unable to add item to cart (HTTP ${response.status}). Please try again.`
                    );
                }

                if (!response.ok || !payload?.success) {
                    throw new Error(payload?.message || 'Unable to add item to cart.');
                }

                if (payload.count !== undefined) {
                    updateCartBadge(payload.count);
                } else {
                    await syncCartBadge();
                }
                showCartToast('Added to cart.');
            } catch (error) {
                console.error('Unable to add item to cart.', error);
                showCartToast(error instanceof Error ? error.message : 'Unable to add item to cart.', true);
            } finally {
                form.dataset.cartSubmitting = 'false';
                submitButton.disabled = false;
                submitButton.textContent = previousLabel;
            }
        });

        syncCartBadge();
    })();
</script>
