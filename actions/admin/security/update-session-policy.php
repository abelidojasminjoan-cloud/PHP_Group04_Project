<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../includes/role-check.php';
require_once __DIR__ . '/../../../includes/csrf.php';
require_once __DIR__ . '/../../../services/security-policy.php';

requirePureViaAdmin($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verifySecurityCsrf();

$timeout = filter_var(
    $_POST['session_timeout_minutes'] ?? null,
    FILTER_VALIDATE_INT
);

$maxAttempts = filter_var(
    $_POST['max_login_attempts'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $timeout === false || $timeout === null || $timeout < 1 || $timeout > 1440 ||
    $maxAttempts === false || $maxAttempts === null || $maxAttempts < 1 ||
    $maxAttempts > 20
) {
    securityRedirect('Invalid session or lockout settings.', false);
}

try {
    $conn->begin_transaction();
    $settingId = securitySettingsId($conn);

    $stmt = $conn->prepare(
        "UPDATE security_settings
         SET session_timeout_minutes = ?,
             max_login_attempts = ?
         WHERE id = ?"
    );

    $stmt->bind_param(
        'iii',
        $timeout,
        $maxAttempts,
        $settingId
    );

    $stmt->execute();
    $stmt->close();

    securityAudit(
        $conn,
        'update_session_policy',
        'Administrator updated session timeout and login lockout settings.',
        $settingId
    );

    $conn->commit();

    securityRedirect('Session and lockout settings updated.');
} catch (Throwable $e) {
    $conn->rollback();
    error_log($e->getMessage());
    securityRedirect('Unable to save session settings.', false);
}
