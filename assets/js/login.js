(() => {
    const form = document.getElementById("login-form");
    const errorEl = document.getElementById("login-form-error");
    const submitBtn = form?.querySelector(".auth-submit-btn");
    const loginUrl = window.APP_LOGIN_URL;
    const csrfToken = window.APP_CSRF_TOKEN;

    if (!form || !errorEl || !submitBtn || !loginUrl || !csrfToken) {
        return;
    }

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
            showError("Email or username and password are required.");
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
                showError(data.error || "Unable to sign in.");
                return;
            }

            window.location.reload();
        } catch {
            showError("Unable to sign in right now.");
        } finally {
            submitBtn.disabled = false;
        }
    });
})();
