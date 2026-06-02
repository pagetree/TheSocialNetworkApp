(() => {
    const form = document.getElementById("register-form");
    const errorEl = document.getElementById("register-form-error");
    const submitBtn = form?.querySelector(".auth-submit-btn");
    const registerUrl = window.APP_REGISTER_URL;
    const homeUrl = window.APP_HOME_URL;

    if (!form || !errorEl || !submitBtn || !registerUrl || !homeUrl) {
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
        const payload = {
            first_name: String(formData.get("first_name") ?? "").trim(),
            last_name: String(formData.get("last_name") ?? "").trim(),
            username: String(formData.get("username") ?? "").trim(),
            email: String(formData.get("email") ?? "").trim(),
            password: String(formData.get("password") ?? ""),
            password_confirm: String(formData.get("password_confirm") ?? ""),
        };

        if (
            !payload.first_name ||
            !payload.last_name ||
            !payload.username ||
            !payload.email ||
            !payload.password ||
            !payload.password_confirm
        ) {
            showError("All fields are required.");
            return;
        }

        if (payload.password !== payload.password_confirm) {
            showError("Passwords do not match.");
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
                showError(data.error || "Unable to create account.");
                return;
            }

            window.location.href = homeUrl;
        } catch {
            showError("Unable to create account right now.");
        } finally {
            submitBtn.disabled = false;
        }
    });
})();
