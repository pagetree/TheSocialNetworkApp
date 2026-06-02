(() => {
    const maxChars = 300;
    const warningAt = 50;

    const textarea = document.getElementById("post-composer-input");
    const counter = document.getElementById("post-char-counter-label");
    const progressCircle = document.querySelector(".post-char-counter-progress");
    const submitBtn = document.getElementById("post-composer-submit");
    const errorEl = document.getElementById("post-composer-error");
    const createUrl = window.APP_POST_CREATE_URL;
    const csrfToken = window.APP_POST_CSRF_TOKEN;

    if (!textarea || !counter || !progressCircle || !submitBtn) {
        return;
    }

    const radius = 15.5;
    const circumference = 2 * Math.PI * radius;
    progressCircle.style.strokeDasharray = String(circumference);

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

    const setSubmitLoading = (isLoading) => {
        submitBtn.disabled = isLoading || textarea.value.trim() === "";
        submitBtn.classList.toggle("is-loading", isLoading);
        submitBtn.setAttribute("aria-busy", isLoading ? "true" : "false");
        submitBtn.textContent = isLoading ? "Posting..." : "Post";
        textarea.disabled = isLoading;
    };

    const updateCounter = () => {
        const used = textarea.value.length;
        const remaining = maxChars - used;
        const progress = remaining / maxChars;
        const hasText = textarea.value.trim() !== "";

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
            submitBtn.disabled = !hasText || !createUrl || !csrfToken;
        }
    };

    textarea.addEventListener("input", () => {
        clearError();
        updateCounter();
    });

    submitBtn.addEventListener("click", async () => {
        if (!createUrl || !csrfToken) {
            return;
        }

        const body = textarea.value.trim();
        if (!body) {
            showError("Write something before posting.");
            return;
        }

        if (body.length > maxChars) {
            showError(`Post must be ${maxChars} characters or less.`);
            return;
        }

        clearError();
        setSubmitLoading(true);

        try {
            const response = await fetch(createUrl, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify({
                    body,
                    csrf_token: csrfToken,
                    _hp_url: "",
                }),
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
