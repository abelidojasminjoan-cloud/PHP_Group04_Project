<?php
declare(strict_types=1);

require_once __DIR__ . '/EmailService.php';

final class OrderReceipt
{
    public static function send(mysqli $conn, int $orderId, int $userId): bool
    {
        $stmt = $conn->prepare("SELECT o.*, u.first_name AS customer_first_name,
                u.last_name AS customer_last_name,
                (SELECT payment_method FROM payments WHERE order_id = o.id ORDER BY id DESC LIMIT 1) AS payment_method
            FROM orders o INNER JOIN users u ON u.id = o.user_id
            WHERE o.id = ? AND o.user_id = ? LIMIT 1");
        $stmt->bind_param('ii', $orderId, $userId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$order || !filter_var($order['email'], FILTER_VALIDATE_EMAIL)
            || $order['order_status'] === 'cancelled'
            || in_array($order['payment_status'], ['failed', 'refunded'], true)) {
            error_log('PureVia order receipt: order is missing, ineligible, or has an invalid email (order ' . $orderId . ').');
            return false;
        }
        $stmt = $conn->prepare('SELECT product_name, quantity, unit_price, subtotal FROM order_items WHERE order_id = ? ORDER BY id');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        if (!$items) {
            error_log('PureVia order receipt: order has no purchased items (order ' . $orderId . ').');
            return false;
        }

        $message = self::message($order, $items);
        return sendPureViaMail($order['email'], $message['name'], $message['subject'], $message['html'], $message['plain'], 15);
    }

    /** Use saved order snapshots, so later catalog changes cannot change a receipt. */
    public static function message(array $order, array $items): array
    {
        $name = trim(($order['customer_first_name'] ?? '') . ' ' . ($order['customer_last_name'] ?? ''));
        if ($name === '') {
            $name = trim($order['first_name'] . ' ' . $order['last_name']);
        }
        $number = (string) $order['order_number'];
        $date = date('F j, Y, g:i A', strtotime((string) $order['created_at']));
        $money = static fn(mixed $amount): string => '₱' . number_format((float) $amount, 2);
        $method = $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Online Payment';
        $paid = $order['payment_status'] === 'paid';
        $paymentStatus = $paid ? 'Paid' : 'Pending';
        $paymentNote = $paid ? 'Payment has been received.'
            : ($order['payment_method'] === 'cod'
                ? 'Payment has not been collected. Please pay ' . $money($order['total_amount']) . ' in cash when your order arrives.'
                : 'Payment is pending. This email confirms your order, not receipt of payment.');
        $delivery = array_values(array_filter([
            trim($order['first_name'] . ' ' . $order['last_name']),
            $order['address_line1'],
            $order['address_line2'] ?? '',
            $order['barangay'] ?? '',
            trim($order['city'] . ', ' . $order['province'] . ' ' . $order['postal_code']),
            $order['country'],
        ], static fn(string $value): bool => trim($value) !== ''));

        $rows = '';
        $plainItems = [];
        foreach ($items as $item) {
            $product = (string) $item['product_name'];
            $quantity = (int) $item['quantity'];
            $unit = $money($item['unit_price']);
            $line = $money($item['subtotal']);
            $rows .= '<tr><td style="padding:12px 8px;border-bottom:1px solid #e0d9cd;">' . pvHtml($product)
                . '</td><td style="padding:12px 8px;text-align:center;border-bottom:1px solid #e0d9cd;">' . $quantity
                . '</td><td style="padding:12px 8px;text-align:right;border-bottom:1px solid #e0d9cd;white-space:nowrap;">' . pvHtml($unit)
                . '</td><td style="padding:12px 8px;text-align:right;border-bottom:1px solid #e0d9cd;white-space:nowrap;">' . pvHtml($line) . '</td></tr>';
            $plainItems[] = $product . ' — Qty: ' . $quantity . ' × ' . $unit . ' = ' . $line;
        }
        $address = implode('<br>', array_map('pvHtml', $delivery));
        $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>PureVia order receipt</title></head>'
            . '<body style="margin:0;padding:24px 12px;background:#f8f5ef;color:#202820;font-family:Arial,sans-serif;">'
            . '<div style="max-width:640px;margin:auto;padding:28px 20px;background:#fff;border:1px solid #e0d9cd;border-radius:12px;">'
            . '<p style="margin:0;color:#38644b;font-size:22px;font-weight:bold;">PureVia</p>'
            . '<h1 style="font-size:25px;">Your order receipt</h1><p>Hello ' . pvHtml($name) . ',</p>'
            . '<p>Thank you for shopping with PureVia. Your order has been placed successfully.</p>'
            . '<p><strong>Order:</strong> ' . pvHtml($number) . '<br><strong>Date:</strong> ' . pvHtml($date) . ' (Philippine time)</p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:13px;"><thead><tr style="background:#ede9e1;">'
            . '<th scope="col" style="padding:10px 8px;text-align:left;">Item</th><th scope="col" style="padding:10px 8px;">Qty</th>'
            . '<th scope="col" style="padding:10px 8px;text-align:right;">Unit price</th><th scope="col" style="padding:10px 8px;text-align:right;">Subtotal</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<table role="presentation" style="width:100%;margin-top:16px;font-size:14px;">'
            . '<tr><td style="padding:6px 0;">Subtotal</td><td style="text-align:right;">' . pvHtml($money($order['subtotal'])) . '</td></tr>'
            . '<tr><td style="padding:6px 0;">Shipping</td><td style="text-align:right;">' . pvHtml($money($order['shipping_fee'])) . '</td></tr>'
            . '<tr style="color:#38644b;font-weight:bold;"><td style="padding:12px 0;">Order total</td><td style="text-align:right;">' . pvHtml($money($order['total_amount'])) . '</td></tr></table>'
            . '<p><strong>Payment method:</strong> ' . pvHtml($method) . '<br><strong>Payment status:</strong> ' . pvHtml($paymentStatus) . '</p>'
            . '<p style="padding:14px;background:#ede9e1;border-radius:8px;">' . pvHtml($paymentNote) . '</p>'
            . '<h2 style="font-size:17px;">Delivery details</h2><p style="line-height:1.7;">' . $address . '</p>'
            . '<p style="margin-top:24px;color:#6c746c;">You can view your order under My Account → My Orders.</p>'
            . '<p>PureVia Team</p></div></body></html>';
        $plain = "PUREVIA — ORDER RECEIPT\n\nHello " . $name . ",\n\nYour order has been placed successfully.\n"
            . 'Order: ' . $number . "\nDate: " . $date . " (Philippine time)\n\n"
            . implode("\n", $plainItems) . "\n\nSubtotal: " . $money($order['subtotal'])
            . "\nShipping: " . $money($order['shipping_fee']) . "\nOrder total: " . $money($order['total_amount'])
            . "\nPayment method: " . $method . "\nPayment status: " . $paymentStatus . "\n" . $paymentNote
            . "\n\nDelivery details:\n" . implode("\n", $delivery) . "\n\nView your order under My Account > My Orders.\n\nPureVia Team";

        return ['name' => $name, 'subject' => 'PureVia order receipt — ' . $number, 'html' => $html, 'plain' => $plain];
    }
}
