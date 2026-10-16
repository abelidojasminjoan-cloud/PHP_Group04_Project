<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

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

$submittedToken = (string) ($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['csrf_token'] ?? '');

if (
    $sessionToken === '' ||
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

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        SELECT
            o.id,
            o.order_status,
            o.payment_status,
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

    if (
        $order['order_status'] !== 'pending' ||
        $order['payment_status'] !== 'pending' ||
        $order['payment_method'] !== 'cod'
    ) {
        $conn->rollback();

        cancelResponse(
            false,
            'This order can no longer be cancelled online.',
            409
        );
    }

    $stmt = $conn->prepare("
        UPDATE orders
        SET order_status = 'cancelled'
        WHERE id = ?
          AND user_id = ?
          AND order_status = 'pending'
    ");

    $stmt->bind_param('ii', $orderId, $userId);
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