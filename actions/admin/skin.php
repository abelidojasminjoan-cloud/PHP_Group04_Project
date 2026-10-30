<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/skin_api.php';
require_once __DIR__ . '/../../services/SkinAdmin.php';
require_once __DIR__ . '/../../services/SkinQuestionBank.php';
try{
    $admin=skinApiUser($conn,[1]);
    if($_SERVER['REQUEST_METHOD']==='GET'){
        $id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id)throw new InvalidArgumentException('Invalid record.');
        if(($_GET['record']??'')==='suitability'){$record=SkinAdmin::suitability($conn,$id);}
        elseif(($_GET['record']??'')==='assessment'){
            $s=$conn->prepare('SELECT a.id,a.created_at,a.assessment_method,a.skin_type_label,a.clarity,a.usefulness,a.profile_snapshot FROM skin_assessments a WHERE a.id=?');$s->bind_param('i',$id);$s->execute();$record=$s->get_result()->fetch_assoc();if($record){$record['profile']=json_decode($record['profile_snapshot'],true);unset($record['profile_snapshot']);}
        }else throw new InvalidArgumentException('Invalid record type.');
        if(!$record)skinReply(['success'=>false,'message'=>'Record not found.'],404);skinReply(['success'=>true,'record'=>$record]);
    }
    $data=skinApiData();$action=$data['action']??null;
    if($action==='question.save')SkinQuestionBank::save($conn,$admin,$data);
    elseif($action==='question.delete')SkinQuestionBank::save($conn,$admin,$data,true);
    elseif($action==='type.save')SkinAdmin::type($conn,$admin,$data);
    elseif($action==='type.delete')SkinAdmin::type($conn,$admin,$data,true);
    elseif($action==='tag.save')SkinAdmin::tag($conn,$admin,$data);
    elseif($action==='tag.delete')SkinAdmin::tag($conn,$admin,$data,true);
    elseif($action==='suitability.save')SkinAdmin::saveSuitability($conn,$admin,$data);
    else throw new InvalidArgumentException('Invalid admin action.');
    skinReply(['success'=>true]);
}catch(JsonException|InvalidArgumentException $e){skinReply(['success'=>false,'message'=>$e instanceof JsonException?'Invalid submitted data.':$e->getMessage()],422);}
catch(DomainException $e){skinReply(['success'=>false,'message'=>$e->getMessage()],409);}
catch(mysqli_sql_exception $e){if((int)$e->getCode()===1062)skinReply(['success'=>false,'message'=>isset($action)&&$action==='tag.save'?'A suitability tag with this name already exists.':'A skin type with this name already exists.'],409);error_log('Skin admin: '.$e->getMessage());skinReply(['success'=>false,'message'=>'Unable to save this record. Please try again.'],500);}
catch(Throwable $e){error_log('Skin admin: '.$e->getMessage());skinReply(['success'=>false,'message'=>'Unable to process this request.'],500);}
