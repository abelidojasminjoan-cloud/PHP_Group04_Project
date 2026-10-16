<?php

session_start();
require_once __DIR__ . '/../../config/app.php';

$pageTitle = "Users";


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
   SAMPLE USER DATA
   Replace with MySQL query later
========================================= */

$users = [

    [
        'id' => 1,
        'name' => 'Ava Santos',
        'email' => 'admin@purevia.com',
        'phone' => '+63 917 123 4567',
        'role' => 'Admin',
        'status' => 'active',
        'joined' => '2024-01-15'
    ],

    [
        'id' => 2,
        'name' => 'Marcus Williams',
        'email' => 'marcus@purevia.com',
        'phone' => '+63 918 234 5678',
        'role' => 'Staff',
        'status' => 'active',
        'joined' => '2024-03-10'
    ],

    [
        'id' => 3,
        'name' => 'Lisa Park',
        'email' => 'lisa@purevia.com',
        'phone' => '+63 919 345 6789',
        'role' => 'Staff',
        'status' => 'active',
        'joined' => '2024-04-22'
    ],

    [
        'id' => 4,
        'name' => 'Sofia Dela Rosa',
        'email' => 'sofia.delarosa@email.com',
        'phone' => '+63 920 456 7890',
        'role' => 'Customer',
        'status' => 'active',
        'joined' => '2025-02-14'
    ],

    [
        'id' => 5,
        'name' => 'James Uy',
        'email' => 'james.uy@email.com',
        'phone' => '+63 921 567 8901',
        'role' => 'Customer',
        'status' => 'active',
        'joined' => '2025-04-08'
    ],

    [
        'id' => 6,
        'name' => 'Maria Garcia',
        'email' => 'maria.garcia@email.com',
        'phone' => '+63 922 678 9012',
        'role' => 'Customer',
        'status' => 'locked',
        'joined' => '2025-01-20'
    ],

    [
        'id' => 7,
        'name' => 'Carlo Bautista',
        'email' => 'carlo.bautista@email.com',
        'phone' => '+63 923 789 0123',
        'role' => 'Customer',
        'status' => 'active',
        'joined' => '2025-07-01'
    ],

    [
        'id' => 8,
        'name' => 'Nina Tan',
        'email' => 'nina.tan@email.com',
        'phone' => '+63 924 890 1234',
        'role' => 'Customer',
        'status' => 'inactive',
        'joined' => '2024-11-05'
    ]

];


$totalUsers = count($users);

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


    <!-- USERS -->

    <link
        rel="stylesheet"
        href="../../assets/css/user.css"
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
                    href="./add.php"
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
                                    strtoupper(
                                        substr(
                                            $user['name'],
                                            0,
                                            1
                                        )
                                    );

                                ?>

                                <tr
                                    class="user-row"
                                    data-role="<?= htmlspecialchars($role) ?>"
                                    data-search="<?= htmlspecialchars(
                                        strtolower(
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
                                                    avatar-<?= htmlspecialchars($role) ?>
                                                "
                                            >

                                                <?= htmlspecialchars($initial) ?>

                                            </div>


                                            <div class="user-details">

                                                <p class="user-name">

                                                    <?= htmlspecialchars(
                                                        $user['name']
                                                    ) ?>

                                                </p>


                                                <p class="user-email">

                                                    <?= htmlspecialchars(
                                                        $user['email']
                                                    ) ?>

                                                </p>

                                            </div>

                                        </div>

                                    </td>



                                    <!-- PHONE -->

                                    <td class="user-phone">

                                        <?= htmlspecialchars(
                                            $user['phone']
                                        ) ?>

                                    </td>



                                    <!-- ROLE -->

                                    <td>

                                        <span
                                            class="
                                                role-badge
                                                role-<?= htmlspecialchars($role) ?>
                                            "
                                        >

                                            <?php if ($role === 'admin'): ?>

                                                <i class="fa-solid fa-shield-halved"></i>

                                            <?php elseif ($role === 'staff'): ?>

                                                <i class="fa-regular fa-user"></i>

                                            <?php else: ?>

                                                <i class="fa-regular fa-user"></i>

                                            <?php endif; ?>


                                            <?= htmlspecialchars(
                                                $user['role']
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                user-status
                                                status-<?= htmlspecialchars($status) ?>
                                            "
                                        >

                                            <?= strtoupper(
                                                htmlspecialchars(
                                                    $user['status']
                                                )
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- JOINED -->

                                    <td class="joined-date">

                                        <?= htmlspecialchars(
                                            $user['joined']
                                        ) ?>

                                    </td>



                                    <!-- ACTIONS -->
                                    <td>
                                        <div class="user-actions">

                                            <!-- EDIT -->
                                            <a
                                                href="./edit.php?id=<?= urlencode($user['id']) ?>"
                                                class="action-button edit-button"
                                                title="Edit user"
                                                aria-label="Edit <?= htmlspecialchars($user['name']) ?>"
                                            >
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>


                                            <!-- DELETE -->
                                            <button
                                                type="button"
                                                class="action-button delete-button"
                                                title="Delete user"
                                                aria-label="Delete <?= htmlspecialchars($user['name']) ?>"
                                                data-user-id="<?= htmlspecialchars($user['id']) ?>"
                                                data-user-name="<?= htmlspecialchars($user['name']) ?>"
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



    <!-- =====================================
         SIDEBAR JS
    ====================================== -->

    <script src="../../assets/js/sidemenu.js"></script>
    <script src="../../assets/js/user.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>