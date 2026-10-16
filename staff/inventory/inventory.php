<?php

session_start();

require_once __DIR__ . "/../../config/app.php";
require_once __DIR__ . "/../../config/db.php";

$pageTitle = "Inventory";


/* =========================================================
   STAFF ACCESS ONLY
========================================================= */

if (
    empty($_SESSION['user_id']) ||
    empty($_SESSION['role_id']) ||
    (int) $_SESSION['role_id'] !== 2
) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}


/* =========================================================
   INVENTORY SETTINGS
========================================================= */

/*
    Your database currently does not have a
    low_stock_threshold column.

    For now, PureVia uses 15 as the low-stock threshold.
*/

$defaultThreshold = 15;


/* =========================================================
   LOAD INVENTORY FROM DATABASE
========================================================= */

$inventory = [];


/*
    IMPORTANT:

    We only retrieve fields that belong to the existing
    products/categories structure.

    No:
        p.low_stock_threshold
        p.last_restocked_at
*/

$sql = "
    SELECT
        p.id,
        p.product_name,
        p.product_code,
        p.stock_quantity,
        p.updated_at,
        c.category_name

    FROM products AS p

    LEFT JOIN categories AS c
        ON c.id = p.category_id

    ORDER BY p.product_name ASC
";


$result = $conn->query($sql);


while ($row = $result->fetch_assoc()) {

    $inventory[] = [

        'id' => (int) $row['id'],

        'product' => $row['product_name'],

        'sku' => $row['product_code'],

        'category' =>
            $row['category_name'] ?? 'Uncategorized',

        'stock' =>
            (int) $row['stock_quantity'],

        'threshold' =>
            $defaultThreshold,

        /*
            Because your database currently has no dedicated
            last_restocked_at field, updated_at is used here.

            If you later create proper restock history,
            this can be changed.
        */

        'last_restocked' =>
            $row['updated_at'] ?? null
    ];
}


/* =========================================================
   TOTAL RECORDS
========================================================= */

$totalInventory = count($inventory);


/* =========================================================
   LOAD CATEGORIES FOR FILTER
========================================================= */

$categories = [];

$categorySql = "
    SELECT
        category_name
    FROM categories
    ORDER BY category_name ASC
";

$categoryResult = $conn->query($categorySql);


while ($category = $categoryResult->fetch_assoc()) {

    $categories[] = $category['category_name'];
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
        <?= htmlspecialchars($pageTitle) ?> | PureVia
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


    <!-- SHARED SIDEMENU CSS -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/sidemenu.css"
    >


    <!-- SHARED INVENTORY CSS -->

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/inventory.css"
    >

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>


<body>


    <!-- =====================================================
         SHARED SIDEMENU
    ====================================================== -->

    <?php
    include __DIR__ . "/../../includes/sidemenu.php";
    ?>


    <!-- =====================================================
         PAGE LAYOUT
    ====================================================== -->

    <div class="admin-layout">


        <!-- =================================================
             TOP BAR
        ================================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Inventory
            </p>

        </header>


        <!-- =================================================
             MAIN CONTENT
        ================================================== -->

        <main class="inventory-main">


            <!-- =============================================
                 PAGE HEADER
            ============================================== -->

            <section class="inventory-page-header">

                <div class="inventory-heading">

                    <h1>
                        Inventory
                    </h1>

                    <p>

                        <span id="inventoryRecordCount">
                            <?= number_format($totalInventory) ?>
                        </span>

                        <span id="inventoryRecordLabel">
                            <?= $totalInventory === 1
                                ? 'record'
                                : 'records' ?>
                        </span>

                    </p>

                </div>

            </section>


            <!-- =============================================
                 INVENTORY CARD
            ============================================== -->

            <section class="inventory-card">


                <!-- =========================================
                     SEARCH AND FILTERS
                ========================================== -->

                <div class="inventory-toolbar">


                    <!-- SEARCH -->

                    <div class="inventory-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="inventorySearch"
                            placeholder="Search inventory..."
                            autocomplete="off"
                            aria-label="Search inventory"
                        >

                    </div>


                    <!-- CATEGORY FILTER -->

                    <div class="inventory-filter-dropdown">

                        <i
                            class="fa-solid fa-filter inventory-filter-icon"
                        ></i>

                        <select
                            id="inventoryCategoryFilter"
                            class="inventory-filter-select"
                            aria-label="Filter inventory by category"
                        >

                            <option value="all">
                                All Categories
                            </option>


                            <?php foreach ($categories as $category): ?>

                                <option
                                    value="<?= htmlspecialchars(
                                        strtolower($category)
                                    ) ?>"
                                >

                                    <?= htmlspecialchars($category) ?>

                                </option>

                            <?php endforeach; ?>


                        </select>


                        <i
                            class="fa-solid fa-chevron-down inventory-filter-arrow"
                        ></i>

                    </div>


                    <!-- STOCK FILTER -->

                    <div class="inventory-filter-dropdown">

                        <select
                            id="inventoryStockFilter"
                            class="inventory-filter-select inventory-filter-no-icon"
                            aria-label="Filter inventory by stock level"
                        >

                            <option value="all">
                                All Stock
                            </option>

                            <option value="in-stock">
                                In Stock
                            </option>

                            <option value="low-stock">
                                Low Stock
                            </option>

                            <option value="out-of-stock">
                                Out of Stock
                            </option>

                        </select>


                        <i
                            class="fa-solid fa-chevron-down inventory-filter-arrow"
                        ></i>

                    </div>


                </div>


                <!-- =========================================
                     INVENTORY TABLE
                ========================================== -->

                <div class="inventory-table-wrapper">

                    <table class="inventory-table">


                        <!-- ===============================
                             TABLE HEADER
                        ================================ -->

                        <thead>

                            <tr>

                                <th>PRODUCT</th>

                                <th>SKU</th>

                                <th>CATEGORY</th>

                                <th>STOCK</th>

                                <th>THRESHOLD</th>

                                <th>STATUS</th>

                                <th>LAST RESTOCKED</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <!-- ===============================
                             TABLE BODY
                        ================================ -->

                        <tbody>


                        <?php foreach ($inventory as $item): ?>


                            <?php

                            /* =================================
                               STOCK VALUES
                            ================================= */

                            $stock =
                                (int) $item['stock'];

                            $threshold =
                                (int) $item['threshold'];


                            /* =================================
                               STOCK STATUS
                            ================================= */

                            if ($stock <= 0) {

                                $stockLevel =
                                    'out-of-stock';

                                $stockStatusText =
                                    'Out of Stock';

                            } elseif ($stock <= $threshold) {

                                $stockLevel =
                                    'low-stock';

                                $stockStatusText =
                                    'Low Stock';

                            } else {

                                $stockLevel =
                                    'in-stock';

                                $stockStatusText =
                                    'In Stock';
                            }


                            /* =================================
                               SEARCH DATA
                            ================================= */

                            $searchData = strtolower(

                                $item['product']
                                . ' '
                                . $item['sku']
                                . ' '
                                . $item['category']

                            );


                            /* =================================
                               DATE
                            ================================= */

                            $lastRestocked = '—';

                            if (!empty($item['last_restocked'])) {

                                $timestamp = strtotime(
                                    $item['last_restocked']
                                );

                                if ($timestamp !== false) {

                                    $lastRestocked =
                                        date(
                                            'Y-m-d',
                                            $timestamp
                                        );
                                }
                            }

                            ?>


                            <tr
                                class="inventory-row"

                                data-search="<?= htmlspecialchars(
                                    $searchData
                                ) ?>"

                                data-category="<?= htmlspecialchars(
                                    strtolower(
                                        $item['category']
                                    )
                                ) ?>"

                                data-stock-level="<?= htmlspecialchars(
                                    $stockLevel
                                ) ?>"
                            >


                                <!-- =========================
                                     PRODUCT
                                ========================== -->

                                <td>

                                    <span class="inventory-product-name">

                                        <?= htmlspecialchars(
                                            $item['product']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- =========================
                                     SKU
                                ========================== -->

                                <td>

                                    <span class="inventory-sku">

                                        <?= htmlspecialchars(
                                            $item['sku']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- =========================
                                     CATEGORY
                                ========================== -->

                                <td>

                                    <span class="inventory-category">

                                        <?= htmlspecialchars(
                                            $item['category']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- =========================
                                     STOCK
                                ========================== -->

                                <td>

                                    <span
                                        class="
                                            inventory-stock
                                            <?= htmlspecialchars(
                                                $stockLevel
                                            ) ?>
                                        "
                                    >

                                        <?= number_format($stock) ?>

                                    </span>

                                </td>


                                <!-- =========================
                                     THRESHOLD
                                ========================== -->

                                <td>

                                    <span class="inventory-threshold">

                                        <?= number_format(
                                            $threshold
                                        ) ?>

                                    </span>

                                </td>


                                <!-- =========================
                                     STATUS
                                ========================== -->

                                <td>

                                    <span
                                        class="inventory-stock-status <?= htmlspecialchars(
                                            $stockLevel
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $stockStatusText
                                        ) ?>

                                    </span>

                                </td>


                                <!-- =========================
                                     LAST RESTOCKED
                                ========================== -->

                                <td>

                                    <span class="inventory-date">

                                        <?= htmlspecialchars(
                                            $lastRestocked
                                        ) ?>

                                    </span>

                                </td>


                                <!-- =========================
                                     ACTIONS
                                ========================== -->

                                <td>

                                    <div class="inventory-actions">

                                        <a
                                            href="<?= BASE_URL ?>/staff/inventory/edit_inventory.php?id=<?= urlencode(
                                                $item['id']
                                            ) ?>"

                                            class="inventory-edit-button"

                                            title="Update stock"

                                            aria-label="Update stock for <?= htmlspecialchars(
                                                $item['product']
                                            ) ?>"
                                        >

                                            <i
                                                class="fa-regular fa-pen-to-square"
                                            ></i>

                                        </a>

                                    </div>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                        <!-- =================================
                             NO RESULTS
                        ================================== -->

                        <tr
                            id="noInventoryFound"
                            class="no-inventory-row"
                            style="<?= $totalInventory === 0
                                ? ''
                                : 'display: none;' ?>"
                        >

                            <td colspan="8">

                                <div class="no-inventory-message">

                                    <i
                                        class="fa-solid fa-box-open"
                                    ></i>

                                    <p>
                                        No inventory records found.
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
         SHARED JS
    ====================================================== -->

    <script
        src="<?= BASE_URL ?>/assets/js/sidemenu.js"
    ></script>

    <script
        src="<?= BASE_URL ?>/assets/js/inventory.js"
    ></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>