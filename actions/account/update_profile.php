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
    header('Location: ' . BASE_URL . '/public/index.php');
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

$values = [];
$fieldErrors = [];
foreach (['first_name','last_name','phone','gender','birth_date'] as $field) {
    $value = $_POST[$field] ?? '';
    if (!is_string($value)) {
        $fieldErrors[$field] = 'Invalid form value.';
        $value = '';
    }
    $values[$field] = trim($value);
}
$firstName = $values['first_name'];
$lastName = $values['last_name'];
$phone = $values['phone'];
$gender = $values['gender'];
$birthDate = $values['birth_date'];

foreach (['first_name' => 'first name', 'last_name' => 'last name'] as $field => $label) {
    if ($values[$field] === '') $fieldErrors[$field] = 'Please enter your ' . $label . '.';
    elseif (mb_strlen($values[$field]) > 100) $fieldErrors[$field] = 'Use no more than 100 characters.';
}
if ($phone !== '' && (strlen($phone) > 30 || !preg_match('/^[0-9+\\s()\\-]+$/', $phone))) {
    $fieldErrors['phone'] = 'Please enter a valid phone number.';
}
if (!in_array($gender, ['', 'Female', 'Male', 'Non-binary', 'Prefer not to say'], true)) {
    $fieldErrors['gender'] = 'Please select a valid gender option.';
}
if ($birthDate !== '') {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
    if (!$date || $date->format('Y-m-d') !== $birthDate ||
        $date > new DateTimeImmutable('today') || $date < new DateTimeImmutable('1900-01-01')) {
        $fieldErrors['birth_date'] = 'Please enter a valid birth date.';
    }
}
if ($fieldErrors) {
    $_SESSION['profile_field_errors'] = $fieldErrors;
    $_SESSION['profile_old'] = $values;
    profileError('Please check the highlighted fields.', $editUrl);
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
