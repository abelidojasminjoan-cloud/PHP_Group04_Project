<?php
require __DIR__ . '/../skin/_boot.php';
require_once __DIR__ . '/../../services/QuizAnalytics.php';

/* ==========================================
   QUIZ ANALYTICS CSV EXPORT
   MUST RUN BEFORE HTML OUTPUT
========================================== */

if (($_GET['export'] ?? '') === 'csv') {

    try {
        [$from, $to, $until] =
            QuizAnalytics::dates($_GET);
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        exit('Invalid report dates.');
    }

    $method = $_GET['method'] ?? 'all';
    $clarity = $_GET['clarity'] ?? 'all';
    $skinType = $_GET['skin_type'] ?? 'all';

    if (
        !is_string($method) ||
        !in_array($method, ['all', 'guided', 'manual'], true) ||
        !is_string($clarity) ||
        !in_array(
            $clarity,
            ['all', 'clear', 'ambiguous', 'insufficient'],
            true
        ) ||
        !is_string($skinType) ||
        !preg_match('/^[a-zA-Z _-]{1,100}$/', $skinType)
    ) {
        http_response_code(422);
        exit('Invalid report filters.');
    }

    $records = [];
    $page = 1;
    $pageSize = 100;

    do {
        $report = QuizAnalytics::report(
            $conn,
            $from,
            $until,
            $page,
            $pageSize
        );

        foreach ($report['rows'] as $row) {

            $rowMethod = strtolower(
                (string) ($row['assessment_method'] ?? '')
            );

            $rowClarity = strtolower(
                (string) ($row['clarity'] ?? '')
            );

            $rowType = strtolower(
                (string) ($row['skin_type_label'] ?? '')
            );

            if ($method !== 'all' && $rowMethod !== $method) {
                continue;
            }

            if ($clarity !== 'all' && $rowClarity !== $clarity) {
                continue;
            }

            if (
                $skinType !== 'all' &&
                $rowType !== strtolower($skinType)
            ) {
                continue;
            }

            $records[] = $row;
        }

        $pages = max(1, (int) ($report['pages'] ?? 1));

        $page++;

    } while ($page <= $pages);

    /* ======================================
       CALCULATE FILTERED TOTALS
    ====================================== */

    $total = count($records);

    $methods = ['guided' => 0, 'manual' => 0];

    $clarities = [
        'clear' => 0,
        'ambiguous' => 0,
        'insufficient' => 0
    ];

    $types = [];

    foreach ($records as $row) {

        $m = strtolower(
            (string) ($row['assessment_method'] ?? '')
        );

        $c = strtolower(
            (string) ($row['clarity'] ?? '')
        );

        $type = trim(
            (string) ($row['skin_type_label'] ?? '')
        ) ?: 'Undetermined';

        if (isset($methods[$m])) {
            $methods[$m]++;
        }

        if ($m === 'guided') {
            if (isset($clarities[$c])) {
                $clarities[$c]++;
            }

            $types[$type] = ($types[$type] ?? 0) + 1;
        }
    }

    ksort($types);

    $guided = $methods['guided'];

    /* ======================================
       DOWNLOAD HEADERS
    ====================================== */

    header('Content-Type: text/csv; charset=UTF-8');

    header(
        'Content-Disposition: attachment; filename="' .
        'purevia-quiz-analytics-' . $from .
        '-to-' . $to . '.csv"'
    );

    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');

    $out = fopen('php://output', 'w');

    if ($out === false) {
        exit('Unable to create CSV.');
    }

    fwrite($out, "\xEF\xBB\xBF");

    $write = static function (array $row) use ($out): void {
        $safe = array_map(
            static function ($value): string {
                $value = (string) ($value ?? '');
                return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value)
                    ? "'" . $value
                    : $value;
            },
            $row
        );
        fputcsv($out, $safe);
    };

    $percent = static function (int $count, int $base): string {
        return $base > 0
            ? number_format($count * 100 / $base, 2) . '%'
            : '0.00%';
    };

    /* ======================================
       SECTION 1: REPORT OVERVIEW
    ====================================== */

    $write(['PUREVIA QUIZ ANALYTICS REPORT']);
    $write(['Report From', $from]);
    $write(['Report To', $to]);
    $write(['Method Filter', ucfirst($method)]);
    $write(['Clarity Filter', ucfirst($clarity)]);
    $write(['Skin Type Filter', ucfirst($skinType)]);

    $write([]);
    $write(['ASSESSMENT OVERVIEW', 'COUNT']);

    $write(['Total Assessments', $total]);
    $write(['Guided Assessments', $methods['guided']]);
    $write(['Manual Assessments', $methods['manual']]);

    /* ======================================
       SECTION 2: ASSESSMENT METHODS
    ====================================== */

    $write([]);
    $write(['ASSESSMENT METHOD SUMMARY']);
    $write(['Method', 'Count', 'Percentage']);

    foreach ($methods as $name => $count) {
        $write([
            ucfirst($name),
            $count,
            $percent($count, $total)
        ]);
    }

    /* ======================================
       SECTION 3: SKIN TYPE RESULTS
    ====================================== */

    $write([]);
    $write(['SKIN TYPE RESULTS']);
    $write(['Skin Type', 'Count', 'Percentage']);

    foreach ($types as $type => $count) {
        $write([
            $type,
            $count,
            $percent($count, $guided)
        ]);
    }

    /* ======================================
       SECTION 4: RESULT CLARITY
    ====================================== */

    $write([]);
    $write(['GUIDED RESULT CLARITY']);
    $write(['Clarity', 'Count', 'Percentage']);

    foreach ($clarities as $name => $count) {
        $write([
            ucfirst($name),
            $count,
            $percent($count, $guided)
        ]);
    }

    /* ======================================
       SECTION 5: CUSTOMER ASSESSMENTS
    ====================================== */

    $write([]);
    $write(['DETAILED CUSTOMER ASSESSMENTS']);

    $write([
        'Assessment ID',
        'Assessment Date',
        'Customer Name',
        'Method',
        'Skin Type',
        'Result Clarity'
    ]);

    
foreach ($records as $row) {

    $customerName = trim(
        (string) ($row['customer'] ?? '')
    );

    $skinTypeResult = trim(
        (string) ($row['skin_type_label'] ?? '')
    );

    $write([
        $row['id'] ?? '',
        $row['created_at'] ?? '',

        $customerName !== ''
            ? $customerName
            : 'Unknown / Deleted Account',

        ucfirst(
            (string) ($row['assessment_method'] ?? '')
        ),

        $skinTypeResult !== ''
            ? $skinTypeResult
            : 'Undetermined',

        ucfirst(
            (string) ($row['clarity'] ?? '')
        )
    ]);
}


    if (!$records) {
        $write(['No matching assessments']);
    }

    $write([]);
    $write([
        'Note',
        'Clear results describe questionnaire clarity, not medical certainty.'
    ]);

    fclose($out);

    // CRITICAL: Do not print webpage HTML in CSV.
    exit;
}

/* ==========================================
   EXISTING QUIZ ANALYTICS PAGE
========================================== */

$pageTitle = 'Quiz Analytics';
$rangeError = '';


// LOAD QUIZ ANALYTICS EXPORT CSS
$saStyles = ['quiz-analytics-export'];


try{[$from,$to,$until]=QuizAnalytics::dates($_GET);}catch(InvalidArgumentException $e){$rangeError=$e->getMessage();[$from,$to,$until]=QuizAnalytics::dates([]);}
$all=($_GET['view']??'')==='all';$report=QuizAnalytics::report($conn,$from,$until,max(1,(int)($_GET['page']??1)),$all?25:10);$t=$report['totals'];
$guidedPercent=$t['total']?round($t['guided']/$t['total']*100):0;$clearPercent=$t['guided']?round($t['clear_count']/$t['guided']*100):0;
$clarityClear=$t['guided']?$t['clear_count']/$t['guided']*100:0;$clarityAmbiguous=$t['guided']?$t['ambiguous']/$t['guided']*100:0;
require_once __DIR__ . '/../../services/skin-questions.php';
$saConfig=['questions'=>skinQuestions(),'concerns'=>$conn->query('SELECT id,concern_name AS label FROM skin_concerns')->fetch_all(MYSQLI_ASSOC),'ingredients'=>$conn->query('SELECT id,ingredient_name AS label FROM ingredients')->fetch_all(MYSQLI_ASSOC)];
require __DIR__.'/../skin/_header.php';
?>
<section class="sa-heading"><div><h1>Quiz Analytics</h1><p>Insights from the Skin Type Finder and product recommendations.</p></div><form method="get" class="sa-date-filter"><label>From<input type="date" name="from" value="<?= saEsc($from) ?>" required></label><label>To<input type="date" name="to" value="<?= saEsc($to) ?>" required></label><button type="submit" class="sa-secondary">Apply</button></form></section>


<!-- ==========================================
     PUREVIA QUIZ ANALYTICS CSV EXPORT
========================================== -->

<section class="qa-export-panel">

    <div class="qa-export-heading">

        <div class="qa-export-icon">
            <i class="fa-solid fa-file-csv"></i>
        </div>

        <div>
            <h2>Export Quiz Analytics Report</h2>
            <p>
                Download filtered assessment summaries,
                skin type results, and customer assessments.
            </p>
        </div>

    </div>

    <form method="GET" class="qa-export-form">

        <!-- IMPORTANT: EXPORT USING THIS SAME PHP FILE -->
        <input type="hidden" name="export" value="csv">

        <div class="qa-export-grid">

            <label>
                From Date
                <input
                    type="date"
                    name="from"
                    value="<?= saEsc($from) ?>"
                    required
                >
            </label>

            <label>
                To Date
                <input
                    type="date"
                    name="to"
                    value="<?= saEsc($to) ?>"
                    required
                >
            </label>

            <label>
                Assessment Method
                <select name="method">
                    <option value="all">All Methods</option>
                    <option value="guided">Guided</option>
                    <option value="manual">Manual</option>
                </select>
            </label>

            <label>
                Result Clarity
                <select name="clarity">
                    <option value="all">All Results</option>
                    <option value="clear">Clear</option>
                    <option value="ambiguous">Ambiguous</option>
                    <option value="insufficient">Insufficient</option>
                </select>
            </label>

            <label>
                Skin Type
                <select name="skin_type">
                    <option value="all">All Skin Types</option>

                    <?php
                    $quizSkinTypes = $conn->query("
                        SELECT skin_type_name
                        FROM skin_types
                        ORDER BY skin_type_name
                    ")->fetch_all(MYSQLI_ASSOC);
                    ?>

                    <?php foreach ($quizSkinTypes as $type): ?>
                        <option
                            value="<?= saEsc(strtolower($type['skin_type_name'])) ?>"
                        >
                            <?= saEsc($type['skin_type_name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </label>

        </div>

        <div class="qa-export-footer">

            <p>
                Export all matching assessments for the
                selected date range and filters.
            </p>

            <button type="submit">
                <i class="fa-solid fa-download"></i>
                Export Quiz Analytics CSV
            </button>

        </div>

    </form>

</section>


<?php if($rangeError):?><p class="sa-alert" role="alert"><?= saEsc($rangeError) ?></p><?php endif; ?>
<section class="sa-stat-grid" aria-label="Assessment summary">
<article class="sa-stat"><span class="sa-stat-icon" aria-hidden="true"><svg viewBox="0 0 28 28" fill="#6484a4"><circle cx="14" cy="7" r="4"/><path d="M7 23v-4a7 7 0 0 1 14 0v4Z"/><circle cx="5" cy="10" r="3" opacity=".65"/><circle cx="23" cy="10" r="3" opacity=".65"/><path d="M1 23v-6a4 4 0 0 1 7-2l-3 8Zm26 0v-6a4 4 0 0 0-7-2l3 8Z" opacity=".65"/></svg></span><strong><?= number_format($t['total']) ?></strong><p>Total Assessments</p><small>Saved quiz completions</small></article>
<article class="sa-stat"><span class="sa-stat-icon" aria-hidden="true"><svg viewBox="0 0 28 28"><rect x="3" y="13" width="5" height="12" rx="1" fill="#aab8b5"/><rect x="11" y="4" width="5" height="21" rx="1" fill="#bc9c8e"/><rect x="19" y="8" width="5" height="17" rx="1" fill="#d5b7a9"/></svg></span><strong><?= $guidedPercent ?>%</strong><p>Guided Assessments</p><small><?= $t['guided'] ?> of <?= $t['total'] ?> assessments</small></article>
<article class="sa-stat"><span class="sa-stat-icon" aria-hidden="true"><svg viewBox="0 0 28 28"><circle cx="14" cy="14" r="12" fill="#528e68"/><path d="m8 14 4 4 8-9" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span><strong><?= $clearPercent ?>%</strong><p>Clear Guided Results</p><small>Among guided assessments</small></article>
<article class="sa-stat"><span class="sa-stat-icon sa-gold">★</span><strong><?= $t['average_usefulness']===null?'—':number_format((float)$t['average_usefulness'],1) ?></strong><p>Average Usefulness</p><small><?= $t['rating_count'] ?> customer ratings · out of 5</small></article>
</section>
<section class="sa-chart-grid">
<article class="sa-chart"><h2>Assessment Method</h2><div class="sa-donut-layout"><div class="sa-donut" role="img" aria-label="<?= $t['guided'] ?> guided and <?= $t['manual'] ?> manual assessments" style="--sa-chart:<?= $t['total']?'conic-gradient(#127d50 0 '.$guidedPercent.'%,#a4cdae '.$guidedPercent.'% 100%)':'#ece8df' ?>"><div><strong><?= $t['total'] ?></strong><small>Total</small></div></div><ul class="sa-legend"><li><i style="background:#127d50"></i><span>Guided<small><?= $guidedPercent ?>% (<?= $t['guided'] ?>)</small></span></li><li><i style="background:#a4cdae"></i><span>Manual<small><?= $t['total']?100-$guidedPercent:0 ?>% (<?= $t['manual'] ?>)</small></span></li></ul></div></article>
<article class="sa-chart"><h2>Guided Results by Skin Type</h2><div class="sa-bars" role="img" aria-label="<?= saEsc(implode(', ',array_map(fn($k,$v)=>ucfirst($k).': '.$v,array_keys($report['types']),$report['types']))) ?>">
<?php $max=max(1,...array_values($report['types']));foreach($report['types'] as $type=>$count):?><div><span><?= $count ?></span><i class="sa-bar sa-bar-<?= $type ?>" style="height:<?= $count?max(3,round($count/$max*115)):0 ?>px"></i><small><?= ucfirst($type) ?></small></div><?php endforeach; ?></div><p class="sa-help">Unresolved results are shown in Result Clarity.</p></article>
<article class="sa-chart"><h2>Result Clarity</h2><div class="sa-donut-layout"><div class="sa-donut" role="img" aria-label="Guided results: <?= $t['clear_count'] ?> clear, <?= $t['ambiguous'] ?> ambiguous, <?= $t['insufficient'] ?> insufficient" style="--sa-chart:<?= $t['guided']?'conic-gradient(#77a37e 0 '.$clarityClear.'%,#ffd083 '.$clarityClear.'% '.($clarityClear+$clarityAmbiguous).'%,#ef9a86 '.($clarityClear+$clarityAmbiguous).'% 100%)':'#ece8df' ?>"><div></div></div><ul class="sa-legend"><?php foreach(['Clear'=>[$t['clear_count'],'#77a37e'],'Ambiguous'=>[$t['ambiguous'],'#ffd083'],'Insufficient'=>[$t['insufficient'],'#ef9a86']] as $label=>[$count,$color]):?><li><i style="background:<?= $color ?>"></i><span><?= $label ?><small><?= $t['guided']?round($count/$t['guided']*100):0 ?>% (<?= $count ?>)</small></span></li><?php endforeach; ?></ul></div></article>
</section>
<section class="sa-card" id="saRecent"><div class="sa-card-heading"><h2>Recent Assessments</h2><?php if(!$all):?><a href="?<?= saEsc(http_build_query(['from'=>$from,'to'=>$to,'view'=>'all'])) ?>#saRecent">View All</a><?php endif; ?></div><div class="sa-table-scroll"><table class="sa-table"><thead><tr><th>Date</th><th>Customer</th><th>Method</th><th>Result</th><th>Clarity</th><th>Actions</th></tr></thead><tbody>
<?php foreach($report['rows'] as $r):?><tr><td><?= saEsc(date('M d, Y h:i A',strtotime($r['created_at']))) ?></td><td><?= saEsc($r['customer']?:'Deleted account') ?></td><td><?= saEsc(ucfirst($r['assessment_method'])) ?></td><td><?= saEsc($r['skin_type_label']?:'Undetermined') ?></td><td><span class="sa-badge <?= $r['clarity']==='clear'?'sa-green':($r['clarity']==='ambiguous'?'sa-amber':'sa-muted') ?>"><?= saEsc(ucfirst($r['clarity'])) ?></span></td><td><button type="button" class="sa-link" data-sa-assessment="<?= (int)$r['id'] ?>">View</button></td></tr><?php endforeach; ?>
<?php if(!$report['rows']):?><tr><td colspan="6">No saved quiz assessments in this date range.</td></tr><?php endif; ?></tbody></table></div><nav class="sa-pagination" aria-label="Assessment pages"><?php $query=['from'=>$from,'to'=>$to,'view'=>$all?'all':'recent'];if($report['page']>1):?><a href="?<?= saEsc(http_build_query($query+['page'=>$report['page']-1])) ?>#saRecent">← Previous</a><?php endif; ?><span>Page <?= $report['page'] ?> of <?= $report['pages'] ?></span><?php if($report['page']<$report['pages']):?><a href="?<?= saEsc(http_build_query($query+['page'=>$report['page']+1])) ?>#saRecent">Next →</a><?php endif; ?></nav></section>
<p class="sa-help">Profile edits and repeated saves are excluded from quiz completion totals. “Clear” describes questionnaire result clarity, not medical certainty.</p>
<?php require __DIR__.'/../skin/_footer.php'; ?>
