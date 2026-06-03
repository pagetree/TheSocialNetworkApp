(() => {
    const detailUrl = window.APP_POST_STATS_DETAIL_URL;
    const csrfToken = window.APP_POST_STATS_CSRF_TOKEN;
    const overlay = document.getElementById("post-stats-modal-overlay");
    const closeBtn = document.getElementById("post-stats-modal-close");
    const titleEl = document.getElementById("post-stats-modal-title");
    const subtitleEl = document.getElementById("post-stats-modal-subtitle");
    const panelEl = document.getElementById("post-stats-modal-panel");
    const engageEl = document.getElementById("post-stats-modal-engage");
    const listEl = document.getElementById("post-stats-modal-list");
    const statusEl = document.getElementById("post-stats-modal-status");

    if (
        !detailUrl
        || !csrfToken
        || !overlay
        || !closeBtn
        || !titleEl
        || !subtitleEl
        || !panelEl
        || !engageEl
        || !listEl
        || !statusEl
    ) {
        return;
    }

    const ENGAGE_ORDER = ["likes", "reposts", "replies"];
    const LIST_ORDER = ["views", "interactions", "score"];

    let activeTrigger = null;
    let fetchController = null;

    const setStatus = (message, isError = false) => {
        panelEl.hidden = true;
        statusEl.textContent = message;
        statusEl.hidden = false;
        statusEl.classList.toggle("is-error", isError);
    };

    const clearPanel = () => {
        engageEl.replaceChildren();
        listEl.replaceChildren();
        engageEl.hidden = true;
        panelEl.hidden = true;
    };

    const createEngageItem = (metric) => {
        const item = document.createElement("div");
        item.className = "post-stats-engage-item";

        const icon = document.createElement("span");
        icon.className = "post-stats-engage-icon";
        icon.innerHTML = `<i data-lucide="${metric.icon || "bar-chart-2"}" aria-hidden="true"></i>`;

        const value = document.createElement("span");
        value.className = "post-stats-engage-value";
        value.textContent = String(metric.value || "0");

        const label = document.createElement("span");
        label.className = "post-stats-engage-label";
        label.textContent = String(metric.label || "");

        item.append(icon, value, label);
        return item;
    };

    const createListRow = (metric) => {
        const row = document.createElement("div");
        row.className = "post-stats-list-row";

        const term = document.createElement("dt");
        term.textContent = String(metric.label || "");

        const detail = document.createElement("dd");
        detail.textContent = String(metric.value || "0");

        row.append(term, detail);
        return row;
    };

    const renderMetrics = (metrics) => {
        clearPanel();

        const byKey = new Map(metrics.map((metric) => [String(metric.key || ""), metric]));
        const engageMetrics = ENGAGE_ORDER.map((key) => byKey.get(key)).filter(Boolean);
        const listMetrics = LIST_ORDER.map((key) => byKey.get(key)).filter(Boolean);

        if (engageMetrics.length === 0 && listMetrics.length === 0) {
            setStatus("No stats available yet.");
            return;
        }

        statusEl.hidden = true;
        statusEl.classList.remove("is-error");
        panelEl.hidden = false;

        if (engageMetrics.length > 0) {
            engageEl.hidden = false;
            engageEl.dataset.count = String(engageMetrics.length);
            engageMetrics.forEach((metric) => {
                engageEl.append(createEngageItem(metric));
            });
        }

        listMetrics.forEach((metric) => {
            listEl.append(createListRow(metric));
        });

        window.lucide?.createIcons?.();
    };

    const closeModal = () => {
        fetchController?.abort();
        fetchController = null;
        overlay.hidden = true;
        document.body.classList.remove("post-stats-modal-open");
        activeTrigger?.classList.remove("is-active");
        activeTrigger = null;
        clearPanel();
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
        clearPanel();
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
            subtitleEl.textContent = data.is_owner ? "Your content" : "";
            subtitleEl.hidden = !data.is_owner;

            renderMetrics(Array.isArray(data.metrics) ? data.metrics : []);
        } catch (error) {
            if (error?.name === "AbortError") {
                return;
            }

            setStatus("Unable to load stats right now.", true);
        }
    };

    document.addEventListener("click", (event) => {
        const trigger = event.target instanceof Element
            ? event.target.closest(".post-action-stat-views, .post-detail-meta-views-btn")
            : null;

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
