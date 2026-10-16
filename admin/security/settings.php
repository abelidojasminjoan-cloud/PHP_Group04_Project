<?php

/* =========================================================
   PUREVIA ADMIN - SECURITY SETTINGS
========================================================= */

session_start();
require_once __DIR__ . '/../../config/app.php';

$pageTitle = 'Security Settings';

require_once __DIR__ . '/../../config/db.php';


/* =========================================================
   TEMPORARY ADMIN SESSION
   Remove this when final authentication is connected.
========================================================= */

$_SESSION['first_name'] =
    $_SESSION['first_name'] ?? 'Ava';

$_SESSION['last_name'] =
    $_SESSION['last_name'] ?? 'Santos';

$_SESSION['email'] =
    $_SESSION['email'] ?? 'admin@purevia.com';


/* =========================================================
   DEFAULT SECURITY SETTINGS
========================================================= */

$settings = [
    'password_min_length' => 12,
    'require_uppercase' => 1,
    'require_lowercase' => 1,
    'require_number' => 1,
    'require_special_character' => 1,
    'require_otp' => 1,

    'session_timeout_minutes' => 30,
    'max_login_attempts' => 3,
    'lockout_minutes' => 15
];


/* =========================================================
   LOAD SECURITY SETTINGS
========================================================= */

$settingsQuery = "
    SELECT
        password_min_length,
        require_uppercase,
        require_lowercase,
        require_number,
        require_special_character,
        require_otp,
        session_timeout_minutes,
        max_login_attempts,
        lockout_minutes
    FROM security_settings
    ORDER BY id ASC
    LIMIT 1
";

$settingsResult = $conn->query($settingsQuery);

if ($settingsResult && $settingsResult->num_rows > 0) {

    $row = $settingsResult->fetch_assoc();

    $settings['password_min_length'] =
        (int) $row['password_min_length'];

    $settings['require_uppercase'] =
        (int) $row['require_uppercase'];

    $settings['require_lowercase'] =
        (int) $row['require_lowercase'];

    $settings['require_number'] =
        (int) $row['require_number'];

    $settings['require_special_character'] =
        (int) $row['require_special_character'];

    $settings['require_otp'] =
        (int) $row['require_otp'];

    $settings['session_timeout_minutes'] =
        (int) $row['session_timeout_minutes'];

    $settings['max_login_attempts'] =
        (int) $row['max_login_attempts'];

    $settings['lockout_minutes'] =
        (int) $row['lockout_minutes'];
}

/* =========================================================
   LOAD LOCKED ACCOUNTS
========================================================= */

$lockedAccounts = [];

$lockedQuery = "
    SELECT
        id,
        first_name,
        last_name,
        email,
        status,
        failed_login_attempts,
        locked_until
    FROM users
    WHERE status = 'locked'
       OR (
            locked_until IS NOT NULL
            AND locked_until > NOW()
       )
    ORDER BY
        locked_until DESC,
        first_name ASC,
        last_name ASC
";

$lockedResult = $conn->query($lockedQuery);

if ($lockedResult) {

    while ($row = $lockedResult->fetch_assoc()) {

        $lockedAccounts[] = $row;
    }
}

$totalLockedAccounts = count($lockedAccounts);


/* =========================================================
   ADMIN INFORMATION
========================================================= */

$adminFirstName =
    $_SESSION['first_name'] ?? 'Admin';

$adminLastName =
    $_SESSION['last_name'] ?? 'User';

$adminEmail =
    $_SESSION['email'] ?? 'admin@purevia.com';

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
        <?= htmlspecialchars($pageTitle) ?> | PureVia Admin
    </title>


    <!-- GOOGLE FONTS -->

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link 
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" 
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- SIDEMENU -->

    <link rel="stylesheet" href="../../assets/css/sidemenu.css">
    <link rel="stylesheet" href="../../assets/css/security_settings.css">

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>


<body>


<?php include("../../includes/sidemenu.php"); ?>


<div class="admin-layout">


    <!-- =====================================================
         TOP BAR
    ====================================================== -->

    <header class="admin-topbar">

        <p class="admin-topbar-title">
            Security Settings
        </p>

    </header>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="security-main">


        <!-- =================================================
             PAGE TITLE
        ================================================== -->

        <section class="security-page-header">

            <div>

                <h1>
                    Security Settings
                </h1>

                <p>
                    Manage password, session and account
                    security policies.
                </p>

            </div>

        </section>


        <!-- =================================================
             SUCCESS / ERROR MESSAGE
        ================================================== -->

        <?php if (!empty($_SESSION['security_success'])): ?>

            <div class="security-message security-message-success">

                <i class="fa-solid fa-circle-check"></i>

                <span>
                    <?= htmlspecialchars(
                        $_SESSION['security_success']
                    ) ?>
                </span>

            </div>

            <?php unset($_SESSION['security_success']); ?>

        <?php endif; ?>


        <?php if (!empty($_SESSION['security_error'])): ?>

            <div class="security-message security-message-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>
                    <?= htmlspecialchars(
                        $_SESSION['security_error']
                    ) ?>
                </span>

            </div>

            <?php unset($_SESSION['security_error']); ?>

        <?php endif; ?>


        <!-- =================================================
             SETTINGS GRID
        ================================================== -->

        <section class="security-settings-grid">


            <!-- =============================================
                 PASSWORD POLICY
            ============================================== -->

            <form
                action="../../actions/admin/security/update-password-policy.php"
                method="POST"
                class="security-settings-card"
            >

                <h2>
                    Password Policy
                </h2>


                <!-- MINIMUM LENGTH -->

                <div class="security-setting-row">

                    <label for="passwordMinLength">
                        Minimum Length
                    </label>

                    <input
                        type="number"
                        id="passwordMinLength"
                        name="password_min_length"
                        min="8"
                        max="128"
                        value="<?= htmlspecialchars(
                            $settings['password_min_length']
                        ) ?>"
                        required
                    >

                </div>


                <!-- REQUIRE UPPERCASE -->

                <div class="security-setting-row">

                    <label for="passwordRequireUppercase">
                        Require Uppercase
                    </label>

                    <select
                        id="passwordRequireUppercase"
                        name="password_require_uppercase"
                    >

                        <option
                            value="1"
                            <?= (int) $settings[
                                'password_require_uppercase'
                            ] === 1 ? 'selected' : '' ?>
                        >
                            Yes
                        </option>

                        <option
                            value="0"
                            <?= (int) $settings[
                                'password_require_uppercase'
                            ] === 0 ? 'selected' : '' ?>
                        >
                            No
                        </option>

                    </select>

                </div>


                <!-- REQUIRE NUMBER -->

                <div class="security-setting-row">

                    <label for="passwordRequireNumber">
                        Require Number
                    </label>

                    <select
                        id="passwordRequireNumber"
                        name="password_require_number"
                    >

                        <option
                            value="1"
                            <?= (int) $settings[
                                'require_number'
                            ] === 1 ? 'selected' : '' ?>
                        >
                            Yes
                        </option>

                        <option
                            value="0"
                            <?= (int) $settings[
                                'require_number'
                            ] === 0 ? 'selected' : '' ?>
                        >
                            No
                        </option>

                    </select>

                </div>


                <!-- REQUIRE SPECIAL CHARACTER -->

                <div class="security-setting-row">

                    <label for="passwordRequireSpecial">
                        Require Special Char
                    </label>

                    <select
                        id="passwordRequireSpecial"
                        name="password_require_special"
                    >

                        <option
                            value="1"
                            <?= (int) $settings[
                                'require_special_character'
                            ] === 1 ? 'selected' : '' ?>
                        >
                            Yes
                        </option>

                        <option
                            value="0"
                            <?= (int) $settings[
                                'require_special_character'
                            ] === 0 ? 'selected' : '' ?>
                        >
                            No
                        </option>

                    </select>

                </div>


                <!-- PASSWORD EXPIRY -->

                <div class="security-setting-row">

                    <label for="passwordExpiryDays">
                        Password Expiry (days)
                    </label>

                    <input
                        type="number"
                        id="passwordExpiryDays"
                        name="password_expiry_days"
                        min="0"
                        max="365"
                        value="<?= htmlspecialchars(
                            $settings['require_uppercase']
                        ) ?>"
                    >

                </div>


                <button
                    type="submit"
                    class="security-save-button"
                >
                    Save Changes
                </button>

            </form>


            <!-- =============================================
                 SESSION & LOCKOUT
            ============================================== -->

            <form
                action="../../actions/admin/security/update-session-policy.php"
                method="POST"
                class="security-settings-card"
            >

                <h2>
                    Session &amp; Lockout
                </h2>


                <!-- SESSION TIMEOUT -->

                <div class="security-setting-row">

                    <label for="sessionTimeout">
                        Session Timeout (min)
                    </label>

                    <input
                        type="number"
                        id="sessionTimeout"
                        name="session_timeout_minutes"
                        min="1"
                        max="1440"
                        value="<?= htmlspecialchars(
                            $settings[
                                'session_timeout_minutes'
                            ]
                        ) ?>"
                        required
                    >

                </div>


                <!-- MAX FAILED ATTEMPTS -->

                <div class="security-setting-row">

                    <label for="maxLoginAttempts">
                        Max Failed Attempts
                    </label>

                    <input
                        type="number"
                        id="maxLoginAttempts"
                        name="max_login_attempts"
                        min="1"
                        max="20"
                        value="<?= htmlspecialchars(
                            $settings['max_login_attempts']
                        ) ?>"
                        required
                    >

                </div>


                <!-- LOCKOUT DURATION -->

                <div class="security-setting-row">

                    <label for="lockoutDuration">
                        Lockout Duration (min)
                    </label>

                    <input
                        type="number"
                        id="lockoutDuration"
                        name="lockout_duration_minutes"
                        min="1"
                        max="1440"
                        value="<?= htmlspecialchars(
                            $settings[
                                'lockout_minutes'
                            ]
                        ) ?>"
                        required
                    >

                </div>


                <!-- REMEMBER ME -->

                <div class="security-setting-row">

                    <label for="rememberMeDays">
                        Remember Me (days)
                    </label>

                    <input
                        type="number"
                        id="rememberMeDays"
                        name="remember_me_days"
                        min="1"
                        max="90"
                        value="<?= htmlspecialchars(
                            $settings['remember_me_days']
                        ) ?>"
                    >

                </div>


                <button
                    type="submit"
                    class="security-save-button"
                >
                    Save Changes
                </button>

            </form>


        </section>


        <!-- =================================================
             LOCKED ACCOUNTS
        ================================================== -->

        <section class="locked-accounts-section">


            <!-- =============================================
                 LOCKED ACCOUNTS HEADER
            ============================================== -->

            <div class="locked-accounts-heading">

                <div>

                    <h2>
                        Locked Accounts
                    </h2>

                    <p>

                        <span id="lockedAccountCount">
                            <?= $totalLockedAccounts ?>
                        </span>

                        <span id="lockedAccountLabel">
                            <?= $totalLockedAccounts === 1
                                ? 'account'
                                : 'accounts' ?>
                        </span>

                    </p>

                </div>

            </div>


            <!-- =============================================
                 LOCKED ACCOUNTS CARD
            ============================================== -->

            <div class="locked-accounts-card">


                <!-- =========================================
                     SEARCH + FILTERS
                ========================================== -->

                <div class="locked-toolbar">


                    <!-- SEARCH -->

                    <div class="locked-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="search"
                            id="lockedAccountSearch"
                            placeholder="Search locked accounts..."
                            autocomplete="off"
                        >

                    </div>


                    <!-- STATUS FILTER -->

                    <div class="locked-filter">

                        <i class="fa-solid fa-filter"></i>

                        <select id="lockedStatusFilter">

                            <option value="all">
                                All Status
                            </option>

                            <option value="locked">
                                Locked
                            </option>

                            <option value="temporary">
                                Temporary Lock
                            </option>

                        </select>

                        <i class="fa-solid fa-chevron-down"></i>

                    </div>


                </div>


                <!-- =========================================
                     TABLE
                ========================================== -->

                <div class="locked-table-wrapper">

                    <table class="locked-table">

                        <thead>

                            <tr>

                                <th>USER</th>

                                <th>EMAIL</th>

                                <th>FAILED ATTEMPTS</th>

                                <th>LOCKED UNTIL</th>

                                <th>STATUS</th>

                                <th>ACTIONS</th>

                            </tr>

                        </thead>


                        <tbody id="lockedAccountsBody">


                        <?php if (!empty($lockedAccounts)): ?>


                            <?php foreach ($lockedAccounts as $account): ?>


                                <?php

                                $fullName = trim(
                                    $account['first_name']
                                    . ' '
                                    . $account['last_name']
                                );

                                $initial = strtoupper(
                                    substr(
                                        $account['first_name'],
                                        0,
                                        1
                                    )
                                );


                                /* =========================
                                   DETERMINE LOCK TYPE
                                ========================== */

                                $lockType = 'locked';

                                if (
                                    !empty($account['locked_until'])
                                    &&
                                    strtotime(
                                        $account['locked_until']
                                    ) > time()
                                ) {

                                    $lockType = 'temporary';
                                }


                                /* =========================
                                   LOCKED UNTIL DISPLAY
                                ========================== */

                                $lockedUntilDisplay = '—';

                                if (!empty($account['locked_until'])) {

                                    $timestamp = strtotime(
                                        $account['locked_until']
                                    );

                                    if ($timestamp !== false) {

                                        $lockedUntilDisplay = date(
                                            'Y-m-d H:i',
                                            $timestamp
                                        );
                                    }
                                }


                                $searchValue = strtolower(
                                    $fullName . ' '
                                    . $account['email']
                                );

                                ?>


                                <tr
                                    class="locked-account-row"

                                    data-search="<?= htmlspecialchars(
                                        $searchValue
                                    ) ?>"

                                    data-status="<?= htmlspecialchars(
                                        $lockType
                                    ) ?>"
                                >


                                    <!-- USER -->

                                    <td>

                                        <div class="locked-user">

                                            <div class="locked-avatar">

                                                <?= htmlspecialchars(
                                                    $initial
                                                ) ?>

                                            </div>

                                            <div>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $fullName
                                                    ) ?>

                                                </strong>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- EMAIL -->

                                    <td>

                                        <span class="locked-email">

                                            <?= htmlspecialchars(
                                                $account['email']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- FAILED ATTEMPTS -->

                                    <td>

                                        <?= (int) (
                                            $account[
                                                'failed_login_attempts'
                                            ] ?? 0
                                        ) ?>

                                    </td>


                                    <!-- LOCKED UNTIL -->

                                    <td>

                                        <span class="locked-until">

                                            <?= htmlspecialchars(
                                                $lockedUntilDisplay
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <span
                                            class="
                                                locked-status
                                                locked-status-<?=
                                                    htmlspecialchars(
                                                        $lockType
                                                    )
                                                ?>
                                            "
                                        >

                                            <?= $lockType === 'temporary'
                                                ? 'TEMPORARY'
                                                : 'LOCKED' ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <form
                                            action="../../actions/admin/security/unlock-account.php"
                                            method="POST"
                                            class="unlock-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= (int) $account['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="unlock-account-button"
                                                data-user-name="<?= htmlspecialchars(
                                                    $fullName
                                                ) ?>"
                                            >
                                                Unlock Account
                                            </button>

                                        </form>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        <!-- NO RESULTS -->

                        <tr
                            id="noLockedAccountsRow"
                            class="no-locked-accounts"
                            <?= !empty($lockedAccounts)
                                ? 'hidden'
                                : '' ?>
                        >

                            <td colspan="6">

                                <i class="fa-solid fa-lock-open"></i>

                                <p>
                                    No locked accounts found.
                                </p>

                            </td>

                        </tr>


                        </tbody>

                    </table>

                </div>


            </div>


        </section>


    </main>

</div>


<script src="../../assets/js/sidemenu.js"></script>

<script src="../../assets/js/security_settings.js"></script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>