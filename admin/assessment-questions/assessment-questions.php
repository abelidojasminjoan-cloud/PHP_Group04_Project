<?php
require __DIR__.'/../skin/_boot.php';
require __DIR__.'/../../services/SkinQuestionBank.php';
$questions=SkinQuestionBank::rows($conn,false);
$concerns=$conn->query("SELECT id,concern_name AS label FROM skin_concerns WHERE status='active' ORDER BY concern_name")->fetch_all(MYSQLI_ASSOC);
$pageTitle='Assessment Questions';
$saConfig=['questionBank'=>$questions,'sources'=>SkinEvidence::sources(),'concerns'=>$concerns];
require __DIR__.'/../skin/_header.php';
?>
<section class="sa-heading"><div><h1>Assessment Questions</h1><p><?= count($questions) ?> questions · research-informed, custom assessment</p></div><button type="button" class="sa-primary" data-sa-question="0">Add observation question</button></section>
<p class="sa-alert">Published studies validate their own instruments. Editing wording or combining questions does not produce a clinically validated quiz. Keep core answer meanings and order: dry, balanced, oily, combination, unsure; sensitivity: rare, occasional, frequent, unsure. Core questions are protected from deletion. Additional observations suggest concerns for the customer to confirm; they do not change skin-type scoring.</p>
<section class="sa-card"><div class="sa-table-scroll"><table class="sa-table"><thead><tr><th>Question</th><th>Evidence / purpose</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($questions as $q):?><tr><td class="sa-description"><strong><?= saEsc($q['prompt']) ?></strong><small><?= saEsc($q['question_key']) ?> · revision <?= (int)$q['revision'] ?> · <?= $q['is_core']?'Core scoring':'Concern observation' ?></small><ol type="A"><?php foreach(json_decode($q['options_json'],true) as $option):?><li><?= saEsc($option) ?></li><?php endforeach;?></ol></td><td class="sa-description"><?= saEsc(SkinEvidence::sources()[$q['source_key']]['title']??$q['source_key']) ?><small><?= saEsc($q['rationale']) ?></small></td><td><?= saEsc($q['status']) ?></td><td><div class="sa-row-actions"><button type="button" data-sa-question="<?= (int)$q['id'] ?>">Edit</button><?php if(!$q['is_core']):?><button type="button" data-sa-question-delete="<?= (int)$q['id'] ?>">Delete</button><?php endif;?></div></td></tr><?php endforeach;?>
</tbody></table></div></section>
<?php require __DIR__.'/../skin/_footer.php'; ?>
