(() => {
    const script = document.currentScript;
    const fields = {
        region: {
            input: document.querySelector("#checkout-region"),
            suggestions: document.querySelector("#checkout-region-suggestions"),
        },
        city: {
            input: document.querySelector("#checkout-city"),
            suggestions: document.querySelector("#checkout-city-suggestions"),
        },
        barangay: {
            input: document.querySelector("#checkout-barangay"),
            suggestions: document.querySelector("#checkout-barangay-suggestions"),
        },
    };
    const status = document.querySelector("#checkout-location-status");

    if (
        !script || !status
        || Object.values(fields).some(({ input, suggestions }) => !input || !suggestions)
    ) {
        return;
    }

    const apiBase = "https://psgc.gitlab.io/api";
    const initialRegion = script.dataset.initialRegion || "";
    const initialCity = script.dataset.initialCity || "";
    const initialBarangay = script.dataset.initialBarangay || "";
    let locationsByName = new Map();
    let citiesByName = new Map();
    let regionPlaces = [];
    let cityPlaces = [];
    let barangayPlaces = [];
    let activeRegionCode = null;
    let activeCityCode = null;
    let cityRequestId = 0;
    let barangayRequestId = 0;

    const setStatus = (message, isError = false) => {
        status.textContent = message;
        status.classList.toggle("is-error", isError);
    };

    const normalizeName = (name) => name.trim().toLocaleLowerCase();
    const findByName = (places, name) => places.get(normalizeName(name));

    const closeSuggestions = (field) => {
        field.suggestions.hidden = true;
        field.input.setAttribute("aria-expanded", "false");
        field.input.removeAttribute("aria-activedescendant");
    };

    const renderSuggestions = (field, places, query = field.input.value) => {
        const normalizedQuery = normalizeName(query);
        const matches = places
            .filter((place) => !normalizedQuery || normalizeName(place.name).includes(normalizedQuery))
            .slice(0, 20);

        field.suggestions.replaceChildren();
        matches.forEach((place, index) => {
            const option = document.createElement("button");
            option.type = "button";
            option.id = `${field.input.id}-suggestion-${index}`;
            option.className = "checkout-location-suggestion";
            option.setAttribute("role", "option");
            option.setAttribute("aria-selected", "false");
            option.textContent = place.name;
            option.addEventListener("pointerdown", (event) => event.preventDefault());
            option.addEventListener("click", () => {
                field.input.value = place.name;
                closeSuggestions(field);
                field.input.dispatchEvent(new Event("input", { bubbles: true }));
                field.input.dispatchEvent(new Event("change", { bubbles: true }));
                field.input.focus();
            });
            field.suggestions.append(option);
        });

        field.suggestions.hidden = matches.length === 0;
        field.input.setAttribute("aria-expanded", String(matches.length > 0));
        field.input.removeAttribute("aria-activedescendant");
        return matches;
    };

    const fetchPlaces = async (path) => {
        const response = await fetch(`${apiBase}${path}`, {
            headers: { Accept: "application/json" },
        });
        if (!response.ok) {
            throw new Error(`PSGC API request failed with status ${response.status}.`);
        }

        const places = await response.json();
        if (!Array.isArray(places) || places.some((place) => (
            typeof place.code !== "string" || typeof place.name !== "string"
        ))) {
            throw new Error("PSGC API returned an invalid location list.");
        }
        return places.sort((first, second) => first.name.localeCompare(second.name));
    };

    const clearCityAndBarangaySuggestions = () => {
        cityRequestId += 1;
        barangayRequestId += 1;
        activeCityCode = null;
        cityPlaces = [];
        barangayPlaces = [];
        citiesByName = new Map();
        closeSuggestions(fields.city);
        closeSuggestions(fields.barangay);
    };

    const loadCities = async (location, selectedBarangay = "") => {
        if (activeRegionCode === location.code) {
            return;
        }
        activeRegionCode = location.code;
        const requestId = ++cityRequestId;
        barangayRequestId += 1;
        activeCityCode = null;
        cityPlaces = [];
        barangayPlaces = [];
        citiesByName = new Map();
        closeSuggestions(fields.city);
        closeSuggestions(fields.barangay);
        setStatus("Loading city and municipality suggestions...");

        const path = location.type === "region"
            ? `/regions/${encodeURIComponent(location.code)}/cities-municipalities/`
            : `/provinces/${encodeURIComponent(location.code)}/cities-municipalities/`;

        try {
            cityPlaces = await fetchPlaces(path);
            if (requestId !== cityRequestId) {
                return;
            }

            citiesByName = new Map(cityPlaces.map((city) => [normalizeName(city.name), city]));
            if (document.activeElement === fields.city.input) {
                renderSuggestions(fields.city, cityPlaces);
            }
            setStatus("");

            const selectedCity = findByName(citiesByName, fields.city.input.value);
            if (selectedCity) {
                await loadBarangays(selectedCity, selectedBarangay);
            }
        } catch (error) {
            if (requestId !== cityRequestId) {
                return;
            }
            console.error("Could not load PSGC cities and municipalities.", error);
            setStatus("Could not load city suggestions. You can still enter the city and barangay manually.", true);
        }
    };

    const loadBarangays = async (city, selectedBarangay = "") => {
        if (activeCityCode === city.code) {
            return;
        }
        activeCityCode = city.code;
        const requestId = ++barangayRequestId;
        barangayPlaces = [];
        closeSuggestions(fields.barangay);
        setStatus("Loading barangay suggestions...");

        try {
            barangayPlaces = await fetchPlaces(
                `/cities-municipalities/${encodeURIComponent(city.code)}/barangays/`
            );
            if (requestId !== barangayRequestId) {
                return;
            }

            if (selectedBarangay) {
                const matchedBarangay = barangayPlaces.find(
                    (barangay) => normalizeName(barangay.name) === normalizeName(selectedBarangay)
                );
                if (matchedBarangay) {
                    fields.barangay.input.value = matchedBarangay.name;
                }
            }
            if (document.activeElement === fields.barangay.input) {
                renderSuggestions(fields.barangay, barangayPlaces);
            }
            setStatus("");
        } catch (error) {
            if (requestId !== barangayRequestId) {
                return;
            }
            console.error("Could not load PSGC barangays.", error);
            setStatus("Could not load barangay suggestions. You can still enter the barangay manually.", true);
        }
    };

    const updateRegionSuggestions = () => {
        renderSuggestions(fields.region, regionPlaces);
        const location = findByName(locationsByName, fields.region.input.value);
        if (!location) {
            if (activeRegionCode !== null) {
                activeRegionCode = null;
                fields.city.input.value = "";
                fields.barangay.input.value = "";
                clearCityAndBarangaySuggestions();
            }
            setStatus("");
            return;
        }
        if (activeRegionCode !== location.code) {
            fields.city.input.value = "";
            fields.barangay.input.value = "";
            activeCityCode = null;
            loadCities(location);
        }
    };

    const updateCitySuggestions = () => {
        renderSuggestions(fields.city, cityPlaces);
        const city = findByName(citiesByName, fields.city.input.value);
        if (!city) {
            if (activeCityCode !== null) {
                activeCityCode = null;
                fields.barangay.input.value = "";
                barangayRequestId += 1;
                barangayPlaces = [];
                closeSuggestions(fields.barangay);
            }
            setStatus("");
            return;
        }
        if (activeCityCode !== city.code) {
            fields.barangay.input.value = "";
            loadBarangays(city);
        }
    };

    const updateBarangaySuggestions = () => {
        renderSuggestions(fields.barangay, barangayPlaces);
    };

    const attachAutocomplete = (field, getPlaces, onInput) => {
        field.input.addEventListener("focus", () => renderSuggestions(field, getPlaces()));
        field.input.addEventListener("input", onInput);
        field.input.addEventListener("change", onInput);
        field.input.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                closeSuggestions(field);
                return;
            }

            if (!["ArrowDown", "ArrowUp", "Enter"].includes(event.key) || field.suggestions.hidden) {
                return;
            }
            const options = [...field.suggestions.querySelectorAll('[role="option"]')];
            if (options.length === 0) {
                return;
            }
            event.preventDefault();
            const currentIndex = options.findIndex((option) => option.id === field.input.getAttribute("aria-activedescendant"));
            const nextIndex = event.key === "ArrowUp"
                ? (currentIndex <= 0 ? options.length - 1 : currentIndex - 1)
                : (currentIndex + 1) % options.length;

            if (event.key === "Enter") {
                options[currentIndex < 0 ? 0 : currentIndex].click();
                return;
            }

            options.forEach((option, index) => {
                const isActive = index === nextIndex;
                option.classList.toggle("is-active", isActive);
                option.setAttribute("aria-selected", String(isActive));
            });
            field.input.setAttribute("aria-activedescendant", options[nextIndex].id);
        });
    };

    attachAutocomplete(fields.region, () => regionPlaces, updateRegionSuggestions);
    attachAutocomplete(fields.city, () => cityPlaces, updateCitySuggestions);
    attachAutocomplete(fields.barangay, () => barangayPlaces, updateBarangaySuggestions);

    document.addEventListener("click", (event) => {
        Object.values(fields).forEach((field) => {
            if (!field.input.parentElement.contains(event.target)) {
                closeSuggestions(field);
            }
        });
    });

    const loadRegionsAndProvinces = async () => {
        try {
            const [regions, provinces] = await Promise.all([
                fetchPlaces("/regions/"),
                fetchPlaces("/provinces/"),
            ]);
            const provinceRegionCodes = new Set(provinces.map((province) => province.regionCode));
            regionPlaces = [
                ...provinces.map((province) => ({
                    code: province.code,
                    name: province.name,
                    type: "province",
                })),
                ...regions
                    .filter((region) => !provinceRegionCodes.has(region.code))
                    .map((region) => ({
                        code: region.code,
                        name: region.name,
                        type: "region",
                    })),
            ].sort((first, second) => first.name.localeCompare(second.name));

            locationsByName = new Map(regionPlaces.map((location) => [normalizeName(location.name), location]));
            if (initialRegion) {
                fields.region.input.value = initialRegion;
                fields.city.input.value = initialCity;
                fields.barangay.input.value = initialBarangay;
            }

            const selectedRegion = findByName(locationsByName, fields.region.input.value);
            if (selectedRegion) {
                await loadCities(selectedRegion, initialBarangay);
            } else {
                setStatus("");
            }
            if (document.activeElement === fields.region.input) {
                renderSuggestions(fields.region, regionPlaces);
            }
        } catch (error) {
            console.error("Could not load PSGC regions and provinces.", error);
            setStatus("Could not load location suggestions. You can still enter the address manually.", true);
        }
    };

    loadRegionsAndProvinces();
})();
