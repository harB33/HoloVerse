const showCollectorFeedback = (message, loginUrl = '', type = 'success') => {
    let toast = document.querySelector('[data-collector-feedback-toast]');
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'collector-feedback-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.setAttribute('data-collector-feedback-toast', '');

        const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.setAttribute('class', 'collector-feedback-toast-icon');
        icon.setAttribute('viewBox', '0 0 24 24');
        icon.setAttribute('fill', 'none');
        icon.setAttribute('stroke', 'currentColor');
        icon.setAttribute('stroke-width', '2');
        icon.setAttribute('stroke-linecap', 'round');
        icon.setAttribute('stroke-linejoin', 'round');
        icon.setAttribute('aria-hidden', 'true');
        const iconCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        iconCircle.setAttribute('cx', '12');
        iconCircle.setAttribute('cy', '12');
        iconCircle.setAttribute('r', '10');
        icon.append(iconCircle);
        const iconCheck = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        iconCheck.setAttribute('d', 'm8 12 2.5 2.5L16 9');
        icon.append(iconCheck);
        toast.append(icon);

        const copy = document.createElement('p');
        copy.className = 'collector-feedback-toast-copy';
        toast.append(copy);

        const close = document.createElement('button');
        close.className = 'collector-feedback-toast-close';
        close.type = 'button';
        close.setAttribute('aria-label', 'Dismiss message');
        const closeIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        closeIcon.setAttribute('viewBox', '0 0 24 24');
        closeIcon.setAttribute('fill', 'none');
        closeIcon.setAttribute('stroke', 'currentColor');
        closeIcon.setAttribute('stroke-width', '2');
        closeIcon.setAttribute('stroke-linecap', 'round');
        closeIcon.setAttribute('stroke-linejoin', 'round');
        closeIcon.setAttribute('aria-hidden', 'true');
        const closePath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        closePath.setAttribute('d', 'm18 6-12 12M6 6l12 12');
        closeIcon.append(closePath);
        close.append(closeIcon);
        close.addEventListener('click', () => {
            toast.hidden = true;
        });
        toast.append(close);
        document.body.append(toast);
    }

    const isError = type === 'error';
    toast.classList.toggle('is-error', isError);
    toast.classList.toggle('is-success', !isError);
    toast.setAttribute('role', isError ? 'alert' : 'status');
    toast.setAttribute('aria-live', isError ? 'assertive' : 'polite');
    const iconPath = toast.querySelector('.collector-feedback-toast-icon path');
    if (iconPath) {
        iconPath.setAttribute('d', isError ? 'M15 9l-6 6M9 9l6 6' : 'm8 12 2.5 2.5L16 9');
    }
    const copy = toast.querySelector('.collector-feedback-toast-copy');
    copy.replaceChildren(document.createTextNode(message));
    if (loginUrl) {
        const loginLink = document.createElement('a');
        loginLink.href = loginUrl;
        loginLink.textContent = ' Sign in';
        copy.append(loginLink);
    }
    toast.hidden = false;
    window.clearTimeout(toast.dismissTimeout);
    toast.dismissTimeout = window.setTimeout(() => {
        toast.hidden = true;
    }, loginUrl ? 9000 : 5000);
};

window.showCollectorFeedback = showCollectorFeedback;

const updateFavoritePagination = (count, pageSize) => {
    const pagination = document.querySelector('[data-favorite-pagination]');
    if (!pagination) {
        return;
    }

    const currentUrl = new URL(window.location.href);
    const pageCount = Math.max(1, Math.ceil(count / pageSize));
    const currentPage = Math.min(
        Number(currentUrl.searchParams.get('favorites_page')) || 1,
        pageCount,
    );

    pagination.replaceChildren();
    if (pageCount <= 1) {
        pagination.style.display = 'none';
        return;
    }

    pagination.style.removeProperty('display');
    if (currentPage > 1) {
        const previous = document.createElement('a');
        const previousUrl = new URL(currentUrl);
        previousUrl.searchParams.set('favorites_page', String(currentPage - 1));
        previous.href = previousUrl.href;
        previous.rel = 'prev';
        previous.textContent = 'Previous';
        pagination.append(previous);
    }

    const pageLabel = document.createElement('span');
    pageLabel.setAttribute('aria-current', 'page');
    pageLabel.textContent = `Page ${currentPage} of ${pageCount}`;
    pagination.append(pageLabel);

    if (currentPage < pageCount) {
        const next = document.createElement('a');
        const nextUrl = new URL(currentUrl);
        nextUrl.searchParams.set('favorites_page', String(currentPage + 1));
        next.href = nextUrl.href;
        next.rel = 'next';
        next.textContent = 'Next';
        pagination.append(next);
    }
};

const loadFavoritePage = async (pageUrl, updateHistory = false) => {
    const currentSection = document.querySelector('[aria-labelledby="collector-favorites-title"]');
    if (!currentSection) {
        return;
    }

    currentSection.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(pageUrl, {
            headers: { Accept: 'text/html' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            throw new Error(`Unable to load favorites (HTTP ${response.status}).`);
        }

        const pageDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
        const nextSection = pageDocument.querySelector('[aria-labelledby="collector-favorites-title"]');
        if (!nextSection) {
            throw new Error('The requested favorites page was incomplete.');
        }

        currentSection.replaceWith(nextSection);
        if (updateHistory) {
            window.history.pushState({}, '', response.url);
        }
    } catch (error) {
        showCollectorFeedback(
            error instanceof Error ? error.message : 'Could not load favorites. Check your connection and try again.',
            '',
            'error',
        );
    } finally {
        document.querySelector('[aria-labelledby="collector-favorites-title"]')?.removeAttribute('aria-busy');
    }
};

document.addEventListener('click', (event) => {
    const link = event.target instanceof Element
        ? event.target.closest('[data-favorite-pagination] a[href]')
        : null;
    if (
        !link ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey ||
        (link.target && link.target !== '_self')
    ) {
        return;
    }

    event.preventDefault();
    loadFavoritePage(link.href, true);
});

window.addEventListener('popstate', () => {
    loadFavoritePage(window.location.href);
});

document.addEventListener('submit', async (event) => {
    const form = event.target instanceof Element
        ? event.target.closest('[data-favorite-form]')
        : null;
    if (!form) {
        return;
    }

    event.preventDefault();
    const button = form.querySelector('.collector-favorite-button');
    if (button) {
        button.disabled = true;
    }

    try {
        const response = await fetch(form.getAttribute('action'), {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const responseBody = await response.text();
        let result;
        try {
            result = JSON.parse(responseBody);
        } catch {
            showCollectorFeedback(
                `The server couldn't update your favorite (HTTP ${response.status}). Please try again; if it continues, contact support.`,
                '',
                'error',
            );
            console.error('Collector Showcase returned a non-JSON response.', response.status);
            return;
        }

        if (!response.ok) {
            showCollectorFeedback(
                result.message || `Unable to update your Collector Showcase (HTTP ${response.status}).`,
                result.login_url || '',
                'error',
            );
            return;
        }

        const isFavorite = result.is_favorite === true;
        const productId = Number(form.querySelector('input[name="product_id"]')?.value || 0);
        const matchingForms = productId > 0
            ? document.querySelectorAll(`[data-favorite-form] input[name="product_id"][value="${productId}"]`)
            : [];
        const formsToUpdate = matchingForms.length > 0
            ? Array.from(matchingForms, (input) => input.closest('[data-favorite-form]')).filter(Boolean)
            : [form];

        formsToUpdate.forEach((targetForm) => {
            const targetButton = targetForm.querySelector('.collector-favorite-button');
            if (targetButton) {
                targetButton.classList.toggle('is-favorite', isFavorite);
                targetButton.classList.toggle('active', isFavorite);
                targetButton.setAttribute('aria-pressed', String(isFavorite));
                targetButton.setAttribute('aria-label', `${isFavorite ? 'Remove' : 'Add'} ${result.product_name} ${isFavorite ? 'from' : 'to'} your Collector Showcase`);
                targetButton.title = isFavorite ? 'Remove from Collector Showcase' : 'Save to Collector Showcase';
                const svg = targetButton.querySelector('svg');
                if (svg) {
                    svg.setAttribute('fill', isFavorite ? 'currentColor' : 'none');
                }
            }
            const actionInput = targetForm.querySelector('input[name="action"]');
            if (actionInput) {
                actionInput.value = isFavorite ? 'remove' : 'save';
            }
        });

        const favoriteCount = Number(result.favorite_count);
        if (Number.isFinite(favoriteCount)) {
            const countLabel = document.querySelector('[data-favorite-count]');
            if (countLabel) {
                countLabel.textContent = `${favoriteCount} saved`;
            }
        }

        window.dispatchEvent(new CustomEvent('holoverse:favorite-updated', {
            detail: {
                productId,
                isFavorite,
                favoriteCount: Number.isFinite(favoriteCount) ? favoriteCount : undefined,
                productName: result.product_name,
            },
        }));

        const showcaseCard = form.closest('.collector-showcase-card, [data-favorite-grid] > .product-card');
        if (!isFavorite && showcaseCard) {
            const grid = showcaseCard.closest('[data-favorite-grid]');
            showcaseCard.remove();
            if (favoriteCount === 0) {
                const emptyState = document.querySelector('[data-favorite-empty]');
                if (grid) {
                    grid.style.display = 'none';
                }
                if (emptyState) {
                    emptyState.style.removeProperty('display');
                }
                const pagination = document.querySelector('[data-favorite-pagination]');
                if (pagination) {
                    pagination.style.display = 'none';
                }
            } else {
                const pageSize = Number(result.favorite_page_size) || 10;
                const pageCount = Math.max(1, Math.ceil(favoriteCount / pageSize));
                const currentUrl = new URL(window.location.href);
                const currentPage = Number(currentUrl.searchParams.get('favorites_page')) || 1;
                if (grid && grid.children.length === 0 && currentPage > pageCount) {
                    const nextPageUrl = new URL(currentUrl);
                    if (pageCount > 1) {
                        nextPageUrl.searchParams.set('favorites_page', String(pageCount));
                    } else {
                        nextPageUrl.searchParams.delete('favorites_page');
                    }
                    const pageResponse = await fetch(nextPageUrl, {
                        headers: { Accept: 'text/html' },
                        credentials: 'same-origin',
                    });
                    if (!pageResponse.ok) {
                        throw new Error(`Unable to load your remaining favorites (HTTP ${pageResponse.status}).`);
                    }
                    const pageDocument = new DOMParser().parseFromString(await pageResponse.text(), 'text/html');
                    const nextSection = pageDocument.querySelector('[aria-labelledby="collector-favorites-title"]');
                    const currentSection = document.querySelector('[aria-labelledby="collector-favorites-title"]');
                    if (!nextSection || !currentSection) {
                        throw new Error('The updated favorites page was incomplete.');
                    }
                    currentSection.replaceWith(nextSection);
                    window.history.replaceState({}, '', nextPageUrl);
                } else {
                    updateFavoritePagination(favoriteCount, pageSize);
                }
            }
        }

        showCollectorFeedback(
            isFavorite ? 'Added to your Collector Showcase.' : 'Removed from your Collector Showcase.',
        );
    } catch (error) {
        showCollectorFeedback(
            error instanceof Error ? error.message : 'Could not reach the save service. Check your connection and try again.',
            '',
            'error',
        );
        console.error('Collector Showcase update failed.', error);
    } finally {
        if (button) {
            button.disabled = false;
        }
    }
});
