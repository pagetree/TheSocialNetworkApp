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

    const buildReplyActionsHtml = (replyCount = "0", likeCount = "0") => `
        <footer class="post-actions post-reply-actions" aria-label="Reply engagement">
            <button type="button" class="post-action post-reply-action-reply" aria-label="Reply to this reply">
                <i data-lucide="message-circle" aria-hidden="true"></i>
                <span>${replyCount}</span>
            </button>
            <button type="button" class="post-action" aria-label="Repost reply">
                <i data-lucide="repeat-2" aria-hidden="true"></i>
                <span>0</span>
            </button>
            <button type="button" class="post-action" aria-label="Like reply">
                <i data-lucide="heart" aria-hidden="true"></i>
                <span>${likeCount}</span>
            </button>
        </footer>
    `;

    const buildReplyArticle = (reply, depth = 0) => {
        const article = document.createElement("article");
        const nestedClass = depth > 0 ? " post-reply-item--nested" : "";
        const parentReplyId = Number(reply.parent_reply_id || 0);

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
                <img class="post-reply-avatar" src="${reply.author?.avatar_url ?? ""}" alt="${authorName} avatar">
                <span class="post-reply-thread-line" aria-hidden="true"></span>
            </div>
            <div class="post-reply-body">
                <header class="post-reply-header">
                    <p class="post-reply-meta-line">
                        <span class="post-reply-author">${authorName}</span>
                        <span class="post-reply-handle">${authorHandle}</span>
                        <time class="post-reply-time" datetime="${reply.created_at ?? ""}">${reply.time_label ?? "just now"}</time>
                    </p>
                </header>
                <p class="post-reply-text"></p>
                ${buildReplyActionsHtml(replyCount, likeCount)}
            </div>
        `;
        article.querySelector(".post-reply-text").textContent = reply.body ?? "";

        return article;
    };

    const insertReplyInTree = (reply) => {
        if (!repliesList || !reply) {
            return;
        }

        const parentId = Number(reply.parent_reply_id || 0);
        let depth = 0;

        if (parentId > 0) {
            const parentEl = repliesList.querySelector(`[data-reply-id="${parentId}"]`);
            if (!parentEl) {
                repliesList.appendChild(buildReplyArticle(reply, 0));
                refreshIcons();
                return;
            }

            depth = getReplyDepth(parentEl) + 1;
            const newEl = buildReplyArticle(reply, depth);
            const parentDepth = getReplyDepth(parentEl);
            let cursor = parentEl.nextElementSibling;

            while (
                cursor &&
                cursor.classList.contains("post-reply-item") &&
                getReplyDepth(cursor) > parentDepth
            ) {
                cursor = cursor.nextElementSibling;
            }

            if (cursor) {
                repliesList.insertBefore(newEl, cursor);
            } else {
                repliesList.appendChild(newEl);
            }

            refreshIcons();
            return;
        }

        repliesList.appendChild(buildReplyArticle(reply, depth));
        refreshIcons();
    };

    const submitReply = async ({ body, parentReplyId = null, onSuccess, onError, setLoading }) => {
        if (!body) {
            onError("Write a reply before posting.");
            return;
        }

        if (body.length > maxChars) {
            onError(`Reply must be ${maxChars} characters or less.`);
            return;
        }

        setLoading(true);

        try {
            const payload = {
                post_id: postId,
                body,
                csrf_token: csrfToken,
                _hp_url: "",
            };

            const nestedParentId = Number(parentReplyId || 0);
            if (nestedParentId > 0) {
                payload.parent_reply_id = nestedParentId;
            }

            const response = await fetch(replyUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.ok) {
                onError(data.error || "Unable to post reply.");
                return;
            }

            insertReplyInTree(data.reply);
            incrementReplyCounts(data.reply);
            onSuccess();
        } catch {
            onError("Unable to post reply right now.");
        } finally {
            setLoading(false);
        }
    };

    if (hasComposer) {
        const radius = 15.5;
        const circumference = 2 * Math.PI * radius;
        progressCircle.style.strokeDasharray = String(circumference);

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
            submitBtn.disabled = isLoading || textarea.value.trim() === "";
            submitBtn.classList.toggle("is-loading", isLoading);
            submitBtn.setAttribute("aria-busy", isLoading ? "true" : "false");
            submitBtn.textContent = isLoading ? "Replying..." : "Reply";
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
                submitBtn.disabled = textarea.value.trim() === "";
            }
        };

        textarea.addEventListener("input", () => {
            clearComposerError();
            updateComposerCounter();
        });

        document.getElementById("post-reply-image-btn")?.addEventListener("click", () => {
            showComposerError("Image replies are not available yet.");
        });

        document.getElementById("post-reply-video-btn")?.addEventListener("click", () => {
            showComposerError("Video replies are not available yet.");
        });

        submitBtn.addEventListener("click", async () => {
            clearComposerError();
            await submitReply({
                body: textarea.value.trim(),
                parentReplyId: null,
                onSuccess: () => {
                    textarea.value = "";
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
            modalSubmit.disabled = isLoading || modalInput.value.trim() === "";
            modalSubmit.classList.toggle("is-loading", isLoading);
            modalSubmit.setAttribute("aria-busy", isLoading ? "true" : "false");
            modalSubmit.textContent = isLoading ? "Submitting..." : "Submit";
            modalInput.disabled = isLoading;
            modalCancel.disabled = isLoading;
        };

        const updateModalCounter = () => {
            if (!modalCharCounter || !modalCharProgress) {
                modalSubmit.disabled = modalInput.value.trim() === "" || modalSubmit.classList.contains("is-loading");
                return;
            }

            const used = modalInput.value.length;
            const remaining = maxChars - used;
            const progress = remaining / maxChars;

            modalCharProgress.style.strokeDashoffset = String(modalCharCircumference * (1 - progress));

            const isTyping = used > 0;
            modalCharCounter.hidden = !isTyping;

            if (!modalSubmit.classList.contains("is-loading")) {
                modalSubmit.disabled = modalInput.value.trim() === "";
            }
        };

        const closeModal = () => {
            modalOverlay.hidden = true;
            document.body.classList.remove("reply-modal-open");
            modalInput.value = "";
            modalParentId.value = "";
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
                body: modalInput.value.trim(),
                parentReplyId: parentReplyId > 0 ? parentReplyId : null,
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
