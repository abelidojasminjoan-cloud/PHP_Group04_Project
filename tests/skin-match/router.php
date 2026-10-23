<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli-server'){http_response_code(404);exit;}
$root=dirname(__DIR__,2);$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(str_starts_with($path,'/purevia_website/assets/')){
    $file=realpath($root.substr($path,strlen('/purevia_website')));
    if(!$file||!str_starts_with($file,$root.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR)||!is_file($file)){http_response_code(404);exit;}
    $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));header('Content-Type: '.(['css'=>'text/css','js'=>'text/javascript','svg'=>'image/svg+xml','png'=>'image/png','jpg'=>'image/jpeg'][$ext]??'application/octet-stream'));readfile($file);exit;
}
if(str_contains($path,'session-status.php')){header('Content-Type: application/json');echo '{"authenticated":true,"remaining_seconds":1800}';exit;}
$stateFile=getenv('PUREVIA_SKIN_TEST_STATE');
if(!$stateFile||!str_starts_with($stateFile,$root.DIRECTORY_SEPARATOR.'purevia-skin-test-')){http_response_code(500);exit('Missing isolated test state.');}
require __DIR__.'/fixture.php';require_once $root.'/services/SkinProfile.php';
$tables=['skin_profiles','skin_profile_concerns','user_avoided_ingredients','skin_assessments','skin_types','product_suitability','product_skin_types','product_concerns','product_ingredients','audit_logs','suitability_tags','product_suitability_tags','products','product_variants','product_images','product_benefits'];
if(is_file($stateFile)){
    $state=json_decode(file_get_contents($stateFile),true,32,JSON_THROW_ON_ERROR);
    foreach($tables as $table){$conn->query('DELETE FROM '.$table);foreach($state['tables'][$table] as $row){$columns=array_keys($row);$s=$conn->prepare('INSERT INTO '.$table.' (`'.implode('`,`',$columns).'`) VALUES('.implode(',',array_fill(0,count($columns),'?')).')');$values=array_values($row);$types=str_repeat('s',count($values));$s->bind_param($types,...$values);$s->execute();}}
    $_SESSION['skin_assessment_tokens']=$state['tokens'];
}else{
    $p=SkinProfile::validate($conn,skinTestData());SkinProfile::save($conn,3,$p,str_repeat('c',64),'quiz');
    $guided=skinTestData(['assessment_method'=>'guided','answers'=>array_fill_keys(array_map(fn($q)=>'q'.$q,range(2,8)),'D')+['q9'=>'A','q10'=>'A','q11'=>'A']]);SkinProfile::save($conn,4,SkinProfile::validate($conn,$guided),str_repeat('d',64),'quiz');
    $ambiguous=skinTestData(['assessment_method'=>'guided','answers'=>array_combine(array_map(fn($q)=>'q'.$q,range(2,8)),str_split('AABBCCE'))+['f1'=>'A','f2'=>'B','q9'=>'D','q10'=>'D','q11'=>'D']]);SkinProfile::save($conn,3,SkinProfile::validate($conn,$ambiguous),str_repeat('f',64),'quiz');
    SkinProfile::save($conn,3,$p,str_repeat('e',64),'profile_edit');$conn->query("UPDATE skin_assessments SET created_at='2026-10-21 10:00:00'");$conn->query('UPDATE skin_assessments SET usefulness=4 WHERE id=1');
}
register_shutdown_function(function()use($conn,$tables,$stateFile){$state=['tokens'=>$_SESSION['skin_assessment_tokens'],'tables'=>[]];foreach($tables as $table)$state['tables'][$table]=$conn->query('SELECT * FROM '.$table)->fetch_all(MYSQLI_ASSOC);file_put_contents($stateFile,json_encode($state,JSON_THROW_ON_ERROR));});
$who=$_SERVER['HTTP_X_SKIN_TEST_USER']??(str_contains($path,'/admin/')?'admin':(isset($_GET['guest'])?'guest':'customer'));
if($who==='guest'){unset($_SESSION['user_id']);$_SESSION['role']='guest';}
elseif($who==='admin'){$_SESSION['user_id']=1;$_SESSION['role']='admin';}
elseif($who==='staff'){$_SESSION['user_id']=2;$_SESSION['role']='staff';}
elseif($who==='other'){$_SESSION['user_id']=4;}
elseif($who==='inactive')$conn->query("UPDATE users SET status='inactive' WHERE id=3");
elseif($who==='locked')$conn->query("UPDATE users SET status='locked' WHERE id=3");
$_SERVER['PHP_SELF']=$path;
$_SESSION['csrf_product']=str_repeat('a',64);
if(str_ends_with($path,'/actions/skin/profile.php'))require $root.'/actions/skin/profile.php';
elseif(str_ends_with($path,'/actions/admin/skin.php'))require $root.'/actions/admin/skin.php';
elseif(str_contains($path,'/admin/skin-types/'))require $root.'/admin/skin-types/skin-types.php';
elseif(str_contains($path,'/admin/suitability/'))require $root.'/admin/suitability/suitability.php';
elseif(str_ends_with($path,'/admin/products/edit_product.php'))require $root.'/admin/products/edit_product.php';
elseif(str_ends_with($path,'/admin/products/add_product.php'))require $root.'/admin/products/add_product.php';
elseif(str_ends_with($path,'/admin/products/products.php'))require $root.'/admin/products/products.php';
elseif(str_contains($path,'/admin/quiz-analytics/'))require $root.'/admin/quiz-analytics/quiz-analytics.php';
elseif(str_contains($path,'/account/skin-profile.php'))require $root.'/account/skin-profile.php';
else{if(str_ends_with($path,'/skin-type-finder.php'))$_GET['path']='guided';require $root.'/public/skin-match.php';}
