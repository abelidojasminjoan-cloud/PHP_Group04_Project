<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/SecureUpload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$redirect = BASE_URL . '/account/profile.php';

function photoError(string $message): never {
    global $redirect;

    $_SESSION['profile_photo_error'] = $message;
    header('Location: ' . $redirect, true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    http_response_code(403);
    exit('Invalid security token.');
}

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT profile_photo
    FROM users
    WHERE id = ?
      AND role_id = 3
      AND status = 'active'
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(403);
    exit('Access denied.');
}

$destination = null;
try {
    if (!is_array($_FILES['profile_photo'] ?? null)) {
        photoError('Please select a profile photo.');
    }
    $image = SecureUpload::image($_FILES['profile_photo']);
    $stored = SecureUpload::store($image, 'profiles');
    $filename = $stored['filename'];
    $destination = $stored['path'];
    $uploadDirectory = dirname($destination) . '/';

    $update = $conn->prepare("UPDATE users SET profile_photo = ?
        WHERE id = ? AND role_id = 3 AND status = 'active'");
    $update->bind_param('si', $filename, $userId);
    $update->execute();
    $changed = $update->affected_rows === 1;
    $update->close();
    if (!$changed) {
        throw new RuntimeException('Unable to update your profile photo.');
    }
} catch (Throwable $exception) {
    if ($destination !== null && is_file($destination)) {
        @unlink($destination);
    }
    error_log('Profile photo upload failed: ' . $exception->getMessage());
    photoError($exception instanceof RuntimeException && !($exception instanceof mysqli_sql_exception)
        ? $exception->getMessage() : 'Unable to update your profile photo. Please try again.');
}

// Delete the previous photo only after saving the new one.
if (!empty($user['profile_photo'])) {
    $oldFilename = basename($user['profile_photo']);

    if ($oldFilename !== $filename) {
        $oldPath = $uploadDirectory . $oldFilename;

        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }
}

$_SESSION['profile_success'] = 'Your profile photo has been updated.';
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: ' . $redirect, true, 303);
exit;
