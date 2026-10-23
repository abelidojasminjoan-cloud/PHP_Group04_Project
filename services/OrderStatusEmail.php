<?php
declare(strict_types=1);

require_once __DIR__ . '/EmailService.php';

final class OrderStatusEmail
{
    private const STATUSES = [
        'pending' => 'Pending', 'confirmed' => 'Confirmed', 'preparing' => 'Processing',
        'shipping' => 'Shipped', 'delivered' => 'Delivered', 'completed' => 'Completed', 'cancelled' => 'Cancelled',
    ];
    private const PAYMENTS = ['pending' => 'Pending', 'paid' => 'Verified', 'failed' => 'Failed', 'refunded' => 'Refunded'];

    /** Call only after committing a real status change, using the locked before/after values. */
    public static function send(
        mysqli $conn, int $orderId, int $userId,
        string $oldStatus, string $newStatus, string $oldPayment, string $newPayment
    ): bool {
        if ($oldStatus === $newStatus && $oldPayment === $newPayment) {
            return false;
        }
        $stmt = $conn->prepare('SELECT o.order_number, o.email, o.first_name, o.last_name, o.total_amount,
                u.first_name AS customer_first_name, u.last_name AS customer_last_name,
                (SELECT payment_method FROM payments WHERE order_id = o.id ORDER BY id DESC LIMIT 1) AS payment_method
            FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ? AND o.user_id = ? LIMIT 1');
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$order || !filter_var($order['email'], FILTER_VALIDATE_EMAIL)) {
            error_log('PureVia status email: missing order or invalid customer email (order ' . $orderId . ').');
            return false;
        }
        $message = self::message($order, $oldStatus, $newStatus, $oldPayment, $newPayment);
        return sendPureViaMail($order['email'], $message['name'], $message['subject'], $message['html'], $message['plain'], 15);
    }

    public static function message(array $order, string $oldStatus, string $newStatus, string $oldPayment, string $newPayment): array
    {
        if (!isset(self::STATUSES[$oldStatus], self::STATUSES[$newStatus], self::PAYMENTS[$oldPayment], self::PAYMENTS[$newPayment])) {
            throw new InvalidArgumentException('Invalid order status notification.');
        }
        $name = trim(($order['customer_first_name'] ?? '') . ' ' . ($order['customer_last_name'] ?? ''));
        if ($name === '') {
            $name = trim($order['first_name'] . ' ' . $order['last_name']);
        }
        $number = (string) $order['order_number'];
        $statusChanged = $oldStatus !== $newStatus;
        $paymentChanged = $oldPayment !== $newPayment;
        $status = self::STATUSES[$newStatus];
        $payment = self::PAYMENTS[$newPayment];
        $heading = $statusChanged ? 'Your order status has changed' : 'Your payment status has changed';
        $description = $statusChanged ? match ($newStatus) {
            'pending' => 'Your order is pending.',
            'confirmed' => 'Your order has been confirmed.',
            'preparing' => 'Your order is being prepared.',
            'shipping' => 'Your order has been shipped.',
            'delivered' => 'Your order has been marked as delivered.',
            'completed' => 'Your order has been marked as completed.',
            'cancelled' => 'Your order has been cancelled.',
        } : 'The payment status of your order has been updated.';
        $statusLine = ($statusChanged ? self::STATUSES[$oldStatus] . ' → ' : '') . $status;
        $paymentLine = ($paymentChanged ? self::PAYMENTS[$oldPayment] . ' → ' : '') . $payment;
        $total = '₱' . number_format((float) $order['total_amount'], 2);
        $paymentNote = match ($newPayment) {
            'pending' => ($order['payment_method'] ?? '') === 'cod' && $newStatus !== 'cancelled'
                ? 'Payment remains due on delivery.' : 'No payment has been recorded for this order.',
            'paid' => 'Payment is marked as verified.',
            'failed' => 'Payment is marked as failed.',
            'refunded' => 'Payment is marked as refunded.',
        };
        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>PureVia order update</title></head>'
            . '<body style="margin:0;padding:24px 12px;background:#f8f5ef;color:#202820;font-family:Arial,sans-serif;">'
            . '<div style="max-width:600px;margin:auto;padding:28px 20px;background:#fff;border:1px solid #e0d9cd;border-radius:12px;">'
            . '<p style="margin:0;color:#38644b;font-size:22px;font-weight:bold;">PureVia</p>'
            . '<h1 style="font-size:24px;">' . pvHtml($heading) . '</h1><p>Hello ' . pvHtml($name) . ',</p>'
            . '<p>' . pvHtml($description) . '</p><p><strong>Order:</strong> ' . pvHtml($number) . '</p>'
            . '<div style="padding:18px;background:#ede9e1;border-radius:8px;line-height:1.8;">'
            . '<strong>Order status:</strong> ' . pvHtml($statusLine) . '<br>'
            . '<strong>Payment status:</strong> ' . pvHtml($paymentLine) . '<br>'
            . '<strong>Order total:</strong> ' . pvHtml($total) . '</div>'
            . '<p>' . pvHtml($paymentNote) . '</p>'
            . '<p>You can review the latest details under My Account → My Orders.</p>'
            . '<p>PureVia Team</p></div></body></html>';
        $plain = "PUREVIA — ORDER UPDATE\n\nHello " . $name . ",\n\n" . $description
            . "\nOrder: " . $number . "\nOrder status: " . $statusLine . "\nPayment status: " . $paymentLine
            . "\nOrder total: " . $total . "\n\n" . $paymentNote
            . "\n\nReview the latest details under My Account > My Orders.\n\nPureVia Team";
        $subject = $statusChanged ? 'PureVia order ' . $number . ' — ' . $status : 'PureVia payment update — ' . $number;
        return ['name' => $name, 'subject' => $subject, 'html' => $html, 'plain' => $plain];
    }
}
