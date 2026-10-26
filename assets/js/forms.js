document.addEventListener("DOMContentLoaded", function () {


    /* =====================================================
       MODAL
    ====================================================== */

    const modal =
        document.getElementById("addProductModal");

    const openButton =
        document.getElementById("openAddProductModal");

    const closeButtons =
        document.querySelectorAll(
            "[data-close-form-modal]"
        );


    function openModal() {

        if (!modal) {
            return;
        }

        modal.classList.add("is-open");

        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "form-modal-open"
        );


        const firstInput =
            modal.querySelector(
                "input:not([type='hidden'])"
            );


        if (firstInput) {

            setTimeout(function () {

                firstInput.focus();

            }, 100);
        }
    }


    function closeModal() {

        if (!modal) {
            return;
        }

        modal.classList.remove("is-open");

        modal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.classList.remove(
            "form-modal-open"
        );
    }


    if (openButton) {

        openButton.addEventListener(
            "click",
            openModal
        );
    }


    closeButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            closeModal
        );

    });


    /* ESCAPE CLOSE */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                modal &&
                modal.classList.contains("is-open")
            ) {

                closeModal();
            }

        }
    );


    /* =====================================================
       PRODUCT VARIANTS
    ====================================================== */

    const variantRows =
        document.getElementById("variantRows");

    const addSizeButton =
        document.getElementById("addSizeButton");


    if (
        variantRows &&
        addSizeButton
    ) {


        /* =================================================
           CREATE SIZE ROW
        ================================================== */

        function createVariantRow() {

            const row =
                document.createElement("div");


            row.className =
                "variant-row";


            function input(attributes) {
                const field = document.createElement('input');
                Object.entries(attributes).forEach(([key, value]) => field.setAttribute(key, value));
                return field;
            }
            const size = input({ type: 'text', name: 'size_capacity[]', class: 'form-control', placeholder: 'e.g. 30ml, 1 sheet', maxlength: '100', required: '' });
            const label = input({ type: 'text', name: 'variant_label[]', class: 'form-control', placeholder: 'e.g. Regular, Trial', maxlength: '100' });
            const priceBox = document.createElement('div');
            priceBox.className = 'price-input';
            const currency = document.createElement('span');
            currency.textContent = '\u20b1';
            priceBox.append(currency, input({ type: 'number', name: 'variant_price[]', class: 'form-control', placeholder: '0.00', min: '0', step: '0.01', required: '' }));
            const stock = input({ type: 'number', name: 'variant_stock[]', class: 'form-control', value: '0', min: '0', step: '1', required: '' });
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'variant-remove';
            remove.setAttribute('aria-label', 'Remove size');
            const icon = document.createElement('i');
            icon.className = 'fa-solid fa-xmark';
            remove.append(icon);
            row.append(size, label, priceBox, stock, remove);

            return row;
        }


        /* =================================================
           ADD SIZE
        ================================================== */

        addSizeButton.addEventListener(
            "click",
            function () {

                const newRow =
                    createVariantRow();


                variantRows.appendChild(
                    newRow
                );


                updateRemoveButtons();


                const firstInput =
                    newRow.querySelector("input");


                if (firstInput) {

                    firstInput.focus();
                }

            }
        );


        /* =================================================
           REMOVE SIZE
        ================================================== */

        variantRows.addEventListener(
            "click",
            function (event) {


                const removeButton =
                    event.target.closest(
                        ".variant-remove"
                    );


                if (!removeButton) {
                    return;
                }


                const rows =
                    variantRows.querySelectorAll(
                        ".variant-row"
                    );


                if (rows.length <= 1) {
                    return;
                }


                const row =
                    removeButton.closest(
                        ".variant-row"
                    );


                if (row) {

                    row.remove();

                    updateRemoveButtons();
                }

            }
        );


        /* =================================================
           ENABLE / DISABLE REMOVE
        ================================================== */

        function updateRemoveButtons() {

            const rows =
                variantRows.querySelectorAll(
                    ".variant-row"
                );


            rows.forEach(function (row) {

                const removeButton =
                    row.querySelector(
                        ".variant-remove"
                    );


                if (!removeButton) {
                    return;
                }


                removeButton.disabled =
                    rows.length === 1;

            });

        }


        updateRemoveButtons();

    }


    /* =====================================================
       PREVENT DOUBLE SUBMISSION
    ====================================================== */

    const addProductForm =
        document.getElementById(
            "addProductForm"
        );


    const submitButton =
        document.getElementById(
            "submitProductButton"
        );


    if (
        addProductForm &&
        submitButton
    ) {

        addProductForm.addEventListener(
            "submit",
            function () {


                if (
                    !addProductForm.checkValidity()
                ) {

                    return;
                }


                submitButton.disabled = true;

                submitButton.textContent =
                    "Adding...";

            }
        );

    }

});