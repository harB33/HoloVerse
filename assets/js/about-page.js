(() => {
  const page = document.querySelector(".about-page");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  if (!page || reducedMotion || document.documentElement.dataset.reduceMotion === "true") {
    return;
  }

  if (!("IntersectionObserver" in window)) {
    return;
  }

  const targets = page.querySelectorAll(
    ".about-story, .about-interlude, .about-principles article, .about-section-heading, .about-team-member, .about-toolkit"
  );

  if (targets.length === 0) {
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px 64px 0px" }
  );

  page.classList.add("about-reveal-ready");
  targets.forEach((target) => observer.observe(target));
})();
