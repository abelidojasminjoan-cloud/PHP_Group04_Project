<?php
declare(strict_types=1);
require_once __DIR__.'/AuditContext.php';

final class InventoryManagement
{
    public const MAX=2147483647;
    public const REASONS=['restock'=>'Restock','cancelled_order'=>'Cancelled Order','customer_return'=>'Customer Return',
        'damaged_expired'=>'Damaged / Expired','inventory_correction'=>'Inventory Correction','other'=>'Other'];

    private static function actor(mysqli $conn,int $actor,bool $lock=false): void
    {
        $s=$conn->prepare('SELECT id,role_id,status,failed_login_attempts,locked_until FROM users WHERE id=?'.($lock?' FOR UPDATE':''));$s->bind_param('i',$actor);$s->execute();$user=$s->get_result()->fetch_assoc();
        if(!$user||(int)$user['role_id']!==1||$user['status']!=='active'||isPureViaLoginLocked($conn,$user,securityPolicy($conn)))throw new DomainException('Active administrator access is required.');
    }

    public static function details(mysqli $conn,int $actor,int $id): array
    {
        self::actor($conn,$actor);
        $s=$conn->prepare('SELECT v.id,v.product_id,v.variant_label,v.size_capacity,v.stock_quantity,v.low_stock_threshold,v.updated_at,p.product_name FROM product_variants v JOIN products p ON p.id=v.product_id WHERE v.id=?');$s->bind_param('i',$id);$s->execute();$row=$s->get_result()->fetch_assoc();
        if(!$row)throw new OutOfBoundsException('Product variant not found.');
        $row['revision']=self::revision($row);
        $row['title']=$row['product_name'].' - '.trim(($row['variant_label']??'').' ('.$row['size_capacity'].')');
        $row['updated_date']=date('Y-m-d',strtotime($row['updated_at']));
        return $row;
    }

    private static function revision(array $row): string
    {
        return hash('sha256',json_encode([(int)$row['id'],(int)$row['product_id'],(int)$row['stock_quantity'],(int)$row['low_stock_threshold'],$row['updated_at']],JSON_THROW_ON_ERROR));
    }

    public static function save(mysqli $conn,int $actor,array $data): array
    {
        foreach(['variant_id'=>1,'quantity'=>0,'threshold'=>0] as $key=>$min){
            $value=$data[$key]??null;
            if(!is_scalar($value)||($value=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>self::MAX]]))===false)throw new InvalidArgumentException('Enter valid whole-number quantities and a valid product.');
            $data[$key]=$value;
        }
        foreach(['type','reason','notes','revision','request_id'] as $key)if(!is_string($data[$key]??null))throw new InvalidArgumentException('Invalid inventory adjustment.');
        $id=$data['variant_id'];$quantity=$data['quantity'];$threshold=$data['threshold'];$type=$data['type'];$reason=$data['reason'];$notes=trim($data['notes']);
        if(!in_array($type,['add','remove'],true)||!isset(self::REASONS[$reason]))throw new InvalidArgumentException('Choose a valid adjustment type and reason.');
        if(($reason==='damaged_expired'&&$type!=='remove')||(in_array($reason,['restock','cancelled_order','customer_return'],true)&&$type!=='add'))throw new InvalidArgumentException('The adjustment type does not match its reason.');
        if(!mb_check_encoding($notes,'UTF-8')||mb_strlen($notes,'UTF-8')>2000)throw new InvalidArgumentException('Notes must contain at most 2,000 characters.');
        if($reason!=='restock'&&$notes==='')throw new InvalidArgumentException('Add notes explaining this adjustment. For returns or cancellations, include the order number and relevant condition.');
        if(!preg_match('/^[a-f0-9]{64}$/D',$data['revision'])||!preg_match('/^[a-f0-9]{32}$/D',$data['request_id']))throw new InvalidArgumentException('Reopen Manage Inventory to load the latest record.');
        $key=hash('sha256',$actor.':'.$data['request_id']);
        $fingerprint=hash('sha256',json_encode([$id,$quantity,$threshold,$type,$reason,$notes,$data['revision']],JSON_THROW_ON_ERROR));
        $conn->begin_transaction();
        try{
            self::actor($conn,$actor,true);
            $s=$conn->prepare('SELECT request_fingerprint FROM inventory_adjustments WHERE request_key=?');$s->bind_param('s',$key);$s->execute();$existing=$s->get_result()->fetch_assoc();
            if($existing){
                if(!hash_equals($existing['request_fingerprint'],$fingerprint))throw new DomainException('This save request was already used with different details. Reopen the inventory record.');
                $conn->commit();return ['already_applied'=>true,'variant'=>self::details($conn,$actor,$id)];
            }
            $s=$conn->prepare('SELECT product_id FROM product_variants WHERE id=?');$s->bind_param('i',$id);$s->execute();$owner=$s->get_result()->fetch_assoc();
            if(!$owner)throw new OutOfBoundsException('Product variant not found.');
            $productId=(int)$owner['product_id'];
            $s=$conn->prepare('SELECT id FROM products WHERE id=? FOR UPDATE');$s->bind_param('i',$productId);$s->execute();
            if(!$s->get_result()->fetch_assoc())throw new OutOfBoundsException('Product not found.');
            $s=$conn->prepare('SELECT id,product_id,stock_quantity,low_stock_threshold,updated_at FROM product_variants WHERE product_id=? ORDER BY id FOR UPDATE');$s->bind_param('i',$productId);$s->execute();$variants=$s->get_result()->fetch_all(MYSQLI_ASSOC);
            $row=null;$total=0;
            foreach($variants as $variant){$total+=(int)$variant['stock_quantity'];if((int)$variant['id']===$id)$row=$variant;}
            if(!$row)throw new OutOfBoundsException('Product variant not found.');
            if(!hash_equals(self::revision($row),$data['revision']))throw new DomainException('Inventory changed while this dialog was open. Close it and reopen to review the latest stock.');
            $before=(int)$row['stock_quantity'];$beforeThreshold=(int)$row['low_stock_threshold'];$delta=$type==='add'?$quantity:-$quantity;$after=$before+$delta;
            if($after<0)throw new DomainException('You cannot remove more units than the current stock.');
            if($after>self::MAX||$total+$delta>4294967295)throw new DomainException('The new stock total is too large.');
            if($delta===0&&$beforeThreshold===$threshold)throw new DomainException('Enter a quantity or change the low-stock threshold before saving.');
            $restock=$type==='add'&&$reason==='restock'&&$quantity>0?1:0;
            $s=$conn->prepare('UPDATE product_variants SET stock_quantity=?,low_stock_threshold=?,last_restocked_at=IF(?=1,NOW(),last_restocked_at),updated_at=NOW() WHERE id=?');$s->bind_param('iiii',$after,$threshold,$restock,$id);$s->execute();
            $s=$conn->prepare('UPDATE products SET stock_quantity=(SELECT COALESCE(SUM(stock_quantity),0) FROM product_variants WHERE product_id=?) WHERE id=?');$s->bind_param('ii',$productId,$productId);$s->execute();
            $ip=filter_var($_SERVER['REMOTE_ADDR']??'',FILTER_VALIDATE_IP)?:null;$browser=pureViaAuditBrowser();
            $s=$conn->prepare('INSERT INTO inventory_adjustments(variant_id,product_id,user_id,adjustment_type,reason,quantity,stock_before,stock_after,threshold_before,threshold_after,notes,request_key,request_fingerprint,ip_address,browser) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $s->bind_param('iiissiiiiisssss',$id,$productId,$actor,$type,$reason,$quantity,$before,$after,$beforeThreshold,$threshold,$notes,$key,$fingerprint,$ip,$browser);$s->execute();
            $action='UPDATE';$module='Inventory';$record=(string)$id;
            $description='Variant #'.$id.': stock '.$before.' to '.$after.'; threshold '.$beforeThreshold.' to '.$threshold.'; '.self::REASONS[$reason].($notes!==''?'. '.$notes:'');
            $s=$conn->prepare('INSERT INTO audit_logs(user_id,action,module,record_id,description,ip_address,browser) VALUES(?,?,?,?,?,?,?)');$s->bind_param('issssss',$actor,$action,$module,$record,$description,$ip,$browser);$s->execute();
            $result=self::details($conn,$actor,$id);$conn->commit();
            return ['already_applied'=>false,'variant'=>$result];
        }catch(Throwable $error){$conn->rollback();throw $error;}
    }
}
