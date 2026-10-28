<?php

/* =========================================================
   PUREVIA ADMIN - AUDIT LOGS
========================================================= */

require_once __DIR__ . '/../../includes/role-check.php';
require_once __DIR__ . '/../../includes/ip-address.php';
requirePureViaAdmin($conn);

$pageTitle = 'Audit Logs';


/* =========================================================
   LOAD AUDIT LOGS

   LEFT JOIN is important because audit_logs.user_id
   is allowed to be NULL.
========================================================= */

$auditLogs = [];

$sql = "
    SELECT
        al.id,
        al.user_id,
        al.action,
        al.module,
        al.record_id,
        al.description,
        al.ip_address,
        al.browser,
        al.created_at,

        u.first_name,
        u.last_name,
        u.email

    FROM audit_logs AS al

    LEFT JOIN users AS u
        ON al.user_id = u.id

    ORDER BY
        al.created_at DESC,
        al.id DESC
";

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $auditLogs[] = $row;
    }
}

$totalLogs = count($auditLogs);


/* =========================================================
   GET UNIQUE ACTIONS
========================================================= */

$actions = [];

foreach ($auditLogs as $log) {

    $action = trim(
        (string) ($log['action'] ?? '')
    );

    if ($action !== '') {

        $actions[strtolower($action)] = $action;
    }
}

ksort($actions);


/* =========================================================
   GET UNIQUE MODULES
========================================================= */

$modules = [];

foreach ($auditLogs as $log) {

    $module = trim(
        (string) ($log['module'] ?? '')
    );

    if ($module !== '') {

        $modules[strtolower($module)] = $module;
    }
}

ksort($modules);

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


    <!-- =====================================================
         GOOGLE FONTS
    ====================================================== -->

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


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../../assets/css/sidemenu.css"
    >

    <link
        rel="stylesheet"
        href="../../assets/css/audit_logs.css?v=<?= filemtime(__DIR__ . '/../../assets/css/audit_logs.css') ?>"
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
            Audit Logs
        </p>

    </header>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="audit-main">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <section class="audit-page-header">

            <div class="audit-heading">

                <h1>
                    Audit Logs
                </h1>

                <p>

                    <span id="auditRecordCount">
                        <?= $totalLogs ?>
                    </span>

                    <span id="auditRecordLabel">
                        <?= $totalLogs === 1
                            ? 'record'
                            : 'records' ?>
                    </span>

                </p>

            </div>

        </section>


        <!-- =================================================
             READ ONLY NOTICE
        ================================================== -->

        <div class="audit-readonly-notice">

            <i class="fa-solid fa-lock"></i>

            <span>
                Audit logs are read-only and cannot be edited or deleted.
            </span>

        </div>


        <!-- =================================================
             AUDIT LOG CARD
        ================================================== -->

        <section class="audit-card">


            <!-- =============================================
                 SEARCH + FILTERS
            ============================================== -->

            <div class="audit-toolbar">


                <!-- SEARCH -->

                <div class="audit-search">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="search"
                        id="auditSearch"
                        placeholder="Search audit logs..."
                        autocomplete="off"
                    >

                </div>


                <!-- ACTION FILTER -->

                <div class="audit-filter-dropdown">

                    <i
                        class="
                            fa-solid
                            fa-filter
                            audit-filter-icon
                        "
                    ></i>

                    <select
                        id="auditActionFilter"
                        class="audit-filter-select"
                    >

                        <option value="all">
                            All Actions
                        </option>

                        <?php foreach ($actions as $value => $label): ?>

                            <option
                                value="<?= pvHtml($value) ?>"
                            >
                                <?= pvHtml(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            strtolower($label)
                                        )
                                    )
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <i
                        class="
                            fa-solid
                            fa-chevron-down
                            audit-filter-arrow
                        "
                    ></i>

                </div>


                <!-- MODULE FILTER -->

                <div class="audit-filter-dropdown">

                    <i
                        class="
                            fa-solid
                            fa-layer-group
                            audit-filter-icon
                        "
                    ></i>

                    <select
                        id="auditModuleFilter"
                        class="audit-filter-select"
                    >

                        <option value="all">
                            All Modules
                        </option>

                        <?php foreach ($modules as $value => $label): ?>

                            <option
                                value="<?= pvHtml($value) ?>"
                            >
                                <?= pvHtml(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            strtolower($label)
                                        )
                                    )
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <i
                        class="
                            fa-solid
                            fa-chevron-down
                            audit-filter-arrow
                        "
                    ></i>

                </div>


            </div>


            <!-- =============================================
                 TABLE
            ============================================== -->

            <div class="audit-table-wrapper">

                <table class="audit-table">


                    <!-- TABLE HEADER -->

                    <thead>

                        <tr>

                            <th>
                                ACTION
                            </th>

                            <th>
                                MODULE
                            </th>

                            <th>
                                USER
                            </th>

                            <th>
                                DATE &amp; TIME
                            </th>

                            <th>IP ADDRESS</th>
                            <th>BROWSER</th>
                            <th>
                                DETAILS
                            </th>

                        </tr>

                    </thead>


                    <!-- TABLE BODY -->

                    <tbody id="auditTableBody">


                    <?php if (!empty($auditLogs)): ?>


                        <?php foreach ($auditLogs as $log): ?>


                            <?php

                            /* =================================
                               ACTION
                            ================================= */

                            $action =
                                strtolower(
                                    trim(
                                        (string) (
                                            $log['action']
                                            ?? 'unknown'
                                        )
                                    )
                                );

                            $actionLabel =
                                strtoupper(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $action
                                    )
                                );


                            /* =================================
                               MODULE
                            ================================= */

                            $module =
                                trim(
                                    (string) (
                                        $log['module']
                                        ?? 'System'
                                    )
                                );

                            $moduleLabel =
                                ucwords(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $module
                                    )
                                );


                            /* =================================
                               USER
                            ================================= */

                            if (
                                !empty($log['first_name'])
                                ||
                                !empty($log['last_name'])
                            ) {

                                $userName = trim(
                                    ($log['first_name'] ?? '')
                                    . ' '
                                    . ($log['last_name'] ?? '')
                                );

                            } elseif (!empty($log['email'])) {

                                $userName =
                                    $log['email'];

                            } elseif (!empty($log['user_id'])) {

                                $userName =
                                    'User #' . $log['user_id'];

                            } else {

                                $userName =
                                    'System';
                            }


                            /* =================================
                               DATE
                            ================================= */

                            $dateDisplay = '—';

                            if (!empty($log['created_at'])) {

                                $timestamp =
                                    strtotime(
                                        $log['created_at']
                                    );

                                if ($timestamp !== false) {

                                    $dateDisplay =
                                        date(
                                            'Y-m-d H:i',
                                            $timestamp
                                        );
                                }
                            }


                            /* =================================
                               DETAILS
                            ================================= */

                            $details =
                                trim(
                                    (string) (
                                        $log['description']
                                        ?? ''
                                    )
                                );

                            if ($details === '') {

                                $details = '—';
                            }


                            /* =================================
                               SEARCHABLE TEXT
                            ================================= */

                            $searchValue =
                                strtolower(
                                    $action . ' '
                                    . $module . ' '
                                    . $userName . ' '
                                    . ($log['email'] ?? '') . ' '
                                    . $dateDisplay . ' '
                                    . $details . ' '
                                    . ($log['record_id'] ?? '') . ' '
                                    . ($log['ip_address'] ?? '') . ' ' . ($log['browser'] ?? '')
                                );

                            ?>


                            <tr
                                class="audit-row"

                                data-search="<?= pvHtml(
                                    $searchValue
                                ) ?>"

                                data-action="<?= pvHtml(
                                    $action
                                ) ?>"

                                data-module="<?= pvHtml(
                                    strtolower($module)
                                ) ?>"
                            >


                                <!-- ACTION -->

                                <td>

                                    <span
                                        class="
                                            audit-action-badge
                                            audit-action-<?=
                                                pvHtml(
                                                    preg_replace(
                                                        '/[^a-z0-9_-]/',
                                                        '',
                                                        $action
                                                    )
                                                )
                                            ?>
                                        "
                                    >

                                        <?= pvHtml(
                                            $actionLabel
                                        ) ?>

                                    </span>

                                </td>


                                <!-- MODULE -->

                                <td>

                                    <span class="audit-module">

                                        <?= pvHtml(
                                            $moduleLabel
                                        ) ?>

                                    </span>

                                </td>


                                <!-- USER -->

                                <td>

                                    <span class="audit-user">

                                        <?= pvHtml(
                                            $userName
                                        ) ?>

                                    </span>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <span class="audit-date">

                                        <?= pvHtml(
                                            $dateDisplay
                                        ) ?>

                                    </span>

                                </td>


                                <td><span class="audit-ip"><?= !empty($log['ip_address']) ? pvHtml(pureViaIpLabel($log['ip_address'])) : '&mdash;' ?></span></td>
                                <td><span class="audit-browser"><?= !empty($log['browser']) ? pvHtml($log['browser']) : '&mdash;' ?></span></td>
                                <!-- DETAILS -->

                                <td>

                                    <span class="audit-details">

                                        <?= pvHtml(
                                            $details
                                        ) ?>

                                    </span>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    <!-- =====================================
                         NO RESULTS
                    ====================================== -->

                    <tr
                        id="noAuditResults"
                        class="no-audit-row"
                        <?= !empty($auditLogs)
                            ? 'hidden'
                            : '' ?>
                    >

                        <td colspan="7">

                            <div class="no-audit-message">

                                <i
                                    class="
                                        fa-solid
                                        fa-clipboard-list
                                    "
                                ></i>

                                <p>
                                    No audit logs found.
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


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/sidemenu.js"></script>

<script nonce="<?= pvScriptNonce() ?>" src="../../assets/js/audit_logs.js"></script>


<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>