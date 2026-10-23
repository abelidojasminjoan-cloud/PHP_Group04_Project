<?php
declare(strict_types=1);
require __DIR__.'/fixture.php';
require_once __DIR__.'/../../admin/products/product_data.php';
$case=$argv[1]??'edit';
$_SESSION['user_id']=1;$_SESSION['role']='admin';$_SESSION['csrf_product']=str_repeat('a',64);
$_SERVER['REQUEST_METHOD']='POST';$_SERVER['HTTP_ACCEPT']='application/json';
if(in_array($case,['inactive','retain-inactive'],true))$conn->query("UPDATE suitability_tags SET status='inactive' WHERE id=2");
if($case==='retain-inactive')$conn->query('INSERT INTO product_suitability_tags(product_id,suitability_tag_id) VALUES(101,2)');
if($case==='legacy')$conn->query('INSERT INTO product_suitability_tags(product_id,suitability_tag_id) VALUES(101,1)');
$p=productEditData($conn,101);$variantId=(int)$p['variants'][0]['id'];$before=ProductSuitability::read($conn,101);
$_POST=['csrf_token'=>str_repeat('a',64),'product_id'=>'101','product_revision'=>$p['revision'],'product_name'=>'Test Cleanser','product_code'=>'SKIN-101','category_id'=>'1','status'=>'active','description'=>'','key_benefits'=>'','size_capacity'=>['50ml'],'variant_label'=>[''],'variant_price'=>['20'],'variant_stock'=>['10'],'variant_threshold'=>['15'],'variant_id'=>[(string)$variantId],'skin_type_ids'=>['4'],'concern_ids'=>['1'],'ingredient_ids'=>['3'],'suitability_tags_present'=>'1','suitability_tag_ids'=>['1','2'],'routine_settings_present'=>'1','routine_role'=>'cleanser','usage_period'=>'am','routine_instructions'=>'Updated label directions.','ingredients_complete'=>'1','gentle'=>'1'];
if($case==='add'){$_POST['product_id']='';$_POST['product_revision']='';$_POST['product_name']='Added Routine Product';$_POST['product_code']='NEW-ROUTINE';$_POST['variant_id']=[''];}
if($case==='forged')$_POST['suitability_tag_ids']=['999'];
if($case==='invalid-role')$_POST['routine_role']='invalid';
if($case==='legacy'){unset($_POST['suitability_tags_present'],$_POST['suitability_tag_ids'],$_POST['routine_settings_present']);}
if($case==='csrf')$_POST['csrf_token']='wrong';
if($case==='stale'){$changed=$before;$changed['usage_period']='pm';ProductSuitability::save($conn,101,$changed);$before=ProductSuitability::read($conn,101);}
$expectSuccess=in_array($case,['edit','add','retain-inactive','legacy'],true);
ob_start();
register_shutdown_function(function()use($conn,$case,$expectSuccess,$variantId,$before){
    $response=json_decode(ob_get_clean(),true);$ok=is_array($response)&&$response['success']===$expectSuccess;
    $id=$case==='add'?(int)($conn->query("SELECT id FROM products WHERE product_code='NEW-ROUTINE'")->fetch_assoc()['id']??0):101;
    $settings=$id?ProductSuitability::read($conn,$id):[];$ids=$id?array_map('intval',array_column($conn->query('SELECT suitability_tag_id FROM product_suitability_tags WHERE product_id='.$id.' ORDER BY suitability_tag_id')->fetch_all(MYSQLI_ASSOC),'suitability_tag_id')):[];
    if($expectSuccess&&$case!=='legacy')$ok=$ok&&$ids===[1,2]&&$settings['routine_role']==='cleanser'&&$settings['usage_period']==='am'&&(int)$settings['ingredients_complete']===1;
    if($case==='legacy')$ok=$ok&&$ids===[1]&&$settings['usage_period']===$before['usage_period']&&(int)$settings['revision']===(int)$before['revision'];
    if(!$expectSuccess)$ok=$ok&&$ids===[]&&$settings['usage_period']===$before['usage_period']&&(int)$settings['revision']===(int)$before['revision'];
    if($case!=='add')$ok=$ok&&(int)$conn->query('SELECT id FROM product_variants WHERE product_id=101')->fetch_assoc()['id']===$variantId;
    if(!$ok){fwrite(STDERR,json_encode(['case'=>$case,'response'=>$response,'settings'=>$settings,'ids'=>$ids]));exit(1);}
    echo "Product form $case passed with temporary tables.\n";
});
require __DIR__.'/../../admin/products/add_product.php';
