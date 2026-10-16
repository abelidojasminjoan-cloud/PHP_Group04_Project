
<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

function moEsc(mixed $value): string
{
    return htmlspecialchars(
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

    if ($filename === '' || $filename === '.' || $filename === '..') {
        return $fallback;
    }

    if (!is_file(__DIR__ . '/uploads/products/' . $filename)) {
        return $fallback;
    }

    return BASE_URL . '/uploads/products/' . rawurlencode($filename);
}

function moTab(array $order): string
{
    $status = (string) $order['order_status'];
    $payment = (string) $order['payment_status'];
    $method = (string) ($order['payment_method'] ?? 'cod');

    if ($status === 'cancelled') {
        return 'cancelled';
    }

    if (in_array($status, ['delivered', 'completed'], true)) {
        return 'completed';
    }

    if ($status === 'shipping') {
        return 'receive';
    }

    if (in_array($status, ['confirmed', 'preparing'], true)) {
        return 'ship';
    }

    if ($method !== 'cod' && $payment !== 'paid') {
        return 'pay';
    }

    return 'ship';
}

function moStatusLabel(array $order): string
{
    return match ((string) $order['order_status']) {
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Processing',
        'shipping' => 'Shipping',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        default => 'Pending'
    };
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

    <style>
        /* ORDER TOTAL BELOW PRODUCTS */

        .mo-order-total-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            padding: 6px 22px 20px;
            background: #ffffff;
        }

        .mo-order-total-row span {
            font-size: 12px;
            color: #24221f;
        }

        .mo-order-total-row .mo-order-total {
            color: #35654d;
            font-family: 'Poppins', sans-serif;
            font-size: 17px;
            font-weight: 700;
        }

        /* ORDER ACTIONS */

        .mo-order-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 9px;
        }

        .mo-cancel-button,
        .mo-cancellation-details-button,
        .mo-review-button,
        .mo-details-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 37px;
            padding: 0 16px;
            border-radius: 7px;
            font-family: inherit;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.2;
            text-decoration: none;
            cursor: pointer;
            transition:
                background-color 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease;
        }

        /* CANCEL ORDER DEFAULT */

        .mo-cancel-button {
            border: 1px solid transparent;
            background: transparent;
            color: #c3382a;
        }

        /* CANCEL ORDER HOVER */

        .mo-cancel-button:hover {
            border-color: #c3382a;
            background: #c3382a;
            color: #ffffff;
            text-decoration: none;
        }

        /* VIEW DETAILS DEFAULT */

        .mo-details-button {
            border: 1px solid #35654d;
            background: #ffffff;
            color: #35654d;
        }

        /* VIEW DETAILS HOVER */

        .mo-details-button:hover {
            border-color: #35654d;
            background: #35654d;
            color: #ffffff;
            text-decoration: none;
        }

        /* REVIEW PRODUCTS */

        .mo-review-button {
            border: 1px solid transparent;
            background: transparent;
            color: #171713;
        }

        .mo-review-button:hover {
            background: #f0eee9;
            color: #35654d;
            text-decoration: none;
        }

        /* ACCESSIBILITY */

        .mo-cancel-button:focus-visible,
        .mo-cancellation-details-button:focus-visible,
        .mo-review-button:focus-visible,
        .mo-details-button:focus-visible {
            outline: 2px solid #35654d;
            outline-offset: 3px;
        }

        @media (max-width: 650px) {
            .mo-order-total-row {
                padding: 6px 15px 18px;
            }

            .mo-order-actions {
                gap: 8px;
            }

            .mo-cancel-button,
            .mo-cancellation-details-button,
            .mo-review-button,
            .mo-details-button {
                min-height: 35px;
                padding: 0 12px;
            }
        }
    </style>
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="mo-page">

    <div class="mo-container">

        <!-- BACK TO ACCOUNT -->

        <a href="<?= BASE_URL ?>/account/profile.php"
           class="mo-back">
            <i class="fa-solid fa-chevron-left"></i>
            Back to Account
        </a>

        <!-- PAGE HEADING -->

        <div class="mo-heading">

            <div>
                <span class="mo-eyebrow">YOUR PURCHASES</span>

                <h1>My Orders</h1>

                <p>
                    Track deliveries, manage cancellations,
                    and review your products.
                </p>
            </div>

            <span class="mo-total">
                <?= count($orders) ?>
                total <?= count($orders) === 1 ? 'order' : 'orders' ?>
            </span>

        </div>

        <!-- ORDER TABS -->

        <div class="mo-tabs"
             role="tablist"
             aria-label="Order status">

            <button type="button"
                    class="mo-tab active"
                    data-tab="all"
                    aria-selected="true">
                All
            </button>

            <button type="button"
                    class="mo-tab"
                    data-tab="pay"
                    aria-selected="false">
                To Pay
            </button>

            <button type="button"
                    class="mo-tab"
                    data-tab="ship"
                    aria-selected="false">
                To Ship
            </button>

            <button type="button"
                    class="mo-tab"
                    data-tab="receive"
                    aria-selected="false">
                To Receive
            </button>

            <button type="button"
                    class="mo-tab"
                    data-tab="completed"
                    aria-selected="false">
                Completed
            </button>

            <button type="button"
                    class="mo-tab"
                    data-tab="cancelled"
                    aria-selected="false">
                Cancelled
            </button>

        </div>

        <!-- SEARCH -->

        <div class="mo-search">

            <div class="mo-search-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     xmlns="http://www.w3.org/2000/svg" focusable="false">
                    <path d="M14.9536 14.9458L21 21M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z"
                          stroke="currentColor" stroke-width="2"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <input
                type="search"
                id="moSearch"
                placeholder="Search by Order ID or product name"
                autocomplete="off"
                aria-label="Search your orders"
            >

        </div>

        <!-- ORDER LIST -->

        <div class="mo-order-list" id="moOrderList">

            <?php foreach ($orders as $order): ?>

                <?php
                    $orderId = (int) $order['id'];
                    $items = $itemsByOrder[$orderId] ?? [];

                    $tab = moTab($order);
                    $statusLabel = moStatusLabel($order);

                    $paymentMethod = strtolower(
                        (string) ($order['payment_method'] ?? 'cod')
                    );

                    $paymentLabels = [
                        'cod' => 'Cash on Delivery',
                        'gcash' => 'GCash',
                        'card' => 'Credit or Debit Card',
                        'online' => 'Online Payment'
                    ];

                    $paymentLabel = $paymentLabels[$paymentMethod]
                        ?? ucfirst($paymentMethod);

                    $location = implode(', ', array_filter([
                        $order['city'] ?? '',
                        $order['province'] ?? ''
                    ]));

                    $productNames = implode(
                        ' ',
                        array_column($items, 'product_name')
                    );

                    $canCancel =
                        $order['order_status'] === 'pending'
                        && $paymentMethod === 'cod'
                        && $order['payment_status'] === 'pending';
                ?>

                <article
                    class="mo-order-card"
                    data-tab="<?= moEsc($tab) ?>"
                    data-search="<?= moEsc(
                        mb_strtolower(
                            $order['order_number'] . ' ' . $productNames
                        )
                    ) ?>"
                >

                    <!-- ORDER HEADER -->

                    <div class="mo-order-header">

                        <div class="mo-order-identity">

                            <strong class="mo-order-number">
                                <?= moEsc($order['order_number']) ?>
                            </strong>

                            <span class="mo-order-date">
                                Placed on
                                <?= date(
                                    'Y-m-d',
                                    strtotime($order['created_at'])
                                ) ?>
                            </span>

                        </div>

                        <div class="mo-order-status-area">

                            <span class="mo-status mo-status-<?= moEsc(
                                $order['order_status']
                            ) ?>">
                                <?= moEsc($statusLabel) ?>
                            </span>

                        </div>

                    </div>

                    <!-- PURCHASED PRODUCTS -->

                    <div class="mo-order-items">

                        <?php foreach ($items as $item): ?>

                            <?php
                                $image = $item['gallery_image']
                                    ?: $item['image'];

                                $productId = (int) (
                                    $item['product_id'] ?? 0
                                );
                            ?>

                            <div class="mo-product">

                                <div class="mo-product-image">

                                    <img
                                        src="<?= moEsc(moImage($image)) ?>"
                                        alt="<?= moEsc($item['product_name']) ?>"
                                        loading="lazy"
                                        onerror="this.onerror=null;this.src='<?= moEsc(BASE_URL) ?>/assets/images/products/default-product.jpg';"
                                    >

                                </div>

                                <div class="mo-product-info">

                                    <?php if ($productId > 0): ?>

                                        <a
                                            href="<?= BASE_URL ?>/product.php?id=<?= $productId ?>"
                                            class="mo-product-name"
                                        >
                                            <?= moEsc($item['product_name']) ?>
                                        </a>

                                    <?php else: ?>

                                        <strong class="mo-product-name">
                                            <?= moEsc($item['product_name']) ?>
                                        </strong>

                                    <?php endif; ?>

                                    <span class="mo-product-meta">
                                        Qty: <?= (int) $item['quantity'] ?>
                                        · <?= moMoney($item['unit_price']) ?> each
                                    </span>

                                </div>

                                <strong class="mo-product-subtotal">
                                    <?= moMoney($item['subtotal']) ?>
                                </strong>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <!-- ORDER TOTAL BELOW PRODUCTS -->

                    <div class="mo-order-total-row">

                        <span>Total:</span>

                        <strong class="mo-order-total">
                            <?= moMoney($order['total_amount']) ?>
                        </strong>

                    </div>

                    <!-- ORDER FOOTER -->

                    <div class="mo-order-footer">

                        <div class="mo-order-footer-info">

                            <?php if ($location !== ''): ?>
                                <?= moEsc($location) ?>
                                <span class="mo-footer-separator">·</span>
                            <?php endif; ?>

                            <?= moEsc($paymentLabel) ?>

                            <?php if ($paymentMethod !== 'cod'): ?>
                                <span class="mo-footer-separator">·</span>
                                <?= moEsc(ucfirst($order['payment_status'])) ?>
                            <?php endif; ?>

                        </div>

                        <div class="mo-order-actions">

                            <?php if ($canCancel): ?>

                                <button
                                    type="button"
                                    class="mo-cancel-button"
                                    data-cancel-id="<?= $orderId ?>"
                                    data-order-number="<?= moEsc(
                                        $order['order_number']
                                    ) ?>"
                                >
                                    Cancel Order
                                </button>

                            <?php elseif ($order['order_status'] === 'cancelled'): ?>

                                <a
                                    href="<?= BASE_URL ?>/order-details.php?id=<?= $orderId ?>#cancellation-details"
                                    class="mo-cancellation-details-button"
                                >
                                    Cancellation Details
                                </a>

                            <?php elseif (
                                in_array(
                                    $order['order_status'],
                                    ['delivered', 'completed'],
                                    true
                                )
                            ): ?>

                                <?php
                                    $reviewProductId = 0;

                                    foreach ($items as $item) {
                                        if ((int) $item['product_id'] > 0) {
                                            $reviewProductId = (int) $item['product_id'];
                                            break;
                                        }
                                    }
                                ?>

                                <?php if ($reviewProductId > 0): ?>

                                    <a
                                        href="<?= BASE_URL ?>/product.php?id=<?= $reviewProductId ?>#customerReviews"
                                        class="mo-review-button"
                                    >
                                        Review Products
                                    </a>

                                <?php endif; ?>

                            <?php endif; ?>

                            <!-- VIEW DETAILS PAGE -->

                            <a
                                href="<?= BASE_URL ?>/order-details.php?id=<?= $orderId ?>"
                                class="mo-details-button"
                            >
                                View Details
                            </a>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

        <!-- EMPTY STATE -->

        <div class="mo-empty" id="moEmpty" hidden>

            <i class="fa-regular fa-clipboard"></i>

            <h2>No matching orders</h2>

            <p>Try another status or search term.</p>

        </div>

    </div>

</main>

<!-- CANCEL CONFIRMATION MODAL -->

<div class="mo-modal-overlay" id="moCancelModal" hidden>

    <div class="mo-modal mo-cancel-modal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="moCancelTitle">

        <div class="mo-modal-header">

            <h2 id="moCancelTitle">Cancel Order</h2>

            <button type="button"
                    class="mo-modal-close"
                    data-close-modal
                    aria-label="Close cancellation dialog">
                &times;
            </button>

        </div>

        <div class="mo-modal-body">

            <p>
                Are you sure you want to cancel
                <strong id="moCancelOrderNumber"></strong>?
            </p>

            <p class="mo-cancel-note">
                This action cannot be undone.
            </p>

            <p id="moCancelError"
               class="mo-error"
               role="alert"
               hidden></p>

        </div>

        <div class="mo-modal-footer">

            <button type="button"
                    class="mo-modal-done"
                    data-close-modal>
                Keep Order
            </button>

            <button type="button"
                    class="mo-confirm-cancel"
                    id="moConfirmCancel">
                Cancel Order
            </button>

        </div>

    </div>

</div>

<script>
    window.PUREVIA_ORDERS = {
        baseUrl: <?= json_encode(BASE_URL) ?>,
        csrfToken: <?= json_encode($csrfToken) ?>
    };
</script>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script src="<?= BASE_URL ?>/assets/js/my-orders.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
