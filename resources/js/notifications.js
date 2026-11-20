import Swal from "sweetalert2/dist/sweetalert2.esm.js";
import "sweetalert2/dist/sweetalert2.css";

export function notify(icon, text) {
    if (!text) return;

    Swal.fire({
        toast: true,
        icon,
        text,
        position: "top-end",
        theme: document.documentElement.classList.contains("dark") ? "dark" : "light",
        showConfirmButton: false,
        showCloseButton: true,
        closeButtonAriaLabel: document.querySelector("[data-app-notification]")?.dataset.closeLabel || "Close",
        timer: icon === "success" || icon === "info" ? 4200 : 7000,
        timerProgressBar: true,
        customClass: { container: "app-notification-layer", popup: "app-notification-toast" },
        didOpen(toast) {
            toast.addEventListener("mouseenter", Swal.stopTimer);
            toast.addEventListener("mouseleave", Swal.resumeTimer);
        },
    });
}

export function reportInvalidField(field) {
    field.focus();
    notify("warning", field.form.dataset.notifyValidation);
}

export async function confirmAction(form, message, confirmLabel, cancelLabel) {
    const { isConfirmed } = await Swal.fire({
        icon: "warning",
        text: message,
        theme: document.documentElement.classList.contains("dark") ? "dark" : "light",
        showCancelButton: true,
        confirmButtonText: confirmLabel,
        cancelButtonText: cancelLabel,
        customClass: { container: "app-notification-layer" },
    });

    if (isConfirmed) HTMLFormElement.prototype.submit.call(form);
}

export function initializeNotifications() {
    const { icon, message } = document.querySelector("[data-app-notification]")?.dataset ?? {};
    if (message) notify(icon, message);

    let firstInvalidField;
    document.addEventListener("invalid", (event) => {
        const field = event.target;
        if (!field.form?.dataset.notifyValidation) return;
        event.preventDefault();

        if (firstInvalidField) return;
        firstInvalidField = field;
        queueMicrotask(() => {
            reportInvalidField(firstInvalidField);
            firstInvalidField = null;
        });
    }, true);
}
