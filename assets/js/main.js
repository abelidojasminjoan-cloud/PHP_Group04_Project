/* =====================================================
   CUSTOMER ACCOUNT DROPDOWN
===================================================== */

document.addEventListener("DOMContentLoaded", () => {
    const track = document.getElementById("newArrivalsTrack");
    if (!track) return;

    const controls = track.parentElement.querySelectorAll("[data-arrivals-direction]");
    const updateControls = () => {
        const overflows = track.scrollWidth > track.clientWidth + 1;
        controls.forEach(button => {
            button.hidden = !overflows;
            button.disabled = Number(button.dataset.arrivalsDirection) < 0
                ? track.scrollLeft <= 1
                : track.scrollLeft + track.clientWidth >= track.scrollWidth - 1;
        });
    };

    controls.forEach(button => button.addEventListener("click", () => {
        const card = track.querySelector(".product-card");
        if (!card) return;
        const step = card.getBoundingClientRect().width + parseFloat(getComputedStyle(track).columnGap);
        track.scrollBy({
            left: Number(button.dataset.arrivalsDirection) * step,
            behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth"
        });
    }));
    track.addEventListener("scroll", updateControls, { passive: true });
    if (typeof ResizeObserver !== "undefined") {
        new ResizeObserver(updateControls).observe(track);
    } else {
        window.addEventListener("resize", updateControls);
    }
    updateControls();
});

const accountDropdown =
    document.querySelector('.account-dropdown');

const accountDropdownTrigger =
    document.getElementById('accountDropdownTrigger');

const accountDropdownMenu =
    document.getElementById('accountDropdownMenu');


function closeAccountDropdown() {

    if (!accountDropdown) {
        return;
    }

    accountDropdown.classList.remove('active');

    if (accountDropdownTrigger) {
        accountDropdownTrigger.setAttribute(
            'aria-expanded',
            'false'
        );
    }

    if (accountDropdownMenu) {
        accountDropdownMenu.setAttribute(
            'aria-hidden',
            'true'
        );
    }
}


if (
    accountDropdown &&
    accountDropdownTrigger &&
    accountDropdownMenu
) {

    /* CLICK AVATAR / NAME */

    accountDropdownTrigger.addEventListener(
        'click',
        function (event) {

            event.preventDefault();
            event.stopPropagation();

            const isOpen =
                accountDropdown.classList.contains('active');

            if (isOpen) {

                closeAccountDropdown();

            } else {

                accountDropdown.classList.add('active');

                accountDropdownTrigger.setAttribute(
                    'aria-expanded',
                    'true'
                );

                accountDropdownMenu.setAttribute(
                    'aria-hidden',
                    'false'
                );

            }

        }
    );


    /* PREVENT MENU CLICK FROM CLOSING BEFORE LINK WORKS */

    accountDropdownMenu.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();

        }
    );


    /* CLICK OUTSIDE */

    document.addEventListener(
        'click',
        function () {

            closeAccountDropdown();

        }
    );

}

document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.size-option').forEach(function (button) {
                button.addEventListener('click', function () {
                    const row = button.closest('.product-size-row');
                    if (!row) return;

                    row.querySelectorAll('.size-option').forEach(function (option) {
                        option.classList.remove('selected');
                        option.setAttribute('aria-pressed', 'false');
                    });

                    button.classList.add('selected');
                    button.setAttribute('aria-pressed', 'true');
                });
            });
        });


document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".pv-home-cart-form").forEach(form => {
        const sizeButtons = form.querySelectorAll(
            ".size-option[data-variant-id]"
        );

        const variantInput = form.querySelector(
            ".pv-home-variant-input"
        );

        const priceDisplay = form.querySelector(".product-price");
        const addButton = form.querySelector(".add-to-bag-btn");

        sizeButtons.forEach(button => {
            button.addEventListener("click", () => {
                if (button.disabled) return;

                sizeButtons.forEach(option => {
                    option.classList.remove("selected");
                    option.setAttribute("aria-pressed", "false");
                });

                button.classList.add("selected");
                button.setAttribute("aria-pressed", "true");

                variantInput.value = button.dataset.variantId;

                const price = Number(button.dataset.price);
                const stock = Number(button.dataset.stock);

                priceDisplay.textContent =
                    "₱" + price.toLocaleString("en-PH", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                addButton.disabled = stock <= 0;

                const label = addButton.querySelector(
                    ".pv-home-add-label"
                );

                if (label) {
                    label.textContent = stock > 0
                        ? "Add to Bag"
                        : "Out of Stock";
                }
            });
        });
    });
});
