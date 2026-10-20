<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
