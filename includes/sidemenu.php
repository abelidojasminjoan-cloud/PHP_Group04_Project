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
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/image-fallbacks.js"></script>


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
            href="<?= pvHtml($dashboardUrl) ?>"
            class="sidebar-logo"
        >

            <span class="sidebar-logo-icon">
                <i class="fa-solid fa-leaf"></i>
            </span>

            <span>PureVia</span>

        </a>


        <p class="sidebar-subtitle">
            <?= pvHtml($panelName) ?>
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

                        <span class="sidebar-menu-icon sidebar-product-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="20" height="20" fill="currentColor" focusable="false">
                                <path d="M256 64L256 144L384 144L384 120L456 120C469.3 120 480 109.3 480 96C480 82.7 469.3 72 456 72L384 72L384 64C384 46.3 369.7 32 352 32L288 32C270.3 32 256 46.3 256 64zM224 192C188.7 192 160 220.7 160 256L160 512C160 547.3 188.7 576 224 576L416 576C451.3 576 480 547.3 480 512L480 256C480 220.7 451.3 192 416 192L224 192zM384 408C384 443.3 355.3 464 320 464C284.7 464 256 443.3 256 408C256 375.5 293 327.1 306.9 310.1C310.1 306.2 315 304 320 304C325 304 329.9 306.2 333.1 310.1C347 327.1 384 375.5 384 408z"/>
                            </svg>
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

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" width="18" height="18" fill="currentColor" focusable="false">
                                    <g>
                                        <polygon points="2.75,3.815918 8.190918,1.095459 6,0 0.559082,2.720459"/>
                                        <polygon points="9.309082,1.654541 3.8681641,4.375 6,5.440918 11.440918,2.720459"/>
                                        <polygon points="5.5,6.309082 0,3.559082 0,9.25 5.5,12"/>
                                        <polygon points="6.5,6.309082 6.5,12 12,9.25 12,3.559082"/>
                                    </g>
                                </svg>
                            </div>
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

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M24 48C10.7 48 0 58.7 0 72C0 85.3 10.7 96 24 96L69.3 96C73.2 96 76.5 98.8 77.2 102.6L129.3 388.9C135.5 423.1 165.3 448 200.1 448L456 448C469.3 448 480 437.3 480 424C480 410.7 469.3 400 456 400L200.1 400C188.5 400 178.6 391.7 176.5 380.3L171.4 352L475 352C505.8 352 532.2 330.1 537.9 299.8L568.9 133.9C572.6 114.2 557.5 96 537.4 96L124.7 96L124.3 94C119.5 67.4 96.3 48 69.2 48L24 48zM208 576C234.5 576 256 554.5 256 528C256 501.5 234.5 480 208 480C181.5 480 160 501.5 160 528C160 554.5 181.5 576 208 576zM432 576C458.5 576 480 554.5 480 528C480 501.5 458.5 480 432 480C405.5 480 384 501.5 384 528C384 554.5 405.5 576 432 576z"/>
                                </svg>
                            </div>
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

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-user-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M96 192C96 130.1 146.1 80 208 80C269.9 80 320 130.1 320 192C320 253.9 269.9 304 208 304C146.1 304 96 253.9 96 192zM32 528C32 430.8 110.8 352 208 352C305.2 352 384 430.8 384 528L384 534C384 557.2 365.2 576 342 576L74 576C50.8 576 32 557.2 32 534L32 528zM464 128C517 128 560 171 560 224C560 277 517 320 464 320C411 320 368 277 368 224C368 171 411 128 464 128zM464 368C543.5 368 608 432.5 608 512L608 534.4C608 557.4 589.4 576 566.4 576L421.6 576C428.2 563.5 432 549.2 432 534L432 528C432 476.5 414.6 429.1 385.5 391.3C408.1 376.6 435.1 368 464 368z"/>
                                </svg>
                            </div>
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

                        <span class="sidebar-menu-icon sidebar-product-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="20" height="20" fill="currentColor" focusable="false">
                                <path d="M256 64L256 144L384 144L384 120L456 120C469.3 120 480 109.3 480 96C480 82.7 469.3 72 456 72L384 72L384 64C384 46.3 369.7 32 352 32L288 32C270.3 32 256 46.3 256 64zM224 192C188.7 192 160 220.7 160 256L160 512C160 547.3 188.7 576 224 576L416 576C451.3 576 480 547.3 480 512L480 256C480 220.7 451.3 192 416 192L224 192zM384 408C384 443.3 355.3 464 320 464C284.7 464 256 443.3 256 408C256 375.5 293 327.1 306.9 310.1C310.1 306.2 315 304 320 304C325 304 329.9 306.2 333.1 310.1C347 327.1 384 375.5 384 408z"/>
                            </svg>
                        </span>

                        <span>Products</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/categories/categories.php"
                    class="sidebar-link <?= sidebarActive('/categories/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-category-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="20" height="20" fill="currentColor" focusable="false">
                                    <path d="M215.99512,71.99805H130.65625L102.917,51.19336a16.07363,16.07363,0,0,0-9.57812-3.19531H39.99512a16.01581,16.01581,0,0,0-16,16v136a16.01582,16.01582,0,0,0,16,16h176a16.01583,16.01583,0,0,0,16-16v-112A16.01582,16.01582,0,0,0,215.99512,71.99805Zm-176,24v-32H93.33887l21.32617,15.999-21.3418,16.001ZM151.99707,156h-48a8,8,0,0,1,0-16h48a8,8,0,0,1,0,16Z"/>
                                </svg>
                            </div>
                        </span>

                        <span>Categories</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/ingredients/ingredients.php"
                    class="sidebar-link <?= sidebarActive('/ingredients/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-ingredient-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M384 64L224 64C206.3 64 192 78.3 192 96C192 113.7 206.3 128 224 128L224 279.5L103.5 490.3C98.6 499 96 508.7 96 518.7C96 550.4 121.6 576 153.3 576L486.7 576C518.3 576 544 550.4 544 518.7C544 508.7 541.4 498.9 536.5 490.3L416 279.5L416 128C433.7 128 448 113.7 448 96C448 78.3 433.7 64 416 64L384 64zM288 279.5L288 128L352 128L352 279.5C352 290.6 354.9 301.6 360.4 311.3L402 384L238 384L279.6 311.3C285.1 301.6 288 290.7 288 279.5z"/>
                                </svg>
                            </div>
                        </span>

                        <span>Ingredients</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/concerns/concerns.php"
                    class="sidebar-link <?= sidebarActive('/concerns/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-concern-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 56 56" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M 26.6875 12.6602 C 26.9687 12.6602 27.1094 12.4961 27.1797 12.2383 C 27.9062 8.3242 27.8594 8.2305 31.9375 7.4570 C 32.2187 7.4102 32.3828 7.2461 32.3828 6.9648 C 32.3828 6.6836 32.2187 6.5195 31.9375 6.4726 C 27.8828 5.6524 28.0000 5.5586 27.1797 1.6914 C 27.1094 1.4336 26.9687 1.2695 26.6875 1.2695 C 26.4062 1.2695 26.2656 1.4336 26.1953 1.6914 C 25.3750 5.5586 25.5156 5.6524 21.4375 6.4726 C 21.1797 6.5195 20.9922 6.6836 20.9922 6.9648 C 20.9922 7.2461 21.1797 7.4102 21.4375 7.4570 C 25.5156 8.2774 25.4687 8.3242 26.1953 12.2383 C 26.2656 12.4961 26.4062 12.6602 26.6875 12.6602 Z M 15.3438 28.7852 C 15.7891 28.7852 16.0938 28.5039 16.1406 28.0821 C 16.9844 21.8242 17.1953 21.8242 23.6641 20.5821 C 24.0860 20.5117 24.3906 20.2305 24.3906 19.7852 C 24.3906 19.3633 24.0860 19.0586 23.6641 18.9883 C 17.1953 18.0977 16.9609 17.8867 16.1406 11.5117 C 16.0938 11.0899 15.7891 10.7852 15.3438 10.7852 C 14.9219 10.7852 14.6172 11.0899 14.5703 11.5352 C 13.7969 17.8164 13.4687 17.7930 7.0469 18.9883 C 6.6250 19.0821 6.3203 19.3633 6.3203 19.7852 C 6.3203 20.2539 6.6250 20.5117 7.1406 20.5821 C 13.5156 21.6133 13.7969 21.7774 14.5703 28.0352 C 14.6172 28.5039 14.9219 28.7852 15.3438 28.7852 Z M 31.2344 54.7305 C 31.8438 54.7305 32.2891 54.2852 32.4062 53.6524 C 34.0703 40.8086 35.8750 38.8633 48.5781 37.4570 C 49.2344 37.3867 49.6797 36.8945 49.6797 36.2852 C 49.6797 35.6758 49.2344 35.2070 48.5781 35.1133 C 35.8750 33.7070 34.0703 31.7617 32.4062 18.9180 C 32.2891 18.2852 31.8438 17.8633 31.2344 17.8633 C 30.6250 17.8633 30.1797 18.2852 30.0860 18.9180 C 28.4219 31.7617 26.5938 33.7070 13.9140 35.1133 C 13.2344 35.2070 12.7891 35.6758 12.7891 36.2852 C 12.7891 36.8945 13.2344 37.3867 13.9140 37.4570 C 26.5703 39.1211 28.3281 40.8321 30.0860 53.6524 C 30.1797 54.2852 30.6250 54.7305 31.2344 54.7305 Z"/>
                                </svg>
                            </div>
                        </span>

                        <span>Skin Concerns</span>

                    </span>
                </a>

            </div>



            <div class="sidebar-section">
                <p class="sidebar-section-title">SKIN MATCH</p>
                <a href="<?= $adminBase ?>/skin-types/skin-types.php" class="sidebar-link <?= sidebarActive('/skin-types/') ?>">
                    <span class="sidebar-link-left">
                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-skin-type-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M512 320C512 214 426 128 320 128L320 512C426 512 512 426 512 320zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320z"/>
                                </svg>
                            </div>
                        </span>
                        <span>Skin Types</span>
                    </span>
                </a>
                <a href="<?= $adminBase ?>/suitability/suitability.php" class="sidebar-link <?= sidebarActive('/suitability/') ?>">
                    <span class="sidebar-link-left">
                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-suitability-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M480 96C515.3 96 544 124.7 544 160L544 480C544 515.3 515.3 544 480 544L160 544C124.7 544 96 515.3 96 480L96 160C96 124.7 124.7 96 160 96L480 96zM160 144C151.2 144 144 151.2 144 160L144 480C144 488.8 151.2 496 160 496L480 496C488.8 496 496 488.8 496 480L496 160C496 151.2 488.8 144 480 144L160 144zM390.7 233.9C398.5 223.2 413.5 220.8 424.2 228.6C434.9 236.4 437.3 251.4 429.5 262.1L307.4 430.1C303.3 435.8 296.9 439.4 289.9 439.9C282.9 440.4 276 437.9 271.1 433L215.2 377.1C205.8 367.7 205.8 352.5 215.2 343.2C224.6 333.9 239.8 333.8 249.1 343.2L285.1 379.2L390.7 234z"/>
                                </svg>
                            </div>
                        </span>
                        <span>Suitability</span>
                    </span>
                </a>
                <a href="<?= $adminBase ?>/assessment-questions/assessment-questions.php" class="sidebar-link <?= sidebarActive('/assessment-questions/') ?>">
                    <span class="sidebar-link-left">
                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-assessment-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M224 224C224 171 267 128 320 128C373 128 416 171 416 224C416 266.7 388.1 302.9 349.5 315.4C321.1 324.6 288 350.7 288 392L288 416C288 433.7 302.3 448 320 448C337.7 448 352 433.7 352 416L352 392C352 390.3 352.6 387.9 355.5 384.7C358.5 381.4 363.4 378.2 369.2 376.3C433.5 355.6 480 295.3 480 224C480 135.6 408.4 64 320 64C231.6 64 160 135.6 160 224C160 241.7 174.3 256 192 256C209.7 256 224 241.7 224 224zM320 576C342.1 576 360 558.1 360 536C360 513.9 342.1 496 320 496C297.9 496 280 513.9 280 536C280 558.1 297.9 576 320 576z"/>
                                </svg>
                            </div>
                        </span>
                        <span>Assessment</span>
                    </span>
                </a>
                <a href="<?= $adminBase ?>/quiz-analytics/quiz-analytics.php" class="sidebar-link <?= sidebarActive('/quiz-analytics/') ?>">
                    <span class="sidebar-link-left">
                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-quiz-analytics-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M160 96C124.7 96 96 124.7 96 160L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 160C544 124.7 515.3 96 480 96L160 96zM216 288C229.3 288 240 298.7 240 312L240 424C240 437.3 229.3 448 216 448C202.7 448 192 437.3 192 424L192 312C192 298.7 202.7 288 216 288zM400 376C400 362.7 410.7 352 424 352C437.3 352 448 362.7 448 376L448 424C448 437.3 437.3 448 424 448C410.7 448 400 437.3 400 424L400 376zM320 192C333.3 192 344 202.7 344 216L344 424C344 437.3 333.3 448 320 448C306.7 448 296 437.3 296 424L296 216C296 202.7 306.7 192 320 192z"/>
                                </svg>
                            </div>
                        </span>
                        <span>Quiz Analytics</span>
                    </span>
                </a>
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

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" width="18" height="18" fill="currentColor" focusable="false">
                                    <g>
                                        <polygon points="2.75,3.815918 8.190918,1.095459 6,0 0.559082,2.720459"/>
                                        <polygon points="9.309082,1.654541 3.8681641,4.375 6,5.440918 11.440918,2.720459"/>
                                        <polygon points="5.5,6.309082 0,3.559082 0,9.25 5.5,12"/>
                                        <polygon points="6.5,6.309082 6.5,12 12,9.25 12,3.559082"/>
                                    </g>
                                </svg>
                            </div>
                        </span>

                        <span>Inventory</span>

                    </span>
                </a>


                <a
                    href="<?= $ordersUrl ?>"
                    class="sidebar-link <?= sidebarActive('/orders/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M24 48C10.7 48 0 58.7 0 72C0 85.3 10.7 96 24 96L69.3 96C73.2 96 76.5 98.8 77.2 102.6L129.3 388.9C135.5 423.1 165.3 448 200.1 448L456 448C469.3 448 480 437.3 480 424C480 410.7 469.3 400 456 400L200.1 400C188.5 400 178.6 391.7 176.5 380.3L171.4 352L475 352C505.8 352 532.2 330.1 537.9 299.8L568.9 133.9C572.6 114.2 557.5 96 537.4 96L124.7 96L124.3 94C119.5 67.4 96.3 48 69.2 48L24 48zM208 576C234.5 576 256 554.5 256 528C256 501.5 234.5 480 208 480C181.5 480 160 501.5 160 528C160 554.5 181.5 576 208 576zM432 576C458.5 576 480 554.5 480 528C480 501.5 458.5 480 432 480C405.5 480 384 501.5 384 528C384 554.5 405.5 576 432 576z"/>
                                </svg>
                            </div>
                        </span>

                        <span>Orders</span>

                    </span>
                </a>


                <a href="<?= $adminBase ?>/returns/returns.php" class="sidebar-link <?= sidebarActive('/returns/') ?>"><span class="sidebar-link-left"><span class="sidebar-menu-icon" aria-hidden="true">&#8617;</span><span>Returns</span></span></a>
                <a
                    href="<?= $paymentsUrl ?>"
                    class="sidebar-link <?= sidebarActive('/payments/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-payment-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M64 192L64 224L576 224L576 192C576 156.7 547.3 128 512 128L128 128C92.7 128 64 156.7 64 192zM64 272L64 448C64 483.3 92.7 512 128 512L512 512C547.3 512 576 483.3 576 448L576 272L64 272zM128 424C128 410.7 138.7 400 152 400L200 400C213.3 400 224 410.7 224 424C224 437.3 213.3 448 200 448L152 448C138.7 448 128 437.3 128 424zM272 424C272 410.7 282.7 400 296 400L360 400C373.3 400 384 410.7 384 424C384 437.3 373.3 448 360 448L296 448C282.7 448 272 437.3 272 424z"/>
                                </svg>
                            </div>
                        </span>

                        <span>Payments</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/reports/sales_report.php"
                    class="sidebar-link <?= sidebarActive('/reports/') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-report-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M416 224C398.3 224 384 209.7 384 192C384 174.3 398.3 160 416 160L576 160C593.7 160 608 174.3 608 192L608 352C608 369.7 593.7 384 576 384C558.3 384 544 369.7 544 352L544 269.3L374.6 438.7C362.1 451.2 341.8 451.2 329.3 438.7L224 333.3L86.6 470.6C74.1 483.1 53.8 483.1 41.3 470.6C28.8 458.1 28.8 437.8 41.3 425.3L201.3 265.3C213.8 252.8 234.1 252.8 246.6 265.3L352 370.7L498.7 224L416 224z"/>
                                </svg>
                            </div>
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

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-security-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M256 160L256 224L384 224L384 160C384 124.7 355.3 96 320 96C284.7 96 256 124.7 256 160zM192 224L192 160C192 89.3 249.3 32 320 32C390.7 32 448 89.3 448 160L448 224C483.3 224 512 252.7 512 288L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 288C128 252.7 156.7 224 192 224z"/>
                                </svg>
                            </div>
                        </span>

                        <span>Security Settings</span>

                    </span>
                </a>


                <a href="<?= $adminBase ?>/security/account_lockouts.php"
                   class="sidebar-link <?= sidebarActive('/security/account_lockouts.php') ?>">
                    <span class="sidebar-link-left"><span class="sidebar-menu-icon" aria-hidden="true">&#8856;</span><span>Account Lockout Logs</span></span>
                </a>
                <a
                    href="<?= $adminBase ?>/security/login_attempts.php"
                    class="sidebar-link <?= sidebarActive('/security/login_attempts.php') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-login-attempts-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M400 416C497.2 416 576 337.2 576 240C576 142.8 497.2 64 400 64C302.8 64 224 142.8 224 240C224 258.7 226.9 276.8 232.3 293.7L71 455C66.5 459.5 64 465.6 64 472L64 552C64 565.3 74.7 576 88 576L168 576C181.3 576 192 565.3 192 552L192 512L232 512C245.3 512 256 501.3 256 488L256 448L296 448C302.4 448 308.5 445.5 313 441L346.3 407.7C363.2 413.1 381.3 416 400 416zM440 160C462.1 160 480 177.9 480 200C480 222.1 462.1 240 440 240C417.9 240 400 222.1 400 200C400 177.9 417.9 160 440 160z"/>
                                </svg>
                            </div>
                        </span>

                        <span>Login Attempts</span>

                    </span>
                </a>


                <a
                    href="<?= $adminBase ?>/security/audit_logs.php"
                    class="sidebar-link <?= sidebarActive('/security/audit_logs.php') ?>"
                >
                    <span class="sidebar-link-left">

                        <span class="sidebar-menu-icon" aria-hidden="true">
                            <div class="sidebar-svg-icon sidebar-audit-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="18" height="18" fill="currentColor" focusable="false">
                                    <path d="M448 96L439.4 96C428.4 76.9 407.7 64 384 64L256 64C232.3 64 211.6 76.9 200.6 96L192 96C156.7 96 128 124.7 128 160L128 512C128 547.3 156.7 576 192 576L448 576C483.3 576 512 547.3 512 512L512 160C512 124.7 483.3 96 448 96zM264 176C250.7 176 240 165.3 240 152C240 138.7 250.7 128 264 128L376 128C389.3 128 400 138.7 400 152C400 165.3 389.3 176 376 176L264 176z"/>
                                </svg>
                            </div>
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

                <?= pvHtml($sidebarInitial) ?>

            </div>



            <!-- DATABASE USER INFORMATION -->

            <div class="sidebar-user-information">


                <p class="sidebar-user-name">

                    <?= pvHtml($sidebarFullName) ?>

                </p>


                <p class="sidebar-user-email">

                    <?= pvHtml($sidebarEmail) ?>

                </p>


                <span
                    class="sidebar-role-badge
                    <?= $isAdmin
                        ? 'sidebar-role-admin'
                        : 'sidebar-role-staff' ?>"
                >

                    <?= pvHtml(
                        ucfirst($currentRole)
                    ) ?>

                </span>


            </div>


            <?php if ($isAdmin || $isStaff): ?>
                <a href="<?= BASE_URL ?>/account/manage.php" class="sidebar-profile-edit" aria-label="Manage Account" title="Manage Account">
                    <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 5H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7"/><path d="m16 3 5 5M9 15l1-5L18 2a2.1 2.1 0 0 1 3 3l-8 8-4 2Z"/></svg>
                </a>
            <?php endif; ?>
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
