<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$redirect = BASE_URL . '/account/profile.php';

function photoError(string $message): never {
    global $redirect;

    $_SESSION['profile_error'] = $message;
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

if (
    !isset($_FILES['profile_photo']) ||
    !is_array($_FILES['profile_photo']) ||
    is_array($_FILES['profile_photo']['error'])
) {
    photoError('Please select a profile photo.');
}

$file = $_FILES['profile_photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    photoError('Photo upload failed. Please try again.');
}

$maxSize = 5 * 1024 * 1024;

if ($file['size'] <= 0 || $file['size'] > $maxSize) {
    photoError('The photo must be 5 MB or smaller.');
}

if (!is_uploaded_file($file['tmp_name'])) {
    photoError('Invalid uploaded file.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];

if (!isset($allowedTypes[$mime])) {
    photoError('Only JPG, PNG, and WebP images are allowed.');
}

$imageInfo = @getimagesize($file['tmp_name']);

if (
    $imageInfo === false ||
    ($imageInfo['mime'] ?? '') !== $mime ||
    $imageInfo[0] < 1 ||
    $imageInfo[1] < 1 ||
    $imageInfo[0] > 6000 ||
    $imageInfo[1] > 6000
) {
    photoError('Invalid image or unsupported image dimensions.');
}

$uploadDirectory = __DIR__ . '/../../uploads/profiles/';

if (!is_dir($uploadDirectory)) {
    if (!mkdir($uploadDirectory, 0755, true) &&
        !is_dir($uploadDirectory)) {
        photoError('Unable to create the photo directory.');
    }
}

$filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
$destination = $uploadDirectory . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    photoError('Unable to save your profile photo.');
}

chmod($destination, 0644);

$update = $conn->prepare("
    UPDATE users
    SET profile_photo = ?
    WHERE id = ?
      AND role_id = 3
      AND status = 'active'
");

$update->bind_param('si', $filename, $userId);

if (!$update->execute() || $update->affected_rows !== 1) {
    $update->close();
    @unlink($destination);
    photoError('Unable to update your profile photo.');
}

$update->close();

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