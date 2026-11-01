(() => {
    const page = document.querySelector('.home-page');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!page || reducedMotion || document.documentElement.dataset.reduceMotion === 'true') {
        return;
    }

    const marquees = [...page.querySelectorAll('.talent-marquee')];
    const referenceGroup = marquees[0]?.querySelector('.talent-marquee-group');
    if (referenceGroup && marquees.length > 1) {
        const referenceDuration = 72;
        const updateMarqueeSpeeds = () => {
            const referenceWidth = referenceGroup.getBoundingClientRect().width;
            if (referenceWidth <= 0) {
                return;
            }

            marquees.forEach((marquee) => {
                const group = marquee.querySelector('.talent-marquee-group');
                const track = marquee.querySelector('.talent-marquee-track');
                if (!group || !track) {
                    return;
                }

                const duration = referenceDuration * group.getBoundingClientRect().width / referenceWidth;
                track.style.animationDuration = `${duration}s`;
            });
        };

        updateMarqueeSpeeds();
        if ('ResizeObserver' in window) {
            const marqueeResizeObserver = new ResizeObserver(updateMarqueeSpeeds);
            marquees.forEach((marquee) => {
                const group = marquee.querySelector('.talent-marquee-group');
                if (group) {
                    marqueeResizeObserver.observe(group);
                }
            });
        } else {
            window.addEventListener('resize', updateMarqueeSpeeds);
        }
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
