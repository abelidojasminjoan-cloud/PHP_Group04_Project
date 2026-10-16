<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/product_data.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function productDeleteResponse(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    productDeleteResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$deleteTransactionStarted = false;
try {
    if (!productAdminAllowed($conn)) {
        productDeleteResponse(403, ['success' => false, 'message' => 'Admin access is required. Please sign in again.']);
    }

    $token = $_POST['csrf_token'] ?? null;
    $sessionToken = $_SESSION['csrf_product'] ?? null;
    if (!is_string($token) || !is_string($sessionToken) || $sessionToken === ''
        || !hash_equals($sessionToken, $token)) {
        productDeleteResponse(403, ['success' => false, 'message' => 'Invalid security token. Refresh the page.']);
    }

    $productId = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]);
    if ($productId === false) {
        productDeleteResponse(400, ['success' => false, 'message' => 'Invalid product.']);
    }

    $conn->begin_transaction();
    $deleteTransactionStarted = true;
    $stmt = $conn->prepare('SELECT product_name, product_code FROM products WHERE id = ? FOR UPDATE');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$product) {
        $conn->rollback();
        $deleteTransactionStarted = false;
        productDeleteResponse(404, ['success' => false, 'message' => 'This product no longer exists. Refresh the list.']);
    }

    // Foreign keys cascade through variants, baskets, images, matching data, and reviews.
    // Order items retain their purchased name and price; only their product_id becomes NULL.
    $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $stmt->close();

    $adminId = (int) $_SESSION['user_id'];
    $recordId = (string) $productId;
    $description = sprintf('Deleted product "%s" (%s).', $product['product_name'], $product['product_code']);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (user_id, action, module, record_id, description, ip_address)
        VALUES (?, 'DELETE', 'Products', ?, ?, ?)
    ");
    $stmt->bind_param('isss', $adminId, $recordId, $description, $ipAddress);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $deleteTransactionStarted = false;
    $_SESSION['product_success'] = 'Product deleted successfully.';
    productDeleteResponse(200, ['success' => true, 'message' => $_SESSION['product_success']]);
} catch (Throwable $error) {
    if ($deleteTransactionStarted) {
        $conn->rollback();
    }
    error_log('Delete Product Error: ' . $error->getMessage());
    productDeleteResponse(500, ['success' => false, 'message' => 'Unable to delete this product. Please try again.']);
}
