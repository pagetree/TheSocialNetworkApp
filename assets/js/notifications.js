(function () {
    "use strict";

    const unreadUrl = window.APP_NOTIFICATIONS_UNREAD_URL;
    const readUrl = window.APP_NOTIFICATIONS_READ_URL;
    const readCsrfToken = window.APP_NOTIFICATIONS_READ_CSRF_TOKEN;
    const pollMs = 15000;
    let pollTimer = null;
    let authExpired = false;

    if (!unreadUrl) {
        return;
    }

    const badges = () => document.querySelectorAll("[data-notifications-badge]");
    const list = document.getElementById("notifications-list");

    function setBadgeVisible(visible) {
        badges().forEach((badge) => {
            if (visible) {
                badge.removeAttribute("hidden");
            } else {
                badge.setAttribute("hidden", "");
            }
        });
    }

    async function fetchUnreadCount() {
        if (authExpired) {
            return null;
        }

        try {
            const response = await fetch(unreadUrl, {
                method: "GET",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                },
            });

            if (response.status === 401) {
                authExpired = true;
                if (pollTimer !== null) {
                    window.clearInterval(pollTimer);
                    pollTimer = null;
                }
                return null;
            }

            if (!response.ok) {
                return null;
            }

            const payload = await response.json();
            if (!payload || payload.ok !== true) {
                return null;
            }

            return Number(payload.unread_count) || 0;
        } catch {
            return null;
        }
    }

    async function pollUnread() {
        if (document.hidden) {
            return;
        }

        const count = await fetchUnreadCount();
        if (count === null) {
            return;
        }

        setBadgeVisible(count > 0);
    }

    async function markAllRead() {
        if (!readUrl || !readCsrfToken) {
            return;
        }

        try {
            const response = await fetch(readUrl, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-Token": readCsrfToken,
                },
                body: JSON.stringify({
                    mark_all: true,
                    csrf_token: readCsrfToken,
                    _hp_url: "",
                }),
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            if (!payload || payload.ok !== true) {
                return;
            }

            setBadgeVisible(false);

            if (list) {
                list.querySelectorAll(".notifications-item.is-unread").forEach((item) => {
                    item.classList.remove("is-unread");
                    item.classList.add("is-read");
                    const dot = item.querySelector(".notifications-item-dot");
                    if (dot) {
                        dot.remove();
                    }
                });
            }

        } catch {
            /* ignore */
        }
    }

    if (window.APP_NOTIFICATIONS_PAGE) {
        setBadgeVisible(false);
        markAllRead();
    } else {
        pollUnread();
        pollTimer = window.setInterval(pollUnread, pollMs);
        document.addEventListener("visibilitychange", pollUnread);
    }
})();
