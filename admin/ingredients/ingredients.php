<?php
/*
=========================================
PUREVIA - INGREDIENTS
LIST AND ADD INGREDIENT BACKEND
=========================================
*/

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

$pageTitle = 'Ingredients';

/* ADMIN AUTHENTICATION */

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
      AND role_id = 1
      AND status = 'active'
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$admin) {
    http_response_code(403);
    exit('Access denied. Administrator account required.');
}

/* HTML ESCAPING */

function ingredientEsc(mixed $value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* CSRF TOKEN */

if (empty($_SESSION['csrf_ingredient'])) {
    $_SESSION['csrf_ingredient'] = bin2hex(random_bytes(32));
}

/* FLASH MESSAGES */

$success = (string) ($_SESSION['ingredient_success'] ?? '');
unset($_SESSION['ingredient_success']);

$errors = [];

$formName = '';
$formDescription = '';
$formStatus = 'active';
$formCategoryId = 0;
$formProductId = 0;

/* =========================================
   ADD INGREDIENT - POST
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'add_ingredient') {

        $token = $_POST['csrf_token'] ?? '';

        if (
            !is_string($token) ||
            !hash_equals(
                $_SESSION['csrf_ingredient'],
                $token
            )
        ) {
            http_response_code(403);
            $errors[] = 'Invalid security token. Refresh the page.';
        } else {

            $formName = is_string($_POST['ingredient_name'] ?? null)
                ? trim($_POST['ingredient_name'])
                : '';

            $formDescription = is_string($_POST['description'] ?? null)
                ? trim($_POST['description'])
                : '';

            $formStatus = is_string($_POST['status'] ?? null)
                ? $_POST['status']
                : '';

            /* VALIDATION */

            if ($formName === '') {
                $errors[] = 'Ingredient name is required.';
            } elseif (mb_strlen($formName) > 150) {
                $errors[] = 'Ingredient name must not exceed 150 characters.';
            }

            if (mb_strlen($formDescription) > 5000) {
                $errors[] = 'Description must not exceed 5000 characters.';
            }

            if (!in_array($formStatus, ['active', 'inactive'], true)) {
                $errors[] = 'Please choose a valid status.';
            }



            /* =========================================
   GET SELECTED CATEGORY AND PRODUCT FIRST
========================================= */

            $formCategoryId = filter_var(
                $_POST['category_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            ) ?: 0;

            $formProductId = filter_var(
                $_POST['product_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            ) ?: 0;


            /* =========================================
   THEN VALIDATE CATEGORY AND PRODUCT
========================================= */

            if ($formCategoryId <= 0 || $formProductId <= 0) {

                $errors[] =
                    'Please select a product category and specific product.';
            } else {

                $stmt = $conn->prepare("
        SELECT p.id
        FROM products p
        INNER JOIN categories c
            ON c.id = p.category_id
        WHERE p.id = ?
          AND p.category_id = ?
          AND c.status = 'active'
          AND p.status IN ('active', 'out_of_stock')
        LIMIT 1
    ");

                $stmt->bind_param(
                    'ii',
                    $formProductId,
                    $formCategoryId
                );

                $stmt->execute();

                $validProduct = $stmt->get_result()->fetch_assoc();

                $stmt->close();

                if (!$validProduct) {
                    $errors[] =
                        'The selected product does not belong to that category.';
                }
            }



            /* DUPLICATE NAME */

            if (!$errors) {

                $stmt = $conn->prepare("
                    SELECT id
                    FROM ingredients
                    WHERE ingredient_name = ?
                    LIMIT 1
                ");

                $stmt->bind_param('s', $formName);
                $stmt->execute();

                $duplicate = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($duplicate) {
                    $errors[] = 'This ingredient already exists.';
                }
            }

            /* INSERT INTO DATABASE */

            if (!$errors) {

                $transactionStarted = false;

                try {

                    $conn->begin_transaction();
                    $transactionStarted = true;

                    $stmt = $conn->prepare("
                        INSERT INTO ingredients (
                            ingredient_name,
                            description,
                            status
                        )
                        VALUES (?, ?, ?)
                    ");

                    $stmt->bind_param(
                        'sss',
                        $formName,
                        $formDescription,
                        $formStatus
                    );

                    $stmt->execute();

                    $ingredientId = (int) $conn->insert_id;

                    $stmt->close();


                    /* =========================================
                    LINK INGREDIENT TO SELECTED PRODUCT
                    ========================================= */

                    $stmt = $conn->prepare("
                        INSERT INTO product_ingredients (
                             product_id,
                             ingredient_id
                        )
                        VALUES (?, ?)
                    ");

                    $stmt->bind_param(
                        'ii',
                        $formProductId,
                        $ingredientId
                    );

                    $stmt->execute();
                    $stmt->close();



                    /* AUDIT LOG */

                    $action = 'CREATE';
                    $module = 'Ingredients';
                    $recordId = (string) $ingredientId;
                    $details = 'Created ingredient ID ' .
                        $ingredientId . '.';

                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

                    $stmt = $conn->prepare("
                        INSERT INTO audit_logs (
                            user_id,
                            action,
                            module,
                            record_id,
                            description,
                            ip_address
                        )
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->bind_param(
                        'isssss',
                        $userId,
                        $action,
                        $module,
                        $recordId,
                        $details,
                        $ipAddress
                    );

                    $stmt->execute();
                    $stmt->close();

                    $conn->commit();
                    $transactionStarted = false;

                    $_SESSION['ingredient_success'] =
                        'Ingredient added successfully.';

                    header(
                        'Location: ingredients.php',
                        true,
                        303
                    );
                    exit;
                } catch (Throwable $exception) {

                    if ($transactionStarted) {
                        $conn->rollback();
                    }

                    error_log(
                        'Add Ingredient Error: ' .
                            $exception->getMessage()
                    );

                    $errors[] =
                        (int) $exception->getCode() === 1062
                        ? 'This ingredient already exists.'
                        : 'Unable to add ingredient. Please try again.';
                }
            }
        }
    }
}


/* =========================================
   EDIT INGREDIENT 
========================================= */

$editErrors = [];
$editForm = [
    'id' => 0,
    'name' => '',
    'description' => '',
    'status' => 'active',
    'category_id' => 0,
    'product_id' => 0
];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['form_action'] ?? '') === 'edit_ingredient'
) {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals(
            $_SESSION['csrf_ingredient'],
            $token
        )
    ) {
        http_response_code(403);
        $editErrors[] = 'Invalid security token. Refresh the page.';
    }

    /* GET SUBMITTED INFORMATION */

    $editForm['id'] = filter_var(
        $_POST['ingredient_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    ) ?: 0;

    $editForm['name'] =
        is_string($_POST['ingredient_name'] ?? null)
        ? trim($_POST['ingredient_name'])
        : '';

    $editForm['description'] =
        is_string($_POST['description'] ?? null)
        ? trim($_POST['description'])
        : '';

    $editForm['status'] =
        is_string($_POST['status'] ?? null)
        ? $_POST['status']
        : '';

    $editForm['category_id'] = filter_var(
        $_POST['category_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    ) ?: 0;

    $editForm['product_id'] = filter_var(
        $_POST['product_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    ) ?: 0;

    /* VALIDATE FIELDS */

    if ($editForm['id'] <= 0) {
        $editErrors[] = 'Invalid ingredient ID.';
    }

    if ($editForm['name'] === '') {
        $editErrors[] = 'Ingredient name is required.';
    } elseif (mb_strlen($editForm['name']) > 150) {
        $editErrors[] = 'Ingredient name must not exceed 150 characters.';
    }

    if (mb_strlen($editForm['description']) > 5000) {
        $editErrors[] = 'Description must not exceed 5000 characters.';
    }

    if (!in_array(
        $editForm['status'],
        ['active', 'inactive'],
        true
    )) {
        $editErrors[] = 'Invalid ingredient status.';
    }

    /*
    Product association is optional during editing.
    If either category or product is selected,
    require both.
    */

    if (
        ($editForm['category_id'] > 0) !==
        ($editForm['product_id'] > 0)
    ) {
        $editErrors[] =
            'Select both a category and a specific product.';
    }

    /* UPDATE DATABASE */

    if (!$editErrors) {

        $transactionStarted = false;

        try {

            $conn->begin_transaction();
            $transactionStarted = true;

            $ingredientId = (int) $editForm['id'];
            $name = $editForm['name'];
            $description = $editForm['description'];
            $status = $editForm['status'];

            /* VERIFY EXISTING INGREDIENT */

            $stmt = $conn->prepare("
                SELECT id
                FROM ingredients
                WHERE id = ?
                LIMIT 1
                FOR UPDATE
            ");

            $stmt->bind_param('i', $ingredientId);
            $stmt->execute();

            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$exists) {
                throw new RuntimeException(
                    'Ingredient no longer exists.'
                );
            }

            /* CHECK DUPLICATE INGREDIENT NAME */

            $stmt = $conn->prepare("
                SELECT id
                FROM ingredients
                WHERE ingredient_name = ?
                  AND id <> ?
                LIMIT 1
            ");

            $stmt->bind_param(
                'si',
                $name,
                $ingredientId
            );

            $stmt->execute();
            $duplicate = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($duplicate) {
                throw new RuntimeException(
                    'An ingredient with this name already exists.'
                );
            }

            /* VALIDATE SELECTED PRODUCT */

            $productId = (int) $editForm['product_id'];
            $categoryId = (int) $editForm['category_id'];

            if ($productId > 0) {

                $stmt = $conn->prepare("
                    SELECT p.id
                    FROM products p
                    INNER JOIN categories c
                        ON c.id = p.category_id
                    WHERE p.id = ?
                      AND p.category_id = ?
                      AND c.status = 'active'
                      AND p.status IN ('active', 'out_of_stock')
                    LIMIT 1
                ");

                $stmt->bind_param(
                    'ii',
                    $productId,
                    $categoryId
                );

                $stmt->execute();

                $validProduct =
                    $stmt->get_result()->fetch_assoc();

                $stmt->close();

                if (!$validProduct) {
                    throw new RuntimeException(
                        'The selected product is invalid.'
                    );
                }
            }

            /* UPDATE INGREDIENT */

            $stmt = $conn->prepare("
                UPDATE ingredients
                SET
                    ingredient_name = ?,
                    description = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                'sssi',
                $name,
                $description,
                $status,
                $ingredientId
            );

            $stmt->execute();
            $stmt->close();

            /* ADD PRODUCT ASSOCIATION IF MISSING */

            if ($productId > 0) {

                $stmt = $conn->prepare("
                    SELECT ingredient_id
                    FROM product_ingredients
                    WHERE product_id = ?
                      AND ingredient_id = ?
                    LIMIT 1
                ");

                $stmt->bind_param(
                    'ii',
                    $productId,
                    $ingredientId
                );

                $stmt->execute();
                $alreadyLinked =
                    $stmt->get_result()->fetch_assoc();

                $stmt->close();

                if (!$alreadyLinked) {

                    $stmt = $conn->prepare("
                        INSERT INTO product_ingredients (
                            product_id,
                            ingredient_id
                        )
                        VALUES (?, ?)
                    ");

                    $stmt->bind_param(
                        'ii',
                        $productId,
                        $ingredientId
                    );

                    $stmt->execute();
                    $stmt->close();
                }
            }

            /* AUDIT LOG */

            $action = 'UPDATE';
            $module = 'Ingredients';
            $recordId = (string) $ingredientId;
            $details = 'Updated ingredient ID ' .
                $ingredientId . '.';

            if ($productId > 0) {
                $details .=
                    ' Associated with product ID ' .
                    $productId . '.';
            }

            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

            $stmt = $conn->prepare("
                INSERT INTO audit_logs (
                    user_id,
                    action,
                    module,
                    record_id,
                    description,
                    ip_address
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'isssss',
                $userId,
                $action,
                $module,
                $recordId,
                $details,
                $ipAddress
            );

            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $transactionStarted = false;

            $_SESSION['ingredient_success'] =
                'Ingredient updated successfully.';

            header(
                'Location: ingredients.php',
                true,
                303
            );
            exit;
        } catch (Throwable $exception) {

            if ($transactionStarted) {
                $conn->rollback();
            }

            error_log(
                'Edit Ingredient Error: ' .
                    $exception->getMessage()
            );

            if ((int) $exception->getCode() === 1062) {
                $editErrors[] =
                    'An ingredient with this name already exists.';
            } elseif ($exception instanceof RuntimeException) {
                $editErrors[] = $exception->getMessage();
            } else {
                $editErrors[] =
                    'Unable to update ingredient. Please try again.';
            }
        }
    }
}

/* =========================================
   LOAD INGREDIENTS FROM MYSQL
========================================= */

$sql = "
    SELECT
        i.id,
        i.ingredient_name AS name,
        i.description,
        i.status,

        COALESCE(
            GROUP_CONCAT(
                DISTINCT c.category_name
                ORDER BY c.category_name
                SEPARATOR ', '
            ),
            '—'
        ) AS common_in

    FROM ingredients i

    LEFT JOIN product_ingredients pi
        ON pi.ingredient_id = i.id

    LEFT JOIN products p
        ON p.id = pi.product_id

    LEFT JOIN categories c
        ON c.id = p.category_id

    GROUP BY
        i.id,
        i.ingredient_name,
        i.description,
        i.status

    ORDER BY i.ingredient_name ASC
";

$result = $conn->query($sql);

$ingredients = $result->fetch_all(MYSQLI_ASSOC);

$totalIngredients = count($ingredients);



/* =========================================
   PRODUCT CATEGORIES AND PRODUCTS
   FOR ADD INGREDIENT MODAL
========================================= */

$ingredientCategories = [];
$ingredientProducts = [];

/* LOAD ACTIVE CATEGORIES */

$categoryResult = $conn->query("
    SELECT id, category_name
    FROM categories
    WHERE status = 'active'
    ORDER BY category_name ASC
");

while ($row = $categoryResult->fetch_assoc()) {
    $ingredientCategories[] = $row;
}

/* LOAD PRODUCTS WITH THEIR CATEGORY IDs */

$productResult = $conn->query("
    SELECT
        p.id,
        p.product_name,
        p.category_id
    FROM products p
    INNER JOIN categories c
        ON c.id = p.category_id
    WHERE c.status = 'active'
      AND p.status IN ('active', 'out_of_stock')
    ORDER BY p.product_name ASC
");

while ($row = $productResult->fetch_assoc()) {
    $ingredientProducts[] = [
        'id' => (int) $row['id'],
        'name' => $row['product_name'],
        'category_id' => (int) $row['category_id']
    ];
}

?>




<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($pageTitle) ?> | PureVia
    </title>


    <!-- GOOGLE FONTS -->

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap">


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


    <!-- SIDEBAR CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/sidemenu.css">


    <!-- INGREDIENT CSS -->

    <link rel="stylesheet" href="../../assets/css/ingredient.css">

    <!-- SAME FORM DESIGN AS ADD PRODUCT -->
    <link
        rel="stylesheet"
        href="<?= ASSET_URL ?>/css/form2.css">

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



                <!-- ADD INGREDIENT MODAL BUTTON -->

                <button
                    type="button"
                    class="add-ingredient-button"
                    id="openAddIngredientModal">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Ingredient</span>
                </button>



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
                            autocomplete="off">

                    </div>


                    <!-- STATUS FILTER -->

                    <div class="ingredient-filter-dropdown">

                        <i
                            class="fa-solid fa-filter ingredient-filter-icon"></i>

                        <select
                            id="ingredientStatusFilter"
                            class="ingredient-filter-select"
                            aria-label="Filter ingredients by status">

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
                            class="fa-solid fa-chevron-down ingredient-filter-arrow"></i>

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
                                                    ) ?>">


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
                                                $ingredient['description'] ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
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
                                            ">

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



                                            <!-- EDIT INGREDIENT BUTTON -->

                                            <button
                                                type="button"
                                                class="ingredient-action-button edit-ingredient-button"
                                                title="Edit ingredient"
                                                aria-label="Edit <?= ingredientEsc($ingredient['name']) ?>"

                                                data-id="<?= (int) $ingredient['id'] ?>"

                                                data-name="<?= ingredientEsc($ingredient['name']) ?>"

                                                data-description="<?= ingredientEsc(
                                                                        $ingredient['description'] ?? ''
                                                                    ) ?>"

                                                data-status="<?= ingredientEsc($ingredient['status']) ?>">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>



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
                                                                        ) ?>">

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
                                style="display: none;">

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



    <!-- =========================================
     ADD INGREDIENT MODAL
========================================= -->

    <div
        class="form-modal"
        id="addIngredientModal"
        aria-hidden="true">
        <!-- BACKGROUND OVERLAY -->

        <div
            class="form-modal-backdrop"
            data-close-ingredient-modal></div>

        <!-- MODAL CONTAINER -->

        <div
            class="form-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="addIngredientTitle">

            <form
                action="ingredients.php"
                method="POST"
                id="addIngredientForm"
                class="purevia-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= ingredientEsc($_SESSION['csrf_ingredient']) ?>">

                <input
                    type="hidden"
                    name="form_action"
                    value="add_ingredient">

                <!-- HEADER -->

                <div class="form-modal-header">

                    <div>
                        <h2 id="addIngredientTitle">
                            Add New Ingredient
                        </h2>

                        <p>
                            Fill in the ingredient details below
                        </p>
                    </div>

                    <button
                        type="button"
                        class="form-modal-close"
                        data-close-ingredient-modal
                        aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>

                <!-- BODY -->

                <div class="form-modal-body">

                    <?php if (!empty($errors)): ?>

                        <div
                            class="form-alert form-alert-error"
                            role="alert">
                            <?php foreach ($errors as $error): ?>
                                <p>
                                    <?= ingredientEsc($error) ?>
                                </p>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                    <!-- NAME AND STATUS -->

                    <div class="form-grid form-grid-2">

                        <div class="form-group">

                            <label for="newIngredientName">
                                Ingredient Name
                                <span class="form-required">*</span>
                            </label>

                            <input
                                type="text"
                                id="newIngredientName"
                                name="ingredient_name"
                                class="form-control"
                                placeholder="e.g. Niacinamide"
                                maxlength="150"
                                value="<?= ingredientEsc($formName) ?>"
                                required>

                        </div>

                        <div class="form-group">

                            <label for="newIngredientStatus">
                                Status
                            </label>

                            <div class="form-select-wrapper">

                                <select
                                    id="newIngredientStatus"
                                    name="status"
                                    class="form-control form-select"
                                    required>
                                    <option
                                        value="active"
                                        <?= $formStatus === 'active'
                                            ? 'selected'
                                            : '' ?>>
                                        Active
                                    </option>

                                    <option
                                        value="inactive"
                                        <?= $formStatus === 'inactive'
                                            ? 'selected'
                                            : '' ?>>
                                        Inactive
                                    </option>
                                </select>

                                <i class="fa-solid fa-chevron-down"></i>

                            </div>

                        </div>

                    </div>


                    <!-- PRODUCT CATEGORY AND SPECIFIC PRODUCT -->

                    <div class="form-grid form-grid-2">

                        <!-- CATEGORY -->

                        <div class="form-group">

                            <label for="ingredientCategory">
                                Product Category
                                <span class="form-required">*</span>
                            </label>

                            <div class="form-select-wrapper">

                                <select
                                    id="ingredientCategory"
                                    name="category_id"
                                    class="form-control form-select"
                                    required>
                                    <option value="">
                                        Select category
                                    </option>

                                    <?php foreach ($ingredientCategories as $category): ?>

                                        <option
                                            value="<?= (int) $category['id'] ?>"
                                            <?= (string) ($formCategoryId ?? '') ===
                                                (string) $category['id']
                                                ? 'selected'
                                                : '' ?>>
                                            <?= ingredientEsc($category['category_name']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <i class="fa-solid fa-chevron-down"></i>

                            </div>

                        </div>

                        <!-- SPECIFIC PRODUCT -->

                        <div class="form-group">

                            <label for="ingredientProduct">
                                Specific Product
                                <span class="form-required">*</span>
                            </label>

                            <div class="form-select-wrapper">

                                <select
                                    id="ingredientProduct"
                                    name="product_id"
                                    class="form-control form-select"
                                    data-selected="<?= (int) ($formProductId ?? 0) ?>"
                                    required
                                    disabled>
                                    <option value="">
                                        Select a category first
                                    </option>
                                </select>

                                <i class="fa-solid fa-chevron-down"></i>

                            </div>

                        </div>

                    </div>


                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label for="newIngredientDescription">
                            Description
                        </label>

                        <textarea
                            id="newIngredientDescription"
                            name="description"
                            class="form-control form-textarea"
                            placeholder="Describe the ingredient and its purpose..."
                            rows="5"
                            maxlength="5000"><?= ingredientEsc($formDescription) ?></textarea>

                    </div>

                </div>

                <!-- FIXED FOOTER -->

                <footer class="form-modal-footer">

                    <button
                        type="button"
                        class="form-cancel-button"
                        data-close-ingredient-modal>
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="form-submit-button"
                        id="submitIngredientButton">
                        Add Ingredient
                    </button>

                </footer>

            </form>

        </div>

    </div>
    <!-- =========================================
     EDIT INGREDIENT MODAL
========================================= -->

    <div
        class="form-modal"
        id="editIngredientModal"
        aria-hidden="true">

        <div
            class="form-modal-backdrop"
            data-close-edit-ingredient></div>

        <div
            class="form-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="editIngredientTitle">

            <form
                action="ingredients.php"
                method="POST"
                id="editIngredientForm"
                class="purevia-form">

                <!-- SECURITY -->

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= ingredientEsc(
                                $_SESSION['csrf_ingredient']
                            ) ?>">

                <input
                    type="hidden"
                    name="form_action"
                    value="edit_ingredient">

                <input
                    type="hidden"
                    id="editIngredientId"
                    name="ingredient_id"
                    value="">

                <!-- HEADER -->

                <div class="form-modal-header">

                    <div>
                        <h2 id="editIngredientTitle">
                            Edit Ingredient
                        </h2>

                        <p>
                            Update ingredient details and product associations
                        </p>
                    </div>

                    <button
                        type="button"
                        class="form-modal-close"
                        data-close-edit-ingredient
                        aria-label="Close Edit Ingredient">
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>

                <!-- BODY -->

                <div class="form-modal-body">

                    <?php if (!empty($editErrors)): ?>

                        <div
                            class="form-alert form-alert-error"
                            role="alert">
                            <?php foreach ($editErrors as $error): ?>
                                <p>
                                    <?= ingredientEsc($error) ?>
                                </p>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                    <!-- INGREDIENT NAME AND STATUS -->

                    <div class="form-grid form-grid-2">

                        <div class="form-group">

                            <label for="editIngredientName">
                                Ingredient Name
                                <span class="form-required">*</span>
                            </label>

                            <input
                                type="text"
                                id="editIngredientName"
                                name="ingredient_name"
                                class="form-control"
                                maxlength="150"
                                required>

                        </div>

                        <div class="form-group">

                            <label for="editIngredientStatus">
                                Status
                            </label>

                            <div class="form-select-wrapper">

                                <select
                                    id="editIngredientStatus"
                                    name="status"
                                    class="form-control form-select"
                                    required>
                                    <option value="active">
                                        Active
                                    </option>

                                    <option value="inactive">
                                        Inactive
                                    </option>
                                </select>

                                <i class="fa-solid fa-chevron-down"></i>

                            </div>

                        </div>

                    </div>

                    <!-- PRODUCT CATEGORY AND SPECIFIC PRODUCT -->

                    <div class="form-grid form-grid-2">

                        <div class="form-group">

                            <label for="editIngredientCategory">
                                Product Category
                            </label>

                            <div class="form-select-wrapper">

                                <select
                                    id="editIngredientCategory"
                                    name="category_id"
                                    class="form-control form-select">
                                    <option value="">
                                        No additional product
                                    </option>

                                    <?php foreach ($ingredientCategories as $category): ?>

                                        <option
                                            value="<?= (int) $category['id'] ?>">
                                            <?= ingredientEsc(
                                                $category['category_name']
                                            ) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <i class="fa-solid fa-chevron-down"></i>

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="editIngredientProduct">
                                Specific Product
                            </label>

                            <div class="form-select-wrapper">

                                <select
                                    id="editIngredientProduct"
                                    name="product_id"
                                    class="form-control form-select"
                                    disabled>
                                    <option value="">
                                        Select a category first
                                    </option>
                                </select>

                                <i class="fa-solid fa-chevron-down"></i>

                            </div>

                        </div>

                    </div>

                    <p class="product-help">
                        Optional: Select a product to associate
                        this ingredient with it. Existing product
                        associations will be preserved.
                    </p>

                    <!-- DESCRIPTION -->

                    <div class="form-group">

                        <label for="editIngredientDescription">
                            Description
                        </label>

                        <textarea
                            id="editIngredientDescription"
                            name="description"
                            class="form-control form-textarea"
                            placeholder="Describe the ingredient and its purpose..."
                            rows="5"
                            maxlength="5000"></textarea>

                    </div>

                </div>

                <!-- FOOTER -->

                <footer class="form-modal-footer">

                    <button
                        type="button"
                        class="form-cancel-button"
                        data-close-edit-ingredient>
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="form-submit-button"
                        id="submitEditIngredient">
                        Save Changes
                    </button>

                </footer>

            </form>

        </div>

    </div>


    <!-- SIDEBAR JS -->

    <script src="../../assets/js/sidemenu.js"></script>


    <!-- INGREDIENT JS -->

    <script src="../../assets/js/ingredients.js"></script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const modal = document.getElementById("addIngredientModal");
            const openButton = document.getElementById("openAddIngredientModal");
            const form = document.getElementById("addIngredientForm");
            const submitButton = document.getElementById("submitIngredientButton");

            if (!modal || !openButton || !form) {
                console.error("Add Ingredient modal elements are missing.");
                return;
            }

            const closeButtons = modal.querySelectorAll(
                "[data-close-ingredient-modal]"
            );

            let lastFocusedElement = null;

            function openModal() {
                lastFocusedElement = document.activeElement;

                modal.classList.add("is-open");
                modal.setAttribute("aria-hidden", "false");

                document.body.classList.add("form-modal-open");

                document.getElementById("newIngredientName").focus();
            }

            function closeModal() {
                modal.classList.remove("is-open");
                modal.setAttribute("aria-hidden", "true");

                document.body.classList.remove("form-modal-open");

                if (lastFocusedElement) {
                    lastFocusedElement.focus();
                }
            }

            openButton.addEventListener("click", openModal);

            closeButtons.forEach(function(button) {
                button.addEventListener("click", closeModal);
            });

            document.addEventListener("keydown", function(event) {

                if (!modal.classList.contains("is-open")) {
                    return;
                }

                if (event.key === "Escape") {
                    closeModal();
                }

                if (event.key === "Tab") {

                    const focusable = Array.from(
                        modal.querySelectorAll(
                            'button:not([disabled]), ' +
                            'input:not([disabled]):not([type="hidden"]), ' +
                            'select:not([disabled]), ' +
                            'textarea:not([disabled])'
                        )
                    ).filter(function(element) {
                        return element.getClientRects().length > 0;
                    });

                    if (!focusable.length) {
                        return;
                    }

                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];

                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });

            form.addEventListener("submit", function(event) {

                const nameInput =
                    document.getElementById("newIngredientName");

                nameInput.value = nameInput.value.trim();

                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = "Adding Ingredient...";
            });

            /* REOPEN MODAL AFTER PHP VALIDATION ERROR */

            const hasErrors = <?= !empty($errors) ? 'true' : 'false' ?>;

            if (hasErrors) {
                openModal();
            }



            /* =========================================
               CATEGORY -> PRODUCT DROPDOWN
            ========================================= */

            const ingredientCategory =
                document.getElementById("ingredientCategory");

            const ingredientProduct =
                document.getElementById("ingredientProduct");

            const ingredientProductData = <?= json_encode(
                                                $ingredientProducts,
                                                JSON_HEX_TAG |
                                                    JSON_HEX_AMP |
                                                    JSON_HEX_APOS |
                                                    JSON_HEX_QUOT
                                            ) ?>;

            function updateIngredientProducts() {

                const selectedCategory = Number(
                    ingredientCategory.value
                );

                const previousSelection =
                    ingredientProduct.dataset.selected || "";

                ingredientProduct.replaceChildren();

                const defaultOption =
                    document.createElement("option");

                defaultOption.value = "";

                if (!selectedCategory) {
                    defaultOption.textContent =
                        "Select a category first";
                    ingredientProduct.appendChild(defaultOption);
                    ingredientProduct.disabled = true;
                    return;
                }

                const matchingProducts = ingredientProductData.filter(
                    product =>
                    Number(product.category_id) === selectedCategory
                );

                defaultOption.textContent =
                    matchingProducts.length ?
                    "Select product" :
                    "No products available";

                ingredientProduct.appendChild(defaultOption);

                matchingProducts.forEach(function(product) {

                    const option =
                        document.createElement("option");

                    option.value = product.id;
                    option.textContent = product.name;

                    if (String(product.id) === previousSelection) {
                        option.selected = true;
                    }

                    ingredientProduct.appendChild(option);
                });

                ingredientProduct.disabled =
                    matchingProducts.length === 0;
            }

            ingredientCategory.addEventListener(
                "change",
                function() {
                    ingredientProduct.dataset.selected = "";
                    updateIngredientProducts();
                }
            );

            /* INITIALIZE ON PAGE LOAD */

            updateIngredientProducts();

        });
    </script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const modal = document.getElementById("editIngredientModal");
            const form = document.getElementById("editIngredientForm");

            if (!modal || !form) {
                return;
            }

            const idInput = document.getElementById("editIngredientId");
            const nameInput = document.getElementById("editIngredientName");
            const descriptionInput =
                document.getElementById("editIngredientDescription");
            const statusInput =
                document.getElementById("editIngredientStatus");

            const categorySelect =
                document.getElementById("editIngredientCategory");
            const productSelect =
                document.getElementById("editIngredientProduct");
            const submitButton =
                document.getElementById("submitEditIngredient");

            const products = <?= json_encode(
                                    $ingredientProducts,
                                    JSON_HEX_TAG |
                                        JSON_HEX_AMP |
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT
                                ) ?>;

            let lastFocused = null;

            /* LOAD PRODUCTS BASED ON CATEGORY */

            function populateProducts(selectedProductId = "") {

                const categoryId = Number(categorySelect.value);

                productSelect.replaceChildren();

                const placeholder = document.createElement("option");
                placeholder.value = "";

                if (!categoryId) {
                    placeholder.textContent = "Select a category first";
                    productSelect.appendChild(placeholder);
                    productSelect.disabled = true;
                    return;
                }

                const matches = products.filter(product =>
                    Number(product.category_id) === categoryId
                );

                placeholder.textContent = matches.length ?
                    "Select product" :
                    "No products available";

                productSelect.appendChild(placeholder);

                matches.forEach(function(product) {

                    const option = document.createElement("option");

                    option.value = String(product.id);
                    option.textContent = product.name;

                    if (String(product.id) === String(selectedProductId)) {
                        option.selected = true;
                    }

                    productSelect.appendChild(option);
                });

                productSelect.disabled = matches.length === 0;
            }

            categorySelect.addEventListener("change", function() {
                populateProducts();
            });

            /* OPEN EDIT MODAL */

            function openModal(ingredient) {

                lastFocused = document.activeElement;

                idInput.value = ingredient.id;
                nameInput.value = ingredient.name;
                descriptionInput.value = ingredient.description;
                statusInput.value = ingredient.status;

                categorySelect.value = ingredient.category_id || "";
                populateProducts(ingredient.product_id || "");

                modal.classList.add("is-open");
                modal.setAttribute("aria-hidden", "false");

                document.body.classList.add("form-modal-open");

                nameInput.focus();
            }

            /* EDIT BUTTONS */

            document.querySelectorAll(
                ".edit-ingredient-button"
            ).forEach(function(button) {

                button.addEventListener("click", function() {

                    openModal({
                        id: button.dataset.id,
                        name: button.dataset.name || "",
                        description: button.dataset.description || "",
                        status: button.dataset.status || "active",
                        category_id: "",
                        product_id: ""
                    });
                });
            });

            /* CLOSE EDIT MODAL */

            function closeModal() {

                modal.classList.remove("is-open");
                modal.setAttribute("aria-hidden", "true");

                document.body.classList.remove("form-modal-open");

                if (lastFocused) {
                    lastFocused.focus();
                }
            }

            modal.querySelectorAll(
                "[data-close-edit-ingredient]"
            ).forEach(function(button) {
                button.addEventListener("click", closeModal);
            });

            document.addEventListener("keydown", function(event) {

                if (!modal.classList.contains("is-open")) {
                    return;
                }

                if (event.key === "Escape") {
                    closeModal();
                }

                if (event.key !== "Tab") {
                    return;
                }

                const focusable = Array.from(
                    modal.querySelectorAll(
                        'button:not([disabled]), ' +
                        'input:not([disabled]):not([type="hidden"]), ' +
                        'select:not([disabled]), ' +
                        'textarea:not([disabled])'
                    )
                ).filter(element =>
                    element.getClientRects().length > 0
                );

                if (!focusable.length) {
                    return;
                }

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            });

            /* PREVENT DOUBLE SUBMISSION */

            form.addEventListener("submit", function(event) {

                nameInput.value = nameInput.value.trim();

                if (!form.checkValidity()) {
                    event.preventDefault();
                    form.reportValidity();
                    return;
                }

                if (categorySelect.value && !productSelect.value) {
                    event.preventDefault();
                    productSelect.focus();
                    productSelect.reportValidity();
                    alert("Please select a specific product.");
                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = "Saving Changes...";
            });

            /* REOPEN WHEN SERVER VALIDATION FAILS */

            const editErrors = <?= json_encode(
                                    !empty($editErrors) ? $editForm : null,
                                    JSON_HEX_TAG |
                                        JSON_HEX_AMP |
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT
                                ) ?>;

            if (editErrors) {
                openModal(editErrors);
            }

        });
    </script>


</body>

</html>