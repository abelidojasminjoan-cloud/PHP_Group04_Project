<?php

/* =========================================================
   PUREVIA
   SHARED ADMIN + STAFF SIDEBAR
========================================================= */


require_once __DIR__ . '/role-check.php';

$currentUser = requirePureViaAdminOrStaff($conn);
$currentRole = $_SESSION['role'];
$isAdmin = $currentRole === 'admin';
$isStaff = $currentRole === 'staff';


/* =========================================================
   CURRENT DATABASE INFORMATION
========================================================= */

$sidebarFirstName =
    trim($currentUser['first_name'] ?? '');

$sidebarLastName =
    trim($currentUser['last_name'] ?? '');

$sidebarEmail =
    trim($currentUser['email'] ?? '');


$sidebarFullName = trim(
    $sidebarFirstName . ' ' . $sidebarLastName
);


/* Fallback if name is empty */

if ($sidebarFullName === '') {
    $sidebarFullName = 'PureVia User';
}


/* Avatar initial */

$sidebarInitial = strtoupper(
    substr(
        $sidebarFirstName !== ''
            ? $sidebarFirstName
            : $sidebarFullName,
        0,
        1
    )
);


/* =========================================================
   REFRESH SESSION FROM DATABASE
========================================================= */

/*
|--------------------------------------------------------------------------
| The database is the source of truth.
|--------------------------------------------------------------------------
| These session values are refreshed so the rest of the website
| receives the latest account information.
*/

$_SESSION['user_id'] =
    (int) $currentUser['id'];

$_SESSION['role_id'] =
    (int) $currentUser['role_id'];

$_SESSION['role'] =
    $currentRole;

$_SESSION['first_name'] =
    $sidebarFirstName;

$_SESSION['last_name'] =
    $sidebarLastName;

$_SESSION['name'] =
    $sidebarFirstName;

$_SESSION['email'] =
    $sidebarEmail;


/* =========================================================
   PANEL INFORMATION
========================================================= */

if ($isAdmin) {

    $panelName = 'Admin Panel';

    $dashboardUrl =
        BASE_URL . '/admin/dashboard.php';

} else {

    $panelName = 'Staff Panel';

    $dashboardUrl =
        BASE_URL . '/staff/dashboard.php';
}


/* =========================================================
   PAGE PATHS
========================================================= */

/*
|--------------------------------------------------------------------------
| Admin pages
|--------------------------------------------------------------------------
*/

$adminBase =
    BASE_URL . '/admin';


/*
|--------------------------------------------------------------------------
| Staff pages
|--------------------------------------------------------------------------
*/

$staffBase =
    BASE_URL . '/staff';


/* =========================================================
   CURRENT PAGE
========================================================= */

$currentPath =
    $_SERVER['PHP_SELF'] ?? '';


/* =========================================================
   ACTIVE LINK FUNCTION
========================================================= */

if (!function_exists('sidebarActive')) {

    function sidebarActive(string $path): string
    {
        global $currentPath;

        return strpos(
            $currentPath,
            $path
        ) !== false
            ? 'active'
            : '';
    }
}


/* =========================================================
   CREATE ROLE-SPECIFIC LINKS
========================================================= */

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

if ($isAdmin) {

    $productsUrl =
        $adminBase . '/products/products.php';

    $inventoryUrl =
        $adminBase . '/inventory/inventory.php';

    $ordersUrl =
        $adminBase . '/orders/orders.php';

    $paymentsUrl =
        $adminBase . '/payments/payments.php';
}


/*
|--------------------------------------------------------------------------
| STAFF
|--------------------------------------------------------------------------
*/

else {

    $productsUrl =
        $staffBase . '/products/products.php';

    $inventoryUrl =
        $staffBase . '/inventory/inventory.php';

    $ordersUrl =
        $staffBase . '/orders/orders.php';

    $paymentsUrl =
        $staffBase . '/payments/payments.php';
}

?>


<!-- =========================================================
     MOBILE BUTTON
========================================================= -->

<button
    type="button"
    class="sidebar-mobile-toggle"
    id="sidebarMobileToggle"
    aria-label="Open menu"
    aria-controls="adminSidebar"
    aria-expanded="false"
>
    <i class="fa-solid fa-bars"></i>
</button>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside
    class="admin-sidebar"
    id="adminSidebar"
>


    <!-- =====================================================
         BRAND
    ====================================================== -->

    <div class="sidebar-brand">

        <a
            href="<?= htmlspecialchars($dashboardUrl) ?>"
            class="sidebar-logo"
        >

            <span class="sidebar-logo-icon">
                <i class="fa-solid fa-leaf"></i>
            </span>

            <span>PureVia</span>

        </a>


        <p class="sidebar-subtitle">
            <?= htmlspecialchars($panelName) ?>
        </p>

    </div>



    <!-- =====================================================
         NAVIGATION
    ====================================================== -->

    <nav class="sidebar-navigation">


        <?php if ($isStaff): ?>

            <!-- =================================================
                STAFF MENU
                NO CATALOG / OPERATIONS HEADINGS
            ================================================== -->

            <div class="sidebar-section staff-sidebar-section">


                <!-- DASHBOARD -->

                <a
                    href="<?= $dashboardUrl ?>"
                    class="sidebar-link <?= sidebarActive('/staff/dashboard.php') ?>"
                >
                    <div class="sidebar-link-left">

                        <div class="sidebar-menu-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" focusable="false">
                                <path d="M14,10V22H4a2,2,0,0,1-2-2V10Z"/>
                                <path d="M22,10V20a2,2,0,0,1-2,2H16V10Z"/>
                                <path d="M22,4V8H2V4A2,2,0,0,1,4,2H20A2,2,0,0,1,22,4Z"/>
                            </svg>
                        </div>

                        <span>Dashboard</span>

                    </div>
                </a>


                <!-- PRODUCTS -->

                <a
                    href="<?= $productsUrl ?>"
                    class="sidebar-link <?= sidebarActive('/staff/products/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            🧴
                        </span>

                        <span>Products</span>

                    </span>
                </a>


                <!-- INVENTORY -->

                <a
                    href="<?= $inventoryUrl ?>"
                    class="sidebar-link <?= sidebarActive('/staff/inventory/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            📦
                        </span>

                        <span>Inventory</span>

                    </span>
                </a>


                <!-- ORDERS -->

                <a
                    href="<?= $ordersUrl ?>"
                    class="sidebar-link <?= sidebarActive('/staff/orders/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            🛒
                        </span>

                        <span>Orders</span>

                    </span>
                </a>


                <!-- PAYMENTS -->

                <a
                    href="<?= $paymentsUrl ?>"
                    class="sidebar-link <?= sidebarActive('/staff/payments/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            💳
                        </span>

                        <span>Payments</span>

                    </span>
                </a>


                <!-- SALES REPORT -->

                <a
                    href="<?= $staffBase ?>/reports/sales_report.php"
                    class="sidebar-link <?= sidebarActive('/staff/reports/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            📈
                        </span>

                        <span>Sales Report</span>

                    </span>
                </a>


            </div>


        <?php else: ?>

            <!-- =================================================
                ADMIN MENU
            ================================================== -->


            <!-- OVERVIEW -->

            <div class="sidebar-section">

                <a
                    href="<?= $dashboardUrl ?>"
                    class="sidebar-link <?= sidebarActive('/admin/dashboard.php') ?>"
                >
                    <div class="sidebar-link-left">

                        <div class="sidebar-menu-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" focusable="false">
                                <path d="M14,10V22H4a2,2,0,0,1-2-2V10Z"/>
                                <path d="M22,10V20a2,2,0,0,1-2,2H16V10Z"/>
                                <path d="M22,4V8H2V4A2,2,0,0,1,4,2H20A2,2,0,0,1,22,4Z"/>
                            </svg>
                        </div>

                        <span>Dashboard</span>

                    </div>
                </a>


                <a
                    href="<?= $adminBase ?>/users/users.php"
                    class="sidebar-link <?= sidebarActive('/users/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            👥
                        </span>

                        <span>Users</span>

                    </span>
                </a>

            </div>



            <!-- =================================================
                CATALOG
                ADMIN ONLY
            ================================================== -->

            <div class="sidebar-section">

                <p class="sidebar-section-title">
                    CATALOG
                </p>


                <a
                    href="<?= $productsUrl ?>"
                    class="sidebar-link <?= sidebarActive('/products/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            🧴
                        </span>

                        <span>Products</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/categories/categories.php"
                    class="sidebar-link <?= sidebarActive('/categories/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            📁
                        </span>

                        <span>Categories</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/ingredients/ingredients.php"
                    class="sidebar-link <?= sidebarActive('/ingredients/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            ⚗️
                        </span>

                        <span>Ingredients</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/concerns/concerns.php"
                    class="sidebar-link <?= sidebarActive('/concerns/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            ✨
                        </span>

                        <span>Skin Concerns</span>

                    </span>
                </a>

            </div>



            <div class="sidebar-section">
                <p class="sidebar-section-title">SKIN MATCH</p>
                <a href="<?= $adminBase ?>/skin-types/skin-types.php" class="sidebar-link <?= sidebarActive('/skin-types/') ?>"><span class="sidebar-link-left"><span class="sidebar-menu-icon">◉</span><span>Skin Types</span></span></a>
                <a href="<?= $adminBase ?>/suitability/suitability.php" class="sidebar-link <?= sidebarActive('/suitability/') ?>"><span class="sidebar-link-left"><span class="sidebar-menu-icon">✓</span><span>Suitability Management</span></span></a>
                <a href="<?= $adminBase ?>/quiz-analytics/quiz-analytics.php" class="sidebar-link <?= sidebarActive('/quiz-analytics/') ?>"><span class="sidebar-link-left"><span class="sidebar-menu-icon">▥</span><span>Quiz Analytics</span></span></a>
            </div>

            <!-- =================================================
                OPERATIONS
                ADMIN ONLY
            ================================================== -->

            <div class="sidebar-section">

                <p class="sidebar-section-title">
                    OPERATIONS
                </p>


                <a
                    href="<?= $inventoryUrl ?>"
                    class="sidebar-link <?= sidebarActive('/inventory/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            📦
                        </span>

                        <span>Inventory</span>

                    </span>
                </a>


                <a
                    href="<?= $ordersUrl ?>"
                    class="sidebar-link <?= sidebarActive('/orders/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            🛒
                        </span>

                        <span>Orders</span>

                    </span>
                </a>


                <a
                    href="<?= $paymentsUrl ?>"
                    class="sidebar-link <?= sidebarActive('/payments/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            💳
                        </span>

                        <span>Payments</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/reports/sales_report.php"
                    class="sidebar-link <?= sidebarActive('/reports/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            📈
                        </span>

                        <span>Sales Report</span>

                    </span>
                </a>

            </div>



            <!-- =================================================
                SECURITY
                ADMIN ONLY
            ================================================== -->

            <div class="sidebar-section">

                <p class="sidebar-section-title">
                    SECURITY
                </p>


                <a
                    href="<?= $adminBase ?>/security/settings.php"
                    class="sidebar-link <?= sidebarActive('/security/settings.php') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            🔐
                        </span>

                        <span>Security Settings</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/security/login_attempts.php"
                    class="sidebar-link <?= sidebarActive('/security/login_attempts.php') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            🔑
                        </span>

                        <span>Login Attempts</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/security/audit_logs.php"
                    class="sidebar-link <?= sidebarActive('/security/audit_logs.php') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon">
                            📋
                        </span>

                        <span>Audit Logs</span>

                    </span>
                </a>

            </div>


        <?php endif; ?>


    </nav>




    <!-- =====================================================
         ACCOUNT
    ====================================================== -->

    <div class="sidebar-account">


        <div class="sidebar-profile">


            <!-- AVATAR -->

            <div class="sidebar-avatar">

                <?= htmlspecialchars($sidebarInitial) ?>

            </div>



            <!-- DATABASE USER INFORMATION -->

            <div class="sidebar-user-information">


                <p class="sidebar-user-name">

                    <?= htmlspecialchars($sidebarFullName) ?>

                </p>


                <p class="sidebar-user-email">

                    <?= htmlspecialchars($sidebarEmail) ?>

                </p>


                <span
                    class="sidebar-role-badge
                    <?= $isAdmin
                        ? 'sidebar-role-admin'
                        : 'sidebar-role-staff' ?>"
                >

                    <?= htmlspecialchars(
                        ucfirst($currentRole)
                    ) ?>

                </span>


            </div>


        </div>



        <!-- =================================================
             SIGN OUT
        ================================================== -->

        <a
            href="<?= BASE_URL ?>/actions/auth/logout.php"
            class="sidebar-logout"
        >

            <i class="fa-solid fa-arrow-right-from-bracket"></i>

            <span>Sign Out</span>

        </a>


    </div>


</aside>

<?php require_once __DIR__ . '/session_timeout.php'; ?>
