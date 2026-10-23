<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/order-returns.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function returnResponse(bool $success, string $message, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    returnResponse(false, 'Invalid request method.', 405);
}
$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    returnResponse(false, 'Please log in first.', 401);
}
$token = $_POST['csrf_token'] ?? null;
$sessionToken = $_SESSION['csrf_token'] ?? null;
if (!is_string($token) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
    returnResponse(false, 'Invalid security token. Please refresh the page.', 403);
}
$orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$orderId) {
    returnResponse(false, 'Invalid order.', 422);
}
$reason = $_POST['reason'] ?? null;
$resolution = $_POST['resolution'] ?? null;
$details = $_POST['details'] ?? '';
if (!is_string($reason) || !in_array($reason, orderReturnReasons(), true)) {
    returnResponse(false, 'Please select a reason for return.', 422);
}
if (!is_string($resolution) || !in_array($resolution, ['refund', 'replacement'], true)) {
    returnResponse(false, 'Please select a preferred resolution.', 422);
}
if (!is_string($details) || !mb_check_encoding($details, 'UTF-8') || mb_strlen($details) > 1000) {
    returnResponse(false, 'Return details must be 1,000 characters or fewer.', 422);
}
$details = trim($details);
$submittedItems = $_POST['items'] ?? null;
if (!is_array($submittedItems) || !$submittedItems || count($submittedItems) > 200) {
    returnResponse(false, 'Please select at least one product to return.', 422);
}
$itemIds = [];
foreach ($submittedItems as $id) {
    if (!is_scalar($id) || !($id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]))) {
        returnResponse(false, 'Invalid product selection.', 422);
    }
    $itemIds[] = $id;
}
if (count($itemIds) !== count(array_unique($itemIds))) {
    returnResponse(false, 'Invalid product selection.', 422);
}

try {
    $conn->begin_transaction();
    $stmt = $conn->prepare('SELECT id, order_status FROM orders WHERE id = ? AND user_id = ? LIMIT 1 FOR UPDATE');
    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) {
        $conn->rollback();
        returnResponse(false, 'Order not found.', 404);
    }
    if (!orderReturnEligible($order)) {
        $conn->rollback();
        returnResponse(false, 'Return requests are available for delivered orders only.', 409);
    }
    $stmt = $conn->prepare('SELECT id FROM order_return_requests WHERE order_id = ? LIMIT 1');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    if ($exists) {
        $conn->rollback();
        returnResponse(false, 'A return request has already been submitted for this order.', 409);
    }

    $stmt = $conn->prepare('SELECT id, quantity FROM order_items WHERE order_id = ? FOR UPDATE');
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $purchased = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'quantity', 'id');
    $stmt->close();
    foreach ($itemIds as $id) {
        if (!isset($purchased[$id]) || (int) $purchased[$id] < 1) {
            $conn->rollback();
            returnResponse(false, 'Please select products from this order only.', 422);
        }
    }

    $stmt = $conn->prepare('INSERT INTO order_return_requests (order_id, reason, resolution, details) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $orderId, $reason, $resolution, $details);
    $stmt->execute();
    $requestId = (int) $conn->insert_id;
    $stmt->close();
    $stmt = $conn->prepare('INSERT INTO order_return_items (return_request_id, order_item_id, quantity) VALUES (?, ?, ?)');
    foreach ($itemIds as $id) {
        $quantity = (int) $purchased[$id];
        $stmt->bind_param('iii', $requestId, $id, $quantity);
        $stmt->execute();
    }
    $stmt->close();
    $conn->commit();
    returnResponse(true, 'Your return request has been submitted for review. Keep the products and original packaging until you receive return instructions.');
} catch (Throwable $error) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }
    error_log('PureVia return request error: ' . $error->getMessage());
    returnResponse(false, 'Unable to submit your return request right now. Please try again.', 500);
}
