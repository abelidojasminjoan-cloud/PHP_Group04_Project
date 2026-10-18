/* =========================================================
   PUREVIA ADMIN SKIN CONCERNS
   ADD/EDIT MODAL + SEARCH + FILTER + DELETE
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    // Concern-only modal handling; product_form.js is not loaded or modified.
    const concernModal = document.getElementById('addConcernModal');
    const concernForm = document.getElementById('addConcernForm');
    const concernOpener = document.getElementById('openAddConcernModal');

    if (concernModal && concernForm && concernOpener) {
        const nameInput = document.getElementById('concernName');
        const descriptionInput = document.getElementById('concernDescription');
        const subtitle = document.getElementById('concernFormSubtitle');
        const submitButton = concernForm.querySelector('[type="submit"]');
        const concernIdInput = concernForm.elements.namedItem('concern_id');
        const concernActionInput = concernForm.elements.namedItem('concern_action');
        const concernTitle = document.getElementById('addConcernTitle');
        let previousFocus = null;
        let backgroundElements = [];
        let saving = false;

        function setFieldError(input, errorId, message) {
            const label = document.getElementById(errorId);
            label.textContent = message;
            label.hidden = !message;
            input.setAttribute('aria-invalid', message ? 'true' : 'false');
        }

        function openConcernModal(resetForm, concern = null, trigger = null) {
            previousFocus = trigger || (document.activeElement === document.body ? concernOpener : document.activeElement);
            if (resetForm) {
                concernForm.reset();
                // A server validation response can contain old default values.
                concernActionInput.value = concern ? 'update' : 'create';
                concernIdInput.value = concern ? concern.id : '';
                nameInput.value = concern ? concern.name : '';
                descriptionInput.value = concern ? concern.description : '';
                setFieldError(nameInput, 'concernNameError', '');
                setFieldError(descriptionInput, 'concernDescriptionError', '');
                document.getElementById('concernFormError').hidden = true;
            }
            const editing = concernActionInput.value === 'update';
            concernTitle.textContent = editing ? 'Edit Skin Concern' : 'Add Skin Concern';
            subtitle.hidden = !editing;
            subtitle.textContent = editing ? (concern ? concern.name : nameInput.value) : '';
            submitButton.textContent = editing ? 'Save Changes' : 'Add Concern';
            concernModal.classList.add('is-open');
            concernModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('form-modal-open');
            backgroundElements = Array.from(document.body.children)
                .filter(element => element !== concernModal && !['SCRIPT', 'STYLE'].includes(element.tagName))
                .map(element => ({ element, inert: element.inert }));
            backgroundElements.forEach(({ element }) => { element.inert = true; });
            const firstInvalid = concernForm.querySelector('[aria-invalid="true"]');
            (firstInvalid || nameInput).focus();
        }

        function closeConcernModal() {
            if (saving) return;
            backgroundElements.forEach(({ element, inert }) => { element.inert = inert; });
            backgroundElements = [];
            (previousFocus && previousFocus.isConnected ? previousFocus : concernOpener).focus();
            concernModal.classList.remove('is-open');
            concernModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('form-modal-open');
        }

        concernOpener.addEventListener('click', function () { openConcernModal(true); });
        document.querySelectorAll('.edit-concern-button').forEach(function (button) {
            button.addEventListener('click', function () {
                openConcernModal(true, {
                    id: button.dataset.concernId,
                    name: button.dataset.concernName,
                    description: button.dataset.concernDescription || ''
                }, button);
            });
        });
        concernModal.querySelectorAll('[data-close-concern-modal]').forEach(function (button) {
            button.addEventListener('click', closeConcernModal);
        });

        concernModal.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeConcernModal();
            }
            if (event.key !== 'Tab') return;
            const focusable = Array.from(concernModal.querySelectorAll('button, input, textarea, [tabindex="0"]'))
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

        concernForm.addEventListener('submit', function (event) {
            if (saving) {
                event.preventDefault();
                return;
            }
            const name = nameInput.value.trim();
            const description = descriptionInput.value.trim();
            let nameError = '';
            if (!name) nameError = 'Please enter a concern name.';
            else if (Array.from(name).length > 100) nameError = 'Use no more than 100 characters.';
            const descriptionError = Array.from(description).length > 5000
                ? 'Use no more than 5,000 characters.' : '';
            setFieldError(nameInput, 'concernNameError', nameError);
            setFieldError(descriptionInput, 'concernDescriptionError', descriptionError);
            document.getElementById('concernFormError').hidden = true;

            if (nameError || descriptionError) {
                event.preventDefault();
                (nameError ? nameInput : descriptionInput).focus();
                return;
            }
            saving = true;
            submitButton.disabled = true;
            submitButton.textContent = concernActionInput.value === 'update' ? 'Saving…' : 'Adding…';
            concernForm.setAttribute('aria-busy', 'true');
        });

        if (concernModal.classList.contains('is-open')) {
            openConcernModal(false);
        }
    }


    /* =====================================================
       ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById('skinConcernSearch');

    const statusFilter =
        document.getElementById('skinConcernStatusFilter');

    const concernRows =
        document.querySelectorAll('.skin-concern-row');

    const noResultsRow =
        document.getElementById('noSkinConcernsFound');



    /* =====================================================
       SEARCH + FILTER
    ===================================================== */

    function filterSkinConcerns() {


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


        let visibleRows = 0;


        /* =================================================
           CHECK EVERY ROW
        ================================================= */

        concernRows.forEach(function (row) {


            const searchableText =
                (row.dataset.search || '')
                    .toLowerCase();


            const rowStatus =
                (row.dataset.status || '')
                    .toLowerCase();


            /* SEARCH MATCH */

            const matchesSearch =
                searchValue === '' ||
                searchableText.includes(searchValue);


            /* STATUS MATCH */

            const matchesStatus =
                statusValue === 'all' ||
                rowStatus === statusValue;


            /* SHOW / HIDE */

            if (matchesSearch && matchesStatus) {

                row.style.display = '';

                visibleRows++;

            } else {

                row.style.display = 'none';

            }

        });


        /* =================================================
           NO RESULTS MESSAGE
        ================================================= */

        if (noResultsRow) {

            noResultsRow.style.display =
                visibleRows === 0
                    ? ''
                    : 'none';

        }

    }


    /* =====================================================
       LIVE SEARCH
    ===================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterSkinConcerns
        );

    }


    /* =====================================================
       STATUS FILTER
    ===================================================== */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterSkinConcerns
        );

    }


    // Admin deletion confirmations are handled by admin_delete.js.


    /* =====================================================
       INITIAL FILTER
    ===================================================== */

    filterSkinConcerns();

});
