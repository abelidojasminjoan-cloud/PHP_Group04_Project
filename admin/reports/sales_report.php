<?php
declare(strict_types=1);

/* ==========================================
   PUREVIA - ADMIN SALES REPORT
   File: admin/reports/sales_report.php
========================================== */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

/* ==========================================
   ADMIN AUTHENTICATION
========================================== */

$adminId = (int) ($_SESSION['user_id'] ?? 0);

if ($adminId < 1) {
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$auth = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
      AND role_id = 1
      AND status = 'active'
    LIMIT 1
");

$auth->bind_param('i', $adminId);
$auth->execute();

$isAdmin = (bool) $auth->get_result()->fetch_assoc();
$auth->close();

if (!$isAdmin) {
    http_response_code(403);
    exit('Administrator access required.');
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
   DATE FILTER
========================================== */

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-t');

if (!is_string($from) || !is_string($to)) {
    http_response_code(422);
    exit('Invalid dates.');
}

foreach ([$from, $to] as $date) {
    $parsed = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $date
    );

    if (
        !$parsed ||
        $parsed->format('Y-m-d') !== $date
    ) {
        http_response_code(422);
        exit('Use dates in YYYY-MM-DD format.');
    }
}

if ($from > $to) {
    http_response_code(422);
    exit('Start date must not exceed end date.');
}

$end = (new DateTimeImmutable($to))
    ->modify('+1 day')
    ->format('Y-m-d');

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
   CSV EXPORT
========================================== */

if (($_GET['export'] ?? '') === 'csv') {

    header('Content-Type: text/csv; charset=UTF-8');

    header(
        'Content-Disposition: attachment; filename="purevia-sales-'
        . $from . '-to-' . $to . '.csv"'
    );

    header('Cache-Control: no-store');

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

    $writeCsv([
        'PureVia Sales Report (by order date)',
        $from,
        $to
    ]);

    $writeCsv([]);

    $summaryRows = [
        ['Orders Placed', $totalOrders],
        ['Gross Collected', $overview['gross_paid']],
        ['Completed Refunds', $overview['refunds_paid']],
        ['Net Collected', $overview['net_paid']],
        ['Pending Payment Value', $overview['pending_value']],
        ['Return Requests', $returnCount],
        ['Refund Requests', $refundRequests],
        ['Replacement Requests', $replacementRequests]
    ];

    foreach ($summaryRows as $row) {
        $writeCsv($row);
    }

    $writeCsv([]);
    $writeCsv(['Order Status', 'Count', 'Percent']);

    foreach ($orderStatuses as $status => $count) {
        $percent = $totalOrders > 0
            ? round(100 * $count / $totalOrders, 1)
            : 0;

        $writeCsv([
            $statusLabels[$status],
            $count,
            $percent . '%'
        ]);
    }

    $writeCsv([]);
    $writeCsv(['Return Resolution', 'Status', 'Count']);

    foreach ($returnRows as $row) {
        $writeCsv([
            $row['resolution'],
            $row['status'],
            $row['total']
        ]);
    }

    $writeCsv([]);
    $writeCsv([
        'Order Number',
        'Placed At',
        'Order Status',
        'Payment Status',
        'Order Total',
        'Gross Paid',
        'Refunded',
        'Net Paid'
    ]);

    $orders = srRows($conn, "
        SELECT
            o.order_number,
            o.created_at,
            o.order_status,
            o.payment_status,
            o.total_amount,

            COALESCE(pay.gross_paid, 0)
                AS gross_paid,

            $refundAmount AS refunded,

            COALESCE(pay.gross_paid, 0)
                - $refundAmount AS net_paid

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

        ORDER BY o.created_at DESC, o.id DESC
    ", $from, $end);

    foreach ($orders as $order) {
        $writeCsv(array_values($order));
    }

    fclose($out);
    exit;
}

/* ==========================================
   EXPORT LINK
========================================== */

$csvUrl = '?' . http_build_query([
    'from' => $from,
    'to' => $to,
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

    <title>Sales Reports | PureVia Admin</title>

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
        href="<?= srEsc(BASE_URL) ?>/assets/css/sales_report.css"
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

            <section class="sales-page-header">

                <div class="sales-heading">

                    <h1>Sales Reports</h1>

                    <p>
                        Order-cohort financial report
                        &middot;
                        <?= srEsc($from) ?>
                        to
                        <?= srEsc($to) ?>
                    </p>

                </div>

                <!-- EXPORT CSV -->

                <a
                    href="<?= srEsc($csvUrl) ?>"
                    class="sales-add-btn"
                    id="exportCsv"
                >

                    <i class="fa-solid fa-file-csv"></i>

                    Export CSV

                </a>

            </section>

            <!-- ==================================
                 DATE FILTER
            =================================== -->

            <section class="sales-panel">

                <form
                    method="GET"
                    class="sales-filter-bar"
                >

                    <div class="sales-filter-group">

                        <label for="from">
                            From
                        </label>

                        <input
                            type="date"
                            id="from"
                            name="from"
                            value="<?= srEsc($from) ?>"
                            required
                        >

                    </div>

                    <div class="sales-filter-group">

                        <label for="to">
                            To
                        </label>

                        <input
                            type="date"
                            id="to"
                            name="to"
                            value="<?= srEsc($to) ?>"
                            required
                        >

                    </div>

                    <div class="sales-filter-actions">

                        <button
                            type="submit"
                            class="sales-filter-btn"
                        >
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

                    <span>
                        Net Collected
                    </span>

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
