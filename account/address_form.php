<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
) ?: 0;

$url = BASE_URL . '/account/addresses.php';

$url .= $id > 0
    ? '?id=' . $id
    : '?add=1';

header('Location: ' . $url, true, 303);
exit;
