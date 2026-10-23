<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$orderId = (int) ($_SESSION['confirmed_order_id'] ?? 0);

if ($userId <= 0 || $orderId <= 0) {
    header('Location: ' . BASE_URL . '/public/cart.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT
        o.*,
        pay.payment_method
    FROM orders o
    JOIN payments pay
        ON pay.order_id = o.id
    WHERE o.id = ?
      AND o.user_id = ?
    LIMIT 1
");

$stmt->bind_param('ii', $orderId, $userId);
$stmt->execute();

$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: ' . BASE_URL . '/public/cart.php');
    exit;
}

function orderEsc(mixed $value): string
{
    return pvHtml(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Order Placed | PureVia</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/header.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/order-success.css"
    >
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="pv-success-page">

    <div class="pv-success-content">

        <div class="pv-success-check" aria-hidden="true">
            &#10003;
        </div>

        <h1>Your order is placed</h1>

        <p class="pv-success-description">
            Thank you for shopping with PureVia.
            Pay in cash when your order arrives.
        </p>

        <strong class="pv-success-order-number">
            <?= orderEsc($order['order_number']) ?>
        </strong>

        <div class="pv-success-details">

            <div class="pv-success-detail-row">
                <span>Payment method</span>

                <strong>
                    Cash on Delivery
                </strong>
            </div>

            <div class="pv-success-detail-row">
                <span>Payment status</span>

                <strong>
                    <?= orderEsc(
                        ucfirst($order['payment_status'])
                    ) ?>
                </strong>
            </div>

            <div class="pv-success-detail-row">
                <span>Order status</span>

                <strong>
                    <?= orderEsc(
                        ucfirst($order['order_status'])
                    ) ?>
                </strong>
            </div>

            <div class="pv-success-detail-row">
                <span>Total to pay on delivery</span>

                <strong>
                    ₱<?= number_format(
                        (float) $order['total_amount'],
                        2
                    ) ?>
                </strong>
            </div>

        </div>

        <!-- UPDATED BUTTONS -->

        <div class="pv-success-actions">

            <a
                href="<?= BASE_URL ?>/public/my-orders.php"
                class="pv-success-button pv-success-primary"
            >
                View My Orders
            </a>

            <a
                href="<?= BASE_URL ?>/public/shop.php"
                class="pv-success-button pv-success-secondary"
            >
                Continue Shopping
            </a>

        </div>

    </div>

</main>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
