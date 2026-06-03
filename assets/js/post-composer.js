(() => {
    const maxChars = 300;
    const warningAt = 50;
    const imageAccept = "image/avif,image/bmp,image/gif,image/heic,image/heif,image/jpeg,image/png,image/tiff,image/webp,image/x-icon,.avif,.bmp,.gif,.heic,.heif,.ico,.jpeg,.jpg,.png,.tif,.tiff,.webp";
    const videoAccept = "video/3gpp,video/3gpp2,video/mp4,video/mpeg,video/ogg,video/quicktime,video/webm,video/x-matroska,video/x-msvideo,.3gp,.3g2,.avi,.m4v,.mkv,.mov,.mp4,.mpeg,.mpg,.ogv,.webm";

    const textarea = document.getElementById("post-composer-input");
    const counter = document.getElementById("post-char-counter-label");
    const progressCircle = document.querySelector(".post-char-counter-progress");
    const submitBtn = document.getElementById("post-composer-submit");
    const errorEl = document.getElementById("post-composer-error");
    const mediaInput = document.getElementById("post-composer-media-input");
    const mediaPreview = document.getElementById("post-composer-media-preview");
    const mediaGrid = document.getElementById("post-composer-media-grid");
    const imageBtn = document.getElementById("post-composer-image-btn");
    const videoBtn = document.getElementById("post-composer-video-btn");
    const createUrl = window.APP_POST_CREATE_URL;
    const csrfToken = window.APP_POST_CSRF_TOKEN;
    const mediaLimits = window.APP_POST_MEDIA_LIMITS || {
        imageMaxBytes: 15728640,
        videoMaxBytes: 52428800,
        maxImages: 4,
        maxVideos: 1,
    };

    if (!textarea || !counter || !progressCircle || !submitBtn) {
        return;
    }

    const radius = 15.5;
    const circumference = 2 * Math.PI * radius;
    progressCircle.style.strokeDasharray = String(circumference);

    /** @type {Array<{ id: string, file: File, objectUrl: string }>} */
    let selectedMedia = [];
    let mediaPickerMode = "image";

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

    const isVideoFile = (file) => file.type.startsWith("video/");

    const maxBytesForFile = (file) => (
        isVideoFile(file) ? mediaLimits.videoMaxBytes : mediaLimits.imageMaxBytes
    );

    const limitLabelForFile = (file) => (isVideoFile(file) ? "50 MB" : "15 MB");

    const countMediaTypes = () => {
        let imageCount = 0;
        let videoCount = 0;

        selectedMedia.forEach(({ file }) => {
            if (isVideoFile(file)) {
                videoCount += 1;
            } else {
                imageCount += 1;
            }
        });

        return { imageCount, videoCount };
    };

    const canSubmit = () => {
        const hasText = textarea.value.trim() !== "";
        const hasMedia = selectedMedia.length > 0;

        return (hasText || hasMedia) && createUrl && csrfToken;
    };

    const setSubmitLoading = (isLoading) => {
        submitBtn.disabled = isLoading || !canSubmit();
        submitBtn.classList.toggle("is-loading", isLoading);
        submitBtn.setAttribute("aria-busy", isLoading ? "true" : "false");
        submitBtn.textContent = isLoading ? "Posting..." : "Post";
        textarea.disabled = isLoading;
        if (imageBtn) {
            imageBtn.disabled = isLoading;
        }
        if (videoBtn) {
            videoBtn.disabled = isLoading;
        }
    };

    const revokeMediaEntry = (entry) => {
        if (entry?.objectUrl) {
            URL.revokeObjectURL(entry.objectUrl);
        }
    };

    const clearMediaSelection = () => {
        selectedMedia.forEach(revokeMediaEntry);
        selectedMedia = [];
        if (mediaInput) {
            mediaInput.value = "";
        }
        if (mediaGrid) {
            mediaGrid.replaceChildren();
        }
        if (mediaPreview) {
            mediaPreview.hidden = true;
        }
    };

    const validateMediaFile = (file) => {
        if (!file) {
            return "Choose a media file to upload.";
        }

        const maxBytes = maxBytesForFile(file);
        if (file.size > maxBytes) {
            return `Media must be ${limitLabelForFile(file)} or smaller.`;
        }

        if (!file.type.startsWith("image/") && !file.type.startsWith("video/")) {
            return "Unsupported media type.";
        }

        return null;
    };

    const validateMediaSelection = (nextFiles) => {
        const draft = [...selectedMedia.map((entry) => entry.file), ...nextFiles];
        let imageCount = 0;
        let videoCount = 0;

        for (const file of draft) {
            const fileError = validateMediaFile(file);
            if (fileError) {
                return fileError;
            }

            if (isVideoFile(file)) {
                videoCount += 1;
            } else {
                imageCount += 1;
            }
        }

        if (videoCount > mediaLimits.maxVideos) {
            return `Posts can include at most ${mediaLimits.maxVideos} video.`;
        }

        if (imageCount > mediaLimits.maxImages) {
            return `Posts can include at most ${mediaLimits.maxImages} images.`;
        }

        if (videoCount > 0 && imageCount > 0) {
            return "Add either images or a video, not both.";
        }

        return null;
    };

    const renderMediaPreview = () => {
        if (!mediaGrid || !mediaPreview) {
            return;
        }

        mediaGrid.replaceChildren();

        selectedMedia.forEach((entry) => {
            const item = document.createElement("div");
            item.className = "post-composer-media-item";
            item.dataset.mediaId = entry.id;

            if (isVideoFile(entry.file)) {
                const video = document.createElement("video");
                video.className = "post-composer-media-video";
                video.src = entry.objectUrl;
                video.controls = true;
                video.preload = "metadata";
                video.playsInline = true;
                item.appendChild(video);
            } else {
                const img = document.createElement("img");
                img.className = "post-composer-media-image";
                img.src = entry.objectUrl;
                img.alt = "";
                item.appendChild(img);
            }

            const removeBtn = document.createElement("button");
            removeBtn.type = "button";
            removeBtn.className = "post-composer-media-remove";
            removeBtn.setAttribute("aria-label", "Remove media");
            removeBtn.innerHTML = '<i data-lucide="x" aria-hidden="true"></i>';
            removeBtn.addEventListener("click", () => {
                selectedMedia = selectedMedia.filter((candidate) => candidate.id !== entry.id);
                revokeMediaEntry(entry);
                renderMediaPreview();
                updateCounter();
            });
            item.appendChild(removeBtn);
            mediaGrid.appendChild(item);
        });

        mediaPreview.hidden = selectedMedia.length === 0;

        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }
    };

    const addMediaFiles = (files) => {
        const incoming = Array.from(files || []);
        if (incoming.length === 0) {
            return;
        }

        clearError();
        const selectionError = validateMediaSelection(incoming);
        if (selectionError) {
            showError(selectionError);
            return;
        }

        if (isVideoFile(incoming[0])) {
            clearMediaSelection();
        } else {
            selectedMedia = selectedMedia.filter((entry) => !isVideoFile(entry.file));
        }

        incoming.forEach((file) => {
            selectedMedia.push({
                id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                file,
                objectUrl: URL.createObjectURL(file),
            });
        });

        const { imageCount, videoCount } = countMediaTypes();
        if (videoCount > 0) {
            selectedMedia = [selectedMedia[selectedMedia.length - 1]];
        } else if (imageCount > mediaLimits.maxImages) {
            selectedMedia = selectedMedia.slice(0, mediaLimits.maxImages);
        }

        renderMediaPreview();
        updateCounter();
    };

    const updateCounter = () => {
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
            submitBtn.disabled = !canSubmit();
        }
    };

    textarea.addEventListener("input", () => {
        clearError();
        updateCounter();
    });

    imageBtn?.addEventListener("click", () => {
        if (!mediaInput) {
            return;
        }
        mediaPickerMode = "image";
        mediaInput.accept = imageAccept;
        mediaInput.multiple = true;
        mediaInput.click();
    });

    videoBtn?.addEventListener("click", () => {
        if (!mediaInput) {
            return;
        }
        mediaPickerMode = "video";
        mediaInput.accept = videoAccept;
        mediaInput.multiple = false;
        mediaInput.click();
    });

    mediaInput?.addEventListener("change", () => {
        const files = mediaInput.files;
        if (!files || files.length === 0) {
            return;
        }

        if (mediaPickerMode === "video") {
            addMediaFiles([files[0]]);
            return;
        }

        addMediaFiles(files);
    });

    submitBtn.addEventListener("click", async () => {
        if (!createUrl || !csrfToken) {
            return;
        }

        const body = textarea.value.trim();
        const hasMedia = selectedMedia.length > 0;

        if (!body && !hasMedia) {
            showError("Write something or add media before posting.");
            return;
        }

        if (body.length > maxChars) {
            showError(`Post must be ${maxChars} characters or less.`);
            return;
        }

        if (hasMedia) {
            const selectionError = validateMediaSelection([]);
            if (selectionError) {
                showError(selectionError);
                return;
            }
        }

        clearError();
        setSubmitLoading(true);

        const formData = new FormData();
        formData.append("body", body);
        formData.append("csrf_token", csrfToken);
        formData.append("_hp_url", "");
        selectedMedia.forEach(({ file }) => {
            formData.append("media[]", file);
        });

        try {
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
                showError(data.error || "Unable to create post.");
                return;
            }

            window.location.reload();
        } catch {
            showError("Unable to create post right now.");
        } finally {
            setSubmitLoading(false);
            updateCounter();
        }
    });

    updateCounter();
})();
