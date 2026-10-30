<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../services/ReturnManagement.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function retReply(int $status, array $data): never {http_response_code($status);echo pvJson($data);exit;}
$actor=(int)($_SESSION['user_id']??0);
if (!$actor) retReply(401,['success'=>false,'message'=>'Please sign in.']);
try {
    ReturnManagement::actor($conn,$actor);
    if ($_SERVER['REQUEST_METHOD']==='GET') {
        $id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$id) throw new InvalidArgumentException('Choose a return request.');
        retReply(200,['success'=>true,'request'=>ReturnManagement::details($conn,$actor,$id)]);
    }
    if ($_SERVER['REQUEST_METHOD']!=='POST') retReply(405,['success'=>false,'message'=>'Method not allowed.']);
    $token=$_POST['csrf_token']??null;
    if(!is_string($token)||!is_string($_SESSION['csrf_token']??null)||$_SESSION['csrf_token']===''||!hash_equals($_SESSION['csrf_token'],$token)) retReply(403,['success'=>false,'message'=>'Refresh the page and try again.']);
    ReturnManagement::save($conn,$actor,$_POST);
    retReply(200,['success'=>true,'message'=>'Return request updated.']);
} catch(OutOfBoundsException $error){retReply(404,['success'=>false,'message'=>$error->getMessage()]);}
catch(InvalidArgumentException $error){retReply(422,['success'=>false,'message'=>$error->getMessage()]);}
catch(DomainException $error){retReply(409,['success'=>false,'message'=>$error->getMessage()]);}
catch(Throwable $error){error_log('Return management: '.$error->getMessage());retReply(500,['success'=>false,'message'=>'Unable to update this return. Please try again.']);}