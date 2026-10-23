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

$length = filter_var(
    $_POST['password_min_length'] ?? null,
    FILTER_VALIDATE_INT
);

if (
    $length === false || $length === null || $length < 8 || $length > 64
) {
    securityRedirect('Invalid password policy values.', false);
}

$fields = [
    'require_uppercase' => 'password_require_uppercase',
    'require_lowercase' => 'password_require_lowercase',
    'require_number' => 'password_require_number',
    'require_special_character' => 'password_require_special'
];

$values = [];
$counts = [];
$totalRequired = 0;

foreach ($fields as $column => $input) {
    $value = $_POST[$input] ?? null;

    if (!in_array($value, ['0', '1'], true)) {
        securityRedirect('Invalid password requirement.', false);
    }

    $values[$column] = (int) $value;
    $type = substr($column, strlen('require_'));
    $count = filter_var($_POST['password_min_' . $type] ?? null, FILTER_VALIDATE_INT);
    // Disabled count controls are not submitted; retain their previous value.
    if ($value === '0' && !isset($_POST['password_min_' . $type])) {
        $count = securityPolicy($conn)['min_' . $type];
    }
    if ($count === false || $count === null || $count < 1 || $count > 64) {
        securityRedirect('Minimum character counts must be between 1 and 64.', false);
    }
    $counts[$type] = $count;
    $totalRequired += (int) $value * $count;
}

$expiry = filter_var($_POST['password_expiry_days'] ?? null, FILTER_VALIDATE_INT);
if ($expiry === false || $expiry === null || $expiry < 0 || $expiry > 365) {
    securityRedirect('Password expiration must be between 0 and 365 days.', false);
}
if ($totalRequired > 72) {
    securityRedirect('The combined character counts cannot exceed the 72-byte password limit.', false);
}

try {
    $conn->begin_transaction();
    $settingId = securitySettingsId($conn);

    $stmt = $conn->prepare(
        "UPDATE security_settings
         SET password_min_length = ?,
             require_uppercase = ?,
             require_lowercase = ?,
             require_number = ?,
             require_special_character = ?,
             min_uppercase = ?,
             min_lowercase = ?,
             min_number = ?,
             min_special_character = ?,
             password_expiry_days = ?
         WHERE id = ?"
    );

    $stmt->bind_param(
        'iiiiiiiiiii',
        $length,
        $values['require_uppercase'],
        $values['require_lowercase'],
        $values['require_number'],
        $values['require_special_character'],
        $counts['uppercase'],
        $counts['lowercase'],
        $counts['number'],
        $counts['special_character'],
        $expiry,
        $settingId
    );

    $stmt->execute();
    $stmt->close();

    securityAudit(
        $conn,
        'update_password_policy',
        'Administrator updated password security settings.',
        $settingId
    );

    $conn->commit();

    securityRedirect('Password policy updated successfully.');
} catch (Throwable $e) {
    $conn->rollback();
    error_log($e->getMessage());
    securityRedirect('Unable to update password policy.', false);
}
