<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/role-check.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../services/AdminUser.php';

$currentUser = requirePureViaAdmin($conn);
$pageTitle = 'Users';
$users = AdminUser::all($conn);


$totalUsers = count($users);
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/form2.css?v=<?= filemtime(__DIR__ . '/../../assets/css/form2.css') ?>">
    
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
                        total users &middot; deactivate accounts to preserve history
                    </p>

                </div>


                <button type="button" id="addStaffButton" class="add-user-button">

                    <i class="fa-solid fa-plus"></i>

                    <span>
                        Add Staff
                    </span>

                </button>

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



    <?php include __DIR__ . '/../../includes/user-edit-dialog.php'; ?>



    <!-- =====================================
         SIDEBAR JS
    ====================================== -->

    <script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/sidemenu.js"></script>
    <script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/user.js?v=<?= filemtime(__DIR__ . '/../../assets/js/user.js') ?>"></script>


<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<?php include __DIR__ . '/../../includes/staff-account-dialog.php'; ?>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/staff-account.js"></script>
</body>

</html>
