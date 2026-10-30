<?php
declare(strict_types=1);

/** Track the first password change even if the configured initial password rotates. */
function pureViaStaffNeedsPasswordChange(array $user): bool
{
    if ((int) ($user['role_id'] ?? 0) !== 2) return false;
    if (empty($user['password_changed_at'])) return true;
    $initial = (string) ($_ENV['STAFF_DEFAULT_PASSWORD'] ?? '');
    return $initial !== '' && !empty($user['password'])
        && password_verify($initial, (string) $user['password']);
}