(() => {
    const feed = document.getElementById("hashtag-post-feed");
    const replyUrl = window.APP_POST_REPLY_URL;
    if (!feed || !replyUrl) {
        return;
    }

    const resolveHashtagBase = (card) => {
        const postUrl = card?.dataset?.postUrl
            || feed.querySelector(".post-card[data-post-url]")?.dataset?.postUrl
            || "";
        const postMarker = "/post/";
        const postIdx = postUrl.indexOf(postMarker);
        if (postIdx >= 0) {
            return postUrl.slice(0, postIdx);
        }

        const backLink = document.querySelector(".hashtag-page-back");
        if (backLink instanceof HTMLAnchorElement && backLink.href) {
            try {
                const parsed = new URL(backLink.href, window.location.origin);
                const path = parsed.pathname.replace(/\/$/, "");
                if (path && path !== "/") {
                    return path;
                }
            } catch {
                return "";
            }
        }

        return "";
    };

    const hashtagHref = (tag, card) => {
        const base = resolveHashtagBase(card);
        return base ? `${base}/hashtag/${tag}` : `/hashtag/${tag}`;
    };

    let activeCard = null;

    const escapeHtml = (value) => {
        const el = document.createElement("div");
        el.textContent = String(value ?? "");
        return el.innerHTML;
    };

    const formatReplyBodyHtml = (body, card) => {
        const text = String(body ?? "").trim();
        if (text === "") {
            return "";
        }

        const pattern = /#([\p{L}\p{N}_]{1,50})/gu;
        let html = "";
        let lastIndex = 0;
        let match;

        while ((match = pattern.exec(text)) !== null) {
            const start = match.index;
            if (start > lastIndex) {
                html += escapeHtml(text.slice(lastIndex, start));
            }

            const rawTag = match[1];
            const tag = rawTag.toLowerCase().replace(/[.,!?]+$/g, "");
            if (tag !== "") {
                const href = escapeHtml(hashtagHref(tag, card));
                html += `<a href="${href}" class="post-hashtag">#${escapeHtml(tag)}</a>`;
            } else {
                html += escapeHtml(match[0]);
            }

            lastIndex = pattern.lastIndex;
        }

        if (lastIndex < text.length) {
            html += escapeHtml(text.slice(lastIndex));
        }

        return html;
    };

    const getRepliesRoot = (card) => {
        let section = card.querySelector(".hashtag-post-replies");
        if (!section) {
            section = document.createElement("section");
            section.className = "hashtag-post-replies post-replies";
            section.setAttribute("aria-label", "Replies to this post");
            const thread = document.createElement("div");
            thread.className = "post-reply-thread";
            section.appendChild(thread);
            card.appendChild(section);
        }

        section.hidden = false;
        let thread = section.querySelector(".post-reply-thread");
        if (!thread) {
            thread = document.createElement("div");
            thread.className = "post-reply-thread";
            section.appendChild(thread);
        }

        return thread;
    };

    const buildReplyElement = (reply, card) => {
        const article = document.createElement("article");
        const body = String(reply.body ?? "").trim();
        const bodyHtml = formatReplyBodyHtml(body, card);
        const authorName = reply.author?.display_name ?? "User";
        const authorHandle = reply.author?.handle ?? "@user";
        const avatarUrl = reply.author?.avatar_url ?? "";
        const likeCount = String(reply.like_count ?? 0);
        const replyCount = String(reply.reply_count ?? 0);

        article.className = "post-reply-item";
        article.dataset.replyId = String(reply.id ?? "");
        article.dataset.replyDepth = "0";
        article.dataset.parentReplyId = "0";

        article.innerHTML = `
            <div class="post-reply-avatar-col">
                <img class="post-reply-avatar" src="${escapeHtml(avatarUrl)}" alt="${escapeHtml(`${authorName} avatar`)}">
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
                ${bodyHtml !== "" ? `<p class="post-reply-text">${bodyHtml}</p>` : ""}
                <footer class="post-actions post-reply-actions" aria-label="Reply engagement">
                    <button type="button" class="post-action post-reply-action-reply" aria-label="Reply to this reply">
                        <i data-lucide="message-circle" aria-hidden="true"></i>
                        <span>${escapeHtml(replyCount)}</span>
                    </button>
                    <button type="button" class="post-action" aria-label="Repost reply">
                        <i data-lucide="repeat-2" aria-hidden="true"></i>
                        <span>0</span>
                    </button>
                    <button type="button" class="post-action" aria-label="Like reply">
                        <i data-lucide="heart" aria-hidden="true"></i>
                        <span>${escapeHtml(likeCount)}</span>
                    </button>
                </footer>
            </div>
        `;

        return article;
    };

    const appendMedia = (article, mediaItems) => {
        const items = Array.isArray(mediaItems) ? mediaItems : [];
        if (items.length === 0) {
            return;
        }

        const bodyEl = article.querySelector(".post-reply-body");
        const actions = article.querySelector(".post-reply-actions");
        if (!bodyEl || !actions) {
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

        items.forEach((item) => {
            const mediaUrl = item?.url || "";
            const mediaType = item?.type || "";
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
            bodyEl.insertBefore(gallery, actions);
        }
    };

    const showReplyOnCard = (card, reply) => {
        if (!card || !reply) {
            return;
        }

        const thread = getRepliesRoot(card);
        const article = buildReplyElement(reply, card);
        appendMedia(article, reply.media);
        thread.appendChild(article);

        if (typeof window.refreshLucideIcons === "function") {
            window.refreshLucideIcons();
        }

        card.querySelector(".hashtag-post-replies")?.scrollIntoView({ behavior: "smooth", block: "nearest" });
    };

    feed.addEventListener(
        "click",
        (event) => {
            const replyBtn = event.target.closest(".post-action-reply");
            if (!replyBtn) {
                return;
            }

            const card = replyBtn.closest(".post-card.post-card--linkable");
            if (card && feed.contains(card)) {
                activeCard = card;
            }
        },
        true
    );

    const nativeFetch = window.fetch.bind(window);

    window.fetch = async (input, init) => {
        const response = await nativeFetch(input, init);

        const requestUrl = typeof input === "string" ? input : String(input?.url || "");
        const method = String(init?.method || "GET").toUpperCase();
        const isReplyPost = method === "POST" && requestUrl.includes(replyUrl);

        if (!isReplyPost || !activeCard) {
            return response;
        }

        try {
            const data = await response.clone().json();
            if (response.ok && data.ok && data.reply) {
                showReplyOnCard(activeCard, data.reply);
            }
        } catch {
            // feed-reply-modal handles errors
        }

        return response;
    };
})();
