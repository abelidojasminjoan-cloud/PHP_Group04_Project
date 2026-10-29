<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../services/AdminUser.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function adminUserReply(array $data, int $status = 200): never
{
    http_response_code($status);
    echo pvJson($data);
    exit;
}

try {
    $adminId = (int) ($_SESSION['user_id'] ?? 0);
    if (!$adminId) adminUserReply(['success' => false, 'message' => 'Please sign in again.'], 401);
    $stmt = $conn->prepare('SELECT role_id, status, failed_login_attempts, locked_until FROM users WHERE id = ?');
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    if (!$admin || (int) $admin['role_id'] !== 1 || $admin['status'] !== 'active' || isPureViaLoginLocked($conn, $admin, securityPolicy($conn))) {
        adminUserReply(['success' => false, 'message' => 'Admin access is required.'], 403);
    }
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        adminUserReply(['success' => false, 'message' => 'Method not allowed.'], 405);
    }
    if ($method === 'GET') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) throw new InvalidArgumentException('Invalid user.');
        $user = AdminUser::details($conn, $id);
        if (!$user) throw new OutOfBoundsException('User not found.');
    } else {
        $raw = file_get_contents('php://input', false, null, 0, 16385);
        if (strlen($raw) > 16384) adminUserReply(['success' => false, 'message' => 'Request too large.'], 413);
        $data = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($data)) throw new InvalidArgumentException('Invalid request.');
        $token = $data['csrf_token'] ?? null;
        $expected = $_SESSION['csrf_token'] ?? null;
        if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            adminUserReply(['success' => false, 'message' => 'Your session changed. Refresh the page and try again.'], 403);
        }
        $user = AdminUser::save($conn, $adminId, $data);
    }
    adminUserReply(['success' => true, 'user' => $user]);
} catch (InvalidArgumentException | JsonException $error) {
    adminUserReply(['success' => false, 'message' => $error instanceof JsonException ? 'Invalid request.' : $error->getMessage()], 422);
} catch (OutOfBoundsException $error) {
    adminUserReply(['success' => false, 'message' => $error->getMessage()], 404);
} catch (DomainException $error) {
    adminUserReply(['success' => false, 'message' => $error->getMessage()], 409);
} catch (Throwable $error) {
    error_log('Admin user: ' . $error->getMessage());
    adminUserReply(['success' => false, 'message' => 'Unable to load or save this user. Please try again.'], 500);
}
