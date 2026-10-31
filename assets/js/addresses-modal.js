
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const config = window.PUREVIA_ADDRESS_CONFIG;
    if (!config) return;

    const modal = document.getElementById("addressModal");
    const form = document.getElementById("addressModalForm");
    const body = modal?.querySelector(".pv-modal-body");

    if (!modal || !form) return;

    const title = document.getElementById("addressModalTitle");
    const subtitle = document.getElementById("addressModalSubtitle");
    const action = document.getElementById("addressAction");
    const addressId = document.getElementById("addressId");
    const saveButton = document.getElementById("saveAddressButton");

    const region = document.getElementById("addressRegion");
    const province = document.getElementById("addressProvince");
    const city = document.getElementById("addressCity");
    const barangay = document.getElementById("addressBarangay");

    const defaultCheckbox = document.getElementById(
        "addressIsDefault"
    );

    const defaultHint = document.getElementById(
        "addressDefaultHint"
    );

    const addresses = config.addresses || {};
    const defaultForm = config.defaultForm || {};

    const locationPicker = window.PureViaPhAddress.create({region,province,city,barangay,
        onChange:()=>{ saveButton.disabled = !locationPicker.isReady; }
    });
    /* =====================================
       FORM FIELDS
    ===================================== */

    function setField(name, value) {
        const element = form.elements.namedItem(name);

        if (element && "value" in element) {
            element.value = value ?? "";
        }
    }

    function populateForm(data, editing = false) {
        form.reset();
        window.PureViaAccountValidation.clear(form);

        action.value = editing
            ? "update"
            : "create";

        addressId.value = editing
            ? String(data.id)
            : "0";

        title.textContent = editing
            ? "Edit Address"
            : "New Address";

        subtitle.textContent = editing
            ? "Update your delivery address details"
            : "Add a delivery address for faster checkout";

        saveButton.textContent = editing
            ? "Save Changes"
            : "Save Address";

        [
            "recipient_first_name",
            "recipient_last_name",
            "phone",
            "country",
            "postal_code",
            "address_line1",
            "address_line2"
        ].forEach(field => {
            setField(field, data[field] ?? "");
        });

        const restoreLocation = locationPicker.setAddress(data);

        /* =====================================
           HOME / OFFICE ONLY
        ===================================== */

        const selectedLabel =
            data.address_label === "Office"
                ? "Office"
                : "Home";

        const labelInputs = form.querySelectorAll(
            'input[name="address_label"]'
        );

        labelInputs.forEach(input => {
            input.checked =
                input.value === selectedLabel;
        });

        /* =====================================
           DEFAULT ADDRESS CHECKBOX
        ===================================== */

        const isCurrentDefault =
            editing &&
            Number(data.is_default) === 1;

        const isFirstAddress =
            !editing &&
            Object.keys(addresses).length === 0;

        // First address is always default.
        // Existing default cannot be unchecked.
        defaultCheckbox.checked =
            isCurrentDefault ||
            isFirstAddress ||
            Number(data.is_default) === 1;

        defaultCheckbox.disabled =
            isCurrentDefault || isFirstAddress;

        defaultHint.hidden =
            !isCurrentDefault && !isFirstAddress;

        if (isFirstAddress) {
            defaultHint.textContent =
                "Your first address automatically becomes the default.";
        } else if (isCurrentDefault) {
            defaultHint.textContent =
                "This is your current default address.";
        }

        /* RESET BUTTON */
        saveButton.disabled = true;
        restoreLocation.then(ready => {
            saveButton.disabled = !ready;
            form.dispatchEvent(new CustomEvent("pv:address-restored"));
        });
    }

    /* =====================================
       MODAL OPEN / CLOSE
    ===================================== */

    let previousFocus = null;

    function openModal(
        data = defaultForm,
        editing = false
    ) {
        previousFocus = document.activeElement;

        populateForm(data, editing);

        modal.hidden = false;

        document.body.classList.add(
            "pv-modal-open"
        );

        if (body) {
            body.scrollTop = 0;
        }

        requestAnimationFrame(() => {
            document.getElementById(
                "recipient_first_name"
            )?.focus();
        });
    }

    function closeModal() {
        modal.hidden = true;

        document.body.classList.remove(
            "pv-modal-open"
        );

        if (
            previousFocus &&
            typeof previousFocus.focus === "function"
        ) {
            previousFocus.focus();
        }
    }

    /* ADD ADDRESS */
    document.getElementById("openAddressModal")
        ?.addEventListener("click", () => {
            openModal(defaultForm, false);
        });

    document.querySelectorAll(
        "[data-address-add]"
    ).forEach(button => {
        button.addEventListener("click", () => {
            openModal(defaultForm, false);
        });
    });

    /* EDIT ADDRESS */
    document.querySelectorAll(
        "[data-address-edit]"
    ).forEach(button => {
        button.addEventListener("click", () => {
            const id = button.dataset.addressEdit;
            const address = addresses[id];

            if (address) {
                openModal(address, true);
            }
        });
    });

    /* CLOSE BUTTON */
    document.getElementById(
        "closeAddressModal"
    )?.addEventListener("click", closeModal);

    /* CANCEL BUTTON */
    document.getElementById(
        "cancelAddressModal"
    )?.addEventListener("click", closeModal);

    /* CLICK OUTSIDE */
    modal.addEventListener("click", event => {
        if (event.target === modal) {
            closeModal();
        }
    });

    /* KEYBOARD ACCESSIBILITY */
    document.addEventListener("keydown", event => {
        if (modal.hidden) return;

        if (event.key === "Escape") {
            closeModal();
            return;
        }

        if (event.key !== "Tab") return;

        const focusable = [
            ...modal.querySelectorAll(
                'button:not(:disabled), ' +
                'input:not(:disabled), ' +
                'select:not(:disabled), ' +
                'textarea:not(:disabled)'
            )
        ].filter(element =>
            element.getClientRects().length > 0
        );

        if (!focusable.length) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (
            event.shiftKey &&
            document.activeElement === first
        ) {
            event.preventDefault();
            last.focus();
        } else if (
            !event.shiftKey &&
            document.activeElement === last
        ) {
            event.preventDefault();
            first.focus();
        }
    });

    /* =====================================
       DELETE ADDRESS
    ===================================== */

    document.querySelectorAll(
        "[data-address-delete-form]"
    ).forEach(deleteForm => {
        deleteForm.addEventListener(
            "submit",
            event => {
                const confirmed = confirm(
                    "Are you sure you want to delete this address?"
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            }
        );
    });

    /* =====================================
       FORM SUBMISSION
    ===================================== */

    form.addEventListener("submit", event => {

        if (!window.PureViaAccountValidation.validate(form)) {
            event.preventDefault();
            return;
        }

        /* VALIDATE HOME / OFFICE */
        const selectedLabel = form.querySelector(
            'input[name="address_label"]:checked'
        );

        if (
            !selectedLabel ||
            !["Home", "Office"].includes(
                selectedLabel.value
            )
        ) {
            event.preventDefault();

            window.PureViaAccountValidation.setError(form.querySelector('[name="address_label"]'), 'Please select Home or Office.');
            return;
        }

        if (!locationPicker.isComplete()) {
            event.preventDefault();
            window.PureViaAccountValidation.setError(region, 'Select your region, province, city / municipality, and barangay.');
            region.focus();
            return;
        }
        /* PREVENT DOUBLE SUBMISSION */
        saveButton.disabled = true;
        saveButton.textContent = "Saving...";
    });

    /* =====================================
       OPEN MODAL FROM URL
    ===================================== */

    if (config.openModal) {
        openModal(
            config.initialForm || defaultForm,
            Boolean(config.isEdit)
        );
        Object.entries(config.fieldErrors || {}).forEach(([name, message]) => {
            const input = form.querySelector('[name="' + name + '"]');
            if (input) window.PureViaAccountValidation.setError(input, message);
        });
    }
});
