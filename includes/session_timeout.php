<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    return;
}

$sessionTimeoutPolicy = securityPolicy($conn);
?>
<script
    src="<?= BASE_URL ?>/assets/js/session_timeout.js"
    data-session-check-url="<?= BASE_URL ?>/actions/auth/session-status.php"
    data-login-url="<?= BASE_URL ?>/public/login.php"
    data-session-remaining-seconds="<?= securitySessionRemainingSeconds($sessionTimeoutPolicy) ?>"
    defer
></script>
