/* =========================================================
   PUREVIA ADMIN INGREDIENTS
   ADD/EDIT MODAL + SEARCH + FILTER + DELETE
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    // Ingredient-only modal handling; product_form.js is not loaded or modified.
    const ingredientModal = document.getElementById('addIngredientModal');
    const ingredientForm = document.getElementById('addIngredientForm');
    const ingredientOpener = document.getElementById('openAddIngredientModal');

    if (ingredientModal && ingredientForm && ingredientOpener) {
        const nameInput = document.getElementById('ingredientName');
        const descriptionInput = document.getElementById('ingredientDescription');
        const commonInInput = document.getElementById('ingredientCommonIn');
        const subtitle = document.getElementById('ingredientFormSubtitle');
        const submitButton = ingredientForm.querySelector('[type="submit"]');
        const ingredientIdInput = ingredientForm.elements.namedItem('ingredient_id');
        const ingredientActionInput = ingredientForm.elements.namedItem('ingredient_action');
        const ingredientTitle = document.getElementById('addIngredientTitle');
        let previousFocus = null;
        let backgroundElements = [];
        let saving = false;

        function setFieldError(input, errorId, message) {
            const label = document.getElementById(errorId);
            label.textContent = message;
            label.hidden = !message;
            input.setAttribute('aria-invalid', message ? 'true' : 'false');
        }

        function openIngredientModal(resetForm, ingredient = null, trigger = null) {
            previousFocus = trigger || (document.activeElement === document.body ? ingredientOpener : document.activeElement);
            if (resetForm) {
                ingredientForm.reset();
                // A server validation response can contain old default values.
                ingredientActionInput.value = ingredient ? 'update' : 'create';
                ingredientIdInput.value = ingredient ? ingredient.id : '';
                nameInput.value = ingredient ? ingredient.name : '';
                descriptionInput.value = ingredient ? ingredient.description : '';
                commonInInput.value = ingredient ? ingredient.commonIn : '';
                setFieldError(nameInput, 'ingredientNameError', '');
                setFieldError(descriptionInput, 'ingredientDescriptionError', '');
                setFieldError(commonInInput, 'ingredientCommonInError', '');
                document.getElementById('ingredientFormError').hidden = true;
            }
            const editing = ingredientActionInput.value === 'update';
            ingredientTitle.textContent = editing ? 'Edit Ingredient' : 'Add Ingredient';
            subtitle.hidden = !editing;
            subtitle.textContent = editing ? (ingredient ? ingredient.name : nameInput.value) : '';
            submitButton.textContent = editing ? 'Save Changes' : 'Add Ingredient';
            ingredientModal.classList.add('is-open');
            ingredientModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('form-modal-open');
            backgroundElements = Array.from(document.body.children)
                .filter(element => element !== ingredientModal && !['SCRIPT', 'STYLE'].includes(element.tagName))
                .map(element => ({ element, inert: element.inert }));
            backgroundElements.forEach(({ element }) => { element.inert = true; });
            const firstInvalid = ingredientForm.querySelector('[aria-invalid="true"]');
            (firstInvalid || nameInput).focus();
        }

        function closeIngredientModal() {
            if (saving) return;
            backgroundElements.forEach(({ element, inert }) => { element.inert = inert; });
            backgroundElements = [];
            (previousFocus && previousFocus.isConnected ? previousFocus : ingredientOpener).focus();
            ingredientModal.classList.remove('is-open');
            ingredientModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('form-modal-open');
        }

        ingredientOpener.addEventListener('click', function () { openIngredientModal(true); });
        document.querySelectorAll('.edit-ingredient-button').forEach(function (button) {
            button.addEventListener('click', function () {
                openIngredientModal(true, {
                    id: button.dataset.ingredientId,
                    name: button.dataset.ingredientName,
                    description: button.dataset.ingredientDescription || '',
                    commonIn: button.dataset.ingredientCommonIn || ''
                }, button);
            });
        });
        ingredientModal.querySelectorAll('[data-close-ingredient-modal]').forEach(function (button) {
            button.addEventListener('click', closeIngredientModal);
        });

        ingredientModal.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeIngredientModal();
            }
            if (event.key !== 'Tab') return;
            const focusable = Array.from(ingredientModal.querySelectorAll('button, input, textarea, [tabindex="0"]'))
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

        ingredientForm.addEventListener('submit', function (event) {
            if (saving) {
                event.preventDefault();
                return;
            }
            const name = nameInput.value.trim();
            const description = descriptionInput.value.trim();
            const commonIn = commonInInput.value.trim();
            let nameError = '';
            if (!name) nameError = 'Please enter an ingredient name.';
            else if (Array.from(name).length > 150) nameError = 'Use no more than 150 characters.';
            const descriptionError = Array.from(description).length > 5000
                ? 'Use no more than 5,000 characters.' : '';
            const commonInError = Array.from(commonIn).length > 255
                ? 'Use no more than 255 characters.' : '';
            setFieldError(nameInput, 'ingredientNameError', nameError);
            setFieldError(descriptionInput, 'ingredientDescriptionError', descriptionError);
            setFieldError(commonInInput, 'ingredientCommonInError', commonInError);
            document.getElementById('ingredientFormError').hidden = true;

            if (nameError || descriptionError || commonInError) {
                event.preventDefault();
                (nameError ? nameInput : descriptionError ? descriptionInput : commonInInput).focus();
                return;
            }
            saving = true;
            submitButton.disabled = true;
            submitButton.textContent = ingredientActionInput.value === 'update' ? 'Saving…' : 'Adding…';
            ingredientForm.setAttribute('aria-busy', 'true');
        });

        if (ingredientModal.classList.contains('is-open')) {
            openIngredientModal(false);
        }
    }


    /* =====================================================
       ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById('ingredientSearch');

    const statusFilter =
        document.getElementById('ingredientStatusFilter');

    const ingredientRows =
        document.querySelectorAll('.ingredient-row');

    const noIngredientsFound =
        document.getElementById('noIngredientsFound');



    /* =====================================================
       FILTER INGREDIENTS
    ===================================================== */

    function filterIngredients() {


        /* SEARCH VALUE */

        const searchValue =
            searchInput
                ? searchInput.value.trim().toLowerCase()
                : '';


        /* STATUS VALUE */

        const statusValue =
            statusFilter
                ? statusFilter.value.toLowerCase()
                : 'all';


        let visibleIngredients = 0;


        /* =================================================
           CHECK EACH ROW
        ================================================= */

        ingredientRows.forEach(function (row) {


            const rowSearch =
                (row.dataset.search || '').toLowerCase();


            const rowStatus =
                (row.dataset.status || '').toLowerCase();


            /* SEARCH */

            const matchesSearch =
                searchValue === '' ||
                rowSearch.includes(searchValue);


            /* STATUS */

            const matchesStatus =
                statusValue === 'all' ||
                rowStatus === statusValue;


            /* SHOW / HIDE */

            if (matchesSearch && matchesStatus) {

                row.style.display = '';

                visibleIngredients++;

            } else {

                row.style.display = 'none';

            }

        });


        /* =================================================
           NO RESULTS
        ================================================= */

        if (noIngredientsFound) {

            noIngredientsFound.style.display =
                visibleIngredients === 0
                    ? ''
                    : 'none';

        }

    }


    /* =====================================================
       SEARCH
    ===================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterIngredients
        );

    }


    /* =====================================================
       STATUS FILTER
    ===================================================== */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterIngredients
        );

    }


    // Admin deletion confirmations are handled by admin_delete.js.


    /* =====================================================
       INITIAL FILTER
    ===================================================== */

    filterIngredients();

});
