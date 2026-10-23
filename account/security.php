<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
$passwordPolicy = securityPolicy($conn);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

function secEsc(mixed $value): string
{
    return pvHtml(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function secInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $first = strtoupper(substr($parts[0] ?? '', 0, 1));
    $last = count($parts) > 1
        ? strtoupper(substr(end($parts), 0, 1))
        : '';

    return $first . $last ?: 'U';
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

// =====================================================
// LOAD ACCOUNT
// =====================================================

$stmt = $conn->prepare("
    SELECT
        id,
        first_name,
        last_name,
        email,
        password
    FROM users
    WHERE id = ?
      AND role_id = 3 AND status = 'active'
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(403);
    exit('Account not found.');
}

$fieldErrors = [];
$formError = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? null;
    if (!is_string($submittedToken) || !is_string($csrfToken) ||
        !hash_equals($csrfToken, $submittedToken)) {
        http_response_code(403);
        $formError = 'Invalid request. Please refresh and try again.';
    } else {
        $currentPassword = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
        $newPassword = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

        if ($currentPassword === '') {
            $fieldErrors['current_password'] = 'Please enter your current password.';
        } elseif (!password_verify($currentPassword, (string) $user['password'])) {
            $fieldErrors['current_password'] = 'Your current password is incorrect.';
        }

        if ($newPassword === '') {
            $fieldErrors['new_password'] = 'Please create a new password.';
        } elseif ($passwordErrors = validatePureViaPassword($newPassword, $passwordPolicy)) {
            $fieldErrors['new_password'] = $passwordErrors[0];
        } elseif ($currentPassword !== '' && hash_equals($currentPassword, $newPassword)) {
            $fieldErrors['new_password'] = 'New password must be different from your current password.';
        }

        if ($confirmPassword === '') {
            $fieldErrors['confirm_password'] = 'Please confirm your new password.';
        } elseif ($newPassword !== $confirmPassword) {
            $fieldErrors['confirm_password'] = 'Passwords do not match.';
        }

        if (!$fieldErrors) {
            try {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                // A concurrent password change must not be overwritten.
                $update = $conn->prepare("
                    UPDATE users SET password = ?
                    WHERE id = ? AND password = ? AND role_id = 3 AND status = 'active'
                ");
                $oldHash = $user['password'];
                $update->bind_param('sis', $newHash, $userId, $oldHash);
                $update->execute();
                $changed = $update->affected_rows === 1;
                $update->close();
                if ($changed) {
                    session_regenerate_id(true);
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $csrfToken = $_SESSION['csrf_token'];
                    $success = 'Your password has been updated successfully.';
                } else {
                    $formError = 'Your account changed. Please refresh and try again.';
                }
            } catch (Throwable $error) {
                error_log('Account password update failed: ' . $error->getMessage());
                $formError = 'Unable to update your password. Please try again.';
            }
        }
    }
}

// =====================================================
// ACCOUNT DISPLAY INFORMATION
// =====================================================

$firstName = trim((string) ($user['first_name'] ?? ''));
$lastName = trim((string) ($user['last_name'] ?? ''));

$name = trim($firstName . ' ' . $lastName);

if ($name === '') {
    $name = 'Customer';
}

$email = (string) $user['email'];

$initials = secInitials($name);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Security Settings | PureVia</title>
    


    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/search.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/security.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<main class="sec-page">

    <div class="sec-container">

        <div class="sec-page-heading">
            <h1>My Account</h1>
            <p>Manage your profile, addresses, and preferences</p>
        </div>

        <div class="sec-layout">

            <!-- ACCOUNT SIDEBAR -->

            <aside class="sec-sidebar">

                <div class="sec-user">

                    <div class="sec-avatar">
                        <?= secEsc($initials) ?>
                    </div>

                    <strong class="sec-user-name">
                        <?= secEsc($name) ?>
                    </strong>

                    <span class="sec-user-email">
                        <?= secEsc($email) ?>
                    </span>

                    <span class="sec-role">CUSTOMER</span>

                </div>

                <nav class="sec-nav" aria-label="Account navigation">

                    <a href="<?= BASE_URL ?>/account/profile.php">
                        <span>👤</span>
                        Profile
                    </a>

                    <a href="<?= BASE_URL ?>/account/addresses.php">
                        <span>📍</span>
                        Addresses
                    </a>

                    <a href="<?= BASE_URL ?>/account/payment-cards.php">
                        <span aria-hidden="true">&#9635;</span>
                        Payment Cards
                    </a>

                    <a href="<?= BASE_URL ?>/account/my-reviews.php">
                        <span aria-hidden="true">&#9734;</span>
                        My Reviews
                    </a>

                    <a href="<?= BASE_URL ?>/account/skin-profile.php">
                        <span>✨</span>
                        Skin Profile
                    </a>

                    <a href="<?= BASE_URL ?>/account/security.php"
                       class="active"
                       aria-current="page">
                        <span>🔒</span>
                        Security
                    </a>

                    <div class="sec-nav-divider"></div>

                    <a href="<?= BASE_URL ?>/public/my-orders.php">
                        <span>📦</span>
                        My Orders
                    </a>

                    <a href="<?= BASE_URL ?>/public/shop.php">
                        <span>🛒</span>
                        Shop
                    </a>

                </nav>

            </aside>

            <!-- SECURITY CONTENT -->

            <section class="sec-content">

                <h2>Security Settings</h2>

                <div class="sec-card">

                    <h3>Change Password</h3>

                    <p class="sec-field-error" id="secFormError" role="alert" <?= $formError === '' ? 'hidden' : '' ?>><?= secEsc($formError) ?></p>

                    <?php if ($success !== ''): ?>

                        <div class="sec-alert sec-alert-success" role="status">
                            <i class="fa-solid fa-circle-check"></i>
                            <?= secEsc($success) ?>
                        </div>

                    <?php endif; ?>

                    <form novalidate
                        id="secPasswordForm"
                        action="<?= BASE_URL ?>/account/security.php"
                        method="POST"
                        autocomplete="on"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= secEsc($csrfToken) ?>"
                        >

                        <!-- CURRENT PASSWORD -->

                        <div class="sec-field">

                            <label for="currentPassword">
                                Current Password
                            </label>

                            <div class="sec-input-wrap">

                                <input
                                    type="password"
                                    id="currentPassword"
                                    name="current_password"
                                    placeholder="Enter your current password"
                                    autocomplete="current-password"
                                    aria-invalid="<?= isset($fieldErrors['current_password']) ? 'true' : 'false' ?>"
                                    aria-describedby="currentPasswordError"
                                    aria-required="true"
                                >

                                <button
                                    type="button"
                                    class="sec-toggle-password"
                                    data-target="currentPassword"
                                    aria-controls="currentPassword"
                                    hidden
                                    aria-label="Show current password"
                                    aria-pressed="false"
                                >
                                    <div class="sec-password-icon" aria-hidden="true">
                                    <!-- Font Awesome Free 7.2.0, Fonticons, Inc. https://fontawesome.com/license/free -->
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" focusable="false">
                                    <path d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z"/>
                                    </svg>
                                    </div>
                                </button>

                            </div>

                            <span class="sec-field-error" id="currentPasswordError" role="alert" <?= isset($fieldErrors['current_password']) ? '' : 'hidden' ?>><?= secEsc($fieldErrors['current_password'] ?? '') ?></span>
                        </div>

                        <!-- NEW PASSWORD -->

                        <div class="sec-field">

                            <label for="newPassword">
                                New Password
                            </label>

                            <div class="sec-input-wrap">

                                <input
                                    type="password"
                                    id="newPassword"
                                    name="new_password"
                                    placeholder="At least <?= (int) $passwordPolicy['password_min_length'] ?> characters"
                                    autocomplete="new-password"
                                    minlength="<?= (int) $passwordPolicy['password_min_length'] ?>"
                                    maxlength="72"
                                    aria-invalid="<?= isset($fieldErrors['new_password']) ? 'true' : 'false' ?>"
                                    aria-describedby="newPasswordError newPasswordFeedback"
                                    aria-required="true"
                                >

                                <button
                                    type="button"
                                    class="sec-toggle-password"
                                    data-target="newPassword"
                                    aria-controls="newPassword"
                                    hidden
                                    aria-label="Show new password"
                                    aria-pressed="false"
                                >
                                    <div class="sec-password-icon" aria-hidden="true">
                                    <!-- Font Awesome Free 7.2.0, Fonticons, Inc. https://fontawesome.com/license/free -->
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" focusable="false">
                                    <path d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z"/>
                                    </svg>
                                    </div>
                                </button>

                            </div>

                            <span class="sec-field-error" id="newPasswordError" role="alert" <?= isset($fieldErrors['new_password']) ? '' : 'hidden' ?>><?= secEsc($fieldErrors['new_password'] ?? '') ?></span>
                            <div class="sec-password-feedback" id="newPasswordFeedback" hidden>
                        <p class="sec-password-strength">
                            Password strength:
                            <strong id="newPasswordStrength" data-level="empty" aria-live="polite" aria-atomic="true">Not set</strong>
                        </p>
                        <ul class="sec-password-checklist">
                            <?php foreach (securityPasswordRequirements($passwordPolicy) as $passwordRuleKey => $passwordRuleLabel): ?>
                                <li data-password-rule="<?= secEsc($passwordRuleKey) ?>" data-met="false">
                                    <div class="sec-rule-icon" aria-hidden="true">
                                        <svg class="sec-rule-check" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" focusable="false">
                                            <!-- Font Awesome Free 7.2.0, Fonticons, Inc. https://fontawesome.com/license/free -->
                                            <path d="M530.8 134.1C545.1 144.5 548.3 164.5 537.9 178.8L281.9 530.8C276.4 538.4 267.9 543.1 258.5 543.9C249.1 544.7 240 541.2 233.4 534.6L105.4 406.6C92.9 394.1 92.9 373.8 105.4 361.3C117.9 348.8 138.2 348.8 150.7 361.3L252.2 462.8L486.2 141.1C496.6 126.8 516.6 123.6 530.9 134z"/>
                                        </svg>
                                        <svg class="sec-rule-x" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" focusable="false">
                                            <!-- Font Awesome Free 7.3.1, Fonticons, Inc. https://fontawesome.com/license/free -->
                                            <path d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"/>
                                        </svg>
                                    </div>
                                    <span><?= secEsc($passwordRuleLabel) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                        </div>

                        <!-- CONFIRM PASSWORD -->

                        <div class="sec-field">

                            <label for="confirmPassword">
                                Confirm New Password
                            </label>

                            <div class="sec-input-wrap">

                                <input
                                    type="password"
                                    id="confirmPassword"
                                    name="confirm_password"
                                    placeholder="Re-enter new password"
                                    autocomplete="new-password"
                                    minlength="<?= (int) $passwordPolicy['password_min_length'] ?>"
                                    maxlength="72"
                                    aria-invalid="<?= isset($fieldErrors['confirm_password']) ? 'true' : 'false' ?>"
                                    aria-describedby="confirmPasswordError"
                                    aria-required="true"
                                >

                                <button
                                    type="button"
                                    class="sec-toggle-password"
                                    data-target="confirmPassword"
                                    aria-controls="confirmPassword"
                                    hidden
                                    aria-label="Show confirmed password"
                                    aria-pressed="false"
                                >
                                    <div class="sec-password-icon" aria-hidden="true">
                                    <!-- Font Awesome Free 7.2.0, Fonticons, Inc. https://fontawesome.com/license/free -->
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" fill="currentColor" focusable="false">
                                    <path d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z"/>
                                    </svg>
                                    </div>
                                </button>

                            </div>

                            <span class="sec-field-error" id="confirmPasswordError" role="alert" <?= isset($fieldErrors['confirm_password']) ? '' : 'hidden' ?>><?= secEsc($fieldErrors['confirm_password'] ?? '') ?></span>

                        </div>

                        <button
                            type="submit"
                            class="sec-submit"
                            id="secSubmitButton"
                        >
                            Update Password
                        </button>

                    </form>

                </div>

            </section>

        </div>

    </div>

</main>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/security.js"></script>

<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
