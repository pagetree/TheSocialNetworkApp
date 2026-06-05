(() => {
    const clickUrl = window.APP_LINK_CLICK_URL;
    const csrfToken = window.APP_LINK_CLICK_CSRF_TOKEN;

    if (!clickUrl || !csrfToken) {
        return;
    }

    const isTrackableExternalLink = (link) => {
        if (!(link instanceof HTMLAnchorElement)) {
            return false;
        }

        if (link.classList.contains("post-mention") || link.classList.contains("post-hashtag")) {
            return false;
        }

        if (link.id === "profile-display-website-link") {
            return true;
        }

        return link.classList.contains("post-external-link");
    };

    const resolveClickTarget = (link) => {
        if (link.id === "profile-display-website-link") {
            const profileUserId = Number(window.APP_PROFILE_TRACK_USER_ID || 0);
            if (profileUserId < 1) {
                return null;
            }

            return { profile_user_id: profileUserId };
        }

        const replyItem = link.closest(".post-reply-item");
        if (replyItem instanceof HTMLElement) {
            const replyId = Number(replyItem.dataset.replyId || 0);
            if (replyId < 1) {
                return null;
            }

            return { reply_id: replyId };
        }

        const postContainer = link.closest(".post-card, .post-detail, .post-quoted-card");
        if (!(postContainer instanceof HTMLElement)) {
            return null;
        }

        const postId = Number(postContainer.dataset.postId || 0);
        if (postId < 1) {
            return null;
        }

        return { post_id: postId };
    };

    const buildPayload = (target) => JSON.stringify({
        post_id: target.post_id || 0,
        reply_id: target.reply_id || 0,
        profile_user_id: target.profile_user_id || 0,
        csrf_token: csrfToken,
        _hp_url: "",
    });

    const recordClick = (target) => {
        const body = buildPayload(target);

        if (navigator.sendBeacon) {
            navigator.sendBeacon(
                clickUrl,
                new Blob([body], { type: "application/json" })
            );
            return;
        }

        fetch(clickUrl, {
            method: "POST",
            credentials: "same-origin",
            keepalive: true,
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-Token": csrfToken,
            },
            body,
        }).catch(() => {
            /* best effort */
        });
    };

    document.addEventListener("click", (event) => {
        const link = event.target instanceof Element
            ? event.target.closest("a")
            : null;

        if (!isTrackableExternalLink(link)) {
            return;
        }

        const target = resolveClickTarget(link);
        if (target === null) {
            return;
        }

        recordClick(target);
    }, true);
})();
