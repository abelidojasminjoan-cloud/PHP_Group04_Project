<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/StaffPassword.php';

$user = requireLogin($conn);
$userId = (int) $user['id'];
$policy = securityPolicy($conn);
$destination = BASE_URL . match ((int) $user['role_id']) {
    1 => '/admin/dashboard.php',
    2 => '/staff/dashboard.php',
    default => '/index.php',
};

$stmt = $conn->prepare('SELECT password, password_changed_at, created_at FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$passwordRecord = $stmt->get_result()->fetch_assoc();
$stmt->close();

$initialStaffPassword = $passwordRecord && pureViaStaffNeedsPasswordChange($passwordRecord + ['role_id' => $user['role_id']]);
if (!$passwordRecord || (!securityPasswordIsExpired($passwordRecord, $policy) && !$initialStaffPassword)) {
    header('Location: ' . $destination, true, 303);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
    $password = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
    $confirmation = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    if (!password_verify($current, $passwordRecord['password'])) {
        $error = 'Your current password is incorrect.';
    } elseif ($errors = validatePureViaPassword($password, $policy)) {
        $error = $errors[0];
    } elseif (password_verify($password, $passwordRecord['password'])) {
        $error = 'New password must be different from your current password.';
    } elseif ((int) $user['role_id'] === 2 && (string) ($_ENV['STAFF_DEFAULT_PASSWORD'] ?? '') !== '' && hash_equals((string) $_ENV['STAFF_DEFAULT_PASSWORD'], $password)) {
        $error = 'Choose a personal password instead of the shared initial password.';
    } elseif ($password !== $confirmation) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $oldHash = $passwordRecord['password'];
            $update = $conn->prepare("UPDATE users SET password = ?, password_changed_at = NOW()
                WHERE id = ? AND password = ? AND status = 'active'");
            $update->bind_param('sis', $hash, $userId, $oldHash);
            $update->execute();
            $changed = $update->affected_rows === 1;
            $update->close();
            if ($changed) {
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: ' . $destination, true, 303);
                exit;
            }
            $error = 'Your account changed. Please refresh and try again.';
        } catch (Throwable $exception) {
            error_log('Password renewal failed: ' . $exception->getMessage());
            $error = 'Unable to update your password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renew Password | PureVia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css">
</head>
<body class="auth-page">
<main class="auth-shell">
    <section class="auth-visual" aria-label="PureVia skincare">
        <a class="auth-brand" href="<?= BASE_URL ?>/index.php" aria-label="PureVia home">
            <img src="<?= BASE_URL ?>/assets/images/logo/purevia_logo_1.png" alt="PureVia" width="168" height="168">
        </a>
        <h2 class="auth-visual-title">Skincare,<br>made simple.</h2>
    </section>
    <section class="auth-content">
        <div class="auth-form-area">
            <div class="auth-heading">
                <p class="auth-eyebrow">ACCOUNT SECURITY</p>
                <h1><?= $initialStaffPassword ? 'Change your initial password' : 'Renew your password' ?></h1>
                <p class="auth-subtitle"><?= $initialStaffPassword ? 'Enter your current staff password, then create a unique password that only you know.' : 'Your password has expired. Choose a new password to continue.' ?></p>
            </div>
            <p class="auth-form-error" role="alert" <?= $error === '' ? 'hidden' : '' ?>><?= pvHtml($error) ?></p>
            <form method="POST" action="<?= BASE_URL ?>/account/renew-password.php" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= pvHtml(csrfToken()) ?>">
                <div class="auth-field">
                    <label for="renewCurrentPassword">Current Password</label>
                    <input type="password" id="renewCurrentPassword" name="current_password" autocomplete="current-password" required>
                </div>
                <div class="auth-field">
                    <label for="renewNewPassword">New Password</label>
                    <input type="password" id="renewNewPassword" name="new_password" autocomplete="new-password" minlength="<?= (int) $policy['password_min_length'] ?>" aria-describedby="renewRequirements" required>
                </div>
                <ul id="renewRequirements" class="auth-subtitle">
                    <?php foreach (securityPasswordRequirements($policy) as $requirement): ?>
                        <li><?= pvHtml($requirement) ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="auth-field">
                    <label for="renewConfirmPassword">Confirm New Password</label>
                    <input type="password" id="renewConfirmPassword" name="confirm_password" autocomplete="new-password" required>
                </div>
                <button type="submit" class="auth-submit">Update Password</button>
            </form>
            <p class="auth-switch-text"><a href="<?= BASE_URL ?>/actions/auth/logout.php">Sign out</a></p>
        </div>
    </section>
</main>
</body>
</html>
