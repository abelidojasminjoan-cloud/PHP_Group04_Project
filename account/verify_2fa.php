<?php
declare(strict_types=1);

// PUREVIA - TWO-FACTOR LOGIN VERIFICATION

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (!TWO_FACTOR_ENABLED) {
    header('Location: ' . BASE_URL . '/public/login.php', true, 303);
    exit;
}
require_once __DIR__ . '/../services/two_factor_auth.php';
require_once __DIR__ . '/../services/complete_2fa_login.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$loginUrl = BASE_URL . '/public/login.php';

$pendingUserId = (int) (
    $_SESSION['pending_2fa_user_id'] ?? 0
);

$pendingStarted = (int) (
    $_SESSION['pending_2fa_started'] ?? 0
);

// Require a valid, unexpired challenge.
if (
    $pendingUserId <= 0 ||
    $pendingStarted <= 0 ||
    $pendingStarted > time() ||
    time() - $pendingStarted > 300
) {
    unset(
        $_SESSION['pending_2fa_user_id'],
        $_SESSION['pending_2fa_started'],
        $_SESSION['pending_2fa_attempts']
    );

    header('Location: ' . $loginUrl);
    exit;
}

if (
    !isset($_SESSION['csrf_token']) ||
    !is_string($_SESSION['csrf_token'])
) {
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}

$error = '';
$remainingAttempts = max(
    0,
    5 - (int) (
        $_SESSION['pending_2fa_attempts'] ?? 0
    )
);

// Process verification form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrf) ||
        !hash_equals($_SESSION['csrf_token'], $csrf)
    ) {
        http_response_code(403);
        exit('Invalid security request.');
    }

    $submitted = $_POST['verification_code'] ?? '';
    $code = is_string($submitted)
        ? strtoupper(trim($submitted))
        : '';

    $attempts = (int) (
        $_SESSION['pending_2fa_attempts'] ?? 0
    );

    if ($attempts >= 5) {
        unset(
            $_SESSION['pending_2fa_user_id'],
            $_SESSION['pending_2fa_started'],
            $_SESSION['pending_2fa_attempts']
        );

        header('Location: ' . $loginUrl, true, 303);
        exit;
    }

    $_SESSION['pending_2fa_attempts'] = $attempts + 1;

    $authenticated = false;
    $user = null;
    $transactionStarted = false;

    try {
        $conn->begin_transaction();
        $transactionStarted = true;

        // Lock the customer record while verifying.
        $stmt = $conn->prepare("
        SELECT
            id,
            email,
            role_id,
            totp_secret,
            totp_last_step
        FROM users
        WHERE id = ?
            AND role_id IN (1, 2, 3)
            AND status = 'active'
            AND totp_enabled_at IS NOT NULL
        FOR UPDATE
    ");

        $stmt->bind_param('i', $pendingUserId);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && !empty($user['totp_secret'])) {

            // Six-digit authenticator code.
            if (preg_match('/^[0-9]{6}$/D', $code)) {

                $secret = pvDecryptSecret(
                    $user['totp_secret']
                );

                $step = pvVerifyTotp(
                    $secret,
                    $code,
                    (int) $user['totp_last_step']
                );

                if ($step !== null) {
                    $stmt = $conn->prepare("
                        UPDATE users
                        SET totp_last_step = ?
                        WHERE id = ?
                          AND totp_enabled_at IS NOT NULL
                          AND totp_last_step < ?
                    ");

                    $stmt->bind_param(
                        'iii',
                        $step,
                        $pendingUserId,
                        $step
                    );

                    $stmt->execute();

                    $authenticated =
                        $stmt->affected_rows === 1;

                    $stmt->close();
                }

            // Ten-character recovery code.
            } elseif (
                preg_match('/^[A-F0-9]{10}$/D', $code)
            ) {

                $stmt = $conn->prepare("
                    SELECT id, code_hash
                    FROM user_totp_recovery_codes
                    WHERE user_id = ?
                      AND used_at IS NULL
                ");

                $stmt->bind_param('i', $pendingUserId);
                $stmt->execute();

                $recoveryCodes = $stmt
                    ->get_result()
                    ->fetch_all(MYSQLI_ASSOC);

                $stmt->close();

                foreach ($recoveryCodes as $recovery) {

                    if (!password_verify(
                        $code,
                        $recovery['code_hash']
                    )) {
                        continue;
                    }

                    $recoveryId = (int) $recovery['id'];

                    $stmt = $conn->prepare("
                        UPDATE user_totp_recovery_codes
                        SET used_at = NOW()
                        WHERE id = ?
                          AND used_at IS NULL
                    ");

                    $stmt->bind_param('i', $recoveryId);
                    $stmt->execute();

                    $authenticated =
                        $stmt->affected_rows === 1;

                    $stmt->close();
                    break;
                }
            }
        }

        if ($authenticated) {
            $conn->commit();
        } else {
            $conn->rollback();
        }

        $transactionStarted = false;

    } catch (Throwable $exception) {

        if ($transactionStarted) {
            $conn->rollback();
        }

        error_log('PureVia two-factor verification failed.');
        $authenticated = false;
    }

    if ($authenticated && $user) {

    try {
        $redirect = pvCompleteTwoFactorLogin(
            $conn,
            (int) $user['id']
        );

        header('Location: ' . $redirect, true, 303);
        exit;

    } catch (Throwable $error) {

        error_log(
            'PureVia login completion failed: ' .
            $error->getMessage()
        );

        $error =
            'Unable to complete login. Please sign in again.';

        unset(
            $_SESSION['pending_2fa_user_id'],
            $_SESSION['pending_2fa_started'],
            $_SESSION['pending_2fa_attempts']
        );

        header('Location: ' . $loginUrl, true, 303);
        exit;
    }
}

    $remainingAttempts = max(
        0,
        5 - (int) $_SESSION['pending_2fa_attempts']
    );

    $error = 'Invalid or expired verification code.';

    if ($remainingAttempts === 0) {
        unset(
            $_SESSION['pending_2fa_user_id'],
            $_SESSION['pending_2fa_started'],
            $_SESSION['pending_2fa_attempts']
        );

        $error =
            'Too many failed attempts. Please sign in again.';
    }
}

// Escape user-controlled text for HTML.
function pvVerifyEsc(string $value): string
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Verify Your Identity | PureVia</title>

    <link
        rel="stylesheet"
        href="<?= pvVerifyEsc(BASE_URL) ?>/assets/css/verify_2fa.css"
    >

    <script
        src="<?= pvVerifyEsc(BASE_URL) ?>/assets/js/verify_2fa.js"
        defer
    ></script>
</head>

<body class="pv-two-factor-page">

    <main class="pv-verify-layout">

        <section
            class="pv-verify-card"
            aria-labelledby="verifyTitle"
        >

            <a
                href="<?= pvVerifyEsc($loginUrl) ?>"
                class="pv-brand"
                aria-label="PureVia Home"
            >
                PureVia
            </a>

            <div class="pv-brand-divider"></div>

            <div class="pv-security-icon" aria-hidden="true">
                <svg
                    width="28"
                    height="28"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path
                        d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"
                    />
                    <path d="m9 12 2 2 4-4"/>
                </svg>
            </div>

            <p class="pv-verify-eyebrow">
                ACCOUNT SECURITY
            </p>

            <h1 id="verifyTitle" class="pv-verify-title">
                Verify Your Identity
            </h1>

            <p class="pv-verify-description">
                Enter the 6-digit verification code from
                your authenticator app to securely
                access your PureVia account.
            </p>

            <?php if ($error !== ''): ?>
                <div
                    class="pv-verify-alert"
                    role="alert"
                >
                    <?= pvVerifyEsc($error) ?>
                </div>
            <?php endif; ?>

            <form
                id="twoFactorForm"
                method="POST"
                action=""
                class="pv-verify-form"
                novalidate
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= pvVerifyEsc(
                        $_SESSION['csrf_token']
                    ) ?>"
                >

                <div class="pv-form-group">
                    <label for="verificationCode">
                        Verification Code
                    </label>

                    <input
                        type="text"
                        id="verificationCode"
                        name="verification_code"
                        class="pv-code-input"
                        placeholder="Enter verification code"
                        maxlength="10"
                        inputmode="text"
                        autocomplete="one-time-code"
                        autocapitalize="characters"
                        spellcheck="false"
                        aria-describedby="codeHelp codeError"
                        required
                        <?= $remainingAttempts === 0
                            ? 'disabled'
                            : '' ?>
                    >

                    <p id="codeHelp" class="pv-input-hint">
                        6-digit authenticator code or
                        10-character recovery code.
                    </p>

                    <p
                        id="codeError"
                        class="pv-input-error"
                        role="alert"
                        hidden
                    ></p>
                </div>

                <button
                    type="submit"
                    id="verifyButton"
                    class="pv-verify-button"
                    <?= $remainingAttempts === 0
                        ? 'disabled'
                        : '' ?>
                >
                    Verify &amp; Continue
                    <span aria-hidden="true">→</span>
                </button>

            </form>

            <div class="pv-security-note">
                <span class="pv-security-note-icon"
                      aria-hidden="true">✦</span>

                <p>
                    Your security matters to us.
                    Never share your verification
                    or recovery codes with anyone.
                </p>
            </div>

            <div class="pv-card-footer">
                <a
                    href="<?= pvVerifyEsc($loginUrl) ?>"
                    class="pv-back-link"
                >
                    ← Back to Login
                </a>
            </div>

        </section>

        <p class="pv-page-footer">
            PUREVIA &nbsp;·&nbsp; SKINCARE MADE PERSONAL
        </p>

    </main>

</body>
</html>
