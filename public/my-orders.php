<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/order-cancellation.php';
require_once __DIR__ . '/../services/order-returns.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

function moEsc(mixed $value): string
{
    return pvHtml(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function moMoney(mixed $value): string
{
    return '₱' . number_format((float) $value, 2);
}

function moImage(?string $path): string
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

    if ($filename === '' || $filename === '.' || $filename === '..') {
        return $fallback;
    }

    if (!is_file(__DIR__ . '/../uploads/products/' . $filename)) {
        return $fallback;
    }

    return BASE_URL . '/uploads/products/' . rawurlencode($filename);
}

function moTab(array $order): string
{
    return match ((string) $order['order_status']) {
        'cancelled' => 'cancelled',
        'returned' => 'returned',
        'delivered', 'completed' => 'completed',
        'shipping' => 'receive',
        'confirmed', 'preparing' => 'ship',
        default => 'pending'
    };
}

function moPresentation(array $order): array
{
    return match (moTab($order)) {
        'ship' => ['label' => 'To ship', 'state' => 'Being prepared', 'message' => 'Getting ready to ship', 'icon' => 'box', 'progress' => 2],
        'receive' => ['label' => 'To receive', 'state' => 'In transit', 'message' => 'On its way to you', 'icon' => 'truck', 'progress' => 3],
        'completed' => ['label' => 'Completed', 'state' => 'Delivered', 'message' => 'Delivery complete', 'icon' => 'bag', 'progress' => 4],
        'cancelled' => ['label' => 'Cancelled', 'state' => 'Order cancelled', 'message' => '', 'icon' => 'box', 'progress' => 0],
        'returned' => ['label' => 'Returned', 'state' => 'Return completed', 'message' => '', 'icon' => 'bag', 'progress' => 0],
        default => ['label' => 'Pending', 'state' => 'Awaiting confirmation', 'message' => $order['payment_status'] === 'failed' ? 'Please review your payment' : 'Confirming shortly', 'icon' => 'box', 'progress' => 1],
    };
}

function moIcon(string $name): string
{
    $paths = [
        'box' => '<path d="m12 3 9 4.5v9L12 21l-9-4.5v-9L12 3Z"/><path d="m3 7.5 9 4.5 9-4.5M12 12v9M7.5 5.25l9 4.5"/>',
        'truck' => '<path d="M3 5h11v12H3zM14 9h4l3 4v4h-7M14 13h7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'bag' => '<path d="M6 7h12l1 14H5L6 7Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/>',
        'arrow-left' => '<path d="m12 5-7 7 7 7M5 12h15"/>',
        'chevron' => '<path d="m9 5 7 7-7 7"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . ($paths[$name] ?? $paths['box']) . '</svg>';
}

function moProductParts(array $item): array
{
    $name = (string) $item['product_name'];
    if (preg_match('/^(.+?)\s+\(([^()]*)\)$/u', $name, $parts)) {
        return [$parts[1], $parts[2]];
    }
    return [$name, moMoney($item['unit_price']) . ' each'];
}

// GET CUSTOMER ORDERS

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
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC, o.id DESC
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// GET ORDER ITEMS

$orderIds = array_map(
    static fn(array $order): int => (int) $order['id'],
    $orders
);

$itemsByOrder = [];

if ($orderIds) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $types = str_repeat('i', count($orderIds));

    $itemStmt = $conn->prepare("
        SELECT
            oi.id,
            oi.order_id,
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
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.id ASC
    ");

    $itemStmt->bind_param($types, ...$orderIds);
    $itemStmt->execute();

    foreach ($itemStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
        $itemsByOrder[(int) $item['order_id']][] = $item;
    }

    $itemStmt->close();
}

$orderTabs = [
    'all' => 'All', 'pending' => 'Pending', 'ship' => 'To ship',
    'receive' => 'To receive', 'completed' => 'Completed',
    'cancelled' => 'Cancelled', 'returned' => 'Returned',
];
$tabCounts = array_fill_keys(array_keys($orderTabs), 0);
$tabCounts['all'] = count($orders);
$nextDelivery = null;
$deliveryPriority = 0;
$cancellations = [];
$returnForms = [];
$returnRequests = $orders ? customerReturnRequests($conn, $userId) : [];
foreach ($orders as $order) {
    $orderTab = moTab($order);
    $tabCounts[$orderTab]++;
    if ($orderTab === 'cancelled') {
        $cancellations[(int) $order['id']] = orderCancellationDetails($order);
    }
    if (orderReturnEligible($order) && !isset($returnRequests[(int) $order['id']])) {
        $returnForms[(int) $order['id']] = orderReturnFormData($order, $itemsByOrder[(int) $order['id']] ?? [], 'moImage');
    }
    $priority = ['pending' => 1, 'ship' => 2, 'receive' => 3][$orderTab] ?? 0;
    if ($priority > $deliveryPriority) {
        $deliveryPriority = $priority;
        $nextDelivery = $order;
    }
}

// CSRF TOKEN

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Orders | PureVia</title>

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
          href="<?= BASE_URL ?>/assets/css/my-orders.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500&family=Poppins:wght@300;400;500;600&family=Source+Serif+4:wght@500&display=swap" rel="stylesheet">

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/order-cancellation.css">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="mo-page">
    <div class="mo-container">
        <a href="<?= BASE_URL ?>/account/profile.php" class="mo-back">
            <?= moIcon('arrow-left') ?> Back to account
        </a>

        <div class="mo-heading">
            <div>
                <span class="mo-eyebrow">Your purchases</span>
                <h1>My orders</h1>
                <p>Keep an eye on deliveries, review past favorites, and manage every purchase.</p>
            </div>
            <div class="mo-total">
                <span class="mo-total-icon"><?= moIcon('box') ?></span>
                <div><strong><?= count($orders) ?></strong><span>Total orders</span></div>
            </div>
        </div>

        <section class="mo-delivery" aria-label="Next delivery">
            <div class="mo-delivery-content">
                <span class="mo-delivery-dot" aria-hidden="true"></span>
                <div>
                    <span class="mo-delivery-label">Next delivery</span>
                    <?php if ($nextDelivery): ?>
                        <?php
                        $deliveryPresentation = moPresentation($nextDelivery);
                        $deliveryItems = $itemsByOrder[(int) $nextDelivery['id']] ?? [];
                        $deliveryName = $deliveryItems ? moProductParts($deliveryItems[0])[0] : $nextDelivery['order_number'];
                        ?>
                        <h2><?= moEsc($deliveryPresentation['message']) ?></h2>
                        <p><?= moEsc($deliveryName) ?><?php if (count($deliveryItems) > 1): ?> &middot; +<?= count($deliveryItems) - 1 ?> more<?php endif; ?></p>
                    <?php else: ?>
                        <h2>No upcoming deliveries</h2>
                        <p>Your next delivery will appear here.</p>
                    <?php endif; ?>
                </div>
            </div>
            <a class="mo-delivery-button" href="<?= BASE_URL ?><?= $nextDelivery ? '/public/order-details.php?id=' . (int) $nextDelivery['id'] : '/public/shop.php' ?>">
                <?= $nextDelivery ? 'Track delivery' : 'Explore skincare' ?> <?= moIcon('chevron') ?>
            </a>
        </section>

        <section class="mo-orders-panel" aria-label="Your orders">
            <div class="mo-tabs" role="tablist" aria-label="Order status">
                <?php foreach ($orderTabs as $key => $label): ?>
                    <button type="button" class="mo-tab<?= $key === 'all' ? ' active' : '' ?>"
                            id="moTab-<?= moEsc($key) ?>" role="tab" data-tab="<?= moEsc($key) ?>"
                            aria-selected="<?= $key === 'all' ? 'true' : 'false' ?>"
                            tabindex="<?= $key === 'all' ? '0' : '-1' ?>" aria-controls="moOrderList">
                        <?= moEsc($label) ?><?php if ($tabCounts[$key] > 0): ?> <span class="mo-tab-count"><?= $tabCounts[$key] ?></span><?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="mo-panel-content">
                <div class="mo-search">
                    <span class="mo-search-icon"><?= moIcon('search') ?></span>
                    <input type="search" id="moSearch" placeholder="Search by order ID or product"
                           autocomplete="off" aria-label="Search your orders">
                </div>

                <div class="mo-order-list" id="moOrderList" role="tabpanel" aria-labelledby="moTab-all" tabindex="0">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $orderId = (int) $order['id'];
                        $items = $itemsByOrder[$orderId] ?? [];
                        $tab = moTab($order);
                        $presentation = moPresentation($order);
                        $canCancel = canCancelCustomerOrder($order);
                        $detailsUrl = BASE_URL . '/public/order-details.php?id=' . $orderId;
                        $productNames = implode(' ', array_column($items, 'product_name'));
                        $firstProductId = 0;
                        foreach ($items as $item) {
                            if ((int) $item['product_id'] > 0) {
                                $firstProductId = (int) $item['product_id'];
                                break;
                            }
                        }
                        $buyAgainUrl = $firstProductId ? BASE_URL . '/public/product.php?id=' . $firstProductId : BASE_URL . '/public/shop.php';
                        $supportUrl = 'mailto:purevia@gmail.com?subject=' . rawurlencode('Order ' . $order['order_number']);
                        ?>
                        <article class="mo-order-card mo-card-<?= moEsc($tab) ?>"
                                 data-tab="<?= moEsc($tab) ?>" data-search="<?= moEsc(mb_strtolower($order['order_number'] . ' ' . $productNames)) ?>">
                            <div class="mo-order-header">
                                <div class="mo-order-identity">
                                    <span class="mo-field-label">Order number</span>
                                    <strong class="mo-order-number"><?= moEsc($order['order_number']) ?></strong>
                                </div>
                                <div class="mo-order-date">
                                    <span class="mo-field-label">Placed on</span>
                                    <time datetime="<?= moEsc(date('Y-m-d', strtotime($order['created_at']))) ?>"><?= moEsc(date('F j, Y', strtotime($order['created_at']))) ?></time>
                                </div>
                                <span class="mo-status mo-status-<?= moEsc($tab) ?>"><?= moIcon($presentation['icon']) ?> <?= moEsc($presentation['label']) ?></span>
                            </div>
                            <div class="mo-order-body">
                                <div class="mo-order-items">
                                    <?php foreach ($items as $item): ?>
                                        <?php [$productName, $productMeta] = moProductParts($item); ?>
                                        <div class="mo-product">
                                            <div class="mo-product-image">
                                                <img src="<?= moEsc(moImage($item['gallery_image'] ?: $item['image'])) ?>"
                                                     alt="<?= moEsc($productName) ?>" loading="lazy" width="100" height="100"
                                                     data-image-fallback="<?= moEsc(BASE_URL) ?>/assets/images/products/default-product.svg">
                                            </div>
                                            <div class="mo-product-info">
                                                <span class="mo-product-state"><?= moEsc($presentation['state']) ?></span>
                                                <?php if ((int) $item['product_id'] > 0): ?>
                                                    <a href="<?= BASE_URL ?>/public/product.php?id=<?= (int) $item['product_id'] ?>" class="mo-product-name"><?= moEsc($productName) ?></a>
                                                <?php else: ?>
                                                    <strong class="mo-product-name"><?= moEsc($productName) ?></strong>
                                                <?php endif; ?>
                                                <span class="mo-product-meta"><?= moEsc($productMeta) ?></span>
                                                <span class="mo-product-quantity">Qty <?= (int) $item['quantity'] ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (!$items): ?><p class="mo-product-meta">Product details are available in your order.</p><?php endif; ?>
                                </div>
                                <div class="mo-order-update">
                                    <?php if ($presentation['message'] !== ''): ?><span><?= moEsc($presentation['message']) ?></span><?php endif; ?>
                                    <?php if ($tab === 'receive'): ?>
                                        <div class="mo-delivery-progress" role="img" aria-label="In transit: delivery is on its way">
                                            <?php for ($step = 1; $step <= 4; $step++): ?><span class="<?= $step <= $presentation['progress'] ? 'is-complete' : '' ?>"></span><?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mo-order-total"><span>Total</span><strong><?= moMoney($order['total_amount']) ?></strong></div>
                            </div>
                            <div class="mo-order-footer">
                                <a href="<?= moEsc($detailsUrl) ?>" class="mo-details-link">View order details <?= moIcon('chevron') ?></a>
                                <div class="mo-order-actions<?= $tab === 'pending' ? ' mo-pending-actions' : '' ?>">
                                    <?php if ($tab === 'pending'): ?>
                                        <a href="<?= moEsc($supportUrl) ?>" class="mo-button mo-button-outline">Contact support</a>
                                        <div class="mo-cancellation-control">
                                            <button type="button" class="mo-button mo-button-primary mo-cancel-order"
                                                    data-cancel-id="<?= $orderId ?>" data-order-number="<?= moEsc($order['order_number']) ?>"
                                                    aria-describedby="moCancellationNotice-<?= $orderId ?>" <?= $canCancel ? '' : 'disabled' ?>>Cancel order</button>
                                            <p class="mo-cancellation-deadline" id="moCancellationNotice-<?= $orderId ?>"><?= moEsc(orderCancellationNotice($order)) ?></p>
                                        </div>
                                    <?php else: ?>
                                        <?php if ($tab === 'cancelled'): ?><button type="button" class="mo-button mo-button-outline" data-cancellation-details="<?= $orderId ?>">Cancellation details</button><?php endif; ?>
                                        <?php if ($tab === 'ship'): ?><a href="<?= moEsc($supportUrl) ?>" class="mo-button mo-button-outline">Contact support</a><?php endif; ?>
                                        <?php if ($tab === 'completed'): ?>
                                            <?php if ($firstProductId): ?><a href="<?= moEsc($buyAgainUrl) ?>#customerReviews" class="mo-button mo-button-outline">Write a review</a><?php endif; ?>
                                            <button type="button" class="mo-button mo-button-outline" data-return-id="<?= $orderId ?>" <?= isset($returnRequests[$orderId]) || !$items ? 'disabled' : '' ?>><?= isset($returnRequests[$orderId]) ? 'Return requested' : 'Return item' ?></button>
                                        <?php endif; ?>
                                        <?php if ($tab === 'returned'): ?><a href="<?= moEsc($detailsUrl) ?>" class="mo-button mo-button-outline">Return details</a><?php endif; ?>
                                        <a href="<?= moEsc($buyAgainUrl) ?>" class="mo-button mo-button-primary">Buy again</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="mo-empty" id="moEmpty" <?= $orders ? 'hidden' : '' ?>>
                    <span class="mo-empty-icon"><?= moIcon('bag') ?></span>
                    <h2><?= $orders ? 'No matching orders' : 'Your orders start here' ?></h2>
                    <p><?= $orders ? 'Try another status or search term.' : 'Your purchases will appear here once you place an order.' ?></p>
                    <a href="<?= BASE_URL ?>/public/shop.php" class="mo-button mo-button-outline">Explore skincare</a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../includes/order_cancellation_modals.php'; ?>
<?php include __DIR__ . '/../includes/order_return_modal.php'; ?>

<script nonce="<?= pvScriptNonce() ?>">
    window.PUREVIA_ORDERS = {
        baseUrl: <?= pvJson(BASE_URL) ?>,
        csrfToken: <?= pvJson($csrfToken) ?>,
        cancellations: <?= pvJson($cancellations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        returns: <?= pvJson($returnForms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    };
</script>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/order-cancellation.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/order-returns.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/my-orders.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
