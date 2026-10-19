<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/EmailService.php';

function resendRedirect(): never {
    header('Location: ' . BASE_URL . '/public/verify-email.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resendRedirect();
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

$userId = (int) (
    $_SESSION['pending_verification_user_id'] ?? 0
);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/public/register.php');
    exit;
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare(
        'SELECT id, first_name, email, status
         FROM users
         WHERE id = ?
         LIMIT 1
         FOR UPDATE'
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || $user['status'] !== 'pending') {
        $conn->commit();

        unset($_SESSION['pending_verification_user_id']);

        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }

    // Count codes created within the last hour.
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(
                    TIMESTAMPDIFF(
                        SECOND,
                        MAX(created_at),
                        NOW()
                    ),
                    999999
                ) AS seconds_since_last
         FROM otp_codes
         WHERE user_id = ?
           AND purpose = 'email_verification'
           AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $limits = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int) $limits['total'] >= 5) {
        $conn->commit();

        $_SESSION['otp_error'] =
            'You have reached the OTP request limit. '
            . 'Please try again later.';

        resendRedirect();
    }

    $elapsed = (int) $limits['seconds_since_last'];

    if ($elapsed < 60) {
        $conn->commit();

        $_SESSION['otp_error'] =
            'Please wait ' . (60 - $elapsed)
            . ' seconds before requesting another code.';

        resendRedirect();
    }

    // Generate a new secure OTP.
    $otp = (string) random_int(100000, 999999);
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);

    // Invalidate old unused codes.
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

    unset($_SESSION['account_activation']);

    $sent = sendOtpEmail(
        $user['email'],
        $user['first_name'],
        $otp
    );

    $_SESSION['otp_notice'] = $sent
        ? 'A new verification code has been sent to your email.'
        : 'The new code could not be delivered. '
          . 'Please check the mail configuration and try later.';

    resendRedirect();

} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
    }

    error_log('OTP resend error: ' . $e->getMessage());

    $_SESSION['otp_error'] =
        'Unable to resend OTP. Please try again later.';

    resendRedirect();
}
