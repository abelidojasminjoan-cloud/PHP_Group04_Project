<?php
declare(strict_types=1);

// PUREVIA - CONFIRM MANDATORY GOOGLE AUTHENTICATOR

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/two_factor_auth.php';
require_once __DIR__ . '/../../services/complete_2fa_login.php';

$setupUrl = BASE_URL . '/account/setup_2fa_login.php';
$loginUrl = BASE_URL . '/public/login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$csrf = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrf) ||
    !is_string($_SESSION['csrf_token'] ?? null) ||
    !hash_equals($_SESSION['csrf_token'], $csrf)
) {
    http_response_code(403);
    exit('Invalid security token.');
}

$userId = (int) (
    $_SESSION['pending_2fa_user_id'] ?? 0
);

$started = (int) (
    $_SESSION['pending_2fa_started'] ?? 0
);

$secret = $_SESSION['2fa_setup_secret'] ?? null;

$setupUserId = (int) (
    $_SESSION['2fa_setup_user_id'] ?? 0
);

$setupExpires = (int) (
    $_SESSION['2fa_setup_expires'] ?? 0
);

if (
    $userId <= 0 ||
    $started <= 0 ||
    $started > time() ||
    time() - $started > 300 ||
    !is_string($secret) ||
    $setupUserId !== $userId ||
    $setupExpires <= time()
) {
    header('Location: ' . $loginUrl, true, 303);
    exit;
}

$attempts = (int) (
    $_SESSION['2fa_setup_attempts'] ?? 0
);

if ($attempts >= 5) {
    $_SESSION['2fa_error'] =
        'Too many attempts. Please log in again.';

    unset(
        $_SESSION['pending_2fa_user_id'],
        $_SESSION['pending_2fa_started'],
        $_SESSION['2fa_setup_secret']
    );

    header('Location: ' . $loginUrl, true, 303);
    exit;
}

$submittedCode = $_POST['authenticator_code'] ?? '';

$code = is_string($submittedCode)
    ? trim($submittedCode)
    : '';

$_SESSION['2fa_setup_attempts'] = $attempts + 1;

// Verify the actual TOTP code on the server.
$step = pvVerifyTotp($secret, $code);

if ($step === null) {
    $_SESSION['2fa_error'] =
        'Incorrect authenticator code. Try again.';

    header('Location: ' . $setupUrl, true, 303);
    exit;
}

$transactionStarted = false;

try {
    $encryptedSecret = pvEncryptSecret($secret);
    $recoveryCodes = pvGenerateRecoveryCodes();

    $conn->begin_transaction();
    $transactionStarted = true;

    $stmt = $conn->prepare("
        UPDATE users
        SET
            totp_secret = ?,
            totp_enabled_at = NOW(),
            totp_last_step = ?
        WHERE id = ?
          AND status = 'active'
          AND totp_enabled_at IS NULL
    ");

    $stmt->bind_param(
        'sii',
        $encryptedSecret,
        $step,
        $userId
    );

    $stmt->execute();
    $updated = $stmt->affected_rows;
    $stmt->close();

    if ($updated !== 1) {
        throw new RuntimeException(
            'Unable to enable authenticator.'
        );
    }

    $stmt = $conn->prepare("
        INSERT INTO user_totp_recovery_codes
            (user_id, code_hash)
        VALUES (?, ?)
    ");

    foreach ($recoveryCodes as $recoveryCode) {
        $hash = password_hash(
            $recoveryCode,
            PASSWORD_DEFAULT
        );

        $stmt->bind_param('is', $userId, $hash);
        $stmt->execute();
    }

    $stmt->close();

    $conn->commit();
    $transactionStarted = false;

    // Complete the login only after successful enrollment.
    $redirect = pvCompleteTwoFactorLogin($conn, $userId);

    // Display recovery codes once, before entering the store.
    $_SESSION['2fa_recovery_display'] = $recoveryCodes;

    header('Location: ' . $setupUrl, true, 303);
    exit;

} catch (Throwable $error) {

    if ($transactionStarted) {
        $conn->rollback();
    }

    error_log(
        'PureVia 2FA confirmation failed: ' .
        $error->getMessage()
    );

    $_SESSION['2fa_error'] =
        'Unable to complete authenticator setup.';

    header('Location: ' . $loginUrl, true, 303);
    exit;
}
