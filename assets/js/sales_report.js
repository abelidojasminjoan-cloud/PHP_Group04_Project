
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
