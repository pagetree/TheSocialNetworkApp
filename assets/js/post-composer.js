(() => {
    const maxChars = 300;
    const warningAt = 50;

    const textarea = document.querySelector(".post-composer-input");
    const counter = document.querySelector(".post-char-counter");
    const progressCircle = document.querySelector(".post-char-counter-progress");

    if (!textarea || !counter || !progressCircle) {
        return;
    }

    const radius = 15.5;
    const circumference = 2 * Math.PI * radius;
    progressCircle.style.strokeDasharray = String(circumference);

    const updateCounter = () => {
        const used = textarea.value.length;
        const remaining = maxChars - used;
        const progress = remaining / maxChars;

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
    };

    textarea.addEventListener("input", updateCounter);
    updateCounter();
})();
