<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/db.php';require __DIR__.'/../services/QuestionScoring.php';
$columns=array_column($conn->query('SHOW COLUMNS FROM skin_questions')->fetch_all(MYSQLI_ASSOC),'Field');
$fields=['question_kind'=>"enum('skin_type','sensitivity','clarification','observation') DEFAULT NULL",'answer_scores_json'=>'text DEFAULT NULL','concern_ids_json'=>'text DEFAULT NULL','skin_type_ids_json'=>'text DEFAULT NULL','concern_answer'=>"varchar(1) NOT NULL DEFAULT 'A'",'source_title'=>'varchar(1000) DEFAULT NULL','source_author'=>'varchar(500) DEFAULT NULL','source_url'=>'varchar(2048) DEFAULT NULL'];
foreach($fields as $name=>$definition)if(!in_array($name,$columns,true))$conn->query('ALTER TABLE skin_questions ADD COLUMN '.$name.' '.$definition);
$conn->begin_transaction();try{
 $rows=$conn->query('SELECT * FROM skin_questions WHERE question_kind IS NULL FOR UPDATE')->fetch_all(MYSQLI_ASSOC);
 $stmt=$conn->prepare('UPDATE skin_questions SET question_kind=?,answer_scores_json=?,concern_ids_json=?,skin_type_ids_json=? WHERE id=?');
 foreach($rows as $row){$legacy=QuestionScoring::legacy($row);$kind=$legacy['question_kind'];$scores=json_encode($legacy['answer_scores'],JSON_THROW_ON_ERROR);$concerns=json_encode($row['concern_id']?[(int)$row['concern_id']]:[],JSON_THROW_ON_ERROR);$types='[]';$id=(int)$row['id'];$stmt->bind_param('ssssi',$kind,$scores,$concerns,$types,$id);$stmt->execute();}
 // Connect existing canonical catalog records after a cleared/recreated local catalog.
 // Never create or rename types, and never replace an existing assessment assignment.
 $types=$conn->query('SELECT id,skin_type_name,assessment_key,status FROM skin_types FOR UPDATE')->fetch_all(MYSQLI_ASSOC);
 foreach(QuestionScoring::TYPES as $key){
  if(in_array($key,array_column($types,'assessment_key'),true))continue;
  $matches=array_values(array_filter($types,static function($type)use($key){$name=preg_replace('/\s+skin$/','',strtolower(trim($type['skin_type_name'])));return $type['assessment_key']===null&&$type['status']==='active'&&$name===$key;}));
  if(count($matches)===1){$id=(int)$matches[0]['id'];$assign=$conn->prepare('UPDATE skin_types SET assessment_key=? WHERE id=? AND assessment_key IS NULL');$assign->bind_param('si',$key,$id);$assign->execute();$assign->close();}
 } $conn->commit();echo "Configurable question schema ready. Existing answers, questions and source references preserved.\n";
}catch(Throwable $error){$conn->rollback();throw $error;}