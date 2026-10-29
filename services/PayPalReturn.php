<?php
declare(strict_types=1);

/** Choose an automatic POST action; URL parameters never establish payment status. */
function pureViaPayPalReturnAction(array $query, array $order): string
{
    if (($order['state'] ?? '') !== 'pending' || ($order['order_status'] ?? '') !== 'pending' || isset($query['cancelled'])) return '';
    if (isset($query['token'])) {
        $token = $query['token'];
        $expected = $order['paypal_order_id'] ?? null;
        if (!is_string($token) || !is_string($expected) || $expected === '' || !hash_equals($expected, $token)) {
            throw new DomainException('This PayPal return does not match your order. Use Check payment to verify its status.');
        }
        return 'confirm';
    }
    return ($query['start'] ?? null) === '1' ? 'start' : '';
}