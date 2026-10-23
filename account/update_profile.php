<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, role_id, first_name, last_name,
           email, phone, status
    FROM users
    WHERE id = ? AND role_id = 3 AND status = 'active'
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(403);
    exit('Access denied.');
}


function e(mixed $value): string
{
    return pvHtml(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


$fullName = trim(
    $user['first_name'] . ' ' . $user['last_name']
);

$initials = strtoupper(
    substr($user['first_name'], 0, 1) .
    substr($user['last_name'], 0, 1)
);

$_SESSION['role'] = 'customer';
$_SESSION['name'] = $fullName;
$_SESSION['email'] = $user['email'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$fieldErrors = $_SESSION['profile_field_errors'] ?? [];
$oldProfile = $_SESSION['profile_old'] ?? [];
unset($_SESSION['profile_field_errors'], $_SESSION['profile_old']);
foreach (['first_name','last_name','phone'] as $field) {
    if (isset($oldProfile[$field]) && is_string($oldProfile[$field])) $user[$field] = $oldProfile[$field];
}
$error = $fieldErrors ? '' : (string) ($_SESSION['profile_error'] ?? '');
unset($_SESSION['profile_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile | PureVia</title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/header.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/search.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet"
          href="<?= BASE_URL ?>/assets/css/account.css">
    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="account-page">

    <div class="account-layout">

        <!-- ACCOUNT SIDEBAR -->
        <aside class="account-sidebar">

            <div class="account-user">

                <div class="account-user-avatar">
                    <?= e($initials) ?>
                </div>

                <h3><?= e($fullName) ?></h3>

                <p><?= e($user['email']) ?></p>

                <span class="account-role">
                    CUSTOMER
                </span>

            </div>

            <div class="account-sidebar-divider"></div>

            <nav class="account-sidebar-nav"
                 aria-label="Account navigation">

                <a href="<?= BASE_URL ?>/account/profile.php"
                   class="account-nav-item active">
                    <span>👤</span> Profile
                </a>

                <a href="<?= BASE_URL ?>/account/addresses.php"
                   class="account-nav-item">
                    <span>📍</span> Addresses
                </a>

                <a href="<?= BASE_URL ?>/account/payment-cards.php"
                   class="account-nav-item">
                    <span aria-hidden="true">&#9635;</span> Payment Cards
                </a>

                <a href="<?= BASE_URL ?>/account/my-reviews.php"
                   class="account-nav-item">
                    <span aria-hidden="true">&#9734;</span> My Reviews
                </a>

                <a href="<?= BASE_URL ?>/account/skin-profile.php"
                   class="account-nav-item">
                    <span>✨</span> Skin Profile
                </a>

                <a href="<?= BASE_URL ?>/account/security.php"
                   class="account-nav-item">
                    <span>🔒</span> Security
                </a>

                <div class="account-sidebar-divider"></div>

                <a href="<?= BASE_URL ?>/public/my-orders.php"
                   class="account-nav-item">
                    <span>📦</span> My Orders
                </a>

                <a href="<?= BASE_URL ?>/public/shop.php"
                   class="account-nav-item">
                    <span>🛒</span> Shop
                </a>

            </nav>

        </aside>

        <!-- EDIT PROFILE CONTENT -->
        <section class="account-content">

            <div class="account-content-header">
                <h1>Personal Information</h1>
            </div>

            <?php if ($error): ?>
                <div class="account-form-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="account-edit-card">

                <form novalidate method="POST"
                      action="<?= BASE_URL ?>/actions/account/update_profile.php"
                      id="profileEditForm"
                      data-field-errors="<?= e(pvJson($fieldErrors, JSON_THROW_ON_ERROR)) ?>">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="account-edit-form">

                        <div class="account-form-group">

                            <label>Full Name</label>

                            <div class="account-name-fields">

                                <input type="text"
                                       name="first_name"
                                       id="firstName"
                                       value="<?= e($user['first_name']) ?>"
                                       placeholder="First Name"
                                       maxlength="100"
                                       aria-label="First Name"
                                       aria-required="true">

                                <input type="text"
                                       name="last_name"
                                       id="lastName"
                                       value="<?= e($user['last_name']) ?>"
                                       placeholder="Last Name"
                                       maxlength="100"
                                       aria-label="Last Name"
                                       aria-required="true">

                            </div>

                        </div>

                        <div class="account-form-group">

                            <label for="email">
                                Email Address
                            </label>

                            <input type="email"
                                   id="email"
                                   value="<?= e($user['email']) ?>"
                                   readonly>

                            <small>
                                Email cannot be changed
                            </small>

                        </div>

                        <div class="account-form-group">

                            <label for="phone">
                                Phone Number
                            </label>

                            <input type="tel"
                                   name="phone"
                                   id="phone"
                                   maxlength="30"
                                   placeholder="+63 9XX XXX XXXX"
                                   value="<?= e($user['phone']) ?>">

                        </div>

                        <div class="account-form-actions">

                            <button type="submit"
                                    class="account-save-btn"
                                    id="saveProfileBtn">
                                Save Changes
                            </button>

                            <a href="<?= BASE_URL ?>/account/profile.php"
                               class="account-cancel-btn">
                                Cancel
                            </a>

                        </div>

                    </div>

                </form>

            </div>

        </section>

    </div>

</main>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/account.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/search.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
