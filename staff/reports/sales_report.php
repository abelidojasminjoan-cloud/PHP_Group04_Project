<?php
declare(strict_types=1);

/* ==========================================
   PUREVIA - ADMIN SALES REPORT
   File: admin/reports/sales_report.php
========================================== */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';


/* ==========================================
   PUREVIA STAFF AUTHENTICATION
   Only active staff accounts are allowed.
========================================== */

$staffId = (int) ($_SESSION['user_id'] ?? 0);

if ($staffId < 1) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$auth = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
      AND role_id = 2
      AND status = 'active'
    LIMIT 1
");

$auth->bind_param('i', $staffId);
$auth->execute();

$isStaff = (bool) $auth->get_result()->fetch_assoc();

$auth->close();

if (!$isStaff) {
    http_response_code(403);
    exit('Staff access required.');
}


header('Cache-Control: private, no-store');

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+08:00'");

/* ==========================================
   HELPER FUNCTIONS
========================================== */

function srEsc(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function srPeso(mixed $value): string
{
    return '₱' . number_format((float) $value, 2);
}

function srRows(
    mysqli $db,
    string $sql,
    string $start,
    string $end
): array {
    $stmt = $db->prepare($sql);

    $stmt->bind_param('ss', $start, $end);
    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

    return $rows;
}

function srTableExists(
    mysqli $db,
    string $tableName
): bool {
    $stmt = $db->prepare("
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
        LIMIT 1
    ");

    $stmt->bind_param('s', $tableName);
    $stmt->execute();

    $exists = (bool) $stmt->get_result()->fetch_row();

    $stmt->close();

    return $exists;
}

function srCsvValue(mixed $value): string
{
    $text = (string) ($value ?? '');

    // Prevent spreadsheet formula injection.
    return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $text)
        ? "'" . $text
        : $text;
}


/* ==========================================
   PUREVIA SALES REPORT
   WEEKLY / MONTHLY / YEARLY / CUSTOM FILTER
========================================== */

date_default_timezone_set('Asia/Manila');

$today = new DateTimeImmutable('today');

/*
 * Support the old ?month= and ?from=&to=
 * links as well as the new filter.
 */
$range = $_GET['range'] ?? (
    isset($_GET['month'])
        ? 'monthly'
        : (
            isset($_GET['from'], $_GET['to'])
                ? 'custom'
                : 'monthly'
        )
);

$allowedRanges = [
    'weekly',
    'monthly',
    'yearly',
    'custom'
];

if (
    !is_string($range) ||
    !in_array($range, $allowedRanges, true)
) {
    http_response_code(422);
    exit('Invalid report period.');
}

/* ==========================================
   DATE VALIDATION HELPER
========================================== */

$readDate = static function (
    mixed $value
): DateTimeImmutable {

    if (!is_string($value)) {
        http_response_code(422);
        exit('Invalid report date.');
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $value
    );

    if (
        !$date ||
        $date->format('Y-m-d') !== $value
    ) {
        http_response_code(422);
        exit('Use dates in YYYY-MM-DD format.');
    }

    return $date;
};

/* ==========================================
   FILTER INPUT VALUES
========================================== */

$weekDate = $_GET['week_date']
    ?? $today->format('Y-m-d');

$month = $_GET['month']
    ?? $today->format('Y-m');

$year = $_GET['year']
    ?? $today->format('Y');

$customFrom = $_GET['from']
    ?? $today->format('Y-m-01');

$customTo = $_GET['to']
    ?? $today->format('Y-m-t');

/* ==========================================
   RESOLVE REPORT DATES
========================================== */

switch ($range) {

    case 'weekly':

        $anchor = $readDate($weekDate);

        // Monday through Sunday.
        $startDate = $anchor->modify(
            'monday this week'
        );

        $endDate = $startDate->modify(
            '+6 days'
        );

        break;

    case 'monthly':

        if (
            !is_string($month) ||
            !preg_match(
                '/^\d{4}-(0[1-9]|1[0-2])$/D',
                $month
            )
        ) {
            http_response_code(422);
            exit('Invalid report month.');
        }

        $startDate = $readDate(
            $month . '-01'
        );

        $endDate = $startDate->modify(
            'last day of this month'
        );

        break;

    case 'yearly':

        if (
            !is_string($year) ||
            !preg_match('/^\d{4}$/D', $year) ||
            (int) $year < 1
        ) {
            http_response_code(422);
            exit('Invalid report year.');
        }

        $startDate = $readDate(
            $year . '-01-01'
        );

        $endDate = $readDate(
            $year . '-12-31'
        );

        break;

    case 'custom':

        $startDate = $readDate(
            $customFrom
        );

        $endDate = $readDate(
            $customTo
        );

        break;
}

/* ==========================================
   FINAL DATES USED IN SQL
========================================== */

$from = $startDate->format('Y-m-d');

$to = $endDate->format('Y-m-d');

if ($from > $to) {
    http_response_code(422);
    exit('Start date must not exceed end date.');
}

/*
 * Exclusive end date ensures the entire
 * last selected day is included.
 */
$end = $endDate
    ->modify('+1 day')
    ->format('Y-m-d');

/* ==========================================
   LABELS FOR THE FILTER UI
========================================== */

$rangeLabels = [
    'weekly'  => 'Weekly',
    'monthly' => 'Monthly',
    'yearly'  => 'Yearly',
    'custom'  => 'Custom Date Range'
];



/* ==========================================
   CHECK AVAILABLE RETURN TABLES
========================================== */

$hasRefunds = srTableExists($conn, 'refunds');

$hasReturns = srTableExists(
    $conn,
    'order_return_requests'
);

$hasReturnItems = srTableExists(
    $conn,
    'order_return_items'
);

/* ==========================================
   REFUND SQL
========================================== */

$refundJoin = $hasRefunds ? "
    LEFT JOIN (
        SELECT
            order_id,
            SUM(amount) AS amount
        FROM refunds
        GROUP BY order_id
    ) rf
        ON rf.order_id = o.id
" : '';

$refundAmount = $hasRefunds
    ? 'COALESCE(rf.amount, 0)'
    : '0';

/* ==========================================
   SALES SUMMARY
========================================== */

$overview = srRows($conn, "
    SELECT
        COUNT(*) AS total_orders,

        COALESCE(SUM(
            o.order_status = 'cancelled'
        ), 0) AS cancelled_orders,

        COALESCE(SUM(
            o.order_status IN (
                'delivered',
                'completed',
                'returned'
            )
        ), 0) AS delivered_orders,

        COALESCE(SUM(
            COALESCE(pay.gross_paid, 0)
        ), 0) AS gross_paid,

        COALESCE(SUM(
            $refundAmount
        ), 0) AS refunds_paid,

        COALESCE(SUM(
            COALESCE(pay.gross_paid, 0)
            - $refundAmount
        ), 0) AS net_paid,

        COALESCE(SUM(
            COALESCE(pay.gross_paid, 0) > 0
        ), 0) AS paid_orders,

        COALESCE(SUM(
            CASE
                WHEN o.payment_status = 'pending'
                 AND o.order_status <> 'cancelled'
                THEN o.total_amount
                ELSE 0
            END
        ), 0) AS pending_value

    FROM orders o

    LEFT JOIN (
        SELECT
            order_id,
            SUM(amount) AS gross_paid
        FROM payments
        WHERE payment_status IN ('paid', 'refunded')
        GROUP BY order_id
    ) pay
        ON pay.order_id = o.id

    $refundJoin

    WHERE o.created_at >= ?
      AND o.created_at < ?
", $from, $end)[0];

$totalOrders = (int) $overview['total_orders'];
$paidOrders = (int) $overview['paid_orders'];

$averageNetOrder = $paidOrders > 0
    ? (float) $overview['net_paid'] / $paidOrders
    : 0;

$notCancelled = $totalOrders
    - (int) $overview['cancelled_orders'];

$deliveryRate = $notCancelled > 0
    ? 100 * (int) $overview['delivered_orders']
        / $notCancelled
    : 0;

/* ==========================================
   ORDER STATUS BREAKDOWN
   ALL EIGHT STATUSES
========================================== */

$statusRows = srRows($conn, "
    SELECT
        order_status,
        COUNT(*) AS total
    FROM orders
    WHERE created_at >= ?
      AND created_at < ?
    GROUP BY order_status
", $from, $end);

$orderStatuses = [
    'pending'   => 0,
    'confirmed' => 0,
    'preparing' => 0,
    'shipping'  => 0,
    'delivered' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'returned'  => 0
];

foreach ($statusRows as $row) {
    $status = strtolower(
        (string) $row['order_status']
    );

    if (array_key_exists($status, $orderStatuses)) {
        $orderStatuses[$status] = (int) $row['total'];
    }
}

$statusLabels = [
    'pending'   => 'Pending',
    'confirmed' => 'Confirmed',
    'preparing' => 'Preparing',
    'shipping'  => 'Shipping',
    'delivered' => 'Delivered',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'returned'  => 'Returned'
];

/* ==========================================
   RETURN AND REPLACEMENT STATISTICS
========================================== */

$returnRows = $hasReturns
    ? srRows($conn, "
        SELECT
            resolution,
            status,
            COUNT(*) AS total
        FROM order_return_requests
        WHERE created_at >= ?
          AND created_at < ?
        GROUP BY resolution, status
        ORDER BY resolution, status
    ", $from, $end)
    : [];

$returnCount = 0;
$refundRequests = 0;
$replacementRequests = 0;

foreach ($returnRows as $row) {
    $count = (int) $row['total'];

    $returnCount += $count;

    if ($row['resolution'] === 'refund') {
        $refundRequests += $count;
    }

    if ($row['resolution'] === 'replacement') {
        $replacementRequests += $count;
    }
}

/* ==========================================
   REFUNDED PRODUCT CALCULATIONS
========================================== */

$hasItemRefunds = (
    $hasRefunds &&
    $hasReturns &&
    $hasReturnItems
);

/*
   Aggregate return quantities before joining.
   This avoids multiplying order-item revenue
   when several return records exist.
*/

$deductJoin = $hasItemRefunds ? "
    LEFT JOIN (
        SELECT
            ri.order_item_id,
            SUM(ri.quantity) AS refunded_qty
        FROM order_return_items ri
        INNER JOIN order_return_requests rr
            ON rr.id = ri.return_request_id
           AND rr.resolution = 'refund'
        INNER JOIN refunds rf
            ON rf.return_request_id = rr.id
        GROUP BY ri.order_item_id
    ) returned_items
        ON returned_items.order_item_id = oi.id
" : '';

$qtyDeduction = $hasItemRefunds
    ? 'LEAST(oi.quantity, COALESCE(returned_items.refunded_qty, 0))'
    : '0';

$itemNet = "
    oi.subtotal -
    (($qtyDeduction) * oi.unit_price)
";

$itemUnits = "
    oi.quantity - ($qtyDeduction)
";

/* ==========================================
   TOP PRODUCTS
========================================== */

$topProducts = srRows($conn, "
    SELECT
        oi.product_name,

        SUM($itemUnits) AS units,

        SUM($itemNet) AS revenue

    FROM order_items oi

    INNER JOIN orders o
        ON o.id = oi.order_id

    INNER JOIN (
        SELECT DISTINCT order_id
        FROM payments
        WHERE payment_status IN ('paid', 'refunded')
    ) paid
        ON paid.order_id = o.id

    $deductJoin

    WHERE o.created_at >= ?
      AND o.created_at < ?

    GROUP BY
        oi.product_id,
        oi.product_name

    HAVING units > 0

    ORDER BY revenue DESC, units DESC

    LIMIT 5
", $from, $end);

/* ==========================================
   CATEGORY REVENUE
========================================== */

$categoryRows = srRows($conn, "
    SELECT
        COALESCE(
            c.category_name,
            'Uncategorized'
        ) AS category,

        SUM($itemNet) AS revenue

    FROM order_items oi

    INNER JOIN orders o
        ON o.id = oi.order_id

    INNER JOIN (
        SELECT DISTINCT order_id
        FROM payments
        WHERE payment_status IN ('paid', 'refunded')
    ) paid
        ON paid.order_id = o.id

    LEFT JOIN products p
        ON p.id = oi.product_id

    LEFT JOIN categories c
        ON c.id = p.category_id

    $deductJoin

    WHERE o.created_at >= ?
      AND o.created_at < ?

    GROUP BY c.id, c.category_name

    ORDER BY revenue DESC
", $from, $end);

$maxCategory = max(array_merge(
    [0.0],
    array_map(
        static fn(array $row): float =>
            (float) $row['revenue'],
        $categoryRows
    )
));

/* ==========================================
   CUSTOMER BREAKDOWN
========================================== */

$customers = $conn->query("
    SELECT
        COUNT(*) AS total,

        COALESCE(SUM(
            status = 'active'
        ), 0) AS active

    FROM users
    WHERE role_id = 3
")->fetch_assoc();

$buyers = srRows($conn, "
    SELECT
        COUNT(DISTINCT user_id) AS total
    FROM orders
    WHERE created_at >= ?
      AND created_at < ?
", $from, $end)[0]['total'];


/* ==========================================
   PUREVIA COMPLETE CSV REPORT
========================================== */

if (($_GET['export'] ?? '') === 'csv') {

    require_once __DIR__
        . '/../../services/SalesDetailedCsv.php';

    $exportStatus = $_GET['order_status'] ?? 'all';

    $allowedStatuses = array_merge(
        ['all'],
        array_keys($statusLabels)
    );

    if (
        !is_string($exportStatus) ||
        !in_array($exportStatus, $allowedStatuses, true)
    ) {
        http_response_code(422);
        exit('Invalid order status filter.');
    }

    header('Content-Type: text/csv; charset=UTF-8');

    header(
        'Content-Disposition: attachment; filename="' .
        'purevia-complete-sales-' .
        $from . '-to-' . $to . '.csv"'
    );

    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');

    $out = fopen('php://output', 'w');

    if ($out === false) {
        exit('Unable to export report.');
    }

    fwrite($out, "\xEF\xBB\xBF");

    $writeCsv = static function (array $values) use ($out): void {
        fputcsv(
            $out,
            array_map('srCsvValue', $values)
        );
    };

    /* ======================================
       1. GENERAL SALES SUMMARY
    ====================================== */

    $writeCsv([
        'PUREVIA SALES REPORT',
        'From',
        $from,
        'To',
        $to
    ]);

    $writeCsv([]);
    $writeCsv(['GENERAL SALES SUMMARY', 'VALUE']);

    $summaryRows = [
        ['Orders Placed', $totalOrders],
        ['Gross Collected (PHP)', $overview['gross_paid']],
        ['Completed Refunds (PHP)', $overview['refunds_paid']],
        ['Net Collected (PHP)', $overview['net_paid']],
        ['Pending Payment Value (PHP)', $overview['pending_value']],
        ['Return Requests', $returnCount],
        ['Refund Requests', $refundRequests],
        ['Replacement Requests', $replacementRequests],
        ['Registered Customers (All Time)', $customers['total']],
        ['Active Customers (All Time)', $customers['active']],
        ['Customers Who Ordered (Period)', $buyers]
    ];

    foreach ($summaryRows as $row) {
        $writeCsv($row);
    }

    /* ======================================
       2. ORDER STATUS SUMMARY
    ====================================== */

    $writeCsv([]);
    $writeCsv(['ORDER STATUS SUMMARY']);
    $writeCsv(['Order Status', 'Count', 'Percentage']);

    foreach ($orderStatuses as $status => $count) {

        $percent = $totalOrders > 0
            ? (100 * $count / $totalOrders)
            : 0;

        $writeCsv([
            $statusLabels[$status],
            $count,
            number_format($percent, 2, '.', '') . '%'
        ]);
    }

    /* ======================================
       3. RETURN AND REPLACEMENT SUMMARY
    ====================================== */

    $writeCsv([]);
    $writeCsv(['RETURN AND REPLACEMENT SUMMARY']);
    $writeCsv(['Resolution', 'Status', 'Count']);

    foreach ($returnRows as $row) {
        $writeCsv([
            $row['resolution'],
            $row['status'],
            $row['total']
        ]);
    }

  
/* ======================================
   4. ORGANIZED CUSTOMER SALES TABLES
====================================== */

/*
 * Capture the detailed CSV rows from the
 * existing service, then organize them
 * into smaller tables.
 *
 * The database queries remain unchanged.
 */

$detailHeaders = [];
$detailRows = [];

$captureCsv = static function (array $values) use (
    &$detailHeaders,
    &$detailRows
): void {

    if ($detailHeaders === []) {
        $detailHeaders = $values;
        return;
    }

    $detailRows[] = $values;
};


pureviaWriteDetailedSalesRows(
    $conn,
    $from,
    $end,
    $exportStatus,
    false,
    $hasRefunds,
    $captureCsv
);


/*
 * Read fields using their header names,
 * rather than relying on column numbers.
 */

$columnIndex = array_flip($detailHeaders);

$get = static function (
    array $row,
    string $column
) use ($columnIndex): string {

    if (!isset($columnIndex[$column])) {
        return '';
    }

    return (string) (
        $row[$columnIndex[$column]] ?? ''
    );
};

/*
 * Verify that the service returned the
 * expected detailed CSV structure.
 */

foreach ([
    'Order Number',
    'Order Date (Asia/Manila)',
    'Customer Name',
    'Product Purchased',
    'Order Status (Current)'
] as $requiredColumn) {

    if (!isset($columnIndex[$requiredColumn])) {
        throw new RuntimeException(
            'Detailed CSV column missing: ' .
            $requiredColumn
        );
    }
}

/*
 * Collect unique orders.
 *
 * Purchased items remain separate rows,
 * but customer/order information only
 * appears once in the other sections.
 */

$uniqueOrders = [];

foreach ($detailRows as $row) {

    $orderNumber = $get($row, 'Order Number');

    if (!isset($uniqueOrders[$orderNumber])) {
        $uniqueOrders[$orderNumber] = $row;
    }
}

/* ======================================
   4. CUSTOMER ORDER INFORMATION
====================================== */

$writeCsv([]);
$writeCsv(['CUSTOMER ORDER INFORMATION']);

$writeCsv([
    'Order Number',
    'Order Date',
    'Customer Name',
    'Customer Email',
    'Customer Phone',
    'Order Status'
]);

foreach ($uniqueOrders as $row) {

    $writeCsv([
        $get($row, 'Order Number'),
        $get($row, 'Order Date (Asia/Manila)'),
        $get($row, 'Customer Name'),
        $get($row, 'Customer Email'),
        $get($row, 'Customer Phone'),
        $get($row, 'Order Status (Current)')
    ]);
}

/* ======================================
   5. PURCHASED PRODUCTS
====================================== */

$writeCsv([]);
$writeCsv(['PURCHASED PRODUCTS']);

$writeCsv([
    'Order Number',
    'Product Purchased',
    'Category',
    'Variant / Size',
    'Quantity',
    'Unit Price (PHP)',
    'Item Subtotal (PHP)'
]);

foreach ($detailRows as $row) {

    $writeCsv([
        $get($row, 'Order Number'),
        $get($row, 'Product Purchased'),
        $get($row, 'Product Category'),
        $get($row, 'Variant / Size'),
        $get($row, 'Quantity'),
        $get($row, 'Unit Price (PHP)'),
        $get($row, 'Item Subtotal (PHP)')
    ]);
}

/* ======================================
   6. PAYMENT DETAILS
====================================== */

$writeCsv([]);
$writeCsv(['PAYMENT DETAILS']);

$writeCsv([
    'Order Number',
    'Payment Method',
    'Payment Status',
    'Order Total (PHP)',
    'Gross Paid (PHP)',
    'Refunded (PHP)',
    'Net Paid (PHP)'
]);

foreach ($uniqueOrders as $row) {

    $writeCsv([
        $get($row, 'Order Number'),
        $get($row, 'Payment Method(s)'),
        $get($row, 'Payment Status (Current)'),
        $get($row, 'Order Total (PHP - repeated)'),
        $get($row, 'Gross Paid (PHP - repeated)'),
        $get($row, 'Refunded (PHP - repeated)'),
        $get($row, 'Net Paid (PHP - repeated)')
    ]);
}

/* ======================================
   7. PAYMENT REFERENCES
====================================== */

$writeCsv([]);
$writeCsv(['PAYMENT REFERENCES']);

$writeCsv([
    'Order Number',
    'Payment Reference',
    'Payment Verified At'
]);

foreach ($uniqueOrders as $row) {

    $writeCsv([
        $get($row, 'Order Number'),
        $get($row, 'Payment Reference'),
        $get($row, 'Payment Verified At')
    ]);
}

/* ======================================
   8. DELIVERY INFORMATION
====================================== */

$writeCsv([]);
$writeCsv(['DELIVERY INFORMATION']);

$writeCsv([
    'Order Number',
    'Recipient Name',
    'Delivery Phone',
    'Full Delivery Address'
]);

foreach ($uniqueOrders as $row) {

    $writeCsv([
        $get($row, 'Order Number'),
        $get($row, 'Recipient Name'),
        $get($row, 'Delivery Phone'),
        $get($row, 'Full Delivery Address')
    ]);
}

// ==========================================
// FINISH CSV EXPORT
// ==========================================

fclose($out);
exit;

}


/* ==========================================
   CSV EXPORT: CURRENT APPLIED DATE RANGE
========================================== */

$csvUrl = '?' . http_build_query([
    'range'  => 'custom',
    'from'   => $from,
    'to'     => $to,
    'export' => 'csv'
]);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Sales Reports | PureVia Staff</title>

    <!-- GOOGLE FONT -->

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
    >

    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    
<!-- SHARED SIDEBAR CSS -->

<link
    rel="stylesheet"
    href="<?= srEsc(BASE_URL) ?>/assets/css/sidemenu.css"
>

<!-- SALES REPORT CSS -->

<link
    rel="stylesheet"
    href="<?= srEsc(BASE_URL) ?>/assets/css/sales_report.css?v=3"
>


    <!-- RESPONSIVE CSS -->

    <link
        rel="stylesheet"
        href="<?= srEsc(BASE_URL) ?>/assets/css/responsive.css"
    >

</head>

<body>

    <!-- ======================================
         PUREVIA ADMIN SIDEBAR
    ======================================= -->

    <?php include __DIR__ . '/../../includes/sidemenu.php'; ?>

    <!-- ======================================
         ADMIN LAYOUT
    ======================================= -->

    <div class="admin-layout">

        <!-- TOPBAR -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Sales Reports
            </p>

        </header>

        <!-- MAIN CONTENT -->

        <main class="sales-main">

            <!-- ==================================
                 PAGE HEADER
            =================================== -->

           
<!-- ==========================================
     SALES REPORT HEADER
========================================== -->

<section class="sales-page-header">

    <div class="sales-heading">

        <h1>Sales Reports</h1>

        <p>
            Sales Report &middot;
            <?= date('F j, Y', strtotime($from)) ?>
            to
            <?= date('F j, Y', strtotime($to)) ?>
        </p>

    </div>

</section>


<!-- ==========================================
     EXPORT SALES REPORT PANEL
========================================== -->

<section
    class="sales-csv-panel"
    aria-labelledby="salesCsvTitle"
>

    <div class="sales-csv-panel-header">

        <div class="sales-csv-panel-heading">

            <div class="sales-csv-panel-icon">
                <i class="fa-solid fa-file-csv"
                   aria-hidden="true"></i>
            </div>

            <div>
                <h2 id="salesCsvTitle">
                    Export Sales Report
                </h2>

               <p>
    Download detailed customer purchases,
    payment records, order statuses,
    and delivery information.
</p>
            </div>

        </div>

        <span class="sales-csv-period">
            <i class="fa-regular fa-calendar"></i>

            <?= srEsc($from) ?>
            &mdash;
            <?= srEsc($to) ?>
        </span>

    </div>

    <form
        method="GET"
        class="sales-csv-options"
        aria-label="Export complete sales report"
    >

        <!-- APPLIED REPORT DATES -->

        <input
            type="hidden"
            name="range"
            value="custom"
        >

        <input
            type="hidden"
            name="from"
            value="<?= srEsc($from) ?>"
        >

        <input
            type="hidden"
            name="to"
            value="<?= srEsc($to) ?>"
        >

        <input
            type="hidden"
            name="export"
            value="csv"
        >

        <!-- ORDER STATUS -->

        <div class="sales-csv-field">

            <label for="csvOrderStatus">
                Order Status
            </label>

            <select
                id="csvOrderStatus"
                name="order_status"
            >
                <option value="all">
                    All Statuses
                </option>

                <?php foreach ($statusLabels as $value => $label): ?>

                    <option value="<?= srEsc($value) ?>">
                        <?= srEsc($label) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        
        <!-- EXPORT BUTTON -->

        <button
            type="submit"
            class="sales-add-btn sales-csv-download"
            id="exportCsv"
        >
            <i class="fa-solid fa-download"
               aria-hidden="true"></i>

            Export Complete CSV
        </button>

    </form>

</section>

            
<!-- ==========================================
     SALES REPORT DATE FILTER
========================================== -->

<section
    class="sales-panel sales-period-panel"
    aria-labelledby="salesPeriodTitle"
>

    <div class="sales-period-heading">

        <div class="sales-period-heading-text">

            <span class="sales-period-eyebrow">
                REPORT PERIOD
            </span>

            <h2 id="salesPeriodTitle">
                Choose Your Reporting Dates
            </h2>

            <p>
                Generate sales reports by week, month,
                year, or custom date range.
            </p>

        </div>

        <span class="sales-current-period">

            <i class="fa-regular fa-calendar-check"></i>

            <?= srEsc($from) ?>
            &mdash;
            <?= srEsc($to) ?>

        </span>

    </div>

    <form
        method="GET"
        id="salesPeriodForm"
        class="sales-period-form"
    >

        <fieldset class="sales-period-types">

            <legend>
                Select Report Type
            </legend>

            <?php foreach ($rangeLabels as $value => $label): ?>

                <label class="sales-period-choice">

                    <input
                        type="radio"
                        name="range"
                        value="<?= srEsc($value) ?>"
                        <?= $range === $value ? 'checked' : '' ?>
                    >

                    <span><?= srEsc($label) ?></span>

                </label>

            <?php endforeach; ?>

        </fieldset>

        <div class="sales-period-controls">

            <!-- WEEKLY -->

            <div
                class="sales-period-input"
                data-period="weekly"
                <?= $range !== 'weekly' ? 'hidden' : '' ?>
            >

                <label for="weekDate">
                    Select Date Within the Week
                </label>

                <input
                    type="date"
                    id="weekDate"
                    name="week_date"
                    value="<?= srEsc($weekDate) ?>"
                    <?= $range === 'weekly' ? 'required' : 'disabled' ?>
                >

                <small>
                    Monday to Sunday
                </small>

            </div>

            <!-- MONTHLY -->

            <div
                class="sales-period-input"
                data-period="monthly"
                <?= $range !== 'monthly' ? 'hidden' : '' ?>
            >

                <label for="reportMonth">
                    Select Month
                </label>

                <input
                    type="month"
                    id="reportMonth"
                    name="month"
                    value="<?= srEsc($month) ?>"
                    <?= $range === 'monthly' ? 'required' : 'disabled' ?>
                >

            </div>

            <!-- YEARLY -->

            <div
                class="sales-period-input"
                data-period="yearly"
                <?= $range !== 'yearly' ? 'hidden' : '' ?>
            >

                <label for="reportYear">
                    Select Year
                </label>

                <input
                    type="number"
                    id="reportYear"
                    name="year"
                    min="1"
                    max="9999"
                    step="1"
                    value="<?= srEsc($year) ?>"
                    <?= $range === 'yearly' ? 'required' : 'disabled' ?>
                >

            </div>

            <!-- CUSTOM DATE RANGE -->

            <div
                class="sales-period-custom"
                data-period="custom"
                <?= $range !== 'custom' ? 'hidden' : '' ?>
            >

                <div class="sales-period-input">

                    <label for="from">
                        From Date
                    </label>

                    <input
                        type="date"
                        id="from"
                        name="from"
                        value="<?= srEsc($customFrom) ?>"
                        <?= $range === 'custom' ? 'required' : 'disabled' ?>
                    >

                </div>

                <div class="sales-period-input">

                    <label for="to">
                        To Date
                    </label>

                    <input
                        type="date"
                        id="to"
                        name="to"
                        value="<?= srEsc($customTo) ?>"
                        <?= $range === 'custom' ? 'required' : 'disabled' ?>
                    >

                </div>

            </div>

            <button
                type="submit"
                class="sales-filter-btn sales-period-apply"
            >
                <i class="fa-solid fa-filter"></i>
                Apply Filter
            </button>

        </div>

    </form>

</section>


            <!-- ==================================
                 MAIN SALES SUMMARY
            =================================== -->

            <section class="sales-summary-grid">

                <!-- NET REVENUE -->

                <article class="sales-summary-card revenue">

                    <div class="sales-summary-icon">
                        <i class="fa-solid fa-peso-sign"></i>
                    </div>

                    <strong>
                        <?= srPeso($overview['net_paid']) ?>
                    </strong>

                    <span>
                        Net Collected Revenue
                    </span>

                </article>

                <!-- ORDERS PLACED -->

                <article class="sales-summary-card orders">

                    <div class="sales-summary-icon">
                        <i class="fa-solid fa-bag-shopping"></i>
                    </div>

                    <strong>
                        <?= number_format($totalOrders) ?>
                    </strong>

                    <span>
                        Orders Placed
                    </span>

                </article>

                <!-- AVERAGE NET ORDER -->

                <article class="sales-summary-card average">

                    <div class="sales-summary-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>

                    <strong>
                        <?= srPeso($averageNetOrder) ?>
                    </strong>

                    <span>Average Net per Paid Order</span>

                </article>

                <!-- DELIVERY RATE -->

                <article class="sales-summary-card delivery">

                    <div class="sales-summary-icon">
                        <i class="fa-solid fa-truck"></i>
                    </div>

                    <strong>
                        <?= number_format($deliveryRate, 1) ?>%
                    </strong>

                    <span>
                        Delivered
                    </span>

                </article>

            </section>

            <!-- ==================================
                 PAYMENT AND RETURNS SUMMARY
            =================================== -->

            <section class="sales-mini-grid">

                <article class="sales-mini-card">

                    <strong>
                        <?= srPeso($overview['gross_paid']) ?>
                    </strong>

                    <span>Gross Collected</span>

                </article>

                <article class="sales-mini-card">

                    <strong>
                        <?= srPeso($overview['refunds_paid']) ?>
                    </strong>

                    <span>Completed Refunds</span>

                </article>

                <article class="sales-mini-card">

                    <strong>
                        <?= srPeso($overview['pending_value']) ?>
                    </strong>

                    <span>Pending Payment Value</span>

                </article>

                <article class="sales-mini-card">

                    <strong>
                        <?= number_format($returnCount) ?>
                    </strong>

                    <span>Return Requests</span>

                </article>

                <article class="sales-mini-card">

                    <strong>
                        <?= number_format($refundRequests) ?>
                    </strong>

                    <span>Refund Requests</span>

                </article>

                <article class="sales-mini-card">

                    <strong>
                        <?= number_format($replacementRequests) ?>
                    </strong>

                    <span>Replacement Requests</span>

                </article>

            </section>

            <!-- REFUND NOTICE -->

            <?php if (!$hasRefunds): ?>

                <p class="sales-note">
                    No refunds table is available yet.
                    Refund deductions will remain zero until
                    completed refunds are recorded.
                </p>

            <?php endif; ?>

            <!-- ==================================
                 TOP PRODUCTS AND CATEGORIES
            =================================== -->

            <div class="sales-report-grid">

                <!-- TOP PRODUCTS -->

                <section class="sales-panel">

                    <div class="sales-panel-header">

                        <h2>
                            <i class="fa-solid fa-trophy"></i>
                            Top Products (Net Paid Items)
                        </h2>

                    </div>

                    <?php if (!$topProducts): ?>

                        <div class="sales-empty-state">

                            <i class="fa-solid fa-box-open"></i>

                            <p>
                                No paid product sales in this period.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="top-products-list">

                            <?php foreach ($topProducts as $i => $product): ?>

                                <div class="top-product-item">

                                    <div class="top-product-left">

                                        <span class="top-product-rank">
                                            <?= $i + 1 ?>.
                                        </span>

                                        <span class="top-product-thumb">
                                            <i class="fa-solid fa-box"></i>
                                        </span>

                                        <div class="top-product-info">

                                            <h3>
                                                <?= srEsc(
                                                    $product['product_name']
                                                ) ?>
                                            </h3>

                                            <p>
                                                <?= (int) $product['units'] ?>
                                                units
                                            </p>

                                        </div>

                                    </div>

                                    <strong class="top-product-revenue">
                                        <?= srPeso($product['revenue']) ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </section>

                <!-- REVENUE BY CATEGORY -->

                <section class="sales-panel">

                    <div class="sales-panel-header">

                        <h2>
                            <i class="fa-solid fa-chart-column"></i>
                            Product Revenue by Category
                        </h2>

                    </div>

                    <?php if (!$categoryRows): ?>

                        <div class="sales-empty-state">

                            <i class="fa-solid fa-chart-column"></i>

                            <p>
                                No paid category sales in this period.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="category-revenue-list">

                            <?php foreach ($categoryRows as $category): ?>

                                <?php
                                $barWidth = $maxCategory > 0
                                    ? max(
                                        0,
                                        100 * (float) $category['revenue']
                                            / $maxCategory
                                    )
                                    : 0;
                                ?>

                                <div class="category-row">

                                    <div class="category-row-top">

                                        <span>
                                            <?= srEsc($category['category']) ?>
                                        </span>

                                        <strong>
                                            <?= srPeso($category['revenue']) ?>
                                        </strong>

                                    </div>

                                    <div class="category-bar">

                                        <div
                                            class="category-bar-fill"
                                            data-width="<?= number_format(
                                                min(100, $barWidth),
                                                1,
                                                '.',
                                                ''
                                            ) ?>"
                                        ></div>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </section>

            </div>

            <!-- ==================================
                 ORDER STATUS BREAKDOWN
                 ALL EIGHT STATUSES
            =================================== -->

            <section class="sales-panel status-panel">

                <div class="sales-panel-header">

                    <h2>
                        Order Status Breakdown
                    </h2>

                </div>

                <div class="status-breakdown-grid">

                    <?php foreach ($orderStatuses as $status => $count): ?>

                        <?php
                        $percentage = $totalOrders > 0
                            ? ($count / $totalOrders) * 100
                            : 0;
                        ?>

                        <article
                            class="status-card <?= srEsc($status) ?>"
                        >

                            <strong>
                                <?= number_format($count) ?>
                            </strong>

                            <span>
                                <?= srEsc($statusLabels[$status]) ?>
                            </span>

                            <small>
                                <?= number_format($percentage, 1) ?>%
                            </small>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

            <!-- ==================================
                 RETURN & REPLACEMENT REQUESTS
            =================================== -->

            <section class="sales-panel sales-returns-panel">

                <div class="sales-panel-header">

                    <h2>
                        <i class="fa-solid fa-rotate-left"></i>
                        Return &amp; Replacement Requests
                    </h2>

                </div>

                <div class="status-breakdown-grid">

                    <?php if (!$returnRows): ?>

                        <div class="sales-empty-state sales-empty-state-inline">

                            <i class="fa-solid fa-rotate-left"></i>

                            <p>
                                No return requests in this period.
                            </p>

                        </div>

                    <?php else: ?>

                        <?php foreach ($returnRows as $row): ?>

                            <article class="status-card">

                                <strong>
                                    <?= (int) $row['total'] ?>
                                </strong>

                                <span>
                                    <?= srEsc(
                                        ucfirst($row['resolution'])
                                    ) ?>

                                    &mdash;

                                    <?= srEsc(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $row['status']
                                            )
                                        )
                                    ) ?>
                                </span>

                            </article>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </section>

            <!-- ==================================
                 CUSTOMER BREAKDOWN
            =================================== -->

            <section class="sales-panel user-breakdown-panel">

                <div class="sales-panel-header">

                    <h2>
                        <i class="fa-solid fa-users"></i>
                        Customer Breakdown
                    </h2>

                </div>

                <div class="user-breakdown-grid">

                    <!-- REGISTERED CUSTOMERS -->

                    <div class="user-breakdown-item">

                        <i class="fa-solid fa-users"></i>

                        <strong>
                            <?= number_format(
                                (int) $customers['total']
                            ) ?>
                        </strong>

                        <span>
                            Registered Customers (All Time)
                        </span>

                    </div>

                    <!-- ACTIVE CUSTOMERS -->

                    <div class="user-breakdown-item">

                        <i class="fa-solid fa-user-check"></i>

                        <strong>
                            <?= number_format(
                                (int) $customers['active']
                            ) ?>
                        </strong>

                        <span>
                            Active Customers (All Time)
                        </span>

                    </div>

                    <!-- CUSTOMERS WHO ORDERED -->

                    <div class="user-breakdown-item">

                        <i class="fa-solid fa-bag-shopping"></i>

                        <strong>
                            <?= number_format((int) $buyers) ?>
                        </strong>

                        <span>
                            Customers Who Ordered (Period)
                        </span>

                    </div>

                </div>

            </section>

            <!-- ==================================
                 REPORT FOOTNOTE
            =================================== -->

            <p class="sales-report-footnote">
                Financial figures are grouped by the original
                order date. Return requests are grouped by their
                submission date.

                Gross collected includes recorded paid payments.
                Refund deductions require completed refund records.

                Replacements do not automatically reduce revenue.
                Product and category totals exclude shipping fees.
            </p>

        </main>

    </div>

    <!-- SIDEBAR JS -->

    <script
        nonce="<?= srEsc(pvScriptNonce()) ?>"
        src="<?= srEsc(BASE_URL) ?>/assets/js/sidemenu.js"
    ></script>

    <!-- SALES REPORT JS -->

    <script
        nonce="<?= srEsc(pvScriptNonce()) ?>"
        src="<?= srEsc(BASE_URL) ?>/assets/js/sales_report.js"
    ></script>

</body>
</html>
