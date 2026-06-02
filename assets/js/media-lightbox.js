(() => {
    const lightbox = document.getElementById("media-lightbox");
    const lightboxImage = document.getElementById("media-lightbox-image");
    const lightboxStage = document.getElementById("media-lightbox-stage");
    const closeBtn = document.getElementById("media-lightbox-close");

    if (!lightbox || !lightboxImage || !lightboxStage || !closeBtn) {
        return;
    }

    const isViewablePostImage = (target) => {
        if (!(target instanceof HTMLImageElement)) {
            return false;
        }

        return target.classList.contains("post-media")
            && target.closest(".post-media-gallery") !== null;
    };

    const refreshIcons = () => {
        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }
    };

    const openLightbox = (imageSrc) => {
        if (!imageSrc) {
            return;
        }

        lightboxImage.src = imageSrc;
        lightbox.hidden = false;
        document.body.classList.add("media-lightbox-open");
        closeBtn.focus();
        refreshIcons();
    };

    const closeLightbox = () => {
        lightbox.hidden = true;
        lightboxImage.removeAttribute("src");
        document.body.classList.remove("media-lightbox-open");
    };

    document.addEventListener("click", (event) => {
        const target = event.target;
        if (!isViewablePostImage(target)) {
            return;
        }

        event.preventDefault();
        openLightbox(target.currentSrc || target.src);
    });

    closeBtn.addEventListener("click", (event) => {
        event.stopPropagation();
        closeLightbox();
    });

    lightbox.addEventListener("click", (event) => {
        if (event.target === lightbox || event.target === lightboxStage) {
            closeLightbox();
        }
    });

    lightboxImage.addEventListener("click", (event) => {
        event.stopPropagation();
    });

    document.addEventListener("keydown", (event) => {
        const target = event.target;

        if (
            (event.key === "Enter" || event.key === " ")
            && isViewablePostImage(target)
        ) {
            event.preventDefault();
            openLightbox(target.currentSrc || target.src);
            return;
        }

        if (event.key === "Escape" && !lightbox.hidden) {
            closeLightbox();
        }
    });
})();
