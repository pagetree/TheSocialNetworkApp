(() => {
    const t = (key, replacements = {}) =>
        window.AppI18n?.t?.(key, replacements) ?? key;
    const maxChars = 300;
    const warningAt = 50;

    const overlay = document.getElementById("post-quote-modal-overlay");
    const closeBtn = document.getElementById("post-quote-modal-close");
    const textarea = document.getElementById("post-quote-input");
    const submitBtn = document.getElementById("post-quote-submit");
    const errorEl = document.getElementById("post-quote-error");
    const postIdInput = document.getElementById("post-quote-post-id");
    const counter = document.getElementById("post-quote-char-counter");
    const progressCircle = document.querySelector(".post-quote-char-counter-progress");
    const previewAvatar = document.getElementById("post-quote-preview-avatar");
    const previewAuthor = document.getElementById("post-quote-preview-author");
    const previewHandle = document.getElementById("post-quote-preview-handle");
    const previewText = document.getElementById("post-quote-preview-text");
    const previewMedia = document.getElementById("post-quote-preview-media");

    const createUrl = window.APP_POST_CREATE_URL;
    const csrfToken = window.APP_POST_CSRF_TOKEN;

    if (
        !overlay
        || !closeBtn
        || !textarea
        || !submitBtn
        || !postIdInput
        || !createUrl
        || !csrfToken
    ) {
        return;
    }

    let activeCard = null;

    const mediaPicker = window.createReplyMediaPicker?.({
        prefix: "post-quote",
        textarea,
        submitBtn,
        errorEl,
        imageBtn: document.getElementById("post-quote-image-btn"),
        videoBtn: document.getElementById("post-quote-video-btn"),
        onChange: () => updateCounter(),
    });

    const refreshIcons = () => {
        if (typeof window.refreshLucideIcons === "function") {
            window.refreshLucideIcons();
        }
    };

    const showError = (message) => {
        if (!errorEl) {
            return;
        }
        errorEl.textContent = message;
        errorEl.hidden = false;
    };

    const clearError = () => {
        if (!errorEl) {
            return;
        }
        errorEl.textContent = "";
        errorEl.hidden = true;
    };

    const canSubmit = () => {
        const hasQuote = Number(postIdInput.value || 0) > 0;

        if (mediaPicker) {
            return mediaPicker.canSubmit(textarea.value) || hasQuote;
        }

        return textarea.value.trim() !== "" || hasQuote;
    };

    const setLoading = (isLoading) => {
        submitBtn.disabled = isLoading || !canSubmit();
        submitBtn.classList.toggle("is-loading", isLoading);
        submitBtn.setAttribute("aria-busy", isLoading ? "true" : "false");
        submitBtn.textContent = isLoading ? t("composer.posting") : t("composer.post");
        textarea.disabled = isLoading;
        closeBtn.disabled = isLoading;
    };

    const radius = 15.5;
    const circumference = progressCircle ? 2 * Math.PI * radius : 0;

    if (progressCircle) {
        progressCircle.style.strokeDasharray = String(circumference);
    }

    const updateCounter = () => {
        const used = textarea.value.length;
        const remaining = maxChars - used;
        const progress = remaining / maxChars;

        if (progressCircle) {
            progressCircle.style.strokeDashoffset = String(circumference * (1 - progress));
        }

        const isTyping = used > 0;
        if (counter) {
            counter.classList.toggle("is-visible", isTyping);
            counter.hidden = !isTyping;
            counter.classList.remove("is-warning", "is-danger");
            if (remaining === 0) {
                counter.classList.add("is-danger");
            } else if (remaining <= warningAt) {
                counter.classList.add("is-warning");
            }
            if (isTyping) {
                counter.setAttribute(
                    "aria-label",
                    `${remaining} character${remaining === 1 ? "" : "s"} remaining`
                );
            } else {
                counter.removeAttribute("aria-label");
            }
        }

        if (!submitBtn.classList.contains("is-loading")) {
            submitBtn.disabled = !canSubmit();
        }
    };

    const populatePreview = (card) => {
        const avatar = card.querySelector(".post-header .post-avatar");
        const author = card.querySelector(".post-author, .post-author-link");
        const handle = card.querySelector(".post-handle");
        const text = card.querySelector(".post-text");
        const mediaGallery = card.querySelector(".post-media-gallery");

        if (previewAvatar && avatar instanceof HTMLImageElement) {
            previewAvatar.src = avatar.src;
            previewAvatar.alt = avatar.alt || "Post author avatar";
        }

        if (previewAuthor) {
            previewAuthor.textContent = author?.textContent?.trim() || "User";
        }

        if (previewHandle) {
            previewHandle.textContent = handle?.textContent?.trim() || "@user";
        }

        if (previewText) {
            const body = text?.textContent?.trim() || "";
            previewText.textContent = body;
            previewText.hidden = body === "";
        }

        if (previewMedia) {
            previewMedia.replaceChildren();
            if (mediaGallery) {
                const clone = mediaGallery.cloneNode(true);
                clone.querySelectorAll("video").forEach((video) => {
                    video.removeAttribute("controls");
                    video.setAttribute("preload", "metadata");
                });
                previewMedia.appendChild(clone);
            }
        }
    };

    const closeModal = () => {
        overlay.hidden = true;
        document.body.classList.remove("post-quote-modal-open");
        textarea.value = "";
        postIdInput.value = "";
        activeCard = null;
        mediaPicker?.clear();
        clearError();
        if (previewText) {
            previewText.textContent = "";
            previewText.hidden = true;
        }
        if (previewMedia) {
            previewMedia.replaceChildren();
        }
        updateCounter();
        textarea.disabled = false;
        closeBtn.disabled = false;
    };

    const openModal = (card) => {
        const postId = Number(card.dataset.postId || 0);
        if (postId < 1) {
            return;
        }

        activeCard = card;
        postIdInput.value = String(postId);
        populatePreview(card);
        overlay.hidden = false;
        document.body.classList.add("post-quote-modal-open");
        clearError();
        updateCounter();
        refreshIcons();
        textarea.focus();
    };

    closeBtn.addEventListener("click", closeModal);

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            closeModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !overlay.hidden) {
            closeModal();
        }
    });

    textarea.addEventListener("input", () => {
        clearError();
        updateCounter();
    });

    submitBtn.addEventListener("click", async () => {
        const postId = Number(postIdInput.value || 0);
        const body = textarea.value.trim();

        clearError();

        if (postId < 1) {
            showError(t("api.invalid_post"));
            return;
        }

        if (!canSubmit()) {
            showError(t("composer.errors.body_or_media_required"));
            return;
        }

        if (body.length > maxChars) {
            showError(t("composer.errors.too_long", { max: maxChars }));
            return;
        }

        if (mediaPicker) {
            const selectionError = mediaPicker.validateBeforeSubmit();
            if (selectionError) {
                showError(selectionError);
                return;
            }
        }

        setLoading(true);

        try {
            const formData = new FormData();
            formData.append("body", body);
            formData.append("quoted_post_id", String(postId));
            formData.append("csrf_token", csrfToken);
            formData.append("_hp_url", "");
            mediaPicker?.appendToFormData(formData);

            const response = await fetch(createUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.ok) {
                showError(data.error || t("quote.errors.create_failed"));
                return;
            }

            if (data.quoted_post_id && data.quote_label !== undefined) {
                document
                    .querySelectorAll(
                        `.post-card[data-post-id="${data.quoted_post_id}"] .post-action-quote span`
                    )
                    .forEach((countEl) => {
                        countEl.textContent = data.quote_label;
                    });
            }

            closeModal();
            window.location.reload();
        } catch {
            showError(t("quote.errors.create_failed"));
        } finally {
            setLoading(false);
            updateCounter();
        }
    });

    const handleQuoteClick = (event) => {
        const quoteBtn = event.target.closest(".post-action-quote");
        if (!quoteBtn) {
            return;
        }

        const card = quoteBtn.closest(".post-card.post-card--linkable");
        if (!card || card.classList.contains("post-composer")) {
            return;
        }

        if (!card.closest("#post-feed, #profile-post-feed, #hashtag-post-feed")) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        openModal(card);
    };

    document.getElementById("post-feed")?.addEventListener("click", handleQuoteClick);
    document.getElementById("profile-post-feed")?.addEventListener("click", handleQuoteClick);
    document.getElementById("hashtag-post-feed")?.addEventListener("click", handleQuoteClick);

    updateCounter();
})();
