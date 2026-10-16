
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const baseUrl = (
        window.PUREVIA_BASE_URL || "/purevia_website"
    ).replace(/\/$/, "");

    // Update the bag count in the header.
    function updateBagCount(count) {
        const total = Number(count);

        if (!Number.isFinite(total) || total < 0) {
            return;
        }

        document.querySelectorAll(".bag-count").forEach(element => {
            element.textContent = total > 99 ? "99+" : String(total);
            element.hidden = total === 0;
            element.closest(".bag-link")?.setAttribute(
                "aria-label",
                `Shopping Bag, ${total} ${total === 1 ? "item" : "items"}`
            );
        });
    }

    // Handle all Add to Bag forms without page navigation.
    document.addEventListener("submit", async event => {
        const form = event.target.closest(
            ".pv-add-cart-form, .shop-card-form, #pdCartForm"
        );

        if (!form) {
            return;
        }

        event.preventDefault();

        if (form.dataset.submitting === "1") {
            return;
        }

        const submitButton = form.querySelector(
            'button[type="submit"]'
        );

        if (!submitButton || submitButton.disabled) {
            return;
        }

        const formData = new FormData(form);

        const productId = Number(formData.get("product_id"));
        const variantId = Number(
            formData.get("product_variant_id")
        );
        const quantity = Number(formData.get("quantity"));

        if (
            !Number.isInteger(productId) ||
            productId <= 0 ||
            !Number.isInteger(variantId) ||
            variantId <= 0 ||
            !Number.isInteger(quantity) ||
            quantity <= 0
        ) {
            console.error("Invalid product, size, or quantity.");
            return;
        }

        form.dataset.submitting = "1";
        submitButton.disabled = true;

        try {
            const response = await fetch(
                `${baseUrl}/actions/cart/add.php`,
                {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin",
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );

            const contentType =
                response.headers.get("content-type") || "";

            if (!contentType.includes("application/json")) {
                throw new Error("Invalid server response.");
            }

            const result = await response.json();

            if (response.status === 401) {
                const loginButton = document.getElementById(
                    "openLoginModal"
                );

                if (loginButton) {
                    loginButton.click();
                }

                return;
            }

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || "Unable to add product to bag."
                );
            }

            // Only update the number in the header.
            updateBagCount(result.cart_count);

            // Inform other scripts that the cart changed.
            window.dispatchEvent(
                new CustomEvent("purevia:cart-updated", {
                    detail: result
                })
            );

        } catch (error) {
            console.error("Add to Bag error:", error);

        } finally {
            delete form.dataset.submitting;

            const selectedVariant = form.querySelector(
                ".shop-card-size.active, " +
                ".size-option.selected, " +
                ".pd-variant.active"
            );

            submitButton.disabled = selectedVariant
                ? Number(selectedVariant.dataset.stock) <= 0
                : false;
        }
    });
});
