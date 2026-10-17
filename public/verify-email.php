<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

$userId = (int) ($_SESSION['pending_verification_user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/public/register.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT email, status FROM users WHERE id = ? LIMIT 1'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || $user['status'] !== 'pending') {
    unset($_SESSION['pending_verification_user_id']);
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = $_SESSION['otp_error'] ?? '';
$notice = $_SESSION['otp_notice'] ?? '';

unset($_SESSION['otp_error'], $_SESSION['otp_notice']);

function otpEscape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">

    <title>Verify Email | PureVia</title>

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/auth.css">

    <style>
        .otp-info {
            color: #555;
            line-height: 1.7;
            margin-bottom: 22px;
        }

        .otp-input {
            width: 100%;
            box-sizing: border-box;
            padding: 15px;
            font-size: 27px;
            letter-spacing: 12px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .otp-message {
            padding: 12px;
            border-radius: 7px;
            margin: 15px 0;
        }

        .otp-error {
            background: #fff0f0;
            color: #a11;
        }

        .otp-success {
            background: #edf6ef;
            color: #286344;
        }

        .resend-form {
            margin-top: 20px;
            text-align: center;
        }

        .resend-button {
            background: none;
            border: none;
            color: #426b58;
            text-decoration: underline;
            cursor: pointer;
            font: inherit;
        }
    </style>
</head>

<body class="auth-page">

<main class="auth-shell">

    <section class="auth-visual">
        <a class="auth-brand"
           href="<?= BASE_URL ?>/public/index.php">
            <img
                src="<?= BASE_URL ?>/assets/images/logo/purevia_logo_1.png"
                alt="PureVia"
                width="168"
                height="168">
        </a>

        <h2 class="auth-visual-title">
            Skincare,<br>made simple.
        </h2>
    </section>

    <section class="auth-content">

        <div class="auth-form-area">

            <div class="auth-heading">
                <p class="auth-eyebrow">ACCOUNT ACTIVATION</p>

                <h1>Verify your email</h1>

                <p class="auth-subtitle">
                    Enter the 6-digit code sent to your email.
                </p>
            </div>

            <p class="otp-info">
                Verification code sent to:<br>
                <strong>
                    <?= otpEscape($user['email']) ?>
                </strong>
                <br>
                The code expires in 10 minutes.
            </p>

            <?php if ($error !== ''): ?>
                <div class="otp-message otp-error" role="alert">
                    <?= otpEscape($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($notice !== ''): ?>
                <div class="otp-message otp-success" role="status">
                    <?= otpEscape($notice) ?>
                </div>
            <?php endif; ?>

            <form
                class="auth-form"
                method="POST"
                action="<?= BASE_URL ?>/actions/auth/verify-otp.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= otpEscape($_SESSION['csrf_token']) ?>">

                <div class="auth-field">
                    <label for="otp">
                        Verification Code
                    </label>

                    <input
                        class="otp-input"
                        type="text"
                        id="otp"
                        name="otp"
                        maxlength="6"
                        minlength="6"
                        pattern="[0-9]{6}"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        placeholder="000000"
                        required
                        autofocus>
                </div>

                <button type="submit" class="auth-submit">
                    Verify & Activate Account
                </button>
            </form>

            <form
                class="resend-form"
                method="POST"
                action="<?= BASE_URL ?>/actions/auth/resend-otp.php">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= otpEscape($_SESSION['csrf_token']) ?>">

                <p>Didn't receive the code?</p>

                <button type="submit" class="resend-button">
                    Resend OTP
                </button>
            </form>

            <p class="auth-bottom-link">
                Already activated?
                <a href="<?= BASE_URL ?>/public/login.php">
                    Sign in
                </a>
            </p>

        </div>
    </section>

</main>

</body>
</html>
