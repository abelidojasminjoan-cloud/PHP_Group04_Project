<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../services/StaffAccounts.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function staffCreateResponse(int $status, bool $success, string $message): never {
    http_response_code($status); echo pvJson(['success' => $success, 'message' => $message]); exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') staffCreateResponse(405, false, 'Method not allowed.');
$adminId = (int) ($_SESSION['user_id'] ?? 0);
if ($adminId < 1) staffCreateResponse(401, false, 'Please sign in as an administrator.');
$token = $_POST['csrf_token'] ?? null;
if (!is_string($token) || !is_string($_SESSION['csrf_token'] ?? null) || $_SESSION['csrf_token'] === '' || !hash_equals($_SESSION['csrf_token'], $token)) staffCreateResponse(403, false, 'Please refresh this page and try again.');
$email = $_POST['email'] ?? null;
if (!is_string($email)) staffCreateResponse(422, false, 'Please enter a valid email address.');
try {
    createPureViaStaffAccount($conn, $adminId, $email, (string) ($_ENV['STAFF_DEFAULT_PASSWORD'] ?? ''));
    staffCreateResponse(201, true, 'Staff account created. The sign-in details have been emailed. The account stays pending until email verification and activation are complete.');
} catch (InvalidArgumentException $error) { staffCreateResponse(422, false, $error->getMessage()); }
catch (DomainException $error) { staffCreateResponse(409, false, $error->getMessage()); }
catch (Throwable $error) {
    error_log('Staff creation failed: ' . $error->getMessage());
    staffCreateResponse(500, false, 'The staff account could not be created or its invitation delivered. Please check the mail and staff password configuration, then try again.');
}