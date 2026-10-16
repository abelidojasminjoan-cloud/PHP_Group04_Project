document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const config = window.PUREVIA_CHECKOUT || {};

    const form = document.getElementById("pvCheckoutForm");
    if (!form) return;

    // =========================================
    // ELEMENTS
    // =========================================

    const changeAddress = document.getElementById("pvChangeAddress");
    const addAddress = document.getElementById("pvAddAddress");
    const noAddress = document.getElementById("pvNoAddress");
    const addressCard = document.getElementById("pvAddressPreview");
    const addressEditor = document.getElementById("pvAddressEditor");
    const saveAddress = document.getElementById("pvSaveAddress");

    const nameInput = document.getElementById("pvRecipientName");
    const phoneInput = document.getElementById("pvRecipientPhone");
    const addressInput = document.getElementById("pvShippingAddress");

    const namePreview = document.getElementById("pvAddressName");
    const phonePreview = document.getElementById("pvAddressPhone");
    const addressPreview = document.getElementById("pvAddressText");

    const paymentOptions = document.querySelectorAll(
        ".pv-payment-option"
    );

    const paymentNotice = document.getElementById("pvPaymentNotice");
    const codMessage = document.getElementById("pvCodMessage");

    const placeOrder = document.getElementById("pvPlaceOrder");
    const placeOrderText = document.getElementById("pvPlaceOrderText");
    const errorBox = document.getElementById("pvCheckoutError");

    let submitting = false;

    // =========================================
    // HELPERS
    // =========================================

    const money = amount =>
        new Intl.NumberFormat("en-PH", {
            style: "currency",
            currency: "PHP"
        }).format(amount);

    function showError(message) {
        if (!errorBox) return;

        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    function clearError() {
        if (!errorBox) return;

        errorBox.textContent = "";
        errorBox.hidden = true;
    }

    // =========================================
    // ADDRESS VALIDATION
    // =========================================

    function validateAddress() {
        const name = nameInput.value.trim();
        const phone = phoneInput.value.trim();
        const address = addressInput.value.trim();

        if (!name || !phone || !address) {
            showError("Please complete your delivery information.");

            if (addressEditor) {
                addressEditor.hidden = false;
            }

            return false;
        }

        if (!/^[0-9+\s()-]{7,30}$/.test(phone)) {
            showError("Please enter a valid contact number.");

            if (addressEditor) {
                addressEditor.hidden = false;
            }

            phoneInput.focus();
            return false;
        }

        if (address.length < 10) {
            showError("Please enter your complete delivery address.");

            if (addressEditor) {
                addressEditor.hidden = false;
            }

            addressInput.focus();
            return false;
        }

        for (const field of addressEditor.querySelectorAll('input, textarea')) {
            if (!field.checkValidity()) {
                addressEditor.hidden = false;
                field.reportValidity();
                return false;
            }
        }

        return true;
    }

    // =========================================
    // UPDATE ADDRESS PREVIEW
    // =========================================

    function updateAddressPreview() {
        if (namePreview) {
            namePreview.textContent = nameInput.value.trim();
        }

        if (phonePreview) {
            phonePreview.textContent = phoneInput.value.trim();
        }

        if (addressPreview) {
            addressPreview.textContent = [addressInput.value.trim(), ...['address_line2', 'barangay', 'city', 'province', 'postal_code', 'country'].map(field => form.elements.namedItem(field)?.value.trim() || '')].filter(Boolean).join(', ');
        }

        if (addressCard) {
            addressCard.hidden = false;
        }

        if (noAddress) {
            noAddress.hidden = true;
        }

        if (changeAddress) {
            changeAddress.hidden = false;
        }
    }

    // =========================================
    // ADD ADDRESS
    // =========================================

    addAddress?.addEventListener("click", () => {
        clearError();

        if (addressEditor) {
            addressEditor.hidden = false;
        }

        if (noAddress) {
            noAddress.hidden = true;
        }

        nameInput.focus();
    });

    // =========================================
    // CHANGE ADDRESS
    // =========================================

    changeAddress?.addEventListener("click", () => {
        if (!addressEditor) return;

        addressEditor.hidden = !addressEditor.hidden;

        if (!addressEditor.hidden) {
            nameInput.focus();
            changeAddress.textContent = "Cancel";
        } else {
            changeAddress.textContent = "Change";
        }
    });

    // =========================================
    // SAVE ADDRESS
    // =========================================

    saveAddress?.addEventListener("click", () => {
        clearError();

        if (!validateAddress()) return;

        updateAddressPreview();

        if (addressEditor) {
            addressEditor.hidden = true;
        }

        if (changeAddress) {
            changeAddress.textContent = "Change";
        }
    });

    // =========================================
    // PAYMENT METHOD SELECTION
    // =========================================

    function updatePaymentSelection() {
        const selected = form.querySelector(
            'input[name="payment_method"]:checked'
        );

        // Update selected payment option appearance
        paymentOptions.forEach(option => {
            const radio = option.querySelector(
                'input[name="payment_method"]'
            );

            option.classList.toggle(
                "selected",
                radio === selected
            );
        });

        // Show COD information only when COD is selected
        if (codMessage) {
            codMessage.hidden =
                !selected || selected.value !== "cod";
        }

        // No payment method selected
        if (!selected) {
            if (paymentNotice) {
                paymentNotice.hidden = false;
                paymentNotice.textContent =
                    "Please select a payment method.";
            }

            if (placeOrder) {
                placeOrder.disabled = true;
            }

            return;
        }

        // =====================================
        // CASH ON DELIVERY
        // =====================================

        if (selected.value === "cod") {
            // COD does not require payment integration.

            if (paymentNotice) {
                paymentNotice.hidden = true;
                paymentNotice.textContent = "";
            }

            if (placeOrder) {
                placeOrder.disabled =
                    submitting || !config.canCheckout;
            }

            return;
        }

        // =====================================
        // GCASH / CARD
        // =====================================

        if (paymentNotice) {
            paymentNotice.hidden = false;

            if (selected.value === "gcash") {
                paymentNotice.textContent =
                    "GCash payment will be available once a payment gateway is connected.";
            } else {
                paymentNotice.textContent =
                    "Credit and debit card payments will be available once a payment gateway is connected.";
            }
        }

        if (placeOrder) {
            placeOrder.disabled = true;
        }
    }

    // =========================================
    // PAYMENT OPTION EVENTS
    // =========================================

    paymentOptions.forEach(option => {
        const radio = option.querySelector(
            'input[name="payment_method"]'
        );

        radio?.addEventListener("change", () => {
            clearError();
            updatePaymentSelection();
        });
    });

    // =========================================
    // PLACE ORDER
    // =========================================

    form.addEventListener("submit", async event => {
        event.preventDefault();

        if (submitting) return;

        clearError();

        // Verify checkout availability
        if (!config.canCheckout) {
            showError(
                "Your order cannot be processed. Please check your cart."
            );
            return;
        }

        // Get selected payment method
        const selectedPayment = form.querySelector(
            'input[name="payment_method"]:checked'
        );

        if (!selectedPayment) {
            showError("Please select a payment method.");
            return;
        }

        // Only COD is currently supported
        if (selectedPayment.value !== "cod") {
            showError(
                "Please select Cash on Delivery to continue."
            );
            return;
        }

        // Prevent duplicate submissions
        submitting = true;

        if (placeOrder) {
            placeOrder.disabled = true;
        }

        if (placeOrderText) {
            placeOrderText.textContent = "Processing order...";
        }

        try {
            // Keep validation and preview errors inside the visible error handler.
            if (!validateAddress()) return;
            updateAddressPreview();

            // =================================
            // SEND ORDER TO PHP BACKEND
            // =================================

            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                credentials: "same-origin",
                headers: {
                    "Accept": "application/json"
                }
            });

            const responseText = await response.text();

            if (!responseText.trim()) {
                throw new Error(
                    `The order server returned an empty response (HTTP ${response.status}). ` +
                    "Check your PHP error log and checkout-order.php."
                );
            }

            let result;

            try {
                result = JSON.parse(responseText);
            } catch (error) {
                console.error("Invalid checkout response:", responseText);

                throw new Error(
                    "The checkout server returned invalid JSON. " +
                    "Check the browser console and PHP error log."
                );
            }

            // =================================
            // HANDLE FAILED ORDER
            // =================================

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message ||
                    "Unable to place your order. Please try again."
                );
            }

            // =================================
            // ORDER SUCCESS
            // =================================

            // Redirect only after PHP confirms
            // that the order was saved successfully.

            if (result.redirect_url) {
                window.location.assign(result.redirect_url);
                return;
            }

            showError(
                "Order saved, but no confirmation page is configured."
            );

        } catch (error) {
            console.error("Checkout error:", error);

            showError(
                error.message ||
                "Something went wrong while placing your order."
            );

        } finally {
            submitting = false;

            if (placeOrderText) {
                placeOrderText.textContent =
                    `Place order · ${money(config.total || 0)}`;
            }

            updatePaymentSelection();
        }
    });

    // =========================================
    // INITIALIZE PAYMENT SELECTION
    // =========================================

    updatePaymentSelection();
});
