<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/order-cancellation.php';
require_once __DIR__ . '/../services/order-returns.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$orderId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$orderId) {
    header('Location: ' . BASE_URL . '/my-orders.php');
    exit;
}

function odEsc(mixed $value): string
{
    return pvHtml(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function odMoney(mixed $value): string
{
    return '₱' . number_format((float) $value, 2);
}

function odImage(?string $path): string
{
    $fallback = BASE_URL . '/assets/images/products/default-product.svg';
    $path = trim((string) $path);

    if ($path === '') {
        return $fallback;
    }

    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($path, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true)
            ? $path
            : $fallback;
    }

    $filename = basename(str_replace('\\', '/', $path));

    if (
        $filename === '' ||
        $filename === '.' ||
        $filename === '..' ||
        !is_file(__DIR__ . '/../uploads/products/' . $filename)
    ) {
        return $fallback;
    }

    return BASE_URL . '/uploads/products/' . rawurlencode($filename);
}

function odIcon(string $name): string
{
    $paths = [
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'box' => '<path d="m12 3 9 4.5v9L12 21l-9-4.5v-9L12 3Z"/><path d="m3 7.5 9 4.5 9-4.5M12 12v9M7.5 5.25l9 4.5"/>',
        'truck' => '<path d="M3 5h11v12H3zM14 9h4l3 4v4h-7M14 13h7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'bag' => '<path d="M6 7h12l1 14H5L6 7Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
        'pin' => '<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        'spark' => '<path d="M12 3c1.1 5.6 3.4 7.9 9 9-5.6 1.1-7.9 3.4-9 9-1.1-5.6-3.4-7.9-9-9 5.6-1.1 7.9-3.4 9-9Z"/>',
        'arrow-left' => '<path d="m12 5-7 7 7 7M5 12h15"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
    ];
    return '<svg class="od-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . ($paths[$name] ?? $paths['box']) . '</svg>';
}

function odProductParts(array $item): array
{
    $name = (string) $item['product_name'];
    if (preg_match('/^(.+?)\s+\(([^()]*)\)$/u', $name, $parts)) {
        return [$parts[1], $parts[2]];
    }
    return [$name, ''];
}

$stmt = $conn->prepare("
    SELECT
        o.*,
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
");

$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();

$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    http_response_code(404);
    exit('Order not found.');
}

$itemStmt = $conn->prepare("
    SELECT
        oi.id,
        oi.product_id,
        oi.product_name,
        oi.quantity,
        oi.unit_price,
        oi.subtotal,
        p.image,
        (
            SELECT pi.image_path
            FROM product_images pi
            WHERE pi.product_id = oi.product_id
            ORDER BY pi.sort_order ASC, pi.id ASC
            LIMIT 1
        ) AS gallery_image
    FROM order_items oi
    LEFT JOIN products p
        ON p.id = oi.product_id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");

$itemStmt->bind_param('i', $orderId);
$itemStmt->execute();

$items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$itemStmt->close();

$returnRequests = orderReturnEligible($order) ? customerReturnRequests($conn, $userId) : [];
$returnForm = orderReturnEligible($order) && !isset($returnRequests[(int) $orderId])
    ? [(int) $orderId => orderReturnFormData($order, $items, 'odImage')] : [];

$status = strtolower((string) $order['order_status']);
$paymentStatus = strtolower((string) $order['payment_status']);
$paymentMethod = strtolower((string) ($order['payment_method'] ?? 'cod'));

$statusLabels = [
    'pending' => 'Pending',
    'confirmed' => 'To ship',
    'preparing' => 'To ship',
    'shipping' => 'To receive',
    'delivered' => 'Completed',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

$statusLabel = $statusLabels[$status] ?? ucfirst($status);

$paymentLabels = [
    'cod' => 'Cash on Delivery',
    'card' => 'Credit or Debit Card',
    'online' => 'PayPal'
];

$paymentLabel = $paymentLabels[$paymentMethod]
    ?? ucfirst($paymentMethod);

$steps = [
    ['label' => $status === 'pending' ? 'Order placed' : 'Order confirmed', 'description' => $status === 'pending' ? 'Awaiting confirmation' : 'We received your order', 'icon' => 'check'],
    ['label' => 'Preparing', 'description' => 'Packed with care', 'icon' => 'box'],
    ['label' => 'On the way', 'description' => 'With our delivery partner', 'icon' => 'truck'],
    ['label' => 'Delivered', 'description' => 'Enjoy your skincare', 'icon' => 'bag'],
];

[$progressTitle, $progressHint] = match ($status) {
    'pending' => ['Your order is awaiting confirmation', 'Confirming shortly'],
    'confirmed', 'preparing' => ['Your order is being prepared', 'Being prepared'],
    'shipping' => ['Your parcel is on its way', 'With our delivery partner'],
    'delivered', 'completed' => ['Your order has been delivered', 'Delivery complete'],
    'cancelled' => ['This order has been cancelled', 'Cancelled'],
    default => ['Your order updates', $statusLabel],
};
$statusIcon = match ($status) {
    'shipping' => 'truck', 'delivered', 'completed' => 'bag', default => 'box',
};

$stepIndex = match ($status) {
    'pending' => 0,
    'confirmed', 'preparing' => 1,
    'shipping' => 2,
    'delivered', 'completed' => 3,
    default => -1
};

$canCancel = canCancelCustomerOrder($order);

$recipient = trim(
    (string) ($order['first_name'] ?? '') . ' ' .
    (string) ($order['last_name'] ?? '')
);

$addressParts = array_filter([
    $order['address_line1'] ?? '',
    $order['address_line2'] ?? '',
    $order['barangay'] ?? '',
    $order['city'] ?? '',
    $order['province'] ?? '',
    $order['postal_code'] ?? ''
], static fn($value) => trim((string) $value) !== '');

$fullAddress = implode(', ', $addressParts);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= odEsc($order['order_number']) ?> | PureVia
    </title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/search.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/order-details.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=Poppins:wght@300;400;500;600&family=Source+Serif+4:wght@500&display=swap" rel="stylesheet">
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/order-cancellation.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/form2.css">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>
<?php if (($order['payment_method'] ?? '') === 'online' && $order['payment_status'] === 'pending' && $order['order_status'] === 'pending'): ?>
<p class="od-payment-resume"><a href="<?= BASE_URL ?>/paypal-payment.php?order_id=<?= (int)$orderId ?>">Continue or cancel PayPal payment</a></p>
<?php endif; ?>

<main class="od-page">
    <div class="od-container">
        <a href="<?= BASE_URL ?>/my-orders.php" class="od-back">
            <?= odIcon('arrow-left') ?> Back to my orders
        </a>

        <div class="od-heading">
            <div>
                <span class="od-eyebrow">Order <?= odEsc($order['order_number']) ?></span>
                <h1>Order details</h1>
                <p>Placed on <time datetime="<?= odEsc(date('Y-m-d', strtotime($order['created_at']))) ?>"><?= odEsc(date('F j, Y', strtotime($order['created_at']))) ?></time></p>
            </div>
            <span class="od-status od-status-<?= odEsc($status) ?>"><?= odIcon($statusIcon) ?> <?= odEsc($statusLabel) ?></span>
        </div>

        <section class="od-progress-card" aria-label="Order progress">
            <div class="od-progress-header">
                <div>
                    <span class="od-progress-eyebrow">Order progress</span>
                    <h2><?= odEsc($progressTitle) ?></h2>
                </div>
                <span class="od-progress-hint"><?= odEsc($progressHint) ?></span>
            </div>
            <?php if ($status === 'cancelled'): ?>
                <div class="od-cancelled-progress" id="cancellation-details"><?= odIcon('close') ?> This order has been cancelled.</div>
            <?php else: ?>
                <div class="od-progress" role="list">
                    <?php foreach ($steps as $index => $step): ?>
                        <div class="od-step<?= $index < $stepIndex ? ' is-complete' : '' ?><?= $index === $stepIndex ? ' is-current' : '' ?>"
                             role="listitem" <?= $index === $stepIndex ? 'aria-current="step"' : '' ?>>
                            <span class="od-step-circle"><?= odIcon($step['icon']) ?></span>
                            <span class="od-step-label"><?= odEsc($step['label']) ?></span>
                            <span class="od-step-description"><?= odEsc($step['description']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="od-grid">
            <div class="od-selection">
                <section class="od-card od-items-card">
                    <div class="od-items-heading">
                        <div><span class="od-eyebrow">Your selection</span><h2>Items ordered</h2></div>
                        <span class="od-item-count"><?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?></span>
                    </div>
                    <div class="od-items">
                        <?php foreach ($items as $item): ?>
                            <?php [$productName, $productMeta] = odProductParts($item); ?>
                            <div class="od-item">
                                <div class="od-item-image">
                                    <img src="<?= odEsc(odImage($item['gallery_image'] ?: $item['image'])) ?>"
                                         alt="<?= odEsc($productName) ?>" loading="lazy" width="158" height="168"
                                         data-image-fallback="<?= odEsc(BASE_URL) ?>/assets/images/products/default-product.svg">
                                    <span class="od-image-brand">PureVia</span>
                                </div>
                                <div class="od-item-info">
                                    <span class="od-item-category">Daily skin essential</span>
                                    <h3><?= odEsc($productName) ?></h3>
                                    <?php if ($productMeta !== ''): ?><p class="od-item-meta"><?= odEsc($productMeta) ?></p><?php endif; ?>
                                    <div class="od-item-facts">
                                        <span>Quantity <strong><?= (int) $item['quantity'] ?></strong></span>
                                        <span>Price <strong><?= odMoney($item['unit_price']) ?></strong></span>
                                    </div>
                                </div>
                                <strong class="od-item-price"><?= odMoney($item['subtotal']) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <aside class="od-packing-note">
                    <span class="od-note-icon"><?= odIcon('spark') ?></span>
                    <div>
                        <span class="od-note-eyebrow">A note from PureVia</span>
                        <h2>Skincare, thoughtfully packed</h2>
                        <p>Every order is checked by hand and packed to keep your products safe on their way to you.</p>
                    </div>
                </aside>
            </div>

            <div class="od-sidebar">
                <section class="od-card od-payment-card">
                    <div class="od-card-heading">
                        <span class="od-card-icon"><?= odIcon('bag') ?></span>
                        <div><span class="od-eyebrow">Payment</span><h2>Order summary</h2></div>
                    </div>
                    <div class="od-summary-row"><span>Subtotal</span><strong><?= odMoney($order['subtotal']) ?></strong></div>
                    <div class="od-summary-row">
                        <span>Shipping</span>
                        <?php if ((float) $order['shipping_fee'] === 0.0): ?><strong class="od-complimentary">Complimentary</strong>
                        <?php else: ?><strong><?= odMoney($order['shipping_fee']) ?></strong><?php endif; ?>
                    </div>
                    <div class="od-summary-row od-summary-total"><span>Total</span><strong><?= odMoney($order['total_amount']) ?></strong></div>
                    <p class="od-payment-note"><span class="od-payment-dot" aria-hidden="true"></span><?= odEsc($paymentLabel) ?><?php if ($paymentMethod !== 'cod'): ?><span class="od-payment-<?= odEsc($paymentStatus) ?>"> &middot; <?= odEsc(ucfirst($paymentStatus)) ?></span><?php endif; ?></p>
                </section>

                <section class="od-card od-address-card">
                    <div class="od-card-heading">
                        <span class="od-card-icon"><?= odIcon('pin') ?></span>
                        <div><span class="od-eyebrow">Shipping to</span><h2>Delivery address</h2></div>
                    </div>
                    <?php if ($recipient !== ''): ?><strong class="od-recipient"><?= odEsc($recipient) ?></strong><?php endif; ?>
                    <?php if (!empty($order['phone'])): ?><p class="od-address-phone"><?= odEsc($order['phone']) ?></p><?php endif; ?>
                    <p class="od-address-text"><?= odEsc($fullAddress ?: 'Address not available') ?></p>
                </section>

                <?php if ($status === 'pending'): ?>
                    <div class="od-cancellation-actions">
                        <a class="od-contact-support" href="<?= odEsc('mailto:purevia@gmail.com?subject=' . rawurlencode('Order ' . $order['order_number'])) ?>">Contact support <?= odIcon('chevron') ?></a>
                        <button type="button" class="od-cancel-order" id="odCancelOrder" data-cancel-id="<?= (int) $orderId ?>" data-order-number="<?= odEsc($order['order_number']) ?>" aria-describedby="odCancellationNotice" <?= $canCancel ? '' : 'disabled' ?>>Cancel order</button>
                        <p class="od-cancellation-deadline" id="odCancellationNotice"><?= odEsc(orderCancellationNotice($order)) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($status === 'cancelled'): ?>
                    <button type="button" class="od-contact-support" data-cancellation-details="<?= (int) $orderId ?>">Cancellation details <?= odIcon('chevron') ?></button>
                <?php endif; ?>
                <?php if (orderReturnEligible($order)): ?>
                    <button type="button" class="od-contact-support" data-return-id="<?= (int) $orderId ?>" <?= isset($returnRequests[(int) $orderId]) || !$items ? 'disabled' : '' ?>><?= isset($returnRequests[(int) $orderId]) ? 'Return requested' : 'Return item' ?></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/order_cancellation_modals.php'; ?>
<?php include __DIR__ . '/../includes/order_return_modal.php'; ?>

<script nonce="<?= pvScriptNonce() ?>">
window.PUREVIA_ORDER_DETAILS = <?= pvJson([
    'baseUrl' => BASE_URL,
    'orderId' => (int) $orderId,
    'csrfToken' => $_SESSION['csrf_token'],
    'cancellations' => $status === 'cancelled' ? [(int) $orderId => orderCancellationDetails($order)] : [],
    'returns' => $returnForm
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/search.js?v=public-root-1"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/order-cancellation.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/order-returns.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/order-details.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
