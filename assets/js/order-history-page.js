(() => {
  const page = document.querySelector(".purchase-history-page, .orders-history-page");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  if (!page || reducedMotion || document.documentElement.dataset.reduceMotion === "true") {
    return;
  }

  if (!("IntersectionObserver" in window)) {
    return;
  }

  const targets = page.querySelectorAll(
    ".order-history-heading, .order-history-card, .order-history-empty, .all-products-pagination"
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

  page.classList.add("order-history-reveal-ready");
  targets.forEach((target, index) => {
    if (target.matches(".order-history-card")) {
      target.style.setProperty("--order-history-reveal-delay", `${(index % 5) * 70}ms`);
    }
    observer.observe(target);
  });
})();
