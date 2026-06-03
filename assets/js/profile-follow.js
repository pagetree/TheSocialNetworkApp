(() => {
    const followUrl = window.APP_PROFILE_FOLLOW_URL;
    const csrfToken = window.APP_PROFILE_FOLLOW_CSRF_TOKEN;
    const followButtons = document.querySelectorAll(".profile-follow-btn");

    if (!followUrl || !csrfToken || followButtons.length === 0) {
        return;
    }

    const bindFollowButton = (followBtn) => {
        if (!(followBtn instanceof HTMLButtonElement)) {
            return;
        }

        const userId = Number(followBtn.dataset.userId || 0);
        if (userId < 1) {
            return;
        }

        let pending = false;

        const setFollowingState = (following) => {
            followBtn.classList.toggle("is-following", following);
            followBtn.dataset.following = following ? "1" : "0";
            followBtn.setAttribute("aria-pressed", following ? "true" : "false");

            const label = followBtn.querySelector(".profile-follow-btn-label");
            if (label) {
                label.textContent = following ? "Following" : "Follow";
            }
        };

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
