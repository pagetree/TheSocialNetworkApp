(() => {
    const detailUrl = window.APP_POST_STATS_DETAIL_URL;
    const csrfToken = window.APP_POST_STATS_CSRF_TOKEN;
    const overlay = document.getElementById("post-stats-modal-overlay");
    const closeBtn = document.getElementById("post-stats-modal-close");
    const titleEl = document.getElementById("post-stats-modal-title");
    const subtitleEl = document.getElementById("post-stats-modal-subtitle");
    const bodyEl = document.getElementById("post-stats-modal-body");
    const contentEl = document.getElementById("post-stats-modal-content");
    const heroEl = document.getElementById("post-stats-modal-hero");
    const insightsEl = document.getElementById("post-stats-modal-insights");
    const insightsGridEl = document.getElementById("post-stats-modal-insights-grid");
    const statusEl = document.getElementById("post-stats-modal-status");

    if (
        !detailUrl
        || !csrfToken
        || !overlay
        || !closeBtn
        || !titleEl
        || !subtitleEl
        || !bodyEl
        || !contentEl
        || !heroEl
        || !insightsEl
        || !insightsGridEl
        || !statusEl
    ) {
        return;
    }

    const HERO_ORDER = ["likes", "reposts", "replies"];
    const INSIGHT_ORDER = ["views", "interactions", "score"];

    const INSIGHT_COPY = {
        views: "Total impressions across the feed and post page.",
        interactions: "Profile visits driven by this post.",
        score: "Weighted engagement score from the last 14 days.",
    };

    let activeTrigger = null;
    let fetchController = null;

    const setStatus = (message, isError = false) => {
        contentEl.hidden = true;
        statusEl.textContent = message;
        statusEl.hidden = false;
        statusEl.classList.toggle("is-error", isError);
    };

    const clearMetrics = () => {
        heroEl.replaceChildren();
        insightsGridEl.replaceChildren();
        heroEl.hidden = true;
        insightsEl.hidden = true;
        contentEl.hidden = true;
    };

    const createHeroItem = (metric) => {
        const item = document.createElement("div");
        item.className = "post-stats-hero-item";
        item.dataset.metricKey = String(metric.key || "");

        const iconWrap = document.createElement("span");
        iconWrap.className = "post-stats-hero-icon";
        iconWrap.innerHTML = `<i data-lucide="${metric.icon || "bar-chart-2"}" aria-hidden="true"></i>`;

        const value = document.createElement("p");
        value.className = "post-stats-hero-value";
        value.textContent = String(metric.value || "0");

        const label = document.createElement("p");
        label.className = "post-stats-hero-label";
        label.textContent = String(metric.label || "");

        item.append(iconWrap, value, label);
        return item;
    };

    const createInsightTile = (metric) => {
        const tile = document.createElement("article");
        const key = String(metric.key || "");
        tile.className = `post-stats-insight-tile post-stats-insight-tile--${key}`;
        tile.dataset.metricKey = key;

        const top = document.createElement("div");
        top.className = "post-stats-insight-top";

        const iconWrap = document.createElement("span");
        iconWrap.className = "post-stats-insight-icon";
        iconWrap.innerHTML = `<i data-lucide="${metric.icon || "bar-chart-2"}" aria-hidden="true"></i>`;

        const label = document.createElement("p");
        label.className = "post-stats-insight-label";
        label.textContent = String(metric.label || "");

        top.append(iconWrap, label);

        const value = document.createElement("p");
        value.className = "post-stats-insight-value";
        value.textContent = String(metric.value || "0");

        const copy = document.createElement("p");
        copy.className = "post-stats-insight-copy";
        copy.textContent = INSIGHT_COPY[key] || "";

        const glow = document.createElement("span");
        glow.className = "post-stats-insight-glow";
        glow.setAttribute("aria-hidden", "true");

        tile.append(glow, top, value, copy);
        return tile;
    };

    const renderMetrics = (metrics) => {
        clearMetrics();

        const metricByKey = new Map(metrics.map((metric) => [String(metric.key || ""), metric]));
        const heroMetrics = HERO_ORDER.map((key) => metricByKey.get(key)).filter(Boolean);
        const insightMetrics = INSIGHT_ORDER.map((key) => metricByKey.get(key)).filter(Boolean);

        if (heroMetrics.length === 0 && insightMetrics.length === 0) {
            setStatus("No stats available yet.");
            return;
        }

        statusEl.hidden = true;
        statusEl.classList.remove("is-error");
        contentEl.hidden = false;

        if (heroMetrics.length > 0) {
            heroEl.hidden = false;
            heroEl.dataset.count = String(heroMetrics.length);
            heroMetrics.forEach((metric, index) => {
                const item = createHeroItem(metric);
                item.style.setProperty("--hero-delay", `${index * 70}ms`);
                heroEl.append(item);
            });
        }

        if (insightMetrics.length > 0) {
            insightsEl.hidden = false;
            insightMetrics.forEach((metric, index) => {
                const tile = createInsightTile(metric);
                tile.style.setProperty("--insight-delay", `${120 + index * 80}ms`);
                insightsGridEl.append(tile);
            });
        }

        window.lucide?.createIcons?.();
    };

    const closeModal = () => {
        fetchController?.abort();
        fetchController = null;
        overlay.hidden = true;
        document.body.classList.remove("post-stats-modal-open");
        activeTrigger?.classList.remove("is-active");
        activeTrigger = null;
        clearMetrics();
        setStatus("Loading…");
    };

    const openModal = async ({ postId = 0, replyId = 0, trigger = null }) => {
        fetchController?.abort();
        fetchController = new AbortController();

        activeTrigger?.classList.remove("is-active");
        activeTrigger = trigger;
        activeTrigger?.classList.add("is-active");

        overlay.hidden = false;
        document.body.classList.add("post-stats-modal-open");
        titleEl.textContent = "Stats";
        subtitleEl.textContent = "";
        subtitleEl.hidden = true;
        clearMetrics();
        setStatus("Loading…");

        try {
            const response = await fetch(detailUrl, {
                method: "POST",
                credentials: "same-origin",
                signal: fetchController.signal,
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify({
                    post_id: Number(postId) || 0,
                    reply_id: Number(replyId) || 0,
                    csrf_token: csrfToken,
                    _hp_url: "",
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.ok) {
                setStatus(data.error || "Unable to load stats.", true);
                return;
            }

            titleEl.textContent = String(data.title || "Stats");
            if (data.is_owner) {
                subtitleEl.textContent = "Your content";
                subtitleEl.hidden = false;
            } else {
                subtitleEl.hidden = true;
            }

            const metrics = Array.isArray(data.metrics) ? data.metrics : [];
            renderMetrics(metrics);
        } catch (error) {
            if (error?.name === "AbortError") {
                return;
            }

            setStatus("Unable to load stats right now.", true);
        }
    };

    const findStatsTrigger = (target) => {
        if (!(target instanceof Element)) {
            return null;
        }

        return target.closest(".post-action-stat-views, .post-detail-meta-views-btn");
    };

    document.addEventListener("click", (event) => {
        const trigger = findStatsTrigger(event.target);
        if (!trigger) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const postId = Number(trigger.dataset.postId || trigger.closest("[data-post-id]")?.dataset.postId || 0);
        const replyId = Number(trigger.dataset.replyId || trigger.closest("[data-reply-id]")?.dataset.replyId || 0);

        if (postId < 1 && replyId < 1) {
            return;
        }

        openModal({ postId, replyId, trigger });
    });

    closeBtn.addEventListener("click", closeModal);

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            closeModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !overlay.hidden) {
            closeModal();
        }
    });
})();
