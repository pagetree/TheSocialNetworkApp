(() => {
    const followUrl = window.APP_PROFILE_FOLLOW_URL;
    const csrfToken = window.APP_PROFILE_FOLLOW_CSRF_TOKEN;
    const followButtons = document.querySelectorAll(".profile-follow-btn");

    if (!followUrl || !csrfToken || followButtons.length === 0) {
        return;
    }

    const t = (key, replacements = {}) => {
        if (typeof window.AppI18n?.t === "function") {
            return window.AppI18n.t(key, replacements);
        }

        const fallbacks = {
            "follow.follow": "Follow",
            "follow.follow_back": "Follow back",
            "follow.following": "Following",
            "follow.unfollow": "Unfollow",
            "follow.follow_user": "Follow :name",
            "follow.follow_back_user": "Follow back :name",
            "follow.unfollow_user": "Unfollow :name",
        };
        let text = fallbacks[key] || key;
        Object.entries(replacements).forEach(([name, value]) => {
            text = text.replaceAll(`:${name}`, String(value));
        });
        return text;
    };

    const bindFollowButton = (followBtn) => {
        if (!(followBtn instanceof HTMLButtonElement)) {
            return;
        }

        const userId = Number(followBtn.dataset.userId || 0);
        if (userId < 1) {
            return;
        }

        let pending = false;

        const userName = followBtn.dataset.userName || "user";
        const followsViewer = followBtn.dataset.followsViewer === "1";

        const updateAriaLabel = (following) => {
            if (following) {
                followBtn.setAttribute("aria-label", t("follow.unfollow_user", { name: userName }));
                return;
            }

            if (followsViewer) {
                followBtn.setAttribute("aria-label", t("follow.follow_back_user", { name: userName }));
                return;
            }

            followBtn.setAttribute("aria-label", t("follow.follow_user", { name: userName }));
        };

        const setFollowingState = (following) => {
            followBtn.classList.toggle("is-following", following);
            followBtn.classList.toggle("is-follow-back", !following && followsViewer);
            followBtn.dataset.following = following ? "1" : "0";
            followBtn.setAttribute("aria-pressed", following ? "true" : "false");
            updateAriaLabel(following);
        };

        setFollowingState(followBtn.dataset.following === "1");

        followBtn.addEventListener("click", async () => {
            if (pending) {
                return;
            }

            pending = true;
            followBtn.disabled = true;

            try {
                const response = await fetch(followUrl, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                        "X-CSRF-Token": csrfToken,
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        csrf_token: csrfToken,
                        _hp_url: "",
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok || !data.ok) {
                    return;
                }

                setFollowingState(Boolean(data.following));

                const followersEl = document.getElementById("profile-followers-count");
                if (followersEl && data.followers_label) {
                    followersEl.textContent = data.followers_label;
                }
            } catch {
                // Best effort.
            } finally {
                pending = false;
                followBtn.disabled = false;
            }
        });
    };

    followButtons.forEach(bindFollowButton);
})();
