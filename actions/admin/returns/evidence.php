<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/role-check.php';
require_once __DIR__ . '/../../../services/ReturnEvidence.php';
requirePureViaAdmin($conn);
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$photo=filter_var($_GET['photo']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>4]]);
if(!$id||$photo===false){http_response_code(404);exit;}
$stmt=$conn->prepare('SELECT evidence_json FROM order_return_requests WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();
$name=(json_decode($row['evidence_json']??'[]',true)?:[])[$photo]??null;
if(!is_string($name)||!preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/D',$name)){http_response_code(404);exit;}
$file=ReturnEvidence::directory().'/'.$name;
if(!is_file($file)){http_response_code(404);exit;}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file);
if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)){http_response_code(404);exit;}
header('Content-Type: '.$mime);header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: default-src 'none'; sandbox");header('Cache-Control: private, no-store');header('Content-Length: '.filesize($file));readfile($file);