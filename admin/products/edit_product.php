<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/product_data.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requireProductEdit = true;
    require __DIR__ . '/add_product.php';
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function productEditResponse(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET, POST');
    productEditResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

try {
    if (!productAdminAllowed($conn)) {
        productEditResponse(403, ['success' => false, 'message' => 'Admin access is required. Please sign in again.']);
    }
    $productId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]);
    if ($productId === false) {
        productEditResponse(400, ['success' => false, 'message' => 'Invalid product.']);
    }

    // Authentication is complete; do not block other requests on the session during database reads.
    session_write_close();
    $conn->begin_transaction(MYSQLI_TRANS_START_READ_ONLY);
    $product = productEditData($conn, $productId);
    $conn->commit();
    if ($product === null) {
        productEditResponse(404, ['success' => false, 'message' => 'This product no longer exists.']);
    }
    productEditResponse(200, ['success' => true, 'product' => $product]);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Load product edit form: ' . $error->getMessage());
    productEditResponse(500, ['success' => false, 'message' => 'Unable to load this product. Please try again.']);
}
