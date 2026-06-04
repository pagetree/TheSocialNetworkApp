(() => {
    const form = document.getElementById("login-form");
    const errorEl = document.getElementById("login-form-error");
    const submitBtn = form?.querySelector(".auth-submit-btn");
    const loginUrl = window.APP_LOGIN_URL;
    const homeUrl = window.APP_HOME_URL;
    const csrfToken = window.APP_CSRF_TOKEN;

    if (!form || !errorEl || !submitBtn || !loginUrl || !csrfToken) {
        return;
    }

    const t = (key, replacements = {}) =>
        window.AppI18n?.t?.(key, replacements) ?? key;

    const showError = (message) => {
        errorEl.textContent = message;
        errorEl.hidden = false;
    };

    const clearError = () => {
        errorEl.textContent = "";
        errorEl.hidden = true;
    };

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        clearError();

        const formData = new FormData(form);
        const identifier = String(formData.get("identifier") ?? "").trim();
        const password = String(formData.get("password") ?? "");

        if (!identifier || !password) {
            showError(t("auth.errors.credentials_required"));
            return;
        }

        submitBtn.disabled = true;

        try {
            const response = await fetch(loginUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    identifier,
                    password,
                    csrf_token: csrfToken,
                    _hp_url: String(formData.get("_hp_url") ?? ""),
                }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.ok) {
                showError(data.error || t("auth.errors.sign_in_failed"));
                return;
            }

            window.location.href = homeUrl || "/";
        } catch {
            showError(t("auth.errors.sign_in_unavailable"));
        } finally {
            submitBtn.disabled = false;
        }
    });
})();
