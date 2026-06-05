(() => {
    const form = document.getElementById("register-form");
    const errorEl = document.getElementById("register-form-error");
    const submitBtn = form?.querySelector(".auth-submit-btn");
    const usernameInput = document.getElementById("register-username");
    const usernameHint = document.getElementById("username-hint");
    const registerUrl = window.APP_REGISTER_URL;
    const checkUsernameUrl = window.APP_CHECK_USERNAME_URL;
    const homeUrl = window.APP_HOME_URL;
    const csrfToken = window.APP_CSRF_TOKEN;

    if (
        !form ||
        !errorEl ||
        !submitBtn ||
        !usernameInput ||
        !usernameHint ||
        !registerUrl ||
        !checkUsernameUrl ||
        !homeUrl ||
        !csrfToken
    ) {
        return;
    }

    const t = (key, replacements = {}) =>
        window.AppI18n?.t?.(key, replacements) ?? key;

    const USERNAME_DEBOUNCE_MS = 500;
    let usernameTimer = null;
    let usernameRequestId = 0;
    let usernameState = {
        value: "",
        valid: false,
        available: false,
        checking: false,
    };

    const showError = (message) => {
        errorEl.textContent = message;
        errorEl.hidden = false;
    };

    const clearError = () => {
        errorEl.textContent = "";
        errorEl.hidden = true;
    };

    const USERNAME_MIN_LENGTH = 3;
    const USERNAME_MAX_LENGTH = 50;
    const USERNAME_ALLOWED_CHARS = /[^a-z0-9._-]/g;
    const USERNAME_FORMAT = /^[a-z0-9](?:[a-z0-9]*(?:[._-][a-z0-9]+)*)?$/;

    const prepareUsernameInput = (value) =>
        value.trim().replace(/^@+/, "").toLowerCase();

    const validateUsernameFormat = (value) => {
        if (value === "") {
            return { valid: false, errorKey: "required" };
        }

        if (value.length < USERNAME_MIN_LENGTH) {
            return { valid: false, errorKey: "min_length" };
        }

        if (value.length > USERNAME_MAX_LENGTH) {
            return { valid: false, errorKey: "too_long" };
        }

        if (!/^[a-z0-9._-]+$/.test(value)) {
            return { valid: false, errorKey: "invalid" };
        }

        if (value.includes("..") || value.includes("--") || value.includes("__")) {
            return { valid: false, errorKey: "invalid" };
        }

        if (!USERNAME_FORMAT.test(value)) {
            return { valid: false, errorKey: "invalid" };
        }

        return { valid: true, errorKey: null };
    };

    const usernameFormatMessage = (errorKey) => {
        if (errorKey === "required") {
            return t("auth.username_status.required");
        }
        if (errorKey === "min_length") {
            return t("auth.username_status.min_length");
        }
        if (errorKey === "too_long") {
            return t("auth.username_status.too_long");
        }

        return t("auth.username_status.invalid");
    };

    const setUsernameHint = (message, state) => {
        usernameHint.textContent = message;
        usernameHint.hidden = message === "";
        usernameHint.classList.remove(
            "is-available",
            "is-unavailable",
            "is-warning",
            "is-checking"
        );
        if (state) {
            usernameHint.classList.add(state);
        }
    };

    const updateSubmitState = () => {
        const usernameReady =
            usernameState.valid && usernameState.available && !usernameState.checking;
        submitBtn.disabled = !usernameReady;
    };

    const checkUsername = async (rawValue) => {
        const requestId = ++usernameRequestId;
        const value = prepareUsernameInput(rawValue);

        if (value === "") {
            usernameState = {
                value: "",
                valid: false,
                available: false,
                checking: false,
            };
            setUsernameHint("");
            updateSubmitState();
            return;
        }

        const formatCheck = validateUsernameFormat(value);
        if (!formatCheck.valid) {
            usernameState = {
                value,
                valid: false,
                available: false,
                checking: false,
            };
            setUsernameHint(usernameFormatMessage(formatCheck.errorKey), "is-warning");
            updateSubmitState();
            return;
        }

        usernameState = {
            value,
            valid: false,
            available: false,
            checking: true,
        };
        setUsernameHint(t("auth.username_status.checking"), "is-checking");
        updateSubmitState();

        try {
            const response = await fetch(
                `${checkUsernameUrl}?username=${encodeURIComponent(value)}`,
                {
                    headers: { Accept: "application/json" },
                }
            );
            const data = await response.json().catch(() => ({}));

            if (requestId !== usernameRequestId) {
                return;
            }

            if (!response.ok || !data.ok) {
                usernameState = {
                    value,
                    valid: false,
                    available: false,
                    checking: false,
                };
                setUsernameHint(t("auth.username_status.check_failed"), "is-warning");
                updateSubmitState();
                return;
            }

            if (data.valid && data.available) {
                usernameState = {
                    value: data.username || value,
                    valid: true,
                    available: true,
                    checking: false,
                };
                setUsernameHint(
                    t("auth.username_status.available_handle", {
                        username: data.username || value,
                    }),
                    "is-available"
                );
            } else if (data.valid && !data.available) {
                usernameState = {
                    value: data.username || value,
                    valid: true,
                    available: false,
                    checking: false,
                };
                setUsernameHint(data.error || t("auth.username_status.taken"), "is-unavailable");
            } else {
                usernameState = {
                    value: data.username || value,
                    valid: false,
                    available: false,
                    checking: false,
                };
                setUsernameHint(data.error || t("auth.username_status.invalid"), "is-warning");
            }
        } catch {
            if (requestId !== usernameRequestId) {
                return;
            }

            usernameState = {
                value,
                valid: false,
                available: false,
                checking: false,
            };
            setUsernameHint(t("auth.username_status.check_failed"), "is-warning");
        }

        updateSubmitState();
    };

    const scheduleUsernameCheck = (value) => {
        window.clearTimeout(usernameTimer);
        usernameTimer = window.setTimeout(() => {
            checkUsername(value);
        }, USERNAME_DEBOUNCE_MS);
    };

    usernameInput.addEventListener("input", () => {
        const cleaned = prepareUsernameInput(usernameInput.value).replace(
            USERNAME_ALLOWED_CHARS,
            ""
        );
        if (usernameInput.value !== cleaned) {
            usernameInput.value = cleaned;
        }

        window.clearTimeout(usernameTimer);
        usernameState = {
            value: cleaned,
            valid: false,
            available: false,
            checking: false,
        };

        if (cleaned === "") {
            setUsernameHint("");
            updateSubmitState();
            return;
        }

        const formatCheck = validateUsernameFormat(cleaned);
        if (!formatCheck.valid) {
            setUsernameHint(usernameFormatMessage(formatCheck.errorKey), "is-warning");
            updateSubmitState();
            return;
        }

        setUsernameHint("", "");
        updateSubmitState();
        scheduleUsernameCheck(cleaned);
    });

    usernameInput.addEventListener("blur", () => {
        const cleaned = prepareUsernameInput(usernameInput.value).replace(
            USERNAME_ALLOWED_CHARS,
            ""
        );
        if (cleaned === "") {
            return;
        }

        const formatCheck = validateUsernameFormat(cleaned);
        if (!formatCheck.valid) {
            usernameState = {
                value: cleaned,
                valid: false,
                available: false,
                checking: false,
            };
            setUsernameHint(usernameFormatMessage(formatCheck.errorKey), "is-warning");
            updateSubmitState();
            return;
        }

        window.clearTimeout(usernameTimer);
        checkUsername(cleaned);
    });

    updateSubmitState();

    const passwordInput = document.getElementById("register-password");
    const passwordToggle = document.getElementById("register-password-toggle");

    if (passwordInput && passwordToggle) {
        passwordToggle.addEventListener("click", () => {
            const isHidden = passwordInput.type === "password";
            passwordInput.type = isHidden ? "text" : "password";
            passwordToggle.setAttribute("aria-pressed", String(isHidden));
            passwordToggle.setAttribute(
                "aria-label",
                isHidden ? t("auth.password_toggle.hide") : t("auth.password_toggle.show")
            );

            const icon = passwordToggle.querySelector("[data-lucide]");
            if (icon) {
                icon.setAttribute("data-lucide", isHidden ? "eye-off" : "eye");
                if (window.lucide) {
                    window.lucide.createIcons({ root: passwordToggle });
                }
            }
        });
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearError();

        const formData = new FormData(form);
        const payload = {
            first_name: String(formData.get("first_name") ?? "").trim(),
            last_name: String(formData.get("last_name") ?? "").trim(),
            username: prepareUsernameInput(String(formData.get("username") ?? "")).replace(
                USERNAME_ALLOWED_CHARS,
                ""
            ),
            email: String(formData.get("email") ?? "").trim(),
            password: String(formData.get("password") ?? ""),
            csrf_token: csrfToken,
            _hp_url: String(formData.get("_hp_url") ?? ""),
        };

        if (
            !payload.first_name ||
            !payload.last_name ||
            !payload.username ||
            !payload.email ||
            !payload.password
        ) {
            showError(t("auth.errors.all_fields_required"));
            return;
        }

        if (!usernameState.valid || !usernameState.available) {
            showError(usernameHint.textContent || t("auth.errors.choose_username"));
            return;
        }

        submitBtn.disabled = true;

        try {
            const response = await fetch(registerUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.ok) {
                showError(data.error || t("auth.errors.register_failed"));
                updateSubmitState();
                return;
            }

            window.location.href = homeUrl;
        } catch {
            showError(t("auth.errors.register_unavailable"));
            updateSubmitState();
        }
    });
})();
