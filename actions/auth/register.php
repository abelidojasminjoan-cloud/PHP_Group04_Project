<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/EmailService.php';

function registrationRedirect(): never {
    header('Location: ' . BASE_URL . '/public/register.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    registrationRedirect();
}

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($_POST['csrf_token'] ?? null) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token']
    )
) {
    http_response_code(403);
    exit('Invalid form request.');
}

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');
$confirm = (string) ($_POST['confirm_password'] ?? '');
$terms = isset($_POST['terms']);

$_SESSION['register_data'] = [
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'terms' => $terms
];

$errors = [];

if (strlen($firstName) < 2 || strlen($firstName) > 100) {
    $errors['first_name'] = 'First name must be 2–100 characters.';
}

if (strlen($lastName) < 2 || strlen($lastName) > 100) {
    $errors['last_name'] = 'Last name must be 2–100 characters.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 254) {
    $errors['email'] = 'Enter a valid email address.';
}

if (
    strlen($password) < 12 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[^A-Za-z0-9]/', $password)
) {
    $errors['password'] =
        'Password needs 12 characters, uppercase, lowercase, '
        . 'number, and special character.';
}

if ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

if (!$terms) {
    $errors['terms'] = 'Accept the Terms and Privacy Policy.';
}

if ($errors) {
    $_SESSION['register_errors'] = $errors;
    registrationRedirect();
}

try {
    $stmt = $conn->prepare(
        'SELECT id FROM users WHERE email = ? LIMIT 1'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($exists) {
        $_SESSION['register_errors'] = [
            'email' => 'An account with this email already exists.'
        ];
        registrationRedirect();
    }

    $otp = (string) random_int(100000, 999999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $roleId = 3;
    $status = 'pending';

    $conn->begin_transaction();

    $stmt = $conn->prepare(
        'INSERT INTO users
            (role_id, first_name, last_name, email, password, status)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    $stmt->bind_param(
        'isssss',
        $roleId,
        $firstName,
        $lastName,
        $email,
        $passwordHash,
        $status
    );

    $stmt->execute();
    $userId = (int) $conn->insert_id;
    $stmt->close();

    $purpose = 'email_verification';

    $stmt = $conn->prepare(
        'INSERT INTO otp_codes
            (user_id, otp_hash, purpose, expires_at)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))'
    );

    $stmt->bind_param(
        'iss',
        $userId,
        $otpHash,
        $purpose
    );

    $stmt->execute();
    $stmt->close();

    $conn->commit();

    $_SESSION['pending_verification_user_id'] = $userId;
    unset($_SESSION['register_data']);

    $sent = sendOtpEmail($email, $firstName, $otp);

    $_SESSION['otp_notice'] = $sent
        ? 'We sent a 6-digit verification code to your email.'
        : 'Your account was created, but the email could not '
          . 'be sent. Check your email settings, then use Resend OTP.';

    header('Location: ' . BASE_URL . '/public/verify-email.php');
    exit;

} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }

    error_log('Registration error: ' . $e->getMessage());

    $_SESSION['register_error'] =
        'Unable to register. Please try again.';

    registrationRedirect();
}
