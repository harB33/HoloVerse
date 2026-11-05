(() => {
    const footer = document.querySelector(".site-footer");
    const art = footer?.querySelector(".footer-suisei-art");

    if (
        !footer ||
        !art ||
        window.matchMedia("(prefers-reduced-motion: reduce)").matches ||
        document.documentElement.dataset.reduceMotion === "true"
    ) {
        return;
    }

    let frame = 0;

    const updateArt = () => {
        frame = 0;

        const footerBounds = footer.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        const revealDistance = Math.max(footerBounds.height * 0.72, viewportHeight * 0.45);
        const progress = Math.min(1, Math.max(0, (viewportHeight - footerBounds.top) / revealDistance));

        footer.style.setProperty("--footer-art-progress", progress.toFixed(3));
        footer.style.setProperty("--footer-art-parallax", `${((1 - progress) * 28).toFixed(2)}px`);
    };

    const requestUpdate = () => {
        if (!frame) {
            frame = window.requestAnimationFrame(updateArt);
        }
    };

    window.addEventListener("scroll", requestUpdate, { passive: true });
    window.addEventListener("resize", requestUpdate);
    requestUpdate();
})();
