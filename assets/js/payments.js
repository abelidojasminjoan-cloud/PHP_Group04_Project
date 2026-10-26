/* =========================================================
   PUREVIA ADMIN PAYMENTS
========================================================= */

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       ELEMENTS
    ====================================================== */

    const searchInput =
        document.getElementById('paymentSearch');

    const methodFilter =
        document.getElementById('paymentMethodFilter');

    const statusFilter =
        document.getElementById('paymentStatusFilter');

    const paymentRows =
        document.querySelectorAll('.payment-row');

    const noPaymentsRow =
        document.getElementById('noPaymentsRow');

    const paymentCount =
        document.getElementById('paymentCount');

    const paymentRecordLabel =
        document.getElementById('paymentRecordLabel');


    /* =====================================================
       FILTER PAYMENTS
    ====================================================== */

    function filterPayments() {

        const searchValue =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : '';

        const selectedMethod =
            methodFilter
                ? methodFilter.value.toLowerCase()
                : 'all';

        const selectedStatus =
            statusFilter
                ? statusFilter.value.toLowerCase()
                : 'all';


        let visibleCount = 0;


        paymentRows.forEach(function (row) {

            const searchableText =
                (
                    row.dataset.search || ''
                ).toLowerCase();

            const paymentMethod =
                (
                    row.dataset.method || ''
                ).toLowerCase();

            const paymentStatus =
                (
                    row.dataset.status || ''
                ).toLowerCase();


            /* SEARCH MATCH */

            const matchesSearch =
                searchValue === '' ||
                searchableText.includes(searchValue);


            /* METHOD MATCH */

            const matchesMethod =
                selectedMethod === 'all' ||
                paymentMethod === selectedMethod;


            /* STATUS MATCH */

            const matchesStatus =
                selectedStatus === 'all' ||
                paymentStatus === selectedStatus;


            /* SHOW / HIDE */

            const shouldShow =
                matchesSearch &&
                matchesMethod &&
                matchesStatus;


            row.hidden = !shouldShow;


            if (shouldShow) {
                visibleCount++;
            }

        });


        /* =================================================
           UPDATE RECORD COUNT
        ================================================== */

        if (paymentCount) {
            paymentCount.textContent =
                visibleCount.toLocaleString();
        }

        if (paymentRecordLabel) {

            paymentRecordLabel.textContent =
                visibleCount === 1
                    ? 'record'
                    : 'records';

        }


        /* =================================================
           NO RESULTS
        ================================================== */

        if (noPaymentsRow) {

            noPaymentsRow.hidden =
                visibleCount !== 0;

        }

    }


    /* =====================================================
       SEARCH EVENT
    ====================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterPayments
        );

    }


    /* =====================================================
       METHOD FILTER
    ====================================================== */

    if (methodFilter) {

        methodFilter.addEventListener(
            'change',
            filterPayments
        );

    }


    /* =====================================================
       STATUS FILTER
    ====================================================== */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterPayments
        );

    }


    /* =====================================================
       VERIFY / REJECT BUTTONS
    ====================================================== */

    const actionButtons =
        document.querySelectorAll(
            '.payment-action-button'
        );


    actionButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                const paymentId =
                    button.dataset.paymentId;

                const action =
                    button.dataset.action;


                if (!paymentId || !action) {
                    return;
                }


                /* =========================================
                   CONFIRMATION
                ========================================== */

                let message = '';

                if (action === 'verified') {

                    message =
                        'Verify this payment?';

                } else if (action === 'rejected') {

                    message =
                        'Reject this payment?';

                }


                if (
                    message !== '' &&
                    !window.confirm(message)
                ) {
                    return;
                }


                /* =========================================
                   SEND UPDATE
                ========================================== */

                updatePaymentStatus(
                    paymentId,
                    action,
                    button
                );

            }
        );

    });


    /* =====================================================
       UPDATE PAYMENT STATUS
    ====================================================== */

    async function updatePaymentStatus(
        paymentId,
        status,
        clickedButton
    ) {

        const row =
            clickedButton.closest('.payment-row');

        if (!row) {
            return;
        }


        const rowButtons =
            row.querySelectorAll(
                '.payment-action-button'
            );


        /* DISABLE BUTTONS */

        rowButtons.forEach(function (button) {
            button.disabled = true;
        });


        try {

            const formData =
                new FormData();

            formData.append(
                'payment_id',
                paymentId
            );

            formData.append(
                'status',
                status
            );


            const response =
                await fetch(
                    'update_payment_status.php',
                    {
                        method: 'POST',
                        body: formData
                    }
                );


            const data =
                await response.json();


            if (!response.ok || !data.success) {

                throw new Error(
                    data.message ||
                    'Unable to update payment.'
                );

            }


            /* =============================================
               UPDATE STATUS BADGE
            ============================================== */

            const statusBadge =
                row.querySelector(
                    '.payment-status-badge'
                );


            if (statusBadge) {

                statusBadge.className =
                    'payment-status-badge ' +
                    'payment-status-' +
                    status;

                statusBadge.textContent =
                    status.toUpperCase();

            }


            /* =============================================
               UPDATE DATA ATTRIBUTE
            ============================================== */

            row.dataset.status = status;


            /* =============================================
               REMOVE ACTION BUTTONS
            ============================================== */

            const actionContainer =
                row.querySelector(
                    '.payment-actions'
                );


            if (actionContainer) {

                const noAction = document.createElement('span');
                noAction.className = 'payment-no-action';
                noAction.textContent = '—';
                actionContainer.replaceChildren(noAction);

            }


            /* =============================================
               APPLY CURRENT FILTER AGAIN
            ============================================== */

            filterPayments();


        } catch (error) {

            alert(
                error.message ||
                'Something went wrong.'
            );


            /* RE-ENABLE BUTTONS */

            rowButtons.forEach(function (button) {
                button.disabled = false;
            });

        }

    }


    /* =====================================================
       INITIAL FILTER
    ====================================================== */

    filterPayments();

});
