(() => {
    const removeUrl = window.APP_POST_REMOVE_URL;
    const csrfToken = window.APP_POST_REMOVE_CSRF_TOKEN;
    const canRemove = Boolean(removeUrl && csrfToken);

    const closeAllMenus = (exceptMenu = null) => {
        document.querySelectorAll(".post-menu").forEach((menu) => {
            if (exceptMenu && menu === exceptMenu) {
                return;
            }

            const dropdown = menu.querySelector(".post-menu-dropdown");
            const toggle = menu.querySelector(".post-menu-btn");
            if (dropdown) {
                dropdown.hidden = true;
            }
            if (toggle) {
                toggle.setAttribute("aria-expanded", "false");
            }
        });
    };

    const findPostCard = (element) => {
        return element.closest(".post-card.post-card--linkable, .post-detail");
    };

    const findReplyItem = (element) => element.closest(".post-reply-item");

    const removePostFromDom = (card) => {
        if (!card) {
            return;
        }

        const next = card.nextElementSibling;
        if (next?.classList.contains("hashtag-post-replies")) {
            next.remove();
        }

        const feed = card.closest("#post-feed, #profile-post-feed, #hashtag-post-feed");
        card.remove();

        if (!feed) {
            return;
        }

        if (!feed.querySelector(".post-card")) {
            const emptyClass = feed.id === "profile-post-feed" ? "profile-feed-empty" : "hashtag-page-empty";
            const emptyText = feed.id === "profile-post-feed"
                ? "You have not posted yet."
                : "No posts with this hashtag yet.";

            if (!feed.querySelector(`.${emptyClass}`) && feed.id !== "post-feed") {
                const empty = document.createElement("p");
                empty.className = emptyClass;
                empty.textContent = emptyText;
                feed.appendChild(empty);
            }
        }
    };

    const removeReplyFromDom = (replyItem) => {
        if (!replyItem) {
            return;
        }

        const thread = replyItem.closest(".post-reply-thread");
        replyItem.remove();

        if (thread && thread.querySelector(".post-reply-item") === null) {
            thread.remove();
        }

        const repliesSection = document.getElementById("post-replies");
        if (repliesSection && repliesSection.querySelector(".post-reply-item") === null) {
            repliesSection.innerHTML = "";
        }
    };

    const setMenuLoading = (menu, isLoading) => {
        const option = menu?.querySelector(".post-menu-option--remove");
        const toggle = menu?.querySelector(".post-menu-btn");
        if (option) {
            option.disabled = isLoading;
        }
        if (toggle) {
            toggle.disabled = isLoading;
        }
    };

    const requestRemove = async (payload) => {
        const response = await fetch(removeUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-Token": csrfToken,
            },
            body: JSON.stringify({
                ...payload,
                csrf_token: csrfToken,
            }),
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) {
            throw new Error(data.error || "Unable to remove right now.");
        }

        return data;
    };

    document.addEventListener("click", (event) => {
        const toggle = event.target.closest(".post-menu-btn");
        if (toggle) {
            event.preventDefault();
            event.stopPropagation();

            const menu = toggle.closest(".post-menu");
            const dropdown = menu?.querySelector(".post-menu-dropdown");
            if (!menu || !dropdown) {
                return;
            }

            const willOpen = dropdown.hidden;
            closeAllMenus(willOpen ? menu : null);
            dropdown.hidden = !willOpen;
            toggle.setAttribute("aria-expanded", willOpen ? "true" : "false");

            if (willOpen && typeof window.refreshLucideIcons === "function") {
                window.refreshLucideIcons();
            }

            return;
        }

        if (event.target.closest(".post-menu-option--placeholder")) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        const removeBtn = event.target.closest(".post-menu-option--remove");
        if (!removeBtn) {
            if (!event.target.closest(".post-menu")) {
                closeAllMenus();
            }
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (!canRemove) {
            return;
        }

        const menu = removeBtn.closest(".post-menu");
        if (!menu || removeBtn.disabled || menu.dataset.isOwn !== "1") {
            return;
        }

        const kind = menu.dataset.menuKind || "post";
        const targetId = Number(menu.dataset.targetId || 0);
        if (targetId < 1) {
            return;
        }

        setMenuLoading(menu, true);
        closeAllMenus();

        const payload = kind === "reply"
            ? { reply_id: targetId }
            : { post_id: targetId };

        requestRemove(payload)
            .then((data) => {
                if (data.removed === "reply") {
                    const replyItem = menu.closest(".post-reply-item");
                    removeReplyFromDom(replyItem);
                    return;
                }

                const card = findPostCard(menu);
                if (card?.classList.contains("post-detail")) {
                    const home = document.querySelector(".hashtag-page-back, .post-detail-back-link");
                    if (home instanceof HTMLAnchorElement && home.href) {
                        window.location.href = home.href;
                        return;
                    }
                    window.location.href = "/";
                    return;
                }

                removePostFromDom(card);
            })
            .catch(() => {
                setMenuLoading(menu, false);
                const dropdown = menu.querySelector(".post-menu-dropdown");
                const toggleBtn = menu.querySelector(".post-menu-btn");
                if (dropdown) {
                    dropdown.hidden = false;
                }
                if (toggleBtn) {
                    toggleBtn.setAttribute("aria-expanded", "true");
                }
            });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeAllMenus();
        }
    });
})();
