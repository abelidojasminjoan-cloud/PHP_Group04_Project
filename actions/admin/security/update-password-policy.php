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
    $length === false || $length === null || $length < 8 || $length > 72
) {
    securityRedirect('Invalid password policy values.', false);
}

$fields = [
    'require_uppercase' => 'password_require_uppercase',
    'require_number' => 'password_require_number',
    'require_special_character' => 'password_require_special'
];

$values = [];

foreach ($fields as $column => $input) {
    $value = $_POST[$input] ?? null;

    if (!in_array($value, ['0', '1'], true)) {
        securityRedirect('Invalid password requirement.', false);
    }

    $values[$column] = (int) $value;
}

try {
    $conn->begin_transaction();
    $settingId = securitySettingsId($conn);

    $stmt = $conn->prepare(
        "UPDATE security_settings
         SET password_min_length = ?,
             require_uppercase = ?,
             require_number = ?,
             require_special_character = ?
         WHERE id = ?"
    );

    $stmt->bind_param(
        'iiiii',
        $length,
        $values['require_uppercase'],
        $values['require_number'],
        $values['require_special_character'],
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
