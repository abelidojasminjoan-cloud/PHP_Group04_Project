<?php

/* =========================================================
   PUREVIA ADMIN - LOGIN ATTEMPTS
========================================================= */

require_once __DIR__ . '/../../includes/role-check.php';
require_once __DIR__ . '/../../includes/ip-address.php';
requirePureViaAdmin($conn);

$pageTitle = 'Login Attempts';


/* =========================================================
   LOAD LOGIN ATTEMPTS
========================================================= */

$loginAttempts = [];

$query = "
    SELECT
        la.id,
        la.user_id,
        la.email_attempted,
        la.ip_address,
        la.browser,
        la.successful,
        la.failure_reason,
        la.attempted_at
    FROM login_attempts AS la
    ORDER BY la.attempted_at DESC, la.id DESC
";

$result = $conn->query($query);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $loginAttempts[] = $row;
    }
}

$totalAttempts = count($loginAttempts);

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

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/sidemenu.css"
    >

    <link
        rel="stylesheet"
        href="../../assets/css/login_attempts.css"
    >

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
            Login Attempts
        </p>

    </header>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="login-attempts-main">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <section class="login-attempts-page-header">

            <div class="login-attempts-heading">

                <h1>
                    Login Attempts
                </h1>

                <p>

                    <span id="loginAttemptCount" aria-live="polite">
                        <?= $totalAttempts ?>
                    </span>

                    <span id="loginAttemptLabel">
                        <?= $totalAttempts === 1
                            ? 'record'
                            : 'records' ?>
                    </span>

                </p>

            </div>

        </section>


        <!-- =================================================
             LOGIN ATTEMPTS CARD
        ================================================== -->

        <section class="login-attempts-card">


            <!-- =============================================
                 SEARCH + FILTERS
            ============================================== -->

            <div class="login-attempts-toolbar">


                <!-- SEARCH -->

                <div class="login-attempts-search">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="search"
                        id="loginAttemptSearch" aria-label="Search login attempts"
                        placeholder="Search login attempts..."
                        autocomplete="off"
                    >

                </div>


                <!-- RESULT FILTER -->

                <div class="login-attempts-filter">

                    <i class="fa-solid fa-filter filter-leading-icon"></i>

                    <select id="loginResultFilter" aria-label="Filter login attempts by result">

                        <option value="all">
                            All Results
                        </option>

                        <option value="success">
                            Success
                        </option>

                        <option value="failed">
                            Failed
                        </option>

                    </select>

                    <i class="fa-solid fa-chevron-down filter-arrow"></i>

                </div>


                <!-- BROWSER FILTER -->

                <div class="login-attempts-filter browser-filter">

                    <i class="fa-solid fa-globe filter-leading-icon"></i>

                    <select id="loginBrowserFilter" aria-label="Filter login attempts by browser">

                        <option value="all">
                            All Browsers
                        </option>

                        <?php

                        $browsers = [];

                        foreach ($loginAttempts as $attempt) {

                            $browser = trim(
                                (string) ($attempt['browser'] ?? '')
                            );

                            if ($browser !== '') {
                                $browsers[$browser] = $browser;
                            }
                        }

                        ksort($browsers);

                        ?>

                        <?php foreach ($browsers as $browser): ?>

                            <option
                                value="<?= pvHtml(
                                    strtolower($browser)
                                ) ?>"
                            >
                                <?= pvHtml($browser) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <i class="fa-solid fa-chevron-down filter-arrow"></i>

                </div>


            </div>


            <!-- =============================================
                 TABLE
            ============================================== -->

            <div class="login-attempts-table-wrapper">

                <table class="login-attempts-table">

                    <thead>

                        <tr>

                            <th>EMAIL</th>

                            <th>RESULT</th>

                            <th>IP ADDRESS</th>

                            <th>BROWSER</th>

                            <th>DATE &amp; TIME</th>

                            <th>REASON</th>

                        </tr>

                    </thead>


                    <tbody id="loginAttemptsBody">


                    <?php if (!empty($loginAttempts)): ?>


                        <?php foreach ($loginAttempts as $attempt): ?>


                            <?php

                            $successful =
                                (int) $attempt['successful'] === 1;

                            $resultValue =
                                $successful
                                    ? 'success'
                                    : 'failed';

                            $resultLabel =
                                $successful
                                    ? 'SUCCESS'
                                    : 'FAILED';


                            $email =
                                $attempt['email_attempted']
                                ?? 'Unknown';


                            $ipAddress =
                                !empty($attempt['ip_address'])
                                    ? $attempt['ip_address']
                                    : '—';


                            $browser =
                                !empty($attempt['browser'])
                                    ? $attempt['browser']
                                    : 'Unknown';


                            $reason =
                                $successful
                                    ? '—'
                                    : (
                                        !empty(
                                            $attempt['failure_reason']
                                        )
                                            ? $attempt[
                                                'failure_reason'
                                            ]
                                            : 'Login failed'
                                    );


                            $dateDisplay = '—';

                            if (!empty($attempt['attempted_at'])) {

                                $timestamp = strtotime(
                                    $attempt['attempted_at']
                                );

                                if ($timestamp !== false) {

                                    $dateDisplay = date(
                                        'Y-m-d H:i',
                                        $timestamp
                                    );
                                }
                            }


                            $searchValue = strtolower(
                                $email . ' '
                                . $ipAddress . ' ' . pureViaIpLabel($ipAddress) . ' '
                                . $browser . ' '
                                . $resultValue . ' '
                                . $reason . ' '
                                . $dateDisplay
                            );

                            ?>


                            <tr
                                class="login-attempt-row"

                                data-search="<?= pvHtml(
                                    $searchValue
                                ) ?>"

                                data-result="<?= pvHtml(
                                    $resultValue
                                ) ?>"

                                data-browser="<?= pvHtml(
                                    strtolower($browser)
                                ) ?>"
                            >


                                <!-- EMAIL -->

                                <td>

                                    <span class="login-attempt-email">

                                        <?= pvHtml($email) ?>

                                    </span>

                                </td>


                                <!-- RESULT -->

                                <td>

                                    <span
                                        class="
                                            login-result-badge
                                            login-result-<?=
                                                pvHtml(
                                                    $resultValue
                                                )
                                            ?>
                                        "
                                    >

                                        <?= $resultLabel ?>

                                    </span>

                                </td>


                                <!-- IP ADDRESS -->

                                <td>

                                    <span class="login-ip-address">

                                        <?= pvHtml(pureViaIpLabel($ipAddress)) ?>

                                    </span>

                                </td>


                                <!-- BROWSER -->

                                <td>

                                    <div class="login-browser">

                                        <i class="fa-regular fa-window-maximize"></i>

                                        <span>

                                            <?= pvHtml(
                                                $browser
                                            ) ?>

                                        </span>

                                    </div>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <span class="login-attempt-date">

                                        <?= pvHtml(
                                            $dateDisplay
                                        ) ?>

                                    </span>

                                </td>


                                <!-- REASON -->

                                <td>

                                    <span
                                        class="<?= $successful
                                            ? 'login-reason-success'
                                            : 'login-reason-failed' ?>"
                                    >

                                        <?= pvHtml(
                                            $reason
                                        ) ?>

                                    </span>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    <!-- NO RESULTS -->

                    <tr
                        id="noLoginAttemptsRow"
                        class="no-login-attempts-row"
                        <?= !empty($loginAttempts)
                            ? 'hidden'
                            : '' ?>
                    >

                        <td colspan="6">

                            <div class="no-login-attempts-message">

                                <i class="fa-solid fa-shield-halved"></i>

                                <p>
                                    No login attempts found.
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


<!-- JAVASCRIPT -->

<script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/sidemenu.js"></script>

<script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/login_attempts.js"></script>


<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>