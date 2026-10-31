<?php
declare(strict_types=1);
require_once __DIR__.'/SkinTypeFinder.php';
require_once __DIR__.'/SkinQuestionBank.php';
final class SkinProfile
{
    public static function choices(mysqli $conn): array
    {
        return ['types'=>$conn->query("SELECT id,assessment_key AS value,skin_type_name AS label,description FROM skin_types WHERE assessment_key IS NOT NULL AND status='active' ORDER BY FIELD(assessment_key,'normal','dry','oily','combination')")->fetch_all(MYSQLI_ASSOC),
            'concerns'=>$conn->query("SELECT id,concern_name AS label FROM skin_concerns WHERE status='active' ORDER BY concern_name")->fetch_all(MYSQLI_ASSOC),
            'ingredients'=>$conn->query("SELECT id,ingredient_name AS label FROM ingredients WHERE status='active' ORDER BY ingredient_name")->fetch_all(MYSQLI_ASSOC)];
    }
    public static function validate(mysqli $conn,array $data,?array $bank=null): array
    {
        $method=$data['assessment_method']??null;
        if(!is_string($method)||!in_array($method,['manual','guided'],true))throw new InvalidArgumentException('Choose manual selection or the guided finder.');
        $raw=$data['answers']??[];if(!is_array($raw)||count($raw)>200)throw new InvalidArgumentException('Invalid assessment answers.');
        $bank??=SkinQuestionBank::rows($conn);$definitions=SkinQuestionBank::definitions($bank);
        foreach($raw as $key=>$value)if(!isset($definitions[$key]))throw new InvalidArgumentException('Unknown assessment question. Refresh and try again.');
        $answers=[];$observed=[];
        foreach($bank as $rawQuestion){
            $question=QuestionScoring::normalize($rawQuestion);$key=$question['question_key'];$kind=$question['question_kind'];
            if($method==='manual'&&in_array($kind,['skin_type','clarification'],true))continue;
            $value=$raw[$key]??'';$options=json_decode($question['options_json'],true,16,JSON_THROW_ON_ERROR);
            $allowed=array_map(static fn($index)=>chr(65+$index),array_keys($options));
            if(!is_string($value)||($value!==''&&!in_array($value,$allowed,true)))throw new InvalidArgumentException('Choose a valid answer for each question.');
            if($kind==='sensitivity'&&$value==='')throw new InvalidArgumentException('Answer all available sensitivity questions.');
            $answers[$key]=$value;
            if($value===$question['concern_answer'])foreach($question['concern_ids'] as $concernId)$observed[]=['concern_id'=>(int)$concernId,'question'=>$question['prompt'],'source'=>$question['source_key'],'source_details'=>SkinQuestionBank::source($question)];
        }
        $assessment=$method==='guided'?SkinTypeFinder::assess($answers,$bank):null;
        $type=$method==='manual'?($data['skin_type']??null):$assessment['skin_type'];
        if($method==='manual'&&(!is_string($type)||!in_array($type,SkinTypeFinder::TYPES,true)))throw new InvalidArgumentException('Select Normal, Dry, Oily, or Combination.');
        $choices=self::choices($conn);$typeId=null;$typeLabel=null;
        foreach($choices['types'] as $row)if($row['value']===$type){$typeId=(int)$row['id'];$typeLabel=$row['label'];}
        if($type!==null&&$typeId===null)throw new InvalidArgumentException('This skin type is unavailable. Please refresh and update your selection.');
        $ids=static function($raw,array $choices,string $label):array{
            if(!is_array($raw)||count($raw)>200)throw new InvalidArgumentException('Invalid '.$label.' selection.');
            $allowed=array_map('intval',array_column($choices,'id'));$ids=[];
            foreach($raw as $id){if((!is_int($id)&&!is_string($id))||!ctype_digit((string)$id)||!in_array((int)$id,$allowed,true))throw new InvalidArgumentException('Select available '.$label.'.');$ids[]=(int)$id;}
            return array_values(array_unique($ids));
        };
        $concerns=$ids($data['concerns']??[],$choices['concerns'],'concerns');
        $main=$data['main_concern_id']??null;
        if($main===''||$main===null)$main=null;
        elseif((!is_int($main)&&!is_string($main))||!ctype_digit((string)$main)||!in_array((int)$main,$concerns,true))throw new InvalidArgumentException('Your main concern must be one of your selected concerns.');
        else $main=(int)$main;
        if($concerns&&$main===null)throw new InvalidArgumentException('Choose the concern you would most like to improve first.');
        $preference=$data['ingredient_preference']??null;
        if(!is_string($preference)||!in_array($preference,['selected','none','unsure'],true))throw new InvalidArgumentException('Choose your ingredient preference.');
        $avoided=$preference==='selected'?$ids($data['avoided_ingredients']??[],$choices['ingredients'],'ingredients'):[];
        if($preference==='selected'&&!$avoided)throw new InvalidArgumentException('Select an ingredient to avoid, or choose No specific ingredients.');
        $reason=$data['avoidance_reason']??'';
        if(!is_string($reason)||!in_array($reason,['','irritation','medical_advice','preference','other'],true))throw new InvalidArgumentException('Invalid avoidance reason.');
        $routine=$data['routine_preference']??null;
        if(!is_string($routine)||!in_array($routine,['basic','targeted','unsure'],true))throw new InvalidArgumentException('Choose a routine preference.');
        return ['assessment_method'=>$method,'skin_type'=>$type,'skin_type_id'=>$typeId,'skin_type_label'=>$typeLabel,'assessment'=>$assessment,'answers'=>$answers,
            'sensitivity'=>SkinTypeFinder::sensitivity($answers,$bank),'concerns'=>$concerns,'main_concern_id'=>$main,'ingredient_preference'=>$preference,
            'avoided_ingredients'=>$avoided,'avoidance_reason'=>$avoided?$reason:'','routine_preference'=>$routine,
            'questionnaire_version'=>SkinQuestionBank::fingerprint($bank),'rule_version'=>'configured-question-bank-v2','questionnaire_snapshot'=>$definitions,'question_evidence'=>$bank,'observed_concerns'=>$observed];
    }
    public static function load(mysqli $conn,int $userId): ?array
    {
        $s=$conn->prepare('SELECT p.*,t.assessment_key,t.skin_type_name FROM skin_profiles p LEFT JOIN skin_types t ON t.id=p.skin_type_id WHERE p.user_id=?');$s->bind_param('i',$userId);$s->execute();$r=$s->get_result()->fetch_assoc();if(!$r)return null;
        $s=$conn->prepare('SELECT concern_id FROM skin_profile_concerns WHERE skin_profile_id=?');$s->bind_param('i',$r['id']);$s->execute();$concerns=array_map('intval',array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'concern_id'));
        $s=$conn->prepare('SELECT ingredient_id FROM user_avoided_ingredients WHERE user_id=?');$s->bind_param('i',$userId);$s->execute();$avoided=array_map('intval',array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'ingredient_id'));
        $stored=json_decode($r['assessment_answers']??'{}',true);$stored=is_array($stored)?$stored:[];
        $answers=$stored['answers']??$stored;$method=$r['assessment_method']??($r['skin_type_source']==='finder'?'guided':'manual');
        return ['assessment_method'=>$method,'skin_type'=>$r['assessment_key'],'skin_type_id'=>$r['assessment_key']?(int)$r['skin_type_id']:null,'skin_type_label'=>$r['assessment_key']?$r['skin_type_name']:null,
            'assessment'=>$stored['assessment']??null,'answers'=>$answers,'sensitivity'=>['level'=>$r['sensitivity_level']??'unknown','score'=>$r['sensitivity_score']===null?null:(float)$r['sensitivity_score'],'frequent_reaction'=>$stored['sensitivity']['frequent_reaction']??SkinTypeFinder::sensitivity($answers)['frequent_reaction']],
            'concerns'=>$concerns,'main_concern_id'=>$r['main_concern_id']?(int)$r['main_concern_id']:null,'ingredient_preference'=>$avoided?'selected':$r['ingredient_preference'],
            'avoided_ingredients'=>$avoided,'avoidance_reason'=>$r['avoidance_reason']??'','routine_preference'=>$r['routine_preference'],
            'questionnaire_version'=>$stored['questionnaire_version']??'legacy','rule_version'=>$stored['rule_version']??'legacy','questionnaire_snapshot'=>$stored['questionnaire_snapshot']??[],
            'observed_concerns'=>$stored['observed_concerns']??[]];
    }
    public static function save(mysqli $conn,int $userId,array $p,string $token,string $origin): int
    {
        if($p['assessment']&&($p['assessment']['needs_followup']||$p['assessment']['reason']==='insufficient'))throw new InvalidArgumentException('Complete the guided observations and any clarification questions, or select your skin type manually.');
        $snapshot=json_encode($p,JSON_THROW_ON_ERROR);$conn->begin_transaction();
        try{
            $s=$conn->prepare("SELECT id FROM users WHERE id=? AND role_id=3 AND status='active' FOR UPDATE");$s->bind_param('i',$userId);$s->execute();if(!$s->get_result()->fetch_assoc())throw new InvalidArgumentException('An active customer account is required.');
            $s=$conn->prepare('SELECT id,user_id,profile_snapshot FROM skin_assessments WHERE submission_key=?');$s->bind_param('s',$token);$s->execute();$old=$s->get_result()->fetch_assoc();
            if($old){if((int)$old['user_id']!==$userId||$old['profile_snapshot']!==$snapshot)throw new InvalidArgumentException('This assessment has already been saved. Start a new assessment to make changes.');$conn->commit();return (int)$old['id'];}
            $type=$p['skin_type_id'];$level=$p['sensitivity']['level']==='unknown'?null:$p['sensitivity']['level'];$score=$p['sensitivity']['score'];$method=$p['assessment_method'];$source=$method==='guided'?'finder':'manual';
            $main=$p['main_concern_id'];$pref=$p['ingredient_preference'];$reason=$p['avoidance_reason']?:null;$routine=$p['routine_preference'];$answers=json_encode(array_intersect_key($p,array_flip(['answers','assessment','questionnaire_version','rule_version','questionnaire_snapshot','question_evidence','observed_concerns'])),JSON_THROW_ON_ERROR);
            $s=$conn->prepare('INSERT INTO skin_profiles(user_id,skin_type_id,sensitivity_level,skin_type_source,assessment_method,sensitivity_score,main_concern_id,ingredient_preference,avoidance_reason,routine_preference,assessment_answers) VALUES(?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE skin_type_id=VALUES(skin_type_id),sensitivity_level=VALUES(sensitivity_level),skin_type_source=VALUES(skin_type_source),assessment_method=VALUES(assessment_method),sensitivity_score=VALUES(sensitivity_score),main_concern_id=VALUES(main_concern_id),ingredient_preference=VALUES(ingredient_preference),avoidance_reason=VALUES(avoidance_reason),routine_preference=VALUES(routine_preference),assessment_answers=VALUES(assessment_answers),updated_at=CURRENT_TIMESTAMP');
            $s->bind_param('iisssdissss',$userId,$type,$level,$source,$method,$score,$main,$pref,$reason,$routine,$answers);$s->execute();
            $s=$conn->prepare('SELECT id FROM skin_profiles WHERE user_id=?');$s->bind_param('i',$userId);$s->execute();$profileId=(int)$s->get_result()->fetch_assoc()['id'];
            $s=$conn->prepare('DELETE FROM skin_profile_concerns WHERE skin_profile_id=?');$s->bind_param('i',$profileId);$s->execute();
            $s=$conn->prepare('INSERT INTO skin_profile_concerns(skin_profile_id,concern_id) VALUES(?,?)');foreach($p['concerns'] as $id){$s->bind_param('ii',$profileId,$id);$s->execute();}
            $s=$conn->prepare('DELETE FROM user_avoided_ingredients WHERE user_id=?');$s->bind_param('i',$userId);$s->execute();
            $s=$conn->prepare('INSERT INTO user_avoided_ingredients(user_id,ingredient_id) VALUES(?,?)');foreach($p['avoided_ingredients'] as $id){$s->bind_param('ii',$userId,$id);$s->execute();}
            $label=$p['skin_type_label'];$clarity=$method==='manual'||$type?'clear':($p['assessment']['reason']==='insufficient'?'insufficient':'ambiguous');
            $s=$conn->prepare('INSERT INTO skin_assessments(user_id,submission_key,assessment_method,origin,skin_type_id,skin_type_label,clarity,profile_snapshot) VALUES(?,?,?,?,?,?,?,?)');
            $s->bind_param('isssisss',$userId,$token,$method,$origin,$type,$label,$clarity,$snapshot);$s->execute();$id=(int)$conn->insert_id;$conn->commit();return $id;
        }catch(Throwable $e){$conn->rollback();throw $e;}
    }
}
