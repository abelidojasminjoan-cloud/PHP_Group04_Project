<?php
require_once __DIR__ . '/../config/db.php';
$passwordPolicy = securityPolicy($conn);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = $_SESSION['register_error'] ?? '';
$fieldErrors = $_SESSION['register_errors'] ?? [];
$data = $_SESSION['register_data'] ?? [];

unset(
    $_SESSION['register_error'],
    $_SESSION['register_errors'],
    $_SESSION['register_data']
);

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


function escapeHtml(string $value): string {
    return pvHtml($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | PureVia</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

   <link rel="stylesheet"
      href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-page">

<main class="auth-shell">

    <section class="auth-visual" aria-label="PureVia skincare">
        <a class="auth-brand" href="<?= BASE_URL ?>/index.php" aria-label="PureVia home">
            <img src="<?= BASE_URL ?>/assets/images/logo/purevia_logo_1.png" alt="PureVia" width="168" height="168">
        </a>
        <h2 class="auth-visual-title">
            Skincare,<br>
            made simple.
        </h2>
    </section>

    <section class="auth-content auth-register-content">

        <div class="auth-form-area">

            <div class="auth-heading">
                <p class="auth-eyebrow">JOIN PUREVIA</p>
                <h1>Register</h1>
                <p class="auth-subtitle">
                    Create your account for personalized routines
                    and thoughtful rewards.
                </p>
            </div>

            <p class="auth-form-error" role="alert" <?= $error === '' ? 'hidden' : '' ?>>
                <?= escapeHtml($error) ?>
            </p>

            <form
                class="auth-form"
                action="<?= BASE_URL ?>/actions/auth/register.php"
                method="POST"
                novalidate
            >
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapeHtml($_SESSION['csrf_token']) ?>"
                >

                <div class="auth-name-row">

                    <div class="auth-field">
                        <label for="registerFirstName">
                            First Name
                        </label>
                        <input
                            type="text"
                            id="registerFirstName"
                            name="first_name"
                            placeholder="Maya"
                            value="<?= escapeHtml($data['first_name'] ?? '') ?>"
                            autocomplete="given-name"
                            minlength="2"
                            maxlength="100"
                            aria-invalid="<?= isset($fieldErrors['first_name']) ? 'true' : 'false' ?>"
                            aria-describedby="registerFirstNameError"
                        >
                        <span class="auth-field-error" id="registerFirstNameError" role="alert" <?= isset($fieldErrors['first_name']) ? '' : 'hidden' ?>>
                            <?= escapeHtml($fieldErrors['first_name'] ?? '') ?>
                        </span>
                    </div>

                    <div class="auth-field">
                        <label for="registerLastName">
                            Last Name
                        </label>
                        <input
                            type="text"
                            id="registerLastName"
                            name="last_name"
                            placeholder="Green"
                            value="<?= escapeHtml($data['last_name'] ?? '') ?>"
                            autocomplete="family-name"
                            minlength="2"
                            maxlength="100"
                            aria-invalid="<?= isset($fieldErrors['last_name']) ? 'true' : 'false' ?>"
                            aria-describedby="registerLastNameError"
                        >
                        <span class="auth-field-error" id="registerLastNameError" role="alert" <?= isset($fieldErrors['last_name']) ? '' : 'hidden' ?>>
                            <?= escapeHtml($fieldErrors['last_name'] ?? '') ?>
                        </span>
                    </div>

                </div>

                <div class="auth-field">
                    <label for="registerEmail">
                        Email Address
                    </label>
                    <input
                        type="email"
                        id="registerEmail"
                        name="email"
                        placeholder="you@example.com"
                        value="<?= escapeHtml($data['email'] ?? '') ?>"
                        autocomplete="email"
                        maxlength="254"
                        aria-invalid="<?= isset($fieldErrors['email']) ? 'true' : 'false' ?>"
                        aria-describedby="registerEmailError"
                    >
                    <span class="auth-field-error" id="registerEmailError" role="alert" <?= isset($fieldErrors['email']) ? '' : 'hidden' ?>>
                        <?= escapeHtml($fieldErrors['email'] ?? '') ?>
                    </span>
                </div>

                <div class="auth-field">
                    <label for="registerPassword">
                        Create Password
                    </label>

                    <div class="auth-password-wrapper">
                        <input
                            type="password"
                            id="registerPassword"
                            name="password"
                            placeholder="At least <?= (int) $passwordPolicy['password_min_length'] ?> characters"
                            autocomplete="new-password"
                            minlength="<?= (int) $passwordPolicy['password_min_length'] ?>"
                            aria-invalid="<?= isset($fieldErrors['password']) ? 'true' : 'false' ?>"
                            aria-describedby="registerPasswordError registerPasswordFeedback"
                        >

                        <button
                            type="button"
                            class="auth-password-toggle"
                            data-toggle-password="registerPassword"
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

                    <span class="auth-field-error" id="registerPasswordError" role="alert" <?= isset($fieldErrors['password']) ? '' : 'hidden' ?>>
                        <?= escapeHtml($fieldErrors['password'] ?? '') ?>
                    </span>
                    <div class="auth-password-feedback" id="registerPasswordFeedback" hidden>
                        <p class="auth-password-strength">
                            Password strength:
                            <strong id="registerPasswordStrength" data-level="empty" aria-live="polite" aria-atomic="true">Not set</strong>
                        </p>
                        <ul class="auth-password-checklist">
                            <?php foreach (securityPasswordRequirements($passwordPolicy) as $passwordRuleKey => $passwordRuleLabel): ?>
                                <li data-password-rule="<?= escapeHtml($passwordRuleKey) ?>" data-min-count="<?= securityPasswordRuleMinimum($passwordPolicy, $passwordRuleKey) ?>" data-met="false">
                                    <div class="auth-rule-icon" aria-hidden="true">
                                        <svg class="auth-rule-check" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" focusable="false">
                                            <!-- Font Awesome Free 7.2.0, Fonticons, Inc. https://fontawesome.com/license/free -->
                                            <path d="M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z"/>
                                        </svg>
                                        <svg class="auth-rule-x" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" focusable="false">
                                            <!-- Font Awesome Free 7.3.1, Fonticons, Inc. https://fontawesome.com/license/free -->
                                            <path d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"/>
                                        </svg>
                                    </div>
                                    <span><?= escapeHtml($passwordRuleLabel) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="registerConfirmPassword">
                        Confirm Password
                    </label>

                    <div class="auth-password-wrapper">
                        <input
                            type="password"
                            id="registerConfirmPassword"
                            name="confirm_password"
                            placeholder="Enter your password again"
                            autocomplete="new-password"
                            minlength="<?= (int) $passwordPolicy['password_min_length'] ?>"
                            aria-invalid="<?= isset($fieldErrors['confirm_password']) ? 'true' : 'false' ?>"
                            aria-describedby="registerConfirmPasswordError"
                        >

                        <button
                            type="button"
                            class="auth-password-toggle"
                            data-toggle-password="registerConfirmPassword"
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
                    <span class="auth-field-error" id="registerConfirmPasswordError" role="alert" <?= isset($fieldErrors['confirm_password']) ? '' : 'hidden' ?>>
                        <?= escapeHtml($fieldErrors['confirm_password'] ?? '') ?>
                    </span>
                </div>

               


<div class="auth-terms-field">
    <p class="auth-terms">
        By creating an account, you agree to our
        <a href="<?= BASE_URL ?>/terms.php"
           target="_blank"
           rel="noopener noreferrer">
            Terms of Use
        </a>
        and acknowledge that you have read our
        <a href="<?= BASE_URL ?>/privacy_policy.php"
           target="_blank"
           rel="noopener noreferrer">
            Privacy Policy
        </a>.
    </p>
</div>




                <!-- Google reCAPTCHA v2 -->
                <div class="auth-field">
                    <div
                        class="g-recaptcha"
                        data-sitekey="<?= pvHtml(
                            $_ENV['RECAPTCHA_SITE_KEY'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    ></div>
                </div>

                <?php if (!empty($fieldErrors['captcha'])): ?>
                    <span class="auth-field-error" role="alert">
                        <?= escapeHtml($fieldErrors['captcha']) ?>
                    </span>
                <?php endif; ?>

                <button
                    type="submit"
                    class="auth-submit"
                    id="registerSubmit"
                >  
                    Create my account
                </button>


            </form>

            <p class="auth-bottom-link">
                Already have an account?
                <a href="<?= BASE_URL ?>/login.php">
                    Sign in
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
    <a href="<?= BASE_URL ?>/privacy_policy.php">
        Privacy
    </a>

    <a href="<?= BASE_URL ?>/terms.php">
        Terms
    </a>

    <span>&copy; <?= date('Y') ?> PureVia</span>
</div>


        </footer>

    </section>

</main>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/auth_loading.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/auth.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>
</html>
