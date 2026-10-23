<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');

$loginUrl = BASE_URL . '/public/login.php';

// Display recovery codes after successful enrollment.
$showRecovery = (
    !empty($_SESSION['2fa_verified']) &&
    !empty($_SESSION['user_id']) &&
    is_array($_SESSION['2fa_recovery_display'] ?? null)
);

if ($showRecovery) {
    $recoveryCodes = $_SESSION['2fa_recovery_display'];
    unset($_SESSION['2fa_recovery_display']);

    $role = $_SESSION['role'] ?? 'customer';

    $continueUrl = match ($role) {
        'admin' => BASE_URL . '/admin/dashboard.php',
        'staff' => BASE_URL . '/staff/dashboard.php',
        default => BASE_URL . '/public/index.php'
    };

} else {
    $userId = (int) (
        $_SESSION['pending_2fa_user_id'] ?? 0
    );

    $started = (int) (
        $_SESSION['pending_2fa_started'] ?? 0
    );

    if (
        $userId <= 0 ||
        $started <= 0 ||
        $started > time() ||
        time() - $started > 300
    ) {
        header('Location: ' . $loginUrl);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT id, email, totp_enabled_at
        FROM users
        WHERE id = ?
          AND status = 'active'
        LIMIT 1
    ");

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || $user['totp_enabled_at'] !== null) {
        header('Location: ' . $loginUrl);
        exit;
    }

    $setupSecret = $_SESSION['2fa_setup_secret'] ?? '';
    $setupUserId = (int) (
        $_SESSION['2fa_setup_user_id'] ?? 0
    );

    $setupExpires = (int) (
        $_SESSION['2fa_setup_expires'] ?? 0
    );

    if (
        !is_string($setupSecret) ||
        $setupUserId !== $userId ||
        $setupExpires <= time()
    ) {
        $setupSecret = '';

        unset(
            $_SESSION['2fa_setup_secret'],
            $_SESSION['2fa_setup_user_id'],
            $_SESSION['2fa_setup_expires'],
            $_SESSION['2fa_setup_attempts']
        );
    }

    if ($setupSecret !== '') {
        $setupUri = 'otpauth://totp/' . rawurlencode('PureVia:' . $user['email']) . '?' .
            http_build_query([
                'secret' => $setupSecret,
                'issuer' => 'PureVia',
                'algorithm' => 'SHA1',
                'digits' => 6,
                'period' => 30,
            ], '', '&', PHP_QUERY_RFC3986);
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = (string) ($_SESSION['2fa_error'] ?? '');
unset($_SESSION['2fa_error']);

function pvSetupEsc(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Set Up Google Authenticator | PureVia</title>

    <link
        rel="stylesheet"
        href="<?= pvSetupEsc(BASE_URL) ?>/assets/css/verify_2fa.css"
    >
</head>

<body class="pv-two-factor-page">

<main class="pv-verify-layout">

<section class="pv-verify-card">

    <div class="pv-brand">PureVia</div>
    <div class="pv-brand-divider"></div>

    <div class="pv-security-icon" aria-hidden="true">
        <svg width="28" height="28"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             stroke-width="1.5"
             stroke-linecap="round"
             stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/>
            <path d="m9 12 2 2 4-4"/>
        </svg>
    </div>

    <?php if ($showRecovery): ?>

        <p class="pv-verify-eyebrow">
            SETUP COMPLETE
        </p>

        <h1 class="pv-verify-title">
            Save Your Recovery Codes
        </h1>

        <p class="pv-verify-description">
            Store these codes somewhere safe.
            Each recovery code can only be used once.
            They will not be shown again.
        </p>

        <div class="pv-security-note">
            <div>
                <?php foreach ($recoveryCodes as $code): ?>
                    <p>
                        <strong><?= pvSetupEsc((string) $code) ?></strong>
                    </p>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pv-card-footer">
            <a class="pv-back-link"
               href="<?= pvSetupEsc($continueUrl) ?>">
                Continue to PureVia →
            </a>
        </div>

    <?php else: ?>

        <p class="pv-verify-eyebrow">
            REQUIRED ACCOUNT SECURITY
        </p>

        <h1 class="pv-verify-title">
            Set Up Google Authenticator
        </h1>

        <p class="pv-verify-description">
            Google Authenticator is required
            every time you sign in to PureVia.
            You only need to set it up once.
        </p>

        <?php if ($error !== ''): ?>
            <div class="pv-verify-alert" role="alert">
                <?= pvSetupEsc($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($setupSecret === ''): ?>

            <form method="POST"
                  action="<?= pvSetupEsc(BASE_URL) ?>/actions/account/setup_2fa.php">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= pvSetupEsc($_SESSION['csrf_token']) ?>">

                <button type="submit"
                        class="pv-verify-button">
                    Start Authenticator Setup
                </button>
            </form>

        <?php else: ?>

            <p class="pv-verify-description">
                In Google Authenticator, tap + and choose Scan a QR code.
                Scan below, then enter the six-digit code from the app.
            </p>

            <div id="authenticatorQr" class="pv-authenticator-qr"
                 data-uri="<?= pvSetupEsc($setupUri) ?>"></div>
            <p id="qrError" class="pv-input-error" role="alert" hidden>
                Unable to display the QR code. Use the setup key below instead.
            </p>

            <details id="manualSetup" class="pv-manual-setup">
                <summary>Can't scan? Enter a setup key instead</summary>
                <div class="pv-form-group">
                    <label for="setupKey">Your Setup Key</label>
                    <input id="setupKey" class="pv-code-input pv-setup-key"
                           type="text" value="<?= pvSetupEsc($setupSecret) ?>" readonly>
                    <p class="pv-input-hint">Choose a time-based key and use PureVia as the account name.</p>
                </div>
            </details>

            <form method="POST"
                  action="<?= pvSetupEsc(BASE_URL) ?>/actions/account/confirm_2fa.php"
                  class="pv-verify-form">

                <input type="hidden"
                       name="csrf_token"
                       value="<?= pvSetupEsc($_SESSION['csrf_token']) ?>">

                <div class="pv-form-group">
                    <label for="authenticatorCode">
                        Six-Digit Authenticator Code
                    </label>

                    <input id="authenticatorCode"
                           name="authenticator_code"
                           class="pv-code-input"
                           type="text"
                           inputmode="numeric"
                           pattern="[0-9]{6}"
                           maxlength="6"
                           autocomplete="one-time-code"
                           placeholder="000000"
                           required>
                </div>

                <button type="submit"
                        class="pv-verify-button">
                    Activate and Continue →
                </button>
            </form>

        <?php endif; ?>

        <div class="pv-card-footer">
            <a class="pv-back-link"
               href="<?= pvSetupEsc($loginUrl) ?>">
                ← Back to Login
            </a>
        </div>

    <?php endif; ?>

</section>

<p class="pv-page-footer">
    PUREVIA · SKINCARE MADE PERSONAL
</p>

</main>
<?php if (!$showRecovery && $setupSecret !== ''): ?>
<script src="<?= pvSetupEsc(BASE_URL) ?>/assets/js/qrcodegen.js" defer></script>
<script src="<?= pvSetupEsc(BASE_URL) ?>/assets/js/authenticator-setup.js" defer></script>
<?php endif; ?>
</body>
</html>
