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

    const productForm = document.getElementById('addProductForm');
    let deletingProduct = false;
    const deleteError = document.createElement('div');
    deleteError.className = 'form-alert form-alert-error';
    deleteError.setAttribute('role', 'alert');
    deleteError.tabIndex = -1;

    document.querySelectorAll('.delete-product-button').forEach(button => {
        button.addEventListener('click', async function () {
            if (deletingProduct || !productForm) return;
            const name = button.dataset.productName || 'this product';
            if (!window.confirm('Delete "' + name + '"?')) return;

            deletingProduct = true;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            deleteError.remove();
            try {
                const body = new URLSearchParams({
                    product_id: button.dataset.productId,
                    csrf_token: productForm.elements.namedItem('csrf_token').value
                });
                const response = await fetch(productForm.dataset.deleteAction, {
                    method: 'POST', body, credentials: 'same-origin',
                    headers: { Accept: 'application/json' }
                });
                const data = await response.json();
                if (!response.ok || !data.success) throw new Error(data.message || 'Unable to delete this product.');
                window.location.reload();
            } catch (error) {
                deleteError.textContent = error.message || 'Unable to delete this product. Please try again.';
                document.querySelector('.products-page-header').after(deleteError);
                deleteError.focus();
            } finally {
                deletingProduct = false;
                button.disabled = false;
                button.removeAttribute('aria-busy');
            }
        });
    });

});
