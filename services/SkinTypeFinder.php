<?php
declare(strict_types=1);
/** PureVia project scoring rules; an informational estimate, not a diagnostic scale. */
require_once __DIR__.'/QuestionScoring.php';
final class SkinTypeFinder
{
    public const TYPES=['normal','dry','oily','combination'];
    private const OPTIONS=['A'=>'dry','B'=>'normal','C'=>'oily','D'=>'combination'];
    // Equal observation votes. These are transparent project rules, not published clinical cutoffs.
    private const WEIGHTS=[2=>[1,1,1,1],3=>[1,1,1,1],4=>[1,1,1,1],5=>[1,1,1,1],6=>[1,1,1,1],7=>[1,1,1,1],8=>[1,1,1,1]];
    public static function assess(array $answers, ?array $bank=null): array
    {
        if ($bank !== null) return QuestionScoring::assess($answers, $bank);
        $scores=array_fill_keys(self::TYPES,0); $answered=$followups=0;
        foreach(self::WEIGHTS as $q=>$weights){
            $a=$answers['q'.$q]??'';
            if(!is_string($a)||!isset(self::OPTIONS[$a]))continue;
            $scores[self::OPTIONS[$a]]+=$weights[ord($a)-65]; $answered++;
        }
        foreach(['f1','f2'] as $q){$a=$answers[$q]??'';if(is_string($a)&&isset(self::OPTIONS[$a])){$scores[self::OPTIONS[$a]]++;$followups++;}}
        arsort($scores);$values=array_values($scores);$margin=$values[0]-$values[1];
        $observations=[$answers['q2']??'', $answers['q3']??'', $answers['q4']??'', $answers['q5']??'', $answers['q7']??'', $answers['q8']??''];
        $conflict=in_array('A',$observations,true)&&in_array('C',$observations,true);
        $followupConflict=$followups===2&&$answers['f1']!==$answers['f2'];
        $followupDisagrees=$followups===2&&($answers['f1']!==array_search(array_key_first($scores),self::OPTIONS,true));
        $reason=$answered<5?'insufficient':($followupConflict||$followupDisagrees?'conflicting':($margin<2?'close':($values[0]<=($answered+$followups)/2?'inconclusive':($conflict&&$followups<2?'conflicting':null))));
        return ['status'=>$reason?'unclear':'suggested','reason'=>$reason,'skin_type'=>$reason?null:array_key_first($scores),'scores'=>$scores,'answered'=>$answered,'margin'=>$margin,'followups_answered'=>$followups,'needs_followup'=>$reason!==null&&$answered>=5&&$followups<2,'rule_version'=>'equal-observations-v2','validation_status'=>'Research-informed custom rules; not clinically validated','explanation'=>'Each answered observation contributes one vote. A result requires at least five observations, a majority and a lead of at least two votes. Conflicting answers may remain undetermined. Scores are not probabilities.'];
    }
    public static function sensitivity(array $answers, ?array $bank=null): array
    {
        if ($bank !== null) return QuestionScoring::sensitivity($answers, $bank);
        $total=$answered=0;$frequent=false;$points=['A'=>0,'B'=>1,'C'=>2];
        foreach(['q9','q10','q11'] as $q){$a=$answers[$q]??'';if(is_string($a)&&isset($points[$a])){$total+=$points[$a];$answered++;$frequent=$frequent||$a==='C';}}
        $score=$answered>=2?$total/(2*$answered):null;
        return ['level'=>$score===null?'unknown':($frequent?'high':($total>0?'moderate':'low')),'score'=>$score===null?null:round($score,4),'answered'=>$answered,'frequent_reaction'=>$frequent,'validation_status'=>'Self-reported reaction flag; not an allergy test or validated sensitivity scale'];
    }
}
