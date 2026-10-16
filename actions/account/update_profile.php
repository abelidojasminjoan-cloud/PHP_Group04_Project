<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$editUrl = BASE_URL . '/account/edit_profile.php';
$profileUrl = BASE_URL . '/account/profile.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

function profileError(string $message, string $url): never {
    $_SESSION['profile_error'] = $message;
    header('Location: ' . $url, true, 303);
    exit;
}


// =====================================================
// CSRF TOKEN VALIDATION
// =====================================================

$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    http_response_code(403);
    exit('Invalid security token.');
}


$userId = (int) $_SESSION['user_id'];

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$gender = trim((string) ($_POST['gender'] ?? ''));
$birthDate = trim((string) ($_POST['birth_date'] ?? ''));

if ($firstName === '' || $lastName === '') {
    profileError('First and last name are required.', $editUrl);
}

if (
    mb_strlen($firstName) > 100 ||
    mb_strlen($lastName) > 100
) {
    profileError('Name is too long.', $editUrl);
}

if (
    $phone !== '' &&
    (
        strlen($phone) > 30 ||
        !preg_match('/^[0-9+\s()\-]+$/', $phone)
    )
) {
    profileError('Enter a valid phone number.', $editUrl);
}

$allowedGenders = [
    '',
    'Female',
    'Male',
    'Non-binary',
    'Prefer not to say'
];

if (!in_array($gender, $allowedGenders, true)) {
    profileError('Please select a valid gender option.', $editUrl);
}

if ($birthDate !== '') {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);

    if (
        !$date ||
        $date->format('Y-m-d') !== $birthDate ||
        $date > new DateTimeImmutable('today') ||
        $date < new DateTimeImmutable('1900-01-01')
    ) {
        profileError('Please enter a valid birth date.', $editUrl);
    }
}

$genderValue = $gender === '' ? null : $gender;
$birthValue = $birthDate === '' ? null : $birthDate;

$check = $conn->prepare("
    SELECT id
    FROM users
    WHERE id = ?
      AND role_id = 3
      AND status = 'active'
    LIMIT 1
");

$check->bind_param('i', $userId);
$check->execute();
$authorized = $check->get_result()->fetch_assoc();
$check->close();

if (!$authorized) {
    http_response_code(403);
    exit('Access denied.');
}

$stmt = $conn->prepare("
    UPDATE users
    SET first_name = ?,
        last_name = ?,
        phone = ?,
        gender = ?,
        birth_date = ?
    WHERE id = ?
      AND role_id = 3
      AND status = 'active'
");

$stmt->bind_param(
    'sssssi',
    $firstName,
    $lastName,
    $phone,
    $genderValue,
    $birthValue,
    $userId
);

if (!$stmt->execute()) {
    $stmt->close();
    profileError('Unable to update your profile.', $editUrl);
}

$stmt->close();

$_SESSION['name'] = $firstName . ' ' . $lastName;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$_SESSION['profile_success'] = 'Your profile has been updated successfully.';

header('Location: ' . $profileUrl, true, 303);
exit;