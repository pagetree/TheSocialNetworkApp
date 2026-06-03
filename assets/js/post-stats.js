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

    const isTrackableElement = (element) =>
        element instanceof Element
        && (element.classList.contains("post-card") || element.classList.contains("post-detail"));

    const updateCardCounts = (postId, data) => {
        const card = document.querySelector(
            `.post-card[data-post-id="${postId}"], .post-detail[data-post-id="${postId}"]`
        );
        if (!card || !data) {
            return;
        }

        const viewCountEls = card.querySelectorAll(".post-detail-view-count");
        if (viewCountEls.length && data.view_label) {
            viewCountEls.forEach((el) => {
                el.textContent = data.view_label;
            });
        } else {
            const viewSpan = card.querySelector(".post-action-stat-views span");
            if (viewSpan && data.view_label) {
                viewSpan.textContent = data.view_label;
            }
        }

        const interactionEl = card.querySelector(".post-stat-interactions");
        if (interactionEl && data.interaction_label) {
            interactionEl.textContent = data.interaction_label;
        }
    };

    const buildStatPayload = (postId, eventType) => JSON.stringify({
        post_id: Number(postId),
        event: eventType,
        csrf_token: csrfToken,
        _hp_url: "",
    });

    const sendStatBeacon = (postId, eventType) => {
        if (!navigator.sendBeacon) {
            return false;
        }

        const blob = new Blob([buildStatPayload(postId, eventType)], {
            type: "application/json",
        });

        return navigator.sendBeacon(statsUrl, blob);
    };

    const recordStat = async (postId, eventType, options = {}) => {
        const dedupeSet = eventType === "view" ? viewedPosts : interactedPosts;
        const pendingKey = `${eventType}:${postId}`;

        if (dedupeSet.has(postId) || pendingRequests.has(pendingKey)) {
            return;
        }

        pendingRequests.add(pendingKey);

        const finishSuccess = (data) => {
            dedupeSet.add(postId);
            updateCardCounts(postId, data);
        };

        try {
            if (options.preferBeacon && sendStatBeacon(postId, eventType)) {
                dedupeSet.add(postId);
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
                body: buildStatPayload(postId, eventType),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.ok) {
                finishSuccess(data);
            }
        } catch {
            // Stats are best effort.
        } finally {
            pendingRequests.delete(pendingKey);
        }
    };

    const VIEW_VISIBILITY_RATIO = 0.45;

    const visibleRatioForCard = (card) => {
        const rect = card.getBoundingClientRect();
        const visibleHeight = Math.min(rect.bottom, window.innerHeight) - Math.max(rect.top, 0);
        if (visibleHeight <= 0 || rect.height <= 0) {
            return 0;
        }

        return visibleHeight / rect.height;
    };

    const isPostViewable = (card, entry) => {
        if (entry && entry.isIntersecting) {
            return entry.intersectionRatio >= VIEW_VISIBILITY_RATIO;
        }

        return visibleRatioForCard(card) >= VIEW_VISIBILITY_RATIO;
    };

    const checkVisiblePosts = () => {
        document.querySelectorAll(".post-card[data-post-id], .post-detail[data-post-id]").forEach((card) => {
            if (!isTrackableCard(card)) {
                return;
            }

            const postId = card.dataset.postId;
            if (!postId || viewedPosts.has(postId)) {
                return;
            }

            if (isPostViewable(card)) {
                recordStat(postId, "view");
            }
        });
    };

    let scrollFrame = null;

    const scheduleCenterCheck = () => {
        if (scrollFrame !== null) {
            return;
        }

        scrollFrame = window.requestAnimationFrame(() => {
            scrollFrame = null;
            checkVisiblePosts();
        });
    };

    const observeTrackablePosts = () => {
        if (!("IntersectionObserver" in window)) {
            scheduleCenterCheck();
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    const card = entry.target;
                    if (!entry.isIntersecting || !isTrackableCard(card)) {
                        return;
                    }

                    const postId = card.dataset.postId;
                    if (!postId || viewedPosts.has(postId)) {
                        return;
                    }

                    if (isPostViewable(card, entry)) {
                        recordStat(postId, "view");
                    }
                });
            },
            {
                root: null,
                threshold: [0, 0.45, 0.6, 0.75, 1],
            }
        );

        document.querySelectorAll(".post-card[data-post-id], .post-detail[data-post-id]").forEach((card) => {
            if (isTrackableCard(card)) {
                observer.observe(card);
            }
        });
    };

    const isInteractionTarget = (target) => {
        if (!(target instanceof Element)) {
            return false;
        }

        if (
            target.closest(".post-actions")
            || target.closest(".post-menu")
            || target.closest(".post-action-stat-views")
            || target.closest(".post-detail-meta-views-btn")
        ) {
            return false;
        }

        if (target.closest(".post-hashtag")) {
            return false;
        }

        return Boolean(
            target.closest(".post-header")
            || target.closest(".post-text")
            || target.closest(".post-detail-header")
            || target.closest(".post-detail-text")
            || target.closest(".post-media-gallery")
        );
    };

    document.addEventListener("click", (event) => {
        const target = event.target;

        const coverLink = target instanceof Element ? target.closest(".post-card-cover-link") : null;
        if (coverLink) {
            const card = coverLink.closest(".post-card");
            if (card && isTrackableElement(card) && isTrackableCard(card)) {
                const postId = card.dataset.postId;
                if (postId && !interactedPosts.has(postId)) {
                    recordStat(postId, "interaction", { preferBeacon: true });
                }
            }
            return;
        }

        const card = target instanceof Element
            ? target.closest(".post-card, .post-detail")
            : null;

        if (!card || !isTrackableElement(card) || !isTrackableCard(card) || !isInteractionTarget(target)) {
            return;
        }

        const postId = card.dataset.postId;
        if (!postId || interactedPosts.has(postId)) {
            return;
        }

        recordStat(postId, "interaction", { preferBeacon: false });
    });

    window.addEventListener("scroll", scheduleCenterCheck, { passive: true });
    document.addEventListener("scroll", scheduleCenterCheck, { passive: true, capture: true });
    window.addEventListener("resize", scheduleCenterCheck);
    observeTrackablePosts();
    scheduleCenterCheck();
})();
