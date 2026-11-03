// Dependency-free Canvas Confetti Burst
const launchHeroConfetti = () => {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        return;
    }
    const canvas = document.createElement("canvas");
    canvas.style.cssText = "position:fixed;inset:0;width:100vw;height:100vh;pointer-events:none;z-index:9999;";
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    document.body.appendChild(canvas);

    const ctx = canvas.getContext("2d");
    if (!ctx) {
        canvas.remove();
        return;
    }

    const heroEl = document.querySelector("#flash-drop-spotlight");
    const heroStyles = heroEl ? getComputedStyle(heroEl) : null;
    const themeColor = heroStyles?.getPropertyValue("--hero-theme").trim() || "#00c3ff";
    const accentColor = heroStyles?.getPropertyValue("--hero-accent").trim() || "#ec4899";
    const colors = [themeColor, accentColor, "#22d3ee", "#f472b6", "#facc15", "#ffffff", "#34d399"];

    const originX = canvas.width * 0.45;
    const originY = canvas.height * 0.6;
    const particles = Array.from({ length: 110 }, () => {
        const angle = -Math.PI / 2 + (Math.random() - 0.5) * 1.35;
        const speed = 7 + Math.random() * 12;
        return {
            x: originX + (Math.random() - 0.5) * 80,
            y: originY,
            vx: Math.cos(angle) * speed,
            vy: Math.sin(angle) * speed,
            size: 5 + Math.random() * 6,
            color: colors[Math.floor(Math.random() * colors.length)],
            rotation: Math.random() * Math.PI * 2,
            vr: (Math.random() - 0.5) * 0.25,
            opacity: 1,
        };
    });

    let frame = 0;
    const animate = () => {
        frame += 1;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let activeCount = 0;

        particles.forEach((p) => {
            p.x += p.vx;
            p.y += p.vy;
            p.vy += 0.34;
            p.vx *= 0.99;
            p.rotation += p.vr;
            if (frame > 45) {
                p.opacity = Math.max(0, p.opacity - 0.024);
            }
            if (p.opacity > 0 && p.y < canvas.height + 40) {
                activeCount += 1;
                ctx.save();
                ctx.globalAlpha = p.opacity;
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rotation);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.size / 2, -p.size / 3, p.size, p.size * 0.66);
                ctx.restore();
            }
        });

        if (activeCount > 0 && frame < 120) {
            window.requestAnimationFrame(animate);
        } else {
            canvas.remove();
        }
    };
    window.requestAnimationFrame(animate);
};

const galleryImage = document.querySelector(".flash-product-main-image");
const galleryBackdrop = document.querySelector(".flash-product-backdrop");
const galleryThumbnailStrip = document.querySelector(".flash-gallery .product-gallery-thumbnails");
const heroPoseToggleBtn = document.querySelector("[data-hero-pose-toggle]");
const heroPoseLabelEl = document.querySelector("[data-hero-pose-label]");
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

const syncPoseToggleLabel = () => {
    if (!heroPoseToggleBtn) {
        return;
    }
    const visibleThumbnails = galleryThumbnails.filter((btn) => !btn.hidden);
    heroPoseToggleBtn.hidden = visibleThumbnails.length < 2;
    if (heroPoseLabelEl && visibleThumbnails.length >= 2) {
        const nextIndex = (activeGalleryIndex + 1) % galleryThumbnails.length;
        const nextBtn = galleryThumbnails[nextIndex];
        heroPoseLabelEl.textContent = nextBtn?.dataset.modeLabel || "Switch Visual";
    }
};

const bindGalleryThumbnails = () => {
    galleryThumbnails = [...document.querySelectorAll(".flash-gallery [data-gallery-image]")];
    galleryThumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener("mouseenter", () => showGalleryImage(index));
        thumbnail.addEventListener("click", () => {
            showGalleryImage(index);
        });
    });
    syncPoseToggleLabel();
};

const showGalleryImage = (index, scrollThumbnail = false) => {
    if (!galleryThumbnails.length || !galleryImage) {
        return;
    }

    activeGalleryIndex = (index + galleryThumbnails.length) % galleryThumbnails.length;
    const activeThumbnail = galleryThumbnails[activeGalleryIndex];
    galleryImage.src = activeThumbnail.dataset.imageSrc;
    galleryImage.alt = activeThumbnail.dataset.imageAlt || "";
    updateStockBarGradientFromImage();
    if (galleryBackdrop) {
        galleryBackdrop.src = activeThumbnail.dataset.imageSrc;
    }
    galleryThumbnails.forEach((thumbnail, thumbnailIndex) => {
        const selected = thumbnailIndex === activeGalleryIndex;
        thumbnail.classList.toggle("is-active", selected);
        thumbnail.classList.toggle("border-cyan-400", selected);
        thumbnail.classList.toggle("shadow-[0_0_10px_rgba(0,195,255,0.4)]", selected);
        thumbnail.classList.toggle("border-slate-800", !selected);
        thumbnail.classList.toggle("opacity-75", !selected);
        thumbnail.setAttribute("aria-pressed", String(selected));
    });
    syncPoseToggleLabel();

    if (scrollThumbnail) {
        activeThumbnail.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "nearest" });
    }
};

const imagePaletteWarnings = new Set();
const updateStockBarGradientFromImage = () => {
    const stockBar = document.querySelector('[data-sale-detail="stock-bar"]');
    const buyButton = document.querySelector("[data-flash-buy-btn]");
    const salePrice = document.querySelector('[data-sale-detail="price"]');
    if ((!stockBar && !buyButton && !salePrice) || !galleryImage) {
        return;
    }

    const setGradient = (gradient, priceColor = "var(--hero-theme)") => {
        if (stockBar) {
            stockBar.style.background = gradient;
        }
        if (buyButton) {
            buyButton.style.background = gradient;
        }
        if (salePrice) {
            salePrice.style.color = priceColor;
        }
    };
    const setThemeGradient = () => setGradient("linear-gradient(90deg, var(--hero-accent) 0%, var(--hero-theme) 100%)");

    if (!galleryImage.complete || !galleryImage.naturalWidth) {
        setThemeGradient();
        return;
    }

    try {
        const canvas = document.createElement("canvas");
        canvas.width = 48;
        canvas.height = 48;
        const context = canvas.getContext("2d", { willReadFrequently: true });
        if (!context) {
            setThemeGradient();
            return;
        }

        context.drawImage(galleryImage, 0, 0, canvas.width, canvas.height);
        const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
        const buckets = new Map();
        for (let index = 0; index < pixels.length; index += 4) {
            const red = pixels[index];
            const green = pixels[index + 1];
            const blue = pixels[index + 2];
            const alpha = pixels[index + 3];
            const max = Math.max(red, green, blue);
            const min = Math.min(red, green, blue);
            const saturation = max === 0 ? 0 : (max - min) / max;
            if (alpha < 128 || max < 40 || max > 242 || saturation < 0.2) {
                continue;
            }

            const key = [red, green, blue].map((channel) => Math.round(channel / 32)).join(",");
            const bucket = buckets.get(key) || { red: 0, green: 0, blue: 0, count: 0, saturation: 0 };
            bucket.red += red;
            bucket.green += green;
            bucket.blue += blue;
            bucket.count += 1;
            bucket.saturation += saturation;
            buckets.set(key, bucket);
        }

        const colors = [...buckets.values()]
            .sort((a, b) => b.saturation - a.saturation)
            .reduce((selected, bucket) => {
                if (selected.length >= 3) {
                    return selected;
                }
                const color = [bucket.red, bucket.green, bucket.blue].map((value) => Math.round(value / bucket.count));
                const isDistinct = selected.every((existing) => {
                    const distance = color.reduce((sum, channel, channelIndex) => sum + (channel - existing[channelIndex]) ** 2, 0);
                    return distance > 45 ** 2;
                });
                if (isDistinct) {
                    selected.push(color);
                }
                return selected;
            }, [])
            .map((color) => `#${color.map((channel) => channel.toString(16).padStart(2, "0")).join("")}`);

        if (colors.length < 2) {
            setThemeGradient();
            return;
        }

        const stops = colors.map((color, index) => `${color} ${(index / (colors.length - 1)) * 100}%`).join(", ");
        setGradient(`linear-gradient(90deg, ${stops})`, colors[0]);
    } catch (error) {
        setThemeGradient();
        const imageSource = galleryImage.currentSrc || galleryImage.src;
        if (!imagePaletteWarnings.has(imageSource)) {
            imagePaletteWarnings.add(imageSource);
            console.warn("Could not sample colors from the flash-sale image; using its product theme colors.", error);
        }
    }
};

galleryImage?.addEventListener("load", updateStockBarGradientFromImage);
updateStockBarGradientFromImage();

bindGalleryThumbnails();

heroPoseToggleBtn?.addEventListener("click", () => {
    showGalleryImage(activeGalleryIndex + 1, true);
});

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
const flashSaleHeroBackdrop = document.querySelector(".flash-sale-hero-backdrop");
const saleEmptyState = document.querySelector("[data-sale-empty]");
const saleDetail = (name) => document.querySelector(`[data-sale-detail="${name}"]`);

const syncProductBadgeWidth = () => {
    // Badges size naturally with max-width and ellipsis in CSS
};

const saleDescriptionToggle = document.querySelector("[data-sale-description-toggle]");
const countdownWrap = document.querySelector("[data-sale-countdown-wrap]");
const countdown = document.querySelector("[data-countdown]");
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
    const msEl = countdown.querySelector("[data-countdown-ms]");

    if (!deadlineTime || isNaN(deadlineTime)) {
        if (daysEl) daysEl.textContent = "00";
        if (hoursEl) hoursEl.textContent = "00";
        if (minutesEl) minutesEl.textContent = "00";
        if (secondsEl) secondsEl.textContent = "00";
        if (msEl) msEl.textContent = "00";
        return;
    }

    const remaining = Math.max(0, deadlineTime - Date.now());
    if (daysEl) daysEl.textContent = String(Math.floor(remaining / 86400000)).padStart(2, "0");
    if (hoursEl) hoursEl.textContent = String(Math.floor((remaining % 86400000) / 3600000)).padStart(2, "0");
    if (minutesEl) minutesEl.textContent = String(Math.floor((remaining % 3600000) / 60000)).padStart(2, "0");
    if (secondsEl) secondsEl.textContent = String(Math.floor((remaining % 60000) / 1000)).padStart(2, "0");
    if (msEl) {
        const centiseconds = remaining > 0 ? Math.floor((remaining % 1000) / 10) : 0;
        msEl.textContent = String(centiseconds).padStart(2, "0");
    }
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

const buildHeroVisualModes = (product) => {
    const modes = [];
    const seen = new Set();
    const fallbackMerchUrl = product.images?.[0]?.image_url || "../assets/images/logo.png";

    const pushMode = (url, alt, label) => {
        const cleanUrl = (url || "").trim();
        if (!cleanUrl || seen.has(cleanUrl)) {
            return;
        }
        seen.add(cleanUrl);
        modes.push({
            image_url: cleanUrl,
            alt_text: alt || product.name || "Flash drop visual",
            mode_label: label,
            fallback_url: fallbackMerchUrl,
        });
    };

    const talentLabel = product.talent || "Talent";
    if (product.standingVisual) {
        pushMode(product.standingVisual, `${talentLabel} — Full Standing Visual`, "STANDING VISUAL");
    }
    if (product.prVisualAlt && product.prVisualAlt !== product.standingVisual) {
        pushMode(product.prVisualAlt, `${talentLabel} — Alt Visual`, "ALT POSE / THUMB");
    }

    if (Array.isArray(product.images) && product.images.length) {
        product.images.forEach((img, idx) => {
            pushMode(
                img.image_url,
                img.alt_text || `${product.name} — View ${idx + 1}`,
                idx === 0 ? "MERCH BUNDLE" : `GALLERY #${idx + 1}`
            );
        });
    }

    if (!modes.length) {
        pushMode("../assets/images/logo.png", product.name || "hololive drop", "MERCH BUNDLE");
    }

    return modes;
};

const syncActiveSaleCard = (product, sourceEl = null) => {
    const targetVariantId = String(product.variantId ?? "");
    const targetProductId = String(product.id ?? "");

    saleCards.forEach((saleCard) => {
        let isMatch = saleCard === sourceEl;
        if (!isMatch && saleCard.dataset.saleProduct) {
            try {
                const cardData = JSON.parse(saleCard.dataset.saleProduct);
                isMatch = (targetVariantId && String(cardData.variantId ?? "") === targetVariantId)
                    || (targetProductId && String(cardData.id ?? "") === targetProductId);
            } catch {
                isMatch = false;
            }
        }
        saleCard.classList.toggle("is-selected", isMatch);
        if (isMatch) {
            saleCard.setAttribute("aria-current", "true");
        } else {
            saleCard.removeAttribute("aria-current");
        }
    });
};

const applySaleProductToHero = (product, sourceEl = null) => {
    if (!flashSaleCard || !galleryImage || !product) {
        return;
    }

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

    const themeColor = product.themeColor || "#26B7FF";
    const accentColor = product.accentColor || "#38BDF8";
    const glowColor = product.glowColor || "rgba(38, 183, 255, 0.45)";

    const heroStyleTarget = flashSaleHeroBackdrop || flashSaleCard;
    heroStyleTarget.style.setProperty("--hero-theme", themeColor);
    heroStyleTarget.style.setProperty("--hero-accent", accentColor);
    heroStyleTarget.style.setProperty("--hero-glow", glowColor);

    const capacity = Math.max(Number(product.capacity) || 24, stock + 6);
    const claimedPercent = Number(product.claimedPercent)
        || Math.min(98, Math.max(12, Math.round(((capacity - stock) / capacity) * 100)));

    const numericPrice = Number(product.priceRaw)
        || Number(String(product.price || "").replace(/[^0-9.]/g, ""))
        || 0;
    const approxUsd = product.approxUsd || `$${(numericPrice / 56).toFixed(2)}`;
    const approxJpy = product.approxJpy || `¥${Math.round(numericPrice * 2.7).toLocaleString()}`;

    const textValues = {
        breadcrumb: product.name,
        "product-badge": product.name,
        "variant-caption": product.variant || "Official Cover Corp Key Visual",
        "category-caption": product.category,
        category: product.category,
        variant: product.variant,
        name: product.name,
        price: product.price,
        "compare-price": product.comparePrice,
        discount: `SAVE ${product.discount}%`,
        "sale-note": product.state === "archive" ? "This flash sale has ended" : product.state === "live" ? "While supplies last" : startStatus,
        "stock-text": stockStatus,
        talent: talent,
        "state-label": stateLabel,
        "jp-name": product.japaneseName || "ホロライブ公式",
        "jp-watermark": product.japaneseName || "ホロライブ公式",
        unit: product.unit || product.category || "hololive Official",
        generation: product.generation || "Special Drop",
        "fan-name": product.fanName || "Official Holofan",
        rarity: product.rarity || "SSR LIMITED",
        "approx-price": `(~${approxUsd} USD · ~${approxJpy} JPY)`,
        "stock-capacity": `/ ${capacity} total`,
        "stock-percent-label": `${claimedPercent}% CLAIMED`,
    };

    Object.entries(textValues).forEach(([name, value]) => {
        const elements = document.querySelectorAll(`[data-sale-detail="${name}"]`);
        elements.forEach((element) => {
            element.textContent = value;
        });
    });

    const variantWrap = saleDetail("variant-wrap");
    if (variantWrap) {
        variantWrap.hidden = !product.variant;
    }

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

    const stockBar = saleDetail("stock-bar");
    if (stockBar) {
        stockBar.style.width = `${claimedPercent}%`;
    }
    const stockClaimedLabel = saleDetail("stock-claimed-label");
    if (stockClaimedLabel) {
        stockClaimedLabel.textContent = live ? `${stock} units left` : (product.state === "archive" ? "Sale ended" : "0 units left");
    }
    const stockTrack = document.querySelector(".flash-stock-track");
    if (stockTrack) {
        stockTrack.setAttribute("aria-valuenow", String(claimedPercent));
    }

    const descriptionPreview = saleDetail("description-preview");
    const descriptionFull = saleDetail("description-full");
    if (descriptionPreview && descriptionFull && saleDescriptionToggle) {
        descriptionPreview.textContent = product.description || "";
        const fullInner = descriptionFull.querySelector(".description-full-text") || descriptionFull;
        fullInner.textContent = product.description || "";
        descriptionPreview.hidden = false;
        descriptionFull.hidden = true;
        saleDescriptionToggle.hidden = (product.description || "").length <= 160;
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
    const visualModes = buildHeroVisualModes(product);
    const fallbackMerchSrc = product.images?.[0]?.image_url || "../assets/images/logo.png";
    galleryImage.dataset.fallbackSrc = fallbackMerchSrc;

    galleryThumbnailStrip?.replaceChildren();
    visualModes.forEach((mode, index) => {
        const thumbnail = document.createElement("button");
        thumbnail.className = `product-gallery-thumbnail w-11 h-11 rounded-none border p-1 bg-slate-950/85 cursor-pointer transition-all shrink-0 ${index === 0 ? "is-active border-cyan-400 shadow-[0_0_10px_rgba(0,195,255,0.4)]" : "border-slate-800 opacity-75 hover:opacity-100"}`;
        thumbnail.type = "button";
        thumbnail.dataset.galleryImage = "";
        thumbnail.dataset.imageSrc = mode.image_url;
        thumbnail.dataset.imageAlt = mode.alt_text;
        thumbnail.dataset.modeLabel = mode.mode_label;
        thumbnail.setAttribute("aria-label", `Show ${mode.mode_label}`);
        thumbnail.setAttribute("aria-pressed", String(index === 0));

        const thumbnailImage = document.createElement("img");
        thumbnailImage.src = mode.image_url;
        thumbnailImage.alt = "";
        thumbnailImage.className = "w-full h-full object-contain";
        thumbnailImage.loading = "lazy";
        thumbnailImage.decoding = "async";
        thumbnailImage.onerror = () => {
            if (thumbnailImage.src !== fallbackMerchSrc) {
                thumbnailImage.src = fallbackMerchSrc;
                thumbnail.dataset.imageSrc = fallbackMerchSrc;
            }
        };
        thumbnail.append(thumbnailImage);
        galleryThumbnailStrip?.append(thumbnail);
    });
    bindGalleryThumbnails();
    const galleryPrevious = document.querySelector("[data-gallery-previous]");
    const galleryNext = document.querySelector("[data-gallery-next]");
    if (galleryPrevious) {
        galleryPrevious.hidden = visualModes.length < 2;
    }
    if (galleryNext) {
        galleryNext.hidden = visualModes.length < 2;
    }
    showGalleryImage(0);

    syncActiveSaleCard(product, sourceEl);
};

const updateSaleProductDetails = (card) => {
    if (!card?.dataset?.saleProduct) {
        return;
    }
    try {
        const product = JSON.parse(card.dataset.saleProduct);
        applySaleProductToHero(product, card);
    } catch {}
};

const heroBuyBtn = document.querySelector("[data-flash-buy-btn]");
if (heroBuyBtn) {
    heroBuyBtn.addEventListener("click", () => {
        launchHeroConfetti();
    });
}

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
    window.setInterval(updateCountdown, 40);
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
