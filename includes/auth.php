<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/db.php';

function requireLogin(mysqli $conn): array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($userId <= 0) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT id, role_id, first_name, last_name,
                email, status, failed_login_attempts, locked_until
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || $user['status'] !== 'active' || isPureViaLoginLocked($conn, $user, securityPolicy($conn))) {
        $_SESSION = [];
        session_destroy();

        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    $_SESSION['role_id'] = (int) $user['role_id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['email'] = $user['email'];

    $_SESSION['role'] = match ((int) $user['role_id']) {
        1 => 'admin',
        2 => 'staff',
        3 => 'customer',
        default => 'unknown'
    };

    return $user;
}
