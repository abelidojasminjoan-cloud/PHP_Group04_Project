<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../services/AdminOrder.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
function adminOrderReply(array $data,int $status=200): never {http_response_code($status);echo pvJson($data);exit;}
try{
    $adminId=(int)($_SESSION['user_id']??0);
    if(!$adminId)adminOrderReply(['success'=>false,'message'=>'Please sign in again.'],401);
    $s=$conn->prepare('SELECT role_id,status,failed_login_attempts,locked_until FROM users WHERE id=?');$s->bind_param('i',$adminId);$s->execute();$admin=$s->get_result()->fetch_assoc();
    if(!$admin||(int)$admin['role_id']!==1||$admin['status']!=='active'||isPureViaLoginLocked($conn,$admin,securityPolicy($conn)))adminOrderReply(['success'=>false,'message'=>'Admin access is required.'],403);
    $method=$_SERVER['REQUEST_METHOD'];
    if(!in_array($method,['GET','POST'],true)){header('Allow: GET, POST');adminOrderReply(['success'=>false,'message'=>'Method not allowed.'],405);}
    if($method==='POST'){
        $raw=file_get_contents('php://input',false,null,0,8193);
        if(strlen($raw)>8192)adminOrderReply(['success'=>false,'message'=>'Request too large.'],413);
        $data=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
        if(!is_array($data))throw new InvalidArgumentException('Invalid request.');
        $csrf=$data['csrf_token']??null;$expected=$_SESSION['csrf_token']??null;
        if(!is_string($csrf)||!is_string($expected)||$expected===''||!hash_equals($expected,$csrf))adminOrderReply(['success'=>false,'message'=>'Your session changed. Refresh the page and try again.'],403);
    }else $data=$_GET;
    $id=filter_var($data['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if(!$id)throw new InvalidArgumentException('Invalid order.');
    if($method==='POST')$order=AdminOrder::save($conn,$adminId,$id,$data);
    else{
        $conn->begin_transaction(MYSQLI_TRANS_START_READ_ONLY);
        try{$order=AdminOrder::details($conn,$id);$conn->commit();}catch(Throwable $error){$conn->rollback();throw $error;}
    }
    if(!$order)adminOrderReply(['success'=>false,'message'=>'Order not found.'],404);
    adminOrderReply(['success'=>true,'order'=>$order]);
}catch(InvalidArgumentException|JsonException $error){adminOrderReply(['success'=>false,'message'=>$error instanceof JsonException?'Invalid request.':$error->getMessage()],422);}
catch(OutOfBoundsException $error){adminOrderReply(['success'=>false,'message'=>$error->getMessage()],404);}
catch(DomainException $error){adminOrderReply(['success'=>false,'message'=>$error->getMessage()],409);}
catch(Throwable $error){error_log('Admin order: '.$error->getMessage());adminOrderReply(['success'=>false,'message'=>'Unable to load or save this order. Please try again.'],500);}
