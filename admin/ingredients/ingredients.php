<?php

session_start();
require_once __DIR__ . '/../../config/app.php';

$pageTitle = "Ingredients";


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
   SAMPLE INGREDIENT DATA
   Replace with MySQL query later
========================================= */

$ingredients = [

    [
        'id' => 1,
        'name' => 'Niacinamide',
        'description' => 'Vitamin B3 that minimizes pores and evens skin tone',
        'common_in' => 'Serums, Toners',
        'status' => 'active'
    ],

    [
        'id' => 2,
        'name' => 'Hyaluronic Acid',
        'description' => 'Powerful humectant that attracts moisture',
        'common_in' => 'Serums, Moisturizers',
        'status' => 'active'
    ],

    [
        'id' => 3,
        'name' => 'Retinol',
        'description' => 'Vitamin A derivative for anti-aging',
        'common_in' => 'Serums, Night Creams',
        'status' => 'active'
    ],

    [
        'id' => 4,
        'name' => 'Vitamin C',
        'description' => 'Antioxidant brightener',
        'common_in' => 'Serums, Toners',
        'status' => 'active'
    ],

    [
        'id' => 5,
        'name' => 'Salicylic Acid',
        'description' => 'BHA that exfoliates inside pores',
        'common_in' => 'Cleansers, Toners',
        'status' => 'active'
    ],

    [
        'id' => 6,
        'name' => 'Glycolic Acid',
        'description' => 'AHA that resurfaces skin texture',
        'common_in' => 'Toners, Exfoliants',
        'status' => 'active'
    ],

    [
        'id' => 7,
        'name' => 'Ceramides',
        'description' => 'Lipids that strengthen skin barrier',
        'common_in' => 'Moisturizers, Cleansers',
        'status' => 'active'
    ],

    [
        'id' => 8,
        'name' => 'Peptides',
        'description' => 'Amino acid chains that support skin firmness',
        'common_in' => 'Serums, Moisturizers',
        'status' => 'active'
    ],

    [
        'id' => 9,
        'name' => 'Azelaic Acid',
        'description' => 'Helps improve uneven tone and blemishes',
        'common_in' => 'Serums, Treatments',
        'status' => 'active'
    ],

    [
        'id' => 10,
        'name' => 'Lactic Acid',
        'description' => 'Gentle AHA that exfoliates and hydrates skin',
        'common_in' => 'Toners, Exfoliants',
        'status' => 'active'
    ],

    [
        'id' => 11,
        'name' => 'Benzoyl Peroxide',
        'description' => 'Ingredient commonly used for acne treatment',
        'common_in' => 'Cleansers, Treatments',
        'status' => 'inactive'
    ],

    [
        'id' => 12,
        'name' => 'Centella Asiatica',
        'description' => 'Soothing botanical ingredient for sensitive skin',
        'common_in' => 'Serums, Moisturizers',
        'status' => 'active'
    ]

];


$totalIngredients = count($ingredients);

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


    <!-- SIDEBAR CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/sidemenu.css"
    >


    <!-- INGREDIENT CSS -->

    <link rel="stylesheet" href="../../assets/css/ingredient.css">

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
                Ingredients
            </p>

        </header>


        <!-- =================================
             MAIN
        ================================== -->

        <main class="ingredients-main">


            <!-- =================================
                 PAGE HEADER
            ================================== -->

            <section class="ingredients-page-header">


                <div class="ingredients-heading">

                    <h1>
                        Ingredients
                    </h1>

                    <p>
                        <?= number_format($totalIngredients) ?>
                        records
                    </p>

                </div>


                <!-- ADD INGREDIENT -->

                <a
                    href="./add_ingredient.php"
                    class="add-ingredient-button"
                >

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add Ingredient
                    </span>

                </a>


            </section>


            <!-- =================================
                 INGREDIENT CARD
            ================================== -->

            <section class="ingredients-card">


                <!-- =================================
                     SEARCH + FILTER
                ================================== -->

                <div class="ingredients-toolbar">


                    <!-- SEARCH -->

                    <div class="ingredients-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="ingredientSearch"
                            placeholder="Search ingredients..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- STATUS FILTER -->

                    <div class="ingredient-filter-dropdown">

                        <i
                            class="fa-solid fa-filter ingredient-filter-icon"
                        ></i>

                        <select
                            id="ingredientStatusFilter"
                            class="ingredient-filter-select"
                            aria-label="Filter ingredients by status"
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
                            class="fa-solid fa-chevron-down ingredient-filter-arrow"
                        ></i>

                    </div>


                </div>


                <!-- =================================
                     TABLE
                ================================== -->

                <div class="ingredients-table-wrapper">


                    <table class="ingredients-table">


                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>NAME</th>

                                <th>DESCRIPTION</th>

                                <th>COMMON IN</th>

                                <th>STATUS</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody>


                            <?php foreach ($ingredients as $ingredient): ?>


                                <?php

                                    $status =
                                        strtolower(
                                            $ingredient['status']
                                        );

                                    $searchData =
                                        strtolower(
                                            $ingredient['name']
                                            . ' '
                                            . $ingredient['description']
                                            . ' '
                                            . $ingredient['common_in']
                                        );

                                ?>


                                <tr
                                    class="ingredient-row"

                                    data-status="<?= htmlspecialchars(
                                        $status
                                    ) ?>"

                                    data-search="<?= htmlspecialchars(
                                        $searchData
                                    ) ?>"
                                >


                                    <!-- NAME -->

                                    <td>

                                        <span class="ingredient-name">

                                            <?= htmlspecialchars(
                                                $ingredient['name']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DESCRIPTION -->

                                    <td>

                                        <span class="ingredient-description">

                                            <?= htmlspecialchars(
                                                $ingredient['description']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- COMMON IN -->

                                    <td>

                                        <span class="ingredient-common">

                                            <?= htmlspecialchars(
                                                $ingredient['common_in']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                ingredient-status
                                                status-<?= htmlspecialchars(
                                                    $status
                                                ) ?>
                                            "
                                        >

                                            <?= strtoupper(
                                                htmlspecialchars(
                                                    $ingredient['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="ingredient-actions">


                                            <!-- EDIT -->

                                            <a
                                                href="./edit_ingredient.php?id=<?= urlencode(
                                                    $ingredient['id']
                                                ) ?>"
                                                class="ingredient-action-button edit-ingredient-button"
                                                title="Edit ingredient"
                                                aria-label="Edit <?= htmlspecialchars(
                                                    $ingredient['name']
                                                ) ?>"
                                            >

                                                <i class="fa-regular fa-pen-to-square"></i>

                                            </a>


                                            <!-- DELETE -->

                                            <button
                                                type="button"
                                                class="ingredient-action-button delete-ingredient-button"

                                                title="Delete ingredient"

                                                aria-label="Delete <?= htmlspecialchars(
                                                    $ingredient['name']
                                                ) ?>"

                                                data-ingredient-id="<?= htmlspecialchars(
                                                    $ingredient['id']
                                                ) ?>"

                                                data-ingredient-name="<?= htmlspecialchars(
                                                    $ingredient['name']
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
                                id="noIngredientsFound"
                                class="no-ingredients-row"
                                style="display: none;"
                            >

                                <td colspan="5">

                                    <div class="no-ingredients-message">

                                        <i class="fa-solid fa-flask"></i>

                                        <p>
                                            No ingredients found.
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


    <!-- INGREDIENT JS -->

    <script src="../../assets/js/ingredients.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>