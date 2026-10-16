<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';

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
    return htmlspecialchars(
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
    $fallback = BASE_URL . '/assets/images/products/default-product.jpg';
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
        !is_file(__DIR__ . '/uploads/products/' . $filename)
    ) {
        return $fallback;
    }

    return BASE_URL . '/uploads/products/' . rawurlencode($filename);
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

$status = strtolower((string) $order['order_status']);
$paymentStatus = strtolower((string) $order['payment_status']);
$paymentMethod = strtolower((string) ($order['payment_method'] ?? 'cod'));

$statusLabels = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'preparing' => 'Processing',
    'shipping' => 'Shipped',
    'delivered' => 'Delivered',
    'completed' => 'Delivered',
    'cancelled' => 'Cancelled'
];

$statusLabel = $statusLabels[$status] ?? ucfirst($status);

$paymentLabels = [
    'cod' => 'Cash on Delivery',
    'gcash' => 'GCash',
    'card' => 'Credit or Debit Card',
    'online' => 'Online Payment'
];

$paymentLabel = $paymentLabels[$paymentMethod]
    ?? ucfirst($paymentMethod);

$steps = [
    ['label' => 'Pending', 'icon' => '✓'],
    ['label' => 'Processing', 'icon' => '⚙'],
    ['label' => 'Shipped', 'icon' => '🚚'],
    ['label' => 'Delivered', 'icon' => '✓']
];

$stepIndex = match ($status) {
    'pending' => 0,
    'confirmed', 'preparing' => 1,
    'shipping' => 2,
    'delivered', 'completed' => 3,
    default => -1
};

$canCancel =
    $status === 'pending' &&
    $paymentStatus === 'pending' &&
    $paymentMethod === 'cod';

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
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="od-page">

    <div class="od-container">

        <!-- BACK -->

        <a href="<?= BASE_URL ?>/my-orders.php" class="od-back">
            <span>←</span>
            Back to Orders
        </a>

        <!-- PAGE HEADING -->

        <div class="od-heading">

            <div>
                <h1>Order Details</h1>

                <p>
                    <?= odEsc($order['order_number']) ?>
                </p>
            </div>

            <span class="od-status od-status-<?= odEsc($status) ?>">
                <?= odEsc($statusLabel) ?>
            </span>

        </div>

        <!-- ORDER PROGRESS -->

        <section class="od-progress-card"
                 aria-label="Order progress">

            <?php if ($status === 'cancelled'): ?>

                <div class="od-cancelled-progress" id="cancellation-details">
                    <i class="fa-solid fa-circle-xmark"></i>
                    This order has been cancelled.
                </div>

            <?php else: ?>

                <div class="od-progress">

                    <?php foreach ($steps as $index => $step): ?>

                        <?php
                            $isComplete = $index <= $stepIndex;
                            $isCurrent = $index === $stepIndex;
                        ?>

                        <div class="od-step <?= $isComplete ? 'is-complete' : '' ?>
                             <?= $isCurrent ? 'is-current' : '' ?>">

                            <div class="od-step-top">

                                <span class="od-step-circle">
                                    <?= odEsc($step['icon']) ?>
                                </span>

                                <?php if ($index < 3): ?>
                                    <span class="od-step-line"></span>
                                <?php endif; ?>

                            </div>

                            <span class="od-step-label">
                                <?= odEsc($step['label']) ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <!-- DETAILS GRID -->

        <div class="od-grid">

            <!-- LEFT: PRODUCTS -->

            <section class="od-card od-items-card">

                <h2>Items Ordered</h2>

                <div class="od-items">

                    <?php foreach ($items as $item): ?>

                        <?php
                            $image = $item['gallery_image']
                                ?: $item['image'];
                        ?>

                        <div class="od-item">

                            <div class="od-item-image">

                                <img
                                    src="<?= odEsc(odImage($image)) ?>"
                                    alt="<?= odEsc($item['product_name']) ?>"
                                    loading="lazy"
                                    onerror="this.onerror=null;this.src='<?= odEsc(BASE_URL) ?>/assets/images/products/default-product.jpg';"
                                >

                            </div>

                            <div class="od-item-info">

                                <strong>
                                    <?= odEsc($item['product_name']) ?>
                                </strong>

                                <span>
                                    Qty: <?= (int) $item['quantity'] ?>
                                    × <?= odMoney($item['unit_price']) ?>
                                </span>

                            </div>

                            <strong class="od-item-price">
                                <?= odMoney($item['subtotal']) ?>
                            </strong>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>

            <!-- RIGHT: PAYMENT + ADDRESS -->

            <div class="od-sidebar">

                <section class="od-card od-payment-card">

                    <h2>Payment Summary</h2>

                    <div class="od-summary-row">
                        <span>Subtotal</span>
                        <strong>
                            <?= odMoney($order['subtotal']) ?>
                        </strong>
                    </div>

                    <div class="od-summary-row">
                        <span>Shipping</span>
                        <strong>
                            <?= odMoney($order['shipping_fee']) ?>
                        </strong>
                    </div>

                    <div class="od-summary-row od-summary-total">
                        <span>Total</span>
                        <strong>
                            <?= odMoney($order['total_amount']) ?>
                        </strong>
                    </div>

                    <p class="od-payment-note">

                        via <?= odEsc($paymentLabel) ?>

                        <span class="od-payment-<?= odEsc($paymentStatus) ?>">
                            · <?= odEsc(ucfirst($paymentStatus)) ?>
                        </span>

                    </p>

                </section>

                <section class="od-card od-address-card">

                    <h2>Delivery Address</h2>

                    <?php if ($recipient !== ''): ?>
                        <strong class="od-recipient">
                            <?= odEsc($recipient) ?>
                        </strong>
                    <?php endif; ?>

                    <?php if (!empty($order['phone'])): ?>
                        <p><?= odEsc($order['phone']) ?></p>
                    <?php endif; ?>

                    <p>
                        <?= odEsc($fullAddress ?: 'Address not available') ?>
                    </p>

                </section>

                <?php if ($canCancel): ?>

                    <button
                        type="button"
                        class="od-cancel-order"
                        id="odCancelOrder"
                    >
                        Cancel Order
                    </button>

                    <p class="od-error"
                       id="odCancelError"
                       role="alert"
                       hidden></p>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>

<?php if ($canCancel): ?>

    <!-- CANCELLATION CONFIRMATION -->

    <div class="od-dialog-backdrop" id="odCancelDialog" hidden>

        <div class="od-dialog"
             role="dialog"
             aria-modal="true"
             aria-labelledby="odDialogTitle">

            <h2 id="odDialogTitle">Cancel Order?</h2>

            <p>
                Are you sure you want to cancel
                <?= odEsc($order['order_number']) ?>?
                This action cannot be undone.
            </p>

            <div class="od-dialog-actions">

                <button type="button" id="odKeepOrder">
                    Keep Order
                </button>

                <button type="button" id="odConfirmCancel">
                    Yes, Cancel Order
                </button>

            </div>

        </div>

    </div>

<?php endif; ?>

<script>
window.PUREVIA_ORDER_DETAILS = <?= json_encode([
    'baseUrl' => BASE_URL,
    'orderId' => (int) $orderId,
    'csrfToken' => $_SESSION['csrf_token']
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script src="<?= BASE_URL ?>/assets/js/order-details.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
