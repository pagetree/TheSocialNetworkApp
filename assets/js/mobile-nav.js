(() => {
    const navRoot = document.getElementById("app-mobile-nav");
    const openButton = document.getElementById("app-mobile-nav-open");
    const closeButton = document.getElementById("app-mobile-nav-close");
    const backdrop = document.getElementById("app-mobile-nav-backdrop");
    const panel = document.getElementById("app-mobile-nav-panel");

    if (!navRoot || !openButton || !panel) {
        return;
    }

    let lastFocused = null;

    const isOpen = () => navRoot.classList.contains("is-open");

    const setOpen = (open) => {
        if (open) {
            navRoot.hidden = false;
            requestAnimationFrame(() => {
                navRoot.classList.add("is-open");
            });
        } else {
            navRoot.classList.remove("is-open");
            window.setTimeout(() => {
                if (!navRoot.classList.contains("is-open")) {
                    navRoot.hidden = true;
                }
            }, 280);
        }

        document.body.classList.toggle("app-mobile-nav-open", open);
        openButton.setAttribute("aria-expanded", open ? "true" : "false");

        if (open) {
            lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            const closeBtn = document.getElementById("app-mobile-nav-close");
            if (closeBtn instanceof HTMLElement) {
                closeBtn.focus();
            }
            if (typeof window.refreshLucideIcons === "function") {
                window.refreshLucideIcons();
            }
            return;
        }

        if (lastFocused instanceof HTMLElement) {
            lastFocused.focus();
        }
        lastFocused = null;
    };

    openButton.addEventListener("click", () => {
        setOpen(!isOpen());
    });

    closeButton?.addEventListener("click", () => {
        setOpen(false);
    });

    backdrop?.addEventListener("click", () => {
        setOpen(false);
    });

    panel.querySelectorAll("a[href]").forEach((link) => {
        link.addEventListener("click", () => {
            setOpen(false);
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && isOpen()) {
            event.preventDefault();
            setOpen(false);
        }
    });
})();
