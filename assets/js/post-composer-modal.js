(() => {
    const modalRoot = document.getElementById("post-composer-modal");
    const overlay = document.getElementById("post-composer-modal-overlay");
    const fab = document.getElementById("post-composer-fab");
    const closeBtn = document.getElementById("post-composer-modal-close");
    const backdrop = document.getElementById("post-composer-modal-backdrop");
    const textarea = document.getElementById("post-composer-input");

    if (!modalRoot || !overlay || !fab) {
        return;
    }

    const mobileQuery = window.matchMedia("(max-width: 900px)");
    let lastFocused = null;

    const useFabComposer = () => mobileQuery.matches;

    const refreshIcons = () => {
        if (typeof window.refreshLucideIcons === "function") {
            window.refreshLucideIcons();
        }
    };

    const setOpen = (open) => {
        if (!useFabComposer()) {
            overlay.hidden = false;
            modalRoot.classList.remove("is-open");
            document.body.classList.remove("post-composer-modal-open");
            fab.setAttribute("aria-expanded", "false");
            return;
        }

        if (open) {
            overlay.hidden = false;
            requestAnimationFrame(() => {
                modalRoot.classList.add("is-open");
            });
        } else {
            modalRoot.classList.remove("is-open");
            window.setTimeout(() => {
                if (!modalRoot.classList.contains("is-open")) {
                    overlay.hidden = true;
                }
            }, 300);
        }

        document.body.classList.toggle("post-composer-modal-open", open);
        fab.setAttribute("aria-expanded", open ? "true" : "false");

        if (open) {
            lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            refreshIcons();
            if (textarea instanceof HTMLTextAreaElement) {
                window.setTimeout(() => textarea.focus(), 300);
            }
            return;
        }

        if (lastFocused instanceof HTMLElement) {
            lastFocused.focus();
        }
        lastFocused = null;
    };

    const syncLayoutMode = () => {
        if (useFabComposer()) {
            if (!modalRoot.classList.contains("is-open")) {
                overlay.hidden = true;
            }
            return;
        }

        modalRoot.classList.remove("is-open");
        overlay.hidden = false;
        document.body.classList.remove("post-composer-modal-open");
        fab.setAttribute("aria-expanded", "false");
    };

    fab.addEventListener("click", () => {
        if (!useFabComposer()) {
            return;
        }
        setOpen(!modalRoot.classList.contains("is-open"));
    });

    closeBtn?.addEventListener("click", () => setOpen(false));
    backdrop?.addEventListener("click", () => setOpen(false));

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && useFabComposer() && modalRoot.classList.contains("is-open")) {
            event.preventDefault();
            setOpen(false);
        }
    });

    if (typeof mobileQuery.addEventListener === "function") {
        mobileQuery.addEventListener("change", syncLayoutMode);
    } else {
        mobileQuery.addListener(syncLayoutMode);
    }

    syncLayoutMode();
})();
