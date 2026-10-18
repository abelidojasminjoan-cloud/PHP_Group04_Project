<?php
/*
=========================================
PUREVIA - SALES REPORT
=========================================
*/

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

/*
=========================================
ADMIN AUTHENTICATION
=========================================
*/

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
      AND role_id = 1
      AND status = 'active'
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    http_response_code(403);
    exit('Access denied. Administrator account required.');
}

/*
=========================================
LOAD REPORT DATA
=========================================
*/

require_once __DIR__ . '/sales_report_data.php';


/* =========================================
   LOAD LIVE SALES REPORT DATA
========================================= */

require_once __DIR__ . '/sales_report_data.php';

/*
=========================================
BACKEND VARIABLES FOR INTELEPHENSE
=========================================
*/

/** @var array<string, mixed> $summary */
/** @var array<int, array<string, mixed>> $topProducts */
/** @var array<int, array<string, mixed>> $categoryRevenue */
/** @var array<int, array<string, mixed>> $statusBreakdown */
/** @var array<string, int> $userBreakdown */
/** @var float $maxCategoryRevenue */


/* =========================================
   EXPORT LIVE SALES REPORT TO CSV
========================================= */

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

    // Excel UTF-8 compatibility
    fwrite($output, "\xEF\xBB\xBF");

    // Safely escape CSV fields
    $writeRow = function (array $row) use ($output) {

        $safeRow = array_map(function ($value) {

            $value = (string) ($value ?? '');

            if (preg_match('/^[=+\-@\t\r]/', $value)) {
                $value = "'" . $value;
            }

            return $value;
        }, $row);

        fputcsv($output, $safeRow, ',', '"', '');
    };

    $writeRow(['PUREVIA SALES REPORT']);
    $writeRow(['Generated', date('Y-m-d H:i:s')]);
    $writeRow([]);

    // SUMMARY

    $writeRow(['SUMMARY', 'VALUE']);

    $writeRow([
        'Total Revenue',
        $summary['total_revenue']
    ]);

    $writeRow([
        'Total Orders',
        $summary['total_orders']
    ]);

    $writeRow([
        'Average Order Value',
        $summary['average_order_value']
    ]);

    $writeRow([
        'Delivery Rate (%)',
        $summary['delivery_rate']
    ]);

    // TOP PRODUCTS

    $writeRow([]);
    $writeRow(['TOP PRODUCT', 'UNITS SOLD', 'REVENUE']);

    foreach ($topProducts as $product) {
        $writeRow([
            $product['name'],
            $product['units'],
            $product['revenue']
        ]);
    }

    // CATEGORY REVENUE

    $writeRow([]);
    $writeRow(['CATEGORY', 'REVENUE']);

    foreach ($categoryRevenue as $category) {
        $writeRow([
            $category['category'],
            $category['amount']
        ]);
    }

    // ORDER STATUS

    $writeRow([]);
    $writeRow(['ORDER STATUS', 'COUNT', 'PERCENT']);

    foreach ($statusBreakdown as $status) {
        $writeRow([
            $status['label'],
            $status['count'],
            $status['percent']
        ]);
    }

    // USER BREAKDOWN

    $writeRow([]);
    $writeRow(['ROLE', 'COUNT']);

    $writeRow(['Admins', $userBreakdown['admins']]);
    $writeRow(['Staff', $userBreakdown['staffs']]);
    $writeRow(['Customers', $userBreakdown['customers']]);

    fclose($output);
    exit;
}

/*


=========================================
LOAD EXISTING SIDEBAR
=========================================
*/

ob_start();

require_once __DIR__ .
    '/../../includes/sidemenu.php';

$sidebarMarkup = ob_get_clean();

/*
=========================================
ESCAPE HTML
=========================================
*/


/**
 * Escape a value for safe HTML output.
 *
 * @param mixed $value
 * @return string
 */
function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

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

    <main class="<?= $isAdmin ? 'admin-layout' : 'staff-layout' ?> sales-page">

        <header class="sales-topbar">
            <p>Sales Report</p>
        </header>

        <section class="sales-content">

            <!-- PAGE HEADER -->
            <div class="sales-title-row">

                <div class="sales-heading">
                    <h1>Reports</h1>
                    <p>Sales analytics and performance overview</p>
                </div>


                <a
                    href="?export=csv"
                    class="sales-export">
                    <i class="fa-solid fa-download"></i>
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
                        ₱<?= number_format((float) $summary['total_revenue'], 2) ?>
                    </strong>

                    <span>Total Revenue</span>
                </article>

                <article class="analytics-card orders-card">
                    <div class="analytics-icon">
                        <i class="fa-regular fa-clipboard"></i>
                    </div>

                    <strong>
                        <?= (int) $summary['total_orders'] ?>
                    </strong>

                    <span>Total Orders</span>
                </article>

                <article class="analytics-card avg-card">
                    <div class="analytics-icon">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                    </div>

                    <strong>
                        ₱<?= number_format((float) $summary['average_order_value'], 2) ?>
                    </strong>

                    <span>Average Order Value</span>
                </article>

                <article class="analytics-card delivery-card">
                    <div class="analytics-icon">
                        <i class="fa-solid fa-cube"></i>
                    </div>

                    <strong>
                        <?= number_format((float) $summary['delivery_rate'], 1) ?>%
                    </strong>

                    <span>Delivery Rate</span>
                </article>

            </section>

            <!-- MIDDLE GRID -->
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


                        <?php if (empty($topProducts)): ?>
                            <div class="sales-empty-products">
                                <i class="fa-solid fa-box-open"></i>
                                <p>No paid product sales recorded yet.</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($topProducts as $product): ?>
                            <div class="top-product-item">

                                <div class="top-product-left">
                                    <span class="top-product-rank">
                                        #<?= (int) $product['rank'] ?>
                                    </span>


                                    <!-- PRODUCT IMAGE -->

                                    <div class="top-product-thumb">

                                        <?php if (!empty($product['image'])): ?>

                                            <img
                                                src="<?= e($product['image']) ?>"
                                                alt="<?= e($product['name']) ?>"
                                                loading="lazy">

                                        <?php else: ?>

                                            <i
                                                class="fa-solid fa-box-open"
                                                aria-hidden="true"></i>

                                        <?php endif; ?>

                                    </div>


                                    <div class="top-product-info">
                                        <h3><?= e($product['name']) ?></h3>
                                        <p><?= (int) $product['units'] ?> units sold</p>
                                    </div>
                                </div>

                                <div class="top-product-revenue">
                                    ₱<?= number_format((float) $product['revenue'], 2) ?>
                                </div>

                            </div>
                        <?php endforeach; ?>

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

                        <?php foreach ($categoryRevenue as $item): ?>
                            <?php
                            $percentage = $maxCategoryRevenue > 0
                                ? ($item['amount'] / $maxCategoryRevenue) * 100
                                : 0;
                            ?>
                            <div class="category-row">

                                <div class="category-row-top">
                                    <span><?= e($item['category']) ?></span>
                                    <strong>
                                        ₱<?= number_format((float) $item['amount'], 2) ?>
                                    </strong>
                                </div>

                                <div class="category-bar">
                                    <div
                                        class="category-bar-fill"
                                        style="width: <?= e(number_format($percentage, 2, '.', '')) ?>%;"></div>
                                </div>

                            </div>
                        <?php endforeach; ?>

                    </div>

                </article>

            </section>

            <!-- STATUS BREAKDOWN -->
            <section class="dashboard-panel status-panel">

                <div class="dashboard-panel-header">
                    <h2>Order Status Breakdown</h2>
                </div>

                <div class="status-breakdown-grid">

                    <?php foreach ($statusBreakdown as $status): ?>
                        <?php
                        $statusClass = strtolower($status['label']);
                        ?>
                        <article class="status-card <?= e($statusClass) ?>">
                            <strong><?= (int) $status['count'] ?></strong>
                            <span><?= e($status['label']) ?></span>
                            <small><?= (int) $status['percent'] ?>%</small>
                        </article>
                    <?php endforeach; ?>

                </div>

            </section>


            <!-- =========================================
     USER BREAKDOWN
========================================= -->

            <section class="dashboard-panel user-breakdown-panel">

                <div class="user-breakdown-header">
                    <h2>User Breakdown</h2>
                </div>

                <div class="user-breakdown-grid">

                    <!-- ADMINS -->

                    <div class="user-breakdown-item">

                        <i
                            class="fa-solid fa-users"
                            aria-hidden="true"></i>

                        <strong>
                            <?= number_format($userBreakdown['admins']) ?>
                        </strong>

                        <span>Admins</span>

                    </div>


                    <!-- STAFFS -->

                    <div class="user-breakdown-item">

                        <i
                            class="fa-solid fa-users"
                            aria-hidden="true"></i>

                        <strong>
                            <?= number_format($userBreakdown['staffs']) ?>
                        </strong>

                        <span>Staffs</span>

                    </div>


                    <!-- CUSTOMERS -->

                    <div class="user-breakdown-item">

                        <i
                            class="fa-solid fa-users"
                            aria-hidden="true"></i>

                        <strong>
                            <?= number_format($userBreakdown['customers']) ?>
                        </strong>

                        <span>Customers</span>

                    </div>

                </div>

            </section>


        </section>

    </main>

</body>

</html>