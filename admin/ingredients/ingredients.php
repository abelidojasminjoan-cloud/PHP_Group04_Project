<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

$pageTitle = 'Ingredients';

function ingredientEscape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$adminId = (int) ($_SESSION['user_id'] ?? 0);
$stmt = $conn->prepare("
    SELECT u.id FROM users u
    INNER JOIN roles r ON r.id = u.role_id
    WHERE u.id = ? AND u.status = 'active' AND LOWER(r.role_name) = 'admin'
    LIMIT 1
");
$stmt->bind_param('i', $adminId);
$stmt->execute();
$ingredientAdminAllowed = $stmt->get_result()->num_rows === 1;
$stmt->close();

if (!$ingredientAdminAllowed) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

header('Cache-Control: no-store');
if (empty($_SESSION['csrf_ingredient'])) {
    $_SESSION['csrf_ingredient'] = bin2hex(random_bytes(32));
}

$ingredientErrors = [];
$ingredientFormError = '';
$ingredientName = '';
$ingredientDescription = '';
$ingredientCommonIn = '';
$ingredientId = 0;
$ingredientEditing = false;
$ingredientSuccess = $_SESSION['ingredient_success'] ?? '';
unset($_SESSION['ingredient_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ingredientName = is_string($_POST['ingredient_name'] ?? null)
        ? trim($_POST['ingredient_name']) : '';
    $ingredientDescription = is_string($_POST['description'] ?? null)
        ? trim($_POST['description']) : '';
    $ingredientCommonIn = is_string($_POST['common_in'] ?? null)
        ? trim($_POST['common_in']) : '';
    $token = $_POST['csrf_token'] ?? null;
    $ingredientAction = $_POST['ingredient_action'] ?? 'create';
    $ingredientEditing = $ingredientAction === 'update';
    $submittedIngredientId = $_POST['ingredient_id'] ?? '';
    if ($ingredientEditing) {
        $ingredientId = is_string($submittedIngredientId)
            ? (filter_var($submittedIngredientId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0)
            : 0;
    }

    if (!is_string($token) || !hash_equals($_SESSION['csrf_ingredient'], $token)) {
        http_response_code(403);
        $ingredientFormError = 'Your session has expired. Refresh the page and try again.';
    } elseif (!in_array($ingredientAction, ['create', 'update'], true)
        || ($ingredientEditing && !$ingredientId)
        || (!$ingredientEditing && $submittedIngredientId !== '')) {
        http_response_code(400);
        $ingredientFormError = 'Invalid ingredient. Close this form and select the ingredient again.';
    } else {
        if ($ingredientName === '') {
            $ingredientErrors['ingredient_name'] = 'Please enter an ingredient name.';
        } elseif (mb_strlen($ingredientName, 'UTF-8') > 150) {
            $ingredientErrors['ingredient_name'] = 'Use no more than 150 characters.';
        }
        if (!is_string($_POST['description'] ?? '')
            || mb_strlen($ingredientDescription, 'UTF-8') > 5000) {
            $ingredientErrors['description'] = 'Use no more than 5,000 characters.';
        }

        if (!is_string($_POST['common_in'] ?? '')
            || mb_strlen($ingredientCommonIn, 'UTF-8') > 255) {
            $ingredientErrors['common_in'] = 'Use no more than 255 characters.';
        }

        if (!$ingredientErrors) {
            $ingredientTransactionStarted = false;
            try {
                $conn->begin_transaction();
                $ingredientTransactionStarted = true;
                if ($ingredientEditing) {
                    $stmt = $conn->prepare('SELECT id FROM ingredients WHERE id = ? FOR UPDATE');
                    $stmt->bind_param('i', $ingredientId);
                    $stmt->execute();
                    $ingredientExists = $stmt->get_result()->num_rows === 1;
                    $stmt->close();
                    if (!$ingredientExists) {
                        throw new DomainException('This ingredient no longer exists. Refresh the list.');
                    }
                    $stmt = $conn->prepare('UPDATE ingredients SET ingredient_name = ?, description = ?, common_in = ? WHERE id = ?');
                    $stmt->bind_param('sssi', $ingredientName, $ingredientDescription, $ingredientCommonIn, $ingredientId);
                    $stmt->execute();
                    $recordId = (string) $ingredientId;
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO ingredients (ingredient_name, description, common_in, status)
                        VALUES (?, ?, ?, 'active')
                    ");
                    $stmt->bind_param('sss', $ingredientName, $ingredientDescription, $ingredientCommonIn);
                    $stmt->execute();
                    $recordId = (string) $conn->insert_id;
                    $stmt->close();
                }

                $auditAction = $ingredientEditing ? 'UPDATE' : 'CREATE';
                $auditDescription = ($ingredientEditing ? 'Updated' : 'Added') . ' ingredient "' . $ingredientName . '".';
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
                $stmt = $conn->prepare("
                    INSERT INTO audit_logs (user_id, action, module, record_id, description, ip_address)
                    VALUES (?, ?, 'Ingredients', ?, ?, ?)
                ");
                $stmt->bind_param('issss', $adminId, $auditAction, $recordId, $auditDescription, $ipAddress);
                $stmt->execute();
                $stmt->close();
                $conn->commit();
                $ingredientTransactionStarted = false;

                $_SESSION['ingredient_success'] = $ingredientEditing
                    ? 'Ingredient updated successfully.' : 'Ingredient added successfully.';
                header('Location: ' . BASE_URL . '/admin/ingredients/ingredients.php', true, 303);
                exit;
            } catch (Throwable $error) {
                if ($ingredientTransactionStarted) {
                    $conn->rollback();
                }
                if ($error instanceof mysqli_sql_exception && $error->getCode() === 1062) {
                    $ingredientErrors['ingredient_name'] = 'An ingredient with this name already exists.';
                } elseif ($error instanceof DomainException) {
                    http_response_code(404);
                    $ingredientFormError = $error->getMessage();
                } else {
                    error_log('Save Ingredient Error: ' . $error->getMessage());
                    $ingredientFormError = 'Unable to save this ingredient. Please try again.';
                }
            }
        }
    }
}

$ingredients = $conn->query("
    SELECT id, ingredient_name AS name, COALESCE(description, '') AS description,
        COALESCE(common_in, '') AS common_in, status
    FROM ingredients
    ORDER BY ingredient_name
")->fetch_all(MYSQLI_ASSOC);
$totalIngredients = count($ingredients);
$ingredientModalOpen = (bool) ($ingredientErrors || $ingredientFormError);
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/form2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin_delete.css">
</head>


<body>


    <!-- =====================================
         SIDEBAR
    ====================================== -->

    <?php include __DIR__ . '/../../includes/sidemenu.php'; ?>


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

                <button
                    type="button"
                    id="openAddIngredientModal"
                    class="add-ingredient-button"
                    aria-haspopup="dialog"
                    aria-controls="addIngredientModal"
                >

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add Ingredient
                    </span>

                </button>


            </section>


            <!-- =================================
                 INGREDIENT CARD
            ================================== -->

            <?php if ($ingredientSuccess): ?>
                <p class="form-alert form-alert-success" role="status"><?= ingredientEscape($ingredientSuccess) ?></p>
            <?php endif; ?>

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

                                            <button
                                                type="button"
                                                class="ingredient-action-button edit-ingredient-button"
                                                data-ingredient-id="<?= (int) $ingredient['id'] ?>"
                                                data-ingredient-name="<?= ingredientEscape($ingredient['name']) ?>"
                                                data-ingredient-description="<?= ingredientEscape($ingredient['description']) ?>"
                                                data-ingredient-common-in="<?= ingredientEscape($ingredient['common_in']) ?>"
                                                aria-haspopup="dialog"
                                                aria-controls="addIngredientModal"
                                                title="Edit ingredient"
                                                aria-label="Edit <?= htmlspecialchars(
                                                    $ingredient['name']
                                                ) ?>"
                                            >

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


    <!-- Ingredient-only IDs and overrides leave the product modal unchanged. -->
    <div
        id="addIngredientModal"
        class="form-modal<?= $ingredientModalOpen ? ' is-open' : '' ?>"
        aria-hidden="<?= $ingredientModalOpen ? 'false' : 'true' ?>"
    >
        <div class="form-modal-backdrop" data-close-ingredient-modal></div>
        <div class="form-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="addIngredientTitle" tabindex="-1">
            <form action="./ingredients.php" method="POST" id="addIngredientForm" class="purevia-form" data-delete-action="<?= BASE_URL ?>/admin/ingredients/delete_ingredient.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= ingredientEscape($_SESSION['csrf_ingredient']) ?>">
                <input type="hidden" name="ingredient_action" value="<?= $ingredientEditing ? 'update' : 'create' ?>">
                <input type="hidden" name="ingredient_id" value="<?= $ingredientEditing && $ingredientId ? $ingredientId : '' ?>">

                <header class="form-modal-header">
                    <div>
                        <h2 id="addIngredientTitle"><?= $ingredientEditing ? 'Edit Ingredient' : 'Add Ingredient' ?></h2>
                        <p id="ingredientFormSubtitle"<?= $ingredientEditing ? '' : ' hidden' ?>><?= ingredientEscape($ingredientName) ?></p>
                    </div>
                    <button type="button" class="form-modal-close" data-close-ingredient-modal aria-label="Close ingredient form">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </header>

                <div class="form-modal-body">
                    <p id="ingredientFormError" class="ingredient-field-error" role="alert"<?= $ingredientFormError === '' ? ' hidden' : '' ?>><?= ingredientEscape($ingredientFormError) ?></p>

                    <div class="form-group">
                        <label for="ingredientName">Ingredient Name <span class="form-required" aria-hidden="true">*</span></label>
                        <input type="text" id="ingredientName" name="ingredient_name" class="form-control" placeholder="e.g. Azelaic Acid" maxlength="150" aria-required="true" aria-describedby="ingredientNameError" aria-invalid="<?= isset($ingredientErrors['ingredient_name']) ? 'true' : 'false' ?>" value="<?= ingredientEscape($ingredientName) ?>">
                        <span id="ingredientNameError" class="ingredient-field-error" role="alert"<?= isset($ingredientErrors['ingredient_name']) ? '' : ' hidden' ?>><?= ingredientEscape($ingredientErrors['ingredient_name'] ?? '') ?></span>
                    </div>

                    <div class="form-group">
                        <label for="ingredientDescription">Description</label>
                        <textarea id="ingredientDescription" name="description" class="form-control form-textarea" rows="3" maxlength="5000" aria-describedby="ingredientDescriptionError" aria-invalid="<?= isset($ingredientErrors['description']) ? 'true' : 'false' ?>"><?= ingredientEscape($ingredientDescription) ?></textarea>
                        <span id="ingredientDescriptionError" class="ingredient-field-error" role="alert"<?= isset($ingredientErrors['description']) ? '' : ' hidden' ?>><?= ingredientEscape($ingredientErrors['description'] ?? '') ?></span>
                    </div>
                    <div class="form-group">
                        <label for="ingredientCommonIn">Commonly Found In</label>
                        <input type="text" id="ingredientCommonIn" name="common_in" class="form-control" placeholder="e.g. Serums, Toners" maxlength="255" aria-describedby="ingredientCommonInError" aria-invalid="<?= isset($ingredientErrors['common_in']) ? 'true' : 'false' ?>" value="<?= ingredientEscape($ingredientCommonIn) ?>">
                        <span id="ingredientCommonInError" class="ingredient-field-error" role="alert"<?= isset($ingredientErrors['common_in']) ? '' : ' hidden' ?>><?= ingredientEscape($ingredientErrors['common_in'] ?? '') ?></span>
                    </div>

                </div>

                <footer class="form-modal-footer">
                    <button type="button" class="form-cancel-button" data-close-ingredient-modal>Cancel</button>
                    <button type="submit" class="form-submit-button"><?= $ingredientEditing ? 'Save Changes' : 'Add Ingredient' ?></button>
                </footer>
            </form>
        </div>
    </div>

    <!-- SIDEBAR JS -->

    <script src="../../assets/js/sidemenu.js"></script>


    <!-- INGREDIENT JS -->

    <script src="../../assets/js/ingredients.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/../../includes/admin_delete_modal.php'; ?>
</body>

</html>
