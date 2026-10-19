const liveFlashNotice = document.querySelector("[data-live-flash-notice]");

if (liveFlashNotice) {
    const liveFlashCount = liveFlashNotice.querySelector("[data-live-flash-count]");
    const statusUrl = liveFlashNotice.dataset.statusUrl;

    const refreshLiveFlashNotice = async () => {
        try {
            const response = await fetch(statusUrl, {
                cache: "no-store",
                headers: { Accept: "application/json" },
            });
            if (!response.ok) {
                return;
            }

            const status = await response.json();
            if (!Number.isInteger(status.count) || status.count < 0) {
                return;
            }

            const hasLiveDeals = status.count > 0;
            liveFlashNotice.classList.toggle("hidden", !hasLiveDeals);
            liveFlashNotice.setAttribute("aria-hidden", String(!hasLiveDeals));
            const message = `${status.count} discounted ${status.count === 1 ? "deal" : "deals"} available now`;
            if (liveFlashCount.textContent !== message) {
                liveFlashCount.textContent = message;
            }
        } catch {
        } finally {
            window.setTimeout(refreshLiveFlashNotice, 15000);
        }
    };

    refreshLiveFlashNotice();
}