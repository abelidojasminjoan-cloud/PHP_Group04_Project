"use strict";

document.addEventListener("DOMContentLoaded", () => {
    const config = window.PUREVIA_ORDERS || {};

    const tabs = Array.from(
        document.querySelectorAll(".mo-tab")
    );

    const cards = Array.from(
        document.querySelectorAll(".mo-order-card")
    );

    const searchInput = document.getElementById("moSearch");
    const emptyState = document.getElementById("moEmpty");

    const cancelModal = document.getElementById("moCancelModal");
    const cancelOrderNumber = document.getElementById(
        "moCancelOrderNumber"
    );
    const cancelError = document.getElementById("moCancelError");
    const confirmCancel = document.getElementById("moConfirmCancel");

    let activeTab = "all";
    let selectedOrderId = null;
    let previousFocus = null;

    // FILTER ORDERS

    function filterOrders() {
        const query = (searchInput?.value || "")
            .trim()
            .toLocaleLowerCase();

        let visibleCount = 0;

        cards.forEach((card) => {
            const cardTab = card.dataset.tab || "";

            const searchable = (card.dataset.search || "")
                .toLocaleLowerCase();

            const matchesTab =
                activeTab === "all" || cardTab === activeTab;

            const matchesSearch =
                query === "" || searchable.includes(query);

            const visible = matchesTab && matchesSearch;

            card.hidden = !visible;

            if (visible) {
                visibleCount++;
            }
        });

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
        }
    }

    // TABS

    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            activeTab = tab.dataset.tab || "all";

            tabs.forEach((button) => {
                const isActive = button === tab;

                button.classList.toggle("active", isActive);

                button.setAttribute(
                    "aria-selected",
                    isActive ? "true" : "false"
                );
                button.tabIndex = isActive ? 0 : -1;
            });

            document.getElementById("moOrderList")?.setAttribute("aria-labelledby", tab.id);

            filterOrders();
            tab.scrollIntoView({ block: "nearest", inline: "nearest" });
        });

        tab.addEventListener("keydown", (event) => {
            const index = tabs.indexOf(tab);
            let nextIndex;
            if (event.key === "ArrowRight") nextIndex = (index + 1) % tabs.length;
            else if (event.key === "ArrowLeft") nextIndex = (index - 1 + tabs.length) % tabs.length;
            else if (event.key === "Home") nextIndex = 0;
            else if (event.key === "End") nextIndex = tabs.length - 1;
            else return;
            event.preventDefault();
            tabs[nextIndex].click();
            tabs[nextIndex].focus();
        });
    });

    searchInput?.addEventListener("input", filterOrders);

    // CANCELLATION MODAL

    function openCancelModal() {
        if (!cancelModal) return;

        previousFocus = document.activeElement;
        cancelModal.hidden = false;
        document.body.style.overflow = "hidden";

        cancelModal.querySelector(".mo-modal-close")?.focus();
    }

    function closeCancelModal() {
        if (cancelModal) {
            cancelModal.hidden = true;
        }

        document.body.style.overflow = "";
        selectedOrderId = null;

        if (previousFocus instanceof HTMLElement) {
            previousFocus.focus();
        }
    }

    document.querySelectorAll("[data-close-modal]").forEach(
        (button) => {
            button.addEventListener("click", closeCancelModal);
        }
    );

    cancelModal?.addEventListener("click", (event) => {
        if (event.target === cancelModal) {
            closeCancelModal();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !cancelModal?.hidden) {
            closeCancelModal();
        }
    });

    // OPEN CANCELLATION CONFIRMATION

    document.querySelectorAll("[data-cancel-id]").forEach(
        (button) => {
            button.addEventListener("click", () => {
                const orderId = Number(button.dataset.cancelId);

                if (!Number.isInteger(orderId) || orderId <= 0) {
                    return;
                }

                selectedOrderId = orderId;

                if (cancelOrderNumber) {
                    cancelOrderNumber.textContent =
                        button.dataset.orderNumber || "";
                }

                if (cancelError) {
                    cancelError.hidden = true;
                    cancelError.textContent = "";
                }

                openCancelModal();
            });
        }
    );

    // SUBMIT CANCELLATION

    confirmCancel?.addEventListener("click", async () => {
        if (!selectedOrderId) return;

        const orderId = selectedOrderId;

        confirmCancel.disabled = true;
        confirmCancel.textContent = "Cancelling...";

        if (cancelError) {
            cancelError.hidden = true;
            cancelError.textContent = "";
        }

        try {
            const formData = new FormData();

            formData.append("order_id", String(orderId));
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

            const responseText = await response.text();

            let result;

            try {
                result = JSON.parse(responseText);
            } catch {
                throw new Error(
                    "The server returned an invalid response."
                );
            }

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || "Unable to cancel the order."
                );
            }

            window.location.reload();

        } catch (error) {
            if (cancelError) {
                cancelError.textContent =
                    error.message || "Cancellation failed.";

                cancelError.hidden = false;
            }
        } finally {
            confirmCancel.disabled = false;
            confirmCancel.textContent = "Cancel Order";
        }
    });

    filterOrders();
});
