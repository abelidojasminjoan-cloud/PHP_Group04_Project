<?php

session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];


/* =========================================================
   VERIFY STAFF ACCOUNT + GET STAFF NAME
========================================================= */

$stmt = $conn->prepare("
    SELECT
        u.id,
        u.first_name,
        u.last_name,
        u.email,
        u.status,
        r.role_name
    FROM users AS u
    INNER JOIN roles AS r
        ON r.id = u.role_id
    WHERE u.id = ?
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$result = $stmt->get_result();
$staffUser = $result->fetch_assoc();

$stmt->close();


if (
    !$staffUser ||
    strtolower($staffUser['status']) !== 'active' ||
    strtolower($staffUser['role_name']) !== 'staff'
) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

$staffFirstName = $staffUser['first_name'];


/* =========================================================
   DASHBOARD COUNTS
========================================================= */


/* ---------------------------------------------------------
   OPEN ORDERS
--------------------------------------------------------- */

$openOrders = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_status IN (
        'pending',
        'processing',
        'shipped'
    )
");

if ($result) {
    $row = $result->fetch_assoc();
    $openOrders = (int) $row['total'];
}


/* ---------------------------------------------------------
   PENDING PAYMENTS
--------------------------------------------------------- */

$pendingPayments = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM payments
    WHERE payment_status = 'pending'
");

if ($result) {
    $row = $result->fetch_assoc();
    $pendingPayments = (int) $row['total'];
}


/* ---------------------------------------------------------
   TOTAL PRODUCTS
--------------------------------------------------------- */

$totalProducts = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
");

if ($result) {
    $row = $result->fetch_assoc();
    $totalProducts = (int) $row['total'];
}


/* ---------------------------------------------------------
   TOTAL ORDERS
--------------------------------------------------------- */

$totalOrders = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM orders
");

if ($result) {
    $row = $result->fetch_assoc();
    $totalOrders = (int) $row['total'];
}


/* ---------------------------------------------------------
   LOW STOCK COUNT
   1 - 10 = LOW STOCK
--------------------------------------------------------- */

$lowStockCount = 0;

$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM products
    WHERE stock_quantity <= 10
      AND stock_quantity > 0
");

if ($result) {
    $row = $result->fetch_assoc();
    $lowStockCount = (int) $row['total'];
}


/* =========================================================
   RECENT ORDERS
   SHOW MAXIMUM OF 5
========================================================= */

$recentOrders = [];

$sql = "
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.order_status,
        o.created_at,
        u.first_name,
        u.last_name
    FROM orders AS o
    INNER JOIN users AS u
        ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 5
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}


/* =========================================================
   LOW STOCK PRODUCTS
   SHOW MAXIMUM OF 5
========================================================= */

$lowStockProducts = [];

$sql = "
    SELECT
        id,
        product_name,
        stock_quantity
    FROM products
    WHERE stock_quantity <= 10
      AND stock_quantity > 0
    ORDER BY stock_quantity ASC,
             product_name ASC
    LIMIT 5
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lowStockProducts[] = $row;
    }
}


$pageTitle = 'Staff Dashboard';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Staff Dashboard | PureVia</title>


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


    <!-- SIDEMENU -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/sidemenu.css"
    >


    <!-- STAFF DASHBOARD -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/staff_dashboard.css"
    >

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>


<body>


    <!-- =====================================================
         SHARED SIDEBAR
    ====================================================== -->

    <?php include __DIR__ . '/../includes/sidemenu.php'; ?>


    <!-- =====================================================
         STAFF LAYOUT
    ====================================================== -->

    <div class="staff-layout">


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="staff-topbar">

            <p class="staff-topbar-title">
                Dashboard
            </p>

        </header>


        <!-- =================================================
             MAIN
        ================================================== -->

        <main class="staff-main">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <section class="staff-dashboard-header">

                <h1>
                    Staff Dashboard
                </h1>

                <p>
                    Welcome back,
                    <?= pvHtml($staffFirstName) ?>!
                </p>

            </section>


            <!-- =================================================
                 STAT CARDS
            ================================================== -->

            <section class="staff-stats">


                <!-- OPEN ORDERS -->

                <a
                    href="<?= BASE_URL ?>/staff/orders/orders.php"
                    class="staff-stat-card staff-stat-orders"
                >

                    <div class="staff-stat-top">

                        <span class="staff-stat-icon">
                            🛒
                        </span>

                        <span class="staff-stat-arrow">
                            →
                        </span>

                    </div>


                    <div class="staff-stat-content">

                        <p class="staff-stat-number">
                            <?= number_format($openOrders) ?>
                        </p>

                        <p class="staff-stat-label">
                            Open Orders
                        </p>

                    </div>

                </a>


                <!-- PENDING PAYMENTS -->

                <a
                    href="<?= BASE_URL ?>/staff/payments/payments.php"
                    class="staff-stat-card staff-stat-payments"
                >

                    <div class="staff-stat-top">

                        <span class="staff-stat-icon">
                            💳
                        </span>

                        <span class="staff-stat-arrow">
                            →
                        </span>

                    </div>


                    <div class="staff-stat-content">

                        <p class="staff-stat-number">
                            <?= number_format($pendingPayments) ?>
                        </p>

                        <p class="staff-stat-label">
                            Pending Payments
                        </p>

                    </div>

                </a>


                <!-- LOW STOCK -->

                <a
                    href="<?= BASE_URL ?>/staff/inventory/inventory.php"
                    class="staff-stat-card staff-stat-low"
                >

                    <div class="staff-stat-top">

                        <span class="staff-stat-icon">
                            ⚠️
                        </span>

                        <span class="staff-stat-arrow">
                            →
                        </span>

                    </div>


                    <div class="staff-stat-content">

                        <p class="staff-stat-number">
                            <?= number_format($lowStockCount) ?>
                        </p>

                        <p class="staff-stat-label">
                            Low Stock
                        </p>

                    </div>

                </a>


                <!-- PRODUCTS -->

                <a
                    href="<?= BASE_URL ?>/staff/products/products.php"
                    class="staff-stat-card staff-stat-products"
                >

                    <div class="staff-stat-top">

                        <span class="staff-stat-icon">
                            🧴
                        </span>

                        <span class="staff-stat-arrow">
                            →
                        </span>

                    </div>


                    <div class="staff-stat-content">

                        <p class="staff-stat-number">
                            <?= number_format($totalProducts) ?>
                        </p>

                        <p class="staff-stat-label">
                            Products
                        </p>

                    </div>

                </a>


            </section>


            <!-- =================================================
                 LOWER DASHBOARD GRID
            ================================================== -->

            <section class="staff-dashboard-grid">


                <!-- =================================================
                     RECENT ORDERS
                ================================================== -->

                <article class="staff-panel recent-orders-panel">


                    <div class="staff-panel-heading">

                        <h2>
                            Recent Orders
                        </h2>

                    </div>


                    <div class="staff-recent-orders">


                        <?php if (!empty($recentOrders)): ?>


                            <?php foreach ($recentOrders as $order): ?>


                                <?php

                                $customerName = trim(
                                    $order['first_name'] . ' ' .
                                    $order['last_name']
                                );

                                $status = strtolower(
                                    $order['order_status']
                                );

                                ?>


                                <div class="staff-order-row">


                                    <div class="staff-order-left">

                                        <p class="staff-order-number">
                                            <?= pvHtml(
                                                $order['order_number']
                                            ) ?>
                                        </p>

                                        <p class="staff-order-customer">
                                            <?= pvHtml(
                                                $customerName
                                            ) ?>
                                        </p>

                                    </div>


                                    <div class="staff-order-right">

                                        <p class="staff-order-total">

                                            ₱<?= number_format(
                                                (float) $order['total_amount'],
                                                2
                                            ) ?>

                                        </p>


                                        <span
                                            class="
                                                staff-status
                                                staff-status-<?= pvHtml($status) ?>
                                            "
                                        >
                                            <?= strtoupper(
                                                pvHtml($status)
                                            ) ?>
                                        </span>

                                    </div>


                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <div class="staff-empty-state">

                                <i class="fa-solid fa-box-open"></i>

                                <p>
                                    No recent orders.
                                </p>

                            </div>


                        <?php endif; ?>


                    </div>


                    <!-- =============================================
                         VIEW ALL ORDERS

                         NORMAL:
                         white / transparent

                         HOVER:
                         beige rounded rectangle
                    ============================================== -->

                    <?php if ($totalOrders > 0): ?>

                        <div class="staff-panel-footer">

                            <a
                                href="<?= BASE_URL ?>/staff/orders/orders.php"
                                class="staff-view-all"
                            >
                                View All Orders
                            </a>

                        </div>

                    <?php endif; ?>


                </article>


                <!-- =================================================
                     LOW STOCK ALERTS
                ================================================== -->

                <article class="staff-panel low-stock-panel">


                    <div class="low-stock-panel-heading">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                        <h2>
                            Low Stock Alerts
                        </h2>

                    </div>


                    <div class="staff-low-stock-list">


                        <?php if (!empty($lowStockProducts)): ?>


                            <?php foreach ($lowStockProducts as $product): ?>


                                <div class="staff-low-stock-row">


                                    <p class="staff-low-stock-name">

                                        <?= pvHtml(
                                            $product['product_name']
                                        ) ?>

                                    </p>


                                    <div class="staff-low-stock-actions">


                                        <span class="staff-low-stock-number">

                                            <?= number_format(
                                                (int) $product['stock_quantity']
                                            ) ?>

                                        </span>


                                        <a
                                            href="<?= BASE_URL ?>/staff/inventory/inventory.php?product=<?= (int) $product['id'] ?>"
                                            class="staff-restock-button"
                                        >
                                            Restock
                                        </a>


                                    </div>


                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <div class="staff-empty-state low-stock-empty">

                                <i class="fa-solid fa-circle-check"></i>

                                <p>
                                    No low stock products.
                                </p>

                            </div>


                        <?php endif; ?>


                    </div>


                    <!-- =============================================
                         VIEW ALL STOCK

                         Appears when there is low stock.
                    ============================================== -->

                    <?php if ($lowStockCount > 0): ?>

                        <div class="staff-panel-footer">

                            <a
                                href="<?= BASE_URL ?>/staff/inventory/inventory.php?filter=low-stock"
                                class="staff-view-all"
                            >
                                View All Stock
                            </a>

                        </div>

                    <?php endif; ?>


                </article>


            </section>


        </main>


    </div>


    <!-- =====================================================
         SIDEMENU JAVASCRIPT
    ====================================================== -->

    <script nonce="<?= pvScriptNonce() ?>"
        src="<?= BASE_URL ?>/assets/js/sidemenu.js"
    ></script>


<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>