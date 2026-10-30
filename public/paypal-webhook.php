<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST'); http_response_code(405); echo '{"error":"Method not allowed."}'; exit;
}
try {
    $raw = file_get_contents('php://input', false, null, 0, 1048577);
    if ($raw === false || strlen($raw) > 1048576) {
        http_response_code(413); echo '{"error":"Payload too large."}'; exit;
    }
    $event = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    if (!is_array($event) || !is_string($event['id'] ?? null) || !is_string($event['event_type'] ?? null)) {
        throw new JsonException('Invalid event.');
    }
    // Preserve JSON objects, including empty ones, in the verification request.
    $notification = json_decode($raw, false, 64, JSON_THROW_ON_ERROR);
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../services/PayPalWebhook.php';
    if (!PayPalWebhook::verify($notification, $_SERVER)) {
        http_response_code(401); echo '{"error":"Invalid signature."}'; exit;
    }
    if ($event['event_type'] === 'PAYMENT.CAPTURE.COMPLETED') {
        require_once __DIR__ . '/../config/db.php';
        require_once __DIR__ . '/../services/PayPalCheckout.php';
        PayPalCheckout::reconcileWebhook($conn, $event);
    }
    echo '{"received":true}';
} catch (JsonException $error) {
    http_response_code(400); echo '{"error":"Invalid event."}';
} catch (Throwable $error) {
    error_log('PureVia PayPal webhook: processing failed (' . get_class($error) . ').');
    http_response_code(503); echo '{"error":"Webhook processing unavailable."}';
}
