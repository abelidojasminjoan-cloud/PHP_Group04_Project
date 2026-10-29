<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| PUREVIA - VERIFY EMAIL OTP
|--------------------------------------------------------------------------
| 1. Validate request and CSRF token
| 2. Validate 6-digit OTP
| 3. Check expiration and attempts
| 4. Verify the email and create a single-use activation link
| 5. Send the activation link in a second email
| 6. Show the check-your-email page
|--------------------------------------------------------------------------
*/

// Correct paths: go up from actions/auth to project root.
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/EmailService.php';

function otpRedirect(string $path, bool $verified = false): never
{
    $url = BASE_URL . $path;

    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=UTF-8');
        $result = ['redirect' => $url, 'verified' => $verified];
        if ($path === '/public/verify-email.php' && !empty($_SESSION['otp_error'])) {
            $result['error'] = $_SESSION['otp_error'];
            unset($_SESSION['otp_error']);
        }
        echo json_encode($result);
        exit;
    }

    header('Location: ' . $url);
    exit;
}

function redirectToOtp(): never
{
    otpRedirect('/public/verify-email.php');
}

function redirectToLogin(): never
{
    otpRedirect('/public/login.php');
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
    otpRedirect('/public/register.php');
}

/*
|--------------------------------------------------------------------------
| VALIDATE OTP FORMAT
|--------------------------------------------------------------------------
*/

$otp = is_string($_POST['otp'] ?? null) ? trim($_POST['otp']) : '';

if (!preg_match('/^[0-9]{6}$/', $otp)) {
    $_SESSION['otp_error'] = $otp === ''
        ? 'Please enter your verification code.'
        : 'Please enter a valid 6-digit verification code.';
    redirectToOtp();
}

/*
|--------------------------------------------------------------------------
| VERIFY OTP AND PREPARE ACCOUNT ACTIVATION
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
         WHERE id = ? AND role_id IN (2, 3)
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
            'This verification code can no longer be used. Please request a new OTP.';

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

        $_SESSION['otp_error'] = $attempts >= 4
            ? 'This verification code can no longer be used. Please request a new OTP.'
            : 'Incorrect verification code.';

        redirectToOtp();
    }

    /*
    |--------------------------------------------------------------------------
    | OTP CORRECT - VERIFY EMAIL, KEEP THE ACCOUNT PENDING
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "UPDATE users
         SET
            email_verified_at = NOW()
         WHERE id = ?
           AND status = 'pending'"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $stmt->close();

    // Store only the hash. The raw token is delivered in the second email.
    $activationToken = bin2hex(random_bytes(32));
    $activationHash = hash('sha256', $activationToken);

    $stmt = $conn->prepare(
        'UPDATE email_verification_tokens
         SET used_at = NOW()
         WHERE user_id = ? AND used_at IS NULL'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
    );
    $stmt->bind_param('is', $userId, $activationHash);
    $stmt->execute();
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
    | SAVE EMAIL VERIFICATION AND THE ACTIVATION TOKEN
    |--------------------------------------------------------------------------
    */

    $conn->commit();
    $transactionStarted = false;

    /*
    |--------------------------------------------------------------------------
    | KEEP THE PENDING SESSION UNTIL THE EMAIL LINK IS USED
    |--------------------------------------------------------------------------
    */

    unset($_SESSION['otp_error'], $_SESSION['otp_notice']);

    /*
    |--------------------------------------------------------------------------
    | SEND SECOND EMAIL - SINGLE-USE ACCOUNT ACTIVATION LINK
    |--------------------------------------------------------------------------
    */

    $emailSent = false;

    try {

        $emailSent = sendActivationEmail(
            $user['email'],
            $user['first_name'],
            APP_URL . '/actions/auth/activate-account.php?token=' . $activationToken
        );

    } catch (Throwable $mailError) {

        error_log(
            'PureVia activation email error: '
            . $mailError->getMessage()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE THE ACTIVATION RESULT FOR THE CONFIRMATION PAGE
    |--------------------------------------------------------------------------
    */

    $_SESSION['account_activation'] = [
        'user_id' => $userId,
        'email_sent' => $emailSent,
        'completed_at' => time()
    ];

    $_SESSION['login_email'] = $user['email'];

    /*
    |--------------------------------------------------------------------------
    | REDIRECT TO THE ACTIVATION CONFIRMATION
    |--------------------------------------------------------------------------
    */

    otpRedirect('/public/account-activated.php', true);

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
