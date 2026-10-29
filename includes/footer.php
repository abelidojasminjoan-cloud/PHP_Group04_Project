<footer class="footer">

    <!-- =====================================
         TOP
    ====================================== -->

    <div class="footer-top">

        <!-- LOGO / BRAND -->

        <div class="footer-logo">

            <div class="footer-brand-name">

                <i class="fa-solid fa-leaf"></i>

                <h2>PUREVIA</h2>

            </div>

            <p>
                Gentle, effective skincare made simple.
                Clear ingredients, recommended skin types,
                and easy guidance for every routine.
            </p>

        </div>


        <!-- =====================================
             GUEST MENU
        ====================================== -->

        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] == 'guest'): ?>

            <div class="footer-menu">

                <a href="<?= BASE_URL ?>/public/login.php">
                    Login
                </a>

                <a href="<?= BASE_URL ?>/public/register.php">
                    Register
                </a>

            </div>

        <?php endif; ?>

    </div>


    <!-- =====================================
         FOOTER GRID
    ====================================== -->

    <div class="footer-grid">


        <!-- =================================
             COLUMN 1 - SHOP
        ================================== -->

        <div class="footer-column">

            <h3>SHOP</h3>

            <a href="<?= BASE_URL ?>/public/shop.php">
                All Products
            </a>

            <a href="<?= BASE_URL ?>/public/shop.php?category=cleansers">
                Cleansers
            </a>

            <a href="<?= BASE_URL ?>/public/shop.php?category=toners">
                Toners
            </a>

            <a href="<?= BASE_URL ?>/public/shop.php?category=serums">
                Serums
            </a>

            <a href="<?= BASE_URL ?>/public/shop.php?category=moisturizers">
                Moisturizers
            </a>

            <a href="<?= BASE_URL ?>/public/shop.php?category=sunscreens">
                Sunscreens
            </a>

        </div>


        <!-- =================================
             COLUMN 2 - DISCOVER
        ================================== -->

        <div class="footer-column">

            <h3>DISCOVER</h3>

            <a href="<?= BASE_URL ?>/skin-type-finder.php">
                Skin Type Finder
            </a>

            <a href="<?= BASE_URL ?>/skin-match.php">
                Skin Matching
            </a>

            <?php if (
                isset($_SESSION['role']) &&
                $_SESSION['role'] == 'customer'
            ): ?>

                <a href="<?= BASE_URL ?>/account/skin-profile.php">
                    My Skin Profile
                </a>

            <?php endif; ?>

            <a href="<?= BASE_URL ?>/public/shop.php">
                Product Ingredients
            </a>

            
            <a href="<?= BASE_URL ?>/public/about_us.php">
                About PureVia
            </a>


        </div>


        <!-- =================================
             COLUMN 3 - QUICK LINKS
        ================================== -->

        <div class="footer-column">

            <h3>QUICK LINKS</h3>


            <!-- ADMIN -->

            <?php if (
                isset($_SESSION['role']) &&
                $_SESSION['role'] == 'admin'
            ): ?>

                <a href="<?= BASE_URL ?>/admin/dashboard.php">
                    Dashboard
                </a>

                <a href="<?= BASE_URL ?>/admin/products/index.php">
                    Products
                </a>

                <a href="<?= BASE_URL ?>/admin/categories/index.php">
                    Categories
                </a>

                <a href="<?= BASE_URL ?>/admin/orders/index.php">
                    Orders
                </a>

                <a href="<?= BASE_URL ?>/admin/payments/index.php">
                    Payments
                </a>


            <!-- STAFF -->

            <?php elseif (
                isset($_SESSION['role']) &&
                $_SESSION['role'] == 'staff'
            ): ?>

                <a href="<?= BASE_URL ?>/staff/dashboard.php">
                    Dashboard
                </a>

                <a href="<?= BASE_URL ?>/staff/products/index.php">
                    Products
                </a>

                <a href="<?= BASE_URL ?>/staff/inventory/index.php">
                    Inventory
                </a>

                <a href="<?= BASE_URL ?>/staff/orders/index.php">
                    Orders
                </a>


            <!-- CUSTOMER -->

            <?php elseif (
                isset($_SESSION['role']) &&
                $_SESSION['role'] == 'customer'
            ): ?>

                <a href="<?= BASE_URL ?>/public/index.php">
                    Home
                </a>

                <a href="<?= BASE_URL ?>/public/shop.php">
                    Shop
                </a>

                <a href="<?= BASE_URL ?>/account/profile.php">
                    My Account
                </a>

                <a href="<?= BASE_URL ?>/public/my-orders.php">
                    My Orders
                </a>

                <a href="<?= BASE_URL ?>/public/cart.php">
                    My Bag
                </a>


            <!-- GUEST -->

            <?php else: ?>

                <a href="<?= BASE_URL ?>/public/index.php">
                    Home
                </a>

                <a href="<?= BASE_URL ?>/public/shop.php">
                    Shop
                </a>

                <a href="<?= BASE_URL ?>/public/login.php">
                    Login
                </a>

                <a href="<?= BASE_URL ?>/public/register.php">
                    Register
                </a>

            <?php endif; ?>

        </div>


        <!-- =================================
             COLUMN 4 - SUPPORT
        ================================== -->

         <div class="footer-column">

         <h3>SUPPORT</h3>

        <a href="<?= BASE_URL ?>/public/about_us.php">
            About Us
        </a>

        <a href="<?= BASE_URL ?>/contact.php">
            Contact
        </a>

        <a href="<?= BASE_URL ?>/faq.php">
            FAQs
        </a>

        <a href="<?= BASE_URL ?>/shipping.php">
            Shipping & Returns
        </a>

        <a href="<?= BASE_URL ?>/public/privacy_policy.php">
            Privacy Policy
        </a>

        <a href="<?= BASE_URL ?>/public/terms.php">
            Terms & Conditions
        </a>

    </div>
</div>


    <!-- =====================================
         SOCIALS
    ====================================== -->

         <div class="footer-socials">

            <a href="#">
                Instagram
            </a>

            <a href="#">
                Pinterest
            </a>

            <a href="#">
                TikTok
            </a>

        </div>


    <!-- =====================================
         COPYRIGHT / LEGAL
    ====================================== -->

    <div class="footer-bottom">

        <p>
            &copy; <?= date('Y') ?> PureVia.
            All rights reserved.
        </p>


        <div class="footer-legal">

            <a href="<?= BASE_URL ?>/public/privacy_policy.php">
                Privacy
            </a>

            <span>·</span>

            <a href="<?= BASE_URL ?>/public/terms.php">
                Terms
            </a>

            <span>·</span>

            <button
                type="button"
                class="cookie-button"
                id="pvOpenCookieSettings"
                aria-haspopup="dialog"
                aria-controls="pvCookiePreferences"
            >
                Cookie Settings
            </button>

            <span>·</span>

            <span>Philippines</span>

        </div>

    </div>

</footer>


<!-- ==========================================
     PUREVIA COOKIE CONSENT
========================================== -->

<link
    rel="stylesheet"
    href="<?= BASE_URL ?>/assets/css/cookie-consent.css"
>

<!-- FIRST VISIT COOKIE BANNER -->

<section
    class="pv-cookie-banner"
    id="pvCookieBanner"
    aria-labelledby="pvCookieTitle"
    aria-describedby="pvCookieDescription"
    hidden
>
    <div class="pv-cookie-banner-inner">

        <div class="pv-cookie-icon" aria-hidden="true">
            <i class="fa-solid fa-cookie-bite"></i>
        </div>

        <div class="pv-cookie-content">

            <p class="pv-cookie-eyebrow">
                YOUR PRIVACY, YOUR CHOICE
            </p>

            <h2 id="pvCookieTitle">
                A little care for your privacy.
            </h2>

            <p id="pvCookieDescription">
                PureVia uses essential cookies for account
                security, shopping, and website functionality.
                You can choose whether to allow optional
                analytics cookies if these are introduced.
            </p>

        </div>

        <div class="pv-cookie-actions">

            <button
                type="button"
                class="pv-cookie-btn pv-cookie-accept"
                id="pvCookieAcceptAll"
            >
                Accept All
            </button>

            <button
                type="button"
                class="pv-cookie-btn pv-cookie-reject"
                id="pvCookieReject"
            >
                Reject Optional
            </button>

            <button
                type="button"
                class="pv-cookie-manage"
                id="pvCookieManage"
            >
                Manage Preferences
            </button>

        </div>

    </div>
</section>


<!-- ==========================================
     COOKIE PREFERENCES DIALOG
========================================== -->

<div
    class="pv-cookie-overlay"
    id="pvCookieOverlay"
    hidden
>
    <section
        class="pv-cookie-modal"
        id="pvCookiePreferences"
        role="dialog"
        aria-modal="true"
        aria-labelledby="pvCookieModalTitle"
        aria-describedby="pvCookieModalDescription"
        tabindex="-1"
    >

        <div class="pv-cookie-modal-header">

            <div>
                <p class="pv-cookie-eyebrow">
                    PUREVIA · COOKIE SETTINGS
                </p>

                <h2 id="pvCookieModalTitle">
                    Your Cookie Preferences
                </h2>
            </div>

            <button
                type="button"
                class="pv-cookie-close"
                id="pvCookieClose"
                aria-label="Close cookie settings"
            >
                &times;
            </button>

        </div>

        <p
            class="pv-cookie-modal-description"
            id="pvCookieModalDescription"
        >
            Manage how PureVia uses cookies.
            Essential cookies are always enabled
            because they support necessary website
            functionality and security.
        </p>

        <!-- ESSENTIAL COOKIES -->

        <div class="pv-cookie-option">

            <div class="pv-cookie-option-copy">

                <h3>Essential Cookies</h3>

                <p>
                    Required for login sessions, account
                    security, shopping, and remembering
                    your cookie choices.
                </p>

            </div>

            <span class="pv-cookie-required">
                Always Active
            </span>

        </div>

        <!-- OPTIONAL ANALYTICS -->

        <div class="pv-cookie-option">

            <div class="pv-cookie-option-copy">

                <h3>Analytics Cookies</h3>

                <p>
                    Optional website analytics are not
                    currently active. These cookies will
                    remain off until the feature is
                    implemented and enabled.
                </p>

            </div>

            <label class="pv-cookie-switch">

                <input
                    type="checkbox"
                    id="pvCookieAnalytics"
                    aria-label="Allow analytics cookies"
                    disabled
                >

                <span class="pv-cookie-slider"></span>

            </label>

        </div>

        <p class="pv-cookie-modal-note">
            Your choice can be changed any time using
            Cookie Settings in the footer.
        </p>

        <div class="pv-cookie-modal-actions">

            <button
                type="button"
                class="pv-cookie-btn pv-cookie-reject"
                id="pvCookieSave"
            >
                Save Preferences
            </button>

            <button
                type="button"
                class="pv-cookie-btn pv-cookie-accept"
                id="pvCookieModalAccept"
            >
                Accept All
            </button>

        </div>

    </section>
</div>


<!-- COOKIE CONSENT JAVASCRIPT -->

<script
    nonce="<?= pvScriptNonce() ?>"
    src="<?= BASE_URL ?>/assets/js/cookie-consent.js"
    defer
></script>
