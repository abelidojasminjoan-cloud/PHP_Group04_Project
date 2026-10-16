
document.addEventListener("DOMContentLoaded", function () {
    const BASE_URL = "/purevia_website";

    const trigger = document.getElementById("pvSearchTrigger");
    const panel = document.getElementById("pvSearchPanel");
    const overlay = document.getElementById("pvSearchOverlay");
    const closeButton = document.getElementById("pvSearchClose");

    const form = document.getElementById("pvSearchForm");
    const input = document.getElementById("pvSearchInput");
    const clearButton = document.getElementById("pvSearchClear");

    const dropdown = document.getElementById("pvSearchDropdown");
    const suggestionBox = document.getElementById("pvSearchSuggestions");
    const productBox = document.getElementById("pvSearchProducts");

    const columns = dropdown?.querySelector(".pv-search-columns");
    const suggestionColumn = dropdown?.querySelector(
        ".pv-search-suggestions"
    );

    const viewAll = document.getElementById("pvSearchViewAll");
    const viewAllText = document.getElementById("pvSearchViewAllText");

    if (
        !trigger || !panel || !overlay ||
        !closeButton || !form || !input ||
        !clearButton || !dropdown ||
        !suggestionBox || !productBox ||
        !columns || !suggestionColumn ||
        !viewAll || !viewAllText
    ) {
        console.error("PureVia search: Missing HTML elements.");
        return;
    }

    let debounceTimer = null;
    let controller = null;
    let requestId = 0;

    function searchUrl(query) {
        return BASE_URL + "/shop.php?q=" +
            encodeURIComponent(query);
    }

    function createElement(tag, className = "", text = "") {
        const element = document.createElement(tag);
        element.className = className;
        element.textContent = text;
        return element;
    }

    function setSuggestionLayout(hasSuggestions) {
        suggestionColumn.hidden = !hasSuggestions;
        columns.classList.toggle(
            "products-only",
            !hasSuggestions
        );
    }

    function updateClearButton() {
        clearButton.hidden = input.value.length === 0;
    }

    function openSearch() {
        panel.hidden = false;
        overlay.hidden = false;

        trigger.setAttribute("aria-expanded", "true");
        input.focus();

        const query = input.value.trim();

        if (query.length >= 2) {
            loadSearchResults(query);
        }
    }

    function closeSearch() {
        panel.hidden = true;
        overlay.hidden = true;
        dropdown.hidden = true;

        trigger.setAttribute("aria-expanded", "false");
        input.setAttribute("aria-expanded", "false");

        clearTimeout(debounceTimer);
        requestId++;

        if (controller) {
            controller.abort();
        }
    }

    function highlightSuggestion(text, query) {
        const fragment = document.createDocumentFragment();
        const index = text.toLowerCase().indexOf(
            query.toLowerCase()
        );

        if (index === -1) {
            fragment.append(document.createTextNode(text));
            return fragment;
        }

        fragment.append(
            document.createTextNode(text.slice(0, index))
        );

        const strong = document.createElement("strong");
        strong.textContent = text.slice(
            index,
            index + query.length
        );

        fragment.append(strong);

        fragment.append(
            document.createTextNode(
                text.slice(index + query.length)
            )
        );

        return fragment;
    }

    function renderSuggestions(items, query) {
        suggestionBox.replaceChildren();

        const validItems = Array.isArray(items)
            ? items.filter(item =>
                typeof item === "string" &&
                item.trim() !== ""
            )
            : [];

        setSuggestionLayout(validItems.length > 0);

        validItems.forEach(function (item) {
            const link = createElement(
                "a",
                "pv-search-suggestion"
            );

            link.href = searchUrl(item);

            link.append(
                highlightSuggestion(item, query)
            );

            suggestionBox.appendChild(link);
        });
    }

    function renderProducts(items) {
        productBox.replaceChildren();

        if (!Array.isArray(items) || items.length === 0) {
            productBox.appendChild(
                createElement(
                    "p",
                    "pv-search-message",
                    "No matching products found."
                )
            );
            return;
        }

        items.forEach(function (product) {
            const link = createElement(
                "a",
                "pv-search-product"
            );

            link.href = BASE_URL +
                "/product.php?id=" +
                encodeURIComponent(product.id);

            const imageBox = createElement(
                "div",
                "pv-search-product-image"
            );

            function showFallback() {
                const fallback = createElement(
                    "span",
                    "pv-search-image-fallback"
                );

                const icon = document.createElement("i");
                icon.className = "fa-solid fa-box-open";

                fallback.appendChild(icon);
                imageBox.replaceChildren(fallback);
            }

            if (product.image) {
                const image = document.createElement("img");

                image.src = product.image;
                image.alt = product.name || "Product";
                image.loading = "lazy";

                image.addEventListener(
                    "error",
                    showFallback,
                    { once: true }
                );

                imageBox.appendChild(image);
            } else {
                showFallback();
            }

            const info = createElement(
                "div",
                "pv-search-product-info"
            );

            const name = createElement(
                "span",
                "pv-search-product-name",
                product.name || ""
            );

            const category = createElement(
                "span",
                "pv-search-product-category",
                product.category || ""
            );

            const price = createElement(
                "span",
                "pv-search-product-price",
                "₱" + Number(product.price || 0).toLocaleString(
                    "en-PH",
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                )
            );

            info.append(name, category, price);
            link.append(imageBox, info);

            productBox.appendChild(link);
        });
    }

    async function loadSearchResults(query) {
        if (controller) {
            controller.abort();
        }

        const currentRequest = ++requestId;
        controller = new AbortController();
        const signal = controller.signal;

        dropdown.hidden = false;
        input.setAttribute("aria-expanded", "true");

        // Keep both headings visible while loading.
        setSuggestionLayout(true);

        suggestionBox.replaceChildren(
            createElement(
                "p",
                "pv-search-message",
                "Finding suggestions..."
            )
        );

        productBox.replaceChildren(
            createElement(
                "p",
                "pv-search-message",
                "Searching products..."
            )
        );

        viewAll.href = searchUrl(query);
        viewAllText.textContent = 'Search for "' + query + '"';

        try {
            const endpoint = BASE_URL +
            "/actions/search/search.php?q=" +
            encodeURIComponent(query);

            const response = await fetch(endpoint, {
                signal: signal,
                headers: {
                    "Accept": "application/json"
                },
                cache: "no-store"
            });

            const raw = await response.text();

            let data;

            try {
                data = JSON.parse(raw);
            } catch (error) {
                console.error(
                    "Search returned invalid JSON:",
                    raw.slice(0, 1000)
                );

                throw new Error(
                    "Invalid JSON response. HTTP " +
                    response.status
                );
            }

            if (!response.ok || data.error) {
                throw new Error(
                    data.error || "HTTP " + response.status
                );
            }

            if (
                signal.aborted ||
                currentRequest !== requestId ||
                panel.hidden ||
                input.value.trim() !== query
            ) {
                return;
            }

            renderSuggestions(data.suggestions || [], query);
            renderProducts(data.products || []);

        } catch (error) {
            if (
                error.name === "AbortError" ||
                currentRequest !== requestId ||
                panel.hidden
            ) {
                return;
            }

            console.error("PureVia search error:", error);

            setSuggestionLayout(true);

            suggestionBox.replaceChildren(
                createElement(
                    "p",
                    "pv-search-message",
                    "Suggestions unavailable."
                )
            );

            productBox.replaceChildren(
                createElement(
                    "p",
                    "pv-search-message",
                    "Unable to load results. Please try again."
                )
            );
        }
    }

    trigger.addEventListener("click", function () {
        if (panel.hidden) {
            openSearch();
        } else {
            closeSearch();
        }
    });

    closeButton.addEventListener("click", closeSearch);
    overlay.addEventListener("click", closeSearch);

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && !panel.hidden) {
            closeSearch();
            trigger.focus();
        }
    });

    input.addEventListener("input", function () {
        const query = input.value.trim();

        updateClearButton();
        clearTimeout(debounceTimer);

        requestId++;

        if (controller) {
            controller.abort();
        }

        if (query.length < 2) {
            dropdown.hidden = true;
            input.setAttribute("aria-expanded", "false");
            return;
        }

        debounceTimer = setTimeout(function () {
            loadSearchResults(query);
        }, 250);
    });

    clearButton.addEventListener("click", function () {
        input.value = "";

        clearTimeout(debounceTimer);
        requestId++;

        if (controller) {
            controller.abort();
        }

        dropdown.hidden = true;
        input.setAttribute("aria-expanded", "false");

        updateClearButton();
        input.focus();
    });

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        const query = input.value.trim();

        if (query) {
            window.location.href = searchUrl(query);
        } else {
            input.focus();
        }
    });

    updateClearButton();
});
