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

    $defaults['password_min_length'] = max(8, min(72, $defaults['password_min_length']));
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
        'uppercase' => ['require_uppercase', 'One uppercase letter'],
        'lowercase' => ['require_lowercase', 'One lowercase letter'],
        'number' => ['require_number', 'One number'],
        'symbol' => ['require_special_character', 'One special character'],
    ] as $key => [$setting, $label]) {
        if ($policy[$setting]) {
            $rules[$key] = $label;
        }
    }
    return $rules;
}

function validatePureViaPassword(
    string $password,
    array $policy
): array {
    $errors = [];

    if (strlen($password) < $policy['password_min_length']) {
        $errors[] = 'Password must contain at least ' . $policy['password_min_length'] . ' characters.';
    }

    if (strlen($password) > 72 || str_contains($password, "\0")) {
        $errors[] = 'Password must not exceed 72 bytes or contain null characters.';
    }

    if (
        $policy['require_uppercase'] &&
        !preg_match('/[A-Z]/', $password)
    ) {
        $errors[] = 'Password needs an uppercase letter.';
    }

    if (
        $policy['require_lowercase'] &&
        !preg_match('/[a-z]/', $password)
    ) {
        $errors[] = 'Password needs a lowercase letter.';
    }

    if (
        $policy['require_number'] &&
        !preg_match('/[0-9]/', $password)
    ) {
        $errors[] = 'Password needs a number.';
    }

    if (
        $policy['require_special_character'] &&
        !preg_match('/[^A-Za-z0-9]/', $password)
    ) {
        $errors[] = 'Password needs a special character.';
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
