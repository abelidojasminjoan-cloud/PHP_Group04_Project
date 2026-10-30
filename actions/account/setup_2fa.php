<?php
declare(strict_types=1);

// PUREVIA - MANDATORY FIRST-LOGIN 2FA SETUP

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

if (!TWO_FACTOR_ENABLED) {
    header('Location: ' . BASE_URL . '/login.php', true, 303);
    exit;
}
require_once __DIR__ . '/../../services/two_factor_auth.php';

$redirect = BASE_URL . '/account/setup_2fa_login.php';

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

// Password verification must have happened first.
if (
    $userId <= 0 ||
    $started <= 0 ||
    $started > time() ||
    time() - $started > 300
) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$failures = (int) (
    $_SESSION['2fa_setup_attempts'] ?? 0
);

if ($failures >= 5) {
    http_response_code(429);
    exit('Too many setup attempts. Please log in again.');
}

try {
    $stmt = $conn->prepare("
        SELECT id
        FROM users
        WHERE id = ?
          AND status = 'active'
          AND totp_enabled_at IS NULL
        LIMIT 1
    ");

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        throw new RuntimeException(
            'Account is not eligible for setup.'
        );
    }

    // Preserve the same pending secret on repeated POSTs.
    $existing = $_SESSION['2fa_setup_secret'] ?? null;

    if (
        is_string($existing) &&
        (int) ($_SESSION['2fa_setup_user_id'] ?? 0)
            === $userId &&
        (int) ($_SESSION['2fa_setup_expires'] ?? 0)
            > time()
    ) {
        header('Location: ' . $redirect, true, 303);
        exit;
    }

    $secret = pvGenerateSecret();

    $_SESSION['2fa_setup_secret'] = $secret;
    $_SESSION['2fa_setup_user_id'] = $userId;
    $_SESSION['2fa_setup_expires'] = time() + 300;
    $_SESSION['2fa_setup_attempts'] = 0;

} catch (Throwable $error) {

    error_log(
        'PureVia 2FA setup failed: ' .
        $error->getMessage()
    );

    $_SESSION['2fa_error'] =
        'Unable to start Google Authenticator setup.';
}

header('Location: ' . $redirect, true, 303);
exit;
