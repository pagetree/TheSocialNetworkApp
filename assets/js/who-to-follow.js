(() => {
    document.querySelectorAll("[data-placeholder-follow]").forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        button.addEventListener("click", () => {
            const following = !button.classList.contains("is-following");
            button.classList.toggle("is-following", following);
            button.setAttribute("aria-pressed", following ? "true" : "false");
        });
    });
})();
