<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

$pageTitle = 'Categories';

function categoryEscape(?string $value): string
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
$categoryAdminAllowed = $stmt->get_result()->num_rows === 1;
$stmt->close();

if (!$categoryAdminAllowed) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

header('Cache-Control: no-store');
if (empty($_SESSION['csrf_category'])) {
    $_SESSION['csrf_category'] = bin2hex(random_bytes(32));
}

$categoryErrors = [];
$categoryFormError = '';
$categoryName = '';
$categoryDescription = '';
$categoryId = 0;
$categoryEditing = false;
$categorySuccess = $_SESSION['category_success'] ?? '';
unset($_SESSION['category_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = is_string($_POST['category_name'] ?? null)
        ? trim($_POST['category_name']) : '';
    $categoryDescription = is_string($_POST['description'] ?? null)
        ? trim($_POST['description']) : '';
    $token = $_POST['csrf_token'] ?? null;
    $categoryAction = $_POST['category_action'] ?? 'create';
    $categoryEditing = $categoryAction === 'update';
    $submittedCategoryId = $_POST['category_id'] ?? '';
    if ($categoryEditing) {
        $categoryId = is_string($submittedCategoryId)
            ? (filter_var($submittedCategoryId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0)
            : 0;
    }

    if (!is_string($token) || !hash_equals($_SESSION['csrf_category'], $token)) {
        http_response_code(403);
        $categoryFormError = 'Your session has expired. Refresh the page and try again.';
    } elseif (!in_array($categoryAction, ['create', 'update'], true)
        || ($categoryEditing && !$categoryId)
        || (!$categoryEditing && $submittedCategoryId !== '')) {
        http_response_code(400);
        $categoryFormError = 'Invalid category. Close this form and select the category again.';
    } else {
        if ($categoryName === '') {
            $categoryErrors['category_name'] = 'Please enter a category name.';
        } elseif (mb_strlen($categoryName, 'UTF-8') > 100) {
            $categoryErrors['category_name'] = 'Use no more than 100 characters.';
        }
        if (!is_string($_POST['description'] ?? '')
            || mb_strlen($categoryDescription, 'UTF-8') > 5000) {
            $categoryErrors['description'] = 'Use no more than 5,000 characters.';
        }

        if (!$categoryErrors) {
            $categoryTransactionStarted = false;
            try {
                $conn->begin_transaction();
                $categoryTransactionStarted = true;
                if ($categoryEditing) {
                    $stmt = $conn->prepare('SELECT id FROM categories WHERE id = ? FOR UPDATE');
                    $stmt->bind_param('i', $categoryId);
                    $stmt->execute();
                    $categoryExists = $stmt->get_result()->num_rows === 1;
                    $stmt->close();
                    if (!$categoryExists) {
                        throw new DomainException('This category no longer exists. Refresh the list.');
                    }
                    $stmt = $conn->prepare('UPDATE categories SET category_name = ?, description = ? WHERE id = ?');
                    $stmt->bind_param('ssi', $categoryName, $categoryDescription, $categoryId);
                    $stmt->execute();
                    $recordId = (string) $categoryId;
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO categories (category_name, description, status)
                        VALUES (?, ?, 'active')
                    ");
                    $stmt->bind_param('ss', $categoryName, $categoryDescription);
                    $stmt->execute();
                    $recordId = (string) $conn->insert_id;
                    $stmt->close();
                }

                $auditAction = $categoryEditing ? 'UPDATE' : 'CREATE';
                $auditDescription = ($categoryEditing ? 'Updated' : 'Added') . ' category "' . $categoryName . '".';
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
                $stmt = $conn->prepare("
                    INSERT INTO audit_logs (user_id, action, module, record_id, description, ip_address)
                    VALUES (?, ?, 'Categories', ?, ?, ?)
                ");
                $stmt->bind_param('issss', $adminId, $auditAction, $recordId, $auditDescription, $ipAddress);
                $stmt->execute();
                $stmt->close();
                $conn->commit();
                $categoryTransactionStarted = false;

                $_SESSION['category_success'] = $categoryEditing
                    ? 'Category updated successfully.' : 'Category added successfully.';
                header('Location: ' . BASE_URL . '/admin/categories/categories.php', true, 303);
                exit;
            } catch (Throwable $error) {
                if ($categoryTransactionStarted) {
                    $conn->rollback();
                }
                if ($error instanceof mysqli_sql_exception && $error->getCode() === 1062) {
                    $categoryErrors['category_name'] = 'A category with this name already exists.';
                } elseif ($error instanceof DomainException) {
                    http_response_code(404);
                    $categoryFormError = $error->getMessage();
                } else {
                    error_log('Save Category Error: ' . $error->getMessage());
                    $categoryFormError = 'Unable to save this category. Please try again.';
                }
            }
        }
    }
}

$categories = $conn->query("
    SELECT c.id, c.category_name AS name, c.description, c.status,
        (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS products
    FROM categories c
    ORDER BY c.category_name
")->fetch_all(MYSQLI_ASSOC);
$totalCategories = count($categories);
$categoryModalOpen = (bool) ($categoryErrors || $categoryFormError);
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


    <!-- Shared product form styles; category overrides stay in category.css. -->
    <link rel="stylesheet" href="../../assets/css/form2.css">

    <!-- CATEGORY CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/category.css"
    >

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
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

                <button
                    type="button"
                    id="openAddCategoryModal"
                    class="add-category-button"
                    aria-haspopup="dialog"
                    aria-controls="addCategoryModal"
                >

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add Category
                    </span>

                </button>


            </section>


            <!-- =================================
                 CATEGORY CARD
            ================================== -->

            <?php if ($categorySuccess): ?>
                <p class="form-alert form-alert-success" role="status"><?= categoryEscape($categorySuccess) ?></p>
            <?php endif; ?>

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
                                                $category['description'] ?? ''
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

                                            <button
                                                type="button"
                                                class="category-action-button edit-category-button"
                                                data-category-id="<?= (int) $category['id'] ?>"
                                                data-category-name="<?= categoryEscape($category['name']) ?>"
                                                data-category-description="<?= categoryEscape($category['description']) ?>"
                                                aria-haspopup="dialog"
                                                aria-controls="addCategoryModal"
                                                title="Edit category"
                                                aria-label="Edit <?= htmlspecialchars(
                                                    $category['name']
                                                ) ?>"
                                            >

                                                <i class="fa-regular fa-pen-to-square"></i>

                                            </button>


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


    <!-- Category-only IDs and overrides leave the product modal unchanged. -->
    <div
        id="addCategoryModal"
        class="form-modal<?= $categoryModalOpen ? ' is-open' : '' ?>"
        aria-hidden="<?= $categoryModalOpen ? 'false' : 'true' ?>"
    >
        <div class="form-modal-backdrop" data-close-category-modal></div>
        <div class="form-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="addCategoryTitle" tabindex="-1">
            <form action="./categories.php" method="POST" id="addCategoryForm" class="purevia-form" data-delete-action="<?= BASE_URL ?>/admin/categories/delete_category.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= categoryEscape($_SESSION['csrf_category']) ?>">
                <input type="hidden" name="category_action" value="<?= $categoryEditing ? 'update' : 'create' ?>">
                <input type="hidden" name="category_id" value="<?= $categoryEditing && $categoryId ? $categoryId : '' ?>">

                <header class="form-modal-header">
                    <h2 id="addCategoryTitle"><?= $categoryEditing ? 'Edit Category' : 'Add Category' ?></h2>
                    <button type="button" class="form-modal-close" data-close-category-modal aria-label="Close category form">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </header>

                <div class="form-modal-body">
                    <p id="categoryFormError" class="category-field-error" role="alert"<?= $categoryFormError === '' ? ' hidden' : '' ?>><?= categoryEscape($categoryFormError) ?></p>

                    <div class="form-group">
                        <label for="categoryName">Category Name <span class="form-required" aria-hidden="true">*</span></label>
                        <input type="text" id="categoryName" name="category_name" class="form-control" placeholder="e.g. Essences" maxlength="100" aria-required="true" aria-describedby="categoryNameError" aria-invalid="<?= isset($categoryErrors['category_name']) ? 'true' : 'false' ?>" value="<?= categoryEscape($categoryName) ?>">
                        <span id="categoryNameError" class="category-field-error" role="alert"<?= isset($categoryErrors['category_name']) ? '' : ' hidden' ?>><?= categoryEscape($categoryErrors['category_name'] ?? '') ?></span>
                    </div>

                    <div class="form-group">
                        <label for="categoryDescription">Description</label>
                        <textarea id="categoryDescription" name="description" class="form-control form-textarea" placeholder="Brief description of this category" rows="3" maxlength="5000" aria-describedby="categoryDescriptionError" aria-invalid="<?= isset($categoryErrors['description']) ? 'true' : 'false' ?>"><?= categoryEscape($categoryDescription) ?></textarea>
                        <span id="categoryDescriptionError" class="category-field-error" role="alert"<?= isset($categoryErrors['description']) ? '' : ' hidden' ?>><?= categoryEscape($categoryErrors['description'] ?? '') ?></span>
                    </div>
                </div>

                <footer class="form-modal-footer">
                    <button type="button" class="form-cancel-button" data-close-category-modal>Cancel</button>
                    <button type="submit" class="form-submit-button"><?= $categoryEditing ? 'Save Changes' : 'Add Category' ?></button>
                </footer>
            </form>
        </div>
    </div>

    <!-- SIDEBAR JS -->

    <script src="../../assets/js/sidemenu.js"></script>


    <!-- CATEGORY JS -->

    <script src="../../assets/js/categories.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/../../includes/admin_delete_modal.php'; ?>
</body>

</html>
