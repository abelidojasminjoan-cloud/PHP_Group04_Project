<?php
/* =====================================================
   PUREVIA HEADER
===================================================== */

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');

$role = $_SESSION['role'] ?? 'guest';
$userName = $_SESSION['name'] ?? 'Guest';

$userInitial = '';

if ($role !== 'guest' && $userName !== '') {
    $userInitial = strtoupper(substr($userName, 0, 1));
}

/*
    cart count.
*/
$cartCount = 0;
$headerUserId = (int) ($_SESSION['user_id'] ?? 0);

if ($headerUserId > 0 && isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0) AS total
        FROM carts c
        INNER JOIN cart_items ci ON ci.cart_id = c.id
        WHERE c.user_id = ?
    ");

    $stmt->bind_param('i', $headerUserId);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();
    $cartCount = (int) ($result['total'] ?? 0);

    $stmt->close();
}

?>

<script nonce="<?= pvScriptNonce() ?>">window.PUREVIA_BASE_URL = <?= pvJson(BASE_URL) ?>;</script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/image-fallbacks.js"></script>
<header class="site-header" id="pureviaHeader">

    <!-- ==========================================
         ANNOUNCEMENT BAR
         ALWAYS VISIBLE
    =========================================== -->

    <div class="announcement-bar">
        <p>
            COMPLIMENTARY SHIPPING ON ORDERS OVER ₱75+
            YOUR CLEAR PATH TO BETTER SKIN
        </p>
    </div>

    <!-- ==========================================
         NAVIGATION AREA
         SEARCH OVERLAYS THIS AREA ONLY
    =========================================== -->

    <div class="pv-header-navigation">

        <div class="navbar">

            <!-- LEFT NAVIGATION -->

            <div class="navbar-left">

                
                <a
                    href="<?= BASE_URL ?>/public/index.php"
                    class="brand"
                    aria-label="PureVia Home"
                >
                    <img
                        src="<?= BASE_URL ?>/assets/images/logo/purevia_logo_1.png"
                        alt="PureVia"
                        class="brand-logo"
                        width="180"
                        height="70"
                    >
                </a>


                <nav class="main-nav">

                    <a
                        href="<?= BASE_URL ?>/public/shop.php"
                        class="<?= $currentPage === 'shop.php' ? 'active' : '' ?>"
                    >
                        SHOP
                    </a>

                    <a
                        href="<?= BASE_URL ?>/skin-match.php"
                        class="<?= $currentPage === 'skin-match.php' ? 'active' : '' ?>"
                    >
                        SKIN MATCH
                    </a>

                </nav>

            </div>

            <!-- RIGHT NAVIGATION -->

            <div class="navbar-right">

                <!-- SEARCH ICON -->

                <button
                    type="button"
                    class="nav-icon pv-search-trigger"
                    id="pvSearchTrigger"
                    aria-label="Open search"
                    aria-expanded="false"
                    aria-controls="pvSearchPanel"
                >
                    <div class="pv-search-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg" focusable="false">
                            <path d="M14.9536 14.9458L21 21M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z"
                                  stroke="currentColor" stroke-width="2"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </button>

                <!-- MY FAVORITES -->
                <a href="<?= BASE_URL ?>/public/favorites.php" class="nav-icon pv-favorites-link"
                   data-favorite-scope="<?= pvHtml(BASE_URL . ':' . $headerUserId, ENT_QUOTES, 'UTF-8') ?>"
                   aria-label="My Favorites">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                    <span class="pv-favorites-count" hidden></span>
                </a>

                <!-- ACCOUNT -->

                <?php if ($role === 'customer'): ?>

                    <div class="account-dropdown">

                        <button
                            type="button"
                            class="account-link account-dropdown-trigger"
                            id="accountDropdownTrigger"
                            aria-expanded="false"
                            aria-controls="accountDropdownMenu"
                        >
                            <span class="account-avatar">
                                <?= pvHtml($userInitial, ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <span class="account-name">
                                <?= pvHtml($userName, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </button>

                        <div
                            class="account-dropdown-menu"
                            id="accountDropdownMenu"
                            aria-hidden="true"
                        >

                            <a
                                href="<?= BASE_URL ?>/account/profile.php"
                                class="account-dropdown-item"
                            >
                                <i class="fa-regular fa-user"></i>
                                <span>My Account</span>
                            </a>

                            <a
                                href="<?= BASE_URL ?>/public/my-orders.php"
                                class="account-dropdown-item"
                            >
                                <i class="fa-solid fa-box"></i>
                                <span>My Orders</span>
                            </a>

                            <div class="account-dropdown-divider"></div>

                            <a
                                href="<?= BASE_URL ?>/actions/auth/logout.php"
                                class="account-dropdown-item account-dropdown-logout"
                            >
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                <span>Sign Out</span>
                            </a>

                        </div>

                    </div>

                <?php elseif ($role === 'admin'): ?>

                    <a
                        href="<?= BASE_URL ?>/admin/dashboard.php"
                        class="account-link"
                    >
                        <span class="account-avatar">
                            <?= pvHtml($userInitial, ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <span class="account-name">Admin</span>
                    </a>

                <?php elseif ($role === 'staff'): ?>

                    <a
                        href="<?= BASE_URL ?>/staff/dashboard.php"
                        class="account-link"
                    >
                        <span class="account-avatar">
                            <?= pvHtml($userInitial, ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <span class="account-name">Staff</span>
                    </a>

                <?php else: ?>

                    <a
                        href="<?= BASE_URL ?>/public/login.php"
                        class="nav-icon guest-account-icon"
                        aria-label="Sign in"
                    >
                        <div class="pv-person-icon" aria-hidden="true">
                            <svg
                                width="20"
                                height="20"
                                viewBox="0 0 64 64"
                                xmlns="http://www.w3.org/2000/svg"
                                stroke-width="3"
                                stroke="currentColor"
                                fill="none"
                            >
                                <circle cx="32" cy="18.14" r="11.14"/>
                                <path d="M54.55,56.85A22.55,22.55,0,0,0,32,34.3h0A22.55,22.55,0,0,0,9.45,56.85Z"/>
                            </svg>
                        </div>
                    </a>


                <?php endif; ?>

                <!-- SHOPPING BAG -->

                <a
                    href="<?= BASE_URL ?>/public/cart.php"
                    class="bag-link"
                    aria-label="Shopping Bag, <?= (int) $cartCount ?> <?= $cartCount === 1 ? 'item' : 'items' ?>"
                >
                    <div class="pv-bag-icon" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="feather feather-shopping-bag" focusable="false">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                    </div>

                    <span class="bag-count" aria-hidden="true" <?= $cartCount === 0 ? 'hidden' : '' ?>><?= $cartCount > 99 ? '99+' : (int) $cartCount ?></span>
                </a>

            </div>

        </div>

        <!-- ==========================================
             SEARCH PANEL
             OVERLAYS NAVIGATION ONLY
        =========================================== -->

        <section
            class="pv-search-panel"
            id="pvSearchPanel"
            role="search"
            aria-label="Search PureVia products"
            hidden
        >

            <div class="pv-search-inner">

                <div class="pv-search-bar-row">

                    <div class="pv-search-widget">

                        <!-- SEARCH FORM -->

                        <form
                            class="pv-search-form"
                            id="pvSearchForm"
                            role="search"
                            autocomplete="off"
                        >

                            <div class="pv-search-field">

                                <label for="pvSearchInput">
                                    Search
                                </label>

                                <input
                                    type="search"
                                    id="pvSearchInput"
                                    name="q"
                                    placeholder=""
                                    aria-label="Search skincare products"
                                    aria-autocomplete="list"
                                    aria-controls="pvSearchDropdown"
                                    aria-expanded="false"
                                >

                            </div>

                            <!-- CLEAR SEARCH -->

                            <button
                                type="button"
                                class="pv-search-clear"
                                id="pvSearchClear"
                                aria-label="Clear search"
                                hidden
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>

                            <!-- SEARCH SUBMIT -->

                            <button
                                type="submit"
                                class="pv-search-submit"
                                aria-label="Submit search"
                            >
                                <div class="pv-search-icon" aria-hidden="true">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                         xmlns="http://www.w3.org/2000/svg" focusable="false">
                                        <path d="M14.9536 14.9458L21 21M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z"
                                              stroke="currentColor" stroke-width="2"
                                              stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </button>

                        </form>

                        <!-- ==================================
                             LIVE SEARCH DROPDOWN
                        =================================== -->

                        <div
                            class="pv-search-dropdown"
                            id="pvSearchDropdown"
                            hidden
                        >

                            <div class="pv-search-columns">

                                <!-- SUGGESTIONS -->

                                <div class="pv-search-suggestions">

                                    <h3>SUGGESTIONS</h3>

                                    <div id="pvSearchSuggestions"></div>

                                </div>

                                <!-- PRODUCTS -->

                                <div class="pv-search-products">

                                    <h3>PRODUCTS</h3>

                                    <div id="pvSearchProducts"></div>

                                </div>

                            </div>

                            <!-- VIEW ALL RESULTS -->

                            <a
                                href="<?= BASE_URL ?>/public/shop.php"
                                class="pv-search-view-all"
                                id="pvSearchViewAll"
                            >

                                <span id="pvSearchViewAllText">
                                    View all results
                                </span>

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>

                        </div>

                    </div>

                    <!-- CLOSE SEARCH -->

                    <button
                        type="button"
                        class="pv-search-close"
                        id="pvSearchClose"
                        aria-label="Close search"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>

            </div>

        </section>

    </div>

</header>

<!-- ==========================================
     DARK BACKGROUND OVERLAY
=========================================== -->

<div
    class="pv-search-overlay"
    id="pvSearchOverlay"
    hidden
></div>

<?php require_once __DIR__ . '/session_timeout.php'; ?>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/product-favorites.js?v=<?= filemtime(__DIR__ . '/../assets/js/product-favorites.js') ?>" defer></script>
