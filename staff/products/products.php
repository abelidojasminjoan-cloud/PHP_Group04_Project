<?php

session_start();

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

$pageTitle = 'Products';


/* =========================================================
   REQUIRE LOGIN
========================================================= */

if (empty($_SESSION['user_id'])) {

    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];


/* =========================================================
   VERIFY STAFF ACCOUNT
========================================================= */

$stmt = $conn->prepare("
    SELECT
        u.id,
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

    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}


/* =========================================================
   LOAD PRODUCTS FROM DATABASE
========================================================= */

$products = [];


/*
    Uses the same fields already used by your dashboard:

    products.id
    products.product_name
    products.product_code
    products.category_id
    products.price
    products.stock_quantity
    products.status
    products.image

    categories.id
    categories.category_name
*/

$sql = "
    SELECT
        p.id,
        p.product_name,
        p.product_code,
        p.price,
        p.stock_quantity,
        p.status,
        p.image,

        c.category_name

    FROM products AS p

    LEFT JOIN categories AS c
        ON c.id = p.category_id

    ORDER BY
        p.product_name ASC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $row['variants'] = [];

        $products[$row['id']] = $row;
    }
}


/* =========================================================
   LOAD PRODUCT VARIANTS / SIZES

   If your product_variants table stores variant_name
   such as 15ML, 30ML, 100ML, etc.
========================================================= */

if (!empty($products)) {

    $variantResult = $conn->query("
        SELECT
            product_id,
            variant_name
        FROM product_variants
        ORDER BY product_id ASC, id ASC
    ");

    if ($variantResult) {

        while ($variant = $variantResult->fetch_assoc()) {

            $productId = (int) $variant['product_id'];

            if (isset($products[$productId])) {

                $variantName = trim(
                    (string) $variant['variant_name']
                );

                if (
                    $variantName !== '' &&
                    !in_array(
                        $variantName,
                        $products[$productId]['variants'],
                        true
                    )
                ) {

                    $products[$productId]['variants'][] =
                        $variantName;
                }
            }
        }
    }
}


/* =========================================================
   RESET ARRAY KEYS
========================================================= */

$products = array_values($products);

$totalProducts = count($products);


/* =========================================================
   GET CATEGORIES FOR FILTER
========================================================= */

$categories = [];

$categoryResult = $conn->query("
    SELECT
        id,
        category_name
    FROM categories
    ORDER BY category_name ASC
");

if ($categoryResult) {

    while ($category = $categoryResult->fetch_assoc()) {

        $categories[] = $category;
    }
}


/* =========================================================
   IMAGE HELPER
========================================================= */

function staffProductImage(?string $image): string
{
    if (empty($image)) {

        return BASE_URL . '/assets/images/product-placeholder.png';
    }

    /*
       Already a full URL.
    */

    if (
        str_starts_with($image, 'http://') ||
        str_starts_with($image, 'https://')
    ) {

        return $image;
    }


    /*
       Already starts with project BASE_URL.
    */

    if (
        defined('BASE_URL') &&
        str_starts_with($image, BASE_URL)
    ) {

        return $image;
    }


    /*
       Already starts from assets/
    */

    if (str_starts_with($image, 'assets/')) {

        return BASE_URL . '/' . $image;
    }


    /*
       Starts with /assets/
    */

    if (str_starts_with($image, '/assets/')) {

        return BASE_URL . $image;
    }


    /*
       Assume database only contains filename.
       Example:
       cleanser.jpg
    */

    return BASE_URL .
        '/assets/images/products/' .
        ltrim($image, '/');
}

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
        Products | PureVia
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


    <!-- SAME SIDEMENU CSS -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/sidemenu.css"
    >


    <!-- SAME PRODUCT CSS USED BY ADMIN -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/product.css"
    >

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>


<body>


    <!-- =====================================================
         SHARED STAFF SIDEBAR
    ====================================================== -->

    <?php include __DIR__ . '/../../includes/sidemenu.php'; ?>


    <!-- =====================================================
         SAME LAYOUT CLASS SO product.css CAN BE REUSED
    ====================================================== -->

    <div class="admin-layout">


        <!-- =================================================
             TOP BAR
        ================================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Products
            </p>

        </header>


        <!-- =================================================
             MAIN
        ================================================== -->

        <main class="products-main">


            <!-- =================================================
                 PAGE HEADER
            ================================================== -->

            <section class="products-page-header">


                <div class="products-heading">

                    <h1>
                        Products
                    </h1>

                    <p id="productRecordCount">
                        <?= number_format($totalProducts) ?>
                        <?= $totalProducts === 1 ? 'record' : 'records' ?>
                    </p>

                </div>


                <!--
                    NO ADD PRODUCT BUTTON FOR STAFF
                -->


            </section>


            <!-- =================================================
                 PRODUCTS CARD
            ================================================== -->

            <section class="products-card">


                <!-- =================================================
                     SEARCH + FILTERS
                ================================================== -->

                <div class="products-toolbar">


                    <!-- SEARCH -->

                    <div class="products-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="productSearch"
                            placeholder="Search products..."
                            autocomplete="off"
                            aria-label="Search products"
                        >

                    </div>


                    <!-- CATEGORY FILTER -->

                    <div class="product-filter-dropdown">

                        <i
                            class="fa-solid fa-filter product-filter-icon"
                        ></i>

                        <select
                            id="categoryFilter"
                            class="product-filter-select category-filter-select"
                            aria-label="Filter products by category"
                        >

                            <option value="all">
                                All Categories
                            </option>


                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        strtolower(
                                            $category['category_name']
                                        )
                                    ) ?>"
                                >
                                    <?= htmlspecialchars(
                                        $category['category_name']
                                    ) ?>
                                </option>

                            <?php endforeach; ?>


                        </select>

                        <i
                            class="fa-solid fa-chevron-down product-filter-arrow"
                        ></i>

                    </div>


                    <!-- STATUS FILTER -->

                    <div
                        class="product-filter-dropdown status-filter-dropdown"
                    >

                        <select
                            id="statusFilter"
                            class="product-filter-select status-filter-select"
                            aria-label="Filter products by status"
                        >

                            <option value="all">
                                All Status
                            </option>

                            <option value="active">
                                Active
                            </option>

                            <option value="inactive">
                                Inactive
                            </option>

                        </select>

                        <i
                            class="fa-solid fa-chevron-down product-filter-arrow"
                        ></i>

                    </div>


                </div>


                <!-- =================================================
                     PRODUCTS TABLE
                ================================================== -->

                <div class="products-table-wrapper">

                    <table class="products-table">


                        <thead>

                            <tr>

                                <th>PRODUCT</th>

                                <th>SKU</th>

                                <th>CATEGORY</th>

                                <th>VARIANTS</th>

                                <th>STOCK</th>

                                <th>PRICE</th>

                                <th>STATUS</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (!empty($products)): ?>


                                <?php foreach ($products as $product): ?>


                                    <?php

                                    $status = strtolower(
                                        trim(
                                            (string) $product['status']
                                        )
                                    );


                                    $categoryName =
                                        $product['category_name']
                                        ?? 'Uncategorized';


                                    $category = strtolower(
                                        trim($categoryName)
                                    );


                                    $searchData = strtolower(
                                        trim(
                                            $product['product_name']
                                            . ' '
                                            . $product['product_code']
                                            . ' '
                                            . $categoryName
                                        )
                                    );


                                    $stock = (int)
                                        $product['stock_quantity'];


                                    $imageUrl = staffProductImage(
                                        $product['image'] ?? null
                                    );

                                    ?>


                                    <tr
                                        class="product-row"

                                        data-category="<?= htmlspecialchars(
                                            $category
                                        ) ?>"

                                        data-status="<?= htmlspecialchars(
                                            $status
                                        ) ?>"

                                        data-search="<?= htmlspecialchars(
                                            $searchData
                                        ) ?>"
                                    >


                                        <!-- =========================
                                             PRODUCT
                                        ========================== -->

                                        <td>

                                            <div class="product-information">


                                                <div class="product-image">

                                                    <img
                                                        src="<?= htmlspecialchars(
                                                            $imageUrl
                                                        ) ?>"
                                                        alt="<?= htmlspecialchars(
                                                            $product['product_name']
                                                        ) ?>"
                                                        loading="lazy"
                                                        onerror="this.style.display='none';"
                                                    >

                                                </div>


                                                <p class="product-name">

                                                    <?= htmlspecialchars(
                                                        $product['product_name']
                                                    ) ?>

                                                </p>


                                            </div>

                                        </td>


                                        <!-- =========================
                                             SKU
                                        ========================== -->

                                        <td class="product-sku">

                                            <?= htmlspecialchars(
                                                $product['product_code']
                                            ) ?>

                                        </td>


                                        <!-- =========================
                                             CATEGORY
                                        ========================== -->

                                        <td class="product-category">

                                            <?= htmlspecialchars(
                                                $categoryName
                                            ) ?>

                                        </td>


                                        <!-- =========================
                                             VARIANTS
                                        ========================== -->

                                        <td>

                                            <div class="product-sizes">


                                                <?php if (!empty($product['variants'])): ?>


                                                    <?php foreach ($product['variants'] as $variant): ?>

                                                        <span class="size-badge">

                                                            <?= htmlspecialchars(
                                                                $variant
                                                            ) ?>

                                                        </span>

                                                    <?php endforeach; ?>


                                                <?php else: ?>


                                                    <span class="size-badge">
                                                        —
                                                    </span>


                                                <?php endif; ?>


                                            </div>

                                        </td>


                                        <!-- =========================
                                             STOCK
                                        ========================== -->

                                        <td>

                                            <span
                                                class="
                                                    product-stock
                                                    <?= $stock <= 10
                                                        ? 'low-stock'
                                                        : '' ?>
                                                "
                                            >

                                                <?= number_format($stock) ?>

                                            </span>

                                        </td>


                                        <!-- =========================
                                             PRICE
                                        ========================== -->

                                        <td>

                                            <span class="staff-product-price">

                                                ₱<?= number_format(
                                                    (float) $product['price'],
                                                    2
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- =========================
                                             STATUS
                                        ========================== -->

                                        <td>

                                            <span
                                                class="
                                                    product-status
                                                    status-<?= htmlspecialchars(
                                                        $status
                                                    ) ?>
                                                "
                                            >

                                                <?= strtoupper(
                                                    htmlspecialchars($status)
                                                ) ?>

                                            </span>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                            <!-- =============================
                                 NO RESULTS
                            ============================== -->

                            <tr
                                id="noProductsFound"
                                class="no-products-row"
                                style="display: <?= empty($products)
                                    ? 'table-row'
                                    : 'none' ?>;"
                            >

                                <td colspan="7">

                                    <div class="no-products-message">

                                        <i
                                            class="fa-solid fa-box-open"
                                        ></i>

                                        <p>
                                            No products found.
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
         SAME SIDEMENU JS
    ====================================================== -->

    <script
        src="<?= BASE_URL ?>/assets/js/sidemenu.js"
    ></script>


    <!-- =====================================================
         SAME PRODUCTS JS USED BY ADMIN
    ====================================================== -->

    <script
        src="<?= BASE_URL ?>/assets/js/products.js"
    ></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>