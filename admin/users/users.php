<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/role-check.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../services/AdminUser.php';

$currentUser = requirePureViaAdmin($conn);
$pageTitle = 'Users';
$users = AdminUser::all($conn);
$totalUsers = count($users);
$roles = $conn->query('SELECT id, role_name FROM roles WHERE id IN (1, 2, 3) ORDER BY id')->fetch_all(MYSQLI_ASSOC);
$passwordRequirements = securityPasswordRequirements(securityPolicy($conn));
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

    <link
        rel="stylesheet"
        href="../../assets/css/sidemenu.css"
    >


    <!-- USERS -->

    <link
        rel="stylesheet"
        href="../../assets/css/user.css"
    >

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/form2.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin_delete.css">
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
                Users
            </p>

        </header>


        <!-- =================================
             MAIN CONTENT
        ================================== -->

        <main class="users-main">


            <!-- =================================
                 PAGE HEADER
            ================================== -->

            <section class="users-page-header">

                <div class="users-heading">

                    <h1>
                        Users
                    </h1>

                    <p>
                        <?= number_format($totalUsers) ?>
                        total users
                    </p>

                </div>


                <a
                    href="#userModal"
                    id="addUserButton"
                    aria-controls="userModal"
                    aria-haspopup="dialog"
                    class="add-user-button"
                >

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add User
                    </span>

                </a>

            </section>



            <!-- =================================
                 USERS CARD
            ================================== -->

            <section class="users-card">


                <!-- =================================
                     SEARCH AND FILTER
                ================================== -->

                <div class="users-toolbar">


                    <!-- SEARCH -->

                    <div class="users-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="userSearch"
                            placeholder="Search users..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- ROLE FILTERS -->

                    <!-- ROLE FILTER -->

                    <div class="user-filter-dropdown">

                        <i class="fa-solid fa-filter"></i>

                        <select
                            id="roleFilter"
                            class="role-filter-select"
                            aria-label="Filter users by role"
                        >
                            <option value="all">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="staff">Staff</option>
                            <option value="customer">Customer</option>
                        </select>

                        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>

                    </div>

                </div>



                <!-- =================================
                     USERS TABLE
                ================================== -->

                <div class="users-table-wrapper">

                    <table class="users-table">

                        <thead>

                            <tr>

                                <th>USER</th>

                                <th>PHONE</th>

                                <th>ROLE</th>

                                <th>STATUS</th>

                                <th>JOINED</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <tbody id="usersTableBody">

                            <?php foreach ($users as $user): ?>

                                <?php

                                $role =
                                    strtolower($user['role']);

                                $status =
                                    strtolower($user['status']);

                                $initial =
                                    mb_strtoupper(
                                        mb_substr(
                                            $user['name'],
                                            0,
                                            1
                                        )
                                    );

                                ?>

                                <tr
                                    class="user-row"
                                    data-role="<?= pvHtml($role) ?>"
                                    data-search="<?= pvHtml(
                                        mb_strtolower(
                                            $user['name'] . ' ' .
                                            $user['email'] . ' ' .
                                            $user['phone']
                                        )
                                    ) ?>"
                                >


                                    <!-- USER -->

                                    <td>

                                        <div class="user-information">


                                            <div
                                                class="
                                                    user-avatar
                                                    avatar-<?= pvHtml($role) ?>
                                                "
                                            >

                                                <?= pvHtml($initial) ?>

                                            </div>


                                            <div class="user-details">

                                                <p class="user-name">

                                                    <?= pvHtml(
                                                        $user['name']
                                                    ) ?>

                                                </p>


                                                <p class="user-email">

                                                    <?= pvHtml(
                                                        $user['email']
                                                    ) ?>

                                                </p>

                                            </div>

                                        </div>

                                    </td>



                                    <!-- PHONE -->

                                    <td class="user-phone">

                                        <?= pvHtml(
                                            $user['phone']
                                        ) ?>

                                    </td>



                                    <!-- ROLE -->

                                    <td>

                                        <span
                                            class="
                                                role-badge
                                                role-<?= pvHtml($role) ?>
                                            "
                                        >

                                            <?php if ($role === 'admin'): ?>

                                                <i class="fa-solid fa-shield-halved"></i>

                                            <?php elseif ($role === 'staff'): ?>

                                                <i class="fa-regular fa-user"></i>

                                            <?php else: ?>

                                                <i class="fa-regular fa-user"></i>

                                            <?php endif; ?>


                                            <?= pvHtml(
                                                $user['role']
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                user-status
                                                status-<?= pvHtml($status) ?>
                                            "
                                        >

                                            <?= strtoupper(
                                                pvHtml(
                                                    $user['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- JOINED -->

                                    <td class="joined-date">

                                        <?= pvHtml(
                                            $user['joined']
                                        ) ?>

                                    </td>



                                    <!-- ACTIONS -->
                                    <td>
                                        <div class="user-actions">

                                            <!-- EDIT -->
                                            <a
                                                href="#userModal"
                                                data-edit-user="<?= (int) $user['id'] ?>"
                                                aria-haspopup="dialog"
                                                class="action-button edit-button"
                                                title="Edit user"
                                                aria-label="Edit <?= pvHtml($user['name']) ?>"
                                            >
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>


                                            <!-- DELETE -->
                                            <button
                                                type="button"
                                                class="action-button delete-button"
                                                title="Delete user"
                                                aria-label="Delete <?= pvHtml($user['name']) ?>"
                                                data-delete-user="<?= (int) $user['id'] ?>"
                                                data-revision="<?= pvHtml($user['revision']) ?>"
                                                data-user-name="<?= pvHtml($user['name']) ?>"
                                            >
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>

                                        </div>
                                    </td>

                                </tr>

                            <?php endforeach; ?>


                            <!-- NO RESULT -->

                            <tr
                                id="noUsersFound"
                                class="no-users-row"
                                style="display: <?= $totalUsers === 0 ? 'table-row' : 'none' ?>;"
                            >

                                <td colspan="6">

                                    <div class="no-users-message">

                                        <i class="fa-regular fa-user"></i>

                                        <p>
                                            No users found.
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



    <div id="userModal" class="form-modal" aria-hidden="true">
        <div class="form-modal-backdrop" data-close-user-modal></div>
        <div class="form-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="userFormTitle" tabindex="-1">
            <form id="userForm" class="purevia-form" action="<?= BASE_URL ?>/actions/admin/users/user.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= pvHtml(csrfToken()) ?>">
                <input type="hidden" name="id">
                <input type="hidden" name="revision">
                <input type="hidden" name="action" value="create">
                <header class="form-modal-header">
                    <h2 id="userFormTitle">Add User</h2>
                    <button type="button" class="form-modal-close" data-close-user-modal aria-label="Close user form">&times;</button>
                </header>
                <div class="form-modal-body">
                    <p id="userFormError" class="form-alert form-alert-error" role="alert" tabindex="-1" hidden></p>
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label for="userFirstName">First Name <span class="form-required">*</span></label>
                            <input id="userFirstName" class="form-control" name="first_name" maxlength="100" autocomplete="off" required>
                        </div>
                        <div class="form-group">
                            <label for="userLastName">Last Name <span class="form-required">*</span></label>
                            <input id="userLastName" class="form-control" name="last_name" maxlength="100" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="userEmail">Email Address <span class="form-required">*</span></label>
                        <input id="userEmail" class="form-control" name="email" type="email" maxlength="255" autocomplete="off" required>
                    </div>
                    <div class="form-group">
                        <label for="userPhone">Phone</label>
                        <input id="userPhone" class="form-control" name="phone" type="tel" maxlength="30" autocomplete="off">
                    </div>
                    <div class="form-grid form-grid-2">
                        <div class="form-group">
                            <label for="userRole">Role</label>
                            <select id="userRole" name="role_id" class="form-control" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= (int) $role['id'] ?>"<?= (int) $role['id'] === 3 ? ' selected' : '' ?>><?= pvHtml($role['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="userStatus">Status</label>
                            <select id="userStatus" name="status" class="form-control" aria-describedby="userStatusHelp" required>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="inactive">Inactive</option>
                                <option value="locked">Locked</option>
                            </select>
                        </div>
                    </div>
                    <p class="user-form-help" id="userStatusHelp">Active grants sign-in access and confirms the email on behalf of the user. Saving an account as Active also clears login locks. Pending users must verify their email.</p>
                    <div class="form-group">
                        <label for="userPassword" id="userPasswordLabel">Password <span class="form-required">*</span></label>
                        <input id="userPassword" class="form-control" name="password" type="password" autocomplete="new-password" aria-describedby="userPasswordHelp" required>
                        <p class="user-form-help" id="userPasswordHelp"><span id="userPasswordOptional" hidden>Leave blank to keep the current password. </span><?= pvHtml(implode('. ', $passwordRequirements)) ?>. Maximum 72 bytes.</p>
                    </div>
                </div>
                <footer class="form-modal-footer">
                    <button type="button" class="form-cancel-button" data-close-user-modal>Cancel</button>
                    <button type="submit" id="userSaveButton" class="form-submit-button">Add User</button>
                </footer>
            </form>
        </div>
    </div>

    <!-- =====================================
         SIDEBAR JS
    ====================================== -->

    <script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/sidemenu.js"></script>
    <script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/user.js"></script>


<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/../../includes/admin_delete_modal.php'; ?>
</body>

</html>
