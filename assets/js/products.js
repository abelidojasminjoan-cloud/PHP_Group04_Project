document.addEventListener("DOMContentLoaded", function () {

    const productSearch = document.getElementById("productSearch");
    const categoryFilter = document.getElementById("categoryFilter");
    const statusFilter = document.getElementById("statusFilter");

    const productRows = document.querySelectorAll(".product-row");
    const noProductsFound = document.getElementById("noProductsFound");
    const productCount = document.getElementById("productCount");

    if (!productSearch || !categoryFilter || !statusFilter) {
        return;
    }

    function filterProducts() {

        const searchValue = productSearch.value
            .trim()
            .toLowerCase();

        const categoryValue = categoryFilter.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();

        let visibleProducts = 0;

        productRows.forEach(row => {

            const searchData = row.dataset.search || "";
            const category = row.dataset.category || "";
            const status = row.dataset.status || "";

            const matchesSearch = searchData.includes(searchValue);

            const matchesCategory =
                categoryValue === "all" ||
                category === categoryValue;

            const matchesStatus =
                statusValue === "all" ||
                status === statusValue;

            const visible =
                matchesSearch &&
                matchesCategory &&
                matchesStatus;

            row.style.display = visible ? "" : "none";

            if (visible) {
                visibleProducts++;
            }
        });

        if (noProductsFound) {
            noProductsFound.style.display =
                visibleProducts === 0 ? "table-row" : "none";
        }

        if (productCount) {
            productCount.textContent = visibleProducts;
        }
    }

    productSearch.addEventListener("input", filterProducts);
    categoryFilter.addEventListener("change", filterProducts);
    statusFilter.addEventListener("change", filterProducts);

    filterProducts();

    // Admin deletion confirmations and requests are handled by admin_delete.js.

});
