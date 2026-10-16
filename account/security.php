<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

function secEsc(mixed $value): string
{
    return htmlspecialchars(
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

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($csrfToken, $submittedToken)) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (
            $currentPassword === '' ||
            $newPassword === '' ||
            $confirmPassword === ''
        ) {
            $errors[] = 'Please complete all password fields.';
        }

        if (
            $currentPassword !== '' &&
            !password_verify($currentPassword, (string) $user['password'])
        ) {
            $errors[] = 'Your current password is incorrect.';
        }

        if (strlen($newPassword) < 12) {
            $errors[] = 'New password must contain at least 12 characters.';
        }

        if (strlen($newPassword) > 128) {
            $errors[] = 'New password must not exceed 128 bytes.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }

        if (
            $currentPassword !== '' &&
            $newPassword !== '' &&
            hash_equals($currentPassword, $newPassword)
        ) {
            $errors[] = 'New password must be different from your current password.';
        }

        if (!$errors) {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $update = $conn->prepare("
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");

            $update->bind_param('si', $newHash, $userId);

            if ($update->execute()) {
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $csrfToken = $_SESSION['csrf_token'];

                $success = 'Your password has been updated successfully.';
            } else {
                $errors[] = 'Unable to update your password. Please try again.';
            }

            $update->close();
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

                    <a href="<?= BASE_URL ?>/my-orders.php">
                        <span>📦</span>
                        My Orders
                    </a>

                    <a href="<?= BASE_URL ?>/shop.php">
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

                    <?php if ($errors): ?>

                        <div class="sec-alert sec-alert-error" role="alert">
                            <?php foreach ($errors as $error): ?>
                                <p><?= secEsc($error) ?></p>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                    <?php if ($success !== ''): ?>

                        <div class="sec-alert sec-alert-success" role="status">
                            <i class="fa-solid fa-circle-check"></i>
                            <?= secEsc($success) ?>
                        </div>

                    <?php endif; ?>

                    <form
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
                                    autocomplete="current-password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="sec-toggle-password"
                                    data-target="currentPassword"
                                    hidden
                                    aria-label="Show current password"
                                    aria-pressed="false"
                                >
                                    <i class="fa-regular fa-eye"></i>
                                </button>

                            </div>

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
                                    placeholder="Min. 12 characters"
                                    autocomplete="new-password"
                                    minlength="12"
                                    maxlength="128"
                                    required
                                >

                                <button
                                    type="button"
                                    class="sec-toggle-password"
                                    data-target="newPassword"
                                    hidden
                                    aria-label="Show new password"
                                    aria-pressed="false"
                                >
                                    <i class="fa-regular fa-eye"></i>
                                </button>

                            </div>

                            <p class="sec-field-hint" id="secPasswordHint">
                                Use at least 12 characters.
                            </p>

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
                                    minlength="12"
                                    maxlength="128"
                                    required
                                >

                                <button
                                    type="button"
                                    class="sec-toggle-password"
                                    data-target="confirmPassword"
                                    hidden
                                    aria-label="Show confirmed password"
                                    aria-pressed="false"
                                >
                                    <i class="fa-regular fa-eye"></i>
                                </button>

                            </div>

                            <p class="sec-field-hint" id="secConfirmHint"></p>

                        </div>

                        <p class="sec-client-error"
                           id="secClientError"
                           role="alert"
                           hidden></p>

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

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script src="<?= BASE_URL ?>/assets/js/security.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
