(() => {
    const statsUrl = window.APP_PROFILE_STATS_URL;
    const csrfToken = window.APP_PROFILE_STATS_CSRF_TOKEN;
    const profileUserId = Number(window.APP_PROFILE_TRACK_USER_ID || 0);

    if (!statsUrl || !csrfToken || profileUserId < 1) {
        return;
    }

    const recordedEvents = new Set();
    const pendingEvents = new Set();

    const buildPayload = (eventType) => JSON.stringify({
        profile_user_id: profileUserId,
        event: eventType,
        csrf_token: csrfToken,
        _hp_url: "",
    });

    const sendBeacon = (eventType) => {
        if (!navigator.sendBeacon) {
            return false;
        }

        return navigator.sendBeacon(
            statsUrl,
            new Blob([buildPayload(eventType)], { type: "application/json" })
        );
    };

    const recordEvent = async (eventType, options = {}) => {
        if (recordedEvents.has(eventType) || pendingEvents.has(eventType)) {
            return;
        }

        pendingEvents.add(eventType);

        try {
            if (options.preferBeacon && sendBeacon(eventType)) {
                recordedEvents.add(eventType);
                return;
            }

            const response = await fetch(statsUrl, {
                method: "POST",
                credentials: "same-origin",
                keepalive: true,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: buildPayload(eventType),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.ok) {
                recordedEvents.add(eventType);
            }
        } catch {
            /* best effort */
        } finally {
            pendingEvents.delete(eventType);
        }
    };

    recordEvent("view");
})();
