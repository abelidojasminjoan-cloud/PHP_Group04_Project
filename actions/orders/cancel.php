<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/order-cancellation.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function cancelResponse(
    bool $success,
    string $message,
    int $status = 200
): never {
    http_response_code($status);

    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cancelResponse(false, 'Invalid request method.', 405);
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    cancelResponse(false, 'Please log in first.', 401);
}

$submittedToken = $_POST['csrf_token'] ?? null;
$sessionToken = $_SESSION['csrf_token'] ?? null;

if (
    !is_string($submittedToken) || !is_string($sessionToken) || $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    cancelResponse(false, 'Invalid security token.', 403);
}

$orderId = filter_var(
    $_POST['order_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($orderId === false || $orderId === null) {
    cancelResponse(false, 'Invalid order.', 422);
}

$reason = $_POST['reason'] ?? null;
$details = $_POST['details'] ?? '';
if (!is_string($reason) || !in_array($reason, orderCancellationReasons(), true)) {
    cancelResponse(false, 'Please select a cancellation reason.', 422);
}
if (!is_string($details) || !mb_check_encoding($details, 'UTF-8') || mb_strlen($details) > 1000) {
    cancelResponse(false, 'Additional details must be 1,000 characters or fewer.', 422);
}
$details = trim($details);

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        SELECT
            o.id,
            o.order_status,
            o.payment_status,
            o.created_at,
            (
                SELECT p.payment_method
                FROM payments p
                WHERE p.order_id = o.id
                ORDER BY p.id DESC
                LIMIT 1
            ) AS payment_method
        FROM orders o
        WHERE o.id = ?
          AND o.user_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();

    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        $conn->rollback();

        cancelResponse(false, 'Order not found.', 404);
    }

    if (!canCancelCustomerOrder($order)) {
        $conn->rollback();

        cancelResponse(
            false,
            orderCancellationEligible($order)
                ? 'The cancellation deadline has passed. Please contact support.'
                : 'This order can no longer be cancelled online.',
            409
        );
    }

    $stmt = $conn->prepare("
        UPDATE orders
        SET order_status = 'cancelled',
            cancellation_reason = ?,
            cancellation_details = ?,
            cancelled_at = NOW()
        WHERE id = ?
          AND user_id = ?
          AND order_status = 'pending'
    ");

    $stmt->bind_param('ssii', $reason, $details, $orderId, $userId);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        $stmt->close();
        $conn->rollback();

        cancelResponse(
            false,
            'Order status changed. Please refresh the page.',
            409
        );
    }

    $stmt->close();

    $conn->commit();

    try {
        require_once __DIR__ . '/../../services/OrderStatusEmail.php';
        if (!OrderStatusEmail::send($conn, $orderId, $userId, $order['order_status'], 'cancelled', $order['payment_status'], $order['payment_status'])) {
            error_log('PureVia cancellation email was not sent for order ' . $orderId . '.');
        }
    } catch (Throwable $mailError) {
        error_log('PureVia cancellation email failed for order ' . $orderId . ': ' . $mailError->getMessage());
    }

    cancelResponse(true, 'Your order has been cancelled.');

} catch (Throwable $error) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }

    error_log('PureVia cancellation error: ' . $error->getMessage());

    cancelResponse(
        false,
        'Unable to cancel your order right now.',
        500
    );
}
