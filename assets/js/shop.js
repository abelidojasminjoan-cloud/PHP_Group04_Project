
document.addEventListener('DOMContentLoaded', function () {

    /* =========================================
       ELEMENTS
    ========================================= */

    const productGrid = document.getElementById('shopProductGrid');

    if (!productGrid) return;

    const productCards = Array.from(
        productGrid.querySelectorAll('.shop-product-card')
    );

    const sortContainer = document.getElementById('shopSortContainer');
    const sortTrigger = document.getElementById('shopSortTrigger');
    const sortMenu = document.getElementById('shopSortMenu');

    const sortRadios = Array.from(
        document.querySelectorAll('input[name="shop_sort"]')
    );

    const categoryCheckboxes = Array.from(
        document.querySelectorAll('input[name="category"]')
    );

    const skinCheckboxes = Array.from(
        document.querySelectorAll('input[name="skin_type"]')
    );

    const concernCheckboxes = Array.from(
        document.querySelectorAll('input[name="concern"]')
    );

    const ingredientCheckboxes = Array.from(
        document.querySelectorAll('input[name="ingredient"]')
    );

    const stockCheckboxes = Array.from(
        document.querySelectorAll('input[name="stock_status"]')
    );

    const myMatches = document.getElementById('shopMyMatches');

    const resultCount = document.getElementById('shopResultCount');
    const emptyState = document.getElementById('shopEmpty');

    const clearButton = document.getElementById('clearFilters');
    const resetEmpty = document.getElementById('shopResetEmpty');

    /* PRICE RANGE */

    const priceRange = document.getElementById('shopPriceRange');

    const minRange = document.getElementById('shopMinRange');
    const maxRange = document.getElementById('shopMaxRange');

    const minPriceInput = document.getElementById('shopMinPrice');
    const maxPriceInput = document.getElementById('shopMaxPrice');

    const priceLimit = maxRange
        ? Number(maxRange.max)
        : Infinity;

    let currentMin = 0;
    let currentMax = priceLimit;

    /* =========================================
       HELPERS
    ========================================= */

    function formatPrice(price) {
        return '₱' + Number(price).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function getCheckedValues(checkboxes) {
        return checkboxes
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
    }

    function parseValues(value) {
        return String(value || '')
            .split(',')
            .filter(Boolean);
    }

    function matchesAny(productValues, selectedValues) {
        if (selectedValues.length === 0) {
            return true;
        }

        return selectedValues.some(value =>
            productValues.includes(value)
        );
    }

    function originalOrder(a, b) {
        return Number(a.dataset.index || 0) -
            Number(b.dataset.index || 0);
    }

    function getSelectedSort() {
        const selected = sortRadios.find(radio => radio.checked);

        return selected ? selected.value : 'relevant';
    }

    /* =========================================
       FILTER ACCORDION PLUS / MINUS
    ========================================= */

    const accordions = document.querySelectorAll(
        '.shop-accordion'
    );

    accordions.forEach(accordion => {

        const toggle = accordion.querySelector(
            '.shop-filter-toggle'
        );

        const content = accordion.querySelector(
            '.shop-filter-content'
        );

        const symbol = accordion.querySelector(
            '.shop-filter-symbol'
        );

        if (!toggle || !content || !symbol) return;

        function setAccordionState(isOpen) {

            accordion.classList.toggle('is-open', isOpen);

            content.hidden = !isOpen;

            toggle.setAttribute(
                'aria-expanded',
                String(isOpen)
            );

            symbol.textContent = isOpen ? '−' : '+';
        }

        setAccordionState(
            accordion.classList.contains('is-open')
        );

        toggle.addEventListener('click', function () {

            const isOpen = accordion.classList.contains('is-open');

            setAccordionState(!isOpen);
        });

    });

    /* =========================================
       PRICE RANGE SLIDER
    ========================================= */

    function updatePriceUI() {

        if (!minRange || !maxRange) return;

        currentMin = clamp(currentMin, 0, priceLimit);
        currentMax = clamp(currentMax, currentMin, priceLimit);

        minRange.value = currentMin;
        maxRange.value = currentMax;

        if (minPriceInput) {
            minPriceInput.value = currentMin;
        }

        if (maxPriceInput) {
            maxPriceInput.value = currentMax;
        }

        const minPercent = priceLimit > 0
            ? (currentMin / priceLimit) * 100
            : 0;

        const maxPercent = priceLimit > 0
            ? (currentMax / priceLimit) * 100
            : 100;

        if (priceRange) {

            priceRange.style.setProperty(
                '--range-min',
                minPercent + '%'
            );

            priceRange.style.setProperty(
                '--range-max',
                maxPercent + '%'
            );
        }

        /* HANDLE OVERLAPPING THUMBS */

        if (currentMin >= currentMax - 1) {
            minRange.style.zIndex = '5';
            maxRange.style.zIndex = '4';
        } else {
            minRange.style.zIndex = '3';
            maxRange.style.zIndex = '5';
        }
    }

    if (minRange) {

        minRange.addEventListener('input', function () {

            currentMin = Math.min(
                Number(minRange.value),
                currentMax
            );

            updatePriceUI();
            filterProducts();
        });
    }

    if (maxRange) {

        maxRange.addEventListener('input', function () {

            currentMax = Math.max(
                Number(maxRange.value),
                currentMin
            );

            updatePriceUI();
            filterProducts();
        });
    }

    if (minPriceInput) {

        minPriceInput.addEventListener('change', function () {

            const value = Number(minPriceInput.value);

            currentMin = clamp(
                Number.isFinite(value) ? value : 0,
                0,
                currentMax
            );

            updatePriceUI();
            filterProducts();
        });
    }

    if (maxPriceInput) {

        maxPriceInput.addEventListener('change', function () {

            const value = Number(maxPriceInput.value);

            currentMax = clamp(
                Number.isFinite(value) ? value : priceLimit,
                currentMin,
                priceLimit
            );

            updatePriceUI();
            filterProducts();
        });
    }

    /* =========================================
       SORT DROPDOWN
    ========================================= */

    function openSortMenu() {

        if (!sortMenu || !sortTrigger) return;

        sortMenu.hidden = false;

        sortTrigger.setAttribute(
            'aria-expanded',
            'true'
        );
    }

    function closeSortMenu() {

        if (!sortMenu || !sortTrigger) return;

        sortMenu.hidden = true;

        sortTrigger.setAttribute(
            'aria-expanded',
            'false'
        );
    }

    if (sortTrigger) {

        sortTrigger.addEventListener('click', function () {

            if (sortMenu.hidden) {
                openSortMenu();
            } else {
                closeSortMenu();
            }
        });
    }

    document.addEventListener('click', function (event) {

        if (
            sortContainer &&
            !sortContainer.contains(event.target)
        ) {
            closeSortMenu();
        }
    });

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            closeSortMenu();
        }
    });

    /* =========================================
       SORT PRODUCTS
    ========================================= */

    function sortProducts() {

        const sort = getSelectedSort();
        const sorted = [...productCards];

        sorted.sort((a, b) => {

            const priceA = Number(a.dataset.price || 0);
            const priceB = Number(b.dataset.price || 0);

            const nameA = a.dataset.name || '';
            const nameB = b.dataset.name || '';

            const dateA = a.dataset.date || '';
            const dateB = b.dataset.date || '';

            const salesA = Number(a.dataset.sales || 0);
            const salesB = Number(b.dataset.sales || 0);

            const matchA = Number(a.dataset.match || 0);
            const matchB = Number(b.dataset.match || 0);

            const concernA = Number(
                a.dataset.concernMatch || 0
            );

            const concernB = Number(
                b.dataset.concernMatch || 0
            );

            switch (sort) {

                case 'featured':
                    return originalOrder(a, b);

                case 'relevant':

                    if (matchA !== matchB) {
                        return matchB - matchA;
                    }

                    if (concernA !== concernB) {
                        return concernB - concernA;
                    }

                    return originalOrder(a, b);

                case 'best-selling':
                    return salesB - salesA ||
                        originalOrder(a, b);

                case 'name-asc':
                    return nameA.localeCompare(nameB) ||
                        originalOrder(a, b);

                case 'name-desc':
                    return nameB.localeCompare(nameA) ||
                        originalOrder(a, b);

                case 'price-low':
                    return priceA - priceB ||
                        originalOrder(a, b);

                case 'price-high':
                    return priceB - priceA ||
                        originalOrder(a, b);

                case 'date-old':
                    return dateA.localeCompare(dateB) ||
                        originalOrder(a, b);

                case 'date-new':
                    return dateB.localeCompare(dateA) ||
                        originalOrder(a, b);

                default:
                    return originalOrder(a, b);
            }
        });

        sorted.forEach(card => {
            productGrid.appendChild(card);
        });
    }

    sortRadios.forEach(radio => {

        radio.addEventListener('change', function () {

            if (!radio.checked) return;

            sortProducts();
            closeSortMenu();
        });
    });

    /* =========================================
       FILTER PRODUCTS
    ========================================= */

    function filterProducts() {

        const selectedCategories = getCheckedValues(
            categoryCheckboxes
        ).filter(value => value !== 'all');

        const selectedTypes = getCheckedValues(
            skinCheckboxes
        );

        const selectedConcerns = getCheckedValues(
            concernCheckboxes
        );

        const selectedIngredients = getCheckedValues(ingredientCheckboxes);

        const selectedStock = getCheckedValues(
            stockCheckboxes
        );

        const onlyMatches = myMatches
            ? myMatches.checked
            : false;

        let visibleCount = 0;

        productCards.forEach(card => {

            const category = card.dataset.category || '';

            const price = Number(
                card.dataset.price || 0
            );

            const stock = Number(
                card.dataset.stock || 0
            );

            const types = parseValues(
                card.dataset.types
            );

            const concerns = parseValues(
                card.dataset.concerns
            );

            const match = card.dataset.match === '1';

            /* CATEGORY */

            const categoryMatch =
                selectedCategories.length === 0 ||
                selectedCategories.includes(category);

            /* SKIN TYPE */

            const skinMatch = matchesAny(
                types,
                selectedTypes
            );

            /* SKIN CONCERN */

            const concernMatch = matchesAny(
                concerns,
                selectedConcerns
            );

            /* PRICE */

            const priceMatch =
                price >= currentMin &&
                price <= currentMax;

            /* SKIN PROFILE */

            const profileMatch =
                !onlyMatches || match;

            /* STOCK STATUS */

            let stockMatch = true;

            if (selectedStock.length === 1) {

                if (selectedStock.includes('in')) {
                    stockMatch = stock > 0;
                }

                if (selectedStock.includes('out')) {
                    stockMatch = stock <= 0;
                }
            }

            /* FINAL VISIBILITY */

            const visible =
                categoryMatch &&
                skinMatch &&
                concernMatch &&
                matchesAny(parseValues(card.dataset.ingredients), selectedIngredients) &&
                priceMatch &&
                profileMatch &&
                stockMatch;

            card.hidden = !visible;

            if (visible) {
                visibleCount++;
            }
        });

        /* UPDATE PRODUCT COUNT */

        if (resultCount) {

            resultCount.textContent =
                visibleCount +
                (visibleCount === 1
                    ? ' product'
                    : ' products');
        }

        /* EMPTY STATE */

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
        }

        sortProducts();
    }

    /* =========================================
       CATEGORY FILTER
    ========================================= */

    categoryCheckboxes.forEach(checkbox => {

        checkbox.addEventListener('change', function () {

            const allCheckbox = categoryCheckboxes.find(
                item => item.value === 'all'
            );

            if (
                checkbox.value === 'all' &&
                checkbox.checked
            ) {

                categoryCheckboxes.forEach(item => {

                    if (item.value !== 'all') {
                        item.checked = false;
                    }
                });

            } else if (checkbox.value !== 'all') {

                if (allCheckbox) {
                    allCheckbox.checked = false;
                }

                const hasSelection = categoryCheckboxes.some(
                    item =>
                        item.value !== 'all' &&
                        item.checked
                );

                if (!hasSelection && allCheckbox) {
                    allCheckbox.checked = true;
                }
            }

            filterProducts();
        });
    });

    /* =========================================
       SKIN TYPE FILTER
    ========================================= */

    ingredientCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', filterProducts);
    });

    skinCheckboxes.forEach(checkbox => {

        checkbox.addEventListener(
            'change',
            filterProducts
        );
    });

    /* =========================================
       SKIN CONCERN FILTER
    ========================================= */

    concernCheckboxes.forEach(checkbox => {

        checkbox.addEventListener(
            'change',
            filterProducts
        );
    });

    /* =========================================
       STOCK STATUS FILTER
    ========================================= */

    stockCheckboxes.forEach(checkbox => {

        checkbox.addEventListener(
            'change',
            filterProducts
        );
    });

    /* =========================================
       MY MATCHES FILTER
    ========================================= */

    if (myMatches) {

        myMatches.addEventListener(
            'change',
            filterProducts
        );
    }

    /* =========================================
       PRODUCT SIZE SELECTION
    ========================================= */

    productCards.forEach(card => {

        const sizeButtons = card.querySelectorAll(
            '.shop-card-size[data-variant-id]'
        );

        const priceDisplay = card.querySelector(
            '.shop-card-price'
        );

        const variantInput = card.querySelector(
            '.shop-card-variant-input'
        );

        const addButton = card.querySelector(
            '.shop-card-add'
        );

        sizeButtons.forEach(button => {

            button.addEventListener('click', function () {

                if (button.disabled) return;

                sizeButtons.forEach(option => {

                    option.classList.remove('active');

                    option.setAttribute(
                        'aria-pressed',
                        'false'
                    );
                });

                button.classList.add('active');

                button.setAttribute(
                    'aria-pressed',
                    'true'
                );

                const price = Number(
                    button.dataset.price
                );

                const stock = Number(
                    button.dataset.stock
                );

                const variantId =
                    button.dataset.variantId;

                /* UPDATE PRICE */

                if (priceDisplay) {
                    priceDisplay.textContent =
                        formatPrice(price);
                }

                /* UPDATE VARIANT */

                if (variantInput) {
                    variantInput.value = variantId;
                }

                /* UPDATE FILTER PRICE */

                card.dataset.price = price;

                /* UPDATE ADD TO BAG */

                if (addButton) {

                    addButton.disabled = stock <= 0;

                    const label = addButton.querySelector(
                        '.shop-card-add-label'
                    );

                    if (label) {

                        label.textContent =
                            stock > 0
                                ? 'Add to Bag'
                                : 'Out of Stock';
                    }
                }

                filterProducts();
            });
        });
    });

    /* =========================================
       RESET FILTERS
    ========================================= */

    function resetFilters() {

        /* SORT */

        sortRadios.forEach(radio => {
            radio.checked =
                radio.value === 'relevant';
        });

        /* CATEGORY */

        categoryCheckboxes.forEach(checkbox => {
            checkbox.checked =
                checkbox.value === 'all';
        });

        /* SKIN TYPE */

        skinCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });

        /* SKIN CONCERN */

        concernCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });

        ingredientCheckboxes.forEach(checkbox => { checkbox.checked = false; });

        /* STOCK STATUS */

        stockCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });

        /* SKIN PROFILE */

        if (myMatches) {
            myMatches.checked = false;
        }

        /* PRICE */

        currentMin = 0;
        currentMax = priceLimit;

        updatePriceUI();

        closeSortMenu();

        filterProducts();
    }

    if (clearButton) {

        clearButton.addEventListener(
            'click',
            resetFilters
        );
    }

    if (resetEmpty) {

        resetEmpty.addEventListener(
            'click',
            resetFilters
        );
    }

    /* =========================================
       IMAGE FALLBACK
    ========================================= */

    document.querySelectorAll(
        '.shop-product-image img'
    ).forEach(img => {

        img.addEventListener('error', function () {

            if (
                img.dataset.fallbackApplied === '1'
            ) {
                return;
            }

            img.dataset.fallbackApplied = '1';

            const placeholder =
                document.createElement('div');

            placeholder.className =
                'shop-image-placeholder';

            placeholder.textContent = 'PureVia';

            img.replaceWith(placeholder);
        });
    });

    /* =========================================
       INITIAL DISPLAY
    ========================================= */

    updatePriceUI();
    filterProducts();

});
