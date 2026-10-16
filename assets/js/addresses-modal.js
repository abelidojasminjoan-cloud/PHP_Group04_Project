
document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    const config = window.PUREVIA_ADDRESS_CONFIG;
    if (!config) return;

    const modal = document.getElementById("addressModal");
    const form = document.getElementById("addressModalForm");
    const body = modal?.querySelector(".pv-modal-body");

    if (!modal || !form) return;

    const title = document.getElementById("addressModalTitle");
    const subtitle = document.getElementById("addressModalSubtitle");
    const action = document.getElementById("addressAction");
    const addressId = document.getElementById("addressId");
    const saveButton = document.getElementById("saveAddressButton");

    const region = document.getElementById("addressRegion");
    const province = document.getElementById("addressProvince");
    const city = document.getElementById("addressCity");
    const barangay = document.getElementById("addressBarangay");

    const defaultCheckbox = document.getElementById(
        "addressIsDefault"
    );

    const defaultHint = document.getElementById(
        "addressDefaultHint"
    );

    const addresses = config.addresses || {};
    const defaultForm = config.defaultForm || {};

    /*
     * SAMPLE PHILIPPINE LOCATION DATA
     *
     * This preserves the dropdown options from your
     * existing JS. It is not a complete PSGC directory.
     */
    const locations = {
        "Metro Manila": {
            "Metro Manila": {
                "Makati": [
                    "Poblacion",
                    "Bel-Air",
                    "San Lorenzo",
                    "Bangkal"
                ],
                "Manila": [
                    "Ermita",
                    "Malate",
                    "Sampaloc"
                ],
                "Quezon City": [
                    "Bagumbayan",
                    "Commonwealth",
                    "Batasan Hills"
                ],
                "Taguig": [
                    "Fort Bonifacio",
                    "Western Bicutan",
                    "Pinagsama",
                    "Ususan"
                ],
                "Pasig": [
                    "Kapitolyo",
                    "Ortigas Center",
                    "San Antonio"
                ]
            }
        },

        "North Luzon": {
            "Pangasinan": {
                "Dagupan": [
                    "Bonuan Gueset",
                    "Pantal",
                    "Tapuac"
                ],
                "Urdaneta": [
                    "Nancayasan",
                    "San Vicente"
                ]
            },
            "La Union": {
                "San Fernando": [
                    "Catbangen",
                    "Pagdalagan"
                ]
            },
            "Benguet": {
                "Baguio": [
                    "Burnham-Legarda",
                    "Camp 7"
                ]
            }
        },

        "South Luzon": {
            "Cavite": {
                "Bacoor": [
                    "Molino I",
                    "Molino II"
                ],
                "Dasmariñas": [
                    "Salitran I",
                    "Paliparan I"
                ]
            },
            "Laguna": {
                "Calamba": [
                    "Real",
                    "Canlubang"
                ],
                "Santa Rosa": [
                    "Balibago",
                    "Tagapo"
                ]
            },
            "Batangas": {
                "Batangas City": [
                    "Alangilan",
                    "Pallocan West"
                ]
            }
        },

        "Visayas": {
            "Cebu": {
                "Cebu City": [
                    "Lahug",
                    "Mabolo",
                    "Guadalupe"
                ],
                "Mandaue": [
                    "Banilad",
                    "Tipolo"
                ]
            },
            "Iloilo": {
                "Iloilo City": [
                    "Jaro",
                    "Mandurriao"
                ]
            }
        },

        "Mindanao": {
            "Davao del Sur": {
                "Davao City": [
                    "Buhangin",
                    "Matina Crossing",
                    "Talomo"
                ]
            },
            "Misamis Oriental": {
                "Cagayan de Oro": [
                    "Carmen",
                    "Lapasan"
                ]
            },
            "South Cotabato": {
                "General Santos": [
                    "Lagao",
                    "Calumpang"
                ]
            }
        }
    };

    const regionNames = [
        "Metro Manila",
        "Mindanao",
        "North Luzon",
        "South Luzon",
        "Visayas"
    ];

    /* =====================================
       LOCATION DROPDOWNS
    ===================================== */

    function resetSelect(select, placeholder) {
        select.replaceChildren(
            new Option(placeholder, "")
        );

        select.disabled = true;
    }

    function populateSelect(
        select,
        values,
        placeholder,
        selected = ""
    ) {
        resetSelect(select, placeholder);

        const unique = [
            ...new Set(values.filter(Boolean))
        ];

        // Preserve previously saved values when editing.
        if (selected && !unique.includes(selected)) {
            unique.push(selected);
        }

        unique.forEach(value => {
            select.add(new Option(value, value));
        });

        select.disabled = unique.length === 0;

        if (selected) {
            select.value = selected;
        }
    }

    function getProvinces(regionName) {
        return Object.keys(
            locations[regionName] || {}
        );
    }

    function getCities(regionName, provinceName) {
        return Object.keys(
            locations[regionName]?.[provinceName] || {}
        );
    }

    function getBarangays(
        regionName,
        provinceName,
        cityName
    ) {
        return (
            locations[regionName]?.[provinceName]?.[cityName]
            || []
        );
    }

    function inferRegion(provinceName) {
        for (const [regionName, provinces] of
            Object.entries(locations)) {

            if (Object.prototype.hasOwnProperty.call(
                provinces,
                provinceName
            )) {
                return regionName;
            }
        }

        return "";
    }

    function loadRegions(selected = "") {
        populateSelect(
            region,
            regionNames,
            "Select region",
            selected
        );
    }

    function loadProvinces(selected = "") {
        populateSelect(
            province,
            getProvinces(region.value),
            "Select province",
            selected
        );
    }

    function loadCities(selected = "") {
        populateSelect(
            city,
            getCities(
                region.value,
                province.value
            ),
            "Select city",
            selected
        );
    }

    function loadBarangays(selected = "") {
        populateSelect(
            barangay,
            getBarangays(
                region.value,
                province.value,
                city.value
            ),
            "Select barangay",
            selected
        );
    }

    region.addEventListener("change", () => {
        loadProvinces();

        resetSelect(city, "Select city");
        resetSelect(barangay, "Select barangay");
    });

    province.addEventListener("change", () => {
        loadCities();

        resetSelect(barangay, "Select barangay");
    });

    city.addEventListener("change", () => {
        loadBarangays();
    });

    /* =====================================
       FORM FIELDS
    ===================================== */

    function setField(name, value) {
        const element = form.elements.namedItem(name);

        if (element && "value" in element) {
            element.value = value ?? "";
        }
    }

    function populateForm(data, editing = false) {
        form.reset();

        action.value = editing
            ? "update"
            : "create";

        addressId.value = editing
            ? String(data.id)
            : "0";

        title.textContent = editing
            ? "Edit Address"
            : "New Address";

        subtitle.textContent = editing
            ? "Update your delivery address details"
            : "Add a delivery address for faster checkout";

        saveButton.textContent = editing
            ? "Save Changes"
            : "Save Address";

        [
            "recipient_first_name",
            "recipient_last_name",
            "phone",
            "country",
            "postal_code",
            "address_line1",
            "address_line2"
        ].forEach(field => {
            setField(field, data[field] ?? "");
        });

        /* RESTORE LOCATION */
        const selectedRegion = inferRegion(
            data.province || ""
        );

        loadRegions(selectedRegion);
        loadProvinces(data.province || "");
        loadCities(data.city || "");
        loadBarangays(data.barangay || "");

        /* =====================================
           HOME / OFFICE ONLY
        ===================================== */

        const selectedLabel =
            data.address_label === "Office"
                ? "Office"
                : "Home";

        const labelInputs = form.querySelectorAll(
            'input[name="address_label"]'
        );

        labelInputs.forEach(input => {
            input.checked =
                input.value === selectedLabel;
        });

        /* =====================================
           DEFAULT ADDRESS CHECKBOX
        ===================================== */

        const isCurrentDefault =
            editing &&
            Number(data.is_default) === 1;

        const isFirstAddress =
            !editing &&
            Object.keys(addresses).length === 0;

        // First address is always default.
        // Existing default cannot be unchecked.
        defaultCheckbox.checked =
            isCurrentDefault ||
            isFirstAddress ||
            Number(data.is_default) === 1;

        defaultCheckbox.disabled =
            isCurrentDefault || isFirstAddress;

        defaultHint.hidden =
            !isCurrentDefault && !isFirstAddress;

        if (isFirstAddress) {
            defaultHint.textContent =
                "Your first address automatically becomes the default.";
        } else if (isCurrentDefault) {
            defaultHint.textContent =
                "This is your current default address.";
        }

        /* RESET BUTTON */
        saveButton.disabled = false;
    }

    /* =====================================
       MODAL OPEN / CLOSE
    ===================================== */

    let previousFocus = null;

    function openModal(
        data = defaultForm,
        editing = false
    ) {
        previousFocus = document.activeElement;

        populateForm(data, editing);

        modal.hidden = false;

        document.body.classList.add(
            "pv-modal-open"
        );

        if (body) {
            body.scrollTop = 0;
        }

        requestAnimationFrame(() => {
            document.getElementById(
                "recipient_first_name"
            )?.focus();
        });
    }

    function closeModal() {
        modal.hidden = true;

        document.body.classList.remove(
            "pv-modal-open"
        );

        if (
            previousFocus &&
            typeof previousFocus.focus === "function"
        ) {
            previousFocus.focus();
        }
    }

    /* ADD ADDRESS */
    document.getElementById("openAddressModal")
        ?.addEventListener("click", () => {
            openModal(defaultForm, false);
        });

    document.querySelectorAll(
        "[data-address-add]"
    ).forEach(button => {
        button.addEventListener("click", () => {
            openModal(defaultForm, false);
        });
    });

    /* EDIT ADDRESS */
    document.querySelectorAll(
        "[data-address-edit]"
    ).forEach(button => {
        button.addEventListener("click", () => {
            const id = button.dataset.addressEdit;
            const address = addresses[id];

            if (address) {
                openModal(address, true);
            }
        });
    });

    /* CLOSE BUTTON */
    document.getElementById(
        "closeAddressModal"
    )?.addEventListener("click", closeModal);

    /* CANCEL BUTTON */
    document.getElementById(
        "cancelAddressModal"
    )?.addEventListener("click", closeModal);

    /* CLICK OUTSIDE */
    modal.addEventListener("click", event => {
        if (event.target === modal) {
            closeModal();
        }
    });

    /* KEYBOARD ACCESSIBILITY */
    document.addEventListener("keydown", event => {
        if (modal.hidden) return;

        if (event.key === "Escape") {
            closeModal();
            return;
        }

        if (event.key !== "Tab") return;

        const focusable = [
            ...modal.querySelectorAll(
                'button:not(:disabled), ' +
                'input:not(:disabled), ' +
                'select:not(:disabled), ' +
                'textarea:not(:disabled)'
            )
        ].filter(element =>
            element.getClientRects().length > 0
        );

        if (!focusable.length) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (
            event.shiftKey &&
            document.activeElement === first
        ) {
            event.preventDefault();
            last.focus();
        } else if (
            !event.shiftKey &&
            document.activeElement === last
        ) {
            event.preventDefault();
            first.focus();
        }
    });

    /* =====================================
       DELETE ADDRESS
    ===================================== */

    document.querySelectorAll(
        "[data-address-delete-form]"
    ).forEach(deleteForm => {
        deleteForm.addEventListener(
            "submit",
            event => {
                const confirmed = confirm(
                    "Are you sure you want to delete this address?"
                );

                if (!confirmed) {
                    event.preventDefault();
                }
            }
        );
    });

    /* =====================================
       LOCATION BUTTON
    ===================================== */

    document.getElementById(
        "addressLocationButton"
    )?.addEventListener("click", () => {

        const parts = [
            form.elements.namedItem(
                "address_line1"
            )?.value,
            barangay.value,
            city.value,
            province.value,
            "Philippines"
        ].filter(Boolean);

        if (parts.length < 3) {
            alert(
                "Please select your location and enter your street address first."
            );
            return;
        }

        const url =
            "https://www.google.com/maps/search/?api=1&query=" +
            encodeURIComponent(parts.join(", "));

        window.open(
            url,
            "_blank",
            "noopener,noreferrer"
        );
    });

    /* =====================================
       FORM SUBMISSION
    ===================================== */

    form.addEventListener("submit", event => {

        if (!form.reportValidity()) {
            event.preventDefault();
            return;
        }

        /* VALIDATE HOME / OFFICE */
        const selectedLabel = form.querySelector(
            'input[name="address_label"]:checked'
        );

        if (
            !selectedLabel ||
            !["Home", "Office"].includes(
                selectedLabel.value
            )
        ) {
            event.preventDefault();

            alert(
                "Please select Home or Office."
            );
            return;
        }

        /* VALIDATE PROVINCE */
        const knownProvinces = getProvinces(
            region.value
        );

        if (
            region.value &&
            knownProvinces.length &&
            !knownProvinces.includes(
                province.value
            )
        ) {
            event.preventDefault();

            alert(
                "Please select a valid province."
            );

            province.focus();
            return;
        }

        /* VALIDATE CITY */
        const knownCities = getCities(
            region.value,
            province.value
        );

        if (
            knownCities.length &&
            !knownCities.includes(city.value)
        ) {
            event.preventDefault();

            alert(
                "Please select a valid city."
            );

            city.focus();
            return;
        }

        /* VALIDATE BARANGAY */
        const knownBarangays = getBarangays(
            region.value,
            province.value,
            city.value
        );

        if (
            barangay.value &&
            knownBarangays.length &&
            !knownBarangays.includes(
                barangay.value
            )
        ) {
            event.preventDefault();

            alert(
                "Please select a valid barangay."
            );

            barangay.focus();
            return;
        }

        /* PREVENT DOUBLE SUBMISSION */
        saveButton.disabled = true;
        saveButton.textContent = "Saving...";
    });

    /* =====================================
       OPEN MODAL FROM URL
    ===================================== */

    if (config.openModal) {
        openModal(
            config.initialForm || defaultForm,
            Boolean(config.isEdit)
        );
    }
});
