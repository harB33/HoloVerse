<a class="cart-fab" href="<?= htmlspecialchars($cartHref, ENT_QUOTES, 'UTF-8') ?>" aria-label="Open cart">
    <span class="cart-badge" aria-live="polite" hidden>0</span>
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shopping-basket" aria-hidden="true">
        <path d="m15 11-1 9" />
        <path d="m19 11-4-7" />
        <path d="M2 11h20" />
        <path d="m3.5 11 1.6 7.4a2 2 0 0 0 2 1.6h9.8a2 2 0 0 0 2-1.6l1.7-7.4" />
        <path d="M4.5 15.5h15" />
        <path d="m5 11 4-7" />
        <path d="m9 11 1 9" />
    </svg>
</a>

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

        const cartBaseUrl = (() => {
            const currentPath = window.location.pathname || '/';
            return currentPath.includes('/pages/') ? 'cart.php' : 'pages/cart.php';
        })();

        const formatCount = (count) => {
            const numericCount = Number(count) || 0;
            return numericCount > 99 ? '99+' : String(numericCount);
        };

        const syncCartBadge = async () => {
            try {
                const response = await fetch(`${cartBaseUrl}?mode=count`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json().catch(() => ({ count: 0 }));
                const count = Number(payload?.count ?? 0);
                const isVisible = count > 0;

                badge.textContent = formatCount(count);
                badge.hidden = !isVisible;
                cartButton.classList.toggle('has-count', isVisible);
            } catch (error) {
                console.warn('Unable to refresh cart count.', error);
            }
        };

        document.querySelectorAll('form').forEach((form) => {
            if (form.dataset.cartAjaxBound === 'true') {
                return;
            }

            const formFields = [...form.querySelectorAll('button, input')];
            const matchesAddCartForm = formFields.some((field) => {
                const fieldName = (field.getAttribute('name') || '').toLowerCase();
                const fieldValue = (field.getAttribute('value') || '').toLowerCase();
                const textValue = (field.textContent || '').toLowerCase();
                return (fieldName === 'action' && fieldValue === 'add')
                    || (field.type === 'submit' && (fieldValue === 'add' || textValue.includes('add to cart')))
                    || (fieldName === 'action' && fieldValue.includes('add'));
            });

            if (!matchesAddCartForm) {
                return;
            }

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

            form.dataset.cartAjaxBound = 'true';
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (form.dataset.cartSubmitting === 'true') {
                    return;
                }

                const submitButton = form.querySelector('button[type="submit"]') || form.querySelector('button');
                if (!submitButton) {
                    return;
                }

                form.dataset.cartSubmitting = 'true';
                const previousLabel = submitButton.textContent;
                submitButton.disabled = true;
                submitButton.textContent = 'Adding...';

                try {
                    const formAction = form.getAttribute('action') || cartBaseUrl;
                    const response = await fetch(formAction, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json().catch(() => null);

                    if (!response.ok || !payload?.success) {
                        throw new Error(payload?.message || 'Unable to add item to cart.');
                    }

                    await syncCartBadge();
                } catch (error) {
                    console.warn(error.message || 'Unable to add item to cart.', error);
                } finally {
                    form.dataset.cartSubmitting = 'false';
                    submitButton.disabled = false;
                    submitButton.textContent = previousLabel;
                }
            });
        });

        syncCartBadge();
    })();
</script>
