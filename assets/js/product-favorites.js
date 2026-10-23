"use strict";

(() => {
    if (window.PureViaFavorites) return;
    const prefix = "purevia:product-favorites:";
    const memory = new Map();
    const unavailableStorage = new Set();
    const validId = id => typeof id === "string" && /^[1-9]\d*$/.test(id);
    const scope = () => document.querySelector(".pv-favorites-link")?.dataset.favoriteScope
        || document.querySelector("[data-favorite-id]")?.dataset.favoriteScope;

    function read(owner) {
        if (unavailableStorage.has(owner)) return new Set(memory.get(owner));
        try {
            const saved = JSON.parse(localStorage.getItem(prefix + owner) || "[]");
            return new Set(Array.isArray(saved) ? saved.filter(validId) : []);
        } catch { return new Set(); }
    }
    function update() {
        document.querySelectorAll("[data-favorite-id]").forEach(button => {
            const selected = read(button.dataset.favoriteScope).has(button.dataset.favoriteId);
            const label = `${selected ? "Remove" : "Add"} ${button.dataset.favoriteName} ${selected ? "from" : "to"} favorites`;
            button.setAttribute("aria-pressed", String(selected));
            button.setAttribute("aria-label", label);
            button.title = label;
        });
        document.querySelectorAll(".pv-favorites-link").forEach(link => {
            const count = read(link.dataset.favoriteScope).size;
            const badge = link.querySelector(".pv-favorites-count");
            badge.textContent = count > 100 ? "99+" : String(count);
            badge.hidden = count === 0;
            link.setAttribute("aria-label", `My Favorites, ${count} ${count === 1 ? "product" : "products"}`);
        });
    }
    function save(owner, selected) {
        memory.set(owner, selected);
        try { localStorage.setItem(prefix + owner, JSON.stringify([...selected])); }
        catch { unavailableStorage.add(owner); }
        update();
        window.dispatchEvent(new CustomEvent("purevia:favorites-updated"));
    }
    window.PureViaFavorites = {
        getIds: () => [...read(scope())],
        remove(ids) {
            const selected = read(scope());
            for (const id of Array.isArray(ids) ? ids : [ids]) selected.delete(String(id));
            save(scope(), selected);
        },
        clear: () => save(scope(), new Set())
    };
    function init() {
        document.addEventListener("click", event => {
            const button = event.target.closest("[data-favorite-id]");
            if (!button) return;
            event.preventDefault(); event.stopPropagation();
            const owner = button.dataset.favoriteScope;
            const selected = read(owner);
            const id = button.dataset.favoriteId;
            if (!validId(id)) return;
            if (selected.has(id)) selected.delete(id);
            else selected.add(id);
            save(owner, selected);
        });
        window.addEventListener("storage", event => {
            if (event.key !== null && !event.key.startsWith(prefix)) return;
            memory.clear(); unavailableStorage.clear(); update();
            window.dispatchEvent(new CustomEvent("purevia:favorites-updated"));
        });
        window.addEventListener("pageshow", event => {
            update();
            if (event.persisted) window.dispatchEvent(new CustomEvent("purevia:favorites-updated"));
        });
        update();
    }
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init, { once: true });
    else init();
})();
