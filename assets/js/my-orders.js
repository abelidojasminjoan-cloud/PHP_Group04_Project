"use strict";

document.addEventListener("DOMContentLoaded", () => {
    window.PureViaCancellation?.init(window.PUREVIA_ORDERS || {});
    window.PureViaReturns?.init(window.PUREVIA_ORDERS || {});

    const tabs = Array.from(
        document.querySelectorAll(".mo-tab")
    );

    const cards = Array.from(
        document.querySelectorAll(".mo-order-card")
    );

    const searchInput = document.getElementById("moSearch");
    const emptyState = document.getElementById("moEmpty");

    let activeTab = "all";

    // FILTER ORDERS

    function filterOrders() {
        const query = (searchInput?.value || "")
            .trim()
            .toLocaleLowerCase();

        let visibleCount = 0;

        cards.forEach((card) => {
            const cardTab = card.dataset.tab || "";

            const searchable = (card.dataset.search || "")
                .toLocaleLowerCase();

            const matchesTab =
                activeTab === "all" || cardTab === activeTab;

            const matchesSearch =
                query === "" || searchable.includes(query);

            const visible = matchesTab && matchesSearch;

            card.hidden = !visible;

            if (visible) {
                visibleCount++;
            }
        });

        if (emptyState) {
            emptyState.hidden = visibleCount > 0;
        }
    }

    // TABS

    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            activeTab = tab.dataset.tab || "all";

            tabs.forEach((button) => {
                const isActive = button === tab;

                button.classList.toggle("active", isActive);

                button.setAttribute(
                    "aria-selected",
                    isActive ? "true" : "false"
                );
                button.tabIndex = isActive ? 0 : -1;
            });

            document.getElementById("moOrderList")?.setAttribute("aria-labelledby", tab.id);

            filterOrders();
            tab.scrollIntoView({ block: "nearest", inline: "nearest" });
        });

        tab.addEventListener("keydown", (event) => {
            const index = tabs.indexOf(tab);
            let nextIndex;
            if (event.key === "ArrowRight") nextIndex = (index + 1) % tabs.length;
            else if (event.key === "ArrowLeft") nextIndex = (index - 1 + tabs.length) % tabs.length;
            else if (event.key === "Home") nextIndex = 0;
            else if (event.key === "End") nextIndex = tabs.length - 1;
            else return;
            event.preventDefault();
            tabs[nextIndex].click();
            tabs[nextIndex].focus();
        });
    });

    searchInput?.addEventListener("input", filterOrders);

    filterOrders();
});
