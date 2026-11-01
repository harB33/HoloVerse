(() => {
    const page = document.querySelector('.home-page');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!page) {
        return;
    }

    const faqItems = [...page.querySelectorAll('.home-faq-item')];
    const faqAnimations = new WeakMap();
    const faqDuration = 240;

    const animateFaqItem = (item, opening) => {
        faqAnimations.get(item)?.cancel();

        if (reducedMotion || document.documentElement.dataset.reduceMotion === 'true') {
            item.open = opening;
            return;
        }

        const summary = item.querySelector('summary');
        if (!summary) {
            item.open = opening;
            return;
        }

        const startHeight = item.getBoundingClientRect().height;
        if (opening) {
            item.open = true;
        }
        const endHeight = opening ? item.scrollHeight : summary.getBoundingClientRect().height;

        if (Math.abs(endHeight - startHeight) < 1) {
            item.open = opening;
            return;
        }

        const animation = item.animate(
            [
                { height: `${startHeight}px`, opacity: 0.88 },
                { height: `${endHeight}px`, opacity: 1 },
            ],
            { duration: faqDuration, easing: 'cubic-bezier(0.2, 0.7, 0.2, 1)' }
        );
        faqAnimations.set(item, animation);

        if (!opening) {
            item.style.overflow = 'hidden';
        }

        animation.onfinish = () => {
            if (faqAnimations.get(item) !== animation) {
                return;
            }

            item.open = opening;
            item.style.removeProperty('height');
            item.style.removeProperty('overflow');
            faqAnimations.delete(item);
        };
    };

    faqItems.forEach((item) => {
        item.querySelector('summary')?.addEventListener('click', (event) => {
            event.preventDefault();
            const opening = !item.open;

            if (opening) {
                faqItems.forEach((otherItem) => {
                    if (otherItem !== item && otherItem.open) {
                        animateFaqItem(otherItem, false);
                    }
                });
            }

            animateFaqItem(item, opening);
        });
    });

    if (reducedMotion || document.documentElement.dataset.reduceMotion === 'true') {
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
