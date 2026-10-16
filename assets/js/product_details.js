
document.addEventListener("DOMContentLoaded", () => {

    /* =====================================
       PRODUCT IMAGE CAROUSEL
    ===================================== */

    const mainImage = document.getElementById("pdMainImage");

    const thumbnails = [
        ...document.querySelectorAll(".pd-thumbnail")
    ];

    const imageCounter = document.getElementById("pdImageCounter");
    const previousImage = document.getElementById("pdPreviousImage");
    const nextImage = document.getElementById("pdNextImage");

    let currentImageIndex = 0;

    function showImage(index) {
        if (!thumbnails.length || !mainImage) return;

        currentImageIndex =
            (index + thumbnails.length) % thumbnails.length;

        const selected = thumbnails[currentImageIndex];

        mainImage.src = selected.dataset.image;

        thumbnails.forEach((thumbnail, i) => {
            thumbnail.classList.toggle(
                "active",
                i === currentImageIndex
            );
        });

        if (imageCounter) {
            imageCounter.textContent =
                `${currentImageIndex + 1} / ${thumbnails.length}`;
        }
    }

    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener("click", () => {
            showImage(index);
        });
    });

    previousImage?.addEventListener("click", () => {
        showImage(currentImageIndex - 1);
    });

    nextImage?.addEventListener("click", () => {
        showImage(currentImageIndex + 1);
    });

    /* =====================================
       PRODUCT VARIANTS
    ===================================== */

    const variants = [
        ...document.querySelectorAll(".pd-variant")
    ];

    const priceDisplay = document.getElementById("pdPrice");
    const stockDisplay = document.getElementById("pdStock");
    const variantInput = document.getElementById("pdVariantInput");

    const quantityInput = document.getElementById("pdQuantity");
    const decreaseButton = document.getElementById("pdDecrease");
    const increaseButton = document.getElementById("pdIncrease");
    const addButton = document.getElementById("pdAddButton");

    let selectedPrice = 0;
    let selectedStock = 0;

    /* FORMAT PRICE */

    function formatProductPrice(price) {
        return "₱" + Number(price).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /* UPDATE ADD TO BAG BUTTON */

    function updatePurchaseDisplay() {
        if (!addButton) return;

        if (selectedStock <= 0) {
            addButton.disabled = true;
            addButton.textContent = "Out of Stock";
        } else {
            addButton.disabled = false;
            addButton.textContent = "Add to Bag";
        }
    }

    /* SELECT PRODUCT SIZE */

    function selectVariant(variant) {
        if (!variant || variant.disabled) return;

        variants.forEach(item => {
            item.classList.remove("active");
            item.setAttribute("aria-pressed", "false");
        });

        variant.classList.add("active");
        variant.setAttribute("aria-pressed", "true");

        selectedPrice = Number(variant.dataset.price) || 0;
        selectedStock = Number(variant.dataset.stock) || 0;

        if (variantInput) {
            variantInput.value = variant.dataset.id;
        }

        /* UPDATE PRICE */

        if (priceDisplay) {
            priceDisplay.textContent =
                formatProductPrice(selectedPrice);
        }

        /* UPDATE STOCK */

        if (stockDisplay) {
            stockDisplay.textContent =
                selectedStock > 0
                    ? `In Stock (${selectedStock})`
                    : "Out of Stock";

            stockDisplay.classList.toggle(
                "out",
                selectedStock <= 0
            );
        }

        /* RESET QUANTITY */

        if (quantityInput) {
            quantityInput.value = 1;
            quantityInput.max = Math.max(1, selectedStock);
        }

        updatePurchaseDisplay();
    }

    /* SIZE BUTTON EVENTS */

    variants.forEach(variant => {
        variant.addEventListener("click", () => {
            selectVariant(variant);
        });
    });

    /* DEFAULT SELECTED SIZE */

    const initialVariant = document.querySelector(
        ".pd-variant.active:not(:disabled)"
    ) || document.querySelector(
        ".pd-variant:not(:disabled)"
    );

    if (initialVariant) {
        selectVariant(initialVariant);
    } else if (addButton) {
        addButton.disabled = true;
        addButton.textContent = "Out of Stock";
    }

    /* =====================================
       QUANTITY CONTROLS
    ===================================== */

    decreaseButton?.addEventListener("click", () => {
        if (!quantityInput) return;

        const currentQuantity =
            Number(quantityInput.value) || 1;

        quantityInput.value = Math.max(
            1,
            currentQuantity - 1
        );
    });

    increaseButton?.addEventListener("click", () => {
        if (!quantityInput || selectedStock <= 0) return;

        const currentQuantity =
            Number(quantityInput.value) || 1;

        quantityInput.value = Math.min(
            selectedStock,
            currentQuantity + 1
        );
    });

    /* =====================================
       PRODUCT INFORMATION TABS
    ===================================== */

    const tabs = document.querySelectorAll(".pd-tab");
    const panels = document.querySelectorAll(".pd-tab-panel");

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {

            const target = tab.dataset.tab;

            tabs.forEach(item => {
                item.classList.remove("active");
                item.setAttribute("aria-selected", "false");
            });

            panels.forEach(panel => {
                panel.hidden = true;
                panel.classList.remove("active");
            });

            tab.classList.add("active");
            tab.setAttribute("aria-selected", "true");

            const targetPanel = document.getElementById(
                "pd-panel-" + target
            );

            if (targetPanel) {
                targetPanel.hidden = false;
                targetPanel.classList.add("active");
            }
        });
    });

    /* =====================================
       CUSTOMER REVIEW FILTERING
    ===================================== */

    const reviewList = document.getElementById("pdReviewList");
    const skinFilter = document.getElementById("pdSkinFilter");
    const reviewSort = document.getElementById("pdReviewSort");
    const noReviews = document.getElementById("pdNoReviews");

    const reviewCards = reviewList
        ? [...reviewList.querySelectorAll(".pd-review-card")]
        : [];

    function filterAndSortReviews() {
        if (!reviewList) return;

        const selectedSkin = skinFilter?.value || "all";
        const selectedSort = reviewSort?.value || "recent";

        const sorted = [...reviewCards];

        sorted.sort((a, b) => {

            const ratingA = Number(a.dataset.rating);
            const ratingB = Number(b.dataset.rating);

            const dateA = Number(a.dataset.date);
            const dateB = Number(b.dataset.date);

            const helpfulA = Number(a.dataset.helpful);
            const helpfulB = Number(b.dataset.helpful);

            const photosA = Number(a.dataset.photos);
            const photosB = Number(b.dataset.photos);

            switch (selectedSort) {

                case "highest":
                    return ratingB - ratingA || dateB - dateA;

                case "lowest":
                    return ratingA - ratingB || dateB - dateA;

                case "helpful":
                    return helpfulB - helpfulA || dateB - dateA;

                case "photos":
                    return photosB - photosA || dateB - dateA;

                default:
                    return dateB - dateA;
            }
        });

        let visible = 0;

        sorted.forEach(card => {

            const skin = (
                card.dataset.skin || ""
            ).toLowerCase();

            const matchesSkin =
                selectedSkin === "all" ||
                skin === selectedSkin;

            const matchesPhotos =
                selectedSort !== "photos" ||
                Number(card.dataset.photos) > 0;

            const show = matchesSkin && matchesPhotos;

            card.hidden = !show;

            if (show) {
                visible++;
            }

            reviewList.appendChild(card);
        });

        if (noReviews) {
            noReviews.hidden = visible > 0;
        }
    }

    skinFilter?.addEventListener(
        "change",
        filterAndSortReviews
    );

    reviewSort?.addEventListener(
        "change",
        filterAndSortReviews
    );

    filterAndSortReviews();

    /* =====================================
       REVIEW HELPFUL VOTING
    ===================================== */

    const settings = window.PUREVIA_PRODUCT || {};

    document.querySelectorAll(".pd-vote-button").forEach(button => {

        button.addEventListener("click", async () => {

            if (!settings.loggedIn) {
                alert("Please log in to vote on reviews.");
                return;
            }

            const card = button.closest(".pd-review-card");

            if (!card) return;

            const buttons = card.querySelectorAll(
                ".pd-vote-button"
            );

            buttons.forEach(item => {
                item.disabled = true;
            });

            try {

                const response = await fetch(settings.voteUrl, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "Content-Type":
                            "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        csrf_token: settings.csrfToken,
                        review_id: button.dataset.review,
                        vote: button.dataset.vote
                    })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(
                        result.message ||
                        "Unable to save your vote."
                    );
                }

                buttons.forEach(item => {

                    const active =
                        item.dataset.vote === result.vote;

                    item.classList.toggle("active", active);

                    item.setAttribute(
                        "aria-pressed",
                        String(active)
                    );
                });

                const helpfulCount = card.querySelector(
                    ".pd-helpful-count"
                );

                if (helpfulCount) {
                    helpfulCount.textContent =
                        result.helpful_count;
                }

                card.dataset.helpful = result.helpful_count;

                if (reviewSort?.value === "helpful") {
                    filterAndSortReviews();
                }

            } catch (error) {

                alert(error.message);

            } finally {

                buttons.forEach(item => {
                    item.disabled = false;
                });
            }
        });
    });

});
