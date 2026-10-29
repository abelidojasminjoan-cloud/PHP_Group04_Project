<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'customer';

    if ($role === 'admin') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } elseif ($role === 'staff') {
        header('Location: ' . BASE_URL . '/staff/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/public/index.php');
    }
    exit;
}

$error = $_SESSION['login_error'] ?? '';
$fieldErrors = $_SESSION['login_errors'] ?? [];
$success = $_SESSION['login_success'] ?? '';
$email = $_SESSION['login_email'] ?? '';

unset(
    $_SESSION['login_error'],
    $_SESSION['login_errors'],
    $_SESSION['login_success'],
    $_SESSION['login_email']
);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escapeHtml(string $value): string{
    return pvHtml($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | PureVia</title>

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
        <h2 class="auth-visual-title">
            Skincare,<br>
            made simple.
        </h2>
    </section>

    <section class="auth-content">

        <div class="auth-form-area">

            <div class="auth-heading">
                <p class="auth-eyebrow">WELCOME BACK</p>
                <h1>Sign in</h1>
                <p class="auth-subtitle">
                    Return to your personalized rituals, rewards,
                    and skincare favorites.
                </p>
            </div>

            <p class="auth-form-error" role="alert" <?= $error === '' ? 'hidden' : '' ?>>
                <?= escapeHtml($error) ?>
            </p>

            <?php if ($success !== ''): ?>
                <div class="auth-alert auth-alert-success" role="status">
                    <?= escapeHtml($success) ?>
                </div>
            <?php endif; ?>

            <form
                class="auth-form"
                action="<?= BASE_URL ?>/actions/auth/login.php"
                method="POST"
                novalidate
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapeHtml($_SESSION['csrf_token']) ?>"
                >

                <div class="auth-field">
                    <label for="loginEmail">Email Address</label>
                    <input
                        type="email"
                        id="loginEmail"
                        name="email"
                        placeholder="you@example.com"
                        value="<?= escapeHtml($email) ?>"
                        autocomplete="email"
                        maxlength="254"
                        aria-invalid="<?= isset($fieldErrors['email']) ? 'true' : 'false' ?>"
                        aria-describedby="loginEmailError"
                    >
                    <span class="auth-field-error" id="loginEmailError" role="alert" <?= isset($fieldErrors['email']) ? '' : 'hidden' ?>>
                        <?= escapeHtml($fieldErrors['email'] ?? '') ?>
                    </span>
                </div>

                <div class="auth-field">
                    <label for="loginPassword">Password</label>

                    <div class="auth-password-wrapper">
                        <input
                            type="password"
                            id="loginPassword"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            aria-invalid="<?= isset($fieldErrors['password']) ? 'true' : 'false' ?>"
                            aria-describedby="loginPasswordError"
                        >

                        <button
                            type="button"
                            class="auth-password-toggle"
                            data-toggle-password="loginPassword"
                            aria-label="Show password"
                            aria-pressed="false"
                            hidden
                        >
                            <div class="auth-password-icon" aria-hidden="true">
                                <!-- Font Awesome Free 7.2.0, Fonticons, Inc. https://fontawesome.com/license/free -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" focusable="false">
                                    <path d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z"/>
                                </svg>
                            </div>
                        </button>
                    </div>
                    <span class="auth-field-error" id="loginPasswordError" role="alert" <?= isset($fieldErrors['password']) ? '' : 'hidden' ?>>
                        <?= escapeHtml($fieldErrors['password'] ?? '') ?>
                    </span>
                </div>

                <div class="auth-forgot">
                    <a href="<?= BASE_URL ?>/forgot-password.php">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="auth-submit">
                    Sign in
                </button>

            </form>

            <p class="auth-bottom-link">
                New to PureVia?
                <a href="<?= BASE_URL ?>/public/register.php">
                    Create an account
                </a>
            </p>

        </div>

        <footer class="auth-footer">
            <div class="auth-help" aria-label="Contact support">
                <span>NEED HELP?</span>
                <a href="tel:+8269336908">+82 6933 6908</a>
                <span aria-hidden="true">|</span>
                <a href="mailto:purevia@gmail.com">purevia@gmail.com</a>
            </div>
            <div class="auth-footer-links">
               <a href="<?= BASE_URL ?>/public/privacy_policy.php">Privacy</a>
                <a href="<?= BASE_URL ?>/public/terms.php">Terms</a>
                <span>&copy; <?= date('Y') ?> PureVia</span>
            </div>
        </footer>

    </section>

</main>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/auth.js"></script>
</body>
</html>
