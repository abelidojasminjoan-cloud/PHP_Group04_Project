/* =========================================================
   PUREVIA ADMIN ORDERS
========================================================= */

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       ELEMENTS
    ====================================================== */

    const searchInput =
        document.getElementById('orderSearch');

    const statusFilter =
        document.getElementById('orderStatusFilter');

    const paymentFilter =
        document.getElementById('paymentStatusFilter');

    const orderCount =
        document.getElementById('orderCount');

    const noOrdersRow =
        document.getElementById('noOrdersRow');

    const orderRows =
        Array.from(
            document.querySelectorAll('.order-row')
        );

    const statusSelects =
        document.querySelectorAll(
            '.order-status-select'
        );


    /* =====================================================
       FILTER ORDERS
    ====================================================== */

    function filterOrders() {

        const searchValue =
            searchInput
                ? searchInput.value
                    .trim()
                    .toLowerCase()
                : '';

        const statusValue =
            statusFilter
                ? statusFilter.value.toLowerCase()
                : 'all';

        const paymentValue =
            paymentFilter
                ? paymentFilter.value.toLowerCase()
                : 'all';


        let visibleCount = 0;


        orderRows.forEach(function (row) {

            const orderId =
                (row.dataset.orderId || '')
                    .toLowerCase();

            const customer =
                (row.dataset.customer || '')
                    .toLowerCase();

            const currentStatusSelect =
                row.querySelector(
                    '.order-status-select'
                );

            const currentStatus =
                currentStatusSelect
                    ? currentStatusSelect.value
                        .toLowerCase()
                    : (row.dataset.status || '')
                        .toLowerCase();

            const paymentStatus =
                (row.dataset.payment || '')
                    .toLowerCase();


            /* SEARCH */

            const matchesSearch =
                searchValue === '' ||
                orderId.includes(searchValue) ||
                customer.includes(searchValue);


            /* ORDER STATUS */

            const matchesStatus =
                statusValue === 'all' ||
                currentStatus === statusValue;


            /* PAYMENT */

            const matchesPayment =
                paymentValue === 'all' ||
                paymentStatus === paymentValue;


            /* FINAL */

            const shouldShow =
                matchesSearch &&
                matchesStatus &&
                matchesPayment;


            if (shouldShow) {

                row.hidden = false;

                visibleCount++;

            } else {

                row.hidden = true;

            }

        });


        /* ===============================================
           UPDATE COUNT
        ================================================ */

        if (orderCount) {
            orderCount.textContent =
                visibleCount;
        }


        /* ===============================================
           NO RESULTS
        ================================================ */

        if (noOrdersRow) {

            noOrdersRow.hidden =
                visibleCount !== 0;

        }

    }


    /* =====================================================
       SEARCH EVENT
    ====================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            filterOrders
        );

    }


    /* =====================================================
       STATUS FILTER
    ====================================================== */

    if (statusFilter) {

        statusFilter.addEventListener(
            'change',
            filterOrders
        );

    }


    /* =====================================================
       PAYMENT FILTER
    ====================================================== */

    if (paymentFilter) {

        paymentFilter.addEventListener(
            'change',
            filterOrders
        );

    }


    /* =====================================================
       ORDER STATUS CHANGE
    ====================================================== */

    statusSelects.forEach(function (select) {

        select.addEventListener(
            'change',
            function () {

                const row =
                    select.closest('.order-row');

                if (!row) {
                    return;
                }


                const saveButton =
                    row.querySelector(
                        '.order-save-button'
                    );

                const emptySave =
                    row.querySelector(
                        '.order-save-empty'
                    );


                const originalValue =
                    select.dataset.originalValue;

                const currentValue =
                    select.value;


                /* =======================================
                   CHANGED
                ======================================== */

                if (
                    currentValue !==
                    originalValue
                ) {

                    select.classList.add(
                        'changed'
                    );

                    if (saveButton) {
                        saveButton.hidden = false;
                    }

                    if (emptySave) {
                        emptySave.hidden = true;
                    }

                }


                /* =======================================
                   BACK TO ORIGINAL
                ======================================== */

                else {

                    select.classList.remove(
                        'changed'
                    );

                    if (saveButton) {
                        saveButton.hidden = true;
                    }

                    if (emptySave) {
                        emptySave.hidden = false;
                    }

                }


                /*
                 * Re-run filters because the user may
                 * currently be filtering by order status.
                 */

                filterOrders();

            }
        );

    });


    /* =====================================================
       SAVE BUTTON
    ====================================================== */

    const saveButtons =
        document.querySelectorAll(
            '.order-save-button'
        );


    saveButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                const row =
                    button.closest('.order-row');

                if (!row) {
                    return;
                }


                const select =
                    row.querySelector(
                        '.order-status-select'
                    );

                if (!select) {
                    return;
                }


                const orderId =
                    button.dataset.orderId;

                const newStatus =
                    select.value;


                /*
                 * The actual database update will be
                 * connected to:
                 *
                 * actions/admin/orders/update-status.php
                 *
                 * For now this preserves the UI behavior.
                 */

                console.log(
                    'Order ID:',
                    orderId
                );

                console.log(
                    'New status:',
                    newStatus
                );


                select.dataset.originalValue =
                    newStatus;

                row.dataset.status =
                    newStatus;

                select.classList.remove(
                    'changed'
                );

                button.hidden = true;


                const emptySave =
                    row.querySelector(
                        '.order-save-empty'
                    );

                if (emptySave) {
                    emptySave.hidden = false;
                }


                filterOrders();

            }
        );

    });


    /* =====================================================
       INITIAL FILTER
    ====================================================== */

    filterOrders();

});


/* =========================================================
   PUREVIA PAGINATION
   ADMIN AND STAFF
   10 RECORDS PER PAGE

   WORKS WITH:
   - Orders
   - Payments
   - Products
   - Inventory

   NO NEW FILES REQUIRED
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    const PAGE_SIZE = 10;

    const configs = [
        {
            table: '.orders-table',
            wrapper: '.orders-table-wrapper',
            row: '.order-row',
            search: 'orderSearch',
            filter1: 'orderStatusFilter',
            filter2: 'paymentStatusFilter',
            type: 'orders'
        },
        {
            table: '.payments-table',
            wrapper: '.payments-table-wrapper',
            row: '.payment-row',
            search: 'paymentSearch',
            filter1: 'paymentMethodFilter',
            filter2: 'paymentStatusFilter',
            type: 'payments'
        },
        {
            table: '.products-table',
            wrapper: '.products-table-wrapper',
            row: '.product-row',
            search: 'productSearch',
            filter1: 'categoryFilter',
            filter2: 'statusFilter',
            type: 'products'
        },
        {
            table: '.inventory-table',
            wrapper: '.inventory-table-wrapper',
            row: '.inventory-row',
            search: 'inventorySearch',
            filter1: 'inventoryCategoryFilter',
            filter2: 'inventoryStockFilter',
            type: 'inventory'
        }
    ];

    const config = configs.find(function (item) {
        return document.querySelector(item.table);
    });

    if (!config) return;

    const wrapper = document.querySelector(config.wrapper);

    if (!wrapper) return;

    const rows = Array.from(
        document.querySelectorAll(config.row)
    );

    const searchInput = document.getElementById(config.search);
    const firstFilter = document.getElementById(config.filter1);
    const secondFilter = document.getElementById(config.filter2);

    let currentPage = 1;

    /* ==========================================
       CREATE PAGINATION CONTAINER
    ========================================== */

    const pagination = document.createElement('nav');

    pagination.className = 'pv-pagination';
    pagination.setAttribute(
        'aria-label',
        config.type + ' pagination'
    );

    Object.assign(pagination.style, {
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
        flexWrap: 'wrap',
        gap: '8px',
        margin: '20px 0',
        fontFamily: 'Poppins, Arial, sans-serif'
    });

    wrapper.insertAdjacentElement(
        'afterend',
        pagination
    );

    /* ==========================================
       CHECK WHICH ROWS MATCH THE FILTERS
    ========================================== */

    function matchesFilters(row) {

        const searchValue = searchInput
            ? searchInput.value.trim().toLowerCase()
            : '';

        const firstValue = firstFilter
            ? firstFilter.value.toLowerCase()
            : 'all';

        const secondValue = secondFilter
            ? secondFilter.value.toLowerCase()
            : 'all';

        const searchableText = (
            row.dataset.search || ''
        ).toLowerCase();

        let firstRowValue = '';
        let secondRowValue = '';

        if (config.type === 'orders') {

            const statusSelect = row.querySelector(
                '.order-status-select'
            );

            firstRowValue = statusSelect
                ? statusSelect.value.toLowerCase()
                : (row.dataset.status || '').toLowerCase();

            secondRowValue = (
                row.dataset.payment || ''
            ).toLowerCase();

        } else if (config.type === 'payments') {

            firstRowValue = (
                row.dataset.method || ''
            ).toLowerCase();

            secondRowValue = (
                row.dataset.status || ''
            ).toLowerCase();

        } else if (config.type === 'products') {

            firstRowValue = (
                row.dataset.category || ''
            ).toLowerCase();

            secondRowValue = (
                row.dataset.status || ''
            ).toLowerCase();

        } else if (config.type === 'inventory') {

            firstRowValue = (
                row.dataset.category || ''
            ).toLowerCase();

            secondRowValue = (
                row.dataset.stockLevel || ''
            ).toLowerCase();

        }

        let matchesSearch =
            searchValue === '' ||
            searchableText.includes(searchValue);

        if (config.type === 'orders') {

            const orderId = (
                row.dataset.orderId || ''
            ).toLowerCase();

            const customer = (
                row.dataset.customer || ''
            ).toLowerCase();

            matchesSearch =
                searchValue === '' ||
                orderId.includes(searchValue) ||
                customer.includes(searchValue);
        }

        const matchesFirst =
            firstValue === 'all' ||
            firstRowValue === firstValue;

        const matchesSecond =
            secondValue === 'all' ||
            secondRowValue === secondValue;

        return (
            matchesSearch &&
            matchesFirst &&
            matchesSecond
        );
    }

    /* ==========================================
       CREATE PAGINATION BUTTON
    ========================================== */

    function createButton(
        label,
        targetPage,
        disabled = false,
        active = false
    ) {

        const button = document.createElement('button');

        button.type = 'button';
        button.textContent = label;
        button.disabled = disabled;

        if (active) {
            button.setAttribute(
                'aria-current',
                'page'
            );
        }

        button.setAttribute(
            'aria-label',
            /^[0-9]+$/.test(label)
                ? 'Page ' + label
                : label + ' page'
        );

        Object.assign(button.style, {
            minWidth: '38px',
            minHeight: '38px',
            padding: '8px 12px',
            border: '1px solid #bdc8b8',
            borderRadius: '8px',
            background: active ? '#53634b' : '#fffdf9',
            color: active ? '#ffffff' : '#40523b',
            fontSize: '12px',
            fontWeight: '600',
            cursor: disabled ? 'not-allowed' : 'pointer',
            opacity: disabled ? '0.45' : '1'
        });

        button.addEventListener('click', function () {

            if (disabled || currentPage === targetPage) {
                return;
            }

            currentPage = targetPage;
            renderPagination();

        });

        return button;
    }

    /* ==========================================
       RENDER PAGINATED ROWS
    ========================================== */

    function renderPagination() {

        const matchingRows = rows.filter(matchesFilters);

        const totalRecords = matchingRows.length;

        const totalPages = Math.max(
            1,
            Math.ceil(totalRecords / PAGE_SIZE)
        );

        currentPage = Math.max(
            1,
            Math.min(currentPage, totalPages)
        );

        const start = (currentPage - 1) * PAGE_SIZE;
        const end = start + PAGE_SIZE;

        const pageRows = new Set(
            matchingRows.slice(start, end)
        );

        rows.forEach(function (row) {

            const visible = pageRows.has(row);

            row.hidden = !visible;
            row.style.display = visible ? '' : 'none';

        });

        /* ======================================
           NO MATCHES
        ====================================== */

        const emptyIds = {
            orders: 'noOrdersRow',
            payments: 'noPaymentsRow',
            products: 'noProductsFound',
            inventory: 'noInventoryFound'
        };

        const emptyRow = document.getElementById(
            emptyIds[config.type]
        );

        if (emptyRow) {

            const isEmpty = totalRecords === 0;

            emptyRow.hidden = !isEmpty;
            emptyRow.style.display = isEmpty ? 'table-row' : 'none';

        }

        /* ======================================
           CLEAR BUTTONS
        ====================================== */

        pagination.replaceChildren();

        if (totalPages <= 1) {

            pagination.hidden = true;
            pagination.style.display = 'none';
            return;
        }

        pagination.hidden = false;
        pagination.style.display = 'flex';

        /* ======================================
           PREVIOUS BUTTON
        ====================================== */

        pagination.appendChild(
            createButton(
                'Previous',
                currentPage - 1,
                currentPage === 1
            )
        );

        /* ======================================
           PAGE NUMBER BUTTONS
        ====================================== */

        const firstPage = Math.max(
            1,
            Math.min(currentPage - 2, totalPages - 4)
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
                createButton(
                    String(page),
                    page,
                    false,
                    page === currentPage
                )
            );

        }

        /* ======================================
           NEXT BUTTON
        ====================================== */

        pagination.appendChild(
            createButton(
                'Next',
                currentPage + 1,
                currentPage === totalPages
            )
        );

    }

    /* ==========================================
       RESET TO FIRST PAGE AFTER SEARCH/FILTER
    ========================================== */

    function resetPagination() {

        currentPage = 1;
        renderPagination();

    }

    /* ==========================================
       SEARCH AND FILTER LISTENERS
    ========================================== */

    [
        searchInput,
        firstFilter,
        secondFilter
    ].forEach(function (element) {

        if (!element) return;

        element.addEventListener(
            'input',
            resetPagination
        );

        element.addEventListener(
            'change',
            resetPagination
        );

    });

    /* ==========================================
       ORDER STATUS CHANGE
    ========================================== */

    if (config.type === 'orders') {

        rows.forEach(function (row) {

            const select = row.querySelector(
                '.order-status-select'
            );

            if (select) {

                select.addEventListener(
                    'change',
                    resetPagination
                );

            }

        });

    }

    /* ==========================================
       PAYMENT STATUS UPDATE
    ========================================== */

    if (
        config.type === 'payments' &&
        typeof MutationObserver !== 'undefined'
    ) {

        const observer = new MutationObserver(function () {
            renderPagination();
        });

        rows.forEach(function (row) {

            observer.observe(row, {
                attributes: true,
                attributeFilter: ['data-status']
            });

        });

    }

    /* ==========================================
       INITIAL PAGINATION
    ========================================== */

    renderPagination();

});
