<?php

/* =========================================================
   PUREVIA ADMIN - ORDERS
========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
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
require_once __DIR__ . '/../../includes/role-check.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../services/AdminOrder.php';
requirePureViaAdmin($conn);
$csrfToken = csrfToken();


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
        <?= pvHtml($pageTitle) ?> | PureVia Admin
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
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Playfair+Display:wght@600;700&display=swap"
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


<body class="orders-admin-page">


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
                            <option value="all">All Status</option>
                            <?php foreach (AdminOrder::STATUSES as $value => $label): ?>
                                <option value="<?= pvHtml($value) ?>"><?= pvHtml($label) ?></option>
                            <?php endforeach; ?>
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
                            <option value="all">All Payment</option>
                            <?php foreach (AdminOrder::PAYMENTS as $value => $label): ?>
                                <option value="<?= pvHtml($value) ?>"><?= pvHtml($label) ?></option>
                            <?php endforeach; ?>
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

                                <th>ACTIONS</th>

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
                                        data-record-id="<?= (int) $order['id'] ?>"

                                        data-search="<?= pvHtml(
                                            $searchData
                                        ) ?>"

                                        data-order-id="<?= pvHtml(
                                            strtolower(
                                                $order['order_number']
                                            )
                                        ) ?>"

                                        data-customer="<?= pvHtml(
                                            strtolower(
                                                $customerName
                                            )
                                        ) ?>"

                                        data-status="<?= pvHtml(
                                            $orderStatus
                                        ) ?>"

                                        data-payment="<?= pvHtml(
                                            $paymentStatus
                                        ) ?>"
                                    >


                                        <!-- =====================
                                             ORDER ID
                                        ====================== -->

                                        <td>

                                            <span class="order-number">

                                                <?= pvHtml(
                                                    $order['order_number']
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================
                                             CUSTOMER
                                        ====================== -->

                                        <td>

                                            <span class="order-customer">

                                                <?= pvHtml(
                                                    $customerName
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =====================
                                             DATE
                                        ====================== -->

                                        <td>

                                            <span class="order-date">

                                                <?= pvHtml(
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


                                        <td><span class="order-status-badge order-state-<?= pvHtml($orderStatus) ?>"><?= pvHtml(strtoupper(AdminOrder::STATUSES[$orderStatus] ?? $orderStatus)) ?></span></td>
                                        <td><span class="payment-badge payment-<?= pvHtml($paymentStatus) ?>"><?= pvHtml(strtoupper(AdminOrder::PAYMENTS[$paymentStatus] ?? $paymentStatus)) ?></span></td>
                                        <td><button type="button" class="order-view-button" data-order-record="<?= (int) $order['id'] ?>" aria-haspopup="dialog" aria-controls="adminOrderModal">View Details</button></td>

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



<div class="admin-order-modal" id="adminOrderModal" hidden>
    <div class="aom-backdrop" data-order-close></div>
    <section class="aom-dialog" role="dialog" aria-modal="true" aria-labelledby="aomTitle" aria-describedby="aomNumber" tabindex="-1">
        <header class="aom-header">
            <div><h2 id="aomTitle">Order Details</h2><p id="aomNumber"></p></div>
            <button type="button" class="aom-dismiss" data-order-close aria-label="Close order details">&times;</button>
        </header>
        <form id="adminOrderForm" class="aom-form">
            <div class="aom-body">
                <p id="aomError" class="aom-error" role="alert" hidden></p>
                <p id="aomLoading" class="aom-loading" role="status">Loading order details...</p>
                <div id="aomContent" hidden>
                    <div class="aom-overview">
                        <div><span>Customer</span><strong id="aomCustomer"></strong><small id="aomCustomerId"></small></div>
                        <div><span>Order Date</span><strong id="aomDate"></strong><small id="aomItemCount"></small></div>
                        <div><span>Order Total</span><strong id="aomTotal"></strong><small id="aomMethod"></small></div>
                    </div>
                    <section class="aom-panel aom-items"><header><h3>Items Ordered</h3><span id="aomProductCount"></span></header><div id="aomItems"></div></section>
                    <div class="aom-columns">
                        <section class="aom-panel"><header><h3>Delivery Details</h3></header><div class="aom-panel-body"><strong id="aomRecipient"></strong><p id="aomPhone"></p><div id="aomAddress"></div></div></section>
                        <section class="aom-panel"><header><h3>Payment Summary</h3></header><div class="aom-panel-body"><dl class="aom-totals"><div><dt>Subtotal</dt><dd id="aomSubtotal"></dd></div><div><dt>Shipping</dt><dd id="aomShipping"></dd></div><div class="aom-grand-total"><dt>Total</dt><dd id="aomSummaryTotal"></dd></div></dl></div></section>
                    </div>
                    <div class="aom-status-controls">
                        <label for="aomStatus">Order Status<select id="aomStatus" name="status" required><?php foreach (AdminOrder::STATUSES as $value=>$label): ?><option value="<?= pvHtml($value) ?>"><?= pvHtml($label) ?></option><?php endforeach; ?></select></label>
                        <label for="aomPayment">Payment Status<select id="aomPayment" name="payment_status" required><?php foreach (AdminOrder::PAYMENTS as $value=>$label): ?><option value="<?= pvHtml($value) ?>"><?= pvHtml($label) ?></option><?php endforeach; ?></select></label>
                    </div>
                </div>
            </div>
            <footer class="aom-footer"><button type="button" class="aom-close" data-order-close>Close</button><button type="submit" id="aomSave" class="aom-save" disabled>Save Changes</button></footer>
        </form>
    </section>
</div>
<script nonce="<?= pvScriptNonce() ?>">window.PUREVIA_ADMIN_ORDERS=<?= pvJson(['baseUrl'=>BASE_URL,'endpoint'=>BASE_URL.'/actions/admin/orders/order.php','csrfToken'=>$csrfToken,'statuses'=>AdminOrder::STATUSES,'payments'=>AdminOrder::PAYMENTS]) ?>;</script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/admin-order-details.js"></script>

<!-- =====================================================
     SIDEBAR JS
 ====================================================== -->

<script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/sidemenu.js"></script>


<!-- =====================================================
    ORDERS JS
 ====================================================== -->

<script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/orders.js"></script>


<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>