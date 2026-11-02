(() => {
    const slideshows = [...document.querySelectorAll('[data-image-slideshow]')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    slideshows.forEach((slideshow) => {
        const layers = [...slideshow.querySelectorAll('[data-slideshow-layer]')];
        const imageSources = (slideshow.dataset.slideshowImages || '').split('|').filter(Boolean);

        if (layers.length !== 2 || imageSources.length < 2) {
            console.error('Image slideshow needs two image layers and at least two images.');
            return;
        }

        const failedImages = new Set();
        const interval = Number(slideshow.dataset.slideshowInterval) || 6500;
        let activeLayer = 0;
        let activeImage = 0;
        let requestId = 0;
        let timer;

        const scheduleNextImage = () => {
            window.clearTimeout(timer);
            if (document.hidden || reducedMotion || document.documentElement.dataset.reduceMotion === 'true') {
                return;
            }

            let nextImage = (activeImage + 1) % imageSources.length;
            let checkedImages = 0;
            while (failedImages.has(nextImage) && checkedImages < imageSources.length) {
                nextImage = (nextImage + 1) % imageSources.length;
                checkedImages += 1;
            }

            if (checkedImages < imageSources.length) {
                timer = window.setTimeout(() => showImage(nextImage), interval);
            }
        };

        const showImage = (imageIndex) => {
            if (imageIndex === activeImage || failedImages.has(imageIndex)) {
                scheduleNextImage();
                return;
            }

            const thisRequest = ++requestId;
            const image = new Image();
            image.onload = () => {
                if (thisRequest !== requestId) {
                    return;
                }

                const nextLayer = (activeLayer + 1) % layers.length;
                layers[nextLayer].style.backgroundImage = `url("${imageSources[imageIndex]}")`;
                layers[nextLayer].classList.add('is-active');
                layers[activeLayer].classList.remove('is-active');
                activeLayer = nextLayer;
                activeImage = imageIndex;
                scheduleNextImage();
            };
            image.onerror = () => {
                if (thisRequest !== requestId) {
                    return;
                }

                failedImages.add(imageIndex);
                console.error(`Unable to load slideshow image: ${imageSources[imageIndex]}`);
                scheduleNextImage();
            };
            image.src = imageSources[imageIndex];
        };

        document.addEventListener('visibilitychange', scheduleNextImage);
        scheduleNextImage();
    });
})();
