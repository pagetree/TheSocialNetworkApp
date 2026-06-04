(() => {
    const t = (key, replacements = {}) =>
        window.AppI18n?.t?.(key, replacements) ?? key;

    const config = window.APP_ONBOARDING;
    if (!config?.urls?.steps) {
        return;
    }

    const stepOrder = ["welcome", "avatar", "bio", "interests", "suggestions"];
    const stepUrls = config.urls.steps;
    const errorEl = document.getElementById("onboarding-step-error");
    const skipButtons = document.querySelectorAll("[data-onboarding-skip]");

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

    const setBusy = (button, busy, loadingLabel = t("onboarding.saving")) => {
        if (!button) {
            return;
        }

        const spinner = button.querySelector(".onboarding-btn-spinner");
        const label = button.querySelector(".onboarding-btn-label");

        if (label && !label.dataset.defaultLabel) {
            label.dataset.defaultLabel = label.textContent.trim();
        }

        button.disabled = busy;
        button.classList.toggle("is-loading", busy);
        button.setAttribute("aria-busy", busy ? "true" : "false");

        if (spinner) {
            spinner.hidden = !busy;
        }

        if (label) {
            label.textContent = busy
                ? loadingLabel
                : label.dataset.defaultLabel || label.textContent;
        }
    };

    const nextStepUrl = () => {
        const currentIndex = stepOrder.indexOf(config.step);
        const nextStep = stepOrder[currentIndex + 1];
        return nextStep ? stepUrls[nextStep] : stepUrls.suggestions;
    };

    const goToNextStep = () => {
        const target = nextStepUrl();
        if (target) {
            window.location.href = target;
        }
    };

    const finishOnboarding = async () => {
        const response = await fetch(config.urls.complete, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ csrf_token: config.csrfToken }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) {
            throw new Error(data.error || t("api.finish_onboarding_failed"));
        }
        window.location.href = data.redirect_url || "/";
    };

    skipButtons.forEach((button) => {
        button.addEventListener("click", () => {
            clearError();
            if (config.step === "suggestions") {
                finishOnboarding().catch((error) => {
                    showError(error.message);
                });
                return;
            }
            if (config.step === "welcome") {
                window.location.href = stepUrls.avatar;
                return;
            }
            goToNextStep();
        });
    });

    const stepsProgressList = document.querySelector(".onboarding-inline-steps-list");

    const lockStepsProgressScroll = () => {
        if (!stepsProgressList) {
            return;
        }
        const scrollLeft = stepsProgressList.scrollLeft;
        requestAnimationFrame(() => {
            stepsProgressList.scrollLeft = scrollLeft;
        });
    };

    const centerCurrentStepInProgress = () => {
        if (!stepsProgressList) {
            return;
        }
        const currentItem = stepsProgressList.querySelector(".onboarding-inline-step.is-current");
        if (!currentItem) {
            return;
        }
        const listWidth = stepsProgressList.clientWidth;
        const itemLeft = currentItem.offsetLeft;
        const itemWidth = currentItem.offsetWidth;
        stepsProgressList.scrollLeft = Math.max(
            0,
            itemLeft - Math.max(0, (listWidth - itemWidth) / 2)
        );
    };

    centerCurrentStepInProgress();

    if (config.step === "avatar") {
        const previewWrap = document.querySelector(".onboarding-avatar-preview-wrap");
        const preview = document.getElementById("onboarding-avatar-preview");
        const placeholder = document.getElementById("onboarding-avatar-placeholder");
        const fileInput = document.getElementById("onboarding-avatar-input");
        const continueBtn = document.getElementById("onboarding-avatar-continue");

        const revokeAvatarObjectUrl = () => {
            if (avatarObjectUrl) {
                URL.revokeObjectURL(avatarObjectUrl);
                avatarObjectUrl = null;
            }
        };

        const setPreviewSrc = (src) => {
            if (!preview) {
                return;
            }
            if (src) {
                preview.src = src;
                preview.hidden = false;
                previewWrap?.classList.add("has-preview");
                if (placeholder) {
                    placeholder.hidden = true;
                    placeholder.setAttribute("aria-hidden", "true");
                }
                return;
            }
            preview.removeAttribute("src");
            preview.hidden = true;
            previewWrap?.classList.remove("has-preview");
            if (placeholder) {
                placeholder.hidden = false;
                placeholder.setAttribute("aria-hidden", "false");
            }
        };

        fileInput?.addEventListener("change", () => {
            clearError();
            const file = fileInput.files?.[0];
            if (!file) {
                return;
            }
            revokeAvatarObjectUrl();
            avatarObjectUrl = URL.createObjectURL(file);
            setPreviewSrc(avatarObjectUrl);
            lockStepsProgressScroll();
        });

        continueBtn?.addEventListener("click", async () => {
            clearError();

            try {
                const hasFile = Boolean(fileInput?.files?.[0]);
                if (!hasFile) {
                    goToNextStep();
                    return;
                }

                setBusy(continueBtn, true, t("onboarding.uploading"));
                const formData = new FormData();
                formData.append("csrf_token", config.csrfToken);
                formData.append("_hp_url", "");
                formData.append("avatar", fileInput.files[0]);

                const response = await fetch(config.urls.avatar, {
                    method: "POST",
                    body: formData,
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.ok) {
                    throw new Error(data.error || t("api.save_avatar_failed"));
                }

                goToNextStep();
            } catch (error) {
                showError(error.message || t("api.save_avatar_failed"));
                setBusy(continueBtn, false);
            }
        });
    }

    if (config.step === "bio") {
        const bioInput = document.getElementById("onboarding-bio-input");
        const bioCount = document.getElementById("onboarding-bio-count");
        const continueBtn = document.getElementById("onboarding-bio-continue");

        bioInput?.addEventListener("input", () => {
            if (bioCount) {
                bioCount.textContent = `${bioInput.value.length} / ${config.bioMaxLength}`;
            }
        });

        continueBtn?.addEventListener("click", async () => {
            clearError();

            try {
                const bio = bioInput?.value?.trim() ?? "";
                if (bio === "") {
                    goToNextStep();
                    return;
                }

                setBusy(continueBtn, true);
                const response = await fetch(config.urls.bio, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        csrf_token: config.csrfToken,
                        bio,
                    }),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.ok) {
                    throw new Error(data.error || t("api.save_bio_failed"));
                }

                goToNextStep();
            } catch (error) {
                showError(error.message || t("api.save_bio_failed"));
                setBusy(continueBtn, false);
            }
        });
    }

    if (config.step === "interests") {
        const continueBtn = document.getElementById("onboarding-interests-continue");
        const chips = document.querySelectorAll(".onboarding-interest-chip");
        const maxInterests = Number(config.maxInterests) || 10;

        chips.forEach((chip) => {
            const input = chip.querySelector('input[type="checkbox"]');
            input?.addEventListener("change", () => {
                const selectedCount = document.querySelectorAll(
                    '.onboarding-interest-chip input[type="checkbox"]:checked'
                ).length;
                if (selectedCount > maxInterests) {
                    input.checked = false;
                    showError(t("api.interests_max", { max: maxInterests }));
                    return;
                }
                clearError();
                chip.classList.toggle("is-selected", input.checked);
            });
        });

        continueBtn?.addEventListener("click", async () => {
            clearError();

            try {
                const selected = Array.from(
                    document.querySelectorAll(
                        '.onboarding-interest-chip input[type="checkbox"]:checked'
                    )
                ).map((input) => Number(input.value));

                if (selected.length === 0) {
                    goToNextStep();
                    return;
                }

                setBusy(continueBtn, true);
                const response = await fetch(config.urls.interests, {
                    method: "POST",
                    headers: {
                        Accept: "application/json",
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        csrf_token: config.csrfToken,
                        interest_ids: selected,
                    }),
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.ok) {
                    throw new Error(data.error || t("api.save_interests_failed"));
                }

                goToNextStep();
            } catch (error) {
                showError(error.message || t("api.save_interests_failed"));
                setBusy(continueBtn, false);
            }
        });
    }

    if (config.step === "suggestions") {
        const finishBtn = document.getElementById("onboarding-suggestions-finish");
        const followButtons = document.querySelectorAll(".onboarding-suggestion-follow");

        const setSuggestionFollowState = (button, following) => {
            button.classList.toggle("is-selected", following);
            button.setAttribute("aria-pressed", following ? "true" : "false");
        };

        followButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const following = !button.classList.contains("is-selected");
                setSuggestionFollowState(button, following);
            });
        });

        finishBtn?.addEventListener("click", async () => {
            clearError();
            setBusy(finishBtn, true, t("onboarding.finishing"));

            try {
                const followUserIds = [];
                const unfollowUserIds = [];

                followButtons.forEach((button) => {
                    const userId = Number(button.dataset.userId || 0);
                    if (userId < 1) {
                        return;
                    }

                    const followedOnLoad = button.dataset.followedOnLoad === "1";
                    const selected = button.classList.contains("is-selected");

                    if (selected && !followedOnLoad) {
                        followUserIds.push(userId);
                    } else if (!selected && followedOnLoad) {
                        unfollowUserIds.push(userId);
                    }
                });

                if (followUserIds.length > 0 || unfollowUserIds.length > 0) {
                    const followResponse = await fetch(config.urls.follow, {
                        method: "POST",
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            csrf_token: config.csrfToken,
                            user_ids: followUserIds,
                            unfollow_user_ids: unfollowUserIds,
                        }),
                    });
                    const followData = await followResponse.json().catch(() => ({}));
                    if (!followResponse.ok || !followData.ok) {
                        throw new Error(followData.error || t("api.update_follows_failed"));
                    }
                }

                await finishOnboarding();
            } catch (error) {
                showError(error.message || t("api.finish_onboarding_failed"));
                setBusy(finishBtn, false);
            }
        });
    }
})();
