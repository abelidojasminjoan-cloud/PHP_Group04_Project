<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/PayPalCheckout.php';
// Schedule hourly. Completed remote captures are reconciled instead of cancelled.
$rows = $conn->query("SELECT pc.order_id,o.user_id FROM paypal_checkouts pc JOIN orders o ON o.id=pc.order_id
    WHERE pc.state='pending' AND pc.created_at < NOW() - INTERVAL 3 HOUR ORDER BY pc.order_id LIMIT 100")->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    try { PayPalCheckout::finish($conn, (int)$row['user_id'], (int)$row['order_id'], true); echo 'Reconciled order ' . $row['order_id'] . PHP_EOL; }
    catch (Throwable $error) { error_log('PayPal expiry deferred for order ' . $row['order_id'] . ': ' . $error->getMessage()); }
}
