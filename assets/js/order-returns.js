"use strict";

window.PureViaReturns = {
    init(config) {
        const modal = document.getElementById("orReturnModal");
        const form = document.getElementById("orReturnForm");
        if (!modal || !form || form.dataset.initialized) return;
        form.dataset.initialized = "true";

        const products = document.getElementById("orProducts");
        const reason = document.getElementById("orReason");
        const fields = document.getElementById("orReturnFields");
        const submitError = document.getElementById("orSubmitError");
        const success = document.getElementById("orSuccess");
        const submit = document.getElementById("orSubmitReturn");
        const closeButton = document.getElementById("orKeepOrder");
        let selectedOrderId = null;
        let previousFocus = null;
        let previousOverflow = "";
        let submitting = false;
        const photoInput = document.getElementById('orPhotos');
        const photoList = document.getElementById('orPhotoList');
        let photos = [];
        function clearPhotos() {
            photos.forEach(photo => URL.revokeObjectURL(photo.url));
            photos = [];
            photoInput.value = '';
            photoList.replaceChildren();
        }
        function showPhotos() {
            photoList.replaceChildren(...photos.map((photo, index) => {
                const li = document.createElement('li');
                const img = document.createElement('img'); img.src = photo.url; img.alt = 'Return evidence ' + (index + 1);
                const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '\u00d7'; remove.setAttribute('aria-label', 'Remove photo ' + (index + 1));
                remove.addEventListener('click', () => { URL.revokeObjectURL(photo.url); photos.splice(index, 1); showPhotos(); });
                li.append(img, remove); return li;
            }));
        }
        document.getElementById('orAddPhotos').addEventListener('click', () => photoInput.click());
        photoInput.addEventListener('change', () => {
            const chosen = [...photoInput.files];
            if (photos.length + chosen.length > 5 || chosen.some(file => !['image/jpeg','image/png','image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024)) {
                error('orPhotosError', 'Choose up to 5 JPG, PNG, or WebP photos, 5 MB each.'); photoInput.value = ''; return;
            }
            chosen.forEach(file => photos.push({file, url: URL.createObjectURL(file)}));
            photoInput.value = ''; error('orPhotosError', ''); showPhotos();
        });
        modal.addEventListener('cancel', event => { event.preventDefault(); close(); });

        function error(id, message) {
            const label = document.getElementById(id);
            label.textContent = message;
            label.hidden = !message;
        }

        function close() {
            if (!modal.open || submitting) return;
            modal.close();
            clearPhotos();
            selectedOrderId = null;
            document.body.style.overflow = previousOverflow;
            if (previousFocus?.disabled) previousFocus.closest(".mo-order-card, .od-sidebar")?.setAttribute("tabindex", "-1");
            (previousFocus?.disabled ? previousFocus.closest(".mo-order-card, .od-sidebar") : previousFocus)?.focus();
        }

        function productOption(item) {
            const label = document.createElement("label");
            label.className = "return-product";
            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.name = "items[]";
            checkbox.value = String(item.id);
            checkbox.setAttribute("aria-describedby", "orProductsError");
            const image = document.createElement("img");
            image.src = item.image;
            image.alt = "";
            image.width = 58;
            image.height = 58;
            image.addEventListener("error", () => {
                image.src = `${config.baseUrl}/assets/images/products/default-product.svg`;
            }, { once: true });
            const info = document.createElement("span");
            const name = document.createElement("strong");
            name.textContent = item.name;
            const detail = document.createElement("small");
            detail.textContent = `Qty: ${item.quantity} · ${item.price}`;
            info.append(name, detail);
            label.append(checkbox, image, info);
            return label;
        }

        document.querySelectorAll("[data-return-id]").forEach((button) => {
            button.addEventListener("click", () => {
                const id = Number(button.dataset.returnId);
                const order = config.returns?.[id];
                if (button.disabled || !order || !order.items?.length) return;
                selectedOrderId = id;
                previousFocus = button;
                previousOverflow = document.body.style.overflow;
                form.reset();
                clearPhotos();
                form.querySelectorAll(".rt-error").forEach((label) => error(label.id, ""));
                form.querySelectorAll("[aria-invalid]").forEach((control) => control.removeAttribute("aria-invalid"));
                fields.hidden = false;
                success.hidden = true;
                submit.hidden = false;
                closeButton.textContent = "Cancel";
                document.getElementById("orOrderNumber").textContent = order.number;
                products.replaceChildren(...order.items.map(productOption));
                modal.showModal();
                modal.querySelector(".form-modal-body").scrollTop = 0;
                document.body.style.overflow = "hidden";
                modal.querySelector(".form-modal-close").focus();
            });
        });

        modal.querySelectorAll("[data-or-close]").forEach((button) => button.addEventListener("click", close));
        modal.addEventListener("click", (event) => { if (event.target === modal) close(); });
        document.addEventListener("keydown", (event) => {
            if (!modal.open) return;
            if (event.key === "Escape") { event.preventDefault(); close(); }
            if (event.key !== "Tab") return;
            const controls = Array.from(modal.querySelectorAll("button:not(:disabled), input, select, textarea"))
                .filter((element) => element.getClientRects().length > 0 && !element.disabled)
                .filter((element) => element.type !== "radio" || element.checked);
            const first = controls[0];
            const last = controls[controls.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault(); last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault(); first.focus();
            }
        });

        products.addEventListener("change", () => error("orProductsError", ""));
        reason.addEventListener("change", () => { reason.removeAttribute("aria-invalid"); error("orReasonError", ""); });
        form.querySelectorAll('[name="resolution"]').forEach((input) => input.addEventListener("change", () => error("orResolutionError", "")));

        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            if (submitting || !selectedOrderId || fields.hidden) return;
            error("orSubmitError", "");
            let invalid = null;
            if (!products.querySelector("input:checked")) {
                error("orProductsError", "Please select at least one product to return.");
                invalid = products.querySelector("input");
            }
            if (!reason.value) {
                error("orReasonError", "Please select a reason for return.");
                reason.setAttribute("aria-invalid", "true");
                invalid ||= reason;
            }
            if (!form.querySelector('[name="resolution"]:checked')) {
                error("orResolutionError", "Please select a preferred resolution.");
                invalid ||= form.querySelector('[name="resolution"]');
            }
            if (invalid) { invalid.focus(); return; }

            submitting = true;
            submit.disabled = true;
            submit.textContent = "Submitting...";
            form.setAttribute("aria-busy", "true");
            const orderId = selectedOrderId;
            try {
                const data = new FormData(form);
                photos.forEach(photo => data.append("photos[]", photo.file));
                data.append("order_id", String(orderId));
                data.append("csrf_token", config.csrfToken || "");
                const response = await fetch(`${config.baseUrl}/actions/orders/return.php`, {
                    method: "POST", body: data, credentials: "same-origin", headers: { Accept: "application/json" }
                });
                let result;
                try { result = await response.json(); }
                catch { throw new Error("Unable to submit your return request right now. Please try again."); }
                if (!response.ok || !result.success) throw new Error(result.message || "Unable to submit your return request.");
                fields.hidden = true;
                submit.hidden = true;
                closeButton.textContent = "Close";
                success.textContent = result.message;
                success.hidden = false;
                document.querySelectorAll("[data-return-id]").forEach((button) => {
                    if (Number(button.dataset.returnId) === orderId) {
                        button.disabled = true;
                        button.textContent = "Return requested";
                    }
                });
                closeButton.focus();
            } catch (failure) {
                submitError.textContent = failure.message || "Unable to submit your return request. Please try again.";
                submitError.hidden = false;
                submitError.scrollIntoView({ block: "nearest" });
            } finally {
                submitting = false;
                submit.disabled = false;
                submit.textContent = "Submit Return Request";
                form.removeAttribute("aria-busy");
            }
        });
    }
};
