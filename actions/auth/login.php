<?php

/* =========================================================
   PUREVIA LOGIN PROCESS
========================================================= */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/AccountLockout.php';
$passwordPolicy = securityPolicy($conn);


/* =========================================================
   ONLY ALLOW POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: ' . BASE_URL . '/public/login.php');

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
   INPUT
========================================================= */

$email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';

$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

unset($_SESSION['login_error'], $_SESSION['login_errors']);


/* =========================================================
   VALIDATION
========================================================= */

$fieldErrors = [];

if ($email === '') {
    $fieldErrors['email'] = 'Please enter your email address.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fieldErrors['email'] = 'Please enter a valid email address.';
}

if ($password === '') {
    $fieldErrors['password'] = 'Please enter your password.';
}

if ($fieldErrors) {
    $_SESSION['login_errors'] = $fieldErrors;

    $_SESSION['login_email'] =
        $email;

    header(
        'Location: ' . BASE_URL . '/public/login.php'
    );

    exit;
}


/* =========================================================
   RECORD LOGIN RESULTS FOR THE EXISTING ADMIN LOG
========================================================= */

function recordLoginAttempt(
    mysqli $conn,
    ?int $userId,
    string $email,
    bool $successful,
    ?string $reason
): void {
    $ipAddress = filter_var(
        $_SERVER['REMOTE_ADDR'] ?? '',
        FILTER_VALIDATE_IP
    ) ?: null;
    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $browser = match (true) {
        str_contains($userAgent, 'Edg/') || str_contains($userAgent, 'EdgiOS/') => 'Edge',
        str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera/') => 'Opera',
        str_contains($userAgent, 'Firefox/') || str_contains($userAgent, 'FxiOS/') => 'Firefox',
        str_contains($userAgent, 'Chrome/') || str_contains($userAgent, 'CriOS/') => 'Chrome',
        str_contains($userAgent, 'Safari/') => 'Safari',
        default => 'Unknown'
    };
    $successValue = $successful ? 1 : 0;

    $attemptStmt = $conn->prepare("
        INSERT INTO login_attempts
            (user_id, email_attempted, ip_address, browser, successful, failure_reason)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $attemptStmt->bind_param('isssis', $userId, $email, $ipAddress, $browser, $successValue, $reason);
    $attemptStmt->execute();
    if (!$successful && $reason === 'Account locked.' && $userId !== null) {
        recordPureViaAccountLockout($conn, $userId, (int) $conn->insert_id, $ipAddress, $browser);
    }
    $attemptStmt->close();
}

function rejectLogin(string $email, string $message): never
{
    $_SESSION['login_email'] = $email;
    $_SESSION['login_errors'] = ['password' => $message];
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

/* =========================================================
   VERIFY CREDENTIALS AND UPDATE THE ACCOUNT ATOMICALLY
========================================================= */

try {
    $conn->begin_transaction();

    // Lock the user row so simultaneous requests cannot lose failed attempts.
    $stmt = $conn->prepare("
        SELECT
            u.id, u.first_name, u.last_name, u.email, u.password, u.status,
            u.failed_login_attempts, u.locked_until,
            u.role_id, u.totp_enabled_at,
            (SELECT r.role_name FROM roles r WHERE r.id = u.role_id) AS role_name
        FROM users u
        WHERE u.email = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        recordLoginAttempt($conn, null, $email, false, 'Invalid email or password.');
        $conn->commit();
        rejectLogin($email, 'Invalid email or password.');
    }

    $userId = (int) $user['id'];

    if (isPureViaLoginLocked($conn, $user, $passwordPolicy)) {
        $update = $conn->prepare("UPDATE users SET status = CASE WHEN status = 'active' THEN 'locked' ELSE status END, locked_until = NULL WHERE id = ?");
        $update->bind_param('i', $userId);
        $update->execute();
        $update->close();
        recordLoginAttempt($conn, $userId, $email, false, 'Login blocked: account locked.');
        $conn->commit();
        rejectLogin($email, 'Your account is locked. Please contact support.');
    }

    $failedAttempts = (int) $user['failed_login_attempts'];

    if (!password_verify($password, $user['password'])) {
        recordFailedPureViaLogin($conn, $user, $passwordPolicy);
        $locked = $failedAttempts + 1 >= $passwordPolicy['max_login_attempts'];

        $reason = $locked
            ? 'Account locked.'
            : 'Invalid email or password.';
        recordLoginAttempt($conn, $userId, $email, false, $reason);
        $conn->commit();

        if ($locked) {
            rejectLogin($email, 'Your account is locked. Please contact support.');
        }

        rejectLogin($email, 'Invalid email or password.');
    }

    if ($user['status'] !== 'active') {
        recordLoginAttempt($conn, $userId, $email, false, 'Account is not active.');
        $conn->commit();
        rejectLogin($email, 'Your account is not currently active.');
    }

    // Password verified, but the login is NOT complete.
    // Successful-login updates happen after Google Authenticator.
$conn->commit();
} catch (Throwable $error) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }

    error_log('Login failed: ' . $error->getMessage());

    rejectLogin(
        $email,
        'Unable to login right now. Please try again.'
    );
}


// Complete login or start Google Authenticator.
// Get the user ID from the verified database record,
// NOT from the session.
$userId = (int) $user['id'];

// Regenerate the session ID for security.
session_regenerate_id(true);

// Remove any previous authenticated identity.
unset(
    $_SESSION['user_id'],
    $_SESSION['role_id'],
    $_SESSION['role'],
    $_SESSION['first_name'],
    $_SESSION['last_name'],
    $_SESSION['name'],
    $_SESSION['email'],
    $_SESSION['last_activity'],
    $_SESSION['2fa_verified'],
    $_SESSION['2fa_setup_secret'],
    $_SESSION['2fa_setup_user_id'],
    $_SESSION['2fa_setup_expires'],
    $_SESSION['2fa_setup_attempts']
);

// Save the pending 2FA challenge.
$_SESSION['pending_2fa_user_id'] = $userId;
$_SESSION['pending_2fa_started'] = time();
$_SESSION['pending_2fa_attempts'] = 0;

if (!TWO_FACTOR_ENABLED) {
    require_once __DIR__ . '/../../services/complete_2fa_login.php';
    try {
        $redirect = pvCompletePasswordLogin($conn, $userId);
    } catch (Throwable $error) {
        error_log('Login completion failed: ' . $error->getMessage());
        rejectLogin($email, 'Unable to login right now. Please try again.');
    }
    header('Location: ' . $redirect, true, 303);
    exit;
}

// First login: Set up Google Authenticator.
if (empty($user['totp_enabled_at'])) {
    header(
        'Location: ' . BASE_URL .
        '/account/setup_2fa_login.php',
        true,
        303
    );
    exit;
}

// Existing 2FA user: Verify authenticator code.
header(
    'Location: ' . BASE_URL .
    '/account/verify_2fa.php',
    true,
    303
);
exit;
