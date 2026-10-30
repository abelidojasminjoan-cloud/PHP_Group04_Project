
document.addEventListener("DOMContentLoaded", function () {

    /* ==========================================
       PUREVIA PRODUCTS
       SEARCH + FILTER + PAGINATION
       ADMIN AND STAFF
    ========================================== */

    const productSearch =
        document.getElementById("productSearch");

    const categoryFilter =
        document.getElementById("categoryFilter");

    const statusFilter =
        document.getElementById("statusFilter");

    const productRows = Array.from(
        document.querySelectorAll(".product-row")
    );

    const noProductsFound =
        document.getElementById("noProductsFound");

    // Admin uses productCount.
    // Staff uses productRecordCount.
    const productCount =
        document.getElementById("productCount") ||
        document.getElementById("productRecordCount");

    const tableWrapper =
        document.querySelector(".products-table-wrapper");

    if (
        !productSearch ||
        !categoryFilter ||
        !statusFilter ||
        !tableWrapper
    ) {
        return;
    }

    /* ==========================================
       PAGINATION SETTINGS
    ========================================== */

    const PRODUCTS_PER_PAGE = 10;

    let currentPage = 1;

    /* ==========================================
       CREATE PAGINATION AREA
    ========================================== */

    const pagination = document.createElement("nav");

    pagination.className = "pv-products-pagination";

    pagination.setAttribute(
        "aria-label",
        "Products pagination"
    );

    Object.assign(pagination.style, {
        display: "flex",
        justifyContent: "center",
        alignItems: "center",
        flexWrap: "wrap",
        gap: "8px",
        padding: "18px 12px",
        fontFamily: "Poppins, Arial, sans-serif"
    });

    tableWrapper.insertAdjacentElement(
        "afterend",
        pagination
    );

    /* ==========================================
       CREATE PAGE BUTTON
    ========================================== */

    function createPageButton(
        label,
        page,
        disabled = false,
        active = false
    ) {

        const button = document.createElement("button");

        button.type = "button";
        button.textContent = label;
        button.disabled = disabled;

        button.className = "pv-page-button";

        button.setAttribute(
            "aria-label",
            /^[0-9]+$/.test(label)
                ? "Go to page " + label
                : label + " page"
        );

        if (active) {
            button.setAttribute("aria-current", "page");
        }

        Object.assign(button.style, {
            minWidth: "38px",
            minHeight: "38px",
            padding: "8px 12px",
            borderRadius: "8px",
            border: "1px solid #bdc8b8",
            backgroundColor: active
                ? "#53634b"
                : "#fffdf9",
            color: active
                ? "#ffffff"
                : "#40523b",
            fontSize: "12px",
            fontWeight: "600",
            cursor: disabled
                ? "not-allowed"
                : "pointer",
            opacity: disabled ? "0.45" : "1"
        });

        button.addEventListener("click", function () {

            if (disabled || page === currentPage) {
                return;
            }

            currentPage = page;

            filterProducts();
        });

        return button;
    }

    /* ==========================================
       SEARCH + FILTER PRODUCTS
    ========================================== */

    function filterProducts() {

        const searchValue = productSearch.value
            .trim()
            .toLowerCase();

        const categoryValue =
            categoryFilter.value.toLowerCase();

        const statusValue =
            statusFilter.value.toLowerCase();

        const filteredProducts = productRows.filter(
            function (row) {

                const searchData = (
                    row.dataset.search || ""
                ).toLowerCase();

                const category = (
                    row.dataset.category || ""
                ).toLowerCase();

                const status = (
                    row.dataset.status || ""
                ).toLowerCase();

                const matchesSearch =
                    searchData.includes(searchValue);

                const matchesCategory =
                    categoryValue === "all" ||
                    category === categoryValue;

                const matchesStatus =
                    statusValue === "all" ||
                    status === statusValue;

                return (
                    matchesSearch &&
                    matchesCategory &&
                    matchesStatus
                );
            }
        );

        /* ======================================
           TOTAL MATCHING PRODUCTS
        ====================================== */

        const totalMatches = filteredProducts.length;

        const totalPages = Math.max(
            1,
            Math.ceil(
                totalMatches / PRODUCTS_PER_PAGE
            )
        );

        currentPage = Math.min(
            Math.max(currentPage, 1),
            totalPages
        );

        /* ======================================
           DETERMINE CURRENT PAGE ROWS
        ====================================== */

        const startIndex =
            (currentPage - 1) * PRODUCTS_PER_PAGE;

        const endIndex =
            startIndex + PRODUCTS_PER_PAGE;

        const visibleRows = new Set(
            filteredProducts.slice(
                startIndex,
                endIndex
            )
        );

        /* ======================================
           SHOW ONLY CURRENT PAGE
        ====================================== */

        productRows.forEach(function (row) {

            const visible = visibleRows.has(row);

            row.hidden = !visible;
            row.style.display = visible ? "" : "none";
        });

        /* ======================================
           EMPTY RESULTS MESSAGE
        ====================================== */

        if (noProductsFound) {

            noProductsFound.hidden =
                totalMatches !== 0;

            noProductsFound.style.display =
                totalMatches === 0
                    ? "table-row"
                    : "none";
        }

        /* ======================================
           UPDATE PRODUCT COUNT
        ====================================== */

        if (productCount) {

            const isStaffCount =
                productCount.id === "productRecordCount";

            productCount.textContent =
                totalMatches.toLocaleString() +
                (isStaffCount
                    ? (totalMatches === 1
                        ? " record"
                        : " records")
                    : "");
        }

        /* ======================================
           RENDER PAGINATION
        ====================================== */

        renderPagination(
            totalMatches,
            totalPages
        );
    }

    /* ==========================================
       RENDER PAGINATION BUTTONS
    ========================================== */

    function renderPagination(
        totalMatches,
        totalPages
    ) {

        pagination.replaceChildren();

        if (totalPages <= 1) {

            pagination.hidden = true;
            pagination.style.display = "none";

            return;
        }

        pagination.hidden = false;
        pagination.style.display = "flex";

        /* ======================================
           PREVIOUS BUTTON
        ====================================== */

        pagination.appendChild(
            createPageButton(
                "Previous",
                currentPage - 1,
                currentPage === 1
            )
        );

        /* ======================================
           PAGE NUMBERS
        ====================================== */

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

        /* ======================================
           NEXT BUTTON
        ====================================== */

        pagination.appendChild(
            createPageButton(
                "Next",
                currentPage + 1,
                currentPage === totalPages
            )
        );

        /* ======================================
           PAGE INFORMATION
        ====================================== */

        const info = document.createElement("span");

        info.textContent =
            "Page " + currentPage +
            " of " + totalPages;

        Object.assign(info.style, {
            marginLeft: "8px",
            fontSize: "12px",
            color: "#66735f"
        });

        pagination.appendChild(info);
    }

    /* ==========================================
       RESET PAGE WHEN SEARCH/FILTER CHANGES
    ========================================== */

    function resetAndFilter() {

        currentPage = 1;

        filterProducts();
    }

    productSearch.addEventListener(
        "input",
        resetAndFilter
    );

    categoryFilter.addEventListener(
        "change",
        resetAndFilter
    );

    statusFilter.addEventListener(
        "change",
        resetAndFilter
    );

    /* ==========================================
       INITIAL DISPLAY
    ========================================== */

    filterProducts();

    // Admin deletion confirmations and requests
    // remain handled by admin_delete.js.

});
