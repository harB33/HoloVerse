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

const saleFilter = document.querySelector("[data-sale-filter]");
const saleTabs = [...document.querySelectorAll("[data-sale-tab]")];
const saleFilterTrigger = document.querySelector("[data-sale-filter-trigger]");
const saleFilterOptions = document.querySelector("[data-sale-filter-options]");
const saleFilterCurrent = document.querySelector("[data-sale-filter-current]");
const saleCards = [...document.querySelectorAll(".flash-sale-drop-card[data-sale-state]")];
const saleGrid = document.querySelector("#flash-sale-products");
const salePrevious = document.querySelector("[data-sale-previous]");
const saleNext = document.querySelector("[data-sale-next]");
const saleCarousel = document.querySelector("[data-sale-carousel]");
const saleCarouselControls = document.querySelector(".flash-sale-carousel-controls");
const flashSaleCard = document.querySelector("#flash-drop-spotlight, .flash-spotlight-card, .flash-sale-card");
const saleEmptyState = document.querySelector("[data-sale-empty]");
const saleDetail = (name) => document.querySelector(`[data-sale-detail="${name}"]`);

const syncProductBadgeWidth = () => {
    // Badges size naturally with max-width and ellipsis in CSS
};

const saleDescriptionToggle = document.querySelector("[data-sale-description-toggle]");
const countdownWrap = document.querySelector("[data-sale-countdown-wrap]");
const countdown = document.querySelector("[data-countdown]");
const countdownText = document.querySelector("[data-sale-countdown-text]");
let countdownDeadline = countdown ? new Date(countdown.dataset.countdown).getTime() : 0;

const updateCountdown = () => {
    if (!countdown) {
        return;
    }
    const rawDeadline = countdown.dataset.countdown;
    const deadlineTime = rawDeadline ? new Date(rawDeadline).getTime() : 0;
    const daysEl = countdown.querySelector("[data-countdown-days]");
    const hoursEl = countdown.querySelector("[data-countdown-hours]");
    const minutesEl = countdown.querySelector("[data-countdown-minutes]");
    const secondsEl = countdown.querySelector("[data-countdown-seconds]");

    if (!deadlineTime || isNaN(deadlineTime)) {
        if (daysEl) daysEl.textContent = "00";
        if (hoursEl) hoursEl.textContent = "00";
        if (minutesEl) minutesEl.textContent = "00";
        if (secondsEl) secondsEl.textContent = "00";
        return;
    }

    const remaining = Math.max(0, deadlineTime - Date.now());
    if (daysEl) daysEl.textContent = String(Math.floor(remaining / 86400000)).padStart(2, "0");
    if (hoursEl) hoursEl.textContent = String(Math.floor((remaining % 86400000) / 3600000)).padStart(2, "0");
    if (minutesEl) minutesEl.textContent = String(Math.floor((remaining % 3600000) / 60000)).padStart(2, "0");
    if (secondsEl) secondsEl.textContent = String(Math.floor((remaining % 60000) / 1000)).padStart(2, "0");
};

let selectedSaleState = saleFilter?.value || "live";
const initialSaleCard = saleCards.find((card) => card.dataset.variantId === saleDetail("variant-id")?.value);
initialSaleCard?.classList.add("is-selected");
initialSaleCard?.setAttribute("aria-current", "true");
syncProductBadgeWidth();
window.addEventListener("resize", syncProductBadgeWidth, { passive: true });

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
        const message = saleEmptyMessages[selectedSaleState] || "No sale items are available in this category.";
        const msgEl = saleEmptyState.querySelector("[data-sale-empty-message]") || saleEmptyState;
        msgEl.textContent = message;
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
    const full = saleDetail("description-full");
    if (!full) {
        return;
    }

    saleDescriptionToggle.setAttribute("aria-expanded", String(expanded));
    const label = saleDescriptionToggle.querySelector("span") || saleDescriptionToggle;
    label.textContent = expanded
        ? "Hide full product details"
        : "Read full product details";
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
    const live = product.state === "live" && stock > 0;
    const startStatus = product.startsLabel ? `Starts ${product.startsLabel}` : "Sale starts soon";
    const stockStatus = product.state === "archive"
        ? "Sale ended"
        : product.state === "live"
            ? stock > 0 ? `${stock} available` : "Currently sold out"
            : startStatus;
    const talent = product.talent || product.category || "hololive production";
    const stateLabel = product.state === "live"
        ? "Live flash sale drop"
        : (product.state === "archive" ? "Vault Archive drop" : "Upcoming drop");

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
        "stock-text": stockStatus,
        talent: talent,
        "state-label": stateLabel,
    };

    Object.entries(textValues).forEach(([name, value]) => {
        const element = saleDetail(name);
        if (element) {
            element.textContent = value;
        }
    });
    syncProductBadgeWidth();

    const productLinks = document.querySelectorAll(`[data-sale-detail="product-link"]`);
    productLinks.forEach((link) => {
        if (product.productUrl) {
            link.href = product.productUrl;
        }
    });

    const comparePriceEl = saleDetail("compare-price");
    if (comparePriceEl) {
        comparePriceEl.hidden = !product.comparePrice || product.comparePrice === product.price;
    }

    const discountEl = saleDetail("discount");
    if (discountEl) {
        discountEl.hidden = !product.discount || product.discount <= 0;
    }

    const capacity = Math.max(24, stock + 6);
    const claimedPercent = Math.min(100, Math.max(12, Math.round(((capacity - stock) / capacity) * 100)));
    const stockBar = saleDetail("stock-bar");
    if (stockBar) {
        stockBar.style.width = `${claimedPercent}%`;
    }
    const stockClaimedLabel = saleDetail("stock-claimed-label");
    if (stockClaimedLabel) {
        stockClaimedLabel.textContent = live ? `${stock} left` : (product.state === "archive" ? "Ended" : "0 left");
    }
    const stockTrack = document.querySelector(".flash-stock-track");
    if (stockTrack) {
        stockTrack.setAttribute("aria-valuenow", String(claimedPercent));
    }

    const descriptionPreview = saleDetail("description-preview");
    const descriptionFull = saleDetail("description-full");
    if (descriptionPreview && descriptionFull && saleDescriptionToggle) {
        descriptionPreview.textContent = product.description;
        const fullInner = descriptionFull.querySelector(".description-full-text") || descriptionFull;
        fullInner.textContent = product.description;
        descriptionPreview.hidden = false;
        descriptionFull.hidden = true;
        saleDescriptionToggle.hidden = product.description.length <= 160;
        saleDescriptionToggle.setAttribute("aria-expanded", "false");
        const label = saleDescriptionToggle.querySelector("span") || saleDescriptionToggle;
        label.textContent = "Read full product details";
    }

    const stockStatusElement = saleDetail("stock-status");
    if (stockStatusElement) {
        stockStatusElement.classList.toggle("is-unavailable", !live);
    }

    const cartForm = saleDetail("cart-form");
    const cartDisabled = saleDetail("cart-disabled");
    const cartMessage = saleDetail("cart-message");
    if (cartForm) {
        cartForm.hidden = !live;
        cartForm.dataset.toastMessage = `${product.name} added to your bag`;
    }
    if (cartDisabled) {
        cartDisabled.hidden = live;
        cartDisabled.textContent = product.state === "archive"
            ? "Sale ended"
            : (product.state === "live" ? "Sold out" : (startStatus || "Upcoming"));
    }
    if (cartMessage) {
        cartMessage.hidden = true;
    }

    const variantInput = saleDetail("variant-id");
    if (variantInput) {
        variantInput.value = String(product.variantId);
    }

    const defaultMidnightIso = new Date();
    defaultMidnightIso.setHours(23, 59, 59, 999);
    const nextDeadline = product.state === "live"
        ? (product.endsAt || defaultMidnightIso.toISOString())
        : product.state === "archive"
            ? ""
            : (product.startsAt || defaultMidnightIso.toISOString());
    if (countdownWrap) {
        countdownWrap.hidden = !nextDeadline;
    }
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
            ? "Drop window ends in"
            : product.state === "archive"
                ? "Sale ended"
                : "Drop window starts in";
    }

    const images = product.images?.length
        ? product.images
        : [{ image_url: "../assets/images/logo.png", alt_text: product.name }];
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

document.addEventListener("click", (event) => {
    const trigger = event.target instanceof Element ? event.target.closest("[data-sale-select-trigger]") : null;
    if (trigger) {
        const card = trigger.closest(".flash-sale-drop-card");
        if (card) {
            updateSaleProductDetails(card);
            flashSaleCard?.scrollIntoView({
                behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth",
                block: "start",
            });
        }
    }
});

const selectSaleFilter = (state) => {
    selectedSaleState = state;
    if (saleFilter && saleFilter.value !== state) {
        saleFilter.value = state;
    }
    saleTabs.forEach((tab) => {
        const matches = tab.dataset.saleTab === state;
        tab.classList.toggle("active", matches);
        tab.setAttribute("aria-selected", String(matches));
    });
    if (saleFilterTrigger && saleFilterCurrent && saleFilter) {
        saleFilterCurrent.textContent = saleFilter.selectedOptions[0]?.textContent.trim() || "";
        saleFilterOptions?.querySelectorAll("[data-sale-filter-option]").forEach((option) => {
            option.setAttribute("aria-selected", String(option.dataset.saleFilterOption === state));
        });
    }
    updateSaleCards();
};

saleTabs.forEach((tab) => {
    tab.addEventListener("click", () => {
        selectSaleFilter(tab.dataset.saleTab);
    });
});

if (saleFilter && saleFilterTrigger && saleFilterOptions && saleFilterCurrent) {
    const filterOptionButtons = [...saleFilterOptions.querySelectorAll("[data-sale-filter-option]")];
    const setFilterMenuOpen = (open, focusSelected = false) => {
        saleFilterTrigger.setAttribute("aria-expanded", String(open));
        saleFilterOptions.hidden = !open;
        if (open && focusSelected) {
            filterOptionButtons.find((option) => option.dataset.saleFilterOption === saleFilter.value)?.focus();
        }
    };

    const syncFilterSelection = () => {
        const selectedOption = saleFilter.selectedOptions[0];
        if (selectedOption) {
            saleFilterCurrent.textContent = selectedOption.textContent.trim();
        }
        filterOptionButtons.forEach((option) => {
            option.setAttribute("aria-selected", String(option.dataset.saleFilterOption === saleFilter.value));
        });
    };

    saleFilter.hidden = true;
    saleFilterTrigger.hidden = false;
    syncFilterSelection();

    saleFilterTrigger.addEventListener("click", () => {
        setFilterMenuOpen(saleFilterOptions.hidden, saleFilterOptions.hidden);
    });

    saleFilterTrigger.addEventListener("keydown", (event) => {
        if (event.key === "ArrowDown" || event.key === "ArrowUp" || event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            setFilterMenuOpen(true, true);
        }
    });

    filterOptionButtons.forEach((option, index) => {
        option.addEventListener("click", () => {
            saleFilter.value = option.dataset.saleFilterOption;
            saleFilter.dispatchEvent(new Event("change", { bubbles: true }));
            syncFilterSelection();
            setFilterMenuOpen(false);
            saleFilterTrigger.focus();
        });

        option.addEventListener("keydown", (event) => {
            let nextIndex;
            if (event.key === "ArrowDown") {
                nextIndex = (index + 1) % filterOptionButtons.length;
            } else if (event.key === "ArrowUp") {
                nextIndex = (index - 1 + filterOptionButtons.length) % filterOptionButtons.length;
            } else if (event.key === "Home") {
                nextIndex = 0;
            } else if (event.key === "End") {
                nextIndex = filterOptionButtons.length - 1;
            } else if (event.key === "Escape") {
                event.preventDefault();
                setFilterMenuOpen(false);
                saleFilterTrigger.focus();
                return;
            } else {
                return;
            }

            event.preventDefault();
            filterOptionButtons[nextIndex].focus();
        });
    });

    document.addEventListener("click", (event) => {
        if (!event.target.closest(".vault-filter-control")) {
            setFilterMenuOpen(false);
        }
    });
}

saleFilter?.addEventListener("change", () => {
    selectSaleFilter(saleFilter.value);
});

if (saleFilter) {
    selectSaleFilter(saleFilter.value);
}

if (countdown) {
    updateCountdown();
    window.setInterval(updateCountdown, 1000);
}

// Drop reminder button
const reminderBtn = document.querySelector("[data-drop-reminder-btn]");
if (reminderBtn) {
    const reminderBell = reminderBtn.querySelector("[data-reminder-icon-bell]");
    const reminderCheck = reminderBtn.querySelector("[data-reminder-icon-check]");
    const reminderLabel = reminderBtn.querySelector("[data-reminder-label]");
    const alertStorageKey = "holoverse.drop-alert";
    let dropAlertSaved = false;
    try {
        dropAlertSaved = JSON.parse(window.localStorage.getItem(alertStorageKey) || "false") === true;
    } catch {
        dropAlertSaved = false;
    }

    const syncReminderState = () => {
        reminderBtn.classList.toggle("is-saved", dropAlertSaved);
        if (reminderBell) reminderBell.hidden = dropAlertSaved;
        if (reminderCheck) reminderCheck.hidden = !dropAlertSaved;
        if (reminderLabel) reminderLabel.textContent = dropAlertSaved ? "Alert saved" : "Remind me";
    };
    syncReminderState();

    reminderBtn.addEventListener("click", () => {
        dropAlertSaved = !dropAlertSaved;
        try {
            window.localStorage.setItem(alertStorageKey, JSON.stringify(dropAlertSaved));
        } catch {}
        syncReminderState();
        if (typeof window.showHoloVerseToast === "function") {
            window.showHoloVerseToast(
                dropAlertSaved ? "Flash drop alert saved in this browser." : "Flash drop alert removed."
            );
        }
    });
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
