(() => {
    const detailUrl = window.APP_POST_STATS_DETAIL_URL;
    const csrfToken = window.APP_POST_STATS_CSRF_TOKEN;
    const overlay = document.getElementById("post-stats-modal-overlay");
    const closeBtn = document.getElementById("post-stats-modal-close");
    const titleEl = document.getElementById("post-stats-modal-title");
    const subtitleEl = document.getElementById("post-stats-modal-subtitle");
    const bodyEl = document.getElementById("post-stats-modal-body");
    const statusEl = document.getElementById("post-stats-modal-status");

    if (!detailUrl || !csrfToken || !overlay || !closeBtn || !titleEl || !subtitleEl || !bodyEl || !statusEl) {
        return;
    }

    let activeTrigger = null;
    let fetchController = null;

    const setStatus = (message, isError = false) => {
        statusEl.textContent = message;
        statusEl.hidden = false;
        statusEl.classList.toggle("is-error", isError);
    };

    const clearMetrics = () => {
        bodyEl.querySelectorAll(".post-stats-metric").forEach((node) => node.remove());
    };

    const renderMetrics = (metrics) => {
        clearMetrics();
        statusEl.hidden = true;
        statusEl.classList.remove("is-error");

        metrics.forEach((metric) => {
            const row = document.createElement("div");
            row.className = "post-stats-metric";
            row.dataset.metricKey = String(metric.key || "");

            const iconWrap = document.createElement("span");
            iconWrap.className = "post-stats-metric-icon";
            iconWrap.innerHTML = `<i data-lucide="${metric.icon || "bar-chart-2"}" aria-hidden="true"></i>`;

            const copy = document.createElement("div");
            copy.className = "post-stats-metric-copy";

            const label = document.createElement("p");
            label.className = "post-stats-metric-label";
            label.textContent = String(metric.label || "");

            const value = document.createElement("p");
            value.className = "post-stats-metric-value";
            value.textContent = String(metric.value || "0");

            copy.append(label, value);
            row.append(iconWrap, copy);
            bodyEl.append(row);
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
            if (metrics.length === 0) {
                setStatus("No stats available yet.");
                return;
            }

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
