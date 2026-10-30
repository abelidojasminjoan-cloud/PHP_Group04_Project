
/* =========================================================
   PUREVIA PAYMENTS
   SEARCH + FILTER + PAGINATION
   STAFF AND ADMIN COMPATIBLE
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    /* =====================================================
       ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById('paymentSearch');

    const methodFilter =
        document.getElementById('paymentMethodFilter');

    const statusFilter =
        document.getElementById('paymentStatusFilter');

    const paymentRows = Array.from(
        document.querySelectorAll('.payment-row')
    );

    const noPaymentsRow =
        document.getElementById('noPaymentsRow');

    const paymentCount =
        document.getElementById('paymentCount');

    const paymentRecordLabel =
        document.getElementById('paymentRecordLabel');

    const tableWrapper =
        document.querySelector('.payments-table-wrapper');

    if (!tableWrapper) {
        return;
    }

    /* =====================================================
       PAGINATION SETTINGS
    ===================================================== */

    const PAYMENTS_PER_PAGE = 10;

    let currentPage = 1;

    /* =====================================================
       CREATE PAGINATION CONTAINER
    ===================================================== */

    const pagination = document.createElement('nav');

    pagination.className = 'pv-payments-pagination';

    pagination.setAttribute(
        'aria-label',
        'Payments pagination'
    );

    Object.assign(pagination.style, {
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        flexWrap: 'wrap',
        gap: '8px',
        padding: '18px 12px',
        fontFamily: 'Poppins, Arial, sans-serif'
    });

    tableWrapper.insertAdjacentElement(
        'afterend',
        pagination
    );

    /* =====================================================
       CREATE PAGINATION BUTTON
    ===================================================== */

    function createPageButton(
        label,
        page,
        disabled = false,
        active = false
    ) {

        const button = document.createElement('button');

        button.type = 'button';
        button.textContent = label;
        button.disabled = disabled;
        button.className = 'pv-page-button';

        button.setAttribute(
            'aria-label',
            /^[0-9]+$/.test(label)
                ? 'Go to page ' + label
                : label + ' page'
        );

        if (active) {
            button.setAttribute('aria-current', 'page');
        }

        Object.assign(button.style, {
            minWidth: '38px',
            minHeight: '38px',
            padding: '8px 12px',
            border: '1px solid #bdc8b8',
            borderRadius: '8px',
            backgroundColor: active
                ? '#53634b'
                : '#fffdf9',
            color: active
                ? '#ffffff'
                : '#40523b',
            fontSize: '12px',
            fontWeight: '600',
            cursor: disabled
                ? 'not-allowed'
                : 'pointer',
            opacity: disabled ? '0.45' : '1'
        });

        button.addEventListener('click', function () {

            if (disabled || page === currentPage) {
                return;
            }

            currentPage = page;

            filterPayments();
        });

        return button;
    }

    /* =====================================================
       SEARCH + FILTER PAYMENTS
    ===================================================== */

    function filterPayments() {

        const searchValue = searchInput
            ? searchInput.value.trim().toLowerCase()
            : '';

        const selectedMethod = methodFilter
            ? methodFilter.value.toLowerCase()
            : 'all';

        const selectedStatus = statusFilter
            ? statusFilter.value.toLowerCase()
            : 'all';

        const matchingPayments = paymentRows.filter(
            function (row) {

                const searchableText = (
                    row.dataset.search || ''
                ).toLowerCase();

                const paymentMethod = (
                    row.dataset.method || ''
                ).toLowerCase();

                const paymentStatus = (
                    row.dataset.status || ''
                ).toLowerCase();

                const matchesSearch =
                    searchValue === '' ||
                    searchableText.includes(searchValue);

                const matchesMethod =
                    selectedMethod === 'all' ||
                    paymentMethod === selectedMethod;

                const matchesStatus =
                    selectedStatus === 'all' ||
                    paymentStatus === selectedStatus;

                return (
                    matchesSearch &&
                    matchesMethod &&
                    matchesStatus
                );
            }
        );

        /* ===============================================
           COUNT MATCHING PAYMENTS
        ================================================ */

        const totalMatches = matchingPayments.length;

        const totalPages = Math.max(
            1,
            Math.ceil(
                totalMatches / PAYMENTS_PER_PAGE
            )
        );

        currentPage = Math.max(
            1,
            Math.min(currentPage, totalPages)
        );

        /* ===============================================
           GET RECORDS FOR CURRENT PAGE
        ================================================ */

        const startIndex =
            (currentPage - 1) * PAYMENTS_PER_PAGE;

        const endIndex =
            startIndex + PAYMENTS_PER_PAGE;

        const visibleRows = new Set(
            matchingPayments.slice(
                startIndex,
                endIndex
            )
        );

        /* ===============================================
           SHOW ONLY CURRENT PAGE
        ================================================ */

        paymentRows.forEach(function (row) {

            const visible = visibleRows.has(row);

            row.hidden = !visible;
            row.style.display = visible ? '' : 'none';
        });

        /* ===============================================
           UPDATE RECORD COUNT
        ================================================ */

        if (paymentCount) {

            paymentCount.textContent =
                totalMatches.toLocaleString();
        }

        if (paymentRecordLabel) {

            paymentRecordLabel.textContent =
                totalMatches === 1
                    ? 'record'
                    : 'records';
        }

        /* ===============================================
           NO PAYMENTS FOUND
        ================================================ */

        if (noPaymentsRow) {

            const isEmpty = totalMatches === 0;

            noPaymentsRow.hidden = !isEmpty;

            noPaymentsRow.style.display =
                isEmpty ? 'table-row' : 'none';
        }

        /* ===============================================
           UPDATE PAGINATION BUTTONS
        ================================================ */

        renderPagination(totalPages);
    }

    /* =====================================================
       RENDER PAGINATION
    ===================================================== */

    function renderPagination(totalPages) {

        pagination.replaceChildren();

        if (totalPages <= 1) {

            pagination.hidden = true;
            pagination.style.display = 'none';

            return;
        }

        pagination.hidden = false;
        pagination.style.display = 'flex';

        /* ===============================================
           PREVIOUS BUTTON
        ================================================ */

        pagination.appendChild(
            createPageButton(
                'Previous',
                currentPage - 1,
                currentPage === 1
            )
        );

        /* ===============================================
           PAGE NUMBER BUTTONS
        ================================================ */

        const firstPage = Math.max(
            1,
            Math.min(
                currentPage - 2,
                totalPages - 4
            )
        );

        const lastPage = Math.min(
            totalPages,
            firstPage + 4
        );

        for (
            let page = firstPage;
            page <= lastPage;
            page++
        ) {

            pagination.appendChild(
                createPageButton(
                    String(page),
                    page,
                    false,
                    page === currentPage
                )
            );
        }

        /* ===============================================
           NEXT BUTTON
        ================================================ */

        pagination.appendChild(
            createPageButton(
                'Next',
                currentPage + 1,
                currentPage === totalPages
            )
        );

        /* ===============================================
           PAGE INFORMATION
        ================================================ */

        const pageInfo = document.createElement('span');

        pageInfo.textContent =
            'Page ' + currentPage +
            ' of ' + totalPages;

        Object.assign(pageInfo.style, {
            marginLeft: '8px',
            fontSize: '12px',
            color: '#66735f'
        });

        pagination.appendChild(pageInfo);
    }

    /* =====================================================
       RESET PAGE WHEN SEARCH OR FILTER CHANGES
    ===================================================== */

    function resetAndFilter() {

        currentPage = 1;

        filterPayments();
    }

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            resetAndFilter
        );
    }

    if (methodFilter) {

        methodFilter.addEventListener(
            'change',
            resetAndFilter
        );
    }

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            resetAndFilter
        );
    }

    /* =====================================================
       VERIFY / REJECT PAYMENT BUTTONS
    ===================================================== */

    const actionButtons =
        document.querySelectorAll(
            '.payment-action-button'
        );

    actionButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const paymentId =
                button.dataset.paymentId;

            const action =
                button.dataset.action;

            if (!paymentId || !action) {
                return;
            }

            /* ===========================================
               CONFIRM ACTION
               Matches PHP values: paid / failed
            ============================================ */

            let message = '';

            if (
                action === 'paid' ||
                action === 'verified'
            ) {

                message = 'Verify this payment?';

            } else if (
                action === 'failed' ||
                action === 'rejected'
            ) {

                message = 'Reject this payment?';
            }

            if (
                message !== '' &&
                !window.confirm(message)
            ) {
                return;
            }

            updatePaymentStatus(
                paymentId,
                action,
                button
            );
        });
    });

    /* =====================================================
       UPDATE PAYMENT STATUS IN DATABASE
    ===================================================== */

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

        rowButtons.forEach(function (button) {
            button.disabled = true;
        });

        try {

            const formData = new FormData();

            formData.append(
                'payment_id',
                paymentId
            );

            formData.append(
                'status',
                status
            );

            /* ===========================================
               EXISTING PHP UPDATE ENDPOINT
            ============================================ */

            const response = await fetch(
                'update_payment_status.php',
                {
                    method: 'POST',
                    body: formData
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {

                throw new Error(
                    data.message ||
                    'Unable to update payment.'
                );
            }

            /* ===========================================
               UPDATE PAYMENT STATUS BADGE
            ============================================ */

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
                    status === 'paid'
                        ? 'VERIFIED'
                        : status.toUpperCase();
            }

            /* ===========================================
               UPDATE ROW DATA
            ============================================ */

            row.dataset.status = status;

            /* ===========================================
               REMOVE ACTION BUTTONS
            ============================================ */

            const actionContainer =
                row.querySelector('.payment-actions');

            if (actionContainer) {

                const noAction =
                    document.createElement('span');

                noAction.className = 'payment-no-action';
                noAction.textContent = '—';

                actionContainer.replaceChildren(noAction);
            }

            /* ===========================================
               REAPPLY SEARCH + PAGINATION
            ============================================ */

            filterPayments();

        } catch (error) {

            alert(
                error.message ||
                'Something went wrong.'
            );

            rowButtons.forEach(function (button) {
                button.disabled = false;
            });
        }
    }

    /* =====================================================
       INITIAL DISPLAY
    ===================================================== */

    filterPayments();

});
