<?php
declare(strict_types=1);

function orderCancellationReasons(): array
{
    return [
        'Changed my mind',
        'Ordered the wrong product or quantity',
        'Need to change the delivery address',
        'Found a better price',
        'Order is taking too long',
        'Other',
    ];
}

function orderCancellationDetails(array $order): array
{
    $requested = 'Not recorded';
    if (!empty($order['cancelled_at'])) {
        try {
            $requested = (new DateTimeImmutable($order['cancelled_at']))->format('M j, Y');
        } catch (Exception $ignored) {
        }
    }

    $refund = match ($order['payment_status'] ?? '') {
        'refunded' => 'Your payment has been marked as refunded.',
        'paid' => 'Please contact support for the status of your refund.',
        default => ($order['payment_method'] ?? '') === 'cod'
            ? 'No payment was collected for this cash-on-delivery order.'
            : 'No verified payment is recorded for this order.',
    };

    return [
        'number' => (string) $order['order_number'],
        'requested' => $requested,
        'reason' => ($order['cancellation_reason'] ?? '') ?: 'Not recorded',
        'details' => ($order['cancellation_details'] ?? '') ?: 'No additional details provided.',
        'refund' => $refund,
    ];
}

function orderCancellationDeadline(array $order): ?DateTimeImmutable
{
    $createdAt = $order['created_at'] ?? null;
    if (!is_string($createdAt) || trim($createdAt) === '') {
        return null;
    }

    try {
        return (new DateTimeImmutable($createdAt))->modify('+1 day')->setTime(23, 59, 59);
    } catch (Exception $error) {
        return null;
    }
}

function orderCancellationEligible(array $order): bool
{
    return ($order['order_status'] ?? '') === 'pending'
        && ($order['payment_status'] ?? '') === 'pending'
        && ($order['payment_method'] ?? '') === 'cod';
}

function canCancelCustomerOrder(array $order, ?DateTimeImmutable $now = null): bool
{
    $deadline = orderCancellationDeadline($order);
    return orderCancellationEligible($order) && $deadline !== null
        && ($now ?? new DateTimeImmutable())->getTimestamp() <= $deadline->getTimestamp();
}

function orderCancellationNotice(array $order, ?DateTimeImmutable $now = null): string
{
    $deadline = orderCancellationDeadline($order);
    if (!orderCancellationEligible($order) || $deadline === null) {
        return 'Contact support to request cancellation.';
    }

    $prefix = canCancelCustomerOrder($order, $now)
        ? 'Cancellation available until ' : 'Cancellation closed on ';
    return $prefix . $deadline->format('M j, Y') . ' · ' . $deadline->format('g:i A');
}
