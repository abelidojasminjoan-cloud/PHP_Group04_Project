<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/PayPalCheckout.php';
require_once __DIR__ . '/../services/PayPalReturn.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
function pureViaPaymentRedirect(string $url): never
{
    global $wantsJson;
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo pvJson(['success' => true, 'redirect_url' => $url]);
    } else {
        header('Location: ' . $url, true, 303);
    }
    exit;
}
$userId = (int) ($_SESSION['user_id'] ?? 0);
if (!$userId) {
    if ($wantsJson) {
        http_response_code(401); header('Content-Type: application/json; charset=utf-8');
        echo pvJson(['success' => false, 'message' => 'Please sign in again, then resume payment from My orders.']); exit;
    }
    header('Location: ' . BASE_URL . '/login.php'); exit;
}
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$orderId = filter_var($_GET['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$message = ''; $row = null; $autoAction = '';
try {
    if (!$orderId) throw new DomainException('Invalid payment order.');
    $row = PayPalCheckout::find($conn, $userId, $orderId);
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && !$wantsJson) {
        // Historical PayPal approval URLs also return to the original confirmation layout.
        $query = ['paypal_order_id' => $orderId];
        foreach (['token', 'cancelled', 'start'] as $key) {
            if (isset($_GET[$key]) && is_string($_GET[$key])) $query[$key] = $_GET[$key];
        }
        header('Location: ' . BASE_URL . '/order-success.php?' . http_build_query($query), true, 303);
        exit;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $csrf = $_POST['csrf_token'] ?? null;
        if (!is_string($csrf) || !is_string($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            http_response_code(403); throw new DomainException('Your session changed. Refresh this page before trying again.');
        }
        $action = $_POST['action'] ?? '';
        if ($action === 'start') pureViaPaymentRedirect(PayPalCheckout::start($conn, $userId, $orderId));
        if (!in_array($action, ['confirm', 'cancel'], true)) throw new DomainException('Invalid payment action.');
        $wasPaid = $row['state'] === 'paid';
        $paid = PayPalCheckout::finish($conn, $userId, $orderId, $action === 'cancel');
        if ($paid) {
            $_SESSION['confirmed_order_id'] = $orderId;
            if ((int) ($_SESSION['paypal_pending_order_id'] ?? 0) === $orderId) {
                unset($_SESSION['paypal_pending_order_id'], $_SESSION['paypal_pending_token'], $_SESSION['checkout_selected_items'], $_SESSION['checkout_token']);
            }
            if (!$wasPaid) {
                try { require_once __DIR__ . '/../services/OrderReceipt.php'; OrderReceipt::send($conn, $orderId, $userId); }
                catch (Throwable $mailError) { error_log('PureVia PayPal receipt failed for order ' . $orderId); }
            }
            pureViaPaymentRedirect(BASE_URL . '/order-success.php');
        }
        unset($_SESSION['paypal_pending_order_id'], $_SESSION['paypal_pending_token']);
        pureViaPaymentRedirect(BASE_URL . '/order-success.php?paypal_order_id=' . $orderId);
    }
    $autoAction = pureViaPayPalReturnAction($_GET, $row);
} catch (Throwable $error) {
    error_log('PureVia PayPal checkout: ' . $error->getMessage());
    $message = $error instanceof DomainException ? $error->getMessage() : 'Unable to reach PayPal. Retry Check payment here before placing another order.';
    if ($wantsJson) {
        if (http_response_code() < 400) http_response_code($error instanceof DomainException ? 409 : 502);
        header('Content-Type: application/json; charset=utf-8');
        echo pvJson(['success' => false, 'message' => $message]); exit;
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>PayPal payment | PureVia</title><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/order-success.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/paypal.css"></head><body>
<main class="pv-success-page"><div class="pv-success-content">
<h1><?= $row && $row['state'] === 'paid' ? 'Payment received' : 'Your PayPal payment' ?></h1>
<p id="pvPayPalError" role="alert" <?= $message === '' ? 'hidden' : '' ?>><?= pvHtml($message) ?></p>
<?php if ($row): ?>
<p><?= pvHtml($row['order_number']) ?> · PHP <?= pvHtml($row['total_amount']) ?></p>
<?php if ($row['state'] === 'pending'): ?>
<p id="pvPayPalStatus" role="status" aria-live="polite"><?= isset($_GET['cancelled']) ? 'PayPal checkout was closed. You can continue payment or cancel this order.' : ($autoAction === 'confirm' ? 'Verifying your PayPal payment…' : ($autoAction === 'start' ? 'Connecting to PayPal…' : 'Continue to PayPal to approve payment, or check an existing payment.')) ?></p>
<p>Your items are reserved while this order awaits payment.</p>
<form method="post" action="<?= BASE_URL ?>/paypal-payment.php?order_id=<?= (int) $orderId ?>" class="pv-paypal-actions" id="pvPayPalPaymentForm" data-auto-action="<?= pvHtml($autoAction) ?>" data-paypal-host="<?= PayPalClient::mode() === 'live' ? 'www.paypal.com' : 'www.sandbox.paypal.com' ?>">
<input type="hidden" name="csrf_token" value="<?= pvHtml($_SESSION['csrf_token']) ?>">
<button class="pv-success-button pv-success-primary" name="action" value="start">Continue to PayPal</button>
<button class="pv-success-button pv-success-primary" name="action" value="confirm">Check payment</button>
<button class="pv-success-button pv-success-secondary" name="action" value="cancel">Cancel unpaid order</button>
</form>
<?php else: ?><p><?= $row['state'] === 'paid' ? 'Your payment has been verified.' : 'This order was cancelled. No payment is recorded.' ?></p><?php endif; ?>
<?php endif; ?>
<p><a href="<?= BASE_URL ?>/my-orders.php">My orders</a> · <a href="<?= BASE_URL ?>/cart.php">Shopping bag</a></p>
</div></main><script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/paypal-payment.js?v=<?= filemtime(__DIR__ . '/../assets/js/paypal-payment.js') ?>"></script></body></html>