"use strict";

window.PureViaCancellation = {
    init(config) {
        const cancelModal = document.getElementById("ocCancelModal");
        const detailsModal = document.getElementById("ocDetailsModal");
        const form = document.getElementById("ocCancelForm");
        if (!cancelModal || !detailsModal || !form || form.dataset.initialized) return;
        form.dataset.initialized = "true";

        const reason = document.getElementById("ocReason");
        const reasonError = document.getElementById("ocReasonError");
        const submitError = document.getElementById("ocSubmitError");
        const confirm = document.getElementById("ocConfirmCancel");
        let activeModal = null;
        let previousFocus = null;
        let previousOverflow = "";
        let selectedOrderId = null;
        let submitting = false;

        function clearError(element) {
            element.hidden = true;
            element.textContent = "";
        }

        function open(modal, trigger) {
            previousFocus = trigger;
            previousOverflow = document.body.style.overflow;
            activeModal = modal;
            modal.hidden = false;
            document.body.style.overflow = "hidden";
            modal.querySelector(".oc-close").focus();
        }

        function close() {
            if (!activeModal || submitting) return;
            activeModal.hidden = true;
            activeModal = null;
            selectedOrderId = null;
            document.body.style.overflow = previousOverflow;
            previousFocus?.focus();
        }

        document.querySelectorAll("[data-cancel-id]").forEach((button) => {
            button.addEventListener("click", () => {
                const id = Number(button.dataset.cancelId);
                if (button.disabled || !Number.isInteger(id) || id <= 0) return;
                selectedOrderId = id;
                form.reset();
                reason.removeAttribute("aria-invalid");
                clearError(reasonError);
                clearError(submitError);
                document.getElementById("ocOrderNumber").textContent = button.dataset.orderNumber || "";
                open(cancelModal, button);
            });
        });

        document.querySelectorAll("[data-cancellation-details]").forEach((button) => {
            button.addEventListener("click", () => {
                const details = config.cancellations?.[button.dataset.cancellationDetails];
                if (!details) return;
                const fields = {
                    ocDetailNumber: "number", ocDetailRequested: "requested",
                    ocDetailReason: "reason", ocDetailText: "details", ocDetailRefund: "refund"
                };
                Object.entries(fields).forEach(([id, key]) => {
                    document.getElementById(id).textContent = details[key] || "";
                });
                open(detailsModal, button);
            });
        });

        [cancelModal, detailsModal].forEach((modal) => {
            modal.querySelectorAll("[data-oc-close]").forEach((button) => button.addEventListener("click", close));
            modal.addEventListener("click", (event) => { if (event.target === modal) close(); });
        });

        document.addEventListener("keydown", (event) => {
            if (!activeModal) return;
            if (event.key === "Escape") {
                event.preventDefault();
                close();
            }
            if (event.key === "Tab") {
                const controls = Array.from(activeModal.querySelectorAll("button:not(:disabled), select, textarea"));
                const first = controls[0];
                const last = controls[controls.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });

        reason.addEventListener("change", () => {
            reason.removeAttribute("aria-invalid");
            clearError(reasonError);
        });

        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            if (submitting || !selectedOrderId) return;
            clearError(submitError);
            if (!reason.value) {
                reasonError.textContent = "Please select a cancellation reason.";
                reasonError.hidden = false;
                reason.setAttribute("aria-invalid", "true");
                reason.focus();
                return;
            }

            submitting = true;
            confirm.disabled = true;
            confirm.textContent = "Cancelling...";
            form.setAttribute("aria-busy", "true");

            try {
                const data = new FormData(form);
                data.append("order_id", String(selectedOrderId));
                data.append("csrf_token", config.csrfToken || "");
                const response = await fetch(`${config.baseUrl}/actions/orders/cancel.php`, {
                    method: "POST", body: data, credentials: "same-origin",
                    headers: { Accept: "application/json" }
                });
                let result;
                try {
                    result = await response.json();
                } catch {
                    throw new Error("Unable to cancel your order right now. Please try again.");
                }
                if (!response.ok || !result.success) {
                    throw new Error(result.message || "Unable to cancel the order.");
                }
                window.location.reload();
            } catch (error) {
                submitError.textContent = error.message || "Cancellation failed. Please try again.";
                submitError.hidden = false;
            } finally {
                submitting = false;
                confirm.disabled = false;
                confirm.textContent = "Confirm Cancellation";
                form.removeAttribute("aria-busy");
            }
        });
    }
};
