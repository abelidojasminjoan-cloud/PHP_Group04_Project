<?php
declare(strict_types=1);
if(!in_array(PHP_SAPI,['cli','cli-server'],true)){http_response_code(404);exit;}
session_set_save_handler(fn($p,$n)=>true,fn()=>true,fn($id)=>'',fn($id,$data)=>true,fn($id)=>true,fn($ttl)=>0);
require_once __DIR__.'/../../config/db.php';
// Temporary tables shadow every customer/catalog table read or changed by these tests.
foreach(['users','roles','audit_logs','skin_types','skin_concerns','ingredients','skin_profiles','skin_profile_concerns','user_avoided_ingredients','skin_assessments','categories','products','product_variants','product_skin_types','product_concerns','product_ingredients','product_images','product_benefits','product_suitability','suitability_tags','product_suitability_tags','carts','cart_items'] as $table){$conn->query('CREATE TEMPORARY TABLE skin_test_'.$table.' LIKE '.$table);$conn->query('ALTER TABLE skin_test_'.$table.' RENAME TO '.$table);}
$conn->query("INSERT INTO roles(id,role_name) VALUES(1,'Admin'),(2,'Staff'),(3,'Customer')");
$conn->query("INSERT INTO users(id,role_id,first_name,last_name,email,password,status) VALUES(1,1,'Test','Admin','admin@example.invalid','unused','active'),(2,2,'Test','Staff','staff@example.invalid','unused','active'),(3,3,'Maya','Green','skin@example.invalid','unused','active'),(4,3,'Other','Customer','other@example.invalid','unused','active')");
$conn->query("INSERT INTO skin_types(id,skin_type_name,assessment_key) VALUES(1,'Normal','normal'),(2,'Dry','dry'),(3,'Oily','oily'),(4,'Combination','combination'),(5,'Sensitive',NULL)");
$conn->query("INSERT INTO skin_concerns(id,concern_name) VALUES(1,'Acne'),(2,'Dryness'),(3,'Dullness'),(4,'Dark Spots')");
$conn->query("INSERT INTO ingredients(id,ingredient_name) VALUES(1,'Fragrance'),(2,'Parfum'),(3,'Glycerin'),(4,'Retinol')");
$conn->query("INSERT INTO categories(id,category_name) VALUES(1,'Cleansers'),(2,'Moisturizers'),(3,'Sunscreens'),(4,'Serums')");
$conn->query("INSERT INTO suitability_tags(id,name,description) VALUES(1,'Daily Use','Appropriate for consistent everyday routines.'),(2,'Beginner Friendly','Suitable for customers new to active skincare.'),(3,'Sensitive Skin Friendly','Formulated with reactive skin in mind.'),(4,'Pregnancy Friendly','Free from ingredients commonly avoided during pregnancy.'),(5,'Fragrance Free','Made without added fragrance.')");
$conn->query("INSERT INTO products(id,category_id,product_name,product_code,price,status) VALUES(101,1,'Test Cleanser','SKIN-101',20,'active'),(102,2,'Test Moisturizer','SKIN-102',30,'active'),(103,3,'Test Sunscreen','SKIN-103',40,'active'),(104,4,'Test Targeted Serum','SKIN-104',50,'active'),(105,4,'Fragrance Serum','SKIN-105',10,'active'),(106,4,'Unknown Ingredient Serum','SKIN-106',10,'active'),(107,4,'Inactive Serum','SKIN-107',10,'inactive'),(108,4,'Sold Out Serum','SKIN-108',10,'active')");
$conn->query("INSERT INTO product_variants(product_id,size_capacity,price,stock_quantity) SELECT id,'50ml',price,IF(id=108,0,10) FROM products");
$conn->query('INSERT INTO product_skin_types(product_id,skin_type_id) SELECT id,4 FROM products');
$conn->query('INSERT INTO product_concerns(product_id,concern_id) SELECT id,1 FROM products');
$conn->query('INSERT INTO product_concerns(product_id,concern_id) VALUES(104,2),(104,3),(104,4)');
$conn->query('INSERT INTO product_ingredients(product_id,ingredient_id) SELECT id,IF(id=105,2,3) FROM products WHERE id<>106');
$conn->query("INSERT INTO product_suitability(product_id,ingredients_complete,routine_role,usage_period,routine_instructions,gentle,targeted_approved,sunscreen_verified) SELECT id,IF(id=106,0,1),CASE id WHEN 101 THEN 'cleanser' WHEN 102 THEN 'moisturizer' WHEN 103 THEN 'sunscreen' WHEN 104 THEN 'treatment' ELSE 'none' END,'both','Follow the product label directions.',1,IF(id=104,1,0),IF(id=103,1,0) FROM products");
$_SESSION=['user_id'=>3,'role'=>'customer','name'=>'Maya Green','csrf_token'=>str_repeat('a',64),'last_activity'=>time(),'skin_assessment_tokens'=>[str_repeat('b',64)=>['expires'=>time()+3600,'origin'=>'quiz']]];
function skinTestData(array $override=[]):array{return array_replace(['assessment_method'=>'manual','skin_type'=>'combination','answers'=>['q9'=>'B','q10'=>'B','q11'=>'A'],'concerns'=>[1,2,3,4],'main_concern_id'=>1,'ingredient_preference'=>'selected','avoided_ingredients'=>[1],'avoidance_reason'=>'preference','routine_preference'=>'targeted'],$override);}
