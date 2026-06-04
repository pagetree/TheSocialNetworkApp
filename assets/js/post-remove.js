(() => {
    const t = (key, replacements = {}) =>
        window.AppI18n?.t?.(key, replacements) ?? key;

    const removeUrl = window.APP_POST_REMOVE_URL;
    const csrfToken = window.APP_POST_REMOVE_CSRF_TOKEN;
    const canRemove = Boolean(removeUrl && csrfToken);
    const FLOATING_CLASS = "post-menu-dropdown--floating";
    const DROPDOWN_REF = "_postMenuDropdown";

    const getDropdown = (menu) => menu[DROPDOWN_REF] || menu.querySelector(".post-menu-dropdown");

    const dockDropdown = (menu) => {
        if (!menu) {
            return;
        }

        const dropdown = getDropdown(menu);
        const toggle = menu.querySelector(".post-menu-btn");
        if (!dropdown) {
            if (toggle) {
                toggle.setAttribute("aria-expanded", "false");
            }
            return;
        }

        dropdown.classList.remove(FLOATING_CLASS);
        dropdown.style.top = "";
        dropdown.style.left = "";
        dropdown.style.visibility = "";
        dropdown.hidden = true;

        if (!menu.contains(dropdown)) {
            menu.appendChild(dropdown);
        }

        delete dropdown._postMenuHost;
        menu[DROPDOWN_REF] = null;

        if (toggle) {
            toggle.setAttribute("aria-expanded", "false");
        }
    };

    const closeAllMenus = (exceptMenu = null) => {
        document.querySelectorAll(".post-menu").forEach((menu) => {
            if (exceptMenu && menu === exceptMenu) {
                return;
            }

            dockDropdown(menu);
        });
    };

    const positionFloatingDropdown = (dropdown, toggle) => {
        const gap = 6;
        const padding = 8;
        const toggleRect = toggle.getBoundingClientRect();
        const dropdownRect = dropdown.getBoundingClientRect();

        let top = toggleRect.bottom + gap;
        let left = toggleRect.right - dropdownRect.width;

        if (left < padding) {
            left = padding;
        }
        if (left + dropdownRect.width > window.innerWidth - padding) {
            left = window.innerWidth - dropdownRect.width - padding;
        }
        if (top + dropdownRect.height > window.innerHeight - padding) {
            top = toggleRect.top - gap - dropdownRect.height;
        }
        if (top < padding) {
            top = padding;
        }

        dropdown.style.top = `${Math.round(top)}px`;
        dropdown.style.left = `${Math.round(left)}px`;
    };

    const openFloatingDropdown = (menu, toggle) => {
        const dropdown = getDropdown(menu);
        if (!dropdown) {
            return;
        }

        dropdown.hidden = false;
        dropdown.classList.add(FLOATING_CLASS);
        menu[DROPDOWN_REF] = dropdown;
        document.body.appendChild(dropdown);

        dropdown.style.visibility = "hidden";
        dropdown.style.top = "0";
        dropdown.style.left = "0";
        positionFloatingDropdown(dropdown, toggle);
        dropdown.style.visibility = "";

        toggle.setAttribute("aria-expanded", "true");

        if (typeof window.refreshLucideIcons === "function") {
            window.refreshLucideIcons();
        }
    };

    const findMenuFromDropdownTarget = (target) => {
        const dropdown = target.closest(".post-menu-dropdown");
        if (!dropdown) {
            return null;
        }

        return dropdown._postMenuHost || target.closest(".post-menu");
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
                ? t("hashtag.empty_own")
                : t("hashtag.empty");

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
        const dropdown = getDropdown(menu);
        const option = dropdown?.querySelector(".post-menu-option--remove");
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
            throw new Error(data.error || t("post.errors.remove_failed"));
        }

        return data;
    };

    document.addEventListener("click", (event) => {
        const toggle = event.target.closest(".post-menu-btn");
        if (toggle) {
            event.preventDefault();
            event.stopPropagation();

            const menu = toggle.closest(".post-menu");
            const dropdown = menu ? getDropdown(menu) : null;
            if (!menu || !dropdown) {
                return;
            }

            const willOpen = dropdown.hidden;
            closeAllMenus(willOpen ? menu : null);

            if (willOpen) {
                dropdown._postMenuHost = menu;
                openFloatingDropdown(menu, toggle);
            } else {
                dockDropdown(menu);
            }

            return;
        }

        if (event.target.closest(".post-menu-option--report")) {
            event.preventDefault();
            event.stopPropagation();
            closeAllMenus();

            const reportBtn = event.target.closest(".post-menu-option--report");
            const menu = reportBtn ? findMenuFromDropdownTarget(reportBtn) : null;
            if (menu && typeof window.openContentReportModal === "function") {
                const kind = menu.dataset.menuKind || "post";
                const targetId = Number(menu.dataset.targetId || 0);
                if (targetId > 0) {
                    window.openContentReportModal({
                        targetType: kind === "reply" ? "reply" : "post",
                        targetId,
                    });
                }
            }

            return;
        }

        const removeBtn = event.target.closest(".post-menu-option--remove");
        if (!removeBtn) {
            if (!event.target.closest(".post-menu-dropdown")) {
                closeAllMenus();
            }
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (!canRemove) {
            return;
        }

        const menu = findMenuFromDropdownTarget(removeBtn);
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
                const menuToggle = menu.querySelector(".post-menu-btn");
                const dropdown = getDropdown(menu);
                if (!menuToggle || !dropdown) {
                    return;
                }

                dropdown._postMenuHost = menu;
                openFloatingDropdown(menu, menuToggle);
            });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeAllMenus();
        }
    });

    window.addEventListener(
        "resize",
        () => {
            closeAllMenus();
        },
        { passive: true }
    );

    document.addEventListener(
        "scroll",
        () => {
            closeAllMenus();
        },
        true
    );
})();
