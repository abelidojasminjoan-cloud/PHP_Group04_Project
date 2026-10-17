<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function categoryDeleteResponse(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    categoryDeleteResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$deleteTransactionStarted = false;
$assignedProductsMessage = 'This category has products assigned to it. Move them to another category before deleting it.';
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
        categoryDeleteResponse(403, ['success' => false, 'message' => 'Admin access is required. Please sign in again.']);
    }

    $token = $_POST['csrf_token'] ?? null;
    $sessionToken = $_SESSION['csrf_category'] ?? null;
    if (!is_string($token) || !is_string($sessionToken) || $sessionToken === ''
        || !hash_equals($sessionToken, $token)) {
        categoryDeleteResponse(403, ['success' => false, 'message' => 'Invalid security token. Refresh the page.']);
    }

    $submittedId = $_POST['category_id'] ?? null;
    $categoryId = is_string($submittedId)
        ? filter_var($submittedId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;
    if ($categoryId === false) {
        categoryDeleteResponse(400, ['success' => false, 'message' => 'Invalid category.']);
    }

    $conn->begin_transaction();
    $deleteTransactionStarted = true;
    $stmt = $conn->prepare('SELECT category_name FROM categories WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $category = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$category) {
        $conn->rollback();
        $deleteTransactionStarted = false;
        categoryDeleteResponse(404, ['success' => false, 'message' => 'This category no longer exists. Refresh the list.']);
    }

    // Keep products intact. The category foreign key also protects concurrent assignments.
    $stmt = $conn->prepare('SELECT id FROM products WHERE category_id = ? LIMIT 1');
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $hasProducts = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($hasProducts) {
        $conn->rollback();
        $deleteTransactionStarted = false;
        categoryDeleteResponse(409, ['success' => false, 'message' => $assignedProductsMessage]);
    }

    $stmt = $conn->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $stmt->close();

    $recordId = (string) $categoryId;
    $description = 'Deleted category "' . $category['category_name'] . '".';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (user_id, action, module, record_id, description, ip_address)
        VALUES (?, 'DELETE', 'Categories', ?, ?, ?)
    ");
    $stmt->bind_param('isss', $adminId, $recordId, $description, $ipAddress);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $deleteTransactionStarted = false;
    $_SESSION['category_success'] = 'Category deleted successfully.';
    categoryDeleteResponse(200, ['success' => true, 'message' => $_SESSION['category_success']]);
} catch (Throwable $error) {
    if ($deleteTransactionStarted) {
        $conn->rollback();
    }
    if ($error instanceof mysqli_sql_exception && $error->getCode() === 1451) {
        categoryDeleteResponse(409, ['success' => false, 'message' => $assignedProductsMessage]);
    }
    error_log('Delete Category Error: ' . $error->getMessage());
    categoryDeleteResponse(500, ['success' => false, 'message' => 'Unable to delete this category. Please try again.']);
}
