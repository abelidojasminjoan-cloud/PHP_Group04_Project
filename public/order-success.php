<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$orderId = (int) ($_SESSION['confirmed_order_id'] ?? 0);
$paypalOrder = null;
$paypalAutoAction = '';
$paypalNotice = '';
$paypalReturnId = filter_var($_GET['paypal_order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($userId > 0 && $paypalReturnId) {
    require_once __DIR__ . '/../services/PayPalCheckout.php';
    require_once __DIR__ . '/../services/PayPalReturn.php';
    try {
        // The query string selects only an order owned by the signed-in customer.
        $paypalOrder = PayPalCheckout::find($conn, $userId, $paypalReturnId);
        $orderId = $paypalReturnId;
        try {
            $paypalAutoAction = pureViaPayPalReturnAction($_GET, $paypalOrder);
        } catch (DomainException $error) {
            $paypalNotice = $error->getMessage();
        }
        if (isset($_GET['cancelled']) && $paypalOrder['state'] === 'pending') {
            $paypalNotice = 'PayPal payment was not completed. Your order is saved with payment pending.';
        }
    } catch (Throwable $error) {
        error_log('PureVia PayPal return: ' . $error->getMessage());
        header('Location: ' . BASE_URL . '/public/my-orders.php');
        exit;
    }
}
$pendingPayPal = $paypalOrder !== null && $paypalOrder['state'] === 'pending' && $paypalOrder['order_status'] === 'pending';
if ($pendingPayPal && empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/paypal.css">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="pv-success-page">

    <div class="pv-success-content">

        <div class="pv-success-check" aria-hidden="true">
            &#10003;
        </div>

        <h1><?= $order['order_status'] === 'cancelled' ? 'Your order is cancelled' : 'Your order is placed' ?></h1>

        <p class="pv-success-description">
            Thank you for shopping with PureVia.
            <?= $order['payment_method'] === 'cod' ? 'Pay in cash when your order arrives.' : ($order['payment_status'] === 'paid' ? 'Your PayPal payment has been received.' : 'Your payment is awaiting verification.') ?>
        </p>

        <strong class="pv-success-order-number">
            <?= orderEsc($order['order_number']) ?>
        </strong>

        <div class="pv-success-details">

            <div class="pv-success-detail-row">
                <span>Payment method</span>

                <strong>
                    <?= $order['payment_method'] === 'cod' ? 'Cash on Delivery' : 'PayPal' ?>
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
                <span><?= $order['payment_method'] === 'cod' ? 'Total to pay on delivery' : 'Order total' ?></span>

                <strong>
                    ₱<?= number_format(
                        (float) $order['total_amount'],
                        2
                    ) ?>
                </strong>
            </div>

        </div>

        <?php if ($pendingPayPal): ?>
            <p id="pvPayPalError" role="alert" <?= $paypalNotice === '' ? 'hidden' : '' ?>><?= orderEsc($paypalNotice) ?></p>
            <p id="pvPayPalStatus" role="status" aria-live="polite"><?= $paypalAutoAction === 'confirm' ? 'Verifying your PayPal payment…' : 'You can continue or check payment for this saved order.' ?></p>
            <form method="post" action="<?= BASE_URL ?>/public/paypal-payment.php?order_id=<?= (int) $orderId ?>" class="pv-paypal-actions" id="pvPayPalPaymentForm" <?= $paypalAutoAction !== '' ? 'hidden' : '' ?> data-auto-action="<?= orderEsc($paypalAutoAction) ?>" data-paypal-host="<?= PayPalClient::mode() === 'live' ? 'www.paypal.com' : 'www.sandbox.paypal.com' ?>">
                <input type="hidden" name="csrf_token" value="<?= orderEsc($_SESSION['csrf_token']) ?>">
                <button type="submit" class="pv-success-button pv-success-primary" name="action" value="start">Continue to PayPal</button>
                <button type="submit" class="pv-success-button pv-success-secondary" name="action" value="confirm">Check payment</button>
                <button type="submit" class="pv-success-button pv-success-secondary" name="action" value="cancel">Cancel unpaid order</button>
            </form>
        <?php endif; ?>
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
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/paypal-payment.js?v=<?= filemtime(__DIR__ . '/../assets/js/paypal-payment.js') ?>"></script>
</body>
</html>
