<?php
declare(strict_types=1);
require_once __DIR__.'/../../../includes/role-check.php';
$admin=requirePureViaAdmin($conn);
require_once __DIR__.'/../../../services/InventoryManagement.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
try{
    $method=$_SERVER['REQUEST_METHOD'];
    if($method==='GET'){
        $id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$id)throw new InvalidArgumentException('Invalid product variant.');
        echo pvJson(['success'=>true,'variant'=>InventoryManagement::details($conn,(int)$admin['id'],$id)]);exit;
    }
    if($method!=='POST'){header('Allow: GET, POST');http_response_code(405);throw new DomainException('Method not allowed.');}
    $csrf=$_POST['csrf_token']??null;
    if(!is_string($csrf)||!is_string($_SESSION['csrf_token']??null)||!hash_equals($_SESSION['csrf_token'],$csrf)){http_response_code(403);throw new DomainException('Refresh the page before saving.');}
    $saved=InventoryManagement::save($conn,(int)$admin['id'],$_POST);
    echo pvJson(['success'=>true,'message'=>$saved['already_applied']?'This adjustment was already saved.':'Inventory updated.']+$saved);
}catch(Throwable $error){
    if(http_response_code()<400)http_response_code($error instanceof InvalidArgumentException?422:($error instanceof OutOfBoundsException?404:($error instanceof DomainException?409:500)));
    if(!$error instanceof InvalidArgumentException&&!$error instanceof OutOfBoundsException&&!$error instanceof DomainException)error_log('PureVia inventory adjustment: '.$error->getMessage());
    echo pvJson(['success'=>false,'message'=>$error instanceof InvalidArgumentException||$error instanceof OutOfBoundsException||$error instanceof DomainException?$error->getMessage():'Unable to save inventory. Retry the same request or reopen the record to check its current stock.']);
}
