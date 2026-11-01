(() => {
    const page = document.querySelector('.home-page');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!page || reducedMotion || document.documentElement.dataset.reduceMotion === 'true') {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        return;
    }

    const targets = page.querySelectorAll('[data-home-reveal]');
    if (targets.length === 0) {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            entry.target.classList.toggle('is-visible', entry.isIntersecting);
        });
    }, { threshold: 0.12, rootMargin: '0px 0px 64px 0px' });

    page.classList.add('home-reveal-ready');
    targets.forEach((target) => observer.observe(target));
})();
