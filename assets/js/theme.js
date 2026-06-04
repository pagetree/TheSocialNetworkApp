(() => {
    const COOKIE_NAME = "app_theme";
    const COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

    const getTheme = () =>
        document.documentElement.getAttribute("data-theme") === "light" ? "light" : "dark";

    const applyTheme = (theme) => {
        const resolved = theme === "light" ? "light" : "dark";
        document.documentElement.setAttribute("data-theme", resolved);
        document.documentElement.style.colorScheme = resolved;
        return resolved;
    };

    const writeThemeCookie = (theme) => {
        let cookie = `${COOKIE_NAME}=${theme};path=/;max-age=${COOKIE_MAX_AGE};SameSite=Lax`;
        if (window.location.protocol === "https:") {
            cookie += ";Secure";
        }
        document.cookie = cookie;
    };

    const t = (key, replacements = {}) =>
        window.AppI18n?.t?.(key, replacements) ?? key;

    const updateToggle = (button, theme) => {
        const isLight = theme === "light";
        button.setAttribute("aria-pressed", isLight ? "true" : "false");
        button.setAttribute(
            "aria-label",
            isLight ? t("theme.switch_to_dark") : t("theme.switch_to_light")
        );
    };

    const init = () => {
        const buttons = document.querySelectorAll(".theme-toggle");
        if (buttons.length === 0) {
            return;
        }

        const syncToggles = (theme) => {
            buttons.forEach((button) => {
                updateToggle(button, theme);
            });
        };

        const theme = applyTheme(getTheme());
        syncToggles(theme);

        buttons.forEach((button) => {
            button.addEventListener("click", () => {
                const nextTheme = getTheme() === "dark" ? "light" : "dark";
                applyTheme(nextTheme);
                writeThemeCookie(nextTheme);
                syncToggles(nextTheme);

                if (typeof window.refreshLucideIcons === "function") {
                    window.refreshLucideIcons();
                }
            });
        });
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
