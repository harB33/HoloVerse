const galleryImage = document.querySelector(".flash-product-image > img");
const galleryThumbnails = document.querySelectorAll(".flash-thumbnail");

if (galleryImage && galleryThumbnails.length) {
    galleryThumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener("click", () => {
            galleryImage.src = thumbnail.dataset.gallerySrc;
            galleryImage.alt = thumbnail.dataset.galleryAlt;

            galleryThumbnails.forEach((item) => {
                const isSelected = item === thumbnail;
                item.classList.toggle("is-selected", isSelected);
                item.setAttribute("aria-pressed", String(isSelected));
            });
        });
    });
}

const saleTabs = [...document.querySelectorAll("[data-sale-filter]")];
const saleCards = [...document.querySelectorAll(".vault-product-card[data-sale-state]")];
const saleGrid = document.querySelector("#flash-sale-products");
const saleEmptyState = document.querySelector("[data-sale-empty]");
const saleEmptyMessages = {
    live: "There are no discounted products available right now.",
    starting: "No flash sales are scheduled to start within the next hour.",
    tomorrow: "No flash sales are scheduled for tomorrow.",
    archive: "There are no completed flash sales in the archive yet.",
};

const selectSaleTab = (selectedTab, moveFocus = false) => {
    const state = selectedTab.dataset.saleFilter;
    let visibleCards = 0;

    saleTabs.forEach((tab) => {
        const isSelected = tab === selectedTab;
        tab.classList.toggle("is-active", isSelected);
        tab.setAttribute("aria-selected", String(isSelected));
        tab.tabIndex = isSelected ? 0 : -1;
    });

    saleCards.forEach((card) => {
        const isVisible = card.dataset.saleState === state;
        card.hidden = !isVisible;
        visibleCards += Number(isVisible);
    });

    if (saleGrid) {
        saleGrid.setAttribute("aria-labelledby", selectedTab.id);
    }
    if (saleEmptyState) {
        saleEmptyState.textContent = saleEmptyMessages[state] || "No sale items are available in this category.";
        saleEmptyState.hidden = visibleCards > 0;
    }
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

const favoriteStorageKey = "holoverse-flash-sale-favorites";
let favoriteIds = [];
try {
    const savedFavorites = JSON.parse(localStorage.getItem(favoriteStorageKey) || "[]");
    if (Array.isArray(savedFavorites) && savedFavorites.every((id) => typeof id === "string")) {
        favoriteIds = savedFavorites;
    }
} catch (error) {
    console.warn("Could not load flash sale favorites.", error);
}

const updateFavoriteButtons = () => {
    document.querySelectorAll("[data-favorite-button]").forEach((button) => {
        const card = button.closest("[data-variant-id]");
        if (!card) {
            return;
        }
        const isFavorite = favoriteIds.includes(card.dataset.variantId);
        button.setAttribute("aria-pressed", String(isFavorite));
        button.textContent = isFavorite ? "♥" : "♡";
        button.setAttribute("aria-label", `${isFavorite ? "Remove saved item" : "Save"} ${card.querySelector("h3")?.textContent || "product"}`);
    });
};

document.querySelectorAll("[data-favorite-button]").forEach((button) => {
    button.addEventListener("click", () => {
        const card = button.closest("[data-variant-id]");
        if (!card) {
            return;
        }
        const variantId = card.dataset.variantId;
        favoriteIds = favoriteIds.includes(variantId)
            ? favoriteIds.filter((id) => id !== variantId)
            : [...favoriteIds, variantId];
        try {
            localStorage.setItem(favoriteStorageKey, JSON.stringify(favoriteIds));
        } catch (error) {
            console.warn("Could not save flash sale favorites.", error);
        }
        updateFavoriteButtons();
    });
});
updateFavoriteButtons();

const countdown = document.querySelector("[data-countdown]");
if (countdown) {
    const deadline = new Date(countdown.dataset.countdown).getTime();
    const days = countdown.querySelector("[data-countdown-days]");
    const hours = countdown.querySelector("[data-countdown-hours]");
    const minutes = countdown.querySelector("[data-countdown-minutes]");
    const seconds = countdown.querySelector("[data-countdown-seconds]");
    const updateCountdown = () => {
        const remaining = Math.max(0, deadline - Date.now());
        days.textContent = String(Math.floor(remaining / 86400000)).padStart(2, "0");
        hours.textContent = String(Math.floor((remaining % 86400000) / 3600000)).padStart(2, "0");
        minutes.textContent = String(Math.floor((remaining % 3600000) / 60000)).padStart(2, "0");
        seconds.textContent = String(Math.floor((remaining % 60000) / 1000)).padStart(2, "0");
    };
    updateCountdown();
    window.setInterval(updateCountdown, 1000);
}
