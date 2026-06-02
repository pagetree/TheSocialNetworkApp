(() => {
    const statsUrl = window.APP_POST_STATS_URL;
    const csrfToken = window.APP_POST_STATS_CSRF_TOKEN;
    const currentUserId = Number(window.APP_CURRENT_USER_ID || 0);

    if (!statsUrl || !csrfToken || currentUserId < 1) {
        return;
    }

    const viewedPosts = new Set();
    const interactedPosts = new Set();
    const pendingRequests = new Set();

    const isTrackableCard = (card) => {
        if (!(card instanceof HTMLElement)) {
            return false;
        }

        if (card.classList.contains("post-composer")) {
            return false;
        }

        if (card.dataset.statTrackable !== "1") {
            return false;
        }

        const postId = card.dataset.postId;
        return postId !== undefined && postId !== "";
    };

    const updateCardCounts = (postId, data) => {
        const card = document.querySelector(`.post-card[data-post-id="${postId}"]`);
        if (!card || !data) {
            return;
        }

        const viewSpan = card.querySelector(".post-action-stat-views span");
        if (viewSpan && data.view_label) {
            viewSpan.textContent = data.view_label;
        }

        const interactionEl = card.querySelector(".post-stat-interactions");
        if (interactionEl && data.interaction_label) {
            interactionEl.textContent = data.interaction_label;
        }
    };

    const recordStat = async (postId, eventType) => {
        const dedupeSet = eventType === "view" ? viewedPosts : interactedPosts;
        if (dedupeSet.has(postId) || pendingRequests.has(`${eventType}:${postId}`)) {
            return;
        }

        pendingRequests.add(`${eventType}:${postId}`);

        try {
            const response = await fetch(statsUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify({
                    post_id: Number(postId),
                    event: eventType,
                    csrf_token: csrfToken,
                    _hp_url: "",
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.ok) {
                dedupeSet.add(postId);
                updateCardCounts(postId, data);
            }
        } catch {
            // Ignore network errors; stats are best effort.
        } finally {
            pendingRequests.delete(`${eventType}:${postId}`);
        }
    };

    const isPostCentered = (card) => {
        const rect = card.getBoundingClientRect();
        if (rect.bottom <= 0 || rect.top >= window.innerHeight) {
            return false;
        }

        const postCenter = rect.top + rect.height / 2;
        const viewportCenter = window.innerHeight / 2;
        const tolerance = Math.max(72, Math.min(rect.height * 0.35, window.innerHeight * 0.18));

        return Math.abs(postCenter - viewportCenter) <= tolerance;
    };

    let scrollFrame = null;

    const checkCenteredPosts = () => {
        scrollFrame = null;

        document.querySelectorAll(".post-card[data-post-id]").forEach((card) => {
            if (!isTrackableCard(card)) {
                return;
            }

            const postId = card.dataset.postId;
            if (!postId || viewedPosts.has(postId)) {
                return;
            }

            if (isPostCentered(card)) {
                recordStat(postId, "view");
            }
        });
    };

    const scheduleCenterCheck = () => {
        if (scrollFrame !== null) {
            return;
        }

        scrollFrame = window.requestAnimationFrame(checkCenteredPosts);
    };

    const isInteractionTarget = (target) => {
        if (!(target instanceof Element)) {
            return false;
        }

        if (target.closest(".post-actions") || target.closest(".post-menu-btn")) {
            return false;
        }

        return Boolean(
            target.closest(".post-header")
            || target.closest(".post-text")
            || target.closest(".post-media-gallery")
        );
    };

    document.addEventListener("click", (event) => {
        const target = event.target;
        const card = target instanceof Element ? target.closest(".post-card") : null;

        if (!card || !isTrackableCard(card) || !isInteractionTarget(target)) {
            return;
        }

        const postId = card.dataset.postId;
        if (!postId || interactedPosts.has(postId)) {
            return;
        }

        recordStat(postId, "interaction");
    });

    window.addEventListener("scroll", scheduleCenterCheck, { passive: true });
    window.addEventListener("resize", scheduleCenterCheck);
    scheduleCenterCheck();
})();
