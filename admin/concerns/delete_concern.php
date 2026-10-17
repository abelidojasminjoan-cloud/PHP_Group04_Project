<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function concernDeleteResponse(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    concernDeleteResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$deleteTransactionStarted = false;
$linkedConcernMessage = 'This skin concern is still linked to products or customer skin profiles. Remove those links before deleting it.';
try {
    $adminId = (int) ($_SESSION['user_id'] ?? 0);
    $stmt = $conn->prepare("
        SELECT u.id FROM users u
        INNER JOIN roles r ON r.id = u.role_id
        WHERE u.id = ? AND u.status = 'active' AND LOWER(r.role_name) = 'admin'
        LIMIT 1
    ");
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $allowed = $stmt->get_result()->num_rows === 1;
    $stmt->close();
    if (!$allowed) {
        concernDeleteResponse(403, ['success' => false, 'message' => 'Admin access is required. Please sign in again.']);
    }

    $token = $_POST['csrf_token'] ?? null;
    $sessionToken = $_SESSION['csrf_concern'] ?? null;
    if (!is_string($token) || !is_string($sessionToken) || $sessionToken === ''
        || !hash_equals($sessionToken, $token)) {
        concernDeleteResponse(403, ['success' => false, 'message' => 'Invalid security token. Refresh the page.']);
    }

    $submittedId = $_POST['concern_id'] ?? null;
    $concernId = is_string($submittedId)
        ? filter_var($submittedId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;
    if ($concernId === false) {
        concernDeleteResponse(400, ['success' => false, 'message' => 'Invalid concern.']);
    }

    $conn->begin_transaction();
    $deleteTransactionStarted = true;
    $stmt = $conn->prepare('SELECT concern_name FROM skin_concerns WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $concernId);
    $stmt->execute();
    $concern = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$concern) {
        $conn->rollback();
        $deleteTransactionStarted = false;
        concernDeleteResponse(404, ['success' => false, 'message' => 'This concern no longer exists. Refresh the list.']);
    }

    // Locking the concern also prevents new foreign-key links during deletion.
    // Preserve product concern data and saved customer skin profiles.
    foreach (['product_concerns', 'skin_profile_concerns'] as $linkTable) {
        $stmt = $conn->prepare("SELECT concern_id FROM {$linkTable} WHERE concern_id = ? LIMIT 1");
        $stmt->bind_param('i', $concernId);
        $stmt->execute();
        $hasLinks = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if ($hasLinks) {
            $conn->rollback();
            $deleteTransactionStarted = false;
            concernDeleteResponse(409, ['success' => false, 'message' => $linkedConcernMessage]);
        }
    }

    $stmt = $conn->prepare('DELETE FROM skin_concerns WHERE id = ?');
    $stmt->bind_param('i', $concernId);
    $stmt->execute();
    $stmt->close();

    $recordId = (string) $concernId;
    $description = 'Deleted concern "' . $concern['concern_name'] . '".';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (user_id, action, module, record_id, description, ip_address)
        VALUES (?, 'DELETE', 'Skin Concerns', ?, ?, ?)
    ");
    $stmt->bind_param('isss', $adminId, $recordId, $description, $ipAddress);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $deleteTransactionStarted = false;
    $_SESSION['concern_success'] = 'Skin concern deleted successfully.';
    concernDeleteResponse(200, ['success' => true, 'message' => $_SESSION['concern_success']]);
} catch (Throwable $error) {
    if ($deleteTransactionStarted) {
        $conn->rollback();
    }
    if ($error instanceof mysqli_sql_exception && $error->getCode() === 1451) {
        concernDeleteResponse(409, ['success' => false, 'message' => $linkedConcernMessage]);
    }
    error_log('Delete Concern Error: ' . $error->getMessage());
    concernDeleteResponse(500, ['success' => false, 'message' => 'Unable to delete this concern. Please try again.']);
}
