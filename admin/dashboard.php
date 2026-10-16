<?php
session_start();
require_once __DIR__ . '/../config/app.php';

$pageTitle = "Admin Dashboard";

/*
Temporary admin session.
Remove when login is connected.
*/

$_SESSION['first_name'] =
    $_SESSION['first_name'] ?? 'Ava';

$_SESSION['last_name'] =
    $_SESSION['last_name'] ?? 'Santos';

$_SESSION['email'] =
    $_SESSION['email'] ?? 'admin@purevia.com';


/* =========================================
   TEMPORARY DASHBOARD DATA

   Later these values will come from MySQL.
========================================= */

$totalCustomers = 5;
$totalProducts = 12;
$ordersThisMonth = 5;
$monthlyRevenue = 12480;
$lowStockCount = 1;
$pendingPayments = 1;


/* =========================================
   DATE / GREETING
========================================= */

date_default_timezone_set('Asia/Manila');

$currentHour = (int) date('H');

if ($currentHour < 12) {
    $greeting = 'Good morning';
} elseif ($currentHour < 18) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

$currentDate = date('l, F j, Y');


/* =========================================
   SAMPLE RECENT ORDERS

   Replace with database query later.
========================================= */

$recentOrders = [

    [
        'order_number' => 'ORD-2026-0891',
        'customer' => 'Sofia Dela Rosa',
        'date' => '2026-09-24',
        'total' => 118.00,
        'status' => 'processing',
        'payment' => 'verified'
    ],

    [
        'order_number' => 'ORD-2026-0887',
        'customer' => 'James Uy',
        'date' => '2026-09-23',
        'total' => 168.00,
        'status' => 'shipped',
        'payment' => 'verified'
    ],

    [
        'order_number' => 'ORD-2026-0882',
        'customer' => 'Carlo Bautista',
        'date' => '2026-09-22',
        'total' => 128.00,
        'status' => 'delivered',
        'payment' => 'verified'
    ],

    [
        'order_number' => 'ORD-2026-0878',
        'customer' => 'Sofia Dela Rosa',
        'date' => '2026-09-18',
        'total' => 83.00,
        'status' => 'delivered',
        'payment' => 'verified'
    ],

    [
        'order_number' => 'ORD-2026-0875',
        'customer' => 'James Uy',
        'date' => '2026-09-15',
        'total' => 70.00,
        'status' => 'pending',
        'payment' => 'pending'
    ]

];


/* =========================================
   SAMPLE LOW STOCK PRODUCTS
========================================= */

$lowStockProducts = [

    [
        'name' => 'Oil-Control Gel Moisturizer',
        'stock' => 8
    ]

];


/* =========================================
   ORDER STATUS COUNTS
========================================= */

$orderStatusCounts = [
    'pending' => 1,
    'processing' => 2,
    'shipped' => 1,
    'delivered' => 3,
    'cancelled' => 1
];

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
         PureVia
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

    <?php include("../includes/sidemenu.php"); ?>


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
                    <?= htmlspecialchars($_SESSION['first_name']) ?>!
                </p>

            </section>



            <!-- =================================
                 STATISTICS CARDS
            ================================== -->

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
                            ₱<?= number_format($monthlyRevenue, 0) ?>
                        </p>

                        <p class="stat-label">
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

                        <p class="stat-label">
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
                        href="./orders/index.php"
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
                                                    $order['status']
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
                                    href="./inventory/index.php"
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

                    <div class="order-overview-card processing">

                        <strong>
                            <?= $orderStatusCounts['processing'] ?>
                        </strong>

                        <span>
                            Processing
                        </span>

                    </div>



                    <!-- SHIPPED -->

                    <div class="order-overview-card shipped">

                        <strong>
                            <?= $orderStatusCounts['shipped'] ?>
                        </strong>

                        <span>
                            Shipped
                        </span>

                    </div>



                    <!-- DELIVERED -->

                    <div class="order-overview-card delivered">

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


        </main>

    </div>


    <!-- =====================================
         JAVASCRIPT
    ====================================== -->

    <script src="../../assets/js/sidemenu.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>