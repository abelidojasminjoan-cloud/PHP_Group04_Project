<?php

session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/StaffPassword.php';


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];


/* =========================================================
   VERIFY STAFF ACCOUNT + GET STAFF NAME
========================================================= */

$stmt = $conn->prepare("
    SELECT
        u.id,
        u.role_id,
        u.password,
        u.password_changed_at,
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
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$staffFirstName = $staffUser['first_name'];
$staffPasswordWarning = pureViaStaffNeedsPasswordChange($staffUser);


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
        href="<?= BASE_URL ?>/assets/css/staff_dashboard.css?v=<?= filemtime(__DIR__ . '/../assets/css/staff_dashboard.css') ?>"
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

            <?php require __DIR__ . '/../includes/staff-password-warning.php'; ?>

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


<!-- ==========================================
     PUREVIA STAFF COOKIE CONSENT
========================================== -->

<div
    id="pvCookieBanner"
    class="pv-cookie-banner"
    role="region"
    aria-label="PureVia Staff Cookie Consent"
    hidden
>
    <div class="pv-cookie-banner-inner">

        <div class="pv-cookie-icon">
            <i class="fa-solid fa-cookie-bite" aria-hidden="true"></i>
        </div>

        <div class="pv-cookie-content">

            <div class="pv-cookie-eyebrow">
                PUREVIA PRIVACY
            </div>

            <h2>Your Privacy Matters</h2>

            <p>
                PureVia uses essential cookies for secure
                staff login and session management.
                You can choose whether to allow optional
                preferences and analytics.
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
     STAFF COOKIE PREFERENCES MODAL
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
            for staff authentication and account security.
        </p>

        <!-- ESSENTIAL COOKIES -->

        <div class="pv-cookie-option">

            <div class="pv-cookie-option-copy">

                <h3>Essential Cookies</h3>

                <p>
                    Required for secure staff login,
                    authentication, and sessions.
                </p>

            </div>

            <span class="pv-cookie-required">
                Always Active
            </span>

        </div>

        <!-- PREFERENCE STORAGE -->

        <div class="pv-cookie-option">

            <div class="pv-cookie-option-copy">

                <h3>Preference Storage</h3>

                <p>
                    Remember optional staff dashboard
                    and interface preferences.
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

        <!-- ANALYTICS COOKIES -->

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
            because they protect your staff session.
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
     PUREVIA STAFF COOKIE CONSENT JAVASCRIPT
========================================== -->

<script nonce="<?= pvScriptNonce() ?>">
document.addEventListener('DOMContentLoaded', function () {

    // Separate consent key for Staff.
    const KEY = 'purevia_staff_cookie_consent_v1';

    const banner =
        document.getElementById('pvCookieBanner');

    const overlay =
        document.getElementById('pvCookieOverlay');

    const accept =
        document.getElementById('pvCookieAccept');

    const reject =
        document.getElementById('pvCookieReject');

    const customize =
        document.getElementById('pvCookieCustomize');

    const close =
        document.getElementById('pvCookieClose');

    const save =
        document.getElementById('pvCookieSave');

    const preferences =
        document.getElementById('pvCookiePreferences');

    const analytics =
        document.getElementById('pvCookieAnalytics');

    if (
        !banner || !overlay || !accept || !reject ||
        !customize || !close || !save ||
        !preferences || !analytics
    ) {
        return;
    }

    let previousFocus = null;

    // ==========================================
    // READ SAVED STAFF COOKIE PREFERENCES
    // ==========================================

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
            // No valid saved preferences.
        }

        return null;
    }

    // ==========================================
    // SAVE STAFF COOKIE PREFERENCES
    // ==========================================

    function storeConsent(
        allowPreferences,
        allowAnalytics
    ) {

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
            // Continue when browser storage is unavailable.
        }

        window.pureviaStaffConsent = consent;

        document.dispatchEvent(
            new CustomEvent('purevia:staff-consent-changed', {
                detail: consent
            })
        );

        banner.hidden = true;
        overlay.hidden = true;
    }

    // ==========================================
    // OPEN COOKIE PREFERENCES
    // ==========================================

    function openPreferences() {

        const current = readConsent();

        preferences.checked =
            current?.preferences ?? false;

        analytics.checked =
            current?.analytics ?? false;

        previousFocus = document.activeElement;

        overlay.hidden = false;
        close.focus();
    }

    // ==========================================
    // CLOSE COOKIE PREFERENCES
    // ==========================================

    function closePreferences() {

        overlay.hidden = true;

        if (
            previousFocus &&
            typeof previousFocus.focus === 'function'
        ) {
            previousFocus.focus();
        }
    }

    // ==========================================
    // INITIAL COOKIE CONSENT STATE
    // ==========================================

    const existing = readConsent();

    if (existing) {

        preferences.checked = existing.preferences;
        analytics.checked = existing.analytics;

        window.pureviaStaffConsent = existing;

        banner.hidden = true;

    } else {

        window.pureviaStaffConsent = {
            version: 1,
            essential: true,
            preferences: false,
            analytics: false
        };

        banner.hidden = false;
    }

    // ==========================================
    // ACCEPT ALL
    // ==========================================

    accept.addEventListener('click', function () {

        storeConsent(true, true);

    });

    // ==========================================
    // REJECT OPTIONAL
    // ==========================================

    reject.addEventListener('click', function () {

        storeConsent(false, false);

    });

    // ==========================================
    // MANAGE PREFERENCES
    // ==========================================

    customize.addEventListener(
        'click',
        openPreferences
    );

    // ==========================================
    // CLOSE MODAL
    // ==========================================

    close.addEventListener(
        'click',
        closePreferences
    );

    // ==========================================
    // SAVE CUSTOM PREFERENCES
    // ==========================================

    save.addEventListener('click', function () {

        storeConsent(
            preferences.checked,
            analytics.checked
        );

    });

    // ==========================================
    // CLOSE BY CLICKING OUTSIDE MODAL
    // ==========================================

    overlay.addEventListener('click', function (event) {

        if (event.target === overlay) {
            closePreferences();
        }

    });

    // ==========================================
    // ESCAPE KEY
    // ==========================================

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape' && !overlay.hidden) {

            closePreferences();

        }

    });

    // Allow a future Staff Settings link to
    // reopen cookie preferences after consent.
    window.openPureviaStaffCookieSettings =
        openPreferences;

});
</script>

</body>
</html>
