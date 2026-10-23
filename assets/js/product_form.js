document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("addProductModal");
    const openButton = document.getElementById("openAddProductModal");
    const form = document.getElementById("addProductForm");

    if (!modal || !form || !openButton) {
        return;
    }

    const submitButton = document.getElementById("submitProductButton");
    const closeButtons = modal.querySelectorAll("[data-close-form-modal]");

    let lastFocusedElement = null;
    let loadController = null;
    let editingProduct = false;
    let savingProduct = false;
    const formBody = modal.querySelector('.form-modal-body');
    const title = document.getElementById('addProductTitle');
    const subtitle = title.nextElementSibling;
    const formError = document.createElement('p');
    formError.className = 'form-alert form-alert-error';
    formError.setAttribute('role', 'alert');
    formError.tabIndex = -1;

    function showFormError(message) {
        formError.textContent = message;
        formBody.prepend(formError);
        formError.focus();
    }

    /* OPEN MODAL */

    function openModal() {
        lastFocusedElement = document.activeElement;

        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");

        document.body.classList.add("form-modal-open");

        document.getElementById("productName")?.focus();
    }

    /* CLOSE MODAL */

    function closeModal() {
        if (savingProduct) return;
        loadController?.abort();
        loadController = null;
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");

        document.body.classList.remove("form-modal-open");

        if (lastFocusedElement) {
            lastFocusedElement.focus();
        }
    }

    openButton.addEventListener('click', function () {
        resetProductForm(false);
        openModal();
    });

    document.querySelectorAll('.edit-product-button').forEach(button => {
        button.addEventListener('click', async function () {
            resetProductForm(true);
            const controller = new AbortController();
            loadController = controller;
            openModal();
            formBody.inert = true;
            form.setAttribute('aria-busy', 'true');
            submitButton.disabled = true;
            subtitle.textContent = 'Loading product details...';
            let loaded = false;
            try {
                const url = new URL(form.dataset.editAction, document.baseURI);
                url.searchParams.set('id', button.dataset.productId);
                const response = await fetch(url, {
                    credentials: 'same-origin', cache: 'no-store',
                    headers: { Accept: 'application/json' }, signal: controller.signal
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'Unable to load this product.');
                if (controller.signal.aborted) return;
                populateProductForm(data.product);
                subtitle.textContent = 'Update the details and save your changes';
                loaded = true;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    formBody.inert = false;
                    subtitle.textContent = 'Close and reopen Edit to try again';
                    showFormError(error.message || 'Unable to load this product. Please try again.');
                }
            } finally {
                if (loadController === controller) {
                    loadController = null;
                    formBody.inert = false;
                    form.removeAttribute('aria-busy');
                    submitButton.disabled = !loaded;
                    if (loaded) document.getElementById('productName').focus();
                }
            }
        });
    });

    closeButtons.forEach(button => {
        button.addEventListener("click", closeModal);
    });

    document.addEventListener("keydown", function (event) {

        if (!modal.classList.contains("is-open")) {
            return;
        }

        if (event.key === "Escape") {
            closeModal();
        }

        if (event.key === "Tab") {

            const focusable = Array.from(
                modal.querySelectorAll(
                    'button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled])'
                )
            ).filter(element => element.getClientRects().length > 0);

            if (!focusable.length) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    /* SEARCH CHECKBOXES */

    function setupCheckboxSearch(inputId, listId) {

        const searchInput = document.getElementById(inputId);
        const list = document.getElementById(listId);

        if (!searchInput || !list) {
            return;
        }

        searchInput.addEventListener("input", function () {

            const query = searchInput.value
                .trim()
                .toLowerCase();

            list.querySelectorAll('.product-checkbox').forEach(option => {

                const text = option.textContent.toLowerCase();

                option.hidden = !text.includes(query);
            });
        });
    }

    setupCheckboxSearch("concernSearch", "concernOptions");
    setupCheckboxSearch("ingredientSearch", "ingredientOptions");

    function updateRoutineFields() {
        const role = document.getElementById('productRoutineRole').value;
        document.getElementById('productRoutineDetails').hidden = role === 'none';
        document.getElementById('productTargetedField').hidden = role !== 'treatment';
        document.getElementById('productSunscreenField').hidden = role !== 'sunscreen';
    }
    document.getElementById('productRoutineRole').addEventListener('change', () => {
        const role = document.getElementById('productRoutineRole').value;
        if (role !== 'treatment') document.getElementById('productTargetedApproved').checked = false;
        if (role !== 'sunscreen') document.getElementById('productSunscreenVerified').checked = false;
        updateRoutineFields();
    });

    /* DYNAMIC PRODUCT SIZES */

    const variantRows = document.getElementById("variantRows");
    const addSizeButton = document.getElementById("addSizeButton");
    const variantTemplate = variantRows.querySelector('.variant-row').cloneNode(true);

    function createVariantRow(variant = {}) {
        const row = variantTemplate.cloneNode(true);
        const values = {
            'variant_id[]': variant.id ?? '',
            'size_capacity[]': variant.size_capacity ?? '',
            'variant_label[]': variant.variant_label ?? '',
            'variant_price[]': variant.price ?? '',
            'variant_stock[]': variant.stock_quantity ?? '0',
            'variant_threshold[]': variant.low_stock_threshold ?? '15'
        };
        row.querySelectorAll('input').forEach(input => { input.value = values[input.name]; });
        return row;
    }

    function updateRemoveButtons() {

        const rows = variantRows.querySelectorAll(".variant-row");

        rows.forEach(row => {
            const button = row.querySelector(".variant-remove");

            button.disabled = rows.length === 1;
        });

        addSizeButton.disabled = rows.length >= 50;
    }

    addSizeButton.addEventListener("click", function () {

        if (variantRows.querySelectorAll(".variant-row").length >= 50) {
            return;
        }

        const newRow = createVariantRow();

        variantRows.appendChild(newRow);

        updateRemoveButtons();

        newRow.querySelector('input[name="size_capacity[]"]').focus();
    });

    variantRows.addEventListener("click", function (event) {

        const removeButton = event.target.closest(".variant-remove");

        if (!removeButton) {
            return;
        }

        const rows = variantRows.querySelectorAll(".variant-row");

        if (rows.length <= 1) {
            return;
        }

        const row = removeButton.closest('.variant-row');
        const size = row.querySelector('input[name="size_capacity[]"]').value.trim();
        window.PureViaAdminDelete?.open({
            trigger: removeButton,
            title: 'Remove Size?',
            name: size || 'this product size',
            message: 'This size will be removed from the form. Save the product to apply the change.',
            confirmLabel: 'Remove Size',
            pendingLabel: 'Removing…',
            onConfirm() {
                row.remove();
                updateRemoveButtons();
            }
        });
    });

    updateRemoveButtons();

    /* ==========================================
    PRODUCT IMAGES
    ========================================== */

    const imageUrlInput = document.getElementById("productImageUrl");
    const addImageUrlButton = document.getElementById("addImageUrl");

    const imageFileInput = document.getElementById("productImageFiles");
    const uploadImagesButton = document.getElementById("uploadImagesButton");

    const imageList = document.getElementById("productImageList");
    const imageUrlFields = document.getElementById("productImageUrlFields");

    let imageEntries = [];

    function resetProductForm(editing) {
        loadController?.abort();
        loadController = null;
        editingProduct = editing;
        form.querySelectorAll('[data-edit-option]').forEach(option => option.remove());
        form.reset();
        form.elements.namedItem('product_id').value = '';
        form.elements.namedItem('product_revision').value = '';
        form.action = editing ? form.dataset.editAction : form.dataset.addAction;
        formBody.inert = false;
        form.removeAttribute('aria-busy');
        formError.remove();
        form.querySelectorAll('.product-checkbox').forEach(option => { option.hidden = false; });
        updateRoutineFields();
        title.textContent = editing ? 'Edit Product' : 'Add New Product';
        subtitle.textContent = 'Fill in the details and add all available sizes';
        submitButton.textContent = editing ? 'Save Changes' : 'Add Product';
        submitButton.disabled = false;
        variantRows.replaceChildren(createVariantRow());
        updateRemoveButtons();
        imageEntries.forEach(entry => { if (entry.type === 'file') URL.revokeObjectURL(entry.preview); });
        imageEntries = [];
        refreshImages();
        formBody.scrollTop = 0;
    }

    function populateProductForm(product) {
        form.elements.namedItem('product_id').value = product.id;
        form.elements.namedItem('product_revision').value = product.revision;
        const fields = {
            productName: product.product_name, productCode: product.product_code,
            productCategory: product.category_id, productStatus: product.status,
            productDescription: product.description ?? '', keyBenefits: product.benefits.join('\n'),
            productRoutineRole: product.suitability_settings.routine_role,
            productUsagePeriod: product.suitability_settings.usage_period,
            productRoutineInstructions: product.suitability_settings.routine_instructions ?? ''
        };
        const category = document.getElementById('productCategory');
        if (![...category.options].some(option => option.value === String(product.category_id))) {
            const option = new Option(product.category_name + ' (inactive)', product.category_id);
            option.dataset.editOption = '';
            category.add(option);
        }
        Object.entries(fields).forEach(([id, value]) => { document.getElementById(id).value = value; });
        for (const [id, key] of Object.entries({ productIngredientsComplete: 'ingredients_complete', productGentle: 'gentle', productTargetedApproved: 'targeted_approved', productSunscreenVerified: 'sunscreen_verified' })) document.getElementById(id).checked = Number(product.suitability_settings[key]) === 1;
        updateRoutineFields();
        const selections = [
            ['skin_type_ids[]', '.skin-type-options', product.skin_types],
            ['concern_ids[]', '#concernOptions', product.concerns],
            ['ingredient_ids[]', '#ingredientOptions', product.ingredients],
            ['suitability_tag_ids[]', '#suitabilityOptions', product.suitability_tags]
        ];
        selections.forEach(([name, selector, items]) => {
            items.forEach(item => {
                let input = [...form.querySelectorAll('input')].find(input => input.name === name && input.value === String(item.id));
                if (!input) {
                    const label = document.createElement('label');
                    label.className = 'product-checkbox';
                    label.dataset.editOption = '';
                    input = document.createElement('input');
                    input.type = 'checkbox'; input.name = name; input.value = item.id;
                    const text = document.createElement('span');
                    text.textContent = item.label + ' (inactive)';
                    label.append(input, text);
                    form.querySelector(selector).appendChild(label);
                }
                input.checked = true;
            });
        });
        variantRows.replaceChildren(...(product.variants.length ? product.variants.map(createVariantRow) : [createVariantRow()]));
        updateRemoveButtons();
        const preview = path => new URL(path, new URL(form.dataset.baseUrl + '/', document.baseURI)).href;
        imageEntries = product.images.map(image => ({
            type: 'existing', value: image.id, preview: preview(image.image_path)
        }));
        if (!imageEntries.length && product.image) {
            imageEntries.push({ type: 'legacy', preview: preview(product.image) });
        }
        refreshImages();
    }

    /* COUNT IMAGES */

    function imageCount() {
        return imageEntries.length;
    }

    /* SYNCHRONIZE IMAGES WITH FORM */

    function syncImageFields() {

        imageUrlFields.replaceChildren();

        function hiddenField(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = name; input.value = value;
            imageUrlFields.appendChild(input);
        }
        let urlIndex = 0;
        let fileIndex = 0;

        imageEntries.forEach(entry => {
            let key;
            if (entry.type === 'url') {
                hiddenField('image_urls[]', entry.value);
                key = 'url:' + urlIndex++;
            } else if (entry.type === 'file') {
                key = 'file:' + fileIndex++;
            } else if (entry.type === 'existing') {
                hiddenField('existing_image_ids[]', entry.value);
                key = 'existing:' + entry.value;
            } else {
                hiddenField('keep_primary_image', '1');
                key = 'legacy';
            }
            hiddenField('image_order[]', key);
        });

        const transfer = new DataTransfer();

        imageEntries.forEach(entry => {

            if (entry.type === "file") {
                transfer.items.add(entry.file);
            }
        });

        imageFileInput.files = transfer.files;
    }

    /* DISPLAY IMAGE THUMBNAILS */

    function renderImageList() {

        imageList.replaceChildren();

        imageEntries.forEach((entry, index) => {

            const item = document.createElement("div");
            item.className = "product-image-item";

            /* IMAGE */

            const img = document.createElement("img");

            img.src = entry.type === "url"
                ? entry.value
                : entry.preview;

            img.alt = "Product image " + (index + 1);

            img.onerror = function () {

                img.style.display = "none";

                if (!item.querySelector(".product-image-error")) {

                    const errorIcon = document.createElement("div");
                    errorIcon.className = "product-image-error";

                    const icon = document.createElement("i");
                    icon.className = "fa-regular fa-image";

                    errorIcon.appendChild(icon);
                    item.insertBefore(errorIcon, item.firstChild);
                }
            };

            item.appendChild(img);

            /* REMOVE BUTTON */

            const removeButton = document.createElement("button");

            removeButton.type = "button";
            removeButton.className = "product-image-remove";
            removeButton.innerHTML = '<i class="fa-solid fa-xmark"></i>';

            removeButton.setAttribute(
                "aria-label",
                "Remove image " + (index + 1)
            );

            removeButton.addEventListener("click", function () {

                const entry = imageEntries[index];
                window.PureViaAdminDelete?.open({
                    trigger: removeButton,
                    title: 'Remove Image?',
                    name: 'product image ' + (index + 1),
                    message: 'This image will be removed from the form. Save the product to apply the change.',
                    confirmLabel: 'Remove Image',
                    pendingLabel: 'Removing…',
                    onConfirm() {
                        const entryIndex = imageEntries.indexOf(entry);
                        if (entryIndex === -1) return;
                        const removed = imageEntries.splice(entryIndex, 1)[0];
                        if (removed.type === 'file') URL.revokeObjectURL(removed.preview);
                        refreshImages();
                    }
                });
            });

            item.appendChild(removeButton);

            /* PRIMARY IMAGE BADGE */

            if (index === 0) {

                const primaryBadge = document.createElement("span");

                primaryBadge.className = "product-image-primary";
                primaryBadge.textContent = "Primary";

                item.appendChild(primaryBadge);
            }

            imageList.appendChild(item);
        });
    }

    /* REFRESH IMAGES */

    function refreshImages() {
        syncImageFields();
        renderImageList();
    }

    /* ADD IMAGE URL */

    addImageUrlButton.addEventListener("click", function () {

        const url = imageUrlInput.value.trim();

        if (!url) {
            imageUrlInput.focus();
            return;
        }

        try {

            const parsed = new URL(url);

            if (!["http:", "https:"].includes(parsed.protocol)) {
                throw new Error("Invalid image URL");
            }

        } catch {

            alert("Please enter a valid HTTP or HTTPS image URL.");
            return;
        }

        if (url.length > 2048) {
            alert("Image URL is too long.");
            return;
        }

        if (imageCount() >= 10) {
            alert("Maximum of 10 product images.");
            return;
        }

        imageEntries.push({
            type: "url",
            value: url
        });

        imageUrlInput.value = "";

        refreshImages();
    });

    /* ENTER KEY ADDS URL */

    imageUrlInput.addEventListener("keydown", function (event) {

        if (event.key === "Enter") {
            event.preventDefault();
            addImageUrlButton.click();
        }
    });

    /* OPEN FILE PICKER */

    uploadImagesButton.addEventListener("click", function () {
        imageFileInput.click();
    });

    /* UPLOAD MULTIPLE IMAGES */

    imageFileInput.addEventListener("change", function () {

        const files = Array.from(imageFileInput.files);

        // Reset before synchronizing selected files.
        imageFileInput.value = "";

        for (const file of files) {

            if (imageCount() >= 10) {
                alert("You can upload a maximum of 10 images.");
                break;
            }

            const allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            if (!allowedTypes.includes(file.type)) {

                alert("Only JPG, PNG, and WebP images are allowed.");
                continue;
            }

            if (file.size > 5 * 1024 * 1024) {

                alert("Each image must be 5 MB or smaller.");
                continue;
            }

            imageEntries.push({
                type: "file",
                file: file,
                preview: URL.createObjectURL(file)
            });
        }

        refreshImages();
    });

    /* SUBMIT VALIDATION */

    form.addEventListener("submit", async function (event) {

        if (savingProduct || loadController || (editingProduct && !form.elements.namedItem('product_id').value)) {
            event.preventDefault();
            return;
        }

        const rows = variantRows.querySelectorAll(".variant-row");

        if (!rows.length) {
            event.preventDefault();
            alert("Please add at least one product size.");
            return;
        }

        const seenVariants = new Set();

        for (const row of rows) {

            const size = row.querySelector(
                'input[name="size_capacity[]"]'
            ).value.trim();

            const label = row.querySelector(
                'input[name="variant_label[]"]'
            ).value.trim();

            const key = (size + "|" + label).toLowerCase();

            if (seenVariants.has(key)) {
                event.preventDefault();

                alert("Duplicate size and label combination.");

                return;
            }

            seenVariants.add(key);
        }

        if (!form.checkValidity()) {
            event.preventDefault();
            form.reportValidity();
            return;
        }

        syncImageFields();

        submitButton.disabled = true;
        submitButton.textContent = editingProduct ? 'Saving Changes...' : 'Adding Product...';

        if (editingProduct) {
            event.preventDefault();
            savingProduct = true;
            formBody.inert = true;
            formError.remove();
            form.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(form.action, {
                    method: 'POST', body: new FormData(form), credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'Unable to save this product.');
                window.location.reload();
            } catch (error) {
                formBody.inert = false;
                showFormError(error.message || 'Unable to save this product. Please try again.');
            } finally {
                savingProduct = false;
                formBody.inert = false;
                form.removeAttribute('aria-busy');
                submitButton.disabled = false;
                submitButton.textContent = 'Save Changes';
            }
        }
    });

});
