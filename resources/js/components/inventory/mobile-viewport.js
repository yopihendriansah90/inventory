const textControl = "textarea, input:not([type=button], [type=submit], [type=checkbox], [type=radio], [type=file], [type=hidden])";

export default function initializeInventoryViewport() {
    const root = document.documentElement;
    const viewport = window.visualViewport;
    if (!root.hasAttribute("data-inventory-mobile-shell") || !viewport) return;

    const mobile = window.matchMedia("(width < 1280px)");
    const topbar = document.querySelector("[data-inventory-topbar]");
    const navigation = document.querySelector("[data-inventory-mobile-nav]");
    const actions = document.querySelector(".inventory-form-actions");
    let focusTimer;

    function updateOffsets() {
        const covered = root.clientHeight - viewport.height;
        const keyboardOverlaysLayout = mobile.matches && viewport.scale === 1 && covered > 80
            && document.activeElement?.matches(textControl);
        const top = keyboardOverlaysLayout ? Math.max(0, viewport.offsetTop) : 0;
        const bottom = keyboardOverlaysLayout ? Math.max(0, covered - top) : 0;

        root.style.setProperty("--inventory-viewport-top", top + "px");
        root.style.setProperty("--inventory-viewport-bottom", bottom + "px");
    }

    function adjustFocusedField() {
        updateOffsets();
        clearTimeout(focusTimer);

        const field = document.activeElement;
        if (!mobile.matches || viewport.scale !== 1 || !field?.matches(textControl) || field.closest("[role=dialog]")) return;

        focusTimer = setTimeout(() => {
            if (document.activeElement !== field) return;

            const top = topbar.getBoundingClientRect().bottom;
            const navTop = navigation.getBoundingClientRect().top;
            const actionsTop = actions && getComputedStyle(actions).position === "sticky"
                ? actions.getBoundingClientRect().top : navTop;
            const bottom = Math.min(navTop, actionsTop);
            const rect = field.getBoundingClientRect();
            if (bottom - top > rect.height && (rect.top < top || rect.bottom > bottom)) {
                field.scrollIntoView({ block: "center" });
            }
        }, 120);
    }

    viewport.addEventListener("resize", adjustFocusedField);
    viewport.addEventListener("scroll", updateOffsets);
    document.addEventListener("focusin", adjustFocusedField);
    document.addEventListener("focusout", () => requestAnimationFrame(updateOffsets));
    updateOffsets();
}
