<?php
declare(strict_types=1);
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../services/SkinProfile.php';
require_once __DIR__.'/../services/SkinMatcher.php';
require_once __DIR__.'/../services/skin-questions.php';
$sqCustomer=(int)($_SESSION['user_id']??0)>0&&($_SESSION['role']??'')==='customer';
if($sqCustomer){require_once __DIR__.'/../includes/role-check.php';requirePureViaCustomer($conn);}
if(empty($_SESSION['csrf_token']))$_SESSION['csrf_token']=bin2hex(random_bytes(32));
$sqOrigin=isset($skinProfileAccount)?'profile_edit':'quiz';
$sqSaved=$sqCustomer&&$sqOrigin==='profile_edit'?SkinProfile::load($conn,(int)$_SESSION['user_id']):null;
$sqToken=bin2hex(random_bytes(32));
$_SESSION['skin_assessment_tokens']=array_filter($_SESSION['skin_assessment_tokens']??[],fn($v)=>$v['expires']>=time());
if(count($_SESSION['skin_assessment_tokens'])>=20)array_shift($_SESSION['skin_assessment_tokens']);
$_SESSION['skin_assessment_tokens'][$sqToken]=['origin'=>$sqOrigin,'expires'=>time()+3600];
$sqChoices=SkinProfile::choices($conn);
$sqBoot=['baseUrl'=>BASE_URL,'csrfToken'=>$_SESSION['csrf_token'],'assessmentToken'=>$sqToken,'canSave'=>$sqCustomer,'questions'=>skinQuestions(),'choices'=>$sqChoices,'guided'=>($_GET['path']??'')==='guided',
    'edit'=>isset($_GET['edit']),'saved'=>$sqSaved?['profile'=>$sqSaved,'matches'=>SkinMatcher::match($conn,$sqSaved),'saved'=>true]:null];
function sqEsc(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
function sqQuestion(string $key,array $definition):void {?>
    <fieldset class="sq-question" data-question="<?= sqEsc($key) ?>"><legend><?= sqEsc($definition[0]) ?></legend><div class="sq-options">
        <?php foreach($definition[1] as $index=>$label):?><label class="sq-choice"><input type="radio" name="<?= sqEsc($key) ?>" value="<?= chr(65+$index) ?>"><span><?= sqEsc($label) ?></span></label><?php endforeach; ?>
    </div></fieldset>
<?php }
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Find Your Skin Match | PureVia</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<?php foreach(['header','search','footer','responsive','skin-quiz'] as $css):?><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $css ?>.css"><?php endforeach; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&amp;family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
</head><body>
<?php include __DIR__.'/../includes/header.php'; ?>
<main class="sq-page">
    <svg class="sq-botanical" viewBox="0 0 230 420" aria-hidden="true"><path d="M35 410Q130 210 175 0" fill="none" stroke="currentColor" stroke-width="3"/><path d="M90 275Q5 240 38 150Q106 190 90 275ZM112 222Q200 220 224 125Q144 150 112 222ZM134 156Q67 110 101 30Q146 78 134 156ZM159 81Q219 67 226 0Q178 20 159 81Z" fill="currentColor"/></svg>
    <div class="sq-container">
        <header class="sq-heading"><h1><?= $sqOrigin==='profile_edit'?'My Skin Profile':'Find Your Skin Match' ?></h1><p>Answer a few questions so we can recommend products that suit your skin.</p></header>
        <p class="sq-error" id="sqError" role="alert" hidden></p>
        <noscript><p>Please enable JavaScript to complete your skin profile.</p></noscript>
        <nav class="sq-progress" id="sqProgress" aria-label="Profile steps" hidden>
            <?php foreach(['Skin Type','Sensitivity','Concerns','Ingredients','Routine','Review'] as $i=>$label):?><div data-phase="<?= $i ?>"><span><?= $i+1 ?></span><small><?= $label ?></small></div><?php endforeach; ?>
        </nav>
        <form id="sqForm" novalidate>
            <section class="sq-panel" data-screen="awareness">
                <h2><span class="sq-question-icon" aria-hidden="true">?</span> Do you already know your skin type?</h2><p class="sq-subtitle">Skip the guided questions if you already know your skin type.</p>
                <div class="sq-awareness-grid">
                    <label class="sq-choice sq-choice-tile"><input type="radio" name="awareness" value="yes"><span class="sq-path-icon" aria-hidden="true">✓</span><strong>Yes, I know my skin type</strong><small>Select your skin type and continue<br>to personalized questions.</small></label>
                    <label class="sq-choice sq-choice-tile"><input type="radio" name="awareness" value="no"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 7Q9 2 3 5v21q6-3 13 1 7-4 13-1V5q-6-3-13 2Zm0 0v20" fill="none" stroke="currentColor" stroke-width="2"/></svg><strong>No / Not sure</strong><small>Let us guide you with a few questions<br>to identify your skin type.</small></label>
                </div>
            </section>
            <section class="sq-panel" data-screen="manual" hidden>
                <h2>What is your skin type?</h2><p class="sq-subtitle">Select the option that best describes your skin. You can update this anytime.</p>
                <div class="sq-types-grid">
                    <?php $sqDescriptions=['normal'=>'Balanced, neither too oily nor too dry.','dry'=>'Often feels tight, rough, or flaky.','oily'=>'Produces excess oil and shine.','combination'=>'Oily in some areas, dry or normal in others.'];$sqIcons=[
                        'normal'=>'<svg viewBox="0 0 40 40"><path d="M20 35C8 32 3 21 5 8c11 2 17 9 15 27Z" fill="#5a8860"/><path d="M20 35C17 19 25 9 36 5c3 14-3 25-16 30Z" fill="#3c7050"/><path d="m20 35-9-18m9 18 9-20" fill="none" stroke="#c0d5a7" stroke-width="1.3"/></svg>',
                        'dry'=>'<svg viewBox="0 0 40 40"><path d="M20 4C16 13 9 20 9 27a11 11 0 0 0 22 0c0-7-7-14-11-23Z" fill="#9bd6f1" stroke="#62acd7" stroke-width="1.6"/><path d="M14 25q-1 7 6 8" fill="none" stroke="#d9f2fb" stroke-width="2" stroke-linecap="round"/></svg>',
                        'oily'=>'<svg viewBox="0 0 40 40"><path d="m25 5 4 11 9 4-9 4-4 11-4-11-9-4 9-4Z" fill="#e8b55a"/><path d="m9 2 2 6 5 2-5 2-2 6-2-6-5-2 5-2Zm1 22 2 4 4 2-4 2-2 5-2-5-4-2 4-2Z" fill="#cbd3c1"/></svg>',
                        'combination'=>'<svg viewBox="0 0 40 40"><circle cx="16" cy="20" r="14" fill="#4c9a6c"/><circle cx="24" cy="20" r="14" fill="#167148" fill-opacity=".82"/><path d="M20 7a14 14 0 0 1 0 26 14 14 0 0 1 0-26Z" fill="#2e875a"/></svg>'
                    ];foreach($sqChoices['types'] as $type): ?>
                        <label class="sq-choice sq-choice-tile"><input type="radio" name="skin_type" value="<?= sqEsc($type['value']) ?>"><span class="sq-type-icon" aria-hidden="true"><?= $sqIcons[$type['value']]??'○' ?></span><strong><?= sqEsc($type['label']) ?></strong><small><?= sqEsc($sqDescriptions[$type['value']]??$type['description']) ?></small></label>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="sq-text-button" id="sqSensitiveOnly">Not sure / Sensitive only? Use the guided finder.</button>
            </section>
            <?php foreach(range(2,8) as $q):?><section class="sq-panel sq-guided" data-screen="q<?= $q ?>" hidden><p class="sq-kicker">Guided Skin Type Finder · <?= $q-1 ?> of 7</p><?php sqQuestion('q'.$q,skinQuestions()['q'.$q]); ?><?php if($q===8):?><p class="sq-help">This observation is optional. You do not need to perform a tissue test; choose Not sure to skip.</p><?php endif; ?></section><?php endforeach; ?>
            <section class="sq-panel" data-screen="sensitivity" hidden><h2>How does your skin react?</h2><p class="sq-subtitle">Sensitivity is separate from your primary skin type.</p><?php foreach(['q9','q10','q11'] as $q)sqQuestion($q,skinQuestions()[$q]); ?></section>
            <section class="sq-panel" data-screen="concerns" hidden><h2>What are your skin concerns?</h2><p class="sq-subtitle">Select all that apply.</p><div class="sq-options sq-two-columns">
                <?php foreach($sqChoices['concerns'] as $c):?><label class="sq-choice"><input type="checkbox" name="concerns" value="<?= (int)$c['id'] ?>"><span><?= sqEsc($c['label']) ?></span></label><?php endforeach; ?>
                <label class="sq-choice"><input type="checkbox" id="sqNoConcerns"><span>No specific concern</span></label>
                </div><p class="sq-help" id="sqConcernCount" aria-live="polite">0 concerns selected</p><label class="sq-field" id="sqMainConcernField" hidden>Which concern would you most like to improve first?<select id="sqMainConcern"><option value="">Select your main concern</option></select></label>
            </section>
            <section class="sq-panel" data-screen="ingredients" hidden><h2>Any ingredients you want to avoid?</h2><p class="sq-subtitle">Optional. Select only ingredients you personally wish to avoid.</p><div class="sq-options">
                <label class="sq-choice"><input type="radio" name="ingredient_preference" value="selected"><span>Yes, I know which ingredients</span></label><label class="sq-choice"><input type="radio" name="ingredient_preference" value="none"><span>No specific ingredients</span></label><label class="sq-choice"><input type="radio" name="ingredient_preference" value="unsure"><span>I'm not sure</span></label>
                </div><div id="sqAvoided" hidden><label class="sq-field">Search ingredients<input type="search" id="sqIngredientSearch" placeholder="Search the ingredient catalog"></label><div class="sq-ingredient-list" id="sqIngredientList">
                <?php foreach($sqChoices['ingredients'] as $ingredient):?><label class="sq-choice"><input type="checkbox" name="avoided_ingredients" value="<?= (int)$ingredient['id'] ?>"><span><?= sqEsc($ingredient['label']) ?></span></label><?php endforeach; ?></div><p id="sqIngredientEmpty" class="sq-help" <?= $sqChoices['ingredients']?'hidden':'' ?>>No ingredients found.</p>
                <label class="sq-field">Why do you avoid these ingredients? (Optional)<select id="sqAvoidanceReason"><option value="">Select a reason</option><option value="irritation">Previously experienced irritation</option><option value="medical_advice">Known allergy or medical advice</option><option value="preference">Personal preference</option><option value="other">Other / Prefer not to say</option></select></label></div>
            </section>
            <section class="sq-panel" data-screen="routine" hidden><h2>What type of routine do you prefer?</h2><p class="sq-subtitle">Choose how simple you would like your routine to be.</p><div class="sq-options">
                <label class="sq-choice"><input type="radio" name="routine_preference" value="basic"><span><strong>Basic skincare routine</strong><small>Cleanser, moisturizer, sunscreen</small></span></label><label class="sq-choice"><input type="radio" name="routine_preference" value="targeted"><span><strong>Targeted skincare routine</strong><small>The basics plus an optional product for your main concern</small></span></label><label class="sq-choice"><input type="radio" name="routine_preference" value="unsure"><span><strong>Not sure</strong><small>Recommend a simple routine</small></span></label></div>
            </section>
            <footer class="sq-actions" id="sqFormActions"><button type="button" class="sq-secondary" id="sqBack">← &nbsp;Back</button><button type="submit" class="sq-primary" id="sqNext">Continue &nbsp; →</button></footer>
        </form>
        <section id="sqReview" hidden aria-labelledby="sqResultTitle">
            <div class="sq-panel sq-review-panel"><p class="sq-kicker" id="sqReviewLabel">Review your skin profile</p><h2 id="sqResultTitle" tabindex="-1"></h2><p class="sq-subtitle" id="sqResultDescription"></p><dl class="sq-summary" id="sqSummary"></dl><p class="sq-notice" id="sqSensitivityNotice" hidden>Frequent irritation was reported. Consider consulting a dermatologist, especially for severe or persistent reactions. These recommendations cannot identify an allergy or guarantee compatibility.</p>
                <div id="sqClarification" hidden><h3>A little more about your skin</h3><p class="sq-help">Your answers overlap. Try these two clarification questions, or choose your skin type manually.</p><?php sqQuestion('f1',skinQuestions()['f1']);sqQuestion('f2',skinQuestions()['f2']); ?><button type="button" id="sqRecalculate" class="sq-primary">Update my preview</button></div>
                <div class="sq-review-actions"><button type="button" class="sq-secondary" id="sqEdit">Edit answers</button><button type="button" class="sq-secondary" id="sqManualOverride" hidden>Choose skin type manually</button><button type="button" class="sq-primary" id="sqSave" <?= $sqCustomer?'':'hidden' ?>>Save Skin Profile</button><a class="sq-primary" id="sqLogin" href="<?= BASE_URL ?>/public/login.php" <?= $sqCustomer?'hidden':'' ?>>Log in to save profile</a></div><p id="sqSaveStatus" role="status"></p>
                <fieldset id="sqRating" class="sq-rating" hidden><legend>How useful were these recommendations?</legend><div><?php foreach(range(1,5) as $n):?><button type="button" data-rating="<?= $n ?>" aria-label="<?= $n ?> out of 5" aria-pressed="false"><?= $n ?> ★</button><?php endforeach; ?></div><p id="sqRatingStatus" class="sq-help" role="status"></p></fieldset>
            </div>
            <section class="sq-recommendations"><h2>Your skincare recommendations</h2><p class="sq-help" id="sqMatchNotice"></p><div id="sqProducts" class="sq-product-grid"></div></section>
            <section class="sq-routine"><h2>Your example skincare routine</h2><p class="sq-help">Follow each product's label directions. This is an informational shopping guide, not a treatment plan.</p><div id="sqRoutine" class="sq-routine-grid"></div><div id="sqTargeted"></div></section>
        </section>
        <p class="sq-disclaimer">The guided finder estimates your skin type using questionnaire rules. It is not a medical assessment.</p>
    </div>
</main>
<?php include __DIR__.'/../includes/footer.php'; ?>
<script>window.PUREVIA_SKIN_QUIZ=<?= json_encode($sqBoot,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;window.PUREVIA_BASE_URL=<?= json_encode(BASE_URL) ?>;</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script><script src="<?= BASE_URL ?>/assets/js/search.js"></script><script src="<?= BASE_URL ?>/assets/js/skin-quiz.js"></script>
</body></html>
