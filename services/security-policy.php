<?php
declare(strict_types=1);

require_once __DIR__ . '/AuditLogger.php';

function securityPolicy(mysqli $conn): array
{
    $defaults = [
        'password_min_length' => 12,
        'require_uppercase' => 1,
        'require_lowercase' => 1,
        'require_number' => 1,
        'require_special_character' => 1,
        'min_uppercase' => 1,
        'min_lowercase' => 1,
        'min_number' => 1,
        'min_special_character' => 1,
        'password_expiry_days' => 0,
        'remember_me_days' => 0,
        'session_timeout_minutes' => 30,
        'max_login_attempts' => 3,
        'lockout_minutes' => 15,
    ];

    $result = $conn->query(
        'SELECT * FROM security_settings ORDER BY id ASC LIMIT 1'
    );

    $row = $result->fetch_assoc();

    if (!$row) {
        return $defaults;
    }

    foreach ($defaults as $key => $value) {
        if (isset($row[$key])) {
            $defaults[$key] = (int) $row[$key];
        }
    }

    $defaults['password_min_length'] = max(8, min(64, $defaults['password_min_length']));
    $defaults['password_expiry_days'] = max(0, min(365, $defaults['password_expiry_days']));
    foreach (['uppercase', 'lowercase', 'number', 'special_character'] as $type) {
        $defaults['min_' . $type] = max(1, min(64, $defaults['min_' . $type]));
    }
    $defaults['session_timeout_minutes'] = max(1, min(1440, $defaults['session_timeout_minutes']));
    $defaults['max_login_attempts'] = max(1, min(20, $defaults['max_login_attempts']));

    return $defaults;
}

function enforcePureViaSessionTimeout(array $policy, bool $refreshActivity = true): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $timeout = (int) $policy['session_timeout_minutes'] * 60;
    if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] >= $timeout) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    if ($refreshActivity) {
        $_SESSION['last_activity'] = time();
    }
}

function securitySessionRemainingSeconds(array $policy): int
{
    return max(0, (int) ($_SESSION['last_activity'] ?? time())
        + (int) $policy['session_timeout_minutes'] * 60 - time());
}

// Call inside the settings update transaction. New installations have no row yet.
function securitySettingsId(mysqli $conn): int
{
    $row = $conn->query(
        'SELECT id FROM security_settings ORDER BY id ASC LIMIT 1 FOR UPDATE'
    )->fetch_assoc();

    if ($row) {
        return (int) $row['id'];
    }

    $conn->query('INSERT INTO security_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id = id');
    return 1;
}

function securityRedirect(string $message, bool $success = true): never
{
    $_SESSION[$success ? 'security_success' : 'security_error'] =
        $message;

    header('Location: ' . BASE_URL . '/admin/security/settings.php');
    exit;
}

function securityPasswordRequirements(array $policy): array
{
    $rules = ['length' => 'At least ' . $policy['password_min_length'] . ' characters'];
    foreach ([
        'uppercase' => ['uppercase', 'uppercase letter'],
        'lowercase' => ['lowercase', 'lowercase letter'],
        'number' => ['number', 'number'],
        'symbol' => ['special_character', 'special character'],
    ] as $key => [$type, $label]) {
        if ($policy['require_' . $type]) {
            $count = securityPasswordRuleMinimum($policy, $key);
            $rules[$key] = 'At least ' . $count . ' ' . $label . ($count === 1 ? '' : 's');
        }
    }
    return $rules;
}

function securityPasswordRuleMinimum(array $policy, string $key): int
{
    if ($key === 'length') {
        return (int) $policy['password_min_length'];
    }
    $type = $key === 'symbol' ? 'special_character' : $key;
    return max(1, (int) ($policy['min_' . $type] ?? 1));
}

function securityPasswordPolicySummary(array $policy): string
{
    $parts = ['At least ' . $policy['password_min_length'] . ' characters'];
    foreach (['uppercase' => 'capital', 'lowercase' => 'small', 'number' => 'number', 'special_character' => 'special'] as $type => $label) {
        if ($policy['require_' . $type]) {
            $parts[] = (int) $policy['min_' . $type] . ' ' . $label;
        }
    }
    $days = (int) $policy['password_expiry_days'];
    $parts[] = $days === 0 ? 'does not expire' : 'expires every ' . $days . ($days === 1 ? ' day' : ' days');
    return implode(' · ', $parts);
}

function securityPasswordIsExpired(array $user, array $policy): bool
{
    $days = (int) $policy['password_expiry_days'];
    $changed = $user['password_changed_at'] ?? $user['created_at'] ?? null;
    if ($days <= 0 || !$changed) {
        return false;
    }
    $changedAt = strtotime((string) $changed);
    return $changedAt !== false && time() >= $changedAt + $days * 86400;
}

function enforcePureViaPasswordExpiry(mysqli $conn, array $policy): void
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if (PHP_SAPI === 'cli' || $userId <= 0 || (int) $policy['password_expiry_days'] === 0) {
        return;
    }
    // This route remains accessible so an expired password can actually be changed.
    if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__DIR__ . '/../account/renew-password.php')) {
        return;
    }
    $stmt = $conn->prepare('SELECT password_changed_at, created_at FROM users WHERE id = ? AND status = \'active\'');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($user && securityPasswordIsExpired($user, $policy)) {
        header('Location: ' . BASE_URL . '/account/renew-password.php', true, 303);
        exit;
    }
}

function validatePureViaPassword(
    string $password,
    array $policy
): array {
    $errors = [];

    if (mb_strlen($password, 'UTF-8') < $policy['password_min_length']) {
        $errors[] = 'Password must contain at least ' . $policy['password_min_length'] . ' characters.';
    }

    if (strlen($password) > 72 || str_contains($password, "\0")) {
        $errors[] = 'Password must not exceed 72 bytes or contain null characters.';
    }

    foreach ([
        'uppercase' => ['/[A-Z]/', 'uppercase letter'],
        'lowercase' => ['/[a-z]/', 'lowercase letter'],
        'number' => ['/[0-9]/', 'number'],
        'special_character' => ['/[^A-Za-z0-9]/u', 'special character'],
    ] as $type => [$pattern, $label]) {
        $count = securityPasswordRuleMinimum($policy, $type);
        if ($policy['require_' . $type] && preg_match_all($pattern, $password) < $count) {
            $errors[] = 'Password needs at least ' . $count . ' ' . $label . ($count === 1 ? '.' : 's.');
        }
    }

    return $errors;
}

/**
 * Must be called inside a database transaction after
 * selecting the user row with FOR UPDATE.
 */
function recordFailedPureViaLogin(
    mysqli $conn,
    array $user,
    array $policy
): void {
    $userId = (int) $user['id'];
    $max = (int) $policy['max_login_attempts'];
    $attempts = min($max, (int) $user['failed_login_attempts'] + 1);
    $locked = $attempts >= $max ? 1 : 0;
    $stmt = $conn->prepare(
        "UPDATE users
         SET failed_login_attempts = ?,
             status = CASE WHEN ? = 1 AND status = 'active' THEN 'locked' ELSE status END,
             locked_until = NULL
         WHERE id = ?"
    );
    $stmt->bind_param('iii', $attempts, $locked, $userId);

    $stmt->execute();
    $stmt->close();
}

/**
 * Returns true when login is blocked.
 * Must be called inside the same locked-user transaction.
 */
function isPureViaLoginLocked(
    mysqli $conn,
    array $user,
    array $policy
): bool {
    return $user['status'] === 'locked'
        || $user['locked_until'] !== null
        || (int) $user['failed_login_attempts'] >= (int) $policy['max_login_attempts'];
}
