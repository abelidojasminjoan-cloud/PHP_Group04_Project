
document.addEventListener("DOMContentLoaded", () => {
    const config = window.PUREVIA_CART;

    if (!config) return;

    const itemsContainer = document.getElementById("cartItems");
    const selectAll = document.getElementById("cartSelectAll");
    const deleteSelected = document.getElementById("cartDeleteSelected");
    const removeInactive = document.getElementById("cartRemoveInactive");
    const checkout = document.getElementById("cartCheckout");
    const message = document.getElementById("cartMessage");

    const subtotalElement = document.getElementById("cartSubtotal");
    const shippingElement = document.getElementById("cartShipping");
    const totalElement = document.getElementById("cartTotal");
    const selectedText = document.getElementById("cartSelectedText");
    const itemCount = document.getElementById("cartItemCount");
    const selectCount = document.getElementById("cartSelectCount");
    const emptyElement = document.getElementById("cartEmpty");

    if (!itemsContainer) return;

    const money = amount =>
        new Intl.NumberFormat("en-PH", {
            style: "currency",
            currency: "PHP"
        }).format(amount);

    const getItems = () =>
        [...itemsContainer.querySelectorAll(".pv-cart-item")];

    const showMessage = (text, error = false) => {
        if (!message) return;

        message.textContent = text;
        message.classList.toggle("is-error", error);
        message.hidden = false;
    };

    const updateSummary = () => {
        const items = getItems();
        let subtotal = 0;
        let selected = 0;
        let totalQuantity = 0;

        items.forEach(item => {
            const checkbox = item.querySelector(".cart-item-check");
            const quantity = Number(
                item.querySelector(".cart-qty-value").textContent
            );
            const price = Number(item.dataset.price);
            totalQuantity += quantity;

            item.querySelector(".cart-line-total").textContent =
                money(price * quantity);

            if (checkbox.checked && !checkbox.disabled) {
                subtotal += price * quantity;
                selected++;
            }
        });

        const shipping = selected > 0
            ? Number(config.shippingFee)
            : 0;

        subtotalElement.textContent = money(subtotal);
        shippingElement.textContent = money(shipping);
        totalElement.textContent = money(subtotal + shipping);

        selectedText.textContent =
            `${selected} of ${items.length} items selected`;

        itemCount.textContent =
            `${items.length} ${items.length === 1 ? "item" : "items"}`;

        selectCount.textContent = items.length;

        document.querySelectorAll(".bag-count").forEach(badge => {
            badge.textContent = totalQuantity > 99 ? "99+" : String(totalQuantity);
            badge.hidden = totalQuantity === 0;
            badge.closest(".bag-link")?.setAttribute(
                "aria-label",
                `Shopping Bag, ${totalQuantity} ${totalQuantity === 1 ? "item" : "items"}`
            );
        });

        checkout.disabled = selected === 0;

        const enabled = items.filter(item =>
            !item.querySelector(".cart-item-check").disabled
        );

        selectAll.checked =
            enabled.length > 0 &&
            enabled.every(item =>
                item.querySelector(".cart-item-check").checked
            );

        selectAll.indeterminate =
            enabled.some(item =>
                item.querySelector(".cart-item-check").checked
            ) && !selectAll.checked;

        selectAll.disabled = enabled.length === 0;

        if (emptyElement) {
            emptyElement.hidden = items.length !== 0;
        }
    };

    async function updateCart(payload) {
        const form = new FormData();

        form.append("csrf_token", config.csrfToken);

        for (const [key, value] of Object.entries(payload)) {
            if (Array.isArray(value)) {
                value.forEach(item => form.append(`${key}[]`, item));
            } else {
                form.append(key, value);
            }
        }

        const response = await fetch(
            `${config.baseUrl}/actions/cart/update.php`,
            {
                method: "POST",
                body: form,
                credentials: "same-origin"
            }
        );

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || "Unable to update cart.");
        }

        return result;
    }

    selectAll?.addEventListener("change", () => {
        getItems().forEach(item => {
            const checkbox = item.querySelector(".cart-item-check");

            if (!checkbox.disabled) {
                checkbox.checked = selectAll.checked;
            }
        });

        updateSummary();
    });

    itemsContainer.addEventListener("change", async event => {
        const item = event.target.closest(".pv-cart-item");
        if (!item) return;

        if (event.target.matches(".cart-item-check")) {
            updateSummary();
            return;
        }

        if (event.target.matches(".cart-variant-select")) {
            const select = event.target;

            select.disabled = true;

            try {
                await updateCart({
                    action: "variant",
                    item_id: item.dataset.itemId,
                    variant_id: select.value
                });

                window.location.reload();
            } catch (error) {
                showMessage(error.message, true);
                select.disabled = false;
                window.location.reload();
            }
        }
    });

    itemsContainer.addEventListener("click", async event => {
        const item = event.target.closest(".pv-cart-item");
        if (!item) return;

        const removeButton = event.target.closest(".cart-remove-item");
        const minusButton = event.target.closest(".cart-qty-minus");
        const plusButton = event.target.closest(".cart-qty-plus");

        if (!removeButton && !minusButton && !plusButton) return;

        const itemId = item.dataset.itemId;

        try {
            if (removeButton) {
                if (!confirm("Remove this product from your bag?")) {
                    return;
                }

                await updateCart({
                    action: "remove",
                    item_id: itemId
                });

                item.remove();
                updateSummary();
                showMessage("Product removed from your bag.");
                return;
            }

            const quantityElement = item.querySelector(".cart-qty-value");
            const current = Number(quantityElement.textContent);
            const stock = Number(item.dataset.stock);

            const next = minusButton
                ? current - 1
                : current + 1;

            if (next < 1) return;

            if (next > stock) {
                showMessage("Maximum available stock reached.", true);
                return;
            }

            await updateCart({
                action: "quantity",
                item_id: itemId,
                quantity: next
            });

            quantityElement.textContent = next;
            updateSummary();
        } catch (error) {
            showMessage(error.message, true);
        }
    });

    deleteSelected?.addEventListener("click", async () => {
        const selectedIds = getItems()
            .filter(item =>
                item.querySelector(".cart-item-check").checked
            )
            .map(item => item.dataset.itemId);

        if (!selectedIds.length) {
            showMessage("Select at least one product.", true);
            return;
        }

        if (!confirm("Delete selected products from your bag?")) {
            return;
        }

        try {
            await updateCart({
                action: "delete_selected",
                item_ids: selectedIds
            });

            getItems().forEach(item => {
                if (selectedIds.includes(item.dataset.itemId)) {
                    item.remove();
                }
            });

            updateSummary();
            showMessage("Selected products removed.");
        } catch (error) {
            showMessage(error.message, true);
        }
    });

    removeInactive?.addEventListener("click", async () => {
        const ids = getItems()
            .filter(item => item.dataset.active !== "1")
            .map(item => item.dataset.itemId);

        if (!ids.length) {
            showMessage("There are no inactive products to remove.");
            return;
        }

        try {
            await updateCart({
                action: "delete_selected",
                item_ids: ids
            });

            getItems().forEach(item => {
                if (ids.includes(item.dataset.itemId)) {
                    item.remove();
                }
            });

            updateSummary();
            showMessage("Inactive products removed.");
        } catch (error) {
            showMessage(error.message, true);
        }
    });

    checkout?.addEventListener("click", () => {
        const selectedIds = getItems()
            .filter(item =>
                item.querySelector(".cart-item-check").checked
            )
            .map(item => item.dataset.itemId);

        if (!selectedIds.length) {
            showMessage("Select products before checkout.", true);
            return;
        }

        const form = document.createElement("form");
        form.method = "POST";
        form.action = `${config.baseUrl}/public/checkout.php`;

        const appendField = (name, value) => {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = name;
            input.value = value;
            form.appendChild(input);
        };

        appendField("csrf_token", config.csrfToken);

        selectedIds.forEach(id => {
            appendField("selected_items[]", id);
        });

        document.body.appendChild(form);
        form.submit();
    });

    updateSummary();
});
