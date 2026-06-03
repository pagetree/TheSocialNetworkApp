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

    const updateToggle = (button, theme) => {
        const isLight = theme === "light";
        button.setAttribute("aria-pressed", isLight ? "true" : "false");
        button.setAttribute(
            "aria-label",
            isLight ? "Switch to dark theme" : "Switch to light theme"
        );

        const label = button.querySelector(".theme-toggle-label");
        if (label) {
            label.textContent = isLight ? "Dark" : "Light";
        }
    };

    const init = () => {
        const button = document.getElementById("theme-toggle");
        if (!button) {
            return;
        }

        const theme = applyTheme(getTheme());
        updateToggle(button, theme);

        button.addEventListener("click", () => {
            const nextTheme = getTheme() === "dark" ? "light" : "dark";
            applyTheme(nextTheme);
            writeThemeCookie(nextTheme);
            updateToggle(button, nextTheme);

            if (typeof window.refreshLucideIcons === "function") {
                window.refreshLucideIcons();
            }
        });
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
