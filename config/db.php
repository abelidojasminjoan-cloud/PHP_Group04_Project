<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';

// Database configuration from .env
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = (int) ($_ENV['DB_PORT'] ?? 3306);
$dbName = $_ENV['DB_NAME'] ?? 'purevia_db';
$dbUsername = $_ENV['DB_USERNAME'] ?? 'root';
$dbPassword = $_ENV['DB_PASSWORD'] ?? '';

// Enable MySQLi exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(
        $dbHost,
        $dbUsername,
        $dbPassword,
        $dbName,
        $dbPort
    );

    $conn->set_charset('utf8mb4');

} catch (mysqli_sql_exception $e) {
    error_log('PureVia Database Error: ' . $e->getMessage());

    http_response_code(500);
    exit('Unable to connect to the database.');
}

require_once __DIR__ . '/../services/security-policy.php';
$pureViaRequestPolicy = securityPolicy($conn);
enforcePureViaSessionTimeout(
    $pureViaRequestPolicy,
    !defined('PUREVIA_SESSION_STATUS_REQUEST')
);
enforcePureViaPasswordExpiry($conn, $pureViaRequestPolicy);
