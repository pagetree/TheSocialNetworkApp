(() => {
    const maxChars = 300;
    const warningAt = 50;

    const textarea = document.getElementById("post-reply-input");
    const counter = document.getElementById("post-reply-char-counter-label");
    const progressCircle = document.querySelector(".post-reply-char-counter-progress");
    const submitBtn = document.getElementById("post-reply-submit");
    const errorEl = document.getElementById("post-reply-form-error");
    const repliesList = document.getElementById("post-replies");
    const replyUrl = window.APP_POST_REPLY_URL;
    const csrfToken = window.APP_POST_REPLY_CSRF_TOKEN;
    const postId = Number(window.APP_POST_REPLY_POST_ID || 0);

    const modalOverlay = document.getElementById("reply-modal-overlay");
    const modalInput = document.getElementById("reply-modal-input");
    const modalError = document.getElementById("reply-modal-error");
    const modalSubmit = document.getElementById("reply-modal-submit");
    const modalCancel = document.getElementById("reply-modal-cancel");
    const modalParentId = document.getElementById("reply-modal-parent-id");
    const modalCharCounter = document.getElementById("reply-modal-char-counter");
    const modalCharProgress = document.querySelector(".reply-modal-char-progress");

    const hasComposer = Boolean(
        textarea && counter && progressCircle && submitBtn && replyUrl && csrfToken && postId > 0
    );
    const hasModal = Boolean(
        modalOverlay && modalInput && modalSubmit && modalCancel && modalParentId && replyUrl && csrfToken && postId > 0
    );

    if (!hasComposer && !hasModal) {
        return;
    }

    const t = (key, replacements = {}) =>
        window.AppI18n?.t?.(key, replacements) ?? key;

    const refreshIcons = () => {
        if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
        }
    };

    const incrementReplyCounts = (reply) => {
        const parentId = Number(reply?.parent_reply_id || 0);

        if (parentId > 0) {
            const parentEl = repliesList?.querySelector(`[data-reply-id="${parentId}"]`);
            const countEl = parentEl?.querySelector(".post-reply-action-reply span");
            if (countEl) {
                const current = parseInt(countEl.textContent.replace(/[^\d]/g, ""), 10) || 0;
                countEl.textContent = String(current + 1);
            }
            return;
        }

        const countEl = document.querySelector(".post-detail-reply-count span");
        if (!countEl) {
            return;
        }
        const current = parseInt(countEl.textContent.replace(/[^\d]/g, ""), 10) || 0;
        countEl.textContent = String(current + 1);
    };

    const getReplyDepth = (element) => Number(element?.dataset.replyDepth || 0);

    const escapeHtml = (value) => {
        const el = document.createElement("div");
        el.textContent = String(value ?? "");
        return el.innerHTML;
    };

    const buildReplyActionsHtml = (replyCount = "0", likeCount = "0") => `
        <footer class="post-actions post-reply-actions" aria-label="${escapeHtml(t("reply.engagement"))}">
            <button type="button" class="post-action post-reply-action-reply" aria-label="${escapeHtml(t("reply.to_reply"))}">
                <i data-lucide="message-circle" aria-hidden="true"></i>
                <span>${replyCount}</span>
            </button>
            <button type="button" class="post-action" aria-label="${escapeHtml(t("reply.repost"))}">
                <i data-lucide="repeat-2" aria-hidden="true"></i>
                <span>0</span>
            </button>
            <button type="button" class="post-action" aria-label="${escapeHtml(t("reply.like"))}">
                <i data-lucide="heart" aria-hidden="true"></i>
                <span>${likeCount}</span>
            </button>
        </footer>
    `;

    const appendReplyMedia = (container, mediaItems) => {
        const items = Array.isArray(mediaItems) ? mediaItems : [];
        if (items.length === 0 || !container) {
            return;
        }

        let galleryClass = "post-media-gallery";
        if (items.length === 1) {
            galleryClass += " post-media-gallery--1";
        } else if (items.length === 2) {
            galleryClass += " post-media-gallery--2";
        } else if (items.length === 3) {
            galleryClass += " post-media-gallery--3";
        } else if (items.length >= 4) {
            galleryClass += " post-media-gallery--4";
        }

        const gallery = document.createElement("div");
        gallery.className = galleryClass;

        items.forEach((mediaItem) => {
            const mediaUrl = mediaItem?.url || "";
            const mediaType = mediaItem?.type || "";
            if (!mediaUrl) {
                return;
            }

            if (mediaType === "video") {
                const video = document.createElement("video");
                video.className = "post-media post-media--video";
                video.controls = true;
                video.preload = "metadata";
                video.playsInline = true;
                video.src = mediaUrl;
                gallery.appendChild(video);
                return;
            }

            const img = document.createElement("img");
            img.className = "post-media post-media--zoomable";
            img.src = mediaUrl;
            img.alt = "";
            gallery.appendChild(img);
        });

        if (gallery.childElementCount > 0) {
            container.appendChild(gallery);
        }
    };

    const buildReplyArticle = (reply, depth = 0) => {
        const article = document.createElement("article");
        const nestedClass = depth > 0 ? " post-reply-item--nested" : "";
        const parentReplyId = Number(reply.parent_reply_id || 0);
        const replyBody = (reply.body ?? "").trim();
        const mediaItems = Array.isArray(reply.media) ? reply.media : [];

        article.className = `post-reply-item${nestedClass}`;
        article.dataset.replyId = String(reply.id ?? "");
        article.dataset.replyDepth = String(depth);
        article.dataset.parentReplyId = String(parentReplyId);
        if (depth > 0) {
            article.style.setProperty("--reply-depth", String(depth));
        }

        const authorName = reply.author?.display_name ?? "User";
        const authorHandle = reply.author?.handle ?? "@user";
        const likeCount = reply.like_count != null ? String(reply.like_count) : "0";
        const replyCount = reply.reply_count != null ? String(reply.reply_count) : "0";

        article.innerHTML = `
            <div class="post-reply-avatar-col">
                <img class="post-reply-avatar" src="${escapeHtml(reply.author?.avatar_url ?? "")}" alt="${escapeHtml(`${authorName} avatar`)}">
                <span class="post-reply-thread-line" aria-hidden="true"></span>
            </div>
            <div class="post-reply-body">
                <header class="post-reply-header">
                    <p class="post-reply-meta-line">
                        <span class="post-reply-author">${escapeHtml(authorName)}</span>
                        <span class="post-reply-handle">${escapeHtml(authorHandle)}</span>
                        <time class="post-reply-time" datetime="${escapeHtml(reply.created_at ?? "")}">${escapeHtml(reply.time_label ?? "just now")}</time>
                    </p>
                </header>
                ${replyBody !== "" ? '<p class="post-reply-text"></p>' : ""}
                <div class="post-reply-media-slot"></div>
                ${buildReplyActionsHtml(replyCount, likeCount)}
            </div>
        `;

        if (replyBody !== "") {
            article.querySelector(".post-reply-text").textContent = replyBody;
        }

        const mediaSlot = article.querySelector(".post-reply-media-slot");
        appendReplyMedia(mediaSlot, mediaItems);
        if (mediaSlot && mediaSlot.childElementCount === 0) {
            mediaSlot.remove();
        }

        return article;
    };

    const findReplyThread = (replyEl) => replyEl?.closest(".post-reply-thread") ?? null;

    const insertReplyInThread = (thread, replyEl, parentEl) => {
        const parentDepth = getReplyDepth(parentEl);
        let cursor = replyEl.nextElementSibling;

        while (
            cursor &&
            cursor.classList.contains("post-reply-item") &&
            getReplyDepth(cursor) > parentDepth
        ) {
            cursor = cursor.nextElementSibling;
        }

        if (cursor) {
            thread.insertBefore(replyEl, cursor);
        } else {
            thread.appendChild(replyEl);
        }
    };

    const insertReplyInTree = (reply) => {
        if (!repliesList || !reply) {
            return;
        }

        const parentId = Number(reply.parent_reply_id || 0);

        if (parentId <= 0) {
            const thread = document.createElement("div");
            thread.className = "post-reply-thread";
            thread.appendChild(buildReplyArticle(reply, 0));
            repliesList.appendChild(thread);
            refreshIcons();
            return;
        }

        const parentEl = repliesList.querySelector(`[data-reply-id="${parentId}"]`);
        if (!parentEl) {
            const thread = document.createElement("div");
            thread.className = "post-reply-thread";
            thread.appendChild(buildReplyArticle(reply, 0));
            repliesList.appendChild(thread);
            refreshIcons();
            return;
        }

        const depth = getReplyDepth(parentEl) + 1;
        const newEl = buildReplyArticle(reply, depth);
        const thread = findReplyThread(parentEl);

        if (!thread) {
            repliesList.appendChild(newEl);
            refreshIcons();
            return;
        }

        insertReplyInThread(thread, newEl, parentEl);
        refreshIcons();
    };

    const submitReply = async ({ body, parentReplyId = null, mediaPicker = null, onSuccess, onError, setLoading }) => {
        const trimmedBody = body.trim();

        if (mediaPicker && !mediaPicker.canSubmit(trimmedBody)) {
            onError(t("reply.errors.body_or_media_required"));
            return;
        }

        if (!mediaPicker && !trimmedBody) {
            onError(t("reply.errors.body_required"));
            return;
        }

        if (trimmedBody.length > maxChars) {
            onError(t("reply.errors.too_long", { max: maxChars }));
            return;
        }

        if (mediaPicker) {
            const selectionError = mediaPicker.validateBeforeSubmit();
            if (selectionError) {
                onError(selectionError);
                return;
            }
        }

        setLoading(true);

        try {
            const formData = new FormData();
            formData.append("post_id", String(postId));
            formData.append("body", trimmedBody);
            formData.append("csrf_token", csrfToken);
            formData.append("_hp_url", "");

            const nestedParentId = Number(parentReplyId || 0);
            if (nestedParentId > 0) {
                formData.append("parent_reply_id", String(nestedParentId));
            }

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
                onError(data.error || t("reply.errors.create_failed"));
                return;
            }

            insertReplyInTree(data.reply);
            incrementReplyCounts(data.reply);
            onSuccess();
        } catch {
            onError(t("reply.errors.create_failed"));
        } finally {
            setLoading(false);
        }
    };

    if (hasComposer) {
        const radius = 15.5;
        const circumference = 2 * Math.PI * radius;
        progressCircle.style.strokeDasharray = String(circumference);

        const composerMediaPicker = window.createReplyMediaPicker?.({
            prefix: "post-reply",
            textarea,
            submitBtn,
            errorEl,
            imageBtn: document.getElementById("post-reply-image-btn"),
            videoBtn: document.getElementById("post-reply-video-btn"),
            onChange: () => updateComposerCounter(),
        });

        const showComposerError = (message) => {
            if (!errorEl) {
                return;
            }
            errorEl.textContent = message;
            errorEl.hidden = false;
        };

        const clearComposerError = () => {
            if (!errorEl) {
                return;
            }
            errorEl.textContent = "";
            errorEl.hidden = true;
        };

        const setComposerLoading = (isLoading) => {
            const canSend = composerMediaPicker
                ? composerMediaPicker.canSubmit(textarea.value)
                : textarea.value.trim() !== "";
            submitBtn.disabled = isLoading || !canSend;
            submitBtn.classList.toggle("is-loading", isLoading);
            submitBtn.setAttribute("aria-busy", isLoading ? "true" : "false");
            submitBtn.textContent = isLoading ? t("reply.replying") : t("reply.reply");
            textarea.disabled = isLoading;
        };

        const updateComposerCounter = () => {
            const used = textarea.value.length;
            const remaining = maxChars - used;
            const progress = remaining / maxChars;

            progressCircle.style.strokeDashoffset = String(circumference * (1 - progress));

            const isTyping = used > 0;
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

            if (!submitBtn.classList.contains("is-loading")) {
                const canSend = composerMediaPicker
                    ? composerMediaPicker.canSubmit(textarea.value)
                    : textarea.value.trim() !== "";
                submitBtn.disabled = !canSend;
            }
        };

        textarea.addEventListener("input", () => {
            clearComposerError();
            updateComposerCounter();
        });

        submitBtn.addEventListener("click", async () => {
            clearComposerError();
            await submitReply({
                body: textarea.value,
                parentReplyId: null,
                mediaPicker: composerMediaPicker,
                onSuccess: () => {
                    textarea.value = "";
                    composerMediaPicker?.clear();
                    updateComposerCounter();
                },
                onError: showComposerError,
                setLoading: setComposerLoading,
            });
            updateComposerCounter();
        });

        document.querySelector(".post-detail-reply-count")?.addEventListener("click", () => {
            modalParentId && (modalParentId.value = "");
            textarea.focus();
            textarea.scrollIntoView({ behavior: "smooth", block: "center" });
        });

        updateComposerCounter();
    }

    if (hasModal) {
        let modalCharCircumference = 0;

        const modalMediaPicker = window.createReplyMediaPicker?.({
            prefix: "reply-modal",
            textarea: modalInput,
            submitBtn: modalSubmit,
            errorEl: modalError,
            imageBtn: document.getElementById("reply-modal-image-btn"),
            videoBtn: document.getElementById("reply-modal-video-btn"),
            onChange: () => updateModalCounter(),
        });

        if (modalCharProgress) {
            const modalRadius = 15.5;
            modalCharCircumference = 2 * Math.PI * modalRadius;
            modalCharProgress.style.strokeDasharray = String(modalCharCircumference);
        }

        const showModalError = (message) => {
            modalError.textContent = message;
            modalError.hidden = false;
        };

        const clearModalError = () => {
            modalError.textContent = "";
            modalError.hidden = true;
        };

        const setModalLoading = (isLoading) => {
            const canSend = modalMediaPicker
                ? modalMediaPicker.canSubmit(modalInput.value)
                : modalInput.value.trim() !== "";
            modalSubmit.disabled = isLoading || !canSend;
            modalSubmit.classList.toggle("is-loading", isLoading);
            modalSubmit.setAttribute("aria-busy", isLoading ? "true" : "false");
            modalSubmit.textContent = isLoading ? t("reply.submitting") : t("reply.submit");
            modalInput.disabled = isLoading;
            modalCancel.disabled = isLoading;
        };

        const updateModalCounter = () => {
            if (!modalCharCounter || !modalCharProgress) {
                const canSend = modalMediaPicker
                    ? modalMediaPicker.canSubmit(modalInput.value)
                    : modalInput.value.trim() !== "";
                modalSubmit.disabled = !canSend || modalSubmit.classList.contains("is-loading");
                return;
            }

            const used = modalInput.value.length;
            const remaining = maxChars - used;
            const progress = remaining / maxChars;

            modalCharProgress.style.strokeDashoffset = String(modalCharCircumference * (1 - progress));

            const isTyping = used > 0;
            modalCharCounter.hidden = !isTyping;

            if (!modalSubmit.classList.contains("is-loading")) {
                const canSend = modalMediaPicker
                    ? modalMediaPicker.canSubmit(modalInput.value)
                    : modalInput.value.trim() !== "";
                modalSubmit.disabled = !canSend;
            }
        };

        const closeModal = () => {
            modalOverlay.hidden = true;
            document.body.classList.remove("reply-modal-open");
            modalInput.value = "";
            modalParentId.value = "";
            modalMediaPicker?.clear();
            clearModalError();
            updateModalCounter();
            modalInput.disabled = false;
            modalCancel.disabled = false;
        };

        const openModal = (parentReplyId) => {
            modalParentId.value = String(parentReplyId);
            modalOverlay.hidden = false;
            document.body.classList.add("reply-modal-open");
            clearModalError();
            updateModalCounter();
            modalInput.focus();
        };

        modalCancel.addEventListener("click", closeModal);

        modalOverlay.addEventListener("click", (event) => {
            if (event.target === modalOverlay) {
                closeModal();
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && !modalOverlay.hidden) {
                closeModal();
            }
        });

        modalInput.addEventListener("input", () => {
            clearModalError();
            updateModalCounter();
        });

        modalSubmit.addEventListener("click", async () => {
            clearModalError();
            const parentReplyId = Number(modalParentId.value || 0);

            await submitReply({
                body: modalInput.value,
                parentReplyId: parentReplyId > 0 ? parentReplyId : null,
                mediaPicker: modalMediaPicker,
                onSuccess: closeModal,
                onError: showModalError,
                setLoading: setModalLoading,
            });
            updateModalCounter();
        });

        repliesList?.addEventListener("click", (event) => {
            const replyBtn = event.target.closest(".post-reply-action-reply");
            if (!replyBtn || !repliesList.contains(replyBtn)) {
                return;
            }

            const replyItem = replyBtn.closest(".post-reply-item");
            const replyId = Number(replyItem?.dataset.replyId || 0);
            if (replyId < 1) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            openModal(replyId);
        });

        updateModalCounter();
    }
})();
