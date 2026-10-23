<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function requireRole(mysqli $conn, array $allowedRoles): array
{
    $user = requireLogin($conn);

    if (!in_array((int) $user['role_id'], $allowedRoles, true)) {
        http_response_code(403);
        exit('Access denied. You do not have permission.');
    }

    return $user;
}

function requirePureViaAdmin(mysqli $conn): array
{
    return requireRole($conn, [1]);
}

function requirePureViaStaff(mysqli $conn): array
{
    return requireRole($conn, [2]);
}

function requirePureViaAdminOrStaff(mysqli $conn): array
{
    return requireRole($conn, [1, 2]);
}

function requirePureViaCustomer(mysqli $conn): array
{
    return requireRole($conn, [3]);
}
