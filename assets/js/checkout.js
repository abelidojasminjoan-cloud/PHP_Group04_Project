document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const config = window.PUREVIA_CHECKOUT || {};

    const form = document.getElementById("pvCheckoutForm");
    if (!form) return;

    // =========================================
    // ELEMENTS
    // =========================================

    const addressEditor = document.getElementById("pvAddressEditor");

    const nameInput = document.getElementById("pvRecipientName");
    const phoneInput = document.getElementById("pvRecipientPhone");
    const addressInput = document.getElementById("pvShippingAddress");


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

        for (const field of addressEditor.querySelectorAll('input, select, textarea')) {
            if (!field.checkValidity()) {
                addressEditor.hidden = false;
                field.reportValidity();
                return false;
            }
        }

        return true;
    }

    // The visible name fields keep the existing recipient_name submission field.
    const firstName = document.getElementById("pvRecipientFirstName");
    const lastName = document.getElementById("pvRecipientLastName");
    const region = document.getElementById("pvDelivery_region");
    const province = document.getElementById("pvDelivery_province");
    const city = document.getElementById("pvDelivery_city");
    const barangay = document.getElementById("pvDelivery_barangay");
    const secondLine = document.getElementById("pvDelivery_address_line2");



    const useSavedAddress = document.getElementById("pvUseSavedAddress");



    function updateAddressPreview() {
        nameInput.value = [firstName.value.trim(), lastName.value.trim()].filter(Boolean).join(" ");
    }

    function deliveryComplete() {
        return locationPicker.isComplete() && [firstName, lastName, phoneInput, addressInput,
            document.getElementById("pvDelivery_postal_code"), document.getElementById("pvDelivery_country")]
            .every(input => input.value.trim() !== "") && /^[0-9+\s()-]{7,30}$/.test(phoneInput.value.trim());
    }

    const locationPicker = window.PureViaPhAddress.create({region,province,city,barangay,onChange:()=>updatePaymentSelection()});
    locationPicker.setAddress({});
    updateAddressPreview();
    useSavedAddress?.addEventListener("change", async () => {
        const saved = useSavedAddress.checked ? config.savedAddress : null;
        firstName.value = saved?.recipient_first_name || "";
        lastName.value = saved?.recipient_last_name || "";
        phoneInput.value = saved?.phone || "";
        addressInput.value = saved?.address_line1 || "";
        secondLine.value = saved?.address_line2 || "";
        for (const name of ["postal_code", "country"]) {
            const input = document.getElementById(`pvDelivery_${name}`);
            input.value = saved?.[name] || (name === "country" ? "Philippines" : "");
        }
        await locationPicker.setAddress(saved || {});
        form.dispatchEvent(new CustomEvent("pv:address-restored"));
        clearError();
        updateAddressPreview();

        updatePaymentSelection();
    });
    addressEditor.addEventListener("input", event => {
        if (event.target !== useSavedAddress && useSavedAddress) useSavedAddress.checked = false;
        updateAddressPreview();

        updatePaymentSelection();
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
                    submitting || !config.canCheckout || !deliveryComplete();
            }

            return;
        }

        if (paymentNotice) {
            paymentNotice.hidden = false;
            paymentNotice.textContent = config.paypalConfigured
                ? "Sign in to your PayPal account to approve payment securely."
                : "PayPal is not configured yet. Please choose Cash on Delivery.";
        }
        if (placeOrder) placeOrder.disabled = submitting || !config.canCheckout || !deliveryComplete() || !config.paypalConfigured;
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

        // Only supported server-side payment methods are accepted.
        if (!["cod", "paypal"].includes(selectedPayment.value)) {
            showError(
                "Please select Cash on Delivery or PayPal."
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

            if (response.status === 409 && result.redirect_url) {
                const retryUrl = new URL(result.redirect_url, location.href);
                if (retryUrl.origin === location.origin) { location.assign(retryUrl.href); return; }
            }
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
                const destination = new URL(result.redirect_url, window.location.href);
                const localDestination = destination.origin === window.location.origin;
                const paypalHost = config.paypalMode === 'live' ? 'www.paypal.com' : 'www.sandbox.paypal.com';
                const paypalDestination = selectedPayment.value === 'paypal' && result.payment_provider === 'paypal' &&
                    destination.protocol === 'https:' && destination.hostname === paypalHost && !destination.port;
                if ((!localDestination && !paypalDestination) || !['http:', 'https:'].includes(destination.protocol) ||
                    destination.username || destination.password) {
                    throw new Error('Invalid payment destination.');
                }
                window.location.assign(destination.href);
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
