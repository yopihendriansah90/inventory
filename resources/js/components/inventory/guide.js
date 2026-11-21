export default function initializeInventoryGuide() {
    const guide = document.querySelector("[data-inventory-guide]");
    if (!guide || !("IntersectionObserver" in window)) return;

    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;

            entry.target.dataset.guideRevealed = "";
            observer.unobserve(entry.target);
        }
    });

    for (const element of guide.querySelectorAll("[data-guide-reveal]")) {
        observer.observe(element);
    }
}
