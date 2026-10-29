<?php
declare(strict_types=1);
require_once __DIR__.'/IngredientChecker.php';
require_once __DIR__.'/SkinEvidence.php';
final class SkinMatcher
{
    public static function match(mysqli $conn,array $profile): array
    {
        $names=[];foreach($conn->query('SELECT id,ingredient_name FROM ingredients')->fetch_all(MYSQLI_ASSOC) as $r)$names[(int)$r['id']]=$r['ingredient_name'];
        $links=[];
        foreach(['product_skin_types'=>'skin_type_id','product_concerns'=>'concern_id','product_ingredients'=>'ingredient_id'] as $t=>$c)
            foreach($conn->query("SELECT product_id,$c AS linked_id FROM $t")->fetch_all(MYSQLI_ASSOC) as $r)$links[$t][(int)$r['product_id']][]=(int)$r['linked_id'];
        $rows=$conn->query("SELECT p.id,p.product_name,p.image,c.category_name,v.price,s.ingredients_complete,s.routine_role,s.usage_period,s.routine_instructions,s.gentle,s.targeted_approved,s.sunscreen_verified,
            (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) AS primary_image
            FROM products p JOIN categories c ON c.id=p.category_id
            JOIN product_variants v ON v.id=(SELECT id FROM product_variants WHERE product_id=p.id AND stock_quantity>0 ORDER BY price,id LIMIT 1)
            LEFT JOIN product_suitability s ON s.product_id=p.id WHERE p.status='active' AND c.status='active' ORDER BY p.id")->fetch_all(MYSQLI_ASSOC);
        $products=[];$excluded=0;
        foreach($rows as $r){
            $id=(int)$r['id'];$skins=$links['product_skin_types'][$id]??[];$concerns=$links['product_concerns'][$id]??[];
            // A concern match must not override an explicit, incompatible catalog skin-type assignment.
            if($profile['skin_type_id']&&$skins&&!in_array($profile['skin_type_id'],$skins,true)){$excluded++;continue;}
            $check=IngredientChecker::check($links['product_ingredients'][$id]??[],(bool)$r['ingredients_complete'],$profile['avoided_ingredients'],$names);
            if(!$check['eligible']){$excluded++;continue;}
            $score=0;$reasons=[];
            if($profile['skin_type_id']&&in_array($profile['skin_type_id'],$skins,true)){$score+=40;$reasons[]='Listed for '.$profile['skin_type_label'].' skin';}
            $matched=array_intersect($profile['concerns'],$concerns);$main=$profile['main_concern_id'];
            if($main&&in_array($main,$matched,true)){$score+=30;$reasons[]='Targets your main concern';}
            $other=count(array_diff($matched,$main?[$main]:[]));$score+=min(20,$other*10);if($other)$reasons[]='Targets other selected concerns';
            if(!$score&&($profile['skin_type_id']||$profile['concerns']))continue;
            $path=(string)($r['primary_image']?:$r['image']);$image=BASE_URL.'/assets/images/products/default-product.svg';
            if(filter_var($path,FILTER_VALIDATE_URL)&&in_array(strtolower((string)parse_url($path,PHP_URL_SCHEME)),['http','https'],true))$image=$path;
            elseif($path&&is_file(__DIR__.'/../uploads/products/'.basename(str_replace('\\','/',$path))))$image=BASE_URL.'/uploads/products/'.rawurlencode(basename(str_replace('\\','/',$path)));
            $products[]=['id'=>$id,'name'=>$r['product_name'],'image'=>$image,'price'=>(float)$r['price'],'category'=>$r['category_name'],'score'=>$score,'reasons'=>$reasons,'ingredient_status'=>$check['status'],
                'role'=>$r['routine_role']??'none','period'=>$r['usage_period']??'both','instructions'=>$r['routine_instructions']??'','gentle'=>(bool)$r['gentle'],'targeted_approved'=>(bool)$r['targeted_approved'],'sunscreen_verified'=>(bool)$r['sunscreen_verified'],'concerns'=>$concerns];
        }
        usort($products,fn($a,$b)=>($b['score']<=>$a['score'])?:($a['id']<=>$b['id']));
        return ['products'=>array_slice($products,0,12),'routine'=>self::routine($products,$profile),'excluded_count'=>$excluded,'guidance'=>SkinEvidence::guidance($profile)];
    }
    private static function routine(array $products,array $profile): array
    {
        $sensitive=$profile['sensitivity']['level']==='high'||$profile['sensitivity']['frequent_reaction'];
        if($sensitive)usort($products,fn($a,$b)=>($b['gentle']<=>$a['gentle'])?:($b['score']<=>$a['score']));
        $pick=static function(string $role,string $period)use($products,$sensitive):?array{
            foreach($products as $p){
                if($p['role']!==$role||!in_array($p['period'],[$period,'both'],true))continue;
                if($role==='sunscreen'&&!$p['sunscreen_verified'])continue;
                if($sensitive&&!$p['gentle'])continue;
                return ['id'=>$p['id'],'name'=>$p['name'],'instructions'=>$p['instructions']];
            }return null;
        };
        $routine=['am'=>[],'pm'=>[],'targeted'=>null];
        foreach(['am'=>['cleanser','moisturizer','sunscreen'],'pm'=>['cleanser','moisturizer']] as $period=>$roles)
            foreach($roles as $role)$routine[$period][]=['step'=>ucfirst($role),'product'=>$pick($role,$period)];
        if(!$sensitive&&$profile['routine_preference']==='targeted'&&$profile['main_concern_id'])
            foreach($products as $p)if($p['role']==='treatment'&&$p['targeted_approved']&&trim($p['instructions'])!==''&&in_array($profile['main_concern_id'],$p['concerns'],true)){
                $routine['targeted']=['id'=>$p['id'],'name'=>$p['name'],'instructions'=>$p['instructions']];break;
            }
        return $routine;
    }
}
