<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/db.php';
require __DIR__.'/../services/SkinProfile.php';
require __DIR__.'/../services/SkinMatcher.php';
$checks=0;
function check(bool $ok,string $message): void {global $checks;if(!$ok)throw new RuntimeException($message);$checks++;}
function rejects(callable $fn,string $class): void {try{$fn();}catch(Throwable $e){check($e instanceof $class,'Unexpected rejection: '.$e->getMessage());return;}throw new RuntimeException('Expected rejection.');}
function answers(string $letter): array {return array_fill_keys(array_merge(array_map(fn($n)=>'q'.$n,range(2,8)),['q9','q10','q11']),$letter);}
foreach(['A'=>'dry','B'=>'normal','C'=>'oily','D'=>'combination'] as $letter=>$type)check(SkinTypeFinder::assess(answers($letter))['skin_type']===$type,'Consistent type '.$type);
check(SkinTypeFinder::assess(answers('E'))['skin_type']===null,'Unknown is never a type.');
$mixed=answers('A');$mixed['q2']='C';check(SkinTypeFinder::assess($mixed)['needs_followup'],'Contradiction requires clarification.');
$mixed['f1']='A';$mixed['f2']='C';check(SkinTypeFinder::assess($mixed)['skin_type']===null,'Contradictory followups remain undetermined.');
check(SkinTypeFinder::sensitivity(['q9'=>'C','q10'=>'A','q11'=>'A'])['level']==='high','Frequent reaction is not averaged away.');
check(SkinTypeFinder::sensitivity(['q9'=>'D','q10'=>'D','q11'=>'D'])['level']==='unknown','Unknown sensitivity preserved.');
$check=IngredientChecker::check([2],true,[1],[1=>'Fragrance',2=>'Parfum']);check(!$check['eligible'],'Fragrance aliases excluded.');
check(!IngredientChecker::check([2],false,[1],[1=>'Fragrance',2=>'Water'])['eligible'],'Incomplete ingredients excluded with selected avoidance.');

// Temporary tables shadow application tables on this connection only. No customer data is changed.
$original=SkinQuestionBank::rows($conn,false);
foreach(['skin_questions','audit_logs','skin_profiles','skin_assessments','skin_profile_concerns','user_avoided_ingredients'] as $table){
    $ddl=$conn->query('SHOW CREATE TABLE '.$table)->fetch_assoc()['Create Table'];
    $ddl=str_replace('CREATE TABLE','CREATE TEMPORARY TABLE',$ddl);
    $ddl=preg_replace('/^\s*CONSTRAINT .*\n?/m','',$ddl);
    $ddl=preg_replace('/,\s*\)/',"\n)",$ddl);
    $conn->query($ddl);
}
$conn->query("CREATE TEMPORARY TABLE users(id INT PRIMARY KEY,role_id INT,status VARCHAR(20))");
$conn->query("INSERT INTO users VALUES(987654,3,'active'),(987655,1,'active')");
foreach($original as $row){$columns=implode(',',array_keys($row));$values=implode(',',array_map(fn($v)=>$v===null?'NULL':"'".$conn->real_escape_string((string)$v)."'",array_values($row)));$conn->query("INSERT INTO skin_questions($columns) VALUES($values)");}
$data=['assessment_method'=>'guided','answers'=>answers('A'),'concerns'=>[],'main_concern_id'=>'','ingredient_preference'=>'none','avoided_ingredients'=>[],'avoidance_reason'=>'','routine_preference'=>'basic'];
$p=SkinProfile::validate($conn,$data);check($p['skin_type']==='dry','Server computes type.');
$data['skin_type']='oily';check(SkinProfile::validate($conn,$data)['skin_type']==='dry','Client cannot override guided result.');
$invalid=$data;$invalid['answers']['q2']=['A'];rejects(fn()=>SkinProfile::validate($conn,$invalid),InvalidArgumentException::class);
$question=['id'=>0,'revision'=>0,'prompt'=>'Have you noticed a change?','options'=>['Yes','No','Not sure'],'source_key'=>'baumann','rationale'=>'Custom observation for testing.','status'=>'active','concern_id'=>''];
SkinQuestionBank::save($conn,987655,$question);$id=(int)$conn->query("SELECT id FROM skin_questions WHERE prompt='Have you noticed a change?'")->fetch_assoc()['id'];check($id>0,'Create question.');
$question['id']=$id;$question['revision']=1;$question['prompt']='Have you noticed recurring changes?';SkinQuestionBank::save($conn,987655,$question);
rejects(fn()=>SkinQuestionBank::save($conn,987655,$question),DomainException::class);
$question['revision']=2;SkinQuestionBank::save($conn,987655,$question,true);check(!$conn->query('SELECT id FROM skin_questions WHERE id='.$id)->num_rows,'Delete custom question.');
$core=$original[0];rejects(fn()=>SkinQuestionBank::save($conn,987655,['id'=>$core['id'],'revision'=>$core['revision']],true),InvalidArgumentException::class);
$p=SkinProfile::validate($conn,$data);$assessmentId=SkinProfile::save($conn,987654,$p,str_repeat('a',64),'quiz');check($assessmentId>0,'Save assessment.');
$loaded=SkinProfile::load($conn,987654);check($loaded['questionnaire_snapshot']===$p['questionnaire_snapshot'],'Saved question wording preserved.');check($loaded['assessment']===$p['assessment'],'Saved scoring preserved.');
$snapshot=json_decode($conn->query('SELECT profile_snapshot FROM skin_assessments WHERE id='.$assessmentId)->fetch_assoc()['profile_snapshot'],true);check($snapshot['questionnaire_version']===$p['questionnaire_version'],'Version recorded.');
$before=SkinQuestionBank::fingerprint(SkinQuestionBank::rows($conn));$conn->query("UPDATE skin_questions SET prompt='Changed later', revision=revision+1 WHERE question_key='q2'");check($before!==SkinQuestionBank::fingerprint(SkinQuestionBank::rows($conn)),'Question changes invalidate stale submissions.');check(SkinProfile::load($conn,987654)['questionnaire_snapshot']===$p['questionnaire_snapshot'],'Later edits do not rewrite history.');
$matches=SkinMatcher::match($conn,$p);check(isset($matches['guidance'],$matches['routine']),'Guidance and matching response.');
echo $checks." checks passed; application records unchanged.\n";
