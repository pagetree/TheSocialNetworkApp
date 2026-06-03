(() => {
    const run = () => {
        if (!window.lucide || typeof window.lucide.createIcons !== "function") {
            return;
        }

        window.lucide.createIcons();
    };

    window.refreshLucideIcons = run;

    const schedule = () => {
        run();
        window.addEventListener("load", run, { once: true });
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", schedule);
    } else {
        schedule();
    }
})();
