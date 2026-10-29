<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

// Verify the existing CSRF token.
$token = $_POST['csrf_token'] ?? null;
$sessionToken = $_SESSION['csrf_token'] ?? null;

if (
    !is_string($token) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $token)
) {
    respond(403, [
        'success' => false,
        'message' => 'Invalid security token.'
    ]);
}

// Allow only the two supported documents.
$policy = $_POST['policy'] ?? null;

if (
    !is_string($policy) ||
    !in_array($policy, ['terms', 'privacy'], true)
) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid policy type.'
    ]);
}

// 1 = checked, 0 = unchecked.
$value = $_POST['confirmed'] ?? null;

if (!is_string($value) || !in_array($value, ['0', '1'], true)) {
    respond(400, [
        'success' => false,
        'message' => 'Invalid confirmation value.'
    ]);
}

$confirmed = $value === '1';

if ($confirmed) {

    $_SESSION['policy_confirmation'][$policy] = [
        'confirmed_at' => time(),
        'version' => 'v1'
    ];

} else {

    // Remove confirmation when the checkbox is unchecked.
    unset($_SESSION['policy_confirmation'][$policy]);

}

respond(200, [
    'success' => true,
    'policy' => $policy,
    'confirmed' => $confirmed
]);
