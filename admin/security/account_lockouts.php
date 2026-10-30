<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/role-check.php';
require_once __DIR__ . '/../../includes/ip-address.php';
requirePureViaAdmin($conn);
$pageTitle='Account Lockout Logs';
$policy=securityPolicy($conn);
$rows=$conn->query("SELECT l.*,u.status AS current_status,u.failed_login_attempts,u.locked_until FROM account_lockout_logs l
    LEFT JOIN users u ON u.id=l.user_id ORDER BY l.locked_at DESC,l.id DESC")->fetch_all(MYSQLI_ASSOC);
$browsers=[];
foreach($rows as $row) $browsers[$row['browser']]=$row['browser'];
natcasesort($browsers);
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Account Lockout Logs | PureVia Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Cormorant+Garamond:wght@600;700&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/sidemenu.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/login_attempts.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/account-lockouts.css">
</head><body>
<?php include __DIR__ . '/../../includes/sidemenu.php'; ?>
<div class="admin-layout">
<header class="admin-topbar"><p class="admin-topbar-title">Account Lockout Logs</p></header>
<main class="login-attempts-main account-lockouts-main">
<section class="login-attempts-page-header"><div class="login-attempts-heading">
<h1>Account Lockout Logs</h1>
<p class="lockout-subtitle">Review account lockouts caused by repeated failed sign-in attempts.</p>
<p aria-live="polite"><span id="loginAttemptCount"><?= count($rows) ?></span> <span id="loginAttemptLabel"><?= count($rows)===1?'record':'records' ?></span></p>
</div></section>
<p class="lockout-note">These records are read-only. Each new lockout is recorded separately, including repeat lockouts. Older records may not include the number of failed attempts.</p>
<section class="login-attempts-card" aria-label="Account lockout records">
<div class="login-attempts-toolbar">
<div class="login-attempts-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" id="loginAttemptSearch" placeholder="Search account lockouts..." aria-label="Search lockouts by name, email, IP, browser or date" autocomplete="off"></div>
<div class="login-attempts-filter"><i class="fa-solid fa-filter filter-leading-icon" aria-hidden="true"></i>
<select id="loginResultFilter" aria-label="Filter by current account lock status"><option value="all">All Accounts</option><option value="locked">Currently Locked</option><option value="unlocked">Currently Unlocked</option><option value="removed">Removed Accounts</option></select>
<i class="fa-solid fa-chevron-down filter-arrow" aria-hidden="true"></i></div>
<div class="login-attempts-filter browser-filter"><i class="fa-solid fa-globe filter-leading-icon" aria-hidden="true"></i>
<select id="loginBrowserFilter" aria-label="Filter by browser"><option value="all">All Browsers</option><?php foreach($browsers as $browser): ?><option value="<?= pvHtml(strtolower($browser)) ?>"><?= pvHtml($browser) ?></option><?php endforeach; ?></select>
<i class="fa-solid fa-chevron-down filter-arrow" aria-hidden="true"></i></div>
</div>
<div class="login-attempts-table-wrapper"><table class="login-attempts-table"><thead><tr><th>NAME</th><th>NUMBER OF TRIES</th><th>DATE &amp; TIME</th><th>IP ADDRESS</th><th>BROWSER</th></tr></thead>
<tbody id="loginAttemptsBody">
<?php foreach($rows as $row):
    $isLocked=$row['current_status']==='locked'||$row['locked_until']!==null||(int)$row['failed_login_attempts']>=(int)$policy['max_login_attempts'];
    $state=$row['current_status']===null?'removed':($isLocked?'locked':'unlocked');
    $search=strtolower(implode(' ',[$row['name_snapshot'],$row['email_snapshot'],$row['failed_attempts']??'', $row['locked_at'],$row['ip_address']??'',pureViaIpLabel($row['ip_address']),$row['browser']]));
?>
<tr class="login-attempt-row" data-search="<?= pvHtml($search) ?>" data-result="<?= $state ?>" data-browser="<?= pvHtml(strtolower($row['browser'])) ?>">
<td><strong class="lockout-name"><?= pvHtml($row['name_snapshot']?:'Unknown account') ?></strong><span class="lockout-email"><?= pvHtml($row['email_snapshot']) ?></span></td>
<td><?php if($row['failed_attempts']!==null): ?><span class="login-result-badge login-result-failed"><?= (int)$row['failed_attempts'] ?> FAILED</span><?php else: ?><span class="lockout-unknown">Not recorded</span><?php endif; ?></td>
<td><time class="login-attempt-date" datetime="<?= pvHtml(str_replace(' ','T',$row['locked_at'])) ?>"><?= pvHtml(date('Y-m-d H:i',strtotime($row['locked_at']))) ?></time></td>
<td><span class="login-ip-address"><?= pvHtml(pureViaIpLabel($row['ip_address'])) ?></span></td><td><?= pvHtml($row['browser']) ?></td>
</tr>
<?php endforeach; ?>
<tr id="noLoginAttemptsRow" class="no-login-attempts-row" <?= $rows?'hidden':'' ?>><td colspan="5"><div class="no-login-attempts-message"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><p>No account lockout records found.</p></div></td></tr>
</tbody></table></div></section></main></div>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/sidemenu.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/login_attempts.js"></script>
</body></html>
