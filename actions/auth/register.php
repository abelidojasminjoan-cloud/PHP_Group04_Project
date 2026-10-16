<?php

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

/* =========================================================
   ONLY ALLOW POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}

/* =========================================================
   VALIDATE FORM TOKEN
========================================================= */

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token']) ||
    !is_string($_POST['csrf_token'] ?? null) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token']
    )
) {
    http_response_code(403);
    exit('Invalid form request. Please refresh and try again.');
}


/* =========================================================
   GET FORM VALUES
========================================================= */

$firstName = is_string($_POST['first_name'] ?? null) ? trim($_POST['first_name']) : '';
$lastName = is_string($_POST['last_name'] ?? null) ? trim($_POST['last_name']) : '';
$email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';

$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

$termsAccepted = isset($_POST['terms']);

unset($_SESSION['register_error'], $_SESSION['register_errors']);


/* =========================================================
   SAVE VALUES TEMPORARILY
========================================================= */

$_SESSION['register_data'] = [
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'terms' => $termsAccepted
];


/* =========================================================
   VALIDATION
========================================================= */

$fieldErrors = [];

if ($firstName === '') {
    $fieldErrors['first_name'] = 'Please enter your first name.';
} elseif (strlen($firstName) < 2 || strlen($firstName) > 100) {
    $fieldErrors['first_name'] = 'First name must contain 2 to 100 characters.';
}

if ($lastName === '') {
    $fieldErrors['last_name'] = 'Please enter your last name.';
} elseif (strlen($lastName) < 2 || strlen($lastName) > 100) {
    $fieldErrors['last_name'] = 'Last name must contain 2 to 100 characters.';
}

if ($email === '') {
    $fieldErrors['email'] = 'Please enter your email address.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fieldErrors['email'] = 'Please enter a valid email address.';
}

if ($password === '') {
    $fieldErrors['password'] = 'Please create a password.';
} elseif (strlen($password) < 12) {
    $fieldErrors['password'] = 'Password must contain at least 12 characters.';
} elseif (!preg_match('/[A-Z]/', $password)) {
    $fieldErrors['password'] = 'Password must contain at least one uppercase letter.';
} elseif (!preg_match('/[a-z]/', $password)) {
    $fieldErrors['password'] = 'Password must contain at least one lowercase letter.';
} elseif (!preg_match('/[0-9]/', $password)) {
    $fieldErrors['password'] = 'Password must contain at least one number.';
} elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
    $fieldErrors['password'] = 'Password must contain at least one special character.';
}

if ($confirmPassword === '') {
    $fieldErrors['confirm_password'] = 'Please confirm your password.';
} elseif ($password !== $confirmPassword) {
    $fieldErrors['confirm_password'] = 'Passwords do not match.';
}

if (!$termsAccepted) {
    $fieldErrors['terms'] = 'Please agree to the Terms and Privacy Policy.';
}

if ($fieldErrors) {
    $_SESSION['register_errors'] = $fieldErrors;
    header('Location: ' . BASE_URL . '/register.php');
    exit;
}


/* =========================================================
   CHECK IF EMAIL ALREADY EXISTS
========================================================= */

$checkStatement = $conn->prepare(
    'SELECT id FROM users WHERE email = ? LIMIT 1'
);

$checkStatement->bind_param(
    's',
    $email
);

$checkStatement->execute();

$checkResult = $checkStatement->get_result();


if ($checkResult->num_rows > 0) {

    $checkStatement->close();

    $_SESSION['register_errors'] = [
        'email' => 'An account with this email already exists.'
    ];

    header('Location: ' . BASE_URL . '/register.php');
    exit;
}


$checkStatement->close();


/* =========================================================
   HASH PASSWORD
========================================================= */

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


/* =========================================================
   CREATE CUSTOMER ACCOUNT

   role_id = 3 → Customer

   DEVELOPMENT ONLY:
   Accounts are temporarily activated immediately so
   registration/login can be tested without OTP/email
   activation.

   PRODUCTION:
   Change this back to:
   $status = 'pending';
========================================================= */

$roleId = 3;


/* DEVELOPMENT */

$status = 'active';


/* PRODUCTION - ENABLE LATER */

// $status = 'pending';


$insertStatement = $conn->prepare(
    'INSERT INTO users
    (
        role_id,
        first_name,
        last_name,
        email,
        password,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?)'
);


$insertStatement->bind_param(
    'isssss',
    $roleId,
    $firstName,
    $lastName,
    $email,
    $passwordHash,
    $status
);


/* =========================================================
   INSERT ACCOUNT
========================================================= */

if (!$insertStatement->execute()) {

    error_log(
        'Registration failed: ' .
        $insertStatement->error
    );

    $insertStatement->close();

    $_SESSION['register_error'] =
        'Unable to create your account. Please try again.';

    header('Location: ' . BASE_URL . '/register.php');
    exit;
}


/* =========================================================
   SUCCESS
========================================================= */

$insertStatement->close();

unset($_SESSION['register_data']);

$_SESSION['login_success'] =
    'Account created successfully. You may now log in.';

header('Location: ' . BASE_URL . '/login.php');
exit;
