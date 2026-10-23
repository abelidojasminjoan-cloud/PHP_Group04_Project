<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/role-check.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../services/security-policy.php';

$admin = requirePureViaAdmin($conn);

$pageTitle = 'Security Settings';
$settings = securityPolicy($conn);
$csrfToken = securityCsrfToken();

$lockedAccounts = [];

$sql = "
    SELECT
        u.id,
        u.role_id,
        u.first_name,
        u.last_name,
        u.email,
        u.status,
        u.failed_login_attempts,
        u.locked_until
    FROM users u
    WHERE u.status = 'locked'
       OR u.locked_until IS NOT NULL
       OR u.failed_login_attempts >= " . (int) $settings['max_login_attempts'] . "
    ORDER BY u.first_name ASC, u.last_name ASC
";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $lockedAccounts[] = $row;
}

$totalLockedAccounts = count($lockedAccounts);

$adminFirstName = $admin['first_name'] ?? 'Admin';
$adminLastName = $admin['last_name'] ?? '';
$adminEmail = $admin['email'] ?? '';
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
        <?= pvHtml($pageTitle) ?> | PureVia Admin
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


<?php include __DIR__ . "/../../includes/sidemenu.php"; ?>


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
                    <?= pvHtml(
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
                    <?= pvHtml(
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
                id="passwordPolicyForm"
                action="../../actions/admin/security/update-password-policy.php"
                method="POST"
                class="security-settings-card password-policy-card"
            >
                <input type="hidden" name="csrf_token" value="<?= pvHtml($csrfToken) ?>">
                <h2>Password Policy</h2>

                <div class="password-policy-row">
                    <div class="password-policy-label">
                        <label for="passwordMinLength">Minimum password length</label>
                        <p id="passwordLengthHelp">Allowed range: 8–64 characters</p>
                    </div>
                    <div class="password-policy-value">
                        <input type="number" id="passwordMinLength" name="password_min_length" min="8" max="64" step="1" value="<?= (int) $settings['password_min_length'] ?>" aria-describedby="passwordLengthHelp" required>
                        <span>characters</span>
                    </div>
                </div>

                <div class="password-policy-row">
                    <div class="password-policy-label">
                        <label for="passwordExpiryDays">Password expiration</label>
                        <p id="passwordExpiryHelp">Enter 0 to disable expiration</p>
                    </div>
                    <div class="password-policy-value">
                        <input type="number" id="passwordExpiryDays" name="password_expiry_days" min="0" max="365" step="1" value="<?= (int) $settings['password_expiry_days'] ?>" aria-describedby="passwordExpiryHelp" required>
                        <span>days</span>
                    </div>
                </div>

                <fieldset class="password-complexity">
                    <legend>Password complexity</legend>
                    <p>Choose Yes to require a character type, then set its minimum count.</p>
                    <?php foreach ([
                        'uppercase' => ['Capital letters', 'A–Z', 'uppercase'],
                        'lowercase' => ['Small letters', 'a–z', 'lowercase'],
                        'number' => ['Numbers', '0–9', 'number'],
                        'special_character' => ['Special characters', '! @ # $ %', 'special'],
                    ] as $type => [$label, $example, $inputName]): ?>
                        <div class="password-complexity-row" data-complexity="<?= pvHtml($type) ?>">
                            <div class="password-policy-label">
                                <span class="password-character-title" id="passwordLabel_<?= pvHtml($type) ?>"><?= pvHtml($label) ?></span>
                                <p><?= pvHtml($example) ?></p>
                            </div>
                            <div class="password-complexity-controls">
                                <label>
                                    <span>Required?</span>
                                    <select name="password_require_<?= pvHtml($inputName) ?>" aria-labelledby="passwordLabel_<?= pvHtml($type) ?>" data-requirement>
                                        <option value="1" <?= $settings['require_' . $type] ? 'selected' : '' ?>>Yes</option>
                                        <option value="0" <?= !$settings['require_' . $type] ? 'selected' : '' ?>>No</option>
                                    </select>
                                </label>
                                <label>
                                    <span>Minimum count</span>
                                    <input type="number" name="password_min_<?= pvHtml($type) ?>" min="1" max="64" step="1" value="<?= (int) $settings['min_' . $type] ?>" aria-label="<?= pvHtml($label) ?> minimum count" data-minimum <?= $settings['require_' . $type] ? 'required' : 'disabled' ?>>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </fieldset>

                <div class="password-policy-summary" role="status" aria-live="polite">
                    <strong>Current requirement</strong>
                    <p id="passwordPolicySummary"><?= pvHtml(securityPasswordPolicySummary($settings)) ?></p>
                </div>
                <button type="submit" class="security-save-button">Save Password Policy</button>
            </form>


            <!-- =============================================
                 SESSION & LOCKOUT
            ============================================== -->

            <form
                action="../../actions/admin/security/update-session-policy.php"
                method="POST"
                class="security-settings-card"
            >
                <input type="hidden" name="csrf_token" value="<?= pvHtml($csrfToken) ?>">

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
                        value="<?= (int) $settings[
                                'session_timeout_minutes'
                            ] ?>"
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
                        value="<?= (int) $settings['max_login_attempts'] ?>"
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
                        min="0"
                        max="1440"
                        value="0"
                        readonly
                        title="Accounts remain locked until an administrator unlocks them."
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
                        min="0"
                        max="90"
                        value="0"
                        readonly
                        title="Remember Me is not enabled."
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
                                $lockedUntilDisplay = 'Until manually unlocked';

                                $searchValue = strtolower(
                                    $fullName . ' '
                                    . $account['email']
                                );

                                ?>


                                <tr
                                    class="locked-account-row"

                                    data-search="<?= pvHtml(
                                        $searchValue
                                    ) ?>"

                                    data-status="<?= pvHtml(
                                        $lockType
                                    ) ?>"
                                >


                                    <!-- USER -->

                                    <td>

                                        <div class="locked-user">

                                            <div class="locked-avatar">

                                                <?= pvHtml(
                                                    $initial
                                                ) ?>

                                            </div>

                                            <div>

                                                <strong>

                                                    <?= pvHtml(
                                                        $fullName
                                                    ) ?>

                                                </strong>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- EMAIL -->

                                    <td>

                                        <span class="locked-email">

                                            <?= pvHtml(
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

                                            <?= pvHtml(
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
                                                    pvHtml(
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
                                            <input type="hidden" name="csrf_token" value="<?= pvHtml($csrfToken) ?>">

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?= (int) $account['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="unlock-account-button"
                                                data-user-name="<?= pvHtml(
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


<script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/sidemenu.js"></script>

<script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/security_settings.js"></script>


<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>