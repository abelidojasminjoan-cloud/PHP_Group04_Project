<?php
declare(strict_types=1);
/** PureVia project scoring rules; an informational estimate, not a diagnostic scale. */
final class SkinTypeFinder
{
    public const TYPES=['normal','dry','oily','combination'];
    private const OPTIONS=['A'=>'dry','B'=>'normal','C'=>'oily','D'=>'combination'];
    private const WEIGHTS=[2=>[2,2,2,2],3=>[2,2,2,3],4=>[2,2,2,3],5=>[2,2,2,3],6=>[2,1,1,2],7=>[2,2,2,3],8=>[1,1,3,3]];
    public static function assess(array $answers): array
    {
        $scores=array_fill_keys(self::TYPES,0); $answered=$followups=0;
        foreach(self::WEIGHTS as $q=>$weights){
            $a=$answers['q'.$q]??'';
            if(!is_string($a)||!isset(self::OPTIONS[$a]))continue;
            $scores[self::OPTIONS[$a]]+=$weights[ord($a)-65]; $answered++;
        }
        foreach(['f1','f2'] as $q){$a=$answers[$q]??'';if(is_string($a)&&isset(self::OPTIONS[$a])){$scores[self::OPTIONS[$a]]+=3;$followups++;}}
        arsort($scores);$values=array_values($scores);$margin=$values[0]-$values[1];
        $conflict=in_array([$answers['q4']??'', $answers['q5']??''],[['C','D'],['D','C']],true)||in_array([$answers['q2']??'', $answers['q7']??''],[['A','C'],['C','A']],true);
        $reason=$answered<5?'insufficient':($margin<=2?'close':($values[0]<7?'inconclusive':($conflict&&$followups<2?'conflicting':null)));
        return ['status'=>$reason?'unclear':'suggested','reason'=>$reason,'skin_type'=>$reason?null:array_key_first($scores),'scores'=>$scores,'answered'=>$answered,'margin'=>$margin,'followups_answered'=>$followups,'needs_followup'=>$reason!==null&&$answered>=5&&$followups<2];
    }
    public static function sensitivity(array $answers): array
    {
        $total=$answered=0;$frequent=false;$points=['A'=>0,'B'=>1,'C'=>2];
        foreach(['q9','q10','q11'] as $q){$a=$answers[$q]??'';if(is_string($a)&&isset($points[$a])){$total+=$points[$a];$answered++;$frequent=$frequent||$a==='C';}}
        $score=$answered>=2?$total/(2*$answered):null;
        return ['level'=>$score===null?'unknown':($score<=.25?'low':($score<=.65?'moderate':'high')),'score'=>$score===null?null:round($score,4),'answered'=>$answered,'frequent_reaction'=>$frequent];
    }
}
