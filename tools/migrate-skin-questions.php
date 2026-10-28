<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../config/db.php';
require __DIR__.'/../services/SkinQuestionBank.php';
$conn->query(file_get_contents(__DIR__.'/../database/migrations/20261027_skin_questions.sql'));
$s=$conn->prepare('INSERT IGNORE INTO skin_questions(question_key,prompt,options_json,source_key,rationale,is_core) VALUES(?,?,?,?,?,1)');
foreach(skinQuestions() as $key=>$definition){
    [$prompt,$options]=$definition;$json=json_encode($options,JSON_THROW_ON_ERROR);
    $source=in_array($key,['q9','q10','q11'],true)?'bstq2025':'sos2014';
    $rationale='Adapted observation for PureVia; not an original validated instrument item. '.(str_starts_with($key,'f')?'Clarifies regional oil distribution.':'Preserve answer meanings and order when editing.');
    $s->bind_param('sssss',$key,$prompt,$json,$source,$rationale);$s->execute();
}
$observations=[
    ['obs_breakouts','Over the past four weeks, have you noticed recurring pimples, blackheads or whiteheads?','Acne','baumann'],
    ['obs_marks','Over the past four weeks, have you noticed persistent dark marks or patches of uneven skin tone?','Dark Spots / Uneven Tone','classification'],
    ['obs_redness','Over the past four weeks, have you noticed recurring facial redness, itching or burning?','Redness / Irritation','baumann'],
    ['obs_lines','Over the past four weeks, have you noticed fine lines that remain visible when your face is relaxed?','Fine Lines / Aging','classification'],
];
$s=$conn->prepare('INSERT IGNORE INTO skin_questions(question_key,prompt,options_json,source_key,rationale,concern_id) VALUES(?,?,?,?,?,?)');
foreach($observations as [$key,$prompt,$name,$source]){
    $lookup=$conn->prepare("SELECT id FROM skin_concerns WHERE concern_name=? AND status='active'");$lookup->bind_param('s',$name);$lookup->execute();$concern=$lookup->get_result()->fetch_assoc()['id']??null;
    $json=json_encode(['Yes','No','Not sure'],JSON_THROW_ON_ERROR);$rationale='Original PureVia observation, informed by the supplied reference. A Yes answer suggests a concern for customer confirmation, not a diagnosis; it has no skin-type scoring weight.';
    $s->bind_param('sssssi',$key,$prompt,$json,$source,$rationale,$concern);$s->execute();
}
echo "Question bank installed; existing questions preserved.\n";
