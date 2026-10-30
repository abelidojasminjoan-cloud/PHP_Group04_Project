<?php
declare(strict_types=1);
require_once __DIR__ . '/PayPalClient.php';

final class PayPalCheckout
{
    public static function find(mysqli $conn, int $userId, int $orderId, bool $lock = false): array
    {
        $s = $conn->prepare('SELECT pc.*, o.total_amount,o.order_number,o.payment_status,o.order_status FROM orders o
            JOIN paypal_checkouts pc ON pc.order_id=o.id JOIN users u ON u.id=o.user_id
            WHERE o.user_id=? AND o.id=? AND u.status=\'active\' AND u.role_id=3' . ($lock ? ' FOR UPDATE' : ''));
        $s->bind_param('ii', $userId, $orderId); $s->execute();
        $row = $s->get_result()->fetch_assoc();
        if (!$row) throw new DomainException('PayPal order not found for your account.');
        if ($row['environment'] !== PayPalClient::mode()) throw new DomainException('This order uses a different PayPal environment. Contact support.');
        return $row;
    }

    public static function start(mysqli $conn, int $userId, int $orderId, ?PayPalClient $client = null): string
    {
        $conn->begin_transaction();
        try {
            $row = self::find($conn, $userId, $orderId, true);
            if ($row['state'] !== 'pending' || $row['order_status'] !== 'pending') throw new DomainException('This checkout is no longer awaiting payment.');
            if ($row['approval_url']) { $conn->commit(); return $row['approval_url']; }
            $snapshot = json_decode($row['items_snapshot'], true, 32, JSON_THROW_ON_ERROR);
            $landingPage = ($snapshot['payment_preference'] ?? 'paypal') === 'card' ? 'GUEST_CHECKOUT' : 'LOGIN';
            $callback = APP_URL . '/order-success.php?paypal_order_id=' . $orderId;
            $remote = ($client ?? new PayPalClient())->request('POST', '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [['custom_id' => (string) $orderId, 'invoice_id' => $row['order_number'],
                    'amount' => ['currency_code' => 'PHP', 'value' => $row['total_amount']]]],
                'payment_source' => ['paypal' => ['experience_context' => ['brand_name' => 'PureVia',
                    'user_action' => 'PAY_NOW', 'landing_page' => $landingPage, 'shipping_preference' => 'NO_SHIPPING',
                    'return_url' => $callback, 'cancel_url' => $callback . '&cancelled=1']]],
            ], 'pv-create-' . $row['request_key']);
            $remoteId = $remote['id'] ?? '';
            if (!is_string($remoteId) || !preg_match('/^[A-Z0-9]{10,32}$/D', $remoteId)) throw new RuntimeException('Invalid PayPal order response.');
            $url = PayPalClient::approvalUrl($remote);
            $s = $conn->prepare('UPDATE paypal_checkouts SET paypal_order_id=?,approval_url=? WHERE order_id=?');
            $s->bind_param('ssi', $remoteId, $url, $orderId); $s->execute();
            $conn->commit(); return $url;
        } catch (Throwable $error) { $conn->rollback(); throw $error; }
    }

    /** Create an order for embedded fields; the SDK sends card details directly to PayPal. */
    public static function cardOrder(mysqli $conn, int $userId, int $orderId, ?PayPalClient $client = null): string
    {
        $conn->begin_transaction();
        try {
            $row = self::find($conn, $userId, $orderId, true);
            $snapshot = json_decode($row['items_snapshot'], true, 32, JSON_THROW_ON_ERROR);
            if (($snapshot['payment_preference'] ?? '') !== 'card' || $row['state'] !== 'pending' || $row['order_status'] !== 'pending') {
                throw new DomainException('This order is not awaiting a card payment.');
            }
            if ($row['paypal_order_id']) { $conn->commit(); return $row['paypal_order_id']; }
            $remote = ($client ?? new PayPalClient())->request('POST', '/v2/checkout/orders', [
                'intent'=>'CAPTURE', 'purchase_units'=>[['custom_id'=>(string)$orderId, 'invoice_id'=>$row['order_number'],
                    'amount'=>['currency_code'=>'PHP','value'=>$row['total_amount']]]],
            ], 'pv-create-' . $row['request_key']);
            $remoteId = $remote['id'] ?? '';
            if (!is_string($remoteId) || !preg_match('/^[A-Z0-9]{10,32}$/D', $remoteId)) throw new RuntimeException('Invalid PayPal order response.');
            $approvalUrl = null;
            try { $approvalUrl = PayPalClient::approvalUrl($remote); } catch (RuntimeException $ignored) {}
            $s = $conn->prepare('UPDATE paypal_checkouts SET paypal_order_id=?,approval_url=? WHERE order_id=?');
            $s->bind_param('ssi', $remoteId, $approvalUrl, $orderId); $s->execute();
            $conn->commit(); return $remoteId;
        } catch (Throwable $error) { $conn->rollback(); throw $error; }
    }

    /** Locks the local order throughout capture/reconciliation; retries reuse the same request ID. */
    public static function finish(mysqli $conn, int $userId, int $orderId, bool $cancel = false, ?PayPalClient $client = null): bool
    {
        $conn->begin_transaction();
        try {
            $row = self::find($conn, $userId, $orderId, true);
            if ($row['state'] === 'paid') { $conn->commit(); return true; }
            if ($row['state'] !== 'pending' || $row['order_status'] !== 'pending') throw new DomainException('This order is no longer payable.');
            $client ??= new PayPalClient();
            $remote = $row['paypal_order_id'] ? $client->request('GET', '/v2/checkout/orders/' . rawurlencode($row['paypal_order_id'])) : null;
            // Even cancellation first checks whether a previous capture completed remotely.
            if (!$cancel && ($remote['status'] ?? '') !== 'COMPLETED') {
                if (($remote['status'] ?? '') !== 'APPROVED') throw new DomainException('Approve this payment on PayPal before confirming.');
                $remote = $client->request('POST', '/v2/checkout/orders/' . rawurlencode($row['paypal_order_id']) . '/capture', [], 'pv-capture-' . $row['request_key']);
            }
            $snapshot = json_decode($row['items_snapshot'], true, 32, JSON_THROW_ON_ERROR);
            $items = $snapshot['items'] ?? $snapshot;
            if (($remote['status'] ?? '') === 'COMPLETED') {
                $capture = PayPalClient::completedCapture($remote, $row);
                self::recordPaid($conn, $row, $userId, $orderId, $capture, $items);
                $conn->commit(); return true;
            }
            if (!$cancel || ($remote !== null && !in_array($remote['status'] ?? '', ['CREATED','PAYER_ACTION_REQUIRED','APPROVED','VOIDED'], true))) {
                throw new DomainException('Payment status is uncertain. Retry the status check before cancelling.');
            }
            // The same order lock prevents this application from capturing after cancellation.
            usort($items, static fn($a, $b) => $a['product_variant_id'] <=> $b['product_variant_id']);
            foreach ($items as $item) {
                $s = $conn->prepare('UPDATE product_variants SET stock_quantity=stock_quantity+? WHERE id=?');
                $s->bind_param('ii', $item['quantity'], $item['product_variant_id']); $s->execute();
            }
            foreach (array_unique(array_column($items, 'product_id')) as $productId) {
                $s = $conn->prepare('UPDATE products SET stock_quantity=(SELECT COALESCE(SUM(stock_quantity),0) FROM product_variants WHERE product_id=?) WHERE id=?');
                $s->bind_param('ii', $productId, $productId); $s->execute();
            }
            $s = $conn->prepare("UPDATE orders SET order_status='cancelled',payment_status='failed',cancelled_at=NOW(),cancellation_reason='PayPal checkout cancelled' WHERE id=?");
            $s->bind_param('i', $orderId); $s->execute();
            $s = $conn->prepare("UPDATE payments SET payment_status='failed' WHERE order_id=? AND payment_method='online'"); $s->bind_param('i', $orderId); $s->execute();
            $s = $conn->prepare("UPDATE paypal_checkouts SET state='cancelled' WHERE order_id=?"); $s->bind_param('i', $orderId); $s->execute();
            $conn->commit(); return false;
        } catch (Throwable $error) { $conn->rollback(); throw $error; }
    }
    /** Called only after authenticating the notification. Never captures money. */
    public static function reconcileWebhook(mysqli $conn, array $event, ?PayPalClient $client = null): void
    {
        $remoteId = $event['resource']['supplementary_data']['related_ids']['order_id'] ?? '';
        $eventCapture = $event['resource']['id'] ?? '';
        if (!is_string($remoteId) || !preg_match('/^[A-Z0-9]{10,32}$/D', $remoteId)
            || !is_string($eventCapture) || !preg_match('/^[A-Z0-9]{10,32}$/D', $eventCapture)) {
            throw new DomainException('Invalid capture notification.');
        }
        $conn->begin_transaction();
        try {
            $s = $conn->prepare('SELECT pc.*,o.user_id,o.total_amount,o.payment_status,o.order_status
                FROM paypal_checkouts pc JOIN orders o ON o.id=pc.order_id
                WHERE pc.paypal_order_id=? AND pc.environment=? FOR UPDATE');
            $mode = PayPalClient::mode();
            $s->bind_param('ss', $remoteId, $mode); $s->execute();
            $row = $s->get_result()->fetch_assoc();
            if (!$row) { $conn->commit(); return; }
            $remote = ($client ?? new PayPalClient())->request('GET', '/v2/checkout/orders/' . rawurlencode($remoteId));
            $capture = PayPalClient::completedCapture($remote, $row);
            if (!hash_equals($capture, $eventCapture)) throw new DomainException('Capture does not match the notification.');
            if ($row['state'] === 'paid') {
                if (!hash_equals((string) $row['capture_id'], $capture)) throw new DomainException('Capture does not match the recorded payment.');
                $conn->commit(); return;
            }
            // Stock may already have been released for a cancelled order.
            if ($row['state'] !== 'pending' || $row['order_status'] !== 'pending') {
                throw new RuntimeException('Captured payment requires manual order review.');
            }
            $snapshot = json_decode($row['items_snapshot'], true, 32, JSON_THROW_ON_ERROR);
            self::recordPaid($conn, $row, (int) $row['user_id'], (int) $row['order_id'], $capture, $snapshot['items'] ?? $snapshot);
            $conn->commit();
        } catch (Throwable $error) { $conn->rollback(); throw $error; }
    }

    private static function recordPaid(mysqli $conn, array $row, int $userId, int $orderId, string $capture, array $items): void
    {
                $s = $conn->prepare("UPDATE payments SET payment_status='paid', transaction_reference=?, gateway_reference=?, verified_at=NOW() WHERE order_id=? AND payment_method='online'");
                $s->bind_param('ssi', $capture, $row['paypal_order_id'], $orderId); $s->execute();
                $s = $conn->prepare("UPDATE orders SET payment_status='paid' WHERE id=?"); $s->bind_param('i', $orderId); $s->execute();
                $s = $conn->prepare("UPDATE paypal_checkouts SET state='paid',capture_id=? WHERE order_id=?"); $s->bind_param('si', $capture, $orderId); $s->execute();
                foreach ($items as $item) {
                    $s = $conn->prepare('DELETE ci FROM cart_items ci JOIN carts c ON c.id=ci.cart_id WHERE c.user_id=? AND ci.id=? AND ci.product_variant_id=? AND ci.quantity=?');
                    $s->bind_param('iiii', $userId, $item['cart_item_id'], $item['product_variant_id'], $item['quantity']); $s->execute();
                }

    }

}
