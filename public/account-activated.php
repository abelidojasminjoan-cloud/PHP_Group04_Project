<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

header('Cache-Control: no-store');

$activation = $_SESSION['account_activation'] ?? null;

if (
    !is_array($activation) ||
    ($activation['user_id'] ?? 0) !== ($_SESSION['pending_verification_user_id'] ?? null) ||
    !is_int($activation['completed_at'] ?? null) ||
    $activation['completed_at'] < time() - 1800
) {
    unset($_SESSION['account_activation']);
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

$message = 'Your email has been verified successfully! '
    . (($activation['email_sent'] ?? false) === true
        ? 'An activation email has been sent. Click the button in that email to activate your account and continue to login.'
        : 'The activation email could not be sent. Request a new code below to try again.');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = $_SESSION['otp_error'] ?? '';
unset($_SESSION['otp_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title>Check Your Email | PureVia</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-visual" aria-label="PureVia skincare">
        <a class="auth-brand" href="<?= BASE_URL ?>/public/index.php" aria-label="PureVia home">
            <img src="<?= BASE_URL ?>/assets/images/logo/purevia_logo_1.png" alt="PureVia" width="168" height="168">
        </a>
        <h2 class="auth-visual-title">Skincare,<br>made simple.</h2>
    </section>

    <section class="auth-content">
        <div class="auth-form-area">
            <div class="auth-heading">
                <p class="auth-eyebrow">EMAIL VERIFICATION</p>
                <h1>Check your email</h1>
                <p class="auth-subtitle">Use the button in your activation email to activate your account.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="auth-alert auth-alert-error" role="alert">
                    <?= pvHtml($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="auth-alert <?= ($activation['email_sent'] ?? false) === true ? 'auth-alert-success' : 'auth-alert-error' ?>" role="status">
                <?= pvHtml($message, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <?php if (($activation['email_sent'] ?? false) !== true): ?>
                <form class="auth-form" method="POST" action="<?= BASE_URL ?>/actions/auth/resend-otp.php">
                    <input type="hidden" name="csrf_token" value="<?= pvHtml($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="auth-submit">Request a new code</button>
                </form>
            <?php endif; ?>

        </div>

        <footer class="auth-footer">
            <div class="auth-help" aria-label="Contact support">
                <span>NEED HELP?</span>
                <a href="tel:+8269336908">+82 6933 6908</a>
                <span aria-hidden="true">|</span>
                <a href="mailto:purevia@gmail.com">purevia@gmail.com</a>
            </div>
            <div class="auth-footer-links">
                <a href="<?= BASE_URL ?>/privacy.php">Privacy</a>
                <a href="<?= BASE_URL ?>/terms.php">Terms</a>
                <span>&copy; <?= date('Y') ?> PureVia</span>
            </div>
        </footer>
    </section>
</main>
</body>
</html>
