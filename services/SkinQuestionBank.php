<?php
declare(strict_types=1);
require_once __DIR__.'/skin-questions.php';
require_once __DIR__.'/SkinEvidence.php';

final class SkinQuestionBank
{
    public static function rows(mysqli $conn, bool $activeOnly=true): array
    {
        return $conn->query('SELECT * FROM skin_questions'.($activeOnly?" WHERE status='active'":'').' ORDER BY is_core DESC,id')->fetch_all(MYSQLI_ASSOC);
    }
    public static function definitions(array $rows): array
    {
        $definitions=skinQuestions();
        foreach($rows as $r)$definitions[$r['question_key']]=[$r['prompt'],json_decode($r['options_json'],true,16,JSON_THROW_ON_ERROR)];
        return $definitions;
    }
    public static function fingerprint(array $rows): string
    {
        return hash('sha256',json_encode($rows,JSON_THROW_ON_ERROR).SkinEvidence::VERSION);
    }
    public static function save(mysqli $conn,int $admin,array $data,bool $delete=false): void
    {
        $id=filter_var($data['id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        $revision=filter_var($data['revision']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        if($id===false||$revision===false||($delete&&!$id))throw new InvalidArgumentException('Invalid question.');
        $conn->begin_transaction();
        try {
            $old=null;
            if($id){$s=$conn->prepare('SELECT * FROM skin_questions WHERE id=? FOR UPDATE');$s->bind_param('i',$id);$s->execute();$old=$s->get_result()->fetch_assoc();if(!$old)throw new DomainException('This question no longer exists. Refresh the page.');if((int)$old['revision']!==$revision)throw new DomainException('This question changed. Refresh before editing.');}
            if($delete){
                if($old['is_core'])throw new InvalidArgumentException('Core scoring questions cannot be deleted. You may edit their wording while preserving the answer meanings.');
                $s=$conn->prepare('DELETE FROM skin_questions WHERE id=?');$s->bind_param('i',$id);$s->execute();
            }else{
                $text=static function(string $key,int $max)use($data):string{$v=$data[$key]??null;if(!is_string($v)||!mb_check_encoding($v,'UTF-8')||trim($v)===''||mb_strlen($v)>$max)throw new InvalidArgumentException('Enter a valid '.str_replace('_',' ',$key).'.');return trim($v);};
                $prompt=$text('prompt',1000);$rationale=$text('rationale',2000);$source=$text('source_key',40);
                if(!isset(SkinEvidence::sources()[$source]))throw new InvalidArgumentException('Choose a supplied reference.');
                if(in_array($source,['fitzpatrick2013','faceq2016','sts'],true))throw new InvalidArgumentException('Phototype, appearance satisfaction and commercial background references cannot support these scoring or symptom questions.');
                $options=$data['options']??null;$count=$old&&$old['is_core']?count(skinQuestions()[$old['question_key']][1]):3;
                if(!is_array($options)||!array_is_list($options)||count($options)!==$count)throw new InvalidArgumentException('Keep the required number and order of answers.');
                foreach($options as &$o){if(!is_string($o)||!mb_check_encoding($o,'UTF-8')||trim($o)===''||mb_strlen($o)>300)throw new InvalidArgumentException('Enter valid answer labels.');$o=trim($o);}unset($o);
                if(count(array_unique($options))!==count($options))throw new InvalidArgumentException('Answer labels must be distinct.');
                $json=json_encode($options,JSON_THROW_ON_ERROR);$status=$data['status']??null;
                if(!in_array($status,['active','inactive'],true)||($old&&$old['is_core']&&$status!=='active'))throw new InvalidArgumentException('Core scoring questions must remain active.');
                $concern=null;
                if(!$old||!$old['is_core']){
                    $raw=$data['concern_id']??'';
                    if($raw!==''){$concern=filter_var($raw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$concern)throw new InvalidArgumentException('Invalid concern.');$s=$conn->prepare("SELECT id FROM skin_concerns WHERE id=? AND status='active'");$s->bind_param('i',$concern);$s->execute();if(!$s->get_result()->num_rows)throw new InvalidArgumentException('Choose an active concern.');}
                }
                if($id){$s=$conn->prepare('UPDATE skin_questions SET prompt=?,options_json=?,source_key=?,rationale=?,concern_id=?,status=?,revision=revision+1 WHERE id=?');$s->bind_param('ssssisi',$prompt,$json,$source,$rationale,$concern,$status,$id);}
                else{$key='obs_'.bin2hex(random_bytes(8));$s=$conn->prepare('INSERT INTO skin_questions(question_key,prompt,options_json,source_key,rationale,concern_id,status) VALUES(?,?,?,?,?,?,?)');$s->bind_param('sssssis',$key,$prompt,$json,$source,$rationale,$concern,$status);}
                $s->execute();if(!$id)$id=(int)$conn->insert_id;
            }
            $action=$delete?'DELETE':($old?'UPDATE':'CREATE');$module='Assessment Questions';$record=(string)$id;$description=$action.' question; original answers are preserved in assessment snapshots.';
            $s=$conn->prepare('INSERT INTO audit_logs(user_id,action,module,record_id,description) VALUES(?,?,?,?,?)');$s->bind_param('issss',$admin,$action,$module,$record,$description);$s->execute();$conn->commit();
        }catch(Throwable $e){$conn->rollback();throw $e;}
    }
}
