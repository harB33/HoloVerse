document.addEventListener('submit', async (event) => {
    const form = event.target instanceof Element
        ? event.target.closest('[data-favorite-form]')
        : null;
    if (!form) {
        return;
    }

    event.preventDefault();
    const button = form.querySelector('.collector-favorite-button');
    const feedback = form.querySelector('[data-favorite-feedback]');
    button.disabled = true;
    feedback.textContent = '';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const result = await response.json();
        if (!response.ok) {
            form.classList.add('has-feedback');
            feedback.textContent = result.message || 'Unable to update your Collector Showcase.';
            if (result.login_url) {
                const loginLink = document.createElement('a');
                loginLink.href = result.login_url;
                loginLink.textContent = ' Sign in';
                feedback.append(loginLink);
            }
            return;
        }

        const isFavorite = result.is_favorite === true;
        form.classList.add('has-feedback');
        if (!isFavorite && form.closest('.collector-showcase-card')) {
            window.location.reload();
            return;
        }
        button.classList.toggle('is-favorite', isFavorite);
        button.setAttribute('aria-pressed', String(isFavorite));
        button.setAttribute('aria-label', `${isFavorite ? 'Remove' : 'Add'} ${result.product_name} ${isFavorite ? 'from' : 'to'} your Collector Showcase`);
        button.title = isFavorite ? 'Remove from Collector Showcase' : 'Save to Collector Showcase';
        form.querySelector('input[name="action"]').value = isFavorite ? 'remove' : 'save';
        feedback.textContent = isFavorite ? 'Saved to your Collector Showcase.' : 'Removed from your Collector Showcase.';
    } catch (error) {
        form.classList.add('has-feedback');
        feedback.textContent = 'Unable to update your Collector Showcase. Please try again.';
        console.error('Collector Showcase update failed.', error);
    } finally {
        button.disabled = false;
    }
});
