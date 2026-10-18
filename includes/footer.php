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

            <a href="<?= BASE_URL ?>/about.php">
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

            <a href="<?= BASE_URL ?>/about.php">
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

            <a href="<?= BASE_URL ?>/privacy-policy.php">
                Privacy Policy
            </a>

            <a href="<?= BASE_URL ?>/terms.php">
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

            <a href="<?= BASE_URL ?>/privacy-policy.php">
                Privacy
            </a>

            <span>·</span>

            <a href="<?= BASE_URL ?>/terms.php">
                Terms
            </a>

            <span>·</span>

            <button
                type="button"
                class="cookie-button"
            >
                Cookies
            </button>

            <span>·</span>

            <span>Philippines</span>

        </div>

    </div>

</footer>