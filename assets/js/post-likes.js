(() => {
    const likeUrl = window.APP_POST_LIKE_URL;
    const csrfToken = window.APP_POST_LIKE_CSRF_TOKEN;

    if (!likeUrl || !csrfToken) {
        return;
    }

    const pendingLikes = new Set();

    const findLikeButton = (target) => {
        if (!(target instanceof Element)) {
            return null;
        }

        return target.closest(".post-action-like");
    };

    const findPostCard = (button) => {
        return button?.closest(".post-card, .post-detail") ?? null;
    };

    const setLikeState = (button, liked, likeLabel) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        button.classList.toggle("is-liked", liked);
        button.setAttribute("aria-pressed", liked ? "true" : "false");
        button.dataset.liked = liked ? "1" : "0";

        const countEl = button.querySelector("span");
        if (countEl && likeLabel !== undefined) {
            countEl.textContent = likeLabel;
        }
    };

    document.addEventListener("click", async (event) => {
        const button = findLikeButton(event.target);
        if (!button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const card = findPostCard(button);
        const postId = Number(card?.dataset.postId || 0);
        if (postId < 1 || pendingLikes.has(postId)) {
            return;
        }

        pendingLikes.add(postId);
        button.disabled = true;

        try {
            const response = await fetch(likeUrl, {
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

            if (!response.ok || !data.ok) {
                return;
            }

            document
                .querySelectorAll(`.post-card[data-post-id="${postId}"] .post-action-like, .post-detail[data-post-id="${postId}"] .post-action-like`)
                .forEach((likeButton) => {
                    setLikeState(likeButton, Boolean(data.liked), data.like_label);
                });
        } catch {
            // Ignore network errors.
        } finally {
            pendingLikes.delete(postId);
            button.disabled = false;
        }
    });
})();
