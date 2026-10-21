const recentSearchStorageKey = "holoverse-recent-searches";
const maxVisibleSearches = 5;

const readRecentSearches = () => {
    try {
        const savedSearches = JSON.parse(localStorage.getItem(recentSearchStorageKey) ?? "[]");
        return Array.isArray(savedSearches)
            ? savedSearches.filter((search) => typeof search === "string" && search.trim() !== "").slice(0, maxVisibleSearches)
            : [];
    } catch (error) {
        console.error("Unable to read recent searches.", error);
        return [];
    }
};

document.querySelectorAll("[data-catalog-search-input]").forEach((input) => {
    const suggestions = document.getElementById(input.getAttribute("aria-controls"));
    const form = input.closest("form");
    const recentGroup = suggestions?.querySelector("[data-recent-searches]");
    const recentList = suggestions?.querySelector("[data-recent-search-list]");
    const popularGroup = suggestions?.querySelector("[data-popular-searches]");
    const popularButtons = [...(popularGroup?.querySelectorAll("[data-search-suggestion]") ?? [])];
    if (!suggestions || !form || !recentGroup || !recentList || !popularGroup) {
        return;
    }

    const setSuggestionsVisible = (visible) => {
        if (visible) {
            renderSuggestions();
        }
        suggestions.hidden = !visible;
        input.setAttribute("aria-expanded", String(visible));
    };

    const renderSuggestions = () => {
        const recentSearches = readRecentSearches();
        const visibleRecent = recentSearches.slice(0, maxVisibleSearches);
        recentList.replaceChildren(...visibleRecent.map((search) => {
            const button = document.createElement("button");
            button.type = "button";
            button.dataset.searchSuggestion = search;
            button.textContent = search;
            return button;
        }));
        recentGroup.hidden = visibleRecent.length === 0;

        let remaining = maxVisibleSearches - visibleRecent.length;
        let visiblePopularCount = 0;
        popularButtons.forEach((button) => {
            const duplicatesRecent = visibleRecent.some(
                (search) => search.toLocaleLowerCase() === button.dataset.searchSuggestion.toLocaleLowerCase()
            );
            const show = remaining > 0 && !duplicatesRecent;
            button.hidden = !show;
            if (show) {
                remaining -= 1;
                visiblePopularCount += 1;
            }
        });
        popularGroup.hidden = visiblePopularCount === 0;
    };

    input.addEventListener("focus", () => setSuggestionsVisible(true));
    input.addEventListener("click", () => setSuggestionsVisible(true));
    input.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            setSuggestionsVisible(false);
        } else if (event.key === "ArrowDown" && !suggestions.hidden) {
            event.preventDefault();
            suggestions.querySelector("button:not([hidden])")?.focus();
        }
    });

    suggestions.addEventListener("click", (event) => {
        const button = event.target.closest("[data-search-suggestion]");
        if (!button) {
            return;
        }
        input.value = button.dataset.searchSuggestion;
        setSuggestionsVisible(false);
        form.requestSubmit();
    });

    suggestions.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            setSuggestionsVisible(false);
            input.focus();
        }
    });

    document.addEventListener("click", (event) => {
        if (!form.contains(event.target)) {
            setSuggestionsVisible(false);
        }
    });

    form.addEventListener("submit", () => {
        const search = input.value.trim();
        if (search !== "") {
            const recentSearches = readRecentSearches().filter(
                (recent) => recent.toLocaleLowerCase() !== search.toLocaleLowerCase()
            );
            try {
                localStorage.setItem(
                    recentSearchStorageKey,
                    JSON.stringify([search, ...recentSearches].slice(0, maxVisibleSearches))
                );
            } catch (error) {
                console.error("Unable to save recent searches.", error);
            }
        }
        setSuggestionsVisible(false);
    });
});
