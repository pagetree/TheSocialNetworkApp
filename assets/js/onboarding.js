(() => {
    const config = window.APP_ONBOARDING;
    if (!config?.urls?.steps) {
        return;
    }

    const stepOrder = ["welcome", "avatar", "bio", "interests", "suggestions"];
    const stepUrls = config.urls.steps;
    const errorEl = document.getElementById("onboarding-step-error");
    const skipButtons = document.querySelectorAll("[data-onboarding-skip]");

    let selectedPresetUrl = "";
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

    const setBusy = (button, busy) => {
        if (!button) {
            return;
        }
        button.disabled = busy;
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
            throw new Error(data.error || "Unable to finish onboarding.");
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
        const preview = document.getElementById("onboarding-avatar-preview");
        const fileInput = document.getElementById("onboarding-avatar-input");
        const continueBtn = document.getElementById("onboarding-avatar-continue");
        const presetButtons = document.querySelectorAll(".onboarding-avatar-preset");
        const preselectedPreset = document.querySelector(".onboarding-avatar-preset.is-selected");

        if (preselectedPreset?.dataset.presetUrl) {
            selectedPresetUrl = preselectedPreset.dataset.presetUrl;
        }

        const revokeAvatarObjectUrl = () => {
            if (avatarObjectUrl) {
                URL.revokeObjectURL(avatarObjectUrl);
                avatarObjectUrl = null;
            }
        };

        const setPreviewSrc = (src) => {
            if (preview) {
                preview.src = src;
            }
        };

        presetButtons.forEach((button) => {
            button.addEventListener("mousedown", (event) => {
                event.preventDefault();
            });
            button.addEventListener("click", (event) => {
                event.preventDefault();
                clearError();
                revokeAvatarObjectUrl();
                if (fileInput) {
                    fileInput.value = "";
                }
                selectedPresetUrl = button.dataset.presetUrl || "";
                presetButtons.forEach((item) => item.classList.remove("is-selected"));
                button.classList.add("is-selected");
                setPreviewSrc(selectedPresetUrl);
                lockStepsProgressScroll();
            });
        });

        fileInput?.addEventListener("change", () => {
            clearError();
            const file = fileInput.files?.[0];
            if (!file) {
                return;
            }
            selectedPresetUrl = "";
            presetButtons.forEach((item) => item.classList.remove("is-selected"));
            revokeAvatarObjectUrl();
            avatarObjectUrl = URL.createObjectURL(file);
            setPreviewSrc(avatarObjectUrl);
            lockStepsProgressScroll();
        });

        continueBtn?.addEventListener("click", async () => {
            clearError();
            setBusy(continueBtn, true);

            try {
                const hasFile = Boolean(fileInput?.files?.[0]);
                if (!hasFile && !selectedPresetUrl) {
                    goToNextStep();
                    return;
                }

                const formData = new FormData();
                formData.append("csrf_token", config.csrfToken);
                formData.append("_hp_url", "");

                if (hasFile) {
                    formData.append("avatar", fileInput.files[0]);
                } else {
                    formData.append("preset_avatar_url", selectedPresetUrl);
                }

                const response = await fetch(config.urls.avatar, {
                    method: "POST",
                    body: formData,
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.ok) {
                    throw new Error(data.error || "Unable to save profile photo.");
                }

                goToNextStep();
            } catch (error) {
                showError(error.message || "Unable to save profile photo.");
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
            setBusy(continueBtn, true);

            try {
                const bio = bioInput?.value?.trim() ?? "";
                if (bio === "") {
                    goToNextStep();
                    return;
                }

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
                    throw new Error(data.error || "Unable to save bio.");
                }

                goToNextStep();
            } catch (error) {
                showError(error.message || "Unable to save bio.");
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
                    showError(`Choose up to ${maxInterests} interests.`);
                    return;
                }
                clearError();
                chip.classList.toggle("is-selected", input.checked);
            });
        });

        continueBtn?.addEventListener("click", async () => {
            clearError();
            setBusy(continueBtn, true);

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
                    throw new Error(data.error || "Unable to save interests.");
                }

                goToNextStep();
            } catch (error) {
                showError(error.message || "Unable to save interests.");
                setBusy(continueBtn, false);
            }
        });
    }

    if (config.step === "suggestions") {
        const finishBtn = document.getElementById("onboarding-suggestions-finish");

        finishBtn?.addEventListener("click", async () => {
            clearError();
            setBusy(finishBtn, true);

            try {
                const selected = Array.from(
                    document.querySelectorAll(
                        '.onboarding-suggestion-follow input[type="checkbox"]:checked:not(:disabled)'
                    )
                ).map((input) => Number(input.value));

                if (selected.length > 0) {
                    const followResponse = await fetch(config.urls.follow, {
                        method: "POST",
                        headers: {
                            Accept: "application/json",
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            csrf_token: config.csrfToken,
                            user_ids: selected,
                        }),
                    });
                    const followData = await followResponse.json().catch(() => ({}));
                    if (!followResponse.ok || !followData.ok) {
                        throw new Error(followData.error || "Unable to follow accounts.");
                    }
                }

                await finishOnboarding();
            } catch (error) {
                showError(error.message || "Unable to finish onboarding.");
                setBusy(finishBtn, false);
            }
        });
    }
})();
