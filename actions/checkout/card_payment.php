<?php
declare(strict_types=1);
ini_set('display_errors','0');
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../services/PayPalCheckout.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
try {
    if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); header('Allow: POST'); throw new DomainException('POST required.'); }
    $userId=(int)($_SESSION['user_id']??0);
    if (!$userId) { http_response_code(401); throw new DomainException('Please sign in again.'); }
    $csrf=$_POST['csrf_token']??null;
    if (!is_string($csrf)||empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],$csrf)) {
        http_response_code(403); throw new DomainException('Refresh checkout before trying again.');
    }
    $s=$conn->prepare("SELECT id FROM users WHERE id=? AND status='active' AND role_id=3");$s->bind_param('i',$userId);$s->execute();
    if (!$s->get_result()->fetch_assoc()) { http_response_code(403); throw new DomainException('An active customer account is required.'); }
    $action=$_POST['action']??'';
    if ($action==='token') {
        echo pvJson(['success'=>true,'clientToken'=>(new PayPalClient())->browserClientToken()]); exit;
    }
    $orderId=filter_var($_POST['order_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if (!$orderId) throw new DomainException('Invalid card order.');
    $row=PayPalCheckout::find($conn,$userId,$orderId);
    $snapshot=json_decode($row['items_snapshot'],true,32,JSON_THROW_ON_ERROR);
    if (($snapshot['payment_preference']??'')!=='card') throw new DomainException('This order is not a card checkout.');
    if ($action==='create') {
        echo pvJson(['success'=>true,'id'=>PayPalCheckout::cardOrder($conn,$userId,$orderId)]); exit;
    }
    if ($action!=='capture') throw new DomainException('Invalid payment action.');
    $remoteId=$_POST['paypal_order_id']??null;
    if (!is_string($remoteId)||!$row['paypal_order_id']||!hash_equals($row['paypal_order_id'],$remoteId)) throw new DomainException('Payment does not match this order.');
    $wasPaid=$row['state']==='paid';
    PayPalCheckout::finish($conn,$userId,$orderId);
    $_SESSION['confirmed_order_id']=$orderId;
    if ((int)($_SESSION['paypal_pending_order_id']??0)===$orderId) unset($_SESSION['paypal_pending_order_id'],$_SESSION['paypal_pending_token'],$_SESSION['checkout_selected_items'],$_SESSION['checkout_token']);
    if (!$wasPaid) {
        try { require_once __DIR__.'/../../services/OrderReceipt.php';OrderReceipt::send($conn,$orderId,$userId); }
        catch (Throwable $mailError) { error_log('PureVia card receipt failed for order '.$orderId); }
    }
    echo pvJson(['success'=>true,'redirect_url'=>BASE_URL.'/public/order-success.php']);
} catch (Throwable $error) {
    error_log('PureVia embedded card payment: '.$error->getMessage());
    if (http_response_code()<400) http_response_code($error instanceof DomainException?409:502);
    echo pvJson(['success'=>false,'message'=>$error instanceof DomainException?$error->getMessage():'Unable to verify payment. Retry the same checkout or check its payment status before starting another order.']);
}
