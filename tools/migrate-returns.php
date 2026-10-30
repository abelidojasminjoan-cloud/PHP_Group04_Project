<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/db.php';
$columns = array_column($conn->query('SHOW COLUMNS FROM order_return_requests')->fetch_all(MYSQLI_ASSOC), 'Field');
$definitions = [
 'return_method'=>"enum('courier','dropoff') NOT NULL DEFAULT 'courier'",
 'evidence_json'=>'text DEFAULT NULL', 'internal_note'=>'text DEFAULT NULL',
 'resolution_reference'=>'varchar(200) DEFAULT NULL', 'revision'=>'int unsigned NOT NULL DEFAULT 0',
 'restocked_at'=>'datetime DEFAULT NULL', 'reviewed_by'=>'int unsigned DEFAULT NULL',
 'updated_at'=>'timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()',
];
foreach ($definitions as $name=>$definition) {
 if (!in_array($name, $columns, true)) $conn->query('ALTER TABLE order_return_requests ADD COLUMN '.$name.' '.$definition);
}
echo "Return-management schema is up to date. Existing requests preserved.\n";