<?php
require_once __DIR__ . '/../config/app.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/public/login.php');
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
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$role = strtolower(trim($adminUser['role_name']));
if ($role !== 'admin') {
    header('Location: ' . BASE_URL . ($role === 'staff' ? '/staff/dashboard.php' : '/public/index.php'));
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
        <?= htmlspecialchars($pageTitle) ?> | PureVia
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
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
                    <?= htmlspecialchars($currentDate) ?>
                    ·
                    <?= htmlspecialchars($greeting) ?>,
                    <?= htmlspecialchars($adminFirstName) ?>!
                </p>

            </section>



            <!-- =================================
                 STATISTICS CARDS
            ================================== -->

            <?php if ($dashboardError !== null): ?>
                <p class="alert alert-danger" role="alert"><?= htmlspecialchars($dashboardError) ?></p>
            <?php else: ?>

            <section class="dashboard-stats">


                <!-- CUSTOMERS -->

                <article class="stat-card stat-customers">

                    <div class="stat-card-header">

                        <span class="stat-emoji">
                            👤
                        </span>

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

                        <span class="stat-emoji">
                            🧴
                        </span>

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

                        <span class="stat-emoji">
                            📦
                        </span>

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

                        <span class="stat-emoji">
                            💰
                        </span>

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

                        <span class="stat-emoji">
                            ⚠️
                        </span>

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

                        <span class="stat-emoji">
                            🔔
                        </span>

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

                                        <?= htmlspecialchars(
                                            $order['order_number']
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $order['customer']
                                        ) ?>

                                    </td>


                                    <td class="order-date">

                                        <?= htmlspecialchars(
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
                                                status-<?= htmlspecialchars(
                                                    $orderStatusClasses[$order['status']] ?? 'pending'
                                                ) ?>
                                            "
                                        >

                                            <?= strtoupper(
                                                htmlspecialchars(
                                                    $order['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                payment-badge
                                                payment-<?= htmlspecialchars(
                                                    $order['payment']
                                                ) ?>
                                            "
                                        >

                                            <?= strtoupper(
                                                htmlspecialchars(
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

                    <i class="fa-solid fa-triangle-exclamation"></i>

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

                                <?= htmlspecialchars(
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

    <script src="<?= BASE_URL ?>/assets/js/sidemenu.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
