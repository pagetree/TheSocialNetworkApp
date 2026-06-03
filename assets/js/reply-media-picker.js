(() => {
    const imageAccept = "image/avif,image/bmp,image/gif,image/heic,image/heif,image/jpeg,image/png,image/svg+xml,image/tiff,image/webp,image/x-icon,.avif,.bmp,.gif,.heic,.heif,.ico,.jpeg,.jpg,.png,.svg,.tif,.tiff,.webp";
    const videoAccept = "video/3gpp,video/3gpp2,video/mp4,video/mpeg,video/ogg,video/quicktime,video/webm,video/x-matroska,video/x-msvideo,.3gp,.3g2,.avi,.m4v,.mkv,.mov,.mp4,.mpeg,.mpg,.ogv,.webm";

    const defaultLimits = {
        imageMaxBytes: 15728640,
        videoMaxBytes: 52428800,
        maxImages: 4,
        maxVideos: 1,
    };

    /**
     * @param {{
     *   prefix: string,
     *   textarea: HTMLTextAreaElement,
     *   submitBtn: HTMLButtonElement,
     *   errorEl?: HTMLElement|null,
     *   imageBtn?: HTMLButtonElement|null,
     *   videoBtn?: HTMLButtonElement|null,
     *   onChange?: () => void,
     * }} config
     */
    window.createReplyMediaPicker = (config) => {
        const mediaLimits = window.APP_POST_MEDIA_LIMITS || defaultLimits;
        const mediaInput = document.getElementById(`${config.prefix}-media-input`);
        const mediaPreview = document.getElementById(`${config.prefix}-media-preview`);
        const mediaGrid = document.getElementById(`${config.prefix}-media-grid`);
        const imageBtn = config.imageBtn || document.getElementById(`${config.prefix}-image-btn`);
        const videoBtn = config.videoBtn || document.getElementById(`${config.prefix}-video-btn`);

        /** @type {Array<{ id: string, file: File, objectUrl: string }>} */
        let selectedMedia = [];
        let mediaPickerMode = "image";

        const isVideoFile = (file) => file.type.startsWith("video/");

        const maxBytesForFile = (file) => (
            isVideoFile(file) ? mediaLimits.videoMaxBytes : mediaLimits.imageMaxBytes
        );

        const limitLabelForFile = (file) => (isVideoFile(file) ? "50 MB" : "15 MB");

        const revokeMediaEntry = (entry) => {
            if (entry?.objectUrl) {
                URL.revokeObjectURL(entry.objectUrl);
            }
        };

        const clear = () => {
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
            config.onChange?.();
        };

        const validateMediaFile = (file) => {
            if (!file) {
                return "Choose a media file to upload.";
            }

            if (file.size > maxBytesForFile(file)) {
                return `Media must be ${limitLabelForFile(file)} or smaller.`;
            }

            if (!file.type.startsWith("image/") && !file.type.startsWith("video/")) {
                return "Unsupported media type.";
            }

            return null;
        };

        const validateMediaSelection = (nextFiles) => {
            const draft = [...selectedMedia.map((entry) => entry.file), ...nextFiles];

            for (const file of draft) {
                const fileError = validateMediaFile(file);
                if (fileError) {
                    return fileError;
                }
            }

            let imageCount = 0;
            let videoCount = 0;

            for (const file of draft) {
                if (isVideoFile(file)) {
                    videoCount += 1;
                } else {
                    imageCount += 1;
                }
            }

            if (videoCount > mediaLimits.maxVideos) {
                return `Replies can include at most ${mediaLimits.maxVideos} video.`;
            }

            if (imageCount > mediaLimits.maxImages) {
                return `Replies can include at most ${mediaLimits.maxImages} images.`;
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
                    config.onChange?.();
                });
                item.appendChild(removeBtn);
                mediaGrid.appendChild(item);
            });

            mediaPreview.hidden = selectedMedia.length === 0;

            if (window.lucide && typeof window.lucide.createIcons === "function") {
                window.lucide.createIcons();
            }

            config.onChange?.();
        };

        const addMediaFiles = (files) => {
            const incoming = Array.from(files || []);
            if (incoming.length === 0) {
                return null;
            }

            const selectionError = validateMediaSelection(incoming);
            if (selectionError) {
                return selectionError;
            }

            if (isVideoFile(incoming[0])) {
                clear();
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

            const imageCount = selectedMedia.filter((entry) => !isVideoFile(entry.file)).length;
            const videoCount = selectedMedia.filter((entry) => isVideoFile(entry.file)).length;

            if (videoCount > 0) {
                selectedMedia = [selectedMedia[selectedMedia.length - 1]];
            } else if (imageCount > mediaLimits.maxImages) {
                selectedMedia = selectedMedia.slice(0, mediaLimits.maxImages);
            }

            renderMediaPreview();

            return null;
        };

        const canSubmit = (text) => text.trim() !== "" || selectedMedia.length > 0;

        const appendToFormData = (formData) => {
            selectedMedia.forEach(({ file }) => {
                formData.append("media[]", file);
            });
        };

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

            const pickerError = addMediaFiles(mediaPickerMode === "video" ? [files[0]] : files);
            if (pickerError && config.errorEl) {
                config.errorEl.textContent = pickerError;
                config.errorEl.hidden = false;
            }
        });

        return {
            clear,
            canSubmit,
            appendToFormData,
            hasMedia: () => selectedMedia.length > 0,
            validateBeforeSubmit: () => validateMediaSelection([]),
        };
    };
})();
