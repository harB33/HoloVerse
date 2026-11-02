(() => {
  const page = document.querySelector(".talents-page");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  if (!page || reducedMotion || document.documentElement.dataset.reduceMotion === "true") {
    return;
  }

  if (!("IntersectionObserver" in window)) {
    return;
  }

  const targets = page.querySelectorAll(
    ".all-products-intro, .talent-directory-filter, .talent-poster-item, .talent-pagination"
  );

  if (targets.length === 0) {
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        entry.target.classList.toggle("is-visible", entry.isIntersecting);
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px 64px 0px" }
  );

  page.classList.add("talent-reveal-ready");
  targets.forEach((target, index) => {
    if (target.matches(".talent-poster-item")) {
      target.style.setProperty("--talent-reveal-delay", `${(index % 4) * 70}ms`);
    }
    observer.observe(target);
  });
})();
