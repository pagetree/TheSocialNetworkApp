(() => {
    const run = () => {
        if (!window.lucide || typeof window.lucide.createIcons !== "function") {
            return false;
        }

        window.lucide.createIcons();
        return true;
    };

    window.refreshLucideIcons = run;

    const schedule = () => {
        if (!run()) {
            window.setTimeout(run, 100);
            window.setTimeout(run, 500);
        }

        window.addEventListener("load", run, { once: true });
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", schedule);
    } else {
        schedule();
    }
})();
