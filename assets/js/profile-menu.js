(() => {
    const menu = document.querySelector(".profile-menu");
    if (!menu) {
        return;
    }

    const toggle = menu.querySelector(".profile-menu-btn");
    const dropdown = menu.querySelector(".profile-menu-dropdown");
    if (!toggle || !dropdown) {
        return;
    }

    const closeMenu = () => {
        dropdown.hidden = true;
        toggle.setAttribute("aria-expanded", "false");
    };

    const openMenu = () => {
        dropdown.hidden = false;
        toggle.setAttribute("aria-expanded", "true");
    };

    toggle.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (dropdown.hidden) {
            openMenu();
        } else {
            closeMenu();
        }
    });

    dropdown.addEventListener("click", (event) => {
        const reportBtn = event.target.closest(".profile-menu-option--report");
        if (reportBtn) {
            event.preventDefault();
            event.stopPropagation();
            closeMenu();

            if (typeof window.openContentReportModal === "function") {
                const userId = Number(menu.dataset.userId || 0);
                const subjectLabel = String(menu.dataset.userName || "").trim();
                if (userId > 0) {
                    window.openContentReportModal({
                        targetType: "user",
                        targetId: userId,
                        subjectLabel,
                    });
                }
            }

            return;
        }

        if (event.target.closest(".profile-menu-option")) {
            event.preventDefault();
            event.stopPropagation();
            closeMenu();
        }
    });

    document.addEventListener("click", (event) => {
        if (!menu.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeMenu();
        }
    });
})();
