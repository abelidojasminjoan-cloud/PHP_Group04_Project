<?php
declare(strict_types=1);
require_once __DIR__.'/../config/db.php';
function skinReply(array $data,int $status=200): never
{http_response_code($status);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);exit;}
function skinApiUser(mysqli $conn,array $roles): int
{
    $id=(int)($_SESSION['user_id']??0);if(!$id)skinReply(['success'=>false,'message'=>'Please log in first.'],401);
    $s=$conn->prepare('SELECT id,role_id,status,failed_login_attempts,locked_until FROM users WHERE id=?');$s->bind_param('i',$id);$s->execute();$u=$s->get_result()->fetch_assoc();
    if(!$u||$u['status']!=='active'||!in_array((int)$u['role_id'],$roles,true)||isPureViaLoginLocked($conn,$u,securityPolicy($conn)))skinReply(['success'=>false,'message'=>'Access denied.'],403);
    return $id;
}
function skinApiData(): array
{
    if($_SERVER['REQUEST_METHOD']!=='POST')skinReply(['success'=>false,'message'=>'Invalid request method.'],405);
    $raw=file_get_contents('php://input',false,null,0,65537);if(strlen($raw)>65536)skinReply(['success'=>false,'message'=>'The submitted data is too large.'],413);
    $data=json_decode($raw,true,32,JSON_THROW_ON_ERROR);if(!is_array($data))throw new InvalidArgumentException('Invalid submitted data.');
    $token=$data['csrf_token']??null;$expected=$_SESSION['csrf_token']??null;
    if(!is_string($token)||!is_string($expected)||$expected===''||!hash_equals($expected,$token))skinReply(['success'=>false,'message'=>'Your session has changed. Refresh and try again.'],403);
    return $data;
}
