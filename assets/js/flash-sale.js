const galleryImage = document.querySelector(".flash-product-main-image");
const galleryBackdrop = document.querySelector(".flash-product-backdrop");
const galleryThumbnailStrip = document.querySelector(".flash-gallery .product-gallery-thumbnails");
let galleryThumbnails = [...document.querySelectorAll(".flash-gallery [data-gallery-image]")];
let activeGalleryIndex = 0;

galleryThumbnailStrip?.addEventListener("wheel", (event) => {
    if (galleryThumbnailStrip.scrollWidth <= galleryThumbnailStrip.clientWidth) {
        return;
    }

    const wheelDelta = Math.abs(event.deltaX) > Math.abs(event.deltaY)
        ? event.deltaX
        : event.deltaY;
    const maxScrollLeft = galleryThumbnailStrip.scrollWidth - galleryThumbnailStrip.clientWidth;
    const canScroll = wheelDelta < 0
        ? galleryThumbnailStrip.scrollLeft > 0
        : galleryThumbnailStrip.scrollLeft < maxScrollLeft;
    if (canScroll && wheelDelta !== 0) {
        event.preventDefault();
        galleryThumbnailStrip.scrollLeft += wheelDelta;
    }
}, { passive: false });

const bindGalleryThumbnails = () => {
    galleryThumbnails = [...document.querySelectorAll(".flash-gallery [data-gallery-image]")];
    galleryThumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener("mouseenter", () => showGalleryImage(index));
        thumbnail.addEventListener("click", () => showGalleryImage(index));
    });
};

const showGalleryImage = (index, scrollThumbnail = false) => {
    if (!galleryThumbnails.length || !galleryImage) {
        return;
    }

    activeGalleryIndex = (index + galleryThumbnails.length) % galleryThumbnails.length;
    const activeThumbnail = galleryThumbnails[activeGalleryIndex];
    galleryImage.src = activeThumbnail.dataset.imageSrc;
    galleryImage.alt = activeThumbnail.dataset.imageAlt || "";
    if (galleryBackdrop) {
        galleryBackdrop.src = activeThumbnail.dataset.imageSrc;
    }
    galleryThumbnails.forEach((thumbnail, thumbnailIndex) => {
        const selected = thumbnailIndex === activeGalleryIndex;
        thumbnail.classList.toggle("is-active", selected);
        thumbnail.setAttribute("aria-pressed", String(selected));
    });

    if (scrollThumbnail) {
        activeThumbnail.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "nearest" });
    }
};

bindGalleryThumbnails();

document.querySelector("[data-gallery-previous]")?.addEventListener("click", () => {
    showGalleryImage(activeGalleryIndex - 1, true);
});
document.querySelector("[data-gallery-next]")?.addEventListener("click", () => {
    showGalleryImage(activeGalleryIndex + 1, true);
});
galleryImage?.addEventListener("keydown", (event) => {
    if (event.key === "ArrowLeft") {
        showGalleryImage(activeGalleryIndex - 1, true);
    } else if (event.key === "ArrowRight") {
        showGalleryImage(activeGalleryIndex + 1, true);
    }
});

const saleTabs = [...document.querySelectorAll("[data-sale-filter]")];
const saleCards = [...document.querySelectorAll(".flash-sale-drop-card[data-sale-state]")];
const saleGrid = document.querySelector("#flash-sale-products");
const salePrevious = document.querySelector("[data-sale-previous]");
const saleNext = document.querySelector("[data-sale-next]");
const saleCarousel = document.querySelector("[data-sale-carousel]");
const saleCarouselControls = document.querySelector(".flash-sale-carousel-controls");
const flashSaleCard = document.querySelector(".flash-sale-card");
const saleEmptyState = document.querySelector("[data-sale-empty]");
const saleDetail = (name) => document.querySelector(`[data-sale-detail="${name}"]`);
const saleDescriptionToggle = document.querySelector("[data-sale-description-toggle]");
const countdown = document.querySelector("[data-countdown]");
const countdownText = document.querySelector("[data-sale-countdown-text]");
let countdownDeadline = countdown ? new Date(countdown.dataset.countdown).getTime() : 0;
const updateCountdown = () => {
    if (!countdown) {
        return;
    }
    const remaining = Math.max(0, countdownDeadline - Date.now());
    countdown.querySelector("[data-countdown-days]").textContent = String(Math.floor(remaining / 86400000)).padStart(2, "0");
    countdown.querySelector("[data-countdown-hours]").textContent = String(Math.floor((remaining % 86400000) / 3600000)).padStart(2, "0");
    countdown.querySelector("[data-countdown-minutes]").textContent = String(Math.floor((remaining % 3600000) / 60000)).padStart(2, "0");
    countdown.querySelector("[data-countdown-seconds]").textContent = String(Math.floor((remaining % 60000) / 1000)).padStart(2, "0");
};
let selectedSaleState = saleTabs.find((tab) => tab.getAttribute("aria-selected") === "true")?.dataset.saleFilter || "live";
const initialSaleCard = saleCards.find((card) => card.dataset.variantId === saleDetail("variant-id")?.value);
initialSaleCard?.classList.add("is-selected");
initialSaleCard?.setAttribute("aria-current", "true");
const updateSaleCarouselControls = () => {
    if (!saleGrid || !salePrevious || !saleNext) {
        return;
    }

    const maxScroll = saleGrid.scrollWidth - saleGrid.clientWidth;
    if (saleCarouselControls) {
        saleCarouselControls.hidden = maxScroll <= 1;
    }
    salePrevious.disabled = saleGrid.scrollLeft <= 1;
    saleNext.disabled = maxScroll <= 1 || saleGrid.scrollLeft >= maxScroll - 1;
};
const saleEmptyMessages = {
    live: "There are no discounted products available right now.",
    starting: "No flash sales are scheduled to start within the next hour.",
    tomorrow: "No flash sales are scheduled for tomorrow.",
    archive: "There are no completed flash sales in the archive yet.",
};

const updateSaleCards = () => {
    let visibleCards = 0;

    saleCards.forEach((card) => {
        const matchesState = card.dataset.saleState === selectedSaleState;
        card.hidden = !matchesState;
        visibleCards += Number(matchesState);
    });

    if (saleGrid) {
        saleGrid.scrollLeft = 0;
        window.requestAnimationFrame(updateSaleCarouselControls);
    }

    if (saleEmptyState) {
        saleEmptyState.textContent = saleEmptyMessages[selectedSaleState] || "No sale items are available in this category.";
        saleEmptyState.hidden = visibleCards > 0;
    }
};

const scrollSaleProducts = (direction) => {
    const card = saleGrid?.querySelector(".flash-sale-drop-card:not([hidden])");
    if (!card || !saleGrid) {
        return;
    }
    const gap = Number.parseFloat(getComputedStyle(saleGrid).columnGap) || 0;
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    saleGrid.scrollBy({
        left: direction * (card.getBoundingClientRect().width + gap),
        behavior: reducedMotion ? "auto" : "smooth",
    });
};

salePrevious?.addEventListener("click", () => scrollSaleProducts(-1));
saleNext?.addEventListener("click", () => scrollSaleProducts(1));
saleDescriptionToggle?.addEventListener("click", () => {
    const expanded = saleDescriptionToggle.getAttribute("aria-expanded") !== "true";
    const preview = saleDetail("description-preview");
    const full = saleDetail("description-full");
    if (!preview || !full) {
        return;
    }

    saleDescriptionToggle.setAttribute("aria-expanded", String(expanded));
    saleDescriptionToggle.textContent = expanded
        ? "Hide Full Product Description"
        : "View Full Product Description";
    preview.hidden = expanded;
    full.hidden = !expanded;
});
document.addEventListener("wheel", (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!saleCarousel || !saleGrid || !target?.closest("[data-sale-carousel]") || event.ctrlKey) {
        return;
    }

    const maxScrollLeft = saleGrid.scrollWidth - saleGrid.clientWidth;
    if (maxScrollLeft <= 1) {
        return;
    }

    const rawDelta = Math.abs(event.deltaX) > Math.abs(event.deltaY)
        ? event.deltaX
        : event.deltaY;
    const wheelDelta = event.deltaMode === WheelEvent.DOM_DELTA_LINE
        ? rawDelta * 16
        : event.deltaMode === WheelEvent.DOM_DELTA_PAGE
            ? rawDelta * saleGrid.clientWidth
            : rawDelta;
    const canScroll = wheelDelta < 0
        ? saleGrid.scrollLeft > 0
        : saleGrid.scrollLeft < maxScrollLeft;

    if (wheelDelta !== 0 && canScroll) {
        event.preventDefault();
        saleGrid.scrollLeft = Math.max(0, Math.min(maxScrollLeft, saleGrid.scrollLeft + wheelDelta));
    }
}, { capture: true, passive: false });
saleGrid?.addEventListener("scroll", updateSaleCarouselControls, { passive: true });
saleCarousel && window.addEventListener("resize", updateSaleCarouselControls);
saleCarousel && updateSaleCarouselControls();

const updateSaleProductDetails = (card) => {
    if (!flashSaleCard || !galleryImage) {
        return;
    }

    const product = JSON.parse(card.dataset.saleProduct);
    const stock = Number(product.stock) || 0;
    const stockPercent = Math.min(100, stock);
    const live = product.state === "live" && stock > 0;
    const startStatus = product.startsLabel ? `Starts ${product.startsLabel}` : "Sale starts soon";
    const status = product.state === "archive"
        ? "Sale ended"
        : product.state === "live"
            ? stock > 0 ? "Available now" : "Currently sold out"
            : startStatus;
    const textValues = {
        breadcrumb: product.name,
        "stock-badge": `${stock} units available`,
        "product-badge": product.name,
        "variant-caption": product.variant,
        "category-caption": product.category,
        category: product.category,
        variant: product.variant,
        name: product.name,
        price: product.price,
        "compare-price": product.comparePrice,
        discount: `${product.discount}% off`,
        "sale-note": product.state === "archive" ? "This flash sale has ended" : product.state === "live" ? "While supplies last" : startStatus,
        "stock-text": `${stock} units available`,
        "stock-count": `${stock} in stock`,
        "stock-note": live
            ? "Adding a live flash-sale item reserves stock for five minutes."
            : product.state === "archive"
                ? "This item is no longer available at the flash-sale price."
                : "This item is not available until its sale starts.",
        availability: status,
    };

    Object.entries(textValues).forEach(([name, value]) => {
        const element = saleDetail(name);
        if (element) {
            element.textContent = value;
        }
    });

    const descriptionPreview = saleDetail("description-preview");
    const descriptionFull = saleDetail("description-full");
    if (descriptionPreview && descriptionFull && saleDescriptionToggle) {
        descriptionPreview.textContent = product.description;
        descriptionFull.textContent = product.description;
        descriptionPreview.hidden = false;
        descriptionFull.hidden = true;
        saleDescriptionToggle.hidden = product.description.length <= 180;
        saleDescriptionToggle.setAttribute("aria-expanded", "false");
        saleDescriptionToggle.textContent = "View Full Product Description";
    }

    const stockProgress = saleDetail("stock-progress");
    if (stockProgress) {
        stockProgress.setAttribute("aria-valuenow", String(stockPercent));
        stockProgress.firstElementChild.style.width = `${stockPercent}%`;
    }

    const cartForm = saleDetail("cart-form");
    const cartMessage = saleDetail("cart-message");
    if (cartForm && cartMessage) {
        cartForm.hidden = !live;
        cartMessage.hidden = live;
        cartMessage.textContent = product.state === "archive"
            ? "This flash sale has ended."
            : product.state === "live"
                ? "Sorry, this product is sold out."
                : `${startStatus}.`;
    }
    const variantInput = saleDetail("variant-id");
    if (variantInput) {
        variantInput.value = String(product.variantId);
    }

    const nextDeadline = product.state === "live"
        ? product.endsAt
        : product.state === "archive"
            ? ""
            : product.startsAt;
    if (countdown) {
        countdown.hidden = !nextDeadline;
        if (nextDeadline) {
            countdown.dataset.countdown = nextDeadline;
            countdownDeadline = new Date(nextDeadline).getTime();
            updateCountdown();
        }
    }
    if (countdownText) {
        countdownText.textContent = product.state === "live"
            ? "Drop ends in:"
            : product.state === "archive"
                ? "Sale ended"
                : "Sale starts in:";
    }

    const images = product.images?.length
        ? product.images
        : [{ image_url: "", alt_text: product.name }];
    galleryThumbnailStrip?.replaceChildren();
    images.forEach((image, index) => {
        const thumbnail = document.createElement("button");
        thumbnail.className = `product-gallery-thumbnail${index === 0 ? " is-active" : ""}`;
        thumbnail.type = "button";
        thumbnail.dataset.galleryImage = "";
        thumbnail.dataset.imageSrc = image.image_url;
        thumbnail.dataset.imageAlt = image.alt_text || product.name;
        thumbnail.setAttribute("aria-label", `Show product image ${index + 1}`);
        thumbnail.setAttribute("aria-pressed", String(index === 0));

        const thumbnailImage = document.createElement("img");
        thumbnailImage.src = image.image_url;
        thumbnailImage.alt = "";
        thumbnailImage.loading = "lazy";
        thumbnailImage.decoding = "async";
        thumbnail.append(thumbnailImage);
        galleryThumbnailStrip?.append(thumbnail);
    });
    bindGalleryThumbnails();
    const galleryPrevious = document.querySelector("[data-gallery-previous]");
    const galleryNext = document.querySelector("[data-gallery-next]");
    if (galleryPrevious) {
        galleryPrevious.hidden = images.length < 2;
    }
    if (galleryNext) {
        galleryNext.hidden = images.length < 2;
    }
    showGalleryImage(0);

    saleCards.forEach((saleCard) => {
        saleCard.classList.toggle("is-selected", saleCard === card);
        if (saleCard === card) {
            saleCard.setAttribute("aria-current", "true");
        } else {
            saleCard.removeAttribute("aria-current");
        }
    });
};

saleCards.forEach((card) => {
    card.addEventListener("click", (event) => {
        if (event.target.closest("button, a, form, input, textarea, select, label")) {
            return;
        }
        updateSaleProductDetails(card);
        flashSaleCard?.scrollIntoView({
            behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth",
            block: "start",
        });
    });
    card.addEventListener("keydown", (event) => {
        if (event.target !== card || (event.key !== "Enter" && event.key !== " ")) {
            return;
        }
        event.preventDefault();
        updateSaleProductDetails(card);
        flashSaleCard?.scrollIntoView({
            behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth",
            block: "start",
        });
    });
});

const selectSaleTab = (selectedTab, moveFocus = false) => {
    selectedSaleState = selectedTab.dataset.saleFilter;
    saleTabs.forEach((tab) => {
        const isSelected = tab === selectedTab;
        tab.classList.toggle("is-active", isSelected);
        tab.setAttribute("aria-selected", String(isSelected));
        tab.tabIndex = isSelected ? 0 : -1;
    });

    if (saleGrid) {
        saleGrid.setAttribute("aria-labelledby", selectedTab.id);
    }
    updateSaleCards();
    if (moveFocus) {
        selectedTab.focus();
    }
};

saleTabs.forEach((tab, index) => {
    tab.addEventListener("click", () => selectSaleTab(tab));
    tab.addEventListener("keydown", (event) => {
        let nextIndex = index;
        if (event.key === "ArrowRight" || event.key === "ArrowDown") {
            nextIndex = (index + 1) % saleTabs.length;
        } else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
            nextIndex = (index - 1 + saleTabs.length) % saleTabs.length;
        } else if (event.key === "Home") {
            nextIndex = 0;
        } else if (event.key === "End") {
            nextIndex = saleTabs.length - 1;
        } else {
            return;
        }
        event.preventDefault();
        selectSaleTab(saleTabs[nextIndex], true);
    });
});

if (saleTabs.length) {
    selectSaleTab(saleTabs.find((tab) => tab.getAttribute("aria-selected") === "true") || saleTabs[0]);
}

if (countdown) {
    updateCountdown();
    window.setInterval(updateCountdown, 1000);
}

const systemClock = document.querySelector("[data-system-clock]");
if (systemClock) {
    const formatter = new Intl.DateTimeFormat("en-CA", {
        timeZone: systemClock.dataset.timezone,
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hourCycle: "h23",
        timeZoneName: "longOffset",
    });

    const updateSystemClock = () => {
        const parts = Object.fromEntries(formatter.formatToParts(new Date()).map(({ type, value }) => [type, value]));
        const offset = parts.timeZoneName.match(/GMT([+-]\d{2}:\d{2})/)?.[1] || "+00:00";
        const date = `${parts.year}-${parts.month}-${parts.day}`;
        const time = `${parts.hour}:${parts.minute}:${parts.second}`;
        systemClock.dateTime = `${date}T${time}${offset}`;
        systemClock.textContent = `System clock: ${date} ${time}`;
    };

    updateSystemClock();
    window.setInterval(updateSystemClock, 1000);
}
