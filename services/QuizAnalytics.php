<?php
declare(strict_types=1);
final class QuizAnalytics
{
    public static function dates(array $query): array
    {
        $from=$query['from']??date('Y-m-01');$to=$query['to']??date('Y-m-t');
        foreach([$from,$to] as $value){$d=is_string($value)?DateTimeImmutable::createFromFormat('!Y-m-d',$value):false;if(!$d||$d->format('Y-m-d')!==$value)throw new InvalidArgumentException('Choose a valid date range.');}
        if($from>$to)throw new InvalidArgumentException('The start date must be before the end date.');
        return [$from,$to,(new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d')];
    }
    public static function report(mysqli $conn,string $from,string $exclusiveTo,int $page=1,int $perPage=10): array
    {
        $s=$conn->prepare("SELECT COUNT(*) AS total,COALESCE(SUM(assessment_method='guided'),0) AS guided,COALESCE(SUM(assessment_method='manual'),0) AS manual,COALESCE(SUM(assessment_method='guided' AND clarity='clear'),0) AS clear_count,COALESCE(SUM(assessment_method='guided' AND clarity='ambiguous'),0) AS ambiguous,COALESCE(SUM(assessment_method='guided' AND clarity='insufficient'),0) AS insufficient,AVG(usefulness) AS average_usefulness,COUNT(usefulness) AS rating_count FROM skin_assessments WHERE origin='quiz' AND created_at>=? AND created_at<?");$s->bind_param('ss',$from,$exclusiveTo);$s->execute();$totals=$s->get_result()->fetch_assoc();
        foreach(['total','guided','manual','clear_count','ambiguous','insufficient','rating_count'] as $key)$totals[$key]=(int)$totals[$key];
        $s=$conn->prepare("SELECT t.assessment_key,COUNT(*) AS count FROM skin_assessments a JOIN skin_types t ON t.id=a.skin_type_id WHERE a.origin='quiz' AND a.assessment_method='guided' AND a.created_at>=? AND a.created_at<? GROUP BY t.assessment_key");$s->bind_param('ss',$from,$exclusiveTo);$s->execute();$types=array_fill_keys(['normal','dry','oily','combination'],0);foreach($s->get_result()->fetch_all(MYSQLI_ASSOC) as $r)if(isset($types[$r['assessment_key']]))$types[$r['assessment_key']]=(int)$r['count'];
        $perPage=max(1,min(50,$perPage));$pages=max(1,(int)ceil($totals['total']/$perPage));$page=max(1,min($pages,$page));$offset=($page-1)*$perPage;
        $s=$conn->prepare("SELECT a.id,a.created_at,a.assessment_method,a.skin_type_label,a.clarity,TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) AS customer FROM skin_assessments a LEFT JOIN users u ON u.id=a.user_id WHERE a.origin='quiz' AND a.created_at>=? AND a.created_at<? ORDER BY a.created_at DESC,a.id DESC LIMIT ? OFFSET ?");$s->bind_param('ssii',$from,$exclusiveTo,$perPage,$offset);$s->execute();
        return ['totals'=>$totals,'types'=>$types,'rows'=>$s->get_result()->fetch_all(MYSQLI_ASSOC),'page'=>$page,'pages'=>$pages];
    }
}
