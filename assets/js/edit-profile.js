(() => {
    const overlay = document.getElementById("profile-edit-overlay");
    const form = document.getElementById("profile-edit-form");
    const openBtn = document.getElementById("profile-edit-open");
    const closeBtn = document.getElementById("profile-edit-close");
    const cancelBtn = document.getElementById("profile-edit-cancel");
    const saveBtn = document.getElementById("profile-edit-save");
    const saveLabel = saveBtn?.querySelector(".profile-edit-save-label");
    const saveSpinner = saveBtn?.querySelector(".profile-edit-save-spinner");
    const errorEl = document.getElementById("profile-edit-form-error");
    const updateUrl = window.APP_PROFILE_UPDATE_URL;
    const csrfToken = window.APP_PROFILE_CSRF_TOKEN;

    const coverInput = document.getElementById("profile-edit-cover-input");
    const avatarInput = document.getElementById("profile-edit-avatar-input");
    const coverPreview = document.getElementById("profile-edit-cover-preview");
    const avatarPreview = document.getElementById("profile-edit-avatar-preview");

    const displayCover = document.getElementById("profile-display-cover");
    const displayAvatar = document.getElementById("profile-display-avatar");
    const displayName = document.getElementById("profile-display-name");
    const displayBio = document.getElementById("profile-display-bio");
    const displayLocation = document.getElementById("profile-display-location");
    const displayLocationWrap = document.getElementById("profile-display-location-wrap");
    const displayWebsite = document.getElementById("profile-display-website");
    const displayWebsiteLink = document.getElementById("profile-display-website-link");
    const displayDob = document.getElementById("profile-display-dob");
    const displayDobText = document.getElementById("profile-display-dob-text");

    const fieldDisplayName = document.getElementById("profile-edit-display-name");
    const fieldBio = document.getElementById("profile-edit-bio");
    const fieldLocation = document.getElementById("profile-edit-location");
    const fieldWebsite = document.getElementById("profile-edit-website");
    const fieldDob = document.getElementById("profile-edit-dob");
    const fieldIsVisible = document.getElementById("profile-edit-is-visible");
    const displayIsVisible = document.getElementById("profile-display-is-visible");

    if (!overlay || !form || !openBtn || !updateUrl || !csrfToken) {
        return;
    }

    const bioMaxLength = 300;
    let coverObjectUrl = null;
    let avatarObjectUrl = null;

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

    const revokeObjectUrl = (url) => {
        if (url) {
            URL.revokeObjectURL(url);
        }
    };

    const syncFormFromDisplay = () => {
        if (coverPreview && displayCover) {
            coverPreview.src = displayCover.src;
        }
        if (avatarPreview && displayAvatar) {
            avatarPreview.src = displayAvatar.src;
        }
        if (fieldDisplayName && displayName) {
            fieldDisplayName.value = displayName.textContent.trim();
        }
        if (fieldBio && displayBio) {
            fieldBio.value = displayBio.textContent.trim();
        }
        if (fieldLocation && displayLocation) {
            fieldLocation.value = displayLocation.textContent.trim();
        }
        if (fieldWebsite && displayWebsiteLink) {
            const href = displayWebsiteLink.getAttribute("href") || "";
            fieldWebsite.value = href === "#" ? "" : href.replace(/^https?:\/\//i, "");
        }
        if (fieldDob && displayDob) {
            fieldDob.value = displayDob.dataset.iso || "";
        }
        if (fieldIsVisible && displayIsVisible) {
            fieldIsVisible.checked = displayIsVisible.value === "1";
        }
        if (coverInput) {
            coverInput.value = "";
        }
        if (avatarInput) {
            avatarInput.value = "";
        }
    };

    const bindImagePreview = (input, preview, slot) => {
        if (!input || !preview) {
            return;
        }

        input.addEventListener("change", () => {
            const file = input.files?.[0];
            if (!file) {
                return;
            }

            if (slot === "cover") {
                revokeObjectUrl(coverObjectUrl);
                coverObjectUrl = URL.createObjectURL(file);
                preview.src = coverObjectUrl;
                return;
            }

            revokeObjectUrl(avatarObjectUrl);
            avatarObjectUrl = URL.createObjectURL(file);
            preview.src = avatarObjectUrl;
        });
    };

    const applyUserProfile = (user) => {
        if (!user) {
            return;
        }

        if (displayCover && user.cover_url) {
            displayCover.src = user.cover_url;
        }
        if (displayAvatar && user.avatar_url) {
            displayAvatar.src = user.avatar_url;
        }
        if (displayName) {
            displayName.textContent = user.display_name ?? "";
        }
        if (displayBio) {
            displayBio.textContent = user.bio ?? "";
        }
        if (displayLocation) {
            displayLocation.textContent = user.location ?? "";
        }
        if (displayLocationWrap) {
            displayLocationWrap.hidden = !(user.location ?? "");
        }

        if (displayWebsite && displayWebsiteLink) {
            if (user.website_url) {
                displayWebsite.hidden = false;
                displayWebsiteLink.href = user.website_url;
                displayWebsiteLink.textContent = user.website_label || user.website_url;
            } else {
                displayWebsite.hidden = true;
                displayWebsiteLink.href = "#";
                displayWebsiteLink.textContent = "";
            }
        }

        if (displayDob && displayDobText) {
            if (user.date_of_birth) {
                displayDob.hidden = false;
                displayDob.dataset.iso = user.date_of_birth;
                displayDobText.textContent = user.date_of_birth_label || "";
            } else {
                displayDob.hidden = true;
                displayDobText.textContent = "";
                delete displayDob.dataset.iso;
            }
        }

        const sidebarName = document.getElementById("profile-sidebar-name");
        const sidebarAvatar = document.getElementById("profile-sidebar-avatar");

        if (sidebarName) {
            sidebarName.textContent = user.display_name ?? "";
        }
        if (sidebarAvatar && user.avatar_url) {
            sidebarAvatar.src = user.avatar_url;
        }

        document.querySelectorAll(".profile-feed .post-avatar").forEach((img) => {
            if (user.avatar_url) {
                img.src = user.avatar_url;
            }
            img.alt = `${user.display_name ?? "User"} avatar`;
        });
        document.querySelectorAll(".profile-feed .post-author").forEach((el) => {
            el.textContent = user.display_name ?? "";
        });
    };

    const setSaveLoading = (isLoading) => {
        if (!saveBtn) {
            return;
        }

        saveBtn.disabled = isLoading;
        saveBtn.classList.toggle("is-loading", isLoading);
        saveBtn.setAttribute("aria-busy", isLoading ? "true" : "false");

        if (saveLabel) {
            saveLabel.textContent = isLoading ? "Saving..." : "Save";
        }

        if (saveSpinner) {
            saveSpinner.hidden = !isLoading;
        }

        if (cancelBtn) {
            cancelBtn.disabled = isLoading;
        }

        if (closeBtn) {
            closeBtn.disabled = isLoading;
        }
    };

    const openModal = () => {
        syncFormFromDisplay();
        clearError();
        overlay.hidden = false;
        document.body.classList.add("profile-edit-open");
        if (typeof lucide !== "undefined") {
            lucide.createIcons();
        }
        fieldDisplayName?.focus();
    };

    const closeModal = () => {
        overlay.hidden = true;
        document.body.classList.remove("profile-edit-open");
        clearError();
        revokeObjectUrl(coverObjectUrl);
        revokeObjectUrl(avatarObjectUrl);
        coverObjectUrl = null;
        avatarObjectUrl = null;
    };

    openBtn.addEventListener("click", openModal);
    closeBtn?.addEventListener("click", closeModal);
    cancelBtn?.addEventListener("click", closeModal);

    overlay.hidden = true;

    bindImagePreview(coverInput, coverPreview, "cover");
    bindImagePreview(avatarInput, avatarPreview, "avatar");

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearError();

        const name = fieldDisplayName?.value.trim() ?? "";
        const bio = fieldBio?.value.trim() ?? "";

        if (!name) {
            showError("Name is required.");
            return;
        }

        if (bio.length > bioMaxLength) {
            showError(`Bio must be ${bioMaxLength} characters or less.`);
            return;
        }

        setSaveLoading(true);

        const formData = new FormData(form);
        formData.set("csrf_token", csrfToken);

        try {
            const response = await fetch(updateUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: formData,
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.ok) {
                showError(data.error || "Unable to save profile.");
                return;
            }

            closeModal();
            window.location.reload();
        } catch {
            showError("Unable to save profile right now.");
        } finally {
            setSaveLoading(false);
        }
    });
})();
