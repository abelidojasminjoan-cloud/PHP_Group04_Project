<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/PayPalCheckout.php';
header('Cache-Control: no-store');
$userId = (int) ($_SESSION['user_id'] ?? 0);
if (!$userId) { header('Location: ' . BASE_URL . '/public/index.php'); exit; }
$orderId = filter_var($_GET['order_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
$message = ''; $row = null; $cardCheckout = false;
try {
    if (!$orderId) throw new DomainException('Invalid payment order.');
    $row = PayPalCheckout::find($conn, $userId, $orderId);
    $snapshot = json_decode($row['items_snapshot'], true, 32, JSON_THROW_ON_ERROR);
    $cardCheckout = ($snapshot['payment_preference'] ?? 'paypal') === 'card';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf = $_POST['csrf_token'] ?? null;
        if (!is_string($csrf) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            http_response_code(403); throw new DomainException('Your session changed. Refresh this page before trying again.');
        }
        $action = $_POST['action'] ?? '';
        if ($action === 'start') { header('Location: ' . PayPalCheckout::start($conn, $userId, $orderId), true, 303); exit; }
        if (!in_array($action, ['confirm','cancel'], true)) throw new DomainException('Invalid payment action.');
        $wasPaid = $row['state'] === 'paid';
        $paid = PayPalCheckout::finish($conn, $userId, $orderId, $action === 'cancel');
        if ($paid) {
            $_SESSION['confirmed_order_id'] = $orderId;
            if (($_SESSION['paypal_pending_order_id'] ?? null) === $orderId) {
                unset($_SESSION['paypal_pending_order_id'], $_SESSION['paypal_pending_token'], $_SESSION['checkout_selected_items'], $_SESSION['checkout_token']);
            }
            if (!$wasPaid) {
                try { require_once __DIR__ . '/../services/OrderReceipt.php'; OrderReceipt::send($conn, $orderId, $userId); }
                catch (Throwable $mailError) { error_log('PureVia PayPal receipt failed for order ' . $orderId); }
            }
            header('Location: ' . BASE_URL . '/public/order-success.php', true, 303); exit;
        }
        unset($_SESSION['paypal_pending_order_id'], $_SESSION['paypal_pending_token']);
        header('Location: ' . BASE_URL . '/public/cart.php', true, 303); exit;
    }
} catch (Throwable $error) {
    error_log('PureVia PayPal checkout: ' . $error->getMessage());
    $message = $error instanceof DomainException ? $error->getMessage() : 'Unable to reach PayPal. Retry here before placing another order.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>PayPal payment | PureVia</title><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/order-success.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/paypal.css"></head><body>
<main class="pv-success-page"><div class="pv-success-content">
<h1><?= $row && $row['state']==='paid' ? 'Payment received' : ($cardCheckout ? 'Your card payment' : 'Your PayPal payment') ?></h1>
<?php if ($message): ?><p role="alert"><?= pvHtml($message) ?></p><?php endif; ?>
<?php if ($row): ?>
<p><?= pvHtml($row['order_number']) ?> · PHP <?= pvHtml($row['total_amount']) ?></p>
<?php if ($row['state']==='pending'): ?>
<p><?= isset($_GET['cancelled']) ? 'PayPal checkout was closed. You can continue payment or cancel this order.' : 'After approving on PayPal, confirm below so we can verify your payment.' ?></p>
<p>Your items are reserved while this order awaits payment.</p><?php if ($cardCheckout): ?><p>Card details are entered securely on PayPal. Guest card checkout depends on PayPal availability.</p><?php endif; ?>
<form method="post" class="pv-paypal-actions">
<input type="hidden" name="csrf_token" value="<?= pvHtml($_SESSION['csrf_token'] ?? '') ?>">
<button class="pv-success-button pv-success-primary" name="action" value="start"><?= $cardCheckout ? 'Continue to secure card checkout' : 'Sign in to PayPal' ?></button>
<button class="pv-success-button pv-success-primary" name="action" value="confirm">Confirm / check payment</button>
<button class="pv-success-button pv-success-secondary" name="action" value="cancel">Cancel unpaid order</button>
</form>
<?php else: ?><p><?= $row['state']==='paid' ? 'Your payment has been verified.' : 'This order was cancelled. No payment is recorded.' ?></p><?php endif; ?>
<?php endif; ?>
<p><a href="<?= BASE_URL ?>/public/my-orders.php">My orders</a> · <a href="<?= BASE_URL ?>/public/cart.php">Shopping bag</a></p>
</div></main></body></html>
