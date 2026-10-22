const showCollectorFeedback = (message, loginUrl = '', type = 'success') => {
    let toast = document.querySelector('[data-collector-feedback-toast]');
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'collector-feedback-toast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.setAttribute('data-collector-feedback-toast', '');

        const icon = document.createElement('span');
        icon.className = 'collector-feedback-toast-icon';
        icon.setAttribute('aria-hidden', 'true');
        toast.append(icon);

        const copy = document.createElement('p');
        copy.className = 'collector-feedback-toast-copy';
        toast.append(copy);

        const close = document.createElement('button');
        close.className = 'collector-feedback-toast-close';
        close.type = 'button';
        close.setAttribute('aria-label', 'Dismiss message');
        close.textContent = '×';
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
    toast.querySelector('.collector-feedback-toast-icon').textContent = isError ? '!' : '✓';

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

document.addEventListener('submit', async (event) => {
    const form = event.target instanceof Element
        ? event.target.closest('[data-favorite-form]')
        : null;
    if (!form) {
        return;
    }

    event.preventDefault();
    const button = form.querySelector('.collector-favorite-button');
    button.disabled = true;

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
        if (!isFavorite && form.closest('.collector-showcase-card')) {
            window.location.reload();
            return;
        }
        button.classList.toggle('is-favorite', isFavorite);
        button.setAttribute('aria-pressed', String(isFavorite));
        button.setAttribute('aria-label', `${isFavorite ? 'Remove' : 'Add'} ${result.product_name} ${isFavorite ? 'from' : 'to'} your Collector Showcase`);
        button.title = isFavorite ? 'Remove from Collector Showcase' : 'Save to Collector Showcase';
        form.querySelector('input[name="action"]').value = isFavorite ? 'remove' : 'save';
        showCollectorFeedback(
            isFavorite ? 'Saved to your Collector Showcase.' : 'Removed from your Collector Showcase.',
        );
    } catch (error) {
        showCollectorFeedback('Could not reach the save service. Check your connection and try again.', '', 'error');
        console.error('Collector Showcase update failed.', error);
    } finally {
        button.disabled = false;
    }
});
