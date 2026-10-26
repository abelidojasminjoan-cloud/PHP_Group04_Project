<?php
declare(strict_types=1);
/** Product-form persistence; the caller owns the product save transaction and row lock. */
final class ProductSuitability
{
    public static function read(mysqli $conn,int $productId): array
    {
        $s=$conn->prepare('SELECT ingredients_complete,routine_role,usage_period,routine_instructions,gentle,targeted_approved,sunscreen_verified,revision FROM product_suitability WHERE product_id=?');
        $s->bind_param('i',$productId);$s->execute();
        return $s->get_result()->fetch_assoc()?:['ingredients_complete'=>0,'routine_role'=>'none','usage_period'=>'both','routine_instructions'=>'','gentle'=>0,'targeted_approved'=>0,'sunscreen_verified'=>0,'revision'=>0];
    }
    public static function fromForm(array $data,array $ingredientIds): ?array
    {
        if(!isset($data['routine_settings_present']))return null;
        if($data['routine_settings_present']!=='1')throw new RuntimeException('Invalid product routine settings.');
        $role=$data['routine_role']??null;$period=$data['usage_period']??null;
        if(!is_string($role)||!in_array($role,['none','cleanser','moisturizer','sunscreen','treatment'],true)||!is_string($period)||!in_array($period,['both','am','pm'],true))throw new RuntimeException('Choose a valid Routine Role and Usage Period.');
        $instructions=$data['routine_instructions']??'';
        if(!is_string($instructions)||!mb_check_encoding($instructions,'UTF-8')||mb_strlen($instructions)>2000)throw new RuntimeException('Product label directions must be 2,000 characters or fewer.');
        $result=['routine_role'=>$role,'usage_period'=>$period,'routine_instructions'=>trim($instructions)];
        foreach(['ingredients_complete','gentle','targeted_approved','sunscreen_verified'] as $flag){$v=$data[$flag]??'0';if(!is_string($v)||!in_array($v,['0','1'],true))throw new RuntimeException('Invalid product suitability setting.');$result[$flag]=(int)$v;}
        if($result['ingredients_complete']&&!$ingredientIds)throw new RuntimeException('Record all product ingredients before confirming the list is complete.');
        if($result['targeted_approved']&&($role!=='treatment'||$result['routine_instructions']===''))throw new RuntimeException('Approved targeted products require a treatment role and product label directions.');
        if($result['sunscreen_verified']&&$role!=='sunscreen')throw new RuntimeException('Sunscreen verification requires the sunscreen routine role.');
        return $result;
    }
    public static function save(mysqli $conn,int $productId,array $settings): void
    {
        $complete=$settings['ingredients_complete'];$role=$settings['routine_role'];$period=$settings['usage_period'];$instructions=$settings['routine_instructions'];$gentle=$settings['gentle'];$targeted=$settings['targeted_approved'];$sunscreen=$settings['sunscreen_verified'];
        $s=$conn->prepare('INSERT INTO product_suitability(product_id,ingredients_complete,routine_role,usage_period,routine_instructions,gentle,targeted_approved,sunscreen_verified) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE ingredients_complete=VALUES(ingredients_complete),routine_role=VALUES(routine_role),usage_period=VALUES(usage_period),routine_instructions=VALUES(routine_instructions),gentle=VALUES(gentle),targeted_approved=VALUES(targeted_approved),sunscreen_verified=VALUES(sunscreen_verified),revision=revision+1');
        $s->bind_param('iisssiii',$productId,$complete,$role,$period,$instructions,$gentle,$targeted,$sunscreen);$s->execute();
    }
}
