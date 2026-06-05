(() => {
    const repostUrl = window.APP_POST_REPOST_URL;
    const csrfToken = window.APP_POST_REPOST_CSRF_TOKEN;

    if (!repostUrl || !csrfToken) {
        return;
    }

    const pendingReposts = new Set();

    const findRepostButton = (target) => {
        if (!(target instanceof Element)) {
            return null;
        }

        return target.closest(".post-action-repost");
    };

    const findPostCard = (button) => {
        return button?.closest(".post-card, .post-detail") ?? null;
    };

    const updateRepostCount = (postId, repostLabel) => {
        document
            .querySelectorAll(`.post-card[data-post-id="${postId}"] .post-action-repost span, .post-detail[data-post-id="${postId}"] .post-action-repost span`)
            .forEach((countEl) => {
                countEl.textContent = repostLabel;
            });
    };

    document.addEventListener("click", async (event) => {
        const button = findRepostButton(event.target);
        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const card = findPostCard(button);
        const postId = Number(card?.dataset.postId || 0);
        if (postId < 1 || pendingReposts.has(postId)) {
            return;
        }

        pendingReposts.add(postId);
        button.disabled = true;

        try {
            const response = await fetch(repostUrl, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify({
                    post_id: postId,
                    csrf_token: csrfToken,
                    _hp_url: "",
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.ok && data.repost_label !== undefined) {
                updateRepostCount(postId, data.repost_label);
            }
        } catch {
            // Ignore network errors.
        } finally {
            pendingReposts.delete(postId);
            button.disabled = false;
        }
    });
})();
