/* =========================================
   PUREVIA - SALES REPORT
========================================= */

document.addEventListener('DOMContentLoaded', function () {
    const exportButton = document.getElementById('exportCsv');

    if (!exportButton) {
        return;
    }

    exportButton.addEventListener('click', function () {

        const csv = rows
            .map(function (row) {
                return row.map(function (value) {
                    return `"${String(value).replace(/"/g, '""')}"`;
                }).join(',');
            })
            .join('\r\n');

        const blob = new Blob(
            ['\ufeff', csv],
            { type: 'text/csv;charset=utf-8;' }
        );

        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = url;
        link.download = 'purevia-sales-report.csv';

        document.body.appendChild(link);
        link.click();
        link.remove();

        URL.revokeObjectURL(url);
    });
});