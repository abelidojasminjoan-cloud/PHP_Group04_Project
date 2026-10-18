<?php

/* =========================================================
   PUREVIA STAFF - PAYMENTS
========================================================= */

session_start();

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

$pageTitle = "Payments";


/* =========================================================
   STAFF ACCESS ONLY

   1 = Admin
   2 = Staff
   3 = Customer
========================================================= */

if (
    empty($_SESSION['user_id']) ||
    empty($_SESSION['role_id']) ||
    (int) $_SESSION['role_id'] !== 2
) {
    header("Location: " . BASE_URL . "/public/index.php");
    exit;
}


/* =========================================================
   GET PAYMENTS
========================================================= */

$sql = "
    SELECT
        p.id,
        p.order_id,
        p.payment_method,
        p.amount,
        p.payment_status,
        p.transaction_reference,
        p.gateway_reference,
        p.verified_by,
        p.verified_at,
        p.created_at,

        o.order_number,

        u.first_name,
        u.last_name

    FROM payments AS p

    INNER JOIN orders AS o
        ON p.order_id = o.id

    INNER JOIN users AS u
        ON o.user_id = u.id

    ORDER BY
        p.created_at DESC,
        p.id DESC
";


$result = $conn->query($sql);

$payments = [];


if ($result) {

    while ($row = $result->fetch_assoc()) {

        $payments[] = $row;
    }
}


/* =========================================================
   TOTAL PAYMENTS
========================================================= */

$totalPayments = count($payments);


/* =========================================================
   GET AVAILABLE PAYMENT METHODS

   This makes the filter automatically match the methods
   currently stored in the database.
========================================================= */

$paymentMethods = [];


foreach ($payments as $payment) {

    $method = trim(
        $payment['payment_method'] ?? ''
    );


    if (
        $method !== '' &&
        !in_array(
            $method,
            $paymentMethods,
            true
        )
    ) {

        $paymentMethods[] = $method;
    }
}


sort($paymentMethods);

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
        <?= htmlspecialchars($pageTitle) ?> | PureVia Staff
    </title>


    <!-- GOOGLE FONTS -->

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


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- REUSE SIDEMENU CSS -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/sidemenu.css"
    >


    <!-- REUSE ADMIN PAYMENTS CSS -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/payments.css"
    >

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>


<body>


    <!-- =====================================================
         SIDEMENU
    ====================================================== -->

    <?php
    include __DIR__ . '/../../includes/sidemenu.php';
    ?>


    <!-- =====================================================
         STAFF LAYOUT
    ====================================================== -->

    <div class="admin-layout">


        <!-- =================================================
             TOP BAR
        ================================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Payments
            </p>

        </header>


        <!-- =================================================
             MAIN
        ================================================== -->

        <main class="payments-main">


            <!-- =============================================
                 PAGE HEADER
            ============================================== -->

            <section class="payments-page-header">

                <div class="payments-heading">

                    <h1>
                        Payments
                    </h1>

                    <p>

                        <span id="paymentCount">
                            <?= number_format($totalPayments) ?>
                        </span>

                        <span id="paymentRecordLabel">

                            <?= $totalPayments === 1
                                ? 'record'
                                : 'records' ?>

                        </span>

                    </p>

                </div>

            </section>


            <!-- =============================================
                 PAYMENTS CARD
            ============================================== -->

            <section class="payments-card">


                <!-- =========================================
                     SEARCH + FILTER
                ========================================== -->

                <div class="payments-toolbar">


                    <!-- SEARCH -->

                    <div class="payments-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="paymentSearch"
                            placeholder="Search payments..."
                            autocomplete="off"
                            aria-label="Search payments"
                        >

                    </div>


                    <!-- PAYMENT METHOD FILTER -->

                    <div class="payments-filter-dropdown">

                        <i
                            class="fa-solid fa-filter payments-filter-icon"
                        ></i>

                        <select
                            id="paymentMethodFilter"
                            class="payments-filter-select"
                            aria-label="Filter by payment method"
                        >

                            <option value="all">
                                All Methods
                            </option>


                            <?php foreach ($paymentMethods as $method): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        strtolower($method)
                                    ) ?>"
                                >

                                    <?= htmlspecialchars(
                                        ucwords(
                                            str_replace(
                                                '_',
                                                ' ',
                                                $method
                                            )
                                        )
                                    ) ?>

                                </option>

                            <?php endforeach; ?>


                        </select>


                        <i
                            class="fa-solid fa-chevron-down payments-filter-arrow"
                        ></i>

                    </div>


                    <!-- PAYMENT STATUS FILTER -->

                    <div class="payments-filter-dropdown">

                        <select
                            id="paymentStatusFilter"
                            class="
                                payments-filter-select
                                payments-filter-no-icon
                            "
                            aria-label="Filter by payment status"
                        >

                            <option value="all">
                                All Status
                            </option>

                            <option value="pending">
                                Pending
                            </option>

                            <option value="paid">
                                Verified
                            </option>

                            <option value="failed">
                                Failed
                            </option>

                            <option value="refunded">
                                Refunded
                            </option>

                        </select>


                        <i
                            class="fa-solid fa-chevron-down payments-filter-arrow"
                        ></i>

                    </div>


                </div>


                <!-- =========================================
                     PAYMENTS TABLE
                ========================================== -->

                <div class="payments-table-wrapper">

                    <table class="payments-table">


                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>ID</th>

                                <th>ORDER</th>

                                <th>CUSTOMER</th>

                                <th>AMOUNT</th>

                                <th>METHOD</th>

                                <th>REFERENCE</th>

                                <th>DATE</th>

                                <th>STATUS</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody id="paymentsTableBody">


                        <?php if (!empty($payments)): ?>


                            <?php foreach ($payments as $payment): ?>


                                <?php

                                /* =============================
                                   CUSTOMER
                                ============================== */

                                $customerName = trim(

                                    ($payment['first_name'] ?? '')
                                    . ' '
                                    . ($payment['last_name'] ?? '')

                                );


                                if ($customerName === '') {

                                    $customerName = 'Customer';
                                }


                                /* =============================
                                   PAYMENT METHOD
                                ============================== */

                                $paymentMethod = strtolower(
                                    trim(
                                        $payment['payment_method']
                                        ?? ''
                                    )
                                );


                                $paymentMethodDisplay = ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $paymentMethod
                                    )
                                );


                                /* =============================
                                   PAYMENT STATUS
                                ============================== */

                                $paymentStatus = strtolower(
                                    trim(
                                        $payment['payment_status']
                                        ?? 'pending'
                                    )
                                );


                                /* =============================
                                   DISPLAY STATUS

                                   Database:
                                   paid = VERIFIED
                                   failed = FAILED
                                ============================== */

                                if ($paymentStatus === 'paid') {

                                    $paymentStatusDisplay = 'VERIFIED';

                                } else {

                                    $paymentStatusDisplay =
                                        strtoupper($paymentStatus);
                                }


                                /* =============================
                                   PAYMENT DATE

                                   Verified payment:
                                   verified_at

                                   Otherwise:
                                   created_at
                                ============================== */

                                $paymentDateSource =

                                    !empty($payment['verified_at'])

                                    ? $payment['verified_at']

                                    : $payment['created_at'];


                                $paymentDate = '—';


                                if (!empty($paymentDateSource)) {

                                    $timestamp = strtotime(
                                        $paymentDateSource
                                    );


                                    if ($timestamp !== false) {

                                        $paymentDate = date(
                                            'Y-m-d',
                                            $timestamp
                                        );
                                    }
                                }


                                /* =============================
                                   PAYMENT DISPLAY ID
                                ============================== */

                                $paymentDisplayId =

                                    'PAY-' .

                                    str_pad(
                                        (string) $payment['id'],
                                        4,
                                        '0',
                                        STR_PAD_LEFT
                                    );


                                /* =============================
                                   PAYMENT REFERENCE
                                ============================== */

                                $paymentReference = '';


                                if (
                                    !empty(
                                        $payment[
                                            'transaction_reference'
                                        ]
                                    )
                                ) {

                                    $paymentReference =
                                        $payment[
                                            'transaction_reference'
                                        ];


                                } elseif (
                                    !empty(
                                        $payment[
                                            'gateway_reference'
                                        ]
                                    )
                                ) {

                                    $paymentReference =
                                        $payment[
                                            'gateway_reference'
                                        ];
                                }


                                /* =============================
                                   SEARCH VALUE
                                ============================== */

                                $searchValue = strtolower(

                                    $paymentDisplayId
                                    . ' '
                                    . ($payment['order_number'] ?? '')
                                    . ' '
                                    . $customerName
                                    . ' '
                                    . $paymentMethodDisplay
                                    . ' '
                                    . $paymentReference
                                    . ' '
                                    . $paymentStatus
                                    . ' '
                                    . $paymentStatusDisplay
                                    . ' '
                                    . $paymentDate

                                );

                                ?>


                                <tr
                                    class="payment-row"

                                    data-search="<?= htmlspecialchars(
                                        $searchValue
                                    ) ?>"

                                    data-method="<?= htmlspecialchars(
                                        $paymentMethod
                                    ) ?>"

                                    data-status="<?= htmlspecialchars(
                                        $paymentStatus
                                    ) ?>"
                                >


                                    <!-- PAYMENT ID -->

                                    <td>

                                        <span class="payment-id">

                                            <?= htmlspecialchars(
                                                $paymentDisplayId
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ORDER -->

                                    <td>

                                        <span class="payment-order">

                                            <?= htmlspecialchars(
                                                $payment['order_number']
                                                ?? '—'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- CUSTOMER -->

                                    <td>

                                        <span class="payment-customer">

                                            <?= htmlspecialchars(
                                                $customerName
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- AMOUNT -->

                                    <td>

                                        <span class="payment-amount">

                                            ₱<?= number_format(
                                                (float) (
                                                    $payment['amount']
                                                    ?? 0
                                                ),
                                                2
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- METHOD -->

                                    <td>

                                        <span class="payment-method">

                                            <?= htmlspecialchars(
                                                $paymentMethodDisplay
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- REFERENCE -->

                                    <td>

                                        <span class="payment-reference">


                                            <?php if (
                                                $paymentReference !== ''
                                            ): ?>

                                                <?= htmlspecialchars(
                                                    $paymentReference
                                                ) ?>

                                            <?php else: ?>

                                                —

                                            <?php endif; ?>


                                        </span>

                                    </td>


                                    <!-- DATE -->

                                    <td>

                                        <span class="payment-date">

                                            <?= htmlspecialchars(
                                                $paymentDate
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                payment-status-badge
                                                payment-status-<?= htmlspecialchars(
                                                    $paymentStatus
                                                ) ?>
                                            "
                                        >

                                            <?= htmlspecialchars(
                                                $paymentStatusDisplay
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="payment-actions">


                                            <?php if (
                                                $paymentStatus === 'pending'
                                            ): ?>


                                                <!-- VERIFY -->

                                                <button
                                                    type="button"
                                                    class="
                                                        payment-action-button
                                                        payment-verify-button
                                                    "
                                                    data-payment-id="<?= (int) $payment['id'] ?>"
                                                    data-action="paid"
                                                >
                                                    Verify
                                                </button>


                                                <!-- REJECT -->

                                                <button
                                                    type="button"
                                                    class="
                                                        payment-action-button
                                                        payment-reject-button
                                                    "
                                                    data-payment-id="<?= (int) $payment['id'] ?>"
                                                    data-action="failed"
                                                >
                                                    Reject
                                                </button>


                                            <?php else: ?>


                                                <span class="payment-no-action">
                                                    —
                                                </span>


                                            <?php endif; ?>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        <!-- =================================
                             NO PAYMENTS
                        ================================== -->

                        <tr
                            id="noPaymentsRow"
                            class="no-payments-row"

                            <?= !empty($payments)
                                ? 'hidden'
                                : '' ?>
                        >

                            <td colspan="9">

                                <div class="no-payments-message">

                                    <i
                                        class="fa-regular fa-folder-open"
                                    ></i>

                                    <p>
                                        No payments found.
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


    <!-- REUSE SIDEMENU JS -->

    <script
        src="<?= BASE_URL ?>/assets/js/sidemenu.js"
    ></script>


    <!-- REUSE PAYMENTS JS -->

    <script
        src="<?= BASE_URL ?>/assets/js/payments.js"
    ></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>