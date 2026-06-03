(() => {
    document.querySelectorAll("[data-placeholder-follow]").forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        const label = button.querySelector(".profile-follow-btn-label");

        button.addEventListener("click", () => {
            const following = !button.classList.contains("is-following");
            button.classList.toggle("is-following", following);
            button.setAttribute("aria-pressed", following ? "true" : "false");
            if (label) {
                label.textContent = following ? "Following" : "Follow";
            }
        });
    });
})();
