<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/skin_api.php';
require_once __DIR__ . '/../../services/SkinProfile.php';
require_once __DIR__ . '/../../services/SkinMatcher.php';
require_once __DIR__ . '/../../services/SkinQuestionBank.php';
try{
    $data=skinApiData();$action=$data['action']??null;
    if($action==='rate'){
        $userId=skinApiUser($conn,[3]);$id=filter_var($data['assessment_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$rating=filter_var($data['rating']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>5]]);
        if(!$id||!$rating)throw new InvalidArgumentException('Choose a usefulness rating from 1 to 5.');
        $s=$conn->prepare('SELECT id FROM skin_assessments WHERE id=? AND user_id=?');$s->bind_param('ii',$id,$userId);$s->execute();if(!$s->get_result()->num_rows)skinReply(['success'=>false,'message'=>'Assessment not found.'],404);
        $s=$conn->prepare('UPDATE skin_assessments SET usefulness=? WHERE id=? AND user_id=?');$s->bind_param('iii',$rating,$id,$userId);$s->execute();skinReply(['success'=>true]);
    }
    if(!is_string($action)||!in_array($action,['preview','save'],true))throw new InvalidArgumentException('Invalid profile action.');
    $token=$data['assessment_token']??null;$entry=is_string($token)?($_SESSION['skin_assessment_tokens'][$token]??null):null;
    if(!$entry||$entry['expires']<time())skinReply(['success'=>false,'message'=>'This assessment has expired. Refresh the page to start again.'],403);
    $userId=$action==='save'?skinApiUser($conn,[3]):0;
    $bank=SkinQuestionBank::rows($conn);
    if(!isset($entry['questionnaire_version'])||!hash_equals($entry['questionnaire_version'],SkinQuestionBank::fingerprint($bank)))skinReply(['success'=>false,'message'=>'The questions changed while you were answering. Refresh to start with the current version.'],409);
    $p=SkinProfile::validate($conn,$data,$bank);$matches=SkinMatcher::match($conn,$p);$id=null;
    if($action==='save')$id=SkinProfile::save($conn,$userId,$p,$token,$entry['origin']);
    skinReply(['success'=>true,'profile'=>$p,'matches'=>$matches,'saved'=>$action==='save','assessment_id'=>$id]);
}catch(JsonException|InvalidArgumentException $e){skinReply(['success'=>false,'message'=>$e instanceof JsonException?'Invalid submitted data.':$e->getMessage()],422);}
catch(Throwable $e){error_log('PureVia skin profile: '.$e->getMessage());skinReply(['success'=>false,'message'=>'Unable to process your profile. Please try again.'],500);}
