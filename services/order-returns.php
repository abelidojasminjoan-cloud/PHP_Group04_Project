<?php
declare(strict_types=1);

function orderReturnReasons(): array
{
    return [
        'Product arrived damaged',
        'Product arrived opened or leaking',
        'Skin reaction or sensitivity',
        'Received the wrong product',
        'Product is defective or has a quality issue',
        'Product does not match the description',
        'Other',
    ];
}

function orderReturnEligible(array $order): bool
{
    return in_array($order['order_status'] ?? '', ['delivered', 'completed'], true);
}

function customerReturnRequests(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare('SELECT r.order_id FROM order_return_requests r JOIN orders o ON o.id = r.order_id WHERE o.user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $ids = array_fill_keys(array_map('intval', array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'order_id')), true);
    $stmt->close();
    return $ids;
}

function orderReturnFormData(array $order, array $items, callable $imageUrl): array
{
    return [
        'number' => (string) $order['order_number'],
        'items' => array_map(static fn(array $item): array => [
            'id' => (int) $item['id'],
            'name' => (string) $item['product_name'],
            'quantity' => (int) $item['quantity'],
            'price' => '₱' . number_format((float) $item['unit_price'], 2),
            'image' => $imageUrl($item['gallery_image'] ?: $item['image']),
        ], $items),
    ];
}
