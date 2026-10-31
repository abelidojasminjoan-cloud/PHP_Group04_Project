
/* =========================================================
   PUREVIA ADMIN + STAFF INVENTORY
   SEARCH + CATEGORY FILTER + STOCK FILTER
   PAGINATION: 10 RECORDS PER PAGE
========================================================= */

document.addEventListener('DOMContentLoaded', function () {

    /* =====================================================
       ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById('inventorySearch');

    const categoryFilter =
        document.getElementById('inventoryCategoryFilter');

    const stockFilter =
        document.getElementById('inventoryStockFilter');

    const inventoryRows = Array.from(
        document.querySelectorAll('.inventory-row')
    );

    const noResultsRow =
        document.getElementById('noInventoryFound');

    const tableWrapper =
        document.querySelector('.inventory-table-wrapper');

    const inventoryRecordCount =
        document.getElementById('inventoryRecordCount');

    const inventoryRecordLabel =
        document.getElementById('inventoryRecordLabel');

    if (!tableWrapper) {
        return;
    }

    /* =====================================================
       PAGINATION SETTINGS
    ===================================================== */

    const RECORDS_PER_PAGE = 10;

    let currentPage = 1;

    /* =====================================================
       CREATE PAGINATION CONTAINER
    ===================================================== */

    const pagination = document.createElement('nav');

    pagination.className = 'pv-inventory-pagination';

    pagination.setAttribute(
        'aria-label',
        'Inventory pagination'
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

            if (
                disabled ||
                page === currentPage
            ) {
                return;
            }

            currentPage = page;

            filterInventory();
        });

        return button;
    }

    /* =====================================================
       SEARCH + FILTER + PAGINATION
    ===================================================== */

    function filterInventory() {

        /* ===============================================
           SEARCH VALUE
        ================================================ */

        const searchValue = searchInput
            ? searchInput.value.trim().toLowerCase()
            : '';

        /* ===============================================
           CATEGORY FILTER
        ================================================ */

        const categoryValue = categoryFilter
            ? categoryFilter.value.toLowerCase()
            : 'all';

        /* ===============================================
           STOCK FILTER
        ================================================ */

        const stockValue = stockFilter
            ? stockFilter.value.toLowerCase()
            : 'all';

        /* ===============================================
           FILTER ALL INVENTORY RECORDS
        ================================================ */

        const matchingRows = inventoryRows.filter(
            function (row) {

                const searchableText = (
                    row.dataset.search || ''
                ).toLowerCase();

                const rowCategory = (
                    row.dataset.category || ''
                ).toLowerCase();

                const rowStockLevel = (
                    row.dataset.stockLevel || ''
                ).toLowerCase();

                const matchesSearch =
                    searchValue === '' ||
                    searchableText.includes(searchValue);

                const matchesCategory =
                    categoryValue === 'all' ||
                    rowCategory === categoryValue;

                const matchesStock =
                    stockValue === 'all' ||
                    rowStockLevel === stockValue;

                return (
                    matchesSearch &&
                    matchesCategory &&
                    matchesStock
                );
            }
        );

        /* ===============================================
           COUNT MATCHING RECORDS
        ================================================ */

        const totalMatches = matchingRows.length;

        const totalPages = Math.max(
            1,
            Math.ceil(
                totalMatches / RECORDS_PER_PAGE
            )
        );

        currentPage = Math.max(
            1,
            Math.min(currentPage, totalPages)
        );

        /* ===============================================
           CURRENT PAGE RANGE
        ================================================ */

        const startIndex =
            (currentPage - 1) * RECORDS_PER_PAGE;

        const endIndex =
            startIndex + RECORDS_PER_PAGE;

        const visibleRows = new Set(
            matchingRows.slice(
                startIndex,
                endIndex
            )
        );

        /* ===============================================
           SHOW CURRENT PAGE ONLY
        ================================================ */

        inventoryRows.forEach(function (row) {

            const visible = visibleRows.has(row);

            row.hidden = !visible;
            row.style.display = visible ? '' : 'none';

        });

        /* ===============================================
           UPDATE STAFF RECORD COUNT
        ================================================ */

        if (inventoryRecordCount) {

            inventoryRecordCount.textContent =
                totalMatches.toLocaleString();
        }

        if (inventoryRecordLabel) {

            inventoryRecordLabel.textContent =
                totalMatches === 1
                    ? 'record'
                    : 'records';
        }

        /* ===============================================
           NO RESULTS ROW
        ================================================ */

        if (noResultsRow) {

            const isEmpty = totalMatches === 0;

            noResultsRow.hidden = !isEmpty;

            noResultsRow.style.display =
                isEmpty ? 'table-row' : 'none';
        }

        /* ===============================================
           RENDER PAGINATION
        ================================================ */

        renderPagination(totalPages);
    }

    /* =====================================================
       RENDER PAGINATION BUTTONS
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
           NUMBERED PAGES
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
       RESET PAGE WHEN SEARCH/FILTER CHANGES
    ===================================================== */

    function resetAndFilter() {

        currentPage = 1;

        filterInventory();
    }

    /* =====================================================
       SEARCH EVENT
    ===================================================== */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            resetAndFilter
        );
    }

    /* =====================================================
       CATEGORY FILTER EVENT
    ===================================================== */

    if (categoryFilter) {

        categoryFilter.addEventListener(
            'change',
            resetAndFilter
        );
    }

    /* =====================================================
       STOCK FILTER EVENT
    ===================================================== */

    if (stockFilter) {

        stockFilter.addEventListener(
            'change',
            resetAndFilter
        );
    }

    /* =====================================================
       INITIAL DISPLAY
    ===================================================== */

    filterInventory();

});
