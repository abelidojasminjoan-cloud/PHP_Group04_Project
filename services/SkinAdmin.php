<?php
declare(strict_types=1);
final class SkinAdmin
{
    private static function text(array $data,string $key,int $max,bool $required=false): string
    {
        $value=$data[$key]??'';
        if(!is_string($value)||!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>$max||($required&&trim($value)===''))throw new InvalidArgumentException('Please enter a valid '.str_replace('_',' ',$key).'.');
        return trim($value);
    }
    private static function audit(mysqli $conn,int $adminId,string $module,string $action,int $id): void
    {
        $description=ucfirst(strtolower($action)).' '.$module.' record.';$record=(string)$id;$ip=filter_var($_SERVER['REMOTE_ADDR']??'',FILTER_VALIDATE_IP)?:null;
        $s=$conn->prepare('INSERT INTO audit_logs(user_id,action,module,record_id,description,ip_address) VALUES(?,?,?,?,?,?)');$s->bind_param('isssss',$adminId,$action,$module,$record,$description,$ip);$s->execute();
    }
    public static function type(mysqli $conn,int $adminId,array $data,bool $delete=false): int
    {
        $id=filter_var($data['id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);if($id===false||($delete&&!$id))throw new InvalidArgumentException('Invalid skin type.');
        $conn->begin_transaction();
        try{
            $old=null;if($id){$s=$conn->prepare('SELECT * FROM skin_types WHERE id=? FOR UPDATE');$s->bind_param('i',$id);$s->execute();$old=$s->get_result()->fetch_assoc();if(!$old)throw new InvalidArgumentException('This skin type no longer exists.');}
            if($delete){
                if($old['assessment_key'])throw new InvalidArgumentException('Primary quiz types cannot be deleted. Their labels and descriptions can be edited.');
                foreach(['product_skin_types'=>'skin_type_id','skin_profiles'=>'skin_type_id','skin_assessments'=>'skin_type_id'] as $table=>$column){
                    $s=$conn->prepare("SELECT 1 FROM $table WHERE $column=? LIMIT 1");$s->bind_param('i',$id);$s->execute();if($s->get_result()->num_rows)throw new InvalidArgumentException('This skin type is in use. Set it inactive instead of deleting it.');
                }
                $s=$conn->prepare('DELETE FROM skin_types WHERE id=?');$s->bind_param('i',$id);$s->execute();
            }else{
                $name=self::text($data,'name',100,true);$description=self::text($data,'description',5000);$status=$data['status']??null;
                if(!is_string($status)||!in_array($status,['active','inactive'],true))throw new InvalidArgumentException('Choose a valid status.');
                if($old&&$old['assessment_key']&&$status!=='active')throw new InvalidArgumentException('Primary quiz types must remain active so all four assessment results remain available.');
                if($id){$s=$conn->prepare('UPDATE skin_types SET skin_type_name=?,description=?,status=? WHERE id=?');$s->bind_param('sssi',$name,$description,$status,$id);}
                else{$s=$conn->prepare('INSERT INTO skin_types(skin_type_name,description,status) VALUES(?,?,?)');$s->bind_param('sss',$name,$description,$status);}
                $s->execute();if(!$id)$id=(int)$conn->insert_id;
            }
            self::audit($conn,$adminId,'Skin Types',$delete?'DELETE':($old?'UPDATE':'CREATE'),$id);$conn->commit();return $id;
        }catch(Throwable $e){$conn->rollback();throw $e;}
    }
    public static function tag(mysqli $conn,int $adminId,array $data,bool $delete=false): int
    {
        $id=filter_var($data['id']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);if($id===false||($delete&&!$id))throw new InvalidArgumentException('Invalid suitability tag.');
        $conn->begin_transaction();
        try{
            if($id){$s=$conn->prepare('SELECT id FROM suitability_tags WHERE id=? FOR UPDATE');$s->bind_param('i',$id);$s->execute();if(!$s->get_result()->num_rows)throw new InvalidArgumentException('This suitability tag no longer exists.');}
            if($delete){
                $s=$conn->prepare('SELECT 1 FROM product_suitability_tags WHERE suitability_tag_id=? LIMIT 1');$s->bind_param('i',$id);$s->execute();if($s->get_result()->num_rows)throw new InvalidArgumentException('This suitability tag is assigned to products. Set it inactive or remove its assignments before deleting it.');
                $s=$conn->prepare('DELETE FROM suitability_tags WHERE id=?');$s->bind_param('i',$id);$s->execute();
            }else{
                $name=self::text($data,'name',100,true);$description=self::text($data,'description',5000);$status=$data['status']??null;
                if(!is_string($status)||!in_array($status,['active','inactive'],true))throw new InvalidArgumentException('Choose a valid suitability status.');
                if($id){$s=$conn->prepare('UPDATE suitability_tags SET name=?,description=?,status=? WHERE id=?');$s->bind_param('sssi',$name,$description,$status,$id);}
                else{$s=$conn->prepare('INSERT INTO suitability_tags(name,description,status) VALUES(?,?,?)');$s->bind_param('sss',$name,$description,$status);}
                $s->execute();if(!$id)$id=(int)$conn->insert_id;
            }
            self::audit($conn,$adminId,'Suitability Tags',$delete?'DELETE':((int)($data['id']??0)?'UPDATE':'CREATE'),$id);$conn->commit();return $id;
        }catch(Throwable $e){$conn->rollback();throw $e;}
    }
    public static function suitability(mysqli $conn,int $productId): ?array
    {
        $s=$conn->prepare('SELECT p.id,p.product_name,COALESCE(s.ingredients_complete,0) AS ingredients_complete,COALESCE(s.routine_role,\'none\') AS routine_role,COALESCE(s.usage_period,\'both\') AS usage_period,COALESCE(s.routine_instructions,\'\') AS routine_instructions,COALESCE(s.gentle,0) AS gentle,COALESCE(s.targeted_approved,0) AS targeted_approved,COALESCE(s.sunscreen_verified,0) AS sunscreen_verified,COALESCE(s.revision,0) AS revision FROM products p LEFT JOIN product_suitability s ON s.product_id=p.id WHERE p.id=?');
        $s->bind_param('i',$productId);$s->execute();$r=$s->get_result()->fetch_assoc();if(!$r)return null;
        foreach(['product_skin_types'=>'skin_type_id','product_concerns'=>'concern_id','product_ingredients'=>'ingredient_id'] as $table=>$column){$s=$conn->prepare("SELECT $column AS id FROM $table WHERE product_id=?");$s->bind_param('i',$productId);$s->execute();$r[$table]=array_map('intval',array_column($s->get_result()->fetch_all(MYSQLI_ASSOC),'id'));}
        return $r;
    }
    public static function saveSuitability(mysqli $conn,int $adminId,array $data): void
    {
        $id=filter_var($data['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$revision=filter_var($data['revision']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        if(!$id||$revision===false)throw new InvalidArgumentException('Invalid product selection.');
        $role=$data['routine_role']??null;$period=$data['usage_period']??null;
        if(!is_string($role)||!in_array($role,['none','cleanser','moisturizer','sunscreen','treatment'],true)||!is_string($period)||!in_array($period,['am','pm','both'],true))throw new InvalidArgumentException('Choose valid routine settings.');
        $instructions=self::text($data,'routine_instructions',2000);
        $flags=[];foreach(['ingredients_complete','gentle','targeted_approved','sunscreen_verified'] as $key){$v=$data[$key]??false;if(!is_bool($v))throw new InvalidArgumentException('Invalid suitability setting.');$flags[$key]=(int)$v;}
        if($flags['targeted_approved']&&($role!=='treatment'||$instructions===''))throw new InvalidArgumentException('An approved targeted product must have a treatment role and usage directions.');
        if($flags['sunscreen_verified']&&$role!=='sunscreen')throw new InvalidArgumentException('Sunscreen verification is only available for the sunscreen role.');
        $conn->begin_transaction();
        try{
            $s=$conn->prepare('SELECT id FROM products WHERE id=? FOR UPDATE');$s->bind_param('i',$id);$s->execute();if(!$s->get_result()->num_rows)throw new InvalidArgumentException('This product no longer exists.');
            $old=self::suitability($conn,$id);if((int)$old['revision']!==$revision)throw new DomainException('This suitability record changed. Close and reopen it before saving.');
            foreach(['product_skin_types'=>['skin_type_id','skin_types'],'product_concerns'=>['concern_id','skin_concerns'],'product_ingredients'=>['ingredient_id','ingredients']] as $table=>[$column,$catalog]){
                $raw=$data[$table]??[];if(!is_array($raw)||count($raw)>500)throw new InvalidArgumentException('Invalid product associations.');
                $allowed=[];foreach($conn->query("SELECT id,status FROM $catalog")->fetch_all(MYSQLI_ASSOC) as $r)if($r['status']==='active'||in_array((int)$r['id'],$old[$table],true))$allowed[]=(int)$r['id'];
                $ids=[];foreach($raw as $v){if((!is_int($v)&&!is_string($v))||!ctype_digit((string)$v)||!in_array((int)$v,$allowed,true))throw new InvalidArgumentException('Select available product associations.');$ids[]=(int)$v;}$ids=array_unique($ids);
                if($table==='product_ingredients'&&$flags['ingredients_complete']&&!$ids)throw new InvalidArgumentException('Record ingredients before confirming a complete list.');
                $s=$conn->prepare("DELETE FROM $table WHERE product_id=?");$s->bind_param('i',$id);$s->execute();$s=$conn->prepare("INSERT INTO $table(product_id,$column) VALUES(?,?)");foreach($ids as $v){$s->bind_param('ii',$id,$v);$s->execute();}
            }
            $complete=$flags['ingredients_complete'];$gentle=$flags['gentle'];$targeted=$flags['targeted_approved'];$sunscreen=$flags['sunscreen_verified'];
            $s=$conn->prepare('INSERT INTO product_suitability(product_id,ingredients_complete,routine_role,usage_period,routine_instructions,gentle,targeted_approved,sunscreen_verified) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE ingredients_complete=VALUES(ingredients_complete),routine_role=VALUES(routine_role),usage_period=VALUES(usage_period),routine_instructions=VALUES(routine_instructions),gentle=VALUES(gentle),targeted_approved=VALUES(targeted_approved),sunscreen_verified=VALUES(sunscreen_verified),revision=revision+1');
            $s->bind_param('iisssiii',$id,$complete,$role,$period,$instructions,$gentle,$targeted,$sunscreen);$s->execute();self::audit($conn,$adminId,'Product Suitability','UPDATE',$id);$conn->commit();
        }catch(Throwable $e){$conn->rollback();throw $e;}
    }
}
