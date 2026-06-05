(() => {
    const menu = document.querySelector(".app-sidebar-footer-menu");
    if (!menu) {
        return;
    }

    const toggle = menu.querySelector(".app-sidebar-footer-menu-btn");
    const dropdown = menu.querySelector(".app-sidebar-footer-dropdown");
    if (!toggle || !dropdown) {
        return;
    }

    const closeMenu = () => {
        if (dropdown.hidden) {
            return;
        }

        dropdown.classList.remove("is-open");
        toggle.setAttribute("aria-expanded", "false");

        const onEnd = (event) => {
            if (event.target !== dropdown || event.propertyName !== "transform") {
                return;
            }

            dropdown.removeEventListener("transitionend", onEnd);
            if (!dropdown.classList.contains("is-open")) {
                dropdown.hidden = true;
            }
        };

        dropdown.addEventListener("transitionend", onEnd);
    };

    const openMenu = () => {
        dropdown.hidden = false;
        toggle.setAttribute("aria-expanded", "true");
        requestAnimationFrame(() => {
            dropdown.classList.add("is-open");
        });
    };

    toggle.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (dropdown.hidden || !dropdown.classList.contains("is-open")) {
            openMenu();
        } else {
            closeMenu();
        }
    });

    dropdown.addEventListener("click", (event) => {
        const settingsBtn = event.target.closest(".app-sidebar-footer-option--settings");
        if (settingsBtn) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        if (event.target.closest(".app-sidebar-footer-option")) {
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
