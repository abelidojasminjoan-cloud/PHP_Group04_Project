<?php

require_once __DIR__ . '/app.php';

return [
    'host' => $_ENV['MAIL_HOST'],
    'port' => (int) $_ENV['MAIL_PORT'],
    'username' => $_ENV['MAIL_USERNAME'],
    'password' => $_ENV['MAIL_PASSWORD'],
    'from_email' => $_ENV['MAIL_FROM_ADDRESS'],
    'from_name' => $_ENV['MAIL_FROM_NAME']
];