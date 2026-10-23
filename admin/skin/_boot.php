<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/role-check.php';
requirePureViaAdmin($conn);
require_once __DIR__.'/../../includes/csrf.php';
header('Cache-Control: no-store');
function saEsc(mixed $value):string{return pvHtml((string)$value,ENT_QUOTES,'UTF-8');}
