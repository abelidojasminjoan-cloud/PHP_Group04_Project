<?php
declare(strict_types=1);

// A background status check must not extend the user's inactivity deadline.
define('PUREVIA_SESSION_STATUS_REQUEST', true);
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
$sessionUser = null;
if ($sessionUserId > 0) {
    $stmt = $conn->prepare(
        'SELECT status, failed_login_attempts, locked_until FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $sessionUserId);
    $stmt->execute();
    $sessionUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$sessionPolicy = securityPolicy($conn);
if (!$sessionUser || $sessionUser['status'] !== 'active'
    || isPureViaLoginLocked($conn, $sessionUser, $sessionPolicy)) {
    if ($sessionUserId > 0) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    http_response_code(401);
    echo json_encode(['authenticated' => false]);
    exit;
}

echo json_encode([
    'authenticated' => true,
    'remaining_seconds' => securitySessionRemainingSeconds($sessionPolicy),
]);
