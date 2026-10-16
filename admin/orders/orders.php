<?php

/* =========================================================
   PUREVIA ADMIN - ORDERS
========================================================= */

session_start();
require_once __DIR__ . '/../../config/app.php';

$pageTitle = "Orders";


/* =========================================================
   TEMPORARY ADMIN SESSION
   Remove when your login system is fully connected
========================================================= */

$_SESSION['first_name'] =
    $_SESSION['first_name'] ?? 'Ava';

$_SESSION['last_name'] =
    $_SESSION['last_name'] ?? 'Santos';

$_SESSION['email'] =
    $_SESSION['email'] ?? 'admin@purevia.com';


/* =========================================================
   DATABASE
========================================================= */

require_once __DIR__ . '/../../config/db.php';


/* =========================================================
   GET ORDERS
========================================================= */

$sql = "
    SELECT
        o.id,
        o.order_number,
        o.total_amount,
        o.order_status,
        o.payment_status,
        o.created_at,
        u.first_name,
        u.last_name
    FROM orders o
    INNER JOIN users u
        ON o.user_id = u.id
    ORDER BY o.created_at DESC
";

$result = $conn->query($sql);

$orders = [];

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $orders[] = $row;

    }

}


/* =========================================================
   TOTAL ORDERS
========================================================= */

$totalOrders = count($orders);


/* =========================================================
   ADMIN INFORMATION
========================================================= */

$adminFirstName =
    $_SESSION['first_name'] ?? 'Admin';

$adminLastName =
    $_SESSION['last_name'] ?? 'User';

$adminEmail =
    $_SESSION['email'] ?? 'admin@purevia.com';

$adminName = trim(
    $adminFirstName . ' ' . $adminLastName
);

$adminInitial = strtoupper(
    substr($adminFirstName, 0, 1)
);

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
        <?= htmlspecialchars($pageTitle) ?> | PureVia Admin
    </title>


    <!-- =====================================================
         GOOGLE FONTS
    ====================================================== -->

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         SIDEBAR CSS
    ====================================================== -->

    <link rel="stylesheet" href="../../assets/css/sidemenu.css">


    <!-- =====================================================
         ORDERS CSS
    ====================================================== -->

    <link rel="stylesheet" href="../../assets/css/orders.css">

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>


<body>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php include("../../includes/sidemenu.php"); ?>


    <!-- =====================================================
         ADMIN LAYOUT
    ====================================================== -->

    <div class="admin-layout">


        <!-- =================================================
             TOP BAR
        ================================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Orders
            </p>

        </header>


        <!-- =================================================
             MAIN CONTENT
        ================================================== -->

        <main class="orders-main">


            <!-- =============================================
                 PAGE HEADER
            ============================================== -->

            <section class="orders-page-header">

                <div class="orders-heading">

                    <h1>
                        Orders
                    </h1>

                    <p>

                        <span id="orderCount">
                            <?= number_format($totalOrders) ?>
                        </span>

                        <?= $totalOrders === 1
                            ? 'record'
                            : 'records' ?>

                    </p>

                </div>

            </section>


            <!-- =============================================
                 ORDERS CARD
            ============================================== -->

            <section class="orders-card">


                <!-- =========================================
                     SEARCH + FILTERS
                ========================================== -->

                <div class="orders-toolbar">


                    <!-- SEARCH -->

                    <div class="orders-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="orderSearch"
                            placeholder="Search orders..."
                            autocomplete="off"
                            aria-label="Search orders"
                        >

                    </div>


                    <!-- ORDER STATUS FILTER -->

                    <div class="orders-filter-dropdown">

                        <i
                            class="fa-solid fa-filter orders-filter-icon"
                        ></i>

                        <select
                            id="orderStatusFilter"
                            class="orders-filter-select"
                            aria-label="Filter by order status"
                        >

                            <option value="all">
                                All Status
                            </option>

                            <option value="pending">
                                Pending
                            </option>

                            <option value="processing">
                                Processing
                            </option>

                            <option value="shipped">
                                Shipped
                            </option>

                            <option value="delivered">
                                Delivered
                            </option>

                            <option value="cancelled">
                                Cancelled
                            </option>

                        </select>

                        <i
                            class="fa-solid fa-chevron-down orders-filter-arrow"
                        ></i>

                    </div>


                    <!-- PAYMENT FILTER -->

                    <div class="orders-filter-dropdown">

                        <select
                            id="paymentStatusFilter"
                            class="
                                orders-filter-select
                                orders-filter-no-icon
                            "
                            aria-label="Filter by payment status"
                        >

                            <option value="all">
                                All Payment
                            </option>

                            <option value="pending">
                                Pending
                            </option>

                            <option value="verified">
                                Verified
                            </option>

                            <option value="paid">
                                Paid
                            </option>

                            <option value="failed">
                                Failed
                            </option>

                            <option value="refunded">
                                Refunded
                            </option>

                        </select>

                        <i
                            class="fa-solid fa-chevron-down orders-filter-arrow"
                        ></i>

                    </div>


                </div>


                <!-- =========================================
                     TABLE
                ========================================== -->

                <div class="orders-table-wrapper">

                    <table class="orders-table">


                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>ORDER ID</th>

                                <th>CUSTOMER</th>

                                <th>DATE</th>

                                <th>TOTAL</th>

                                <th>STATUS</th>

                                <th>PAYMENT</th>

                                <th>SAVE</th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody id="ordersTableBody">


                            <?php if (!empty($orders)): ?>


                                <?php foreach ($orders as $order): ?>


                                    <?php

                                    /* =========================
                                       CUSTOMER NAME
                                    ========================== */

                                    $customerName = trim(
                                        $order['first_name']
                                        . ' '
                                        . $order['last_name']
                                    );


                                    /* =========================
                                       ORDER STATUS
                                    ========================== */

                                    $orderStatus = strtolower(
                                        trim(
                                            $order['order_status']
                                        )
                                    );


                                    /* =========================
                                       PAYMENT STATUS
                                    ========================== */

                                    $paymentStatus = strtolower(
                                        trim(
                                            $order['payment_status']
                                        )
                                    );


                                    /* =========================
                                       ORDER DATE
                                    ========================== */

                                    $orderDate = date(
                                        'Y-m-d',
                                        strtotime(
                                            $order['created_at']
                                        )
                                    );


                                    /* =========================
                                       SEARCH DATA
                                    ========================== */

                                    $searchData = strtolower(
                                        $order['order_number']
                                        . ' '
                                        . $customerName
                                        . ' '
                                        . $orderStatus
                                        . ' '
                                        . $paymentStatus
                                    );

                                    ?>


                                    <tr
                                        class="order-row"

                                        data-search="<?= htmlspecialchars(
                                            $searchData
                                        ) ?>"

                                        data-order-id="<?= htmlspecialchars(
                                            strtolower(
                                                $order['order_number']
                                            )
                                        ) ?>"

                                        data-customer="<?= htmlspecialchars(
                                            strtolower(
                                                $customerName
                                            )
                                        ) ?>"

                                        data-status="<?= htmlspecialchars(
                                            $orderStatus
                                        ) ?>"

                                        data-payment="<?= htmlspecialchars(
                                            $paymentStatus
                                        ) ?>"
                                    >


                                        <!-- =====================
                                             ORDER ID
                                        ====================== -->

                                        <td>

                                            <span class="order-number">

                                                <?= htmlspecialchars(
                                                    $order['order_number']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================
                                             CUSTOMER
                                        ====================== -->

                                        <td>

                                            <span class="order-customer">

                                                <?= htmlspecialchars(
                                                    $customerName
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================
                                             DATE
                                        ====================== -->

                                        <td>

                                            <span class="order-date">

                                                <?= htmlspecialchars(
                                                    $orderDate
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================
                                             TOTAL
                                        ====================== -->

                                        <td>

                                            <span class="order-total">

                                                ₱<?= number_format(
                                                    (float) $order['total_amount'],
                                                    2
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================
                                             ORDER STATUS
                                        ====================== -->

                                        <td>

                                            <select
                                                class="order-status-select"

                                                data-order-id="<?= (int) $order['id'] ?>"

                                                data-original-value="<?= htmlspecialchars(
                                                    $orderStatus
                                                ) ?>"
                                            >


                                                <option
                                                    value="pending"
                                                    <?= $orderStatus === 'pending'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Pending
                                                </option>


                                                <option
                                                    value="processing"
                                                    <?= $orderStatus === 'processing'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Processing
                                                </option>


                                                <option
                                                    value="shipped"
                                                    <?= $orderStatus === 'shipped'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Shipped
                                                </option>


                                                <option
                                                    value="delivered"
                                                    <?= $orderStatus === 'delivered'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Delivered
                                                </option>


                                                <option
                                                    value="cancelled"
                                                    <?= $orderStatus === 'cancelled'
                                                        ? 'selected'
                                                        : '' ?>
                                                >
                                                    Cancelled
                                                </option>


                                            </select>

                                        </td>


                                        <!-- =====================
                                             PAYMENT
                                        ====================== -->

                                        <td>

                                            <span
                                                class="
                                                    payment-badge
                                                    payment-<?= htmlspecialchars(
                                                        $paymentStatus
                                                    ) ?>
                                                "
                                            >

                                                <?= htmlspecialchars(
                                                    strtoupper(
                                                        $paymentStatus
                                                    )
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================
                                             SAVE
                                        ====================== -->

                                        <td>

                                            <div class="order-save-area">

                                                <button
                                                    type="button"
                                                    class="order-save-button"
                                                    data-order-id="<?= (int) $order['id'] ?>"
                                                    hidden
                                                >
                                                    Save
                                                </button>

                                                <span class="order-save-empty">
                                                    —
                                                </span>

                                            </div>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                            <!-- =============================
                                 NO RESULTS
                            ============================== -->

                            <tr
                                id="noOrdersRow"
                                class="no-orders-row"
                                <?= !empty($orders)
                                    ? 'hidden'
                                    : '' ?>
                            >

                                <td colspan="7">

                                    <div class="no-orders-message">

                                        <i
                                            class="fa-regular fa-folder-open"
                                        ></i>

                                        <p>
                                            No orders found.
                                        </p>

                                    </div>

                                </td>

                            </tr>


                        </tbody>


                    </table>

                </div>


            </section>


        </main>


    </div>


<!-- =====================================================
     SIDEBAR JS
 ====================================================== -->

<script src="../../assets/js/sidemenu.js"></script>


<!-- =====================================================
    ORDERS JS
 ====================================================== -->

<script src="../../assets/js/orders.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>