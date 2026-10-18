/* =========================================================
   PUREVIA ADMIN CATEGORIES
   ADD/EDIT MODAL + SEARCH + FILTER + DELETE
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    // Category-only modal handling; product_form.js is not loaded or modified.
    const categoryModal = document.getElementById('addCategoryModal');
    const categoryForm = document.getElementById('addCategoryForm');
    const categoryOpener = document.getElementById('openAddCategoryModal');

    if (categoryModal && categoryForm && categoryOpener) {
        const nameInput = document.getElementById('categoryName');
        const descriptionInput = document.getElementById('categoryDescription');
        const submitButton = categoryForm.querySelector('[type="submit"]');
        const categoryIdInput = categoryForm.elements.namedItem('category_id');
        const categoryActionInput = categoryForm.elements.namedItem('category_action');
        const categoryTitle = document.getElementById('addCategoryTitle');
        let previousFocus = null;
        let backgroundElements = [];
        let saving = false;

        function setFieldError(input, errorId, message) {
            const label = document.getElementById(errorId);
            label.textContent = message;
            label.hidden = !message;
            input.setAttribute('aria-invalid', message ? 'true' : 'false');
        }

        function openCategoryModal(resetForm, category = null, trigger = null) {
            previousFocus = trigger || (document.activeElement === document.body ? categoryOpener : document.activeElement);
            if (resetForm) {
                categoryForm.reset();
                // A server validation response can contain old default values.
                categoryActionInput.value = category ? 'update' : 'create';
                categoryIdInput.value = category ? category.id : '';
                nameInput.value = category ? category.name : '';
                descriptionInput.value = category ? category.description : '';
                setFieldError(nameInput, 'categoryNameError', '');
                setFieldError(descriptionInput, 'categoryDescriptionError', '');
                document.getElementById('categoryFormError').hidden = true;
            }
            const editing = categoryActionInput.value === 'update';
            categoryTitle.textContent = editing ? 'Edit Category' : 'Add Category';
            submitButton.textContent = editing ? 'Save Changes' : 'Add Category';
            categoryModal.classList.add('is-open');
            categoryModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('form-modal-open');
            backgroundElements = Array.from(document.body.children)
                .filter(element => element !== categoryModal && !['SCRIPT', 'STYLE'].includes(element.tagName))
                .map(element => ({ element, inert: element.inert }));
            backgroundElements.forEach(({ element }) => { element.inert = true; });
            const firstInvalid = categoryForm.querySelector('[aria-invalid="true"]');
            (firstInvalid || nameInput).focus();
        }

        function closeCategoryModal() {
            if (saving) return;
            backgroundElements.forEach(({ element, inert }) => { element.inert = inert; });
            backgroundElements = [];
            (previousFocus && previousFocus.isConnected ? previousFocus : categoryOpener).focus();
            categoryModal.classList.remove('is-open');
            categoryModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('form-modal-open');
        }

        categoryOpener.addEventListener('click', function () { openCategoryModal(true); });
        document.querySelectorAll('.edit-category-button').forEach(function (button) {
            button.addEventListener('click', function () {
                openCategoryModal(true, {
                    id: button.dataset.categoryId,
                    name: button.dataset.categoryName,
                    description: button.dataset.categoryDescription || ''
                }, button);
            });
        });
        categoryModal.querySelectorAll('[data-close-category-modal]').forEach(function (button) {
            button.addEventListener('click', closeCategoryModal);
        });

        categoryModal.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeCategoryModal();
            }
            if (event.key !== 'Tab') return;
            const focusable = Array.from(categoryModal.querySelectorAll('button, input, textarea, [tabindex="0"]'))
                .filter(element => !element.disabled && element.getClientRects().length);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        categoryForm.addEventListener('submit', function (event) {
            if (saving) {
                event.preventDefault();
                return;
            }
            const name = nameInput.value.trim();
            const description = descriptionInput.value.trim();
            let nameError = '';
            if (!name) nameError = 'Please enter a category name.';
            else if (Array.from(name).length > 100) nameError = 'Use no more than 100 characters.';
            const descriptionError = Array.from(description).length > 5000
                ? 'Use no more than 5,000 characters.' : '';
            setFieldError(nameInput, 'categoryNameError', nameError);
            setFieldError(descriptionInput, 'categoryDescriptionError', descriptionError);
            document.getElementById('categoryFormError').hidden = true;

            if (nameError || descriptionError) {
                event.preventDefault();
                (nameError ? nameInput : descriptionInput).focus();
                return;
            }
            saving = true;
            submitButton.disabled = true;
            submitButton.textContent = categoryActionInput.value === 'update' ? 'Saving…' : 'Adding…';
            categoryForm.setAttribute('aria-busy', 'true');
        });

        if (categoryModal.classList.contains('is-open')) {
            openCategoryModal(false);
        }
    }

    /* =====================================================
       ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById('categorySearch');

    const statusFilter =
        document.getElementById('categoryStatusFilter');

    const categoryRows =
        document.querySelectorAll('.category-row');

    const noCategoriesFound =
        document.getElementById('noCategoriesFound');



    /* =====================================================
       FILTER CATEGORIES
    ===================================================== */

    function filterCategories() {

        const searchValue =
            searchInput
                ? searchInput.value.trim().toLowerCase()
                : '';

        const statusValue =
            statusFilter
                ? statusFilter.value.toLowerCase()
                : 'all';

        let visibleCategories = 0;


        categoryRows.forEach(function (row) {

            const rowSearch =
                (row.dataset.search || '').toLowerCase();

            const rowStatus =
                (row.dataset.status || '').toLowerCase();


            /* SEARCH MATCH */

            const matchesSearch =
                searchValue === '' ||
                rowSearch.includes(searchValue);


            /* STATUS MATCH */

            const matchesStatus =
                statusValue === 'all' ||
                rowStatus === statusValue;


            /* SHOW / HIDE */

            if (matchesSearch && matchesStatus) {

                row.style.display = '';

                visibleCategories++;

            } else {

                row.style.display = 'none';

            }

        });


        /* =================================================
           NO RESULTS MESSAGE
        ================================================= */

        if (noCategoriesFound) {

            noCategoriesFound.style.display =
                visibleCategories === 0
                    ? ''
                    : 'none';

        }

    }


    /* =====================================================
       SEARCH EVENT
    ===================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterCategories
        );

    }


    /* =====================================================
       STATUS FILTER EVENT
    ===================================================== */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterCategories
        );

    }


    // Admin deletion confirmations are handled by admin_delete.js.


    /* =====================================================
       INITIAL FILTER
    ===================================================== */

    filterCategories();

});
