"use strict";
window.PureViaCards = (() => {
    let initialization = null, session = null, localOrderId = null;
    let cardState = null;
    const cardFields = [
        ["number", "pvHostedCardNumber", "Enter a valid card number."],
        ["expiry", "pvHostedCardExpiry", "Enter a valid expiration date (MM / YY)."],
        ["cvv", "pvHostedCardCvc", "Enter a valid security code."]
    ];
    const controller = { ready: false, initialize, submit };
    const field = id => document.getElementById(id);
    const status = message => { field("pvCardStatus").textContent = message; };
    function validateFields(force = false, blurred = false) {
        if (!cardState) return true; // The SDK also validates every submission independently.
        let valid = true;
        for (const [type, id, message] of cardFields) {
            const state = cardState[type];
            if (!state) continue;
            const invalid = !state.isValid && (force || (!state.isEmpty && (blurred || state.isPotentiallyValid === false)));
            const container = field(id);
            container.classList.toggle("pv-card-invalid", invalid);
            container.setAttribute("aria-invalid", String(invalid));
            const error = field(id + "Error");
            error.textContent = message;
            error.hidden = !invalid;
            if (!state.isValid) valid = false;
        }
        return valid;
    }
    async function api(action, extra = {}) {
        const config = window.PUREVIA_CHECKOUT;
        const form = field("pvCheckoutForm");
        const data = new FormData();
        data.set("csrf_token", form.elements.csrf_token.value);
        data.set("action", action);
        for (const [key, value] of Object.entries(extra)) data.set(key, value);
        const response = await fetch(`${config.baseUrl}/actions/checkout/card_payment.php`, {
            method: "POST", credentials: "same-origin", headers: { Accept: "application/json" }, body: data
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || "Unable to process card payment.");
        return result;
    }
    function initialize(onReady) {
        if (initialization) return initialization;
        initialization = (async () => {
            try {
                if (!window.PUREVIA_CHECKOUT.paypalConfigured) {
                    status("Card payments will be available after PayPal Sandbox credentials are configured.");
                    return;
                }
                status("Loading secure card fields…");
                if (!window.paypal?.createInstance) {
                    await new Promise((resolve, reject) => {
                        const script = document.createElement("script");
                        script.src = window.PUREVIA_CHECKOUT.paypalMode === "live"
                            ? "https://www.paypal.com/web-sdk/v6/core" : "https://www.sandbox.paypal.com/web-sdk/v6/core";
                        script.async = true;
                        script.onload = resolve;
                        script.onerror = () => reject(new Error("Unable to load secure card fields. Refresh to try again."));
                        document.head.append(script);
                    });
                }
                const { clientToken } = await api("token");
                const sdk = await window.paypal.createInstance({ clientToken, components: ["card-fields"] });
                const methods = await sdk.findEligibleMethods({ currencyCode: "PHP" });
                if (!methods.isEligible("advanced_cards")) {
                    status("Embedded card payment is unavailable for this PayPal account or currency. You can choose PayPal Account or Cash on Delivery.");
                    return;
                }
                session = sdk.createCardFieldsOneTimePaymentSession();
                for (const [, id] of cardFields) {
                    const error = document.createElement("p");
                    error.id = id + "Error";
                    error.className = "pv-card-field-error";
                    error.setAttribute("role", "alert");
                    error.hidden = true;
                    field(id).after(error);
                    field(id).setAttribute("aria-describedby", error.id);
                }
                if (typeof session.on === "function") {
                    for (const eventName of ["change", "validitychange", "blur"]) {
                        await session.on(eventName, event => {
                            if (event.data) cardState = event.data;
                            validateFields(false, eventName === "blur");
                        });
                    }
                }
                for (const [id, type, placeholder, label] of [
                    ["pvHostedCardNumber", "number", "Card number", "Card number"],
                    ["pvHostedCardExpiry", "expiry", "MM/YY", "Expiry date"],
                    ["pvHostedCardCvc", "cvv", "CVC", "Security code"]
                ]) {
                    const component = session.createCardFieldsComponent({ type, placeholder, ariaLabel: label,
                        style: { input: { fontSize: "16px", color: "#20221d", padding: "12px", background: "#ffffff" } } });
                    field(id).replaceChildren(component);
                }
                controller.ready = true;
                status("Enter your card details here, then select Place order. PayPal will check the payment and request bank verification if needed.");
                onReady();
            } catch (error) {
                status(error.message || "Unable to load secure card payment. Refresh to retry.");
            }
        })();
        return initialization;
    }
    async function submit() {
        if (!controller.ready || !session) throw new Error("Secure card fields are not ready.");
        const name = field("pvCardholderName").value.trim();
        if (!name) { field("pvCardholderName").focus(); throw new Error("Enter the full name shown on your card."); }
        if (!validateFields(true)) throw new Error("Check the highlighted card details before placing your order.");
        const form = field("pvCheckoutForm");
        const data = new FormData(form);
        data.set("payment_method", "card");
        const response = await fetch(form.action, { method: "POST", credentials: "same-origin", headers: { Accept: "application/json" }, body: data });
        const local = await response.json();
        if (!response.ok || !local.success || !local.order_id) throw new Error(local.message || "Unable to prepare your order.");
        localOrderId = local.order_id;
        const remote = await api("create", { order_id: localOrderId });
        status("Processing your card. Complete any bank verification that appears.");
        const result = await session.submit(remote.id, { name });
        if (result.state !== "succeeded") {
            status("Payment was not completed. You can retry the same checkout.");
            throw new Error(result.state === "canceled" ? "Card verification was cancelled. You have not received an order confirmation." : "Your card payment was not approved. Check your details and retry.");
        }
        const verified = await api("capture", { order_id: localOrderId, paypal_order_id: remote.id });
        const destination = new URL(verified.redirect_url, location.href);
        if (destination.origin !== location.origin || !["http:", "https:"].includes(destination.protocol)) throw new Error("Invalid confirmation URL.");
        location.assign(destination.href);
    }
    return controller;
})();
