<?php

/* =========================================================
   PUREVIA LOGIN PROCESS
========================================================= */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';


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
    $attemptStmt->close();
}

function rejectLogin(string $email, string $message): never
{
    $_SESSION['login_email'] = $email;
    $_SESSION['login_error'] = $message;
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
            COALESCE(u.locked_until > NOW(), 0) AS login_locked,
            GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), u.locked_until)) AS lock_seconds_remaining,
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

    if ($user['status'] === 'locked' || (int) $user['login_locked'] === 1) {
        if ($user['status'] === 'locked') {
            $message = 'Your account is locked. Please contact support.';
        } else {
            $minutes = max(1, (int) ceil((int) $user['lock_seconds_remaining'] / 60));
            $message = 'Account locked after ' . MAX_LOGIN_ATTEMPTS . ' failed login attempts. '
                . 'Try again in ' . $minutes . ($minutes === 1 ? ' minute.' : ' minutes.');
        }
        recordLoginAttempt($conn, $userId, $email, false, 'Login blocked: account locked.');
        $conn->commit();
        rejectLogin($email, $message);
    }

    // An expired temporary lock starts a new set of attempts.
    $failedAttempts = $user['locked_until'] !== null
        ? 0
        : (int) $user['failed_login_attempts'];

    if (!password_verify($password, $user['password'])) {
        $failedAttempts = min(MAX_LOGIN_ATTEMPTS, $failedAttempts + 1);
        $locked = $failedAttempts >= MAX_LOGIN_ATTEMPTS;
        $lockSeconds = LOGIN_LOCKOUT_TIME;
        $lockedValue = $locked ? 1 : 0;
        $update = $conn->prepare("
            UPDATE users
            SET failed_login_attempts = ?,
                locked_until = CASE WHEN ? = 1
                    THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                    ELSE NULL END
            WHERE id = ?
        ");
        $update->bind_param('iiii', $failedAttempts, $lockedValue, $lockSeconds, $userId);
        $update->execute();
        $update->close();

        $reason = $locked
            ? 'Account locked after ' . MAX_LOGIN_ATTEMPTS . ' failed login attempts.'
            : 'Invalid password. Attempt ' . $failedAttempts . ' of ' . MAX_LOGIN_ATTEMPTS . '.';
        recordLoginAttempt($conn, $userId, $email, false, $reason);
        $conn->commit();

        if ($locked) {
            $minutes = max(1, (int) ceil(LOGIN_LOCKOUT_TIME / 60));
            rejectLogin($email, $reason . ' Try again in ' . $minutes . ' minutes.');
        }

        $remaining = MAX_LOGIN_ATTEMPTS - $failedAttempts;
        rejectLogin($email, 'Invalid email or password. ' . $remaining
            . ($remaining === 1 ? ' attempt remaining.' : ' attempts remaining.'));
    }

    if ($user['status'] !== 'active') {
        recordLoginAttempt($conn, $userId, $email, false, 'Account is not active.');
        $conn->commit();
        rejectLogin($email, 'Your account is not currently active.');
    }

    $update = $conn->prepare("
        UPDATE users
        SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW()
        WHERE id = ?
    ");
    $update->bind_param('i', $userId);
    $update->execute();
    $update->close();
    recordLoginAttempt($conn, $userId, $email, true, null);
    $conn->commit();
} catch (Throwable $error) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }
    error_log('Login failed: ' . $error->getMessage());
    rejectLogin($email, 'Unable to login right now. Please try again.');
}


/* =========================================================
   CREATE SESSION
========================================================= */

session_regenerate_id(true);


$_SESSION['user_id'] =
    (int) $user['id'];


$_SESSION['role'] =
    strtolower($user['role_name']);


$_SESSION['first_name'] =
    $user['first_name'];


$_SESSION['last_name'] =
    $user['last_name'];


$_SESSION['name'] =
    $user['first_name'];


$_SESSION['email'] =
    $user['email'];


$_SESSION['last_activity'] =
    time();


/* =========================================================
   REDIRECT BY ROLE
========================================================= */

if ($_SESSION['role'] === 'admin') {

    header(
        'Location: '
        . BASE_URL
        . '/admin/dashboard.php'
    );

    exit;
}


if ($_SESSION['role'] === 'staff') {

    header(
        'Location: '
        . BASE_URL
        . '/staff/dashboard.php'
    );

    exit;
}


/* CUSTOMER */

header(
    'Location: '
    . BASE_URL
    . '/public/index.php'
);

exit;
