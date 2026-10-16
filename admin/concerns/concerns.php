<?php

session_start();
require_once __DIR__ . '/../../config/app.php';

$pageTitle = "Skin Concerns";


/* =========================================================
   TEMPORARY ADMIN SESSION
   Remove this when your login/session is fully connected
========================================================= */

$_SESSION['first_name'] =
    $_SESSION['first_name'] ?? 'Ava';

$_SESSION['last_name'] =
    $_SESSION['last_name'] ?? 'Santos';

$_SESSION['email'] =
    $_SESSION['email'] ?? 'admin@purevia.com';


/* =========================================================
   SAMPLE SKIN CONCERN DATA
   Replace this with your MySQL query later
========================================================= */

$skinConcerns = [

    [
        'id' => 1,
        'name' => 'Acne',
        'description' => 'Treatment and care for acne concerns',
        'status' => 'active'
    ],

    [
        'id' => 2,
        'name' => 'Aging',
        'description' => 'Treatment and care for aging concerns',
        'status' => 'active'
    ],

    [
        'id' => 3,
        'name' => 'Brightening',
        'description' => 'Treatment and care for brightening concerns',
        'status' => 'active'
    ],

    [
        'id' => 4,
        'name' => 'Dark Spots',
        'description' => 'Treatment and care for dark spots concerns',
        'status' => 'active'
    ],

    [
        'id' => 5,
        'name' => 'Dryness',
        'description' => 'Treatment and care for dryness concerns',
        'status' => 'active'
    ],

    [
        'id' => 6,
        'name' => 'Enlarged Pores',
        'description' => 'Treatment and care for enlarged pores concerns',
        'status' => 'active'
    ],

    [
        'id' => 7,
        'name' => 'Hyperpigmentation',
        'description' => 'Treatment and care for hyperpigmentation concerns',
        'status' => 'active'
    ],

    [
        'id' => 8,
        'name' => 'Redness',
        'description' => 'Treatment and care for redness concerns',
        'status' => 'active'
    ],

    [
        'id' => 9,
        'name' => 'Sensitivity',
        'description' => 'Treatment and care for sensitive skin concerns',
        'status' => 'active'
    ],

    [
        'id' => 10,
        'name' => 'Uneven Texture',
        'description' => 'Treatment and care for uneven skin texture',
        'status' => 'inactive'
    ]

];


$totalConcerns = count($skinConcerns);

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

    <link rel="stylesheet"href="../../assets/css/sidemenu.css">


    <!-- SKIN CONCERN CSS -->

    <link rel="stylesheet" href="../../assets/css/concerns.css">

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>


<body>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php include("../../includes/sidemenu.php"); ?>


    <!-- =====================================================
         ADMIN LAYOUT
    ====================================================== -->

    <div class="admin-layout">


        <!-- =================================================
             TOP BAR
        ================================================== -->

        <header class="admin-topbar">

            <p class="admin-topbar-title">
                Skin Concerns
            </p>

        </header>


        <!-- =================================================
             MAIN
        ================================================== -->

        <main class="skin-concerns-main">


            <!-- =============================================
                 PAGE HEADER
            ============================================== -->

            <section class="skin-concerns-page-header">


                <div class="skin-concerns-heading">

                    <h1>
                        Skin Concerns
                    </h1>

                    <p>
                        <?= number_format($totalConcerns) ?>
                        records
                    </p>

                </div>


                <!-- ADD CONCERN -->

                <a
                    href="./add_concern.php"
                    class="add-concern-button"
                >

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add Concern
                    </span>

                </a>


            </section>


            <!-- =============================================
                 CARD
            ============================================== -->

            <section class="skin-concerns-card">


                <!-- =========================================
                     SEARCH + FILTER
                ========================================== -->

                <div class="skin-concerns-toolbar">


                    <!-- SEARCH -->

                    <div class="skin-concerns-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="skinConcernSearch"
                            placeholder="Search skin concerns..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- STATUS FILTER -->

                    <div class="skin-concern-filter-dropdown">

                        <i
                            class="fa-solid fa-filter skin-concern-filter-icon"
                        ></i>

                        <select
                            id="skinConcernStatusFilter"
                            class="skin-concern-filter-select"
                            aria-label="Filter skin concerns by status"
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
                            class="fa-solid fa-chevron-down skin-concern-filter-arrow"
                        ></i>

                    </div>


                </div>


                <!-- =========================================
                     TABLE
                ========================================== -->

                <div class="skin-concerns-table-wrapper">


                    <table class="skin-concerns-table">


                        <!-- TABLE HEADER -->

                        <thead>

                            <tr>

                                <th>NAME</th>

                                <th>DESCRIPTION</th>

                                <th>STATUS</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <!-- TABLE BODY -->

                        <tbody>


                            <?php foreach ($skinConcerns as $concern): ?>


                                <?php

                                $status =
                                    strtolower(
                                        $concern['status']
                                    );

                                $searchData =
                                    strtolower(
                                        $concern['name']
                                        . ' '
                                        . $concern['description']
                                    );

                                ?>


                                <tr
                                    class="skin-concern-row"

                                    data-status="<?= htmlspecialchars(
                                        $status
                                    ) ?>"

                                    data-search="<?= htmlspecialchars(
                                        $searchData
                                    ) ?>"
                                >


                                    <!-- NAME -->

                                    <td>

                                        <span class="skin-concern-name">

                                            <?= htmlspecialchars(
                                                $concern['name']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DESCRIPTION -->

                                    <td>

                                        <span class="skin-concern-description">

                                            <?= htmlspecialchars(
                                                $concern['description']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                skin-concern-status
                                                status-<?= htmlspecialchars(
                                                    $status
                                                ) ?>
                                            "
                                        >

                                            <?= strtoupper(
                                                htmlspecialchars(
                                                    $concern['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="skin-concern-actions">


                                            <!-- EDIT -->

                                            <a
                                                href="./edit_concern.php?id=<?= urlencode(
                                                    $concern['id']
                                                ) ?>"
                                                class="skin-concern-action-button edit-concern-button"
                                                title="Edit concern"
                                                aria-label="Edit <?= htmlspecialchars(
                                                    $concern['name']
                                                ) ?>"
                                            >

                                                <i class="fa-regular fa-pen-to-square"></i>

                                            </a>


                                            <!-- DELETE -->

                                            <button
                                                type="button"
                                                class="skin-concern-action-button delete-concern-button"

                                                title="Delete concern"

                                                aria-label="Delete <?= htmlspecialchars(
                                                    $concern['name']
                                                ) ?>"

                                                data-concern-id="<?= htmlspecialchars(
                                                    $concern['id']
                                                ) ?>"

                                                data-concern-name="<?= htmlspecialchars(
                                                    $concern['name']
                                                ) ?>"
                                            >

                                                <i class="fa-regular fa-trash-can"></i>

                                            </button>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                            <!-- =================================
                                 NO RESULTS
                            ================================== -->

                            <tr
                                id="noSkinConcernsFound"
                                class="no-skin-concerns-row"
                                style="display: none;"
                            >

                                <td colspan="4">

                                    <div class="no-skin-concerns-message">

                                        <i class="fa-solid fa-magnifying-glass"></i>

                                        <p>
                                            No skin concerns found.
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


    <!-- SKIN CONCERN JS -->

    <script src="../../assets/js/concerns.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>