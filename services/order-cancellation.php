<?php
declare(strict_types=1);

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
