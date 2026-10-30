<?php
declare(strict_types=1);
require_once __DIR__.'/skin-questions.php';
require_once __DIR__.'/SkinEvidence.php';
require_once __DIR__.'/QuestionScoring.php';
require_once __DIR__.'/AuditContext.php';
final class SkinQuestionBank
{
    public static function rows(mysqli $conn,bool $activeOnly=true):array
    {
        $rows=$conn->query('SELECT * FROM skin_questions'.($activeOnly?" WHERE status='active'":'')." ORDER BY FIELD(question_kind,'skin_type','sensitivity','clarification','observation'),id")->fetch_all(MYSQLI_ASSOC);
        return array_map([QuestionScoring::class,'normalize'],$rows);
    }
    public static function definitions(array $rows):array
    {
        $definitions=[];foreach($rows as $row)$definitions[$row['question_key']]=[$row['prompt'],json_decode($row['options_json'],true,16,JSON_THROW_ON_ERROR)];return $definitions;
    }
    public static function fingerprint(array $rows):string{return hash('sha256',json_encode($rows,JSON_THROW_ON_ERROR).'configured-question-bank-v1');}
    public static function source(array $row):array
    {
        $base=SkinEvidence::sources()[$row['source_key']]??[];
        return ['title'=>trim($row['source_title']??'')?:($base['title']??'Custom source'),'author'=>trim($row['source_author']??''),'url'=>trim($row['source_url']??'')?:($base['url']??null),'scope'=>$row['rationale']??($base['scope']??'')];
    }
    public static function evidenceSources(array $rows):array
    {
        $sources=SkinEvidence::sources();foreach($rows as $row){$source=self::source($row);$key='question_'.substr(hash('sha256',json_encode($source)),0,16);$sources[$key]=$source;}return $sources;
    }
    private static function text(array $data,string $key,int $max,bool $required=false):string
    {
        $value=$data[$key]??'';if(!is_string($value)||!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>$max||($required&&trim($value)===''))throw new InvalidArgumentException('Enter a valid '.str_replace('_',' ',$key).'.');return trim($value);
    }
    private static function ids(mysqli $conn,mixed $raw,string $table,array $old):array
    {
        if(!is_array($raw)||!array_is_list($raw)||count($raw)>100)throw new InvalidArgumentException('Choose valid concern and skin-type mappings.');$ids=[];
        $stmt=$conn->prepare('SELECT status FROM '.$table.' WHERE id=?');
        foreach($raw as $value){if(!is_scalar($value)||!($id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])))throw new InvalidArgumentException('Invalid mapping.');$stmt->bind_param('i',$id);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();if(!$row||($row['status']!=='active'&&!in_array($id,$old,true)))throw new InvalidArgumentException('Choose available concern and skin-type mappings.');$ids[]=$id;}$stmt->close();return array_values(array_unique($ids));
    }
    public static function save(mysqli $conn,int $admin,array $data,bool $delete=false):void
    {
        $id=filter_var($data['id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);$revision=filter_var($data['revision']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        if($id===false||$revision===false||($delete&&!$id))throw new InvalidArgumentException('Invalid question.');
        $conn->begin_transaction();
        try{
            $stmt=$conn->prepare("SELECT id FROM users WHERE id=? AND role_id=1 AND status='active' FOR UPDATE");$stmt->bind_param('i',$admin);$stmt->execute();if(!$stmt->get_result()->fetch_assoc())throw new DomainException('Active administrator access is required.');$stmt->close();
            $old=null;if($id){$stmt=$conn->prepare('SELECT * FROM skin_questions WHERE id=? FOR UPDATE');$stmt->bind_param('i',$id);$stmt->execute();$old=$stmt->get_result()->fetch_assoc();$stmt->close();if(!$old)throw new DomainException('This question no longer exists. Refresh the page.');if((int)$old['revision']!==$revision)throw new DomainException('This question changed. Refresh before editing.');$old=QuestionScoring::normalize($old);}
            if($delete){$stmt=$conn->prepare('DELETE FROM skin_questions WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();$stmt->close();}
            else{
                $prompt=self::text($data,'prompt',1000,true);$rationale=self::text($data,'rationale',2000,true);$source=self::text($data,'source_key',40,true);
                if($source!=='custom'&&!isset(SkinEvidence::sources()[$source]))throw new InvalidArgumentException('Choose a supplied source or Other source.');
                $title=self::text($data,'source_title',1000,$source==='custom');$author=self::text($data,'source_author',500,$source==='custom');$url=self::text($data,'source_url',2048,$source==='custom');
                if($url!==''&&(!filter_var($url,FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true)))throw new InvalidArgumentException('Use a complete http:// or https:// source link.');
                $kind=$data['question_kind']??null;$status=$data['status']??null;
                if(!is_string($kind)||!in_array($kind,QuestionScoring::KINDS,true)||!is_string($status)||!in_array($status,['active','inactive'],true))throw new InvalidArgumentException('Choose a question purpose and status.');
                $options=$data['options']??null;if(!is_array($options)||!array_is_list($options)||count($options)<2||count($options)>8)throw new InvalidArgumentException('Provide between 2 and 8 answers.');
                foreach($options as &$option){if(!is_string($option)||!mb_check_encoding($option,'UTF-8')||trim($option)===''||mb_strlen($option)>300)throw new InvalidArgumentException('Enter valid answer labels.');$option=trim($option);}unset($option);if(count(array_unique($options))!==count($options))throw new InvalidArgumentException('Answer labels must be distinct.');
                $submitted=$data['answer_scores']??[];if(!is_array($submitted))throw new InvalidArgumentException('Invalid answer weights.');$scores=[];$hasScore=false;
                foreach($options as $index=>$option){$letter=chr(65+$index);$weights=$submitted[$letter]??[];if(!is_array($weights))throw new InvalidArgumentException('Invalid answer weights.');$scores[$letter]=[];
                    if($kind==='observation')continue;
                    $allowed=$kind==='sensitivity'?['sensitivity']:QuestionScoring::TYPES;
                    foreach($weights as $type=>$raw){if(!in_array($type,$allowed,true)||!is_scalar($raw)||($weight=filter_var($raw,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>$kind==='sensitivity'?2:5]]))===false)throw new InvalidArgumentException('Use whole-number skin-type weights from 0 to 5, or sensitivity points from 0 to 2.');if($kind==='sensitivity'||$weight>0)$scores[$letter][$type]=$weight;}
                    $hasScore=$hasScore||($kind==='sensitivity'?array_key_exists('sensitivity',$scores[$letter]):array_sum($scores[$letter])>0);
                }
                if($kind!=='observation'&&!$hasScore)throw new InvalidArgumentException('Map at least one answer to a score. Leave Not sure answers unscored.');
                $concerns=self::ids($conn,$data['concern_ids']??[],'skin_concerns',$old['concern_ids']??[]);$types=self::ids($conn,$data['skin_type_ids']??[],'skin_types',$old['skin_type_ids']??[]);
                $trigger=$data['concern_answer']??'A';if(!is_string($trigger)||!array_key_exists($trigger,$scores))throw new InvalidArgumentException('Choose which answer suggests the selected concerns.');
                $optionsJson=json_encode($options,JSON_THROW_ON_ERROR);$scoresJson=json_encode($scores,JSON_THROW_ON_ERROR);$concernsJson=json_encode($concerns,JSON_THROW_ON_ERROR);$typesJson=json_encode($types,JSON_THROW_ON_ERROR);$concern=$concerns[0]??null;$core=$kind==='observation'?0:1;
                if($id){$stmt=$conn->prepare('UPDATE skin_questions SET prompt=?,options_json=?,source_key=?,rationale=?,concern_id=?,status=?,question_kind=?,source_title=?,source_author=?,source_url=?,answer_scores_json=?,concern_ids_json=?,skin_type_ids_json=?,concern_answer=?,is_core=?,revision=revision+1 WHERE id=?');$stmt->bind_param('ssssisssssssssii',$prompt,$optionsJson,$source,$rationale,$concern,$status,$kind,$title,$author,$url,$scoresJson,$concernsJson,$typesJson,$trigger,$core,$id);}
                else{$key='question_'.bin2hex(random_bytes(8));$stmt=$conn->prepare('INSERT INTO skin_questions(question_key,prompt,options_json,source_key,rationale,concern_id,status,question_kind,source_title,source_author,source_url,answer_scores_json,concern_ids_json,skin_type_ids_json,concern_answer,is_core) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');$stmt->bind_param('sssssisssssssssi',$key,$prompt,$optionsJson,$source,$rationale,$concern,$status,$kind,$title,$author,$url,$scoresJson,$concernsJson,$typesJson,$trigger,$core);}
                $stmt->execute();if(!$id)$id=(int)$conn->insert_id;$stmt->close();
            }
            $action=$delete?'DELETE':($old?'UPDATE':'CREATE');$module='Assessment Questions';$record=(string)$id;$description=$action.' configurable question. Original answers and scoring are retained in assessment snapshots.';$ip=filter_var($_SERVER['REMOTE_ADDR']??'',FILTER_VALIDATE_IP)?:null;$browser=pureViaAuditBrowser();
            $stmt=$conn->prepare('INSERT INTO audit_logs(user_id,action,module,record_id,description,ip_address,browser) VALUES(?,?,?,?,?,?,?)');$stmt->bind_param('issssss',$admin,$action,$module,$record,$description,$ip,$browser);$stmt->execute();$stmt->close();$conn->commit();
        }catch(Throwable $error){$conn->rollback();throw $error;}
    }
}