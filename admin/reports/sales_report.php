<?php
/*
==========================================
PUREVIA - SALES REPORT
Database-driven admin dashboard
==========================================
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

/* ADMIN ACCESS */

if (
    !isset($_SESSION['user_id']) ||
    (int) ($_SESSION['role_id'] ?? 0) !== 1
) {
    http_response_code(403);
    exit('Access denied.');
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    throw new RuntimeException(
        'Database connection unavailable.'
    );
}

$conn->set_charset('utf8mb4');

/* HELPERS */

function reportEscape(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function reportMoney(mixed $amount): string
{
    return '₱' . number_format((float) $amount, 2);
}

function reportRows(mysqli $conn, string $sql): array
{
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function reportImageUrl(?string $path): string
{
    $path = ltrim(
        str_replace('\\', '/', trim((string) $path)),
        '/'
    );

    if (!preg_match(
        '~^uploads/products/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i',
        $path
    )) {
        return '';
    }

    if (!is_file(dirname(__DIR__, 2) . '/' . $path)) {
        return '';
    }

    return BASE_URL . '/' . $path;
}

/* ==========================================
   SUMMARY
========================================== */

$summary = [
    'total_revenue' => 0.0,
    'total_orders' => 0,
    'average_order_value' => 0.0,
    'delivery_rate' => 0.0
];

$summarySql = "
    SELECT
        COUNT(*) AS total_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'paid'
                     AND order_status <> 'cancelled'
                    THEN total_amount
                    ELSE 0
                END
            ), 0
        ) AS total_revenue,

        COALESCE(
            SUM(
                CASE
                    WHEN payment_status = 'paid'
                     AND order_status <> 'cancelled'
                    THEN 1
                    ELSE 0
                END
            ), 0
        ) AS paid_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN order_status IN ('delivered', 'completed')
                    THEN 1
                    ELSE 0
                END
            ), 0
        ) AS delivered_orders

    FROM orders
";

$summaryRow = $conn->query($summarySql)->fetch_assoc();

$totalOrders = (int) ($summaryRow['total_orders'] ?? 0);
$paidOrders = (int) ($summaryRow['paid_orders'] ?? 0);
$deliveredOrders = (int) ($summaryRow['delivered_orders'] ?? 0);
$totalRevenue = (float) ($summaryRow['total_revenue'] ?? 0);

$summary['total_orders'] = $totalOrders;
$summary['total_revenue'] = $totalRevenue;

$summary['average_order_value'] = $paidOrders > 0
    ? $totalRevenue / $paidOrders
    : 0;

$summary['delivery_rate'] = $totalOrders > 0
    ? ($deliveredOrders / $totalOrders) * 100
    : 0;

/* ==========================================
   TOP PRODUCTS BY REVENUE
========================================== */

$topProducts = [];

$productSql = "
    SELECT
        oi.product_id,
        oi.product_name AS name,
        SUM(oi.quantity) AS units,
        SUM(oi.subtotal) AS revenue,
        MAX(p.image) AS image_path

    FROM order_items oi

    INNER JOIN orders o
        ON o.id = oi.order_id

    LEFT JOIN products p
        ON p.id = oi.product_id

    WHERE o.payment_status = 'paid'
      AND o.order_status <> 'cancelled'

    GROUP BY
        oi.product_id,
        oi.product_name

    ORDER BY
        revenue DESC,
        units DESC,
        name ASC

    LIMIT 5
";

$productRows = reportRows($conn, $productSql);

foreach ($productRows as $index => $product) {
    $topProducts[] = [
        'rank' => $index + 1,
        'name' => (string) $product['name'],
        'units' => (int) $product['units'],
        'revenue' => (float) $product['revenue'],
        'image' => reportImageUrl($product['image_path'])
    ];
}

/* ==========================================
   REVENUE BY CATEGORY
========================================== */

$categoryRevenue = [];

$categorySql = "
    SELECT
        c.category_name AS category,

        COALESCE(
            SUM(s.revenue),
            0
        ) AS amount

    FROM categories c

    LEFT JOIN (
        SELECT
            p.category_id,
            oi.subtotal AS revenue

        FROM order_items oi

        INNER JOIN orders o
            ON o.id = oi.order_id

        INNER JOIN products p
            ON p.id = oi.product_id

        WHERE o.payment_status = 'paid'
          AND o.order_status <> 'cancelled'
    ) s
        ON s.category_id = c.id

    GROUP BY
        c.id,
        c.category_name

    ORDER BY
        amount DESC,
        c.category_name ASC
";

foreach (reportRows($conn, $categorySql) as $row) {
    $categoryRevenue[] = [
        'category' => $row['category'],
        'amount' => (float) $row['amount']
    ];
}

$maxCategoryRevenue = 0.0;

foreach ($categoryRevenue as $category) {
    $maxCategoryRevenue = max(
        $maxCategoryRevenue,
        $category['amount']
    );
}

/* ==========================================
   ORDER STATUS BREAKDOWN
========================================== */

$statusCounts = [
    'Pending' => 0,
    'Processing' => 0,
    'Shipped' => 0,
    'Delivered' => 0,
    'Cancelled' => 0
];

$statusSql = "
    SELECT
        order_status,
        COUNT(*) AS total

    FROM orders
    GROUP BY order_status
";

foreach (reportRows($conn, $statusSql) as $row) {
    $count = (int) $row['total'];

    switch ($row['order_status']) {
        case 'pending':
            $statusCounts['Pending'] += $count;
            break;

        case 'confirmed':
        case 'preparing':
            $statusCounts['Processing'] += $count;
            break;

        case 'shipping':
            $statusCounts['Shipped'] += $count;
            break;

        case 'delivered':
        case 'completed':
            $statusCounts['Delivered'] += $count;
            break;

        case 'cancelled':
            $statusCounts['Cancelled'] += $count;
            break;
    }
}

$statusBreakdown = [];

foreach ($statusCounts as $label => $count) {
    $percent = $totalOrders > 0
        ? round(($count / $totalOrders) * 100)
        : 0;

    $statusBreakdown[] = [
        'label' => $label,
        'count' => $count,
        'percent' => $percent
    ];
}

/* ==========================================
   USER BREAKDOWN
========================================== */

$userBreakdown = [
    'admins' => 0,
    'staffs' => 0,
    'customers' => 0
];

$userSql = "
    SELECT
        role_id,
        COUNT(*) AS total

    FROM users

    WHERE role_id IN (1, 2, 3)
    GROUP BY role_id
";

foreach (reportRows($conn, $userSql) as $row) {
    $count = (int) $row['total'];

    switch ((int) $row['role_id']) {
        case 1:
            $userBreakdown['admins'] = $count;
            break;

        case 2:
            $userBreakdown['staffs'] = $count;
            break;

        case 3:
            $userBreakdown['customers'] = $count;
            break;
    }
}

/* ==========================================
   CSV EXPORT
   Uses live report data
========================================== */

if (($_GET['export'] ?? '') === 'csv') {
    $filename = 'purevia-sales-report-' .
        date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=UTF-8');
    header(
        'Content-Disposition: attachment; filename="' .
            $filename . '"'
    );
    header('Cache-Control: no-store');

    $output = fopen('php://output', 'w');

    if ($output === false) {
        exit('Unable to export the report.');
    }

    // UTF-8 BOM for Excel.
    fwrite($output, "\xEF\xBB\xBF");

    /*
     * Prevent spreadsheet formula injection
     * from product/category names.
     */
    $writeRow = static function (array $row) use ($output): void {
        $safe = [];

        foreach ($row as $value) {
            $cell = (string) ($value ?? '');

            if (preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $cell)) {
                $cell = "'" . $cell;
            }

            $safe[] = $cell;
        }

        fputcsv($output, $safe);
    };

    $writeRow(['PUREVIA SALES REPORT']);
    $writeRow(['Generated', date('Y-m-d H:i:s')]);
    $writeRow([]);

    $writeRow(['SUMMARY', 'VALUE']);
    $writeRow(['Total Revenue (PHP)', $summary['total_revenue']]);
    $writeRow(['Total Orders', $summary['total_orders']]);
    $writeRow([
        'Average Order Value (PHP)',
        round($summary['average_order_value'], 2)
    ]);
    $writeRow([
        'Delivery Rate (%)',
        round($summary['delivery_rate'], 1)
    ]);

    $writeRow([]);
    $writeRow(['TOP PRODUCTS', 'UNITS SOLD', 'REVENUE (PHP)']);

    foreach ($topProducts as $product) {
        $writeRow([
            $product['name'],
            $product['units'],
            $product['revenue']
        ]);
    }

    $writeRow([]);
    $writeRow(['CATEGORY', 'REVENUE (PHP)']);

    foreach ($categoryRevenue as $category) {
        $writeRow([
            $category['category'],
            $category['amount']
        ]);
    }

    $writeRow([]);
    $writeRow(['ORDER STATUS', 'COUNT', 'PERCENT (%)']);

    foreach ($statusBreakdown as $status) {
        $writeRow([
            $status['label'],
            $status['count'],
            $status['percent']
        ]);
    }

    $writeRow([]);
    $writeRow(['USER ROLE', 'COUNT']);
    $writeRow(['Admins', $userBreakdown['admins']]);
    $writeRow(['Staffs', $userBreakdown['staffs']]);
    $writeRow(['Customers', $userBreakdown['customers']]);

    fclose($output);
    exit;
}

/* ==========================================
   SIDEBAR
========================================== */

$isAdmin = true;

ob_start();
require __DIR__ . '/../../includes/sidemenu.php';
$sidebarMarkup = ob_get_clean();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Sales Report | PureVia</title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="<?= ASSET_URL ?>/css/sidemenu.css">

    <link
        rel="stylesheet"
        href="<?= ASSET_URL ?>/css/sales_report.css">

    <script
        defer
        src="<?= ASSET_URL ?>/js/sidemenu.js"></script>

    <script
        defer
        src="<?= ASSET_URL ?>/js/sales_report.js"></script>
</head>

<body>

    <?= $sidebarMarkup ?>


    <div class="admin-layout sales-page">

        <!-- TOP BAR - SAME STRUCTURE AS PAYMENTS -->
        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Sales Report
            </p>

        </header>

        <!-- MAIN CONTENT -->
        <main class="sales-content">


            <!-- TITLE -->
            <div class="sales-title-row">

                <div class="sales-heading">
                    <h1>Reports</h1>
                    <p>Sales analytics and performance overview</p>
                </div>

                <a
                    href="<?= reportEscape(
                                strtok($_SERVER['REQUEST_URI'], '?')
                            ) ?>?export=csv"
                    class="sales-export"
                    id="exportCsv">

                    <i
                        class="fa-solid fa-download"
                        aria-hidden="true"></i>

                    Export CSV
                </a>

            </div>

            <!-- SUMMARY CARDS -->
            <section class="sales-summary-dashboard">

                <article class="analytics-card revenue-card">
                    <div class="analytics-icon">
                        <i class="fa-solid fa-peso-sign"></i>
                    </div>

                    <strong>
                        <?= reportMoney($summary['total_revenue']) ?>
                    </strong>

                    <span>Total Revenue</span>
                </article>

                <article class="analytics-card orders-card">
                    <div class="analytics-icon">
                        <i class="fa-regular fa-clipboard"></i>
                    </div>

                    <strong>
                        <?= number_format($summary['total_orders']) ?>
                    </strong>

                    <span>Total Orders</span>
                </article>

                <article class="analytics-card avg-card">
                    <div class="analytics-icon">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                    </div>

                    <strong>
                        <?= reportMoney(
                            $summary['average_order_value']
                        ) ?>
                    </strong>

                    <span>Average Order Value</span>
                </article>

                <article class="analytics-card delivery-card">
                    <div class="analytics-icon">
                        <i class="fa-solid fa-cube"></i>
                    </div>

                    <strong>
                        <?= number_format(
                            $summary['delivery_rate'],
                            1
                        ) ?>%
                    </strong>

                    <span>Delivery Rate</span>
                </article>

            </section>

            <!-- PRODUCTS AND CATEGORIES -->
            <section class="sales-report-grid">

                <!-- TOP PRODUCTS -->
                <article class="dashboard-panel">

                    <div class="dashboard-panel-header">
                        <h2>
                            <i class="fa-solid fa-chart-column"></i>
                            Top Products by Revenue
                        </h2>
                    </div>

                    <div class="top-products-list">

                        <?php if (!$topProducts): ?>

                            <div class="sales-empty-products">
                                <i class="fa-solid fa-box-open"></i>

                                <p>
                                    No paid product sales
                                    recorded yet.
                                </p>
                            </div>

                        <?php else: ?>

                            <?php foreach ($topProducts as $product): ?>

                                <div class="top-product-item">

                                    <div class="top-product-left">

                                        <span class="top-product-rank">
                                            #<?= (int) $product['rank'] ?>
                                        </span>

                                        <div class="top-product-thumb">

                                            <?php if ($product['image'] !== ''): ?>

                                                <img
                                                    src="<?= reportEscape(
                                                                $product['image']
                                                            ) ?>"
                                                    alt="<?= reportEscape(
                                                                $product['name']
                                                            ) ?>"
                                                    loading="lazy">

                                            <?php else: ?>

                                                <i
                                                    class="fa-solid fa-box-open"
                                                    aria-hidden="true"></i>

                                            <?php endif; ?>

                                        </div>

                                        <div class="top-product-info">
                                            <h3>
                                                <?= reportEscape(
                                                    $product['name']
                                                ) ?>
                                            </h3>

                                            <p>
                                                <?= (int) $product['units'] ?>
                                                units sold
                                            </p>
                                        </div>

                                    </div>

                                    <strong class="top-product-revenue">
                                        <?= reportMoney(
                                            $product['revenue']
                                        ) ?>
                                    </strong>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>
                </article>

                <!-- CATEGORY REVENUE -->
                <article class="dashboard-panel">

                    <div class="dashboard-panel-header">
                        <h2>
                            <i class="fa-solid fa-chart-simple"></i>
                            Revenue by Category
                        </h2>
                    </div>

                    <div class="category-revenue-list">

                        <?php if (!$categoryRevenue): ?>

                            <div class="sales-empty-products">
                                <p>No categories available.</p>
                            </div>

                        <?php endif; ?>

                        <?php foreach ($categoryRevenue as $item): ?>

                            <?php
                            $percentage = $maxCategoryRevenue > 0
                                ? ($item['amount'] / $maxCategoryRevenue) * 100
                                : 0;

                            $barWidth = number_format(
                                $percentage,
                                2,
                                '.',
                                ''
                            );
                            ?>

                            <div class="category-row">

                                <div class="category-row-top">
                                    <span>
                                        <?= reportEscape(
                                            $item['category']
                                        ) ?>
                                    </span>

                                    <strong>
                                        <?= reportMoney(
                                            $item['amount']
                                        ) ?>
                                    </strong>
                                </div>

                                <div
                                    class="category-bar"
                                    role="progressbar"
                                    aria-label="<?= reportEscape(
                                                    $item['category']
                                                ) ?> relative revenue"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-valuenow="<?= $barWidth ?>">

                                    <div
                                        class="category-bar-fill"
                                        style="width: <?= $barWidth ?>%;">
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>
                </article>

            </section>

            <!-- ORDER STATUS BREAKDOWN -->
            <section class="dashboard-panel status-panel">

                <div class="dashboard-panel-header">
                    <h2>Order Status Breakdown</h2>
                </div>

                <div class="status-breakdown-grid">

                    <?php foreach ($statusBreakdown as $status): ?>

                        <article
                            class="status-card <?= strtolower(
                                                    reportEscape($status['label'])
                                                ) ?>">

                            <strong>
                                <?= number_format(
                                    $status['count']
                                ) ?>
                            </strong>

                            <span>
                                <?= reportEscape($status['label']) ?>
                            </span>

                            <small>
                                <?= (int) $status['percent'] ?>%
                            </small>

                        </article>

                    <?php endforeach; ?>

                </div>

            </section>

            <!-- USER BREAKDOWN -->
            <section class="dashboard-panel user-breakdown-panel">

                <div class="dashboard-panel-header">
                    <h2>User Breakdown</h2>
                </div>

                <div class="user-breakdown-grid">

                    <div class="user-breakdown-item">

                        <i
                            class="fa-solid fa-users"
                            aria-hidden="true"></i>

                        <strong>
                            <?= number_format(
                                $userBreakdown['admins']
                            ) ?>
                        </strong>

                        <span>Admins</span>

                    </div>

                    <div class="user-breakdown-item">

                        <i
                            class="fa-solid fa-user-gear"
                            aria-hidden="true"></i>

                        <strong>
                            <?= number_format(
                                $userBreakdown['staffs']
                            ) ?>
                        </strong>

                        <span>Staffs</span>

                    </div>

                    <div class="user-breakdown-item">

                        <i
                            class="fa-solid fa-user-group"
                            aria-hidden="true"></i>

                        <strong>
                            <?= number_format(
                                $userBreakdown['customers']
                            ) ?>
                        </strong>

                        <span>Customers</span>

                    </div>

                </div>

            </section>

        </main>

    </div>

</body>

</html>