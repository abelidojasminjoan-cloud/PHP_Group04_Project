<?php
declare(strict_types=1);
/** Configurable shopping-guide rules. Weights express project rules, not clinical accuracy. */
final class QuestionScoring
{
    public const TYPES=['normal','dry','oily','combination'];
    public const KINDS=['skin_type','sensitivity','clarification','observation'];
    public static function legacy(array $row): array
    {
        $key=$row['question_key'];$kind=preg_match('/^q[2-8]$/D',$key)?'skin_type':(in_array($key,['q9','q10','q11'],true)?'sensitivity':(in_array($key,['f1','f2'],true)?'clarification':'observation'));
        $scores=[];$options=json_decode($row['options_json'],true)?:[];
        foreach($options as $index=>$option){$letter=chr(65+$index);$scores[$letter]=[];
            if(in_array($kind,['skin_type','clarification'],true)&&isset(['dry','normal','oily','combination'][$index]))$scores[$letter]=[['dry','normal','oily','combination'][$index]=>1];
            if($kind==='sensitivity'&&$index<3)$scores[$letter]=['sensitivity'=>$index];
        }
        return ['question_kind'=>$kind,'answer_scores'=>$scores];
    }
    public static function normalize(array $row): array
    {
        $legacy=self::legacy($row);
        $row['question_kind']=$row['question_kind']??$legacy['question_kind'];
        $row['answer_scores']=isset($row['answer_scores_json'])?json_decode($row['answer_scores_json'],true):$legacy['answer_scores'];
        $row['answer_scores']=is_array($row['answer_scores'])?$row['answer_scores']:[];
        $row['concern_ids']=isset($row['concern_ids_json'])?(json_decode($row['concern_ids_json'],true)?:[]):(!empty($row['concern_id'])?[(int)$row['concern_id']]:[]);
        $row['skin_type_ids']=json_decode($row['skin_type_ids_json']??'[]',true)?:[];
        $row['concern_answer']=$row['concern_answer']??'A';
        return $row;
    }
    public static function assess(array $answers,array $bank): array
    {
        $scores=array_fill_keys(self::TYPES,0);$answered=$followups=0;$primary=[];$clarifications=[];$observations=[];$followupWinners=[];
        foreach($bank as $raw){$q=self::normalize($raw);if(($q['status']??'active')!=='active')continue;if($q['question_kind']==='skin_type')$primary[]=$q;if($q['question_kind']==='clarification')$clarifications[]=$q;}
        foreach(array_merge($primary,$clarifications) as $q){
            $answer=$answers[$q['question_key']]??'';$weights=is_string($answer)?($q['answer_scores'][$answer]??[]):[];
            $votes=array_intersect_key($weights,$scores);$total=array_sum($votes);if($total<=0)continue;
            foreach($votes as $type=>$weight)$scores[$type]+=(int)$weight;
            arsort($votes);$winner=array_key_first($votes);$rank=array_values($votes);$uniqueWinner=count($rank)===1||$rank[0]>$rank[1];
            if($q['question_kind']==='skin_type'){$answered++;if($uniqueWinner)$observations[]=$winner;}
            else{$followups++;if($uniqueWinner)$followupWinners[]=$winner;}
        }
        arsort($scores);$values=array_values($scores);$margin=$values[0]-$values[1];$minimum=min(5,count($primary));
        $requiredMargin=count($primary)<3?1:2;
        $conflict=in_array('dry',$observations,true)&&in_array('oily',$observations,true);
        $followupConflict=count(array_unique($followupWinners))>1;
        $followupDisagrees=$followups===count($clarifications)&&$followups>0&&count($followupWinners)===$followups&&in_array(false,array_map(fn($winner)=>$winner===array_key_first($scores),$followupWinners),true);
        $reason=(!$primary||$answered<$minimum)?'insufficient':($followupConflict||$followupDisagrees?'conflicting':($margin<$requiredMargin?'close':($values[0]<=array_sum($scores)/2?'inconclusive':($conflict&&$followups<count($clarifications)?'conflicting':null))));
        return ['status'=>$reason?'unclear':'suggested','reason'=>$reason,'skin_type'=>$reason?null:array_key_first($scores),'scores'=>$scores,'answered'=>$answered,'margin'=>$margin,'followups_answered'=>$followups,
            'needs_followup'=>$reason!==null&&$reason!=='insufficient'&&$followups<count($clarifications),'rule_version'=>'configured-question-bank-v1',
            'validation_status'=>'Research-informed custom rules; not clinically validated',
            'explanation'=>!$primary?'No active skin-type scoring questions are configured. Choose your skin type manually.':'Answers use the administrator-configured weights. At least '.$minimum.' non-skipped scoring observations, a majority of awarded points and a lead of '.$requiredMargin.' points are required. Scores are not probabilities.'];
    }
    public static function sensitivity(array $answers,array $bank): array
    {
        $total=$answered=$available=0;$frequent=false;
        foreach($bank as $raw){$q=self::normalize($raw);if(($q['status']??'active')!=='active'||$q['question_kind']!=='sensitivity')continue;$available++;
            $answer=$answers[$q['question_key']]??'';$weights=is_string($answer)?($q['answer_scores'][$answer]??[]):[];
            if(!array_key_exists('sensitivity',$weights))continue;$points=(int)$weights['sensitivity'];$total+=$points;$answered++;$frequent=$frequent||$points>=2;
        }
        $score=$available>0&&$answered>=min(2,$available)?$total/(2*$answered):null;
        return ['level'=>$score===null?'unknown':($frequent?'high':($total>0?'moderate':'low')),'score'=>$score===null?null:round($score,4),'answered'=>$answered,'frequent_reaction'=>$frequent,'validation_status'=>'Self-reported reaction flag; not a validated sensitivity scale'];
    }
}