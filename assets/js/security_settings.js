/* =========================================================
   PUREVIA ADMIN - SECURITY SETTINGS
========================================================= */

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       ELEMENTS
    ====================================================== */

    const searchInput =
        document.getElementById('lockedAccountSearch');

    const statusFilter =
        document.getElementById('lockedStatusFilter');

    const lockedRows =
        document.querySelectorAll('.locked-account-row');

    const noResultsRow =
        document.getElementById('noLockedAccountsRow');

    const accountCount =
        document.getElementById('lockedAccountCount');

    const accountLabel =
        document.getElementById('lockedAccountLabel');


    /* =====================================================
       FILTER LOCKED ACCOUNTS
    ====================================================== */

    function filterLockedAccounts() {

        const searchValue =
            searchInput
                ? searchInput.value.trim().toLowerCase()
                : '';

        const selectedStatus =
            statusFilter
                ? statusFilter.value.toLowerCase()
                : 'all';

        let visibleCount = 0;


        lockedRows.forEach(function (row) {

            const searchableText =
                (row.dataset.search || '').toLowerCase();

            const rowStatus =
                (row.dataset.status || '').toLowerCase();


            /* SEARCH MATCH */

            const matchesSearch =
                searchValue === '' ||
                searchableText.includes(searchValue);


            /* STATUS MATCH */

            const matchesStatus =
                selectedStatus === 'all' ||
                rowStatus === selectedStatus;


            /* FINAL RESULT */

            const shouldShow =
                matchesSearch &&
                matchesStatus;


            row.hidden = !shouldShow;


            if (shouldShow) {
                visibleCount++;
            }

        });


        /* ===============================================
           UPDATE COUNT
        ================================================ */

        if (accountCount) {
            accountCount.textContent = visibleCount;
        }

        if (accountLabel) {

            accountLabel.textContent =
                visibleCount === 1
                    ? 'account'
                    : 'accounts';
        }


        /* ===============================================
           NO RESULTS
        ================================================ */

        if (noResultsRow) {

            noResultsRow.hidden =
                visibleCount !== 0;
        }

    }


    /* =====================================================
       SEARCH EVENT
    ====================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterLockedAccounts
        );

    }


    /* =====================================================
       FILTER EVENT
    ====================================================== */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterLockedAccounts
        );

    }


    /* =====================================================
       UNLOCK CONFIRMATION
    ====================================================== */

    const unlockForms =
        document.querySelectorAll('.unlock-form');


    unlockForms.forEach(function (form) {

        form.addEventListener('submit', function (event) {

            const button =
                form.querySelector(
                    '.unlock-account-button'
                );

            const userName =
                button
                    ? button.dataset.userName
                    : 'this account';


            const confirmed = window.confirm(
                'Unlock ' +
                userName +
                '? Their failed login attempts will be reset.'
            );


            if (!confirmed) {

                event.preventDefault();

                return;
            }


            if (button) {

                button.disabled = true;

                button.textContent =
                    'Unlocking...';
            }

        });

    });


    /* =====================================================
       SECURITY SETTINGS VALIDATION
    ====================================================== */

    const securityForms =
        document.querySelectorAll(
            '.security-settings-card'
        );


    securityForms.forEach(function (form) {

        form.addEventListener('submit', function (event) {

            const numberInputs =
                form.querySelectorAll(
                    'input[type="number"]:not([readonly]):not(:disabled)'
                );


            let valid = true;


            numberInputs.forEach(function (input) {

                const value =
                    Number(input.value);

                const minimum =
                    input.min !== ''
                        ? Number(input.min)
                        : null;

                const maximum =
                    input.max !== ''
                        ? Number(input.max)
                        : null;


                if (
                    Number.isNaN(value) ||
                    (
                        minimum !== null &&
                        value < minimum
                    ) ||
                    (
                        maximum !== null &&
                        value > maximum
                    )
                ) {

                    valid = false;

                    input.focus();
                }

            });


            if (!valid) {

                event.preventDefault();

                window.alert(
                    'Please check the security setting values before saving.'
                );
            }

        });

    });


    /* =====================================================
       INITIAL FILTER
    ====================================================== */

    filterLockedAccounts();

});