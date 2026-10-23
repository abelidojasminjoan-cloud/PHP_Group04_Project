"use strict";

document.addEventListener("DOMContentLoaded", () => {
    const favorites = window.PureViaFavorites;
    const config = window.PUREVIA_FAVORITES_PAGE;
    if (!favorites || !config) return;
    const grid = document.getElementById("fvGrid");
    const count = document.getElementById("fvCount");
    const clear = document.getElementById("fvClear");
    const search = document.getElementById("fvSearch");
    const sort = document.getElementById("fvSort");
    const status = document.getElementById("fvStatus");
    const empty = document.getElementById("fvEmpty");
    const explore = document.getElementById("fvExplore");
    const toolbar = document.getElementById("fvToolbar");
    const cache = new Map();
    const money = value => new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(value);
    let requestId = 0;
    let loading = false;

    function node(tag, className, text) {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    }

    function card(product) {
        const article = node("article", "fv-card");
        article.dataset.productId = String(product.id);
        const url = `${config.baseUrl}/public/product.php?id=${product.id}`;
        const imageLink = node("a", "fv-image");
        imageLink.href = url;
        const image = node("img");
        image.src = product.image;
        image.alt = product.name;
        image.loading = "lazy";
        image.addEventListener("error", () => { image.src = `${config.baseUrl}/assets/images/products/default-product.svg`; }, { once: true });
        imageLink.append(image);
        const details = node("div", "fv-details");
        const heading = node("h2", "fv-name");
        const link = node("a", "", product.name);
        link.href = url;
        heading.append(link);
        const rating = node("div", "fv-rating");
        const stars = node("span", "fv-stars");
        const rounded = Math.min(5, Math.max(0, Math.round(product.rating)));
        stars.textContent = "★".repeat(rounded) + "☆".repeat(5 - rounded);
        stars.setAttribute("role", "img");
        stars.setAttribute("aria-label", `${Number(product.rating).toFixed(1)} out of 5 stars`);
        rating.append(stars, node("span", "", `${product.reviewCount} ${product.reviewCount === 1 ? "review" : "reviews"}`));
        const footer = node("div", "fv-card-footer");
        const remove = node("button", "fv-remove", "Remove");
        remove.type = "button";
        remove.setAttribute("aria-label", `Remove ${product.name} from favorites`);
        remove.addEventListener("click", () => {
            favorites.remove(product.id);
            (grid.querySelector(".fv-remove") || (empty.hidden ? search : explore)).focus();
        });
        const form = node("form", "pv-add-cart-form fv-cart-form");
        form.method = "POST";
        form.action = `${config.baseUrl}/actions/cart/add.php`;
        const fields = { csrf_token: config.csrfToken, product_id: product.id, product_variant_id: product.variantId, quantity: 1 };
        Object.entries(fields).forEach(([name, value]) => {
            const input = node("input");
            input.type = "hidden";
            input.name = name;
            input.value = String(value);
            form.append(input);
        });
        const available = product.variantId > 0 && product.stock > 0;
        const add = node("button", "fv-add", available ? "Add to Bag" : "Out of Stock");
        add.type = "submit";
        add.disabled = !available;
        form.append(add);
        footer.append(node("strong", "fv-price", money(product.price)), remove, form);
        details.append(node("p", "fv-category", product.category), heading, rating, footer);
        article.append(imageLink, details);
        return article;
    }

    function render() {
        const ids = favorites.getIds();
        count.textContent = `${ids.length} ${ids.length === 1 ? "product" : "products"} in your collection`;
        clear.disabled = ids.length === 0;
        clear.hidden = ids.length === 0;
        toolbar.hidden = ids.length === 0;
        grid.hidden = ids.length === 0;
        empty.hidden = ids.length !== 0;
        const query = search.value.trim().toLocaleLowerCase();
        let products = [...ids].reverse().map(id => cache.get(id)).filter(Boolean)
            .filter(product => `${product.name} ${product.category}`.toLocaleLowerCase().includes(query));
        if (sort.value === "name") products.sort((a, b) => a.name.localeCompare(b.name));
        else if (sort.value === "price-low") products.sort((a, b) => a.price - b.price);
        else if (sort.value === "price-high") products.sort((a, b) => b.price - a.price);
        grid.replaceChildren(...products.map(card));
        status.classList.remove("is-error");
        status.hidden = ids.length === 0 || (products.length > 0 && !loading);
        status.textContent = loading ? "Loading saved products…" : ids.length ? "No matching saved products." : "No saved products yet.";
    }

    async function load() {
        const currentRequest = ++requestId;
        const ids = favorites.getIds();
        const missing = ids.filter(id => !cache.has(id));
        loading = missing.length > 0;
        render();
        if (!missing.length) return;
        try {
            const batches = [];
            for (let index = 0; index < missing.length; index += 100) {
                const batch = missing.slice(index, index + 100);
                batches.push((async () => {
                    const response = await fetch(`${config.baseUrl}/actions/favorites/products.php?ids=${encodeURIComponent(batch.join(","))}`, {
                        credentials: "same-origin", headers: { Accept: "application/json" }
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message || "Unable to load saved products.");
                    return { batch, products: result.products };
                })());
            }
            const results = await Promise.all(batches);
            for (const { batch, products } of results) {
                batch.forEach(id => cache.set(id, null));
                products.forEach(product => cache.set(String(product.id), product));
            }
            if (currentRequest !== requestId) return;
            loading = false;
            const unavailable = favorites.getIds().filter(id => cache.has(id) && !cache.get(id));
            if (unavailable.length) favorites.remove(unavailable);
            else render();
        } catch {
            if (currentRequest !== requestId) return;
            loading = false;
            status.hidden = false;
            status.classList.add("is-error");
            status.textContent = "Unable to load saved products. Please refresh and try again.";
        }
    }

    search.addEventListener("input", render);
    sort.addEventListener("change", render);
    clear.addEventListener("click", () => { favorites.clear(); explore.focus(); });
    grid.addEventListener("submit", event => {
        if (config.canAddToBag) return;
        event.preventDefault();
        event.stopPropagation();
        window.location.assign(`${config.baseUrl}/public/login.php`);
    });
    window.addEventListener("purevia:favorites-updated", load);
    load();
});
