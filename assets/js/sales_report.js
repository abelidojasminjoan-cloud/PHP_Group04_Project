
/*
==========================================
PUREVIA - SALES REPORT
==========================================
*/

"use strict";

document.addEventListener("DOMContentLoaded", () => {

    /* ======================================
       CSV EXPORT
    ====================================== */

    const exportButton = document.getElementById("exportCsv");

    if (exportButton) {

        exportButton.addEventListener("click", () => {

            exportButton.classList.add("is-exporting");

            exportButton.setAttribute(
                "aria-label",
                "Downloading sales report CSV"
            );

        });

    }

    /* ======================================
       CATEGORY REVENUE BARS
    ====================================== */

    const bars = document.querySelectorAll(
        ".category-bar-fill"
    );

    bars.forEach((bar) => {

        const value = Number.parseFloat(
            bar.dataset.width ?? "0"
        );

        if (!Number.isFinite(value)) {
            bar.style.width = "0%";
            return;
        }

        const percentage = Math.min(
            100,
            Math.max(0, value)
        );

        bar.style.width = `${percentage}%`;

    });

});


/* ==========================================
   PUREVIA SALES REPORT FILTER SWITCHING
========================================== */

document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("salesPeriodForm");

    if (!form) return;

    const radios = form.querySelectorAll(
        'input[name="range"]'
    );

    const groups = form.querySelectorAll(
        "[data-period]"
    );

    function updatePeriodFields() {

        const selected = form.querySelector(
            'input[name="range"]:checked'
        )?.value || "monthly";

        groups.forEach(function (group) {

            const active =
                group.dataset.period === selected;

            group.hidden = !active;

            group.querySelectorAll("input").forEach(
                function (input) {
                    input.disabled = !active;
                    input.required = active;
                }
            );

        });

    }

    radios.forEach(function (radio) {
        radio.addEventListener(
            "change",
            updatePeriodFields
        );
    });

    updatePeriodFields();

    form.addEventListener("submit", function (event) {

        const from = form.querySelector("#from");
        const to = form.querySelector("#to");

        if (
            from &&
            to &&
            !from.disabled &&
            from.value &&
            to.value &&
            from.value > to.value
        ) {
            event.preventDefault();
            alert("The From Date cannot be after the To Date.");
        }

    });

});
