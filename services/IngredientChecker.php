<?php
declare(strict_types=1);
final class IngredientChecker
{
    private static function canonical(string $name): string
    { $name=mb_strtolower(trim($name));return in_array($name,['fragrance','parfum','perfume','fragrance / parfum'],true)?'fragrance':$name; }
    public static function check(array $ids, bool $complete, array $avoided, array $names): array
    {
        $avoidNames=[];foreach($avoided as $id)if(isset($names[$id]))$avoidNames[]=self::canonical($names[$id]);
        $conflicts=[];foreach($ids as $id)if(in_array($id,$avoided,true)||(isset($names[$id])&&in_array(self::canonical($names[$id]),$avoidNames,true)))$conflicts[]=$names[$id]??'Selected ingredient';
        $complete=$complete&&(bool)$ids;
        return ['eligible'=>!$conflicts&&(!$avoided||$complete),'status'=>$conflicts?'contains_avoided':($complete?'screened':'unverified'),'conflicts'=>array_values(array_unique($conflicts))];
    }
}
