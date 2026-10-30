<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

$pageTitle = 'Skin Concerns';

function concernEscape(?string $value): string
{
    return pvHtml($value ?? '', ENT_QUOTES, 'UTF-8');
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
$concernAdminAllowed = $stmt->get_result()->num_rows === 1;
$stmt->close();

if (!$concernAdminAllowed) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

header('Cache-Control: no-store');
if (empty($_SESSION['csrf_concern'])) {
    $_SESSION['csrf_concern'] = bin2hex(random_bytes(32));
}

$concernErrors = [];
$concernFormError = '';
$concernName = '';
$concernDescription = '';
$concernId = 0;
$concernEditing = false;
$concernSuccess = $_SESSION['concern_success'] ?? '';
unset($_SESSION['concern_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $concernName = is_string($_POST['concern_name'] ?? null)
        ? trim($_POST['concern_name']) : '';
    $concernDescription = is_string($_POST['description'] ?? null)
        ? trim($_POST['description']) : '';
    $token = $_POST['csrf_token'] ?? null;
    $concernAction = $_POST['concern_action'] ?? 'create';
    $concernEditing = $concernAction === 'update';
    $submittedConcernId = $_POST['concern_id'] ?? '';
    if ($concernEditing) {
        $concernId = is_string($submittedConcernId)
            ? (filter_var($submittedConcernId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0)
            : 0;
    }

    if (!is_string($token) || !hash_equals($_SESSION['csrf_concern'], $token)) {
        http_response_code(403);
        $concernFormError = 'Your session has expired. Refresh the page and try again.';
    } elseif (!in_array($concernAction, ['create', 'update'], true)
        || ($concernEditing && !$concernId)
        || (!$concernEditing && $submittedConcernId !== '')) {
        http_response_code(400);
        $concernFormError = 'Invalid concern. Close this form and select the concern again.';
    } else {
        if ($concernName === '') {
            $concernErrors['concern_name'] = 'Please enter a concern name.';
        } elseif (mb_strlen($concernName, 'UTF-8') > 100) {
            $concernErrors['concern_name'] = 'Use no more than 100 characters.';
        }
        if (!is_string($_POST['description'] ?? '')
            || mb_strlen($concernDescription, 'UTF-8') > 5000) {
            $concernErrors['description'] = 'Use no more than 5,000 characters.';
        }

        if (!$concernErrors) {
            $concernTransactionStarted = false;
            try {
                $conn->begin_transaction();
                $concernTransactionStarted = true;
                if ($concernEditing) {
                    $stmt = $conn->prepare('SELECT id FROM skin_concerns WHERE id = ? FOR UPDATE');
                    $stmt->bind_param('i', $concernId);
                    $stmt->execute();
                    $concernExists = $stmt->get_result()->num_rows === 1;
                    $stmt->close();
                    if (!$concernExists) {
                        throw new DomainException('This concern no longer exists. Refresh the list.');
                    }
                    $stmt = $conn->prepare('UPDATE skin_concerns SET concern_name = ?, description = ? WHERE id = ?');
                    $stmt->bind_param('ssi', $concernName, $concernDescription, $concernId);
                    $stmt->execute();
                    $recordId = (string) $concernId;
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO skin_concerns (concern_name, description, status)
                        VALUES (?, ?, 'active')
                    ");
                    $stmt->bind_param('ss', $concernName, $concernDescription);
                    $stmt->execute();
                    $recordId = (string) $conn->insert_id;
                    $stmt->close();
                }

                $auditAction = $concernEditing ? 'UPDATE' : 'CREATE';
                $auditDescription = ($concernEditing ? 'Updated' : 'Added') . ' concern "' . $concernName . '".';
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
                $stmt = $conn->prepare("
                    INSERT INTO audit_logs (user_id, action, module, record_id, description, ip_address)
                    VALUES (?, ?, 'Skin Concerns', ?, ?, ?)
                ");
                $stmt->bind_param('issss', $adminId, $auditAction, $recordId, $auditDescription, $ipAddress);
                $stmt->execute();
                $stmt->close();
                $conn->commit();
                $concernTransactionStarted = false;

                $_SESSION['concern_success'] = $concernEditing
                    ? 'Skin concern updated successfully.' : 'Skin concern added successfully.';
                header('Location: ' . BASE_URL . '/admin/concerns/concerns.php', true, 303);
                exit;
            } catch (Throwable $error) {
                if ($concernTransactionStarted) {
                    $conn->rollback();
                }
                if ($error instanceof mysqli_sql_exception && $error->getCode() === 1062) {
                    $concernErrors['concern_name'] = 'A concern with this name already exists.';
                } elseif ($error instanceof DomainException) {
                    http_response_code(404);
                    $concernFormError = $error->getMessage();
                } else {
                    error_log('Save Concern Error: ' . $error->getMessage());
                    $concernFormError = 'Unable to save this concern. Please try again.';
                }
            }
        }
    }
}

$skinConcerns = $conn->query("
    SELECT id, concern_name AS name, COALESCE(description, '') AS description, status
    FROM skin_concerns
    ORDER BY concern_name
")->fetch_all(MYSQLI_ASSOC);
$totalConcerns = count($skinConcerns);
$concernModalOpen = (bool) ($concernErrors || $concernFormError);
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
        <?= pvHtml($pageTitle) ?> | PureVia
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/form2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin_delete.css">
</head>


<body>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php include __DIR__ . '/../../includes/sidemenu.php'; ?>


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

                <button
                    type="button"
                    id="openAddConcernModal"
                    class="add-concern-button"
                    aria-haspopup="dialog"
                    aria-controls="addConcernModal"
                >

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add Concern
                    </span>

                </button>


            </section>


            <!-- =============================================
                 CARD
            ============================================== -->

            <?php if ($concernSuccess): ?>
                <p class="form-alert form-alert-success" role="status"><?= concernEscape($concernSuccess) ?></p>
            <?php endif; ?>

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

                                    data-status="<?= pvHtml(
                                        $status
                                    ) ?>"

                                    data-search="<?= pvHtml(
                                        $searchData
                                    ) ?>"
                                >


                                    <!-- NAME -->

                                    <td>

                                        <span class="skin-concern-name">

                                            <?= pvHtml(
                                                $concern['name']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- DESCRIPTION -->

                                    <td>

                                        <span class="skin-concern-description">

                                            <?= pvHtml(
                                                $concern['description']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                skin-concern-status
                                                status-<?= pvHtml(
                                                    $status
                                                ) ?>
                                            "
                                        >

                                            <?= strtoupper(
                                                pvHtml(
                                                    $concern['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div class="skin-concern-actions">


                                            <!-- EDIT -->

                                            <button
                                                type="button"
                                                class="skin-concern-action-button edit-concern-button"
                                                data-concern-id="<?= (int) $concern['id'] ?>"
                                                data-concern-name="<?= concernEscape($concern['name']) ?>"
                                                data-concern-description="<?= concernEscape($concern['description']) ?>"
                                                aria-haspopup="dialog"
                                                aria-controls="addConcernModal"
                                                title="Edit concern"
                                                aria-label="Edit <?= pvHtml(
                                                    $concern['name']
                                                ) ?>"
                                            >

                                                <i class="fa-regular fa-pen-to-square"></i>

                                            </button>


                                            <!-- DELETE -->

                                            <button
                                                type="button"
                                                class="skin-concern-action-button delete-concern-button"

                                                title="Delete concern"

                                                aria-label="Delete <?= pvHtml(
                                                    $concern['name']
                                                ) ?>"

                                                data-concern-id="<?= pvHtml(
                                                    $concern['id']
                                                ) ?>"

                                                data-concern-name="<?= pvHtml(
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


    <!-- Concern-only IDs and overrides leave the product modal unchanged. -->
    <div
        id="addConcernModal"
        class="form-modal<?= $concernModalOpen ? ' is-open' : '' ?>"
        aria-hidden="<?= $concernModalOpen ? 'false' : 'true' ?>"
    >
        <div class="form-modal-backdrop" data-close-concern-modal></div>
        <div class="form-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="addConcernTitle" tabindex="-1">
            <form action="./concerns.php" method="POST" id="addConcernForm" class="purevia-form" data-delete-action="<?= BASE_URL ?>/admin/concerns/delete_concern.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= concernEscape($_SESSION['csrf_concern']) ?>">
                <input type="hidden" name="concern_action" value="<?= $concernEditing ? 'update' : 'create' ?>">
                <input type="hidden" name="concern_id" value="<?= $concernEditing && $concernId ? $concernId : '' ?>">

                <header class="form-modal-header">
                    <div>
                        <h2 id="addConcernTitle"><?= $concernEditing ? 'Edit Skin Concern' : 'Add Skin Concern' ?></h2>
                        <p id="concernFormSubtitle"<?= $concernEditing ? '' : ' hidden' ?>><?= concernEscape($concernName) ?></p>
                    </div>
                    <button type="button" class="form-modal-close" data-close-concern-modal aria-label="Close concern form">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                </header>

                <div class="form-modal-body">
                    <p id="concernFormError" class="concern-field-error" role="alert"<?= $concernFormError === '' ? ' hidden' : '' ?>><?= concernEscape($concernFormError) ?></p>

                    <div class="form-group">
                        <label for="concernName">Concern Name <span class="form-required" aria-hidden="true">*</span></label>
                        <input type="text" id="concernName" name="concern_name" class="form-control" placeholder="e.g. Melasma" maxlength="100" aria-required="true" aria-describedby="concernNameError" aria-invalid="<?= isset($concernErrors['concern_name']) ? 'true' : 'false' ?>" value="<?= concernEscape($concernName) ?>">
                        <span id="concernNameError" class="concern-field-error" role="alert"<?= isset($concernErrors['concern_name']) ? '' : ' hidden' ?>><?= concernEscape($concernErrors['concern_name'] ?? '') ?></span>
                    </div>

                    <div class="form-group">
                        <label for="concernDescription">Description</label>
                        <textarea id="concernDescription" name="description" class="form-control form-textarea" rows="3" maxlength="5000" aria-describedby="concernDescriptionError" aria-invalid="<?= isset($concernErrors['description']) ? 'true' : 'false' ?>"><?= concernEscape($concernDescription) ?></textarea>
                        <span id="concernDescriptionError" class="concern-field-error" role="alert"<?= isset($concernErrors['description']) ? '' : ' hidden' ?>><?= concernEscape($concernErrors['description'] ?? '') ?></span>
                    </div>
                </div>

                <footer class="form-modal-footer">
                    <button type="button" class="form-cancel-button" data-close-concern-modal>Cancel</button>
                    <button type="submit" class="form-submit-button"><?= $concernEditing ? 'Save Changes' : 'Add Concern' ?></button>
                </footer>
            </form>
        </div>
    </div>

    <!-- SIDEBAR JS -->

    <script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/sidemenu.js"></script>


    <!-- SKIN CONCERN JS -->

    <script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/concerns.js"></script>


<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/../../includes/admin_delete_modal.php'; ?>
</body>

</html>
