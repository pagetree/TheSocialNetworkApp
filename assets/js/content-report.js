(() => {
    const reportUrl = window.APP_CONTENT_REPORT_URL;
    const csrfToken = window.APP_CONTENT_REPORT_CSRF_TOKEN;
    const detailsMaxLength = Number(window.APP_CONTENT_REPORT_DETAILS_MAX_LENGTH || 500);
    const overlay = document.getElementById("content-report-modal-overlay");
    const closeBtn = document.getElementById("content-report-modal-close");
    const cancelBtn = document.getElementById("content-report-cancel");
    const form = document.getElementById("content-report-modal-form");
    const titleEl = document.getElementById("content-report-modal-title");
    const subtitleEl = document.getElementById("content-report-modal-subtitle");
    const reasonSelect = document.getElementById("content-report-reason");
    const detailsInput = document.getElementById("content-report-details");
    const errorEl = document.getElementById("content-report-error");
    const successEl = document.getElementById("content-report-success");
    const submitBtn = document.getElementById("content-report-submit");

    if (
        !reportUrl
        || !csrfToken
        || !overlay
        || !closeBtn
        || !cancelBtn
        || !form
        || !titleEl
        || !subtitleEl
        || !reasonSelect
        || !detailsInput
        || !errorEl
        || !successEl
        || !submitBtn
    ) {
        return;
    }

    const t = (key, replacements = {}) =>
        window.AppI18n?.t?.(key, replacements) ?? key;

    let activeTarget = null;
    let isSubmitting = false;

    const setError = (message) => {
        if (!message) {
            errorEl.hidden = true;
            errorEl.textContent = "";
            return;
        }

        errorEl.textContent = message;
        errorEl.hidden = false;
        successEl.hidden = true;
        successEl.textContent = "";
    };

    const setSuccess = (message) => {
        successEl.textContent = message;
        successEl.hidden = false;
        errorEl.hidden = true;
        errorEl.textContent = "";
    };

    const selectedReason = () => reasonSelect.value.trim();

    const updateSubmitState = () => {
        const reason = selectedReason();
        const details = detailsInput.value.trim();
        const detailsRequired = reason === "other";
        const canSubmit = reason !== "" && (!detailsRequired || details !== "") && !isSubmitting;
        submitBtn.disabled = !canSubmit;
    };

    const resetForm = () => {
        reasonSelect.value = "";
        detailsInput.value = "";
        setError("");
        setSuccess("");
        submitBtn.disabled = true;
        submitBtn.classList.remove("is-loading");
        submitBtn.textContent = t("report.submit");
        isSubmitting = false;
    };

    const closeModal = () => {
        overlay.hidden = true;
        document.body.classList.remove("content-report-modal-open");
        activeTarget = null;
        resetForm();
    };

    const openModal = ({ targetType, targetId, subjectLabel = "" }) => {
        const normalizedType = String(targetType || "").toLowerCase();
        const normalizedId = Number(targetId || 0);

        if (!["post", "reply", "user"].includes(normalizedType) || normalizedId < 1) {
            return;
        }

        activeTarget = {
            targetType: normalizedType,
            targetId: normalizedId,
        };

        titleEl.textContent = t("report.title");
        const baseSubtitle = t(`report.subtitle.${normalizedType}`) || t("report.subtitle.default");
        subtitleEl.textContent = subjectLabel !== ""
            ? `${baseSubtitle} ${t("report.reporting", { subject: subjectLabel })}`
            : baseSubtitle;

        resetForm();
        overlay.hidden = false;
        document.body.classList.add("content-report-modal-open");
        window.lucide?.createIcons?.();

        reasonSelect.focus();
    };

    const submitReport = async () => {
        if (!activeTarget || isSubmitting) {
            return;
        }

        const reasonCode = selectedReason();
        const details = detailsInput.value.trim();

        if (reasonCode === "") {
            setError(t("report.errors.reason_required"));
            return;
        }

        if (reasonCode === "other" && details === "") {
            setError(t("report.errors.details_required"));
            return;
        }

        if (details.length > detailsMaxLength) {
            setError(t("report.errors.details_too_long", { max: detailsMaxLength }));
            return;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.classList.add("is-loading");
        submitBtn.textContent = t("report.submitting");
        setError("");

        try {
            const response = await fetch(reportUrl, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    "X-CSRF-Token": csrfToken,
                },
                body: JSON.stringify({
                    target_type: activeTarget.targetType,
                    target_id: activeTarget.targetId,
                    reason_code: reasonCode,
                    details,
                    csrf_token: csrfToken,
                }),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.ok) {
                throw new Error(data.error || t("api.report_failed"));
            }

            form.querySelectorAll("select, textarea, button").forEach((element) => {
                element.disabled = true;
            });
            submitBtn.classList.remove("is-loading");
            submitBtn.textContent = t("report.submitted");
            setSuccess(t("report.success"));

            window.setTimeout(() => {
                closeModal();
                form.querySelectorAll("select, textarea, button").forEach((element) => {
                    element.disabled = false;
                });
            }, 1400);
        } catch (error) {
            isSubmitting = false;
            submitBtn.classList.remove("is-loading");
            submitBtn.textContent = t("report.submit");
            updateSubmitState();
            setError(error instanceof Error ? error.message : t("api.report_failed"));
        }
    };

    window.openContentReportModal = openModal;

    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);

    overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
            closeModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !overlay.hidden) {
            closeModal();
        }
    });

    reasonSelect.addEventListener("change", updateSubmitState);
    detailsInput.addEventListener("input", updateSubmitState);

    form.addEventListener("submit", (event) => {
        event.preventDefault();
        submitReport();
    });
})();
