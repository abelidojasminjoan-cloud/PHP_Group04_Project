<?php
require __DIR__.'/../skin/_boot.php';
require __DIR__ . '/../../services/SkinQuestionBank.php';
$questions=SkinQuestionBank::rows($conn,false);
$concerns=$conn->query("SELECT id,concern_name AS label,status FROM skin_concerns ORDER BY concern_name")->fetch_all(MYSQLI_ASSOC);
$types=$conn->query("SELECT id,assessment_key,skin_type_name AS label,status FROM skin_types ORDER BY skin_type_name")->fetch_all(MYSQLI_ASSOC);
$concernLabels=array_column($concerns,'label','id');$typeLabels=array_column($types,'label','id');
$pageTitle='Assessment Questions';
$saStyles=['assessment-questions'];
$saConfig=['questionBank'=>$questions,'sources'=>SkinEvidence::sources(),'concerns'=>$concerns,'types'=>$types];
require __DIR__.'/../skin/_header.php';
?>
<section class="sa-heading"><div><h1>Assessment Questions</h1><p><?= count($questions) ?> questions · research-informed, custom assessment</p></div><button type="button" class="sa-primary" data-sa-question="0">Add question</button></section>
<p class="sa-alert">Questions, answer labels, scoring weights, sources and mappings are configurable. Changes apply to new assessments; saved assessments keep their original snapshots. Published studies validate their own instruments. Citing a source does not make custom questions or scoring clinically validated. Deleted or inactive questions stop appearing in new assessments.</p>
<section class="sa-card sa-question-card">
<div class="sa-question-toolbar">
    <div class="sa-question-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" id="assessmentQuestionSearch" placeholder="Search questions..." autocomplete="off" aria-label="Search assessment questions, answers or evidence"></div>
    <div class="sa-question-filter"><i class="fa-solid fa-filter" aria-hidden="true"></i><select id="assessmentQuestionStatus" aria-label="Filter assessment questions by status"><option value="all">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></div>
</div>
<div class="sa-table-scroll"><table class="sa-table"><thead><tr><th>Question</th><th>Evidence / purpose</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($questions as $q): $source=SkinQuestionBank::source($q); ?><tr data-assessment-question data-status="<?= saEsc($q['status']) ?>"><td class="sa-description"><strong><?= saEsc($q['prompt']) ?></strong><small><?= saEsc($q['question_key']) ?> · revision <?= (int)$q['revision'] ?> · <?= saEsc(['skin_type'=>'Skin-type scoring','sensitivity'=>'Sensitivity scoring','clarification'=>'Clarification scoring','observation'=>'Concern observation'][$q['question_kind']]) ?></small><ol type="A"><?php foreach(json_decode($q['options_json'],true) as $option):?><li><?= saEsc($option) ?></li><?php endforeach;?></ol><?php if($q['concern_ids']):?><small class="sa-question-mappings">Concerns: <?= saEsc(implode(', ',array_map(fn($id)=>$concernLabels[$id]??'Removed concern',$q['concern_ids']))) ?></small><?php endif; ?><?php if($q['skin_type_ids']):?><small class="sa-question-mappings">Applies to: <?= saEsc(implode(', ',array_map(fn($id)=>$typeLabels[$id]??'Removed skin type',$q['skin_type_ids']))) ?></small><?php endif; ?></td><td class="sa-description"><?php if($source['url']):?><a class="sa-question-source" href="<?= saEsc($source['url']) ?>" target="_blank" rel="noopener noreferrer"><?= saEsc($source['title']) ?></a><?php else:?><?= saEsc($source['title']) ?><?php endif; ?><?php if($source['author']!==''):?><small>Author(s): <?= saEsc($source['author']) ?></small><?php endif; ?><small><?= saEsc($q['rationale']) ?></small></td><td><span class="sa-question-status sa-question-status-<?= saEsc($q['status']) ?>"><?= saEsc(strtoupper($q['status'])) ?></span></td><td><div class="sa-row-actions"><button type="button" class="sa-question-edit" data-sa-question="<?= (int)$q['id'] ?>" title="Edit question" aria-label="Edit question <?= saEsc($q['question_key']) ?>"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></button><button type="button" class="sa-question-delete" data-sa-question-delete="<?= (int)$q['id'] ?>" title="Delete question" aria-label="Delete question <?= saEsc($q['question_key']) ?>"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button></div></td></tr><?php endforeach;?>
<tr id="assessmentQuestionsEmpty" <?= $questions ? 'hidden' : '' ?>><td colspan="4"><div class="sa-question-empty" role="status"><i class="fa-regular fa-folder-open" aria-hidden="true"></i><p>No assessment questions found.</p></div></td></tr>
</tbody></table></div></section>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/assessment-questions.js"></script>
<?php require __DIR__.'/../skin/_footer.php'; ?>
