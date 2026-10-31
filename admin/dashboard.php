<?php
require_once __DIR__ . '/../config/app.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$pageTitle = 'Admin Dashboard';
$userId = (int) $_SESSION['user_id'];

// Verify the current database role before loading any dashboard data.
try {
    $stmt = $conn->prepare("
        SELECT u.first_name, u.status, r.role_name
        FROM users AS u
        INNER JOIN roles AS r ON r.id = u.role_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $adminUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (mysqli_sql_exception $error) {
    error_log('Admin dashboard authorization failed: ' . $error->getMessage());
    http_response_code(503);
    exit('The dashboard is temporarily unavailable. Please try again.');
}

if (!$adminUser || strtolower($adminUser['status']) !== 'active') {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$role = strtolower(trim($adminUser['role_name']));
if ($role !== 'admin') {
    header('Location: ' . BASE_URL . ($role === 'staff' ? '/staff/dashboard.php' : '/index.php'));
    exit;
}

$adminFirstName = trim($adminUser['first_name']);
$currentHour = (int) date('H');
$greeting = $currentHour < 12 ? 'Good morning'
    : ($currentHour < 18 ? 'Good afternoon' : 'Good evening');
$currentDate = date('l, F j, Y');
$monthStart = new DateTimeImmutable('first day of this month 00:00:00');
$monthEnd = $monthStart->modify('+1 month');
$monthStartSql = $monthStart->format('Y-m-d H:i:s');
$monthEndSql = $monthEnd->format('Y-m-d H:i:s');

$orderStatusCounts = [
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];
// Preserve the existing five overview groups while covering every database status.
$orderStatusClasses = [
    'pending' => 'pending',
    'confirmed' => 'processing',
    'preparing' => 'processing',
    'shipping' => 'shipped',
    'delivered' => 'delivered',
    'completed' => 'delivered',
    'cancelled' => 'cancelled'
];
$dashboardError = null;

try {
    $totals = $conn->query("
        SELECT
            (SELECT COUNT(*)
             FROM users AS u
             INNER JOIN roles AS r ON r.id = u.role_id
             WHERE LOWER(r.role_name) = 'customer') AS customers,
            (SELECT COUNT(*) FROM products) AS products
    ")->fetch_assoc();
    $totalCustomers = (int) $totals['customers'];
    $totalProducts = (int) $totals['products'];

    // Revenue covers paid, non-cancelled orders placed in the displayed month.
    // A half-open date range includes the whole month and keeps the date index usable.
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS orders,
            COALESCE(SUM(CASE
                WHEN payment_status = 'paid' AND order_status <> 'cancelled'
                THEN total_amount ELSE 0 END), 0) AS revenue
        FROM orders
        WHERE created_at >= ? AND created_at < ?
    ");
    $stmt->bind_param('ss', $monthStartSql, $monthEndSql);
    $stmt->execute();
    $monthly = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $ordersThisMonth = (int) $monthly['orders'];
    $monthlyRevenue = (float) $monthly['revenue'];

    // Count orders awaiting payment once, regardless of payment-attempt history.
    $pendingPayments = (int) $conn->query("
        SELECT COUNT(*) AS total
        FROM orders
        WHERE payment_status = 'pending' AND order_status <> 'cancelled'
    ")->fetch_assoc()['total'];

    // Inventory is held per variant. Include zero stock and each variant's own threshold.
    $lowStockWhere = "p.status <> 'inactive'
        AND pv.stock_quantity <= pv.low_stock_threshold";
    $lowStockCount = (int) $conn->query("
        SELECT COUNT(*) AS total
        FROM product_variants AS pv
        INNER JOIN products AS p ON p.id = pv.product_id
        WHERE $lowStockWhere
    ")->fetch_assoc()['total'];

    $lowStockProducts = $conn->query("
        SELECT CONCAT_WS(' · ', p.product_name,
                NULLIF(pv.size_capacity, ''), NULLIF(pv.variant_label, '')) AS name,
            pv.stock_quantity AS stock
        FROM product_variants AS pv
        INNER JOIN products AS p ON p.id = pv.product_id
        WHERE $lowStockWhere
        ORDER BY pv.stock_quantity ASC, p.product_name ASC, pv.id ASC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    // Use the customer name saved on the order, so later profile edits do not alter it.
    $recentOrders = $conn->query("
        SELECT order_number,
            TRIM(CONCAT_WS(' ', first_name, last_name)) AS customer,
            DATE(created_at) AS date, total_amount AS total,
            order_status AS status, payment_status AS payment
        FROM orders
        ORDER BY created_at DESC, id DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);

    $statuses = $conn->query("
        SELECT order_status, COUNT(*) AS total
        FROM orders
        GROUP BY order_status
    ");
    foreach ($statuses as $status) {
        $group = $orderStatusClasses[$status['order_status']] ?? null;
        if ($group !== null) {
            $orderStatusCounts[$group] += (int) $status['total'];
        }
    }
} catch (mysqli_sql_exception $error) {
    error_log('Admin dashboard data failed: ' . $error->getMessage());
    http_response_code(503);
    $dashboardError = 'Dashboard data is temporarily unavailable. Please refresh and try again.';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= pvHtml($pageTitle) ?> | PureVia
    </title>


    <!-- GOOGLE FONTS -->

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- SIDEBAR -->

    <link
        rel="stylesheet"
        href="../assets/css/sidemenu.css"
    >


    <!-- ADMIN DASHBOARD -->

    <link
        rel="stylesheet"
        href="../assets/css/admin_dashboard.css"
    >

    
<!-- Responsive adjustments -->
<link
    rel="stylesheet"
    href="<?= BASE_URL ?>/assets/css/responsive.css"
>

<!-- PUREVIA COOKIE CONSENT CSS -->
<link
    rel="stylesheet"
    href="<?= BASE_URL ?>/assets/css/cookie-consent.css?v=2"
>

</head>


<body>


    <!-- =====================================
         SIDEBAR
    ====================================== -->

    <?php include __DIR__ . '/../includes/sidemenu.php'; ?>


    <!-- =====================================
         ADMIN LAYOUT
    ====================================== -->

    <div class="admin-layout">


        <!-- =================================
             TOP BAR
        ================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Dashboard
            </p>

        </header>


        <!-- =================================
             DASHBOARD CONTENT
        ================================== -->

        <main class="admin-main">


            <!-- =================================
                 DASHBOARD HEADER
            ================================== -->

            <section class="dashboard-header">

                <h1>Dashboard</h1>

                <p>
                    <?= pvHtml($currentDate) ?>
                    ·
                    <?= pvHtml($greeting) ?>,
                    <?= pvHtml($adminFirstName) ?>!
                </p>

            </section>



            <!-- =================================
                 STATISTICS CARDS
            ================================== -->

            <?php if ($dashboardError !== null): ?>
                <p class="alert alert-danger" role="alert"><?= pvHtml($dashboardError) ?></p>
            <?php else: ?>

            <section class="dashboard-stats">


                <!-- CUSTOMERS -->

                <article class="stat-card stat-customers">

                    <div class="stat-card-header">

                        <div class="stat-emoji" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="28" height="28" fill="currentColor" focusable="false">
                                <path d="M320 312C386.3 312 440 258.3 440 192C440 125.7 386.3 72 320 72C253.7 72 200 125.7 200 192C200 258.3 253.7 312 320 312zM290.3 368C191.8 368 112 447.8 112 546.3C112 562.7 125.3 576 141.7 576L498.3 576C514.7 576 528 562.7 528 546.3C528 447.8 448.2 368 349.7 368L290.3 368z"/>
                            </svg>
                        </div>

                        <span class="stat-dot"></span>

                    </div>

                    <div class="stat-card-content">

                        <p class="stat-number">
                            <?= number_format($totalCustomers) ?>
                        </p>

                        <p class="stat-label">
                            Total Customers
                        </p>

                    </div>

                </article>



                <!-- PRODUCTS -->

                <article class="stat-card stat-products">

                    <div class="stat-card-header">

                        <div class="stat-emoji" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="28" height="28" fill="currentColor" focusable="false">
                                <path d="M256 64L256 144L384 144L384 120L456 120C469.3 120 480 109.3 480 96C480 82.7 469.3 72 456 72L384 72L384 64C384 46.3 369.7 32 352 32L288 32C270.3 32 256 46.3 256 64zM224 192C188.7 192 160 220.7 160 256L160 512C160 547.3 188.7 576 224 576L416 576C451.3 576 480 547.3 480 512L480 256C480 220.7 451.3 192 416 192L224 192zM384 408C384 443.3 355.3 464 320 464C284.7 464 256 443.3 256 408C256 375.5 293 327.1 306.9 310.1C310.1 306.2 315 304 320 304C325 304 329.9 306.2 333.1 310.1C347 327.1 384 375.5 384 408z"/>
                            </svg>
                        </div>

                        <span class="stat-dot"></span>

                    </div>

                    <div class="stat-card-content">

                        <p class="stat-number">
                            <?= number_format($totalProducts) ?>
                        </p>

                        <p class="stat-label">
                            Total Products
                        </p>

                    </div>

                </article>



                <!-- ORDERS -->

                <article class="stat-card stat-orders">

                    <div class="stat-card-header">

                        <div class="stat-emoji" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" width="28" height="28" fill="currentColor" focusable="false">
                                <g>
                                    <polygon points="2.75,3.815918 8.190918,1.095459 6,0 0.559082,2.720459"/>
                                    <polygon points="9.309082,1.654541 3.8681641,4.375 6,5.440918 11.440918,2.720459"/>
                                    <polygon points="5.5,6.309082 0,3.559082 0,9.25 5.5,12"/>
                                    <polygon points="6.5,6.309082 6.5,12 12,9.25 12,3.559082"/>
                                </g>
                            </svg>
                        </div>

                        <span class="stat-dot"></span>

                    </div>

                    <div class="stat-card-content">

                        <p class="stat-number">
                            <?= number_format($ordersThisMonth) ?>
                        </p>

                        <p class="stat-label">
                            Orders This Month
                        </p>

                    </div>

                </article>



                <!-- REVENUE -->

                <article class="stat-card stat-revenue">

                    <div class="stat-card-header">

                        <div class="stat-emoji" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="28" height="28" fill="currentColor" focusable="false">
                                <path d="M192 160L192 144C192 99.8 278 64 384 64C490 64 576 99.8 576 144L576 160C576 190.6 534.7 217.2 474 230.7C471.6 227.9 469.1 225.2 466.6 222.7C451.1 207.4 431.1 195.8 410.2 187.2C368.3 169.7 313.7 160.1 256 160.1C234.1 160.1 212.7 161.5 192.2 164.2C192 162.9 192 161.5 192 160.1zM496 417L496 370.8C511.1 366.9 525.3 362.3 538.2 356.9C551.4 351.4 564.3 344.7 576 336.6L576 352C576 378.8 544.5 402.5 496 417zM496 321L496 288C496 283.5 495.6 279.2 495 275C510.5 271.1 525 266.4 538.2 260.8C551.4 255.2 564.3 248.6 576 240.5L576 255.9C576 282.7 544.5 306.4 496 320.9zM64 304L64 288C64 243.8 150 208 256 208C362 208 448 243.8 448 288L448 304C448 348.2 362 384 256 384C150 384 64 348.2 64 304zM448 400C448 444.2 362 480 256 480C150 480 64 444.2 64 400L64 384.6C75.6 392.7 88.5 399.3 101.8 404.9C143.7 422.4 198.3 432 256 432C313.7 432 368.3 422.3 410.2 404.9C423.4 399.4 436.3 392.7 448 384.6L448 400zM448 480.6L448 496C448 540.2 362 576 256 576C150 576 64 540.2 64 496L64 480.6C75.6 488.7 88.5 495.3 101.8 500.9C143.7 518.4 198.3 528 256 528C313.7 528 368.3 518.3 410.2 500.9C423.4 495.4 436.3 488.7 448 480.6z"/>
                            </svg>
                        </div>

                        <span class="stat-dot"></span>

                    </div>

                    <div class="stat-card-content">

                        <p class="stat-number">
                            ₱<?= number_format($monthlyRevenue, 2) ?>
                        </p>

                        <p class="stat-label" title="Paid, non-cancelled orders placed this month">
                            Revenue (<?= date('M') ?>)
                        </p>

                    </div>

                </article>



                <!-- LOW STOCK -->

                <article class="stat-card stat-low-stock">

                    <div class="stat-card-header">

                        <div class="stat-emoji" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="28" height="28" fill="currentColor" focusable="false">
                                <path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/>
                            </svg>
                        </div>

                        <span class="stat-dot"></span>

                    </div>

                    <div class="stat-card-content">

                        <p class="stat-number">
                            <?= number_format($lowStockCount) ?>
                        </p>

                        <p class="stat-label">
                            Low Stock Items
                        </p>

                    </div>

                </article>



                <!-- PENDING PAYMENTS -->

                <article class="stat-card stat-payments">

                    <div class="stat-card-header">

                        <div class="stat-emoji" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="28" height="28" fill="currentColor" focusable="false">
                                <path d="M193.499,459.298c5.237,30.54,31.518,52.702,62.49,52.702c30.98,0,57.269-22.162,62.506-52.702l0.32-1.86H193.179L193.499,459.298z"/>
                                <path d="M469.782,371.98c-5.126-5.128-10.349-9.464-15.402-13.661c-21.252-17.648-39.608-32.888-39.608-96.168v-50.194c0-73.808-51.858-138.572-123.61-154.81c2.876-5.64,4.334-11.568,4.334-17.655C295.496,17.718,277.777,0,255.995,0c-21.776,0-39.492,17.718-39.492,39.492c0,6.091,1.456,12.018,4.334,17.655c-71.755,16.238-123.61,81.002-123.61,154.81v50.194c0,63.28-18.356,78.521-39.608,96.168c-5.052,4.196-10.276,8.533-15.402,13.661l-0.466,0.466v49.798h428.496v-49.798L469.782,371.98z"/>
                            </svg>
                        </div>

                        <span class="stat-dot"></span>

                    </div>

                    <div class="stat-card-content">

                        <p class="stat-number">
                            <?= number_format($pendingPayments) ?>
                        </p>

                        <p class="stat-label" title="Non-cancelled orders awaiting payment">
                            Pending Payments
                        </p>

                    </div>

                </article>


            </section>



            <!-- =================================
                 RECENT ORDERS
            ================================== -->

            <section class="dashboard-section recent-orders-section">


                <!-- HEADER -->

                <div class="section-header">

                    <h2>
                        Recent Orders
                    </h2>

                    <a
                        href="<?= BASE_URL ?>/admin/orders/orders.php"
                        class="view-all-link"
                    >
                        View All
                    </a>

                </div>


                <!-- TABLE -->

                <div class="table-wrapper">

                    <table class="recent-orders-table">

                        <thead>

                            <tr>

                                <th>ORDER ID</th>
                                <th>CUSTOMER</th>
                                <th>DATE</th>
                                <th>TOTAL</th>
                                <th>STATUS</th>
                                <th>PAYMENT</th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php if (!$recentOrders): ?>
                                <tr><td colspan="6" class="text-center">No orders yet.</td></tr>
                            <?php endif; ?>

                            <?php foreach ($recentOrders as $order): ?>

                                <tr>

                                    <td class="order-id">

                                        <?= pvHtml(
                                            $order['order_number']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= pvHtml(
                                            $order['customer']
                                        ) ?>

                                    </td>


                                    <td class="order-date">

                                        <?= pvHtml(
                                            $order['date']
                                        ) ?>

                                    </td>


                                    <td class="order-total">

                                        ₱<?= number_format(
                                            $order['total'],
                                            2
                                        ) ?>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                status-badge
                                                status-<?= pvHtml(
                                                    $orderStatusClasses[$order['status']] ?? 'pending'
                                                ) ?>
                                            "
                                        >

                                            <?= strtoupper(
                                                pvHtml(
                                                    $order['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                payment-badge
                                                payment-<?= pvHtml(
                                                    $order['payment']
                                                ) ?>
                                            "
                                        >

                                            <?= strtoupper(
                                                pvHtml(
                                                    $order['payment']
                                                )
                                            ) ?>

                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            </section>



            <!-- =================================
                 LOW STOCK ALERT
            ================================== -->

            <section class="dashboard-section low-stock-section">

                <div class="low-stock-heading">

                    <div class="low-stock-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="22" height="22" fill="currentColor" focusable="false">
                            <path d="M320 64C334.7 64 348.2 72.1 355.2 85L571.2 485C577.9 497.4 577.6 512.4 570.4 524.5C563.2 536.6 550.1 544 536 544L104 544C89.9 544 76.8 536.6 69.6 524.5C62.4 512.4 62.1 497.4 68.8 485L284.8 85C291.8 72.1 305.3 64 320 64zM320 416C302.3 416 288 430.3 288 448C288 465.7 302.3 480 320 480C337.7 480 352 465.7 352 448C352 430.3 337.7 416 320 416zM320 224C301.8 224 287.3 239.5 288.6 257.7L296 361.7C296.9 374.2 307.4 384 319.9 384C332.5 384 342.9 374.3 343.8 361.7L351.2 257.7C352.5 239.5 338.1 224 319.8 224z"/>
                        </svg>
                    </div>

                    <h2>
                        Low Stock Alert
                    </h2>

                </div>


                <div class="low-stock-list">

                    <?php if (!$lowStockProducts): ?>
                        <p class="low-stock-product-name">No low-stock items.</p>
                    <?php endif; ?>

                    <?php foreach ($lowStockProducts as $product): ?>

                        <div class="low-stock-row">

                            <p class="low-stock-product-name">

                                <?= pvHtml(
                                    $product['name']
                                ) ?>

                            </p>


                            <div class="low-stock-actions">

                                <span class="stock-units">

                                    <?= number_format(
                                        $product['stock']
                                    ) ?>
                                    units

                                </span>


                                <a
                                    href="<?= BASE_URL ?>/admin/inventory/inventory.php"
                                    class="restock-link"
                                >
                                    Restock
                                </a>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>



            <!-- =================================
                 ORDER STATUS OVERVIEW
            ================================== -->

            <section class="order-overview-section">

                <h2>
                    Order Status Overview
                </h2>


                <div class="order-status-grid">


                    <!-- PENDING -->

                    <div class="order-overview-card pending">

                        <strong>
                            <?= $orderStatusCounts['pending'] ?>
                        </strong>

                        <span>
                            Pending
                        </span>

                    </div>



                    <!-- PROCESSING -->

                    <div class="order-overview-card processing" title="Confirmed and preparing orders">

                        <strong>
                            <?= $orderStatusCounts['processing'] ?>
                        </strong>

                        <span>
                            Processing
                        </span>

                    </div>



                    <!-- SHIPPED -->

                    <div class="order-overview-card shipped" title="Orders with shipping status">

                        <strong>
                            <?= $orderStatusCounts['shipped'] ?>
                        </strong>

                        <span>
                            Shipped
                        </span>

                    </div>



                    <!-- DELIVERED -->

                    <div class="order-overview-card delivered" title="Delivered and completed orders">

                        <strong>
                            <?= $orderStatusCounts['delivered'] ?>
                        </strong>

                        <span>
                            Delivered
                        </span>

                    </div>



                    <!-- CANCELLED -->

                    <div class="order-overview-card cancelled">

                        <strong>
                            <?= $orderStatusCounts['cancelled'] ?>
                        </strong>

                        <span>
                            Cancelled
                        </span>

                    </div>


                </div>

            </section>


            <?php endif; ?>

        </main>

    </div>


    <!-- =====================================
         JAVASCRIPT
    ====================================== -->

    <script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/sidemenu.js"></script>


<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>



<!-- ==========================================
     PUREVIA ADMIN COOKIE CONSENT
========================================== -->

<div
    id="pvCookieBanner"
    class="pv-cookie-banner"
    role="region"
    aria-label="PureVia Cookie Consent"
    hidden
>
    <div class="pv-cookie-banner-inner">

        <div class="pv-cookie-icon">
            <i class="fa-solid fa-cookie-bite"></i>
        </div>

        <div class="pv-cookie-content">

            <div class="pv-cookie-eyebrow">
                PUREVIA PRIVACY
            </div>

            <h2>Your Privacy Matters</h2>

            <p>
                PureVia uses essential cookies for secure
                login and session management. You can
                choose whether to allow optional preferences
                and analytics.
            </p>

        </div>

        <div class="pv-cookie-actions">

            <button
                type="button"
                id="pvCookieAccept"
                class="pv-cookie-btn pv-cookie-accept"
            >
                Accept All
            </button>

            <button
                type="button"
                id="pvCookieReject"
                class="pv-cookie-btn pv-cookie-reject"
            >
                Reject Optional
            </button>

            <button
                type="button"
                id="pvCookieCustomize"
                class="pv-cookie-manage"
            >
                Manage Preferences
            </button>

        </div>

    </div>
</div>


<!-- ==========================================
     COOKIE PREFERENCES MODAL
========================================== -->

<div
    id="pvCookieOverlay"
    class="pv-cookie-overlay"
    hidden
>
    <div
        class="pv-cookie-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="pvCookieModalTitle"
    >

        <div class="pv-cookie-modal-header">

            <h2 id="pvCookieModalTitle">
                Cookie Preferences
            </h2>

            <button
                type="button"
                id="pvCookieClose"
                class="pv-cookie-close"
                aria-label="Close cookie preferences"
            >
                &times;
            </button>

        </div>

        <p class="pv-cookie-modal-description">
            Choose which optional cookies PureVia
            may use. Essential cookies remain active
            for authentication and account security.
        </p>

        <!-- ESSENTIAL COOKIES -->

        <div class="pv-cookie-option">

            <div class="pv-cookie-option-copy">
                <h3>Essential Cookies</h3>
                <p>
                    Required for secure login,
                    authentication, and sessions.
                </p>
            </div>

            <span class="pv-cookie-required">
                Always Active
            </span>

        </div>

        <!-- PREFERENCES -->

        <div class="pv-cookie-option">

            <div class="pv-cookie-option-copy">
                <h3>Preference Storage</h3>
                <p>
                    Remember optional dashboard
                    preferences.
                </p>
            </div>

            <label class="pv-cookie-switch">

                <input
                    type="checkbox"
                    id="pvCookiePreferences"
                    aria-label="Allow preference storage"
                >

                <span class="pv-cookie-slider"></span>

            </label>

        </div>

        <!-- ANALYTICS -->

        <div class="pv-cookie-option">

            <div class="pv-cookie-option-copy">
                <h3>Analytics Cookies</h3>
                <p>
                    Allow optional usage analytics,
                    if enabled on this website.
                </p>
            </div>

            <label class="pv-cookie-switch">

                <input
                    type="checkbox"
                    id="pvCookieAnalytics"
                    aria-label="Allow analytics cookies"
                >

                <span class="pv-cookie-slider"></span>

            </label>

        </div>

        <p class="pv-cookie-modal-note">
            Essential cookies cannot be disabled
            because they protect your admin session.
        </p>

        <div class="pv-cookie-modal-actions">

            <button
                type="button"
                id="pvCookieSave"
                class="pv-cookie-btn pv-cookie-accept"
            >
                Save Preferences
            </button>

        </div>

    </div>
</div>


<!-- ==========================================
     PUREVIA COOKIE CONSENT JAVASCRIPT
========================================== -->

<script nonce="<?= pvScriptNonce() ?>">
document.addEventListener('DOMContentLoaded', function () {

    const KEY = 'purevia_admin_cookie_consent_v1';

    const banner = document.getElementById('pvCookieBanner');
    const overlay = document.getElementById('pvCookieOverlay');

    const accept = document.getElementById('pvCookieAccept');
    const reject = document.getElementById('pvCookieReject');
    const customize = document.getElementById('pvCookieCustomize');
    const close = document.getElementById('pvCookieClose');
    const save = document.getElementById('pvCookieSave');

    const preferences =
        document.getElementById('pvCookiePreferences');

    const analytics =
        document.getElementById('pvCookieAnalytics');

    if (!banner || !overlay) return;

    function readConsent() {
        try {
            const value = JSON.parse(
                localStorage.getItem(KEY) || 'null'
            );

            if (
                value &&
                value.version === 1 &&
                typeof value.preferences === 'boolean' &&
                typeof value.analytics === 'boolean'
            ) {
                return value;
            }
        } catch (error) {
            // No valid stored consent.
        }

        return null;
    }

    function storeConsent(allowPreferences, allowAnalytics) {

        const consent = {
            version: 1,
            essential: true,
            preferences: Boolean(allowPreferences),
            analytics: Boolean(allowAnalytics),
            savedAt: new Date().toISOString()
        };

        try {
            localStorage.setItem(
                KEY,
                JSON.stringify(consent)
            );
        } catch (error) {
            // Continue if browser storage is disabled.
        }

        window.pureviaAdminConsent = consent;

        banner.hidden = true;
        overlay.hidden = true;
    }

    function openPreferences() {
        const current = readConsent();

        preferences.checked = current?.preferences ?? false;
        analytics.checked = current?.analytics ?? false;

        overlay.hidden = false;
        close.focus();
    }

    function closePreferences() {
        overlay.hidden = true;

        if (!banner.hidden) {
            customize.focus();
        }
    }

    const existing = readConsent();

    if (existing) {
        preferences.checked = existing.preferences;
        analytics.checked = existing.analytics;
        window.pureviaAdminConsent = existing;

        banner.hidden = true;
    } else {
        window.pureviaAdminConsent = {
            version: 1,
            essential: true,
            preferences: false,
            analytics: false
        };

        banner.hidden = false;
    }

    accept.addEventListener('click', function () {
        storeConsent(true, true);
    });

    reject.addEventListener('click', function () {
        storeConsent(false, false);
    });

    customize.addEventListener('click', openPreferences);

    close.addEventListener('click', closePreferences);

    save.addEventListener('click', function () {
        storeConsent(
            preferences.checked,
            analytics.checked
        );
    });

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closePreferences();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !overlay.hidden) {
            closePreferences();
        }
    });

});
</script>

</body>
</html>
