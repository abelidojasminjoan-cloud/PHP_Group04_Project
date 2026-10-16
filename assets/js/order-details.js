"use strict";

document.addEventListener("DOMContentLoaded", () => {
    const config = window.PUREVIA_ORDER_DETAILS || {};

    const cancelButton = document.getElementById("odCancelOrder");
    const dialog = document.getElementById("odCancelDialog");
    const keepButton = document.getElementById("odKeepOrder");
    const confirmButton = document.getElementById("odConfirmCancel");
    const errorBox = document.getElementById("odCancelError");

    if (!cancelButton || !dialog || !confirmButton) {
        return;
    }

    function openDialog() {
        dialog.hidden = false;
        document.body.style.overflow = "hidden";
        keepButton?.focus();
    }

    function closeDialog() {
        dialog.hidden = true;
        document.body.style.overflow = "";
        cancelButton.focus();
    }

    cancelButton.addEventListener("click", openDialog);

    keepButton?.addEventListener("click", closeDialog);

    dialog.addEventListener("click", (event) => {
        if (event.target === dialog) {
            closeDialog();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !dialog.hidden) {
            closeDialog();
        }
    });

    confirmButton.addEventListener("click", async () => {
        confirmButton.disabled = true;
        confirmButton.textContent = "Cancelling...";

        if (errorBox) {
            errorBox.hidden = true;
            errorBox.textContent = "";
        }

        try {
            const formData = new FormData();

            formData.append("order_id", String(config.orderId));
            formData.append("csrf_token", config.csrfToken || "");

            const response = await fetch(
                `${config.baseUrl}/actions/orders/cancel.php`,
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin",
                    headers: {
                        Accept: "application/json"
                    }
                }
            );

            const text = await response.text();

            let result;

            try {
                result = JSON.parse(text);
            } catch {
                throw new Error(
                    "Invalid server response. Please check the PHP error log."
                );
            }

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || "Unable to cancel the order."
                );
            }

            window.location.reload();

        } catch (error) {
            closeDialog();

            if (errorBox) {
                errorBox.textContent =
                    error.message || "Cancellation failed.";

                errorBox.hidden = false;
            }
        } finally {
            confirmButton.disabled = false;
            confirmButton.textContent = "Yes, Cancel Order";
        }
    });
});