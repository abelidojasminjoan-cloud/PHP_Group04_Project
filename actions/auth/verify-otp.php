<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PUREVIA - VERIFY EMAIL OTP
|--------------------------------------------------------------------------
| 1. Validate request and CSRF token
| 2. Validate 6-digit OTP
| 3. Check expiration and attempts
| 4. Activate customer account
| 5. Send activation confirmation email
| 6. Redirect to login page
|--------------------------------------------------------------------------
*/

// Correct paths: go up from actions/auth to project root.
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/EmailService.php';

function redirectToOtp(): never
{
    header('Location: ' . BASE_URL . '/public/verify-email.php');
    exit;
}

function redirectToLogin(): never
{
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| ALLOW POST REQUESTS ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectToOtp();
}

/*
|--------------------------------------------------------------------------
| VALIDATE CSRF TOKEN
|--------------------------------------------------------------------------
*/

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
    exit('Invalid request. Please refresh the page and try again.');
}

/*
|--------------------------------------------------------------------------
| GET PENDING CUSTOMER
|--------------------------------------------------------------------------
*/

$userId = (int) (
    $_SESSION['pending_verification_user_id'] ?? 0
);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/public/register.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDATE OTP FORMAT
|--------------------------------------------------------------------------
*/

$otp = trim((string) ($_POST['otp'] ?? ''));

if (!preg_match('/^[0-9]{6}$/', $otp)) {
    $_SESSION['otp_error'] = 'Please enter a valid 6-digit OTP.';
    redirectToOtp();
}

/*
|--------------------------------------------------------------------------
| VERIFY OTP AND ACTIVATE ACCOUNT
|--------------------------------------------------------------------------
*/

$transactionStarted = false;

try {

    $conn->begin_transaction();
    $transactionStarted = true;

    /*
    |--------------------------------------------------------------------------
    | FIND PENDING USER
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "SELECT
            id,
            first_name,
            last_name,
            email,
            status
         FROM users
         WHERE id = ?
         LIMIT 1
         FOR UPDATE"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$user || $user['status'] !== 'pending') {

        $conn->commit();
        $transactionStarted = false;

        unset($_SESSION['pending_verification_user_id']);

        $_SESSION['login_error'] =
            'This account is not awaiting email verification.';

        redirectToLogin();
    }

    /*
    |--------------------------------------------------------------------------
    | FIND MOST RECENT UNUSED OTP
    |--------------------------------------------------------------------------
    */

    $purpose = 'email_verification';

    $stmt = $conn->prepare(
        "SELECT
            id,
            otp_hash,
            expires_at,
            attempt_count,
            (expires_at > NOW()) AS is_valid
         FROM otp_codes
         WHERE user_id = ?
           AND purpose = ?
           AND used_at IS NULL
         ORDER BY id DESC
         LIMIT 1
         FOR UPDATE"
    );

    $stmt->bind_param(
        'is',
        $userId,
        $purpose
    );

    $stmt->execute();

    $otpRecord = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | CHECK OTP EXISTS
    |--------------------------------------------------------------------------
    */

    if (!$otpRecord) {

        $conn->commit();
        $transactionStarted = false;

        $_SESSION['otp_error'] =
            'No active verification code found. Please request a new OTP.';

        redirectToOtp();
    }

    $otpId = (int) $otpRecord['id'];
    $attempts = (int) $otpRecord['attempt_count'];

    /*
    |--------------------------------------------------------------------------
    | CHECK OTP EXPIRATION
    |--------------------------------------------------------------------------
    */

    if ((int) $otpRecord['is_valid'] !== 1) {

        $conn->commit();
        $transactionStarted = false;

        $_SESSION['otp_error'] =
            'Your OTP has expired. Please request a new code.';

        redirectToOtp();
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK MAXIMUM ATTEMPTS
    |--------------------------------------------------------------------------
    */

    if ($attempts >= 5) {

        $conn->commit();
        $transactionStarted = false;

        $_SESSION['otp_error'] =
            'Too many incorrect attempts. Please request a new OTP.';

        redirectToOtp();
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY OTP HASH
    |--------------------------------------------------------------------------
    */

    if (!password_verify($otp, $otpRecord['otp_hash'])) {

        $stmt = $conn->prepare(
            "UPDATE otp_codes
             SET attempt_count = attempt_count + 1
             WHERE id = ?"
        );

        $stmt->bind_param('i', $otpId);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $transactionStarted = false;

        $remainingAttempts = 4 - $attempts;

        if ($remainingAttempts > 0) {

            $_SESSION['otp_error'] =
                'Incorrect verification code. '
                . $remainingAttempts
                . ' attempts remaining.';

        } else {

            $_SESSION['otp_error'] =
                'Too many incorrect attempts. Please request a new OTP.';
        }

        redirectToOtp();
    }

    /*
    |--------------------------------------------------------------------------
    | OTP CORRECT - ACTIVATE ACCOUNT
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE users
         SET
            status = 'active',
            email_verified_at = NOW()
         WHERE id = ?
           AND status = 'pending'"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException('Unable to activate account.');
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | MARK OTP AS USED
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE otp_codes
         SET used_at = NOW()
         WHERE user_id = ?
           AND purpose = 'email_verification'
           AND used_at IS NULL"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | SAVE ACCOUNT ACTIVATION
    |--------------------------------------------------------------------------
    */

    $conn->commit();
    $transactionStarted = false;

    /*
    |--------------------------------------------------------------------------
    | REMOVE PENDING VERIFICATION SESSION
    |--------------------------------------------------------------------------
    */

    unset($_SESSION['pending_verification_user_id']);

    /*
    |--------------------------------------------------------------------------
    | SEND SECOND EMAIL - ACCOUNT ACTIVATION CONFIRMATION
    |--------------------------------------------------------------------------
    */

    $emailSent = false;

    try {

        $emailSent = sendActivationEmail(
            $user['email'],
            $user['first_name']
        );

    } catch (Throwable $mailError) {

        error_log(
            'PureVia activation email error: '
            . $mailError->getMessage()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW SUCCESS MESSAGE ON LOGIN PAGE
    |--------------------------------------------------------------------------
    */

    if ($emailSent) {

        $_SESSION['login_success'] =
            'Your PureVia account has been activated successfully! '
            . 'A confirmation email has been sent. Please log in.';

    } else {

        $_SESSION['login_success'] =
            'Your PureVia account has been activated successfully! '
            . 'You can now log in using your email and password.';
    }

    $_SESSION['login_email'] = $user['email'];

    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO LOGIN
    |--------------------------------------------------------------------------
    */

    redirectToLogin();

} catch (Throwable $e) {

    if ($transactionStarted) {

        try {
            $conn->rollback();
        } catch (Throwable $rollbackError) {
            error_log(
                'PureVia rollback error: '
                . $rollbackError->getMessage()
            );
        }
    }

    error_log(
        'PureVia OTP verification error: '
        . $e->getMessage()
    );

    $_SESSION['otp_error'] =
        'Unable to verify your OTP. Please try again.';

    redirectToOtp();
}
