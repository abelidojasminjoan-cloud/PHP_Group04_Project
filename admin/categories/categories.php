<?php

session_start();
require_once __DIR__ . '/../../config/app.php';

$pageTitle = "Categories";


/* =========================================
   TEMPORARY ADMIN SESSION
   Remove when login is connected
========================================= */

$_SESSION['first_name'] =
    $_SESSION['first_name'] ?? 'Ava';

$_SESSION['last_name'] =
    $_SESSION['last_name'] ?? 'Santos';

$_SESSION['email'] =
    $_SESSION['email'] ?? 'admin@purevia.com';


/* =========================================
   SAMPLE CATEGORY DATA
   Replace with MySQL query later
========================================= */

$categories = [

    [
        'id' => 1,
        'name' => 'Cleansers',
        'description' => 'Facial cleansers and micellar waters',
        'products' => 8,
        'status' => 'active'
    ],

    [
        'id' => 2,
        'name' => 'Toners',
        'description' => 'Balancing and hydrating toners',
        'products' => 6,
        'status' => 'active'
    ],

    [
        'id' => 3,
        'name' => 'Serums',
        'description' => 'Targeted treatment serums',
        'products' => 12,
        'status' => 'active'
    ],

    [
        'id' => 4,
        'name' => 'Moisturizers',
        'description' => 'Day and night creams',
        'products' => 9,
        'status' => 'active'
    ],

    [
        'id' => 5,
        'name' => 'Sunscreen',
        'description' => 'SPF protection products',
        'products' => 5,
        'status' => 'active'
    ],

    [
        'id' => 6,
        'name' => 'Eye Care',
        'description' => 'Eye creams and treatments',
        'products' => 4,
        'status' => 'active'
    ],

    [
        'id' => 7,
        'name' => 'Masks',
        'description' => 'Sheet masks and clay masks',
        'products' => 7,
        'status' => 'active'
    ],

    [
        'id' => 8,
        'name' => 'Exfoliators',
        'description' => 'Chemical and physical exfoliating products',
        'products' => 3,
        'status' => 'inactive'
    ]

];


$totalCategories = count($categories);

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


    <!-- SIDEBAR -->

    <link
        rel="stylesheet"
        href="../../assets/css/sidemenu.css"
    >


    <!-- CATEGORY CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/category.css"
    >

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>


<body>


    <!-- =====================================
         SIDEBAR
    ====================================== -->

    <?php include("../../includes/sidemenu.php"); ?>


    <!-- =====================================
         ADMIN LAYOUT
    ====================================== -->

    <div class="admin-layout">


        <!-- =================================
             TOP BAR
        ================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Categories
            </p>

        </header>


        <!-- =================================
             MAIN CONTENT
        ================================== -->

        <main class="categories-main">


            <!-- =================================
                 PAGE HEADER
            ================================== -->

            <section class="categories-page-header">


                <div class="categories-heading">

                    <h1>
                        Categories
                    </h1>

                    <p>
                        <?= number_format($totalCategories) ?>
                        records
                    </p>

                </div>


                <!-- ADD CATEGORY -->

                <a
                    href="./add_category.php"
                    class="add-category-button"
                >

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add Category
                    </span>

                </a>


            </section>


            <!-- =================================
                 CATEGORY CARD
            ================================== -->

            <section class="categories-card">


                <!-- =================================
                     SEARCH AND FILTER
                ================================== -->

                <div class="categories-toolbar">


                    <!-- SEARCH -->

                    <div class="categories-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="categorySearch"
                            placeholder="Search categories..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- STATUS FILTER -->

                    <div class="category-filter-dropdown">

                        <i class="fa-solid fa-filter category-filter-icon"></i>

                        <select
                            id="categoryStatusFilter"
                            class="category-filter-select"
                            aria-label="Filter categories by status"
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

                        <i class="fa-solid fa-chevron-down category-filter-arrow"></i>

                    </div>


                </div>


                <!-- =================================
                     TABLE
                ================================== -->

                <div class="categories-table-wrapper">


                    <table class="categories-table">


                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>NAME</th>

                                <th>DESCRIPTION</th>

                                <th>PRODUCTS</th>

                                <th>STATUS</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody>


                            <?php foreach ($categories as $category): ?>


                                <?php

                                    $status =
                                        strtolower(
                                            $category['status']
                                        );

                                    $searchData =
                                        strtolower(
                                            $category['name']
                                            . ' '
                                            . $category['description']
                                        );

                                ?>


                                <tr
                                    class="category-row"

                                    data-status="<?= htmlspecialchars(
                                        $status
                                    ) ?>"

                                    data-search="<?= htmlspecialchars(
                                        $searchData
                                    ) ?>"
                                >


                                    <!-- CATEGORY NAME -->

                                    <td>

                                        <span class="category-name">

                                            <?= htmlspecialchars(
                                                $category['name']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DESCRIPTION -->

                                    <td>

                                        <span class="category-description">

                                            <?= htmlspecialchars(
                                                $category['description']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- PRODUCTS -->

                                    <td>

                                        <span class="category-product-count">

                                            <?= number_format(
                                                $category['products']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                category-status
                                                status-<?= htmlspecialchars(
                                                    $status
                                                ) ?>
                                            "
                                        >

                                            <?= strtoupper(
                                                htmlspecialchars(
                                                    $category['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="category-actions">


                                            <!-- EDIT -->

                                            <a
                                                href="./edit_category.php?id=<?= urlencode(
                                                    $category['id']
                                                ) ?>"
                                                class="category-action-button edit-category-button"
                                                title="Edit category"
                                                aria-label="Edit <?= htmlspecialchars(
                                                    $category['name']
                                                ) ?>"
                                            >

                                                <i class="fa-regular fa-pen-to-square"></i>

                                            </a>


                                            <!-- DELETE -->

                                            <button
                                                type="button"
                                                class="category-action-button delete-category-button"

                                                title="Delete category"

                                                aria-label="Delete <?= htmlspecialchars(
                                                    $category['name']
                                                ) ?>"

                                                data-category-id="<?= htmlspecialchars(
                                                    $category['id']
                                                ) ?>"

                                                data-category-name="<?= htmlspecialchars(
                                                    $category['name']
                                                ) ?>"
                                            >

                                                <i class="fa-regular fa-trash-can"></i>

                                            </button>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            <!-- NO RESULTS -->

                            <tr
                                id="noCategoriesFound"
                                class="no-categories-row"
                                style="display: none;"
                            >

                                <td colspan="5">

                                    <div class="no-categories-message">

                                        <i class="fa-solid fa-folder-open"></i>

                                        <p>
                                            No categories found.
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


    <!-- SIDEBAR JS -->

    <script src="../../assets/js/sidemenu.js"></script>


    <!-- CATEGORY JS -->

    <script src="../../assets/js/categories.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>