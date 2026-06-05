(() => {
    const modalRoot = document.getElementById("post-composer-modal");
    const overlay = document.getElementById("post-composer-modal-overlay");

    if (!modalRoot || !overlay) {
        return;
    }

    const isStandalone = modalRoot.classList.contains("post-composer-modal--standalone");
    const fab = document.getElementById("post-composer-fab");
    const closeBtn = document.getElementById("post-composer-modal-close");
    const backdrop = document.getElementById("post-composer-modal-backdrop");
    const textarea = document.getElementById("post-composer-input");
    const mobileQuery = window.matchMedia("(max-width: 900px)");
    let lastFocused = null;

    const isMobile = () => mobileQuery.matches;
    const isInlineFeedDesktop = () => !isStandalone && !isMobile();

    const refreshIcons = () => {
        if (typeof window.refreshLucideIcons === "function") {
            window.refreshLucideIcons();
        }
    };

    const applyMentionDraft = () => {
        const mention = typeof window.APP_POST_COMPOSER_MENTION === "string"
            ? window.APP_POST_COMPOSER_MENTION.trim()
            : "";

        if (!mention || !(textarea instanceof HTMLTextAreaElement) || textarea.value.trim() !== "") {
            return;
        }

        textarea.value = mention.endsWith(" ") ? mention : `${mention} `;
        textarea.dispatchEvent(new Event("input", { bubbles: true }));
    };

    const setOpen = (open, options = {}) => {
        const { applyMention = false } = options;

        if (isInlineFeedDesktop() && !open) {
            modalRoot.classList.remove("is-open");
            document.body.classList.remove("post-composer-modal-open");
            overlay.hidden = false;
            fab?.setAttribute("aria-expanded", "false");

            if (lastFocused instanceof HTMLElement) {
                lastFocused.focus();
            }
            lastFocused = null;
            return;
        }

        if (open) {
            overlay.hidden = false;
            requestAnimationFrame(() => {
                modalRoot.classList.add("is-open");
            });
            document.body.classList.add("post-composer-modal-open");
            fab?.setAttribute("aria-expanded", "true");
            lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
            refreshIcons();

            if (applyMention) {
                applyMentionDraft();
            }

            if (textarea instanceof HTMLTextAreaElement) {
                window.setTimeout(() => textarea.focus(), isMobile() ? 300 : 150);
            }
            return;
        }

        modalRoot.classList.remove("is-open");
        document.body.classList.remove("post-composer-modal-open");
        fab?.setAttribute("aria-expanded", "false");

        window.setTimeout(() => {
            if (modalRoot.classList.contains("is-open")) {
                return;
            }

            if (isStandalone || isMobile()) {
                overlay.hidden = true;
            }
        }, 300);

        if (lastFocused instanceof HTMLElement) {
            lastFocused.focus();
        }
        lastFocused = null;
    };

    const syncLayoutMode = () => {
        if (isStandalone) {
            if (!modalRoot.classList.contains("is-open")) {
                overlay.hidden = true;
            }
            return;
        }

        if (isMobile()) {
            if (!modalRoot.classList.contains("is-open")) {
                overlay.hidden = true;
            }
            return;
        }

        modalRoot.classList.remove("is-open");
        overlay.hidden = false;
        document.body.classList.remove("post-composer-modal-open");
        fab?.setAttribute("aria-expanded", "false");
    };

    document.querySelectorAll("[data-post-composer-open]").forEach((trigger) => {
        trigger.addEventListener("click", () => setOpen(true, { applyMention: true }));
    });

    fab?.addEventListener("click", () => {
        if (!isMobile()) {
            return;
        }

        setOpen(!modalRoot.classList.contains("is-open"));
    });

    closeBtn?.addEventListener("click", () => setOpen(false));
    backdrop?.addEventListener("click", () => setOpen(false));

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modalRoot.classList.contains("is-open")) {
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

    window.openPostComposerModal = (options = {}) => setOpen(true, options);
    window.closePostComposerModal = () => setOpen(false);
})();
