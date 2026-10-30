<?php
declare(strict_types=1);

final class AdminOrder
{
    public const STATUSES = ['pending'=>'Pending','confirmed'=>'Confirmed','preparing'=>'Processing','shipping'=>'Shipped','delivered'=>'Delivered','completed'=>'Completed','cancelled'=>'Cancelled'];
    public const PAYMENTS = ['pending'=>'Pending','paid'=>'Verified','failed'=>'Failed','refunded'=>'Refunded'];

    public static function details(mysqli $conn, int $id): ?array
    {
        $s=$conn->prepare('SELECT o.*,u.first_name AS customer_first_name,u.last_name AS customer_last_name FROM orders o LEFT JOIN users u ON u.id=o.user_id WHERE o.id=?');
        $s->bind_param('i',$id);$s->execute();$order=$s->get_result()->fetch_assoc();
        if(!$order)return null;
        $s=$conn->prepare('SELECT id,payment_method,payment_status,verified_by,verified_at,updated_at FROM payments WHERE order_id=? ORDER BY id DESC LIMIT 1');
        $s->bind_param('i',$id);$s->execute();$payment=$s->get_result()->fetch_assoc();
        $s=$conn->prepare('SELECT i.id,i.product_name,i.quantity,i.unit_price,i.subtotal,p.product_code,c.category_name,COALESCE((SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1),p.image) AS image FROM order_items i LEFT JOIN products p ON p.id=i.product_id LEFT JOIN categories c ON c.id=p.category_id WHERE i.order_id=? ORDER BY i.id');
        $s->bind_param('i',$id);$s->execute();$items=$s->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach($items as &$item){$item['quantity']=(int)$item['quantity'];$item['unit_price']=(float)$item['unit_price'];$item['subtotal']=(float)$item['subtotal'];$item['image']=self::image($item['image']);}unset($item);
        return [
            'id'=>(int)$order['id'],'number'=>$order['order_number'],
            'customer'=>trim(($order['customer_first_name']??$order['first_name']).' '.($order['customer_last_name']??$order['last_name'])),
            'customer_id'=>(int)$order['user_id'],'date'=>date('Y-m-d',strtotime($order['created_at'])),
            'recipient'=>trim($order['first_name'].' '.$order['last_name']),'phone'=>$order['phone'],
            'address'=>array_values(array_filter([$order['address_line1'],$order['address_line2'],implode(', ',array_filter([$order['barangay'],$order['city'],$order['province']])).' '.$order['postal_code'],$order['country']],static fn($v)=>$v!==null&&trim($v)!=='')),
            'subtotal'=>(float)$order['subtotal'],'shipping'=>(float)$order['shipping_fee'],'total'=>(float)$order['total_amount'],
            'status'=>$order['order_status'],'payment_status'=>$order['payment_status'],
            'payment_method'=>$payment?match($payment['payment_method']){'cod'=>'Cash on Delivery','online'=>'Online payment',default=>ucfirst($payment['payment_method'])}:'Not recorded',
            'has_payment'=>$payment!==null,'items'=>$items,'total_items'=>array_sum(array_column($items,'quantity')),
            'revision'=>hash('sha256',json_encode([$order,$payment],JSON_THROW_ON_ERROR)),
        ];
    }

    private static function image(?string $path): string
    {
        $fallback=BASE_URL.'/assets/images/products/default-product.svg';$path=trim($path??'');
        if(filter_var($path,FILTER_VALIDATE_URL))return in_array(strtolower((string)parse_url($path,PHP_URL_SCHEME)),['http','https'],true)?$path:$fallback;
        $name=basename(str_replace('\\','/',$path));
        return $name!==''&&$name!=='.'&&$name!=='..'&&is_file(__DIR__ . '/../uploads/products/'.$name)?BASE_URL.'/uploads/products/'.rawurlencode($name):$fallback;
    }

    public static function save(mysqli $conn,int $adminId,int $id,array $data): array
    {
        $status=$data['status']??null;$paymentStatus=$data['payment_status']??null;$revision=$data['revision']??null;
        if(!is_string($status)||!isset(self::STATUSES[$status])||!is_string($paymentStatus)||!isset(self::PAYMENTS[$paymentStatus]))throw new InvalidArgumentException('Choose a valid order and payment status.');
        if(!is_string($revision)||!preg_match('/^[a-f0-9]{64}$/D',$revision))throw new InvalidArgumentException('Reopen this order to load its latest details.');
        $conn->begin_transaction();
        try{
            $s=$conn->prepare('SELECT id FROM orders WHERE id=? FOR UPDATE');$s->bind_param('i',$id);$s->execute();
            if(!$s->get_result()->fetch_assoc())throw new OutOfBoundsException('Order not found.');
            $s=$conn->prepare('SELECT id FROM payments WHERE order_id=? ORDER BY id DESC LIMIT 1 FOR UPDATE');$s->bind_param('i',$id);$s->execute();$payment=$s->get_result()->fetch_assoc();
            $old=self::details($conn,$id);
            if(!hash_equals($old['revision'],$revision))throw new DomainException('This order changed while it was open. Close and reopen it before saving.');
            if(!$payment&&$paymentStatus!==$old['payment_status'])throw new DomainException('This order has no payment record to update.');
            $ps=$conn->prepare('SELECT state FROM paypal_checkouts WHERE order_id=?');$ps->bind_param('i',$id);$ps->execute();$paypal=$ps->get_result()->fetch_assoc();
            if($paypal && ($paymentStatus!==$old['payment_status'] || ($paypal['state']!=='paid' && $status!==$old['status']))) {
                throw new DomainException('PayPal payment status is controlled by verified gateway responses. Resolve pending payments through the PayPal checkout page; refunds must be processed in PayPal.');
            }
            $changed=$status!==$old['status']||$paymentStatus!==$old['payment_status'];
            if($changed){
                $s=$conn->prepare('UPDATE orders SET order_status=?,payment_status=? WHERE id=?');$s->bind_param('ssi',$status,$paymentStatus,$id);$s->execute();
                if($payment&&$paymentStatus!==$old['payment_status']){
                    $paymentId=(int)$payment['id'];$verifiedBy=$paymentStatus==='paid'?$adminId:null;
                    $s=$conn->prepare("UPDATE payments SET payment_status=?,verified_by=?,verified_at=CASE WHEN ?='paid' THEN NOW() ELSE NULL END WHERE id=?");
                    $s->bind_param('sisi',$paymentStatus,$verifiedBy,$paymentStatus,$paymentId);$s->execute();
                }
                $description='Updated order '.$old['number'].': '.self::STATUSES[$old['status']].' to '.self::STATUSES[$status].'; payment '.self::PAYMENTS[$old['payment_status']].' to '.self::PAYMENTS[$paymentStatus].'.';
                $module='orders';$action='UPDATE';$record=(string)$id;$ip=filter_var($_SERVER['REMOTE_ADDR']??'',FILTER_VALIDATE_IP)?:null;
                $s=$conn->prepare('INSERT INTO audit_logs(user_id,action,module,record_id,description,ip_address) VALUES(?,?,?,?,?,?)');$s->bind_param('isssss',$adminId,$action,$module,$record,$description,$ip);$s->execute();
            }
            $updated=self::details($conn,$id);$conn->commit();
        }catch(Throwable $error){$conn->rollback();throw $error;}
        if($changed){
            try{
                require_once __DIR__.'/OrderStatusEmail.php';
                if(!OrderStatusEmail::send($conn,$id,$old['customer_id'],$old['status'],$status,$old['payment_status'],$paymentStatus)){
                    error_log('PureVia order status email was not sent for order '.$id.'.');
                }
            }catch(Throwable $mailError){error_log('PureVia order status email failed for order '.$id.': '.$mailError->getMessage());}
        }
        return $updated;
    }
}
