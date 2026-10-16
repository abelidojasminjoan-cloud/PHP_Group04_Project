<?php
declare(strict_types=1);


require_once __DIR__ . '/app.php';

return [
    'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
    'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
    'username' => $_ENV['MAIL_USERNAME'] ?? '',
    'password' => $_ENV['MAIL_PASSWORD'] ?? '',
    'from_email' => $_ENV['MAIL_FROM_ADDRESS'] ?? '',
    'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'PureVia'
];
