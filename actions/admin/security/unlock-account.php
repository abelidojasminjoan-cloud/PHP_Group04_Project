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

$userId = filter_var(
    $_POST['user_id'] ?? null,
    FILTER_VALIDATE_INT
);

if ($userId === false || $userId === null || $userId <= 0) {
    securityRedirect('Invalid user account.', false);
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare(
        'SELECT id, role_id, status, email_verified_at, failed_login_attempts, locked_until
         FROM users
         WHERE id = ?
         FOR UPDATE'
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $conn->rollback();
        securityRedirect('Account not found.', false);
    }

    $policy = securityPolicy($conn);
    if (!isPureViaLoginLocked($conn, $user, $policy)) {
        $conn->rollback();
        securityRedirect('This account is not locked.', false);
    }

    $stmt = $conn->prepare(
        "UPDATE users
         SET status = CASE
                 WHEN status = 'locked' AND role_id = 3 AND email_verified_at IS NULL THEN 'pending'
                 WHEN status = 'locked' THEN 'active'
                 ELSE status
             END,
             failed_login_attempts = 0,
             locked_until = NULL
         WHERE id = ?"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException('Account unlock failed.');
    }

    $stmt->close();

    securityAudit(
        $conn,
        'unlock_account',
        'Administrator manually unlocked a user account.',
        $userId
    );

    $conn->commit();

    securityRedirect('Account unlocked successfully.');
} catch (Throwable $e) {
    $conn->rollback();
    error_log($e->getMessage());
    securityRedirect('Unable to unlock account.', false);
}
