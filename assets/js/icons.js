(() => {
    const run = () => {
        if (!window.lucide || typeof window.lucide.createIcons !== "function") {
            return;
        }

        window.lucide.createIcons();
    };

    window.refreshLucideIcons = run;

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", run);
    } else {
        run();
    }
})();
