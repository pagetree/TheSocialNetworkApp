(() => {
    const maxChars = 300;
    const warningAt = 50;

    const overlay = document.getElementById("feed-reply-modal-overlay");
    const closeBtn = document.getElementById("feed-reply-modal-close");
    const textarea = document.getElementById("feed-reply-input");
    const submitBtn = document.getElementById("feed-reply-submit");
    const errorEl = document.getElementById("feed-reply-error");
    const postIdInput = document.getElementById("feed-reply-post-id");
    const counter = document.getElementById("feed-reply-char-counter");
    const progressCircle = document.querySelector(".feed-reply-char-counter-progress");
    const previewAvatar = document.getElementById("feed-reply-preview-avatar");
    const previewAuthor = document.getElementById("feed-reply-preview-author");
    const previewHandle = document.getElementById("feed-reply-preview-handle");
    const previewText = document.getElementById("feed-reply-preview-text");

    const replyUrl = window.APP_POST_REPLY_URL;
    const csrfToken = window.APP_POST_REPLY_CSRF_TOKEN;

    if (
        !overlay
        || !closeBtn
        || !textarea
        || !submitBtn
        || !postIdInput
        || !replyUrl
        || !csrfToken
    ) {
        return;
    }

    let activeCard = null;

    const mediaPicker = window.createReplyMediaPicker?.({
        prefix: "feed-reply",
        textarea,
        submitBtn,
        errorEl,
        imageBtn: document.getElementById("feed-reply-image-btn"),
        videoBtn: document.getElementById("feed-reply-video-btn"),
        onChange: () => updateCounter(),
    });

    const refreshIcons = () => {
        if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
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

    const setLoading = (isLoading) => {
        const canSend = mediaPicker
            ? mediaPicker.canSubmit(textarea.value)
            : textarea.value.trim() !== "";
        submitBtn.disabled = isLoading || !canSend;
        submitBtn.classList.toggle("is-loading", isLoading);
        submitBtn.setAttribute("aria-busy", isLoading ? "true" : "false");
        submitBtn.textContent = isLoading ? "Replying..." : "Reply";
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
            const canSend = mediaPicker
                ? mediaPicker.canSubmit(textarea.value)
                : textarea.value.trim() !== "";
            submitBtn.disabled = !canSend;
        }
    };

    const populatePreview = (card) => {
        const avatar = card.querySelector(".post-avatar");
        const author = card.querySelector(".post-author");
        const handle = card.querySelector(".post-handle");
        const text = card.querySelector(".post-text");

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
    };

    const formatCount = (count) => {
        if (count >= 1_000_000) {
            const value = (count / 1_000_000).toFixed(1).replace(/\.0$/, "");
            return `${value}M`;
        }
        if (count >= 1_000) {
            const value = (count / 1_000).toFixed(1).replace(/\.0$/, "");
            return `${value}K`;
        }
        return String(count);
    };

    const parseCount = (label) => {
        const raw = (label || "").trim().toUpperCase();
        if (raw.endsWith("M")) {
            return Math.round(parseFloat(raw) * 1_000_000) || 0;
        }
        if (raw.endsWith("K")) {
            return Math.round(parseFloat(raw) * 1_000) || 0;
        }
        return parseInt(raw.replace(/[^\d]/g, ""), 10) || 0;
    };

    const incrementReplyCount = (card) => {
        const countEl = card?.querySelector(".post-action-reply span");
        if (!countEl) {
            return;
        }
        countEl.textContent = formatCount(parseCount(countEl.textContent) + 1);
    };

    const closeModal = () => {
        overlay.hidden = true;
        document.body.classList.remove("feed-reply-modal-open");
        textarea.value = "";
        postIdInput.value = "";
        activeCard = null;
        mediaPicker?.clear();
        clearError();
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
        document.body.classList.add("feed-reply-modal-open");
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
            showError("Invalid post.");
            return;
        }

        if (mediaPicker && !mediaPicker.canSubmit(body)) {
            showError("Write a reply or add media before posting.");
            return;
        }

        if (!mediaPicker && !body) {
            showError("Write a reply before posting.");
            return;
        }

        if (body.length > maxChars) {
            showError(`Reply must be ${maxChars} characters or less.`);
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
            formData.append("post_id", String(postId));
            formData.append("body", body);
            formData.append("csrf_token", csrfToken);
            formData.append("_hp_url", "");
            mediaPicker?.appendToFormData(formData);

            const response = await fetch(replyUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.ok) {
                showError(data.error || "Unable to post reply.");
                return;
            }

            if (activeCard) {
                incrementReplyCount(activeCard);
            }
            closeModal();
        } catch {
            showError("Unable to post reply right now.");
        } finally {
            setLoading(false);
            updateCounter();
        }
    });

    const handleReplyClick = (event) => {
        const replyBtn = event.target.closest(".post-action-reply");
        if (!replyBtn) {
            return;
        }

        const card = replyBtn.closest(".post-card.post-card--linkable");
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

    document.getElementById("post-feed")?.addEventListener("click", handleReplyClick);
    document.getElementById("profile-post-feed")?.addEventListener("click", handleReplyClick);
    document.getElementById("hashtag-post-feed")?.addEventListener("click", handleReplyClick);

    updateCounter();
})();
