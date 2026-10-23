<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/public/index.php');
    exit;
}

function e(mixed $value): string {
    return pvHtml((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function maskEmail(string $email): string {
    $parts = explode('@', $email, 2);

    if (count($parts) !== 2) {
        return 'Not provided';
    }

    $name = $parts[0];
    $visible = substr($name, 0, min(2, strlen($name)));

    return $visible . '**********@' . $parts[1];
}

function maskPhone(?string $phone): string {
    $digits = preg_replace('/\D/', '', (string) $phone);

    if ($digits === '') {
        return 'Not provided';
    }

    return str_repeat('*', max(0, strlen($digits) - 2))
        . substr($digits, -2);
}

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, first_name, last_name, email, phone,
           gender, birth_date, profile_photo,
           status, created_at
    FROM users
    WHERE id = ?
      AND role_id = 3
      AND status = 'active'
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(403);
    exit('Access denied.');
}

$fullName = trim($user['first_name'] . ' ' . $user['last_name']);

$initials = strtoupper(
    mb_substr($user['first_name'], 0, 1) .
    mb_substr($user['last_name'], 0, 1)
);

$gender = trim((string) ($user['gender'] ?? ''));
$genderDisplay = $gender !== '' ? $gender : 'Not provided';

$birthDate = $user['birth_date'] ?? null;
$birthDisplay = $birthDate
    ? date('F j, Y', strtotime($birthDate))
    : 'Not provided';

$memberSince = date('Y-m-d', strtotime($user['created_at']));

$photoUrl = null;

if (!empty($user['profile_photo'])) {
    $filename = basename($user['profile_photo']);
    $photoPath = __DIR__ . '/../uploads/profiles/' . $filename;

    if (is_file($photoPath)) {
        $photoUrl = BASE_URL . '/uploads/profiles/' .
            rawurlencode($filename);
    }
}

$_SESSION['role'] = 'customer';
$_SESSION['name'] = $fullName;
$_SESSION['email'] = $user['email'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = (string) ($_SESSION['profile_success'] ?? '');
$error = (string) ($_SESSION['profile_error'] ?? '');
$photoError = (string) ($_SESSION['profile_photo_error'] ?? '');
unset($_SESSION['profile_photo_error']);

unset($_SESSION['profile_success'], $_SESSION['profile_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | PureVia</title>

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

    <div class="pv-account-intro">
        <h1>My Account</h1>
        <p>Manage your profile, addresses, and preferences</p>
    </div>

    <div class="account-layout">

        <aside class="account-sidebar">

            <div class="account-user">

                <div class="account-user-avatar">
                    <?php if ($photoUrl): ?>
                        <img src="<?= e($photoUrl) ?>"
                             alt="Your profile photo">
                    <?php else: ?>
                        <?= e($initials) ?>
                    <?php endif; ?>
                </div>

                <h3><?= e($fullName) ?></h3>
                <p><?= e($user['email']) ?></p>
                <span class="account-role">CUSTOMER</span>

            </div>

            <div class="account-sidebar-divider"></div>

            <nav class="account-sidebar-nav"
                 aria-label="Account navigation">

                <a href="<?= BASE_URL ?>/account/profile.php"
                   class="account-nav-item active"
                   aria-current="page">
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

        <section class="account-content">

            <div class="account-content-header">
                <h2>Personal Information</h2>

                <a href="<?= BASE_URL ?>/account/edit_profile.php"
                   class="account-edit-btn">
                    Edit
                </a>
            </div>

            <?php if ($success !== ''): ?>
                <div class="account-alert success" role="status">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="account-form-error" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="account-profile-grid">

                <div class="account-info-card">

                    <div class="account-info-row">
                        <span class="account-info-label">FULL NAME</span>
                        <span class="account-info-value">
                            <?= e($fullName) ?>
                        </span>
                    </div>

                    <div class="account-info-row">
                        <span class="account-info-label">EMAIL ADDRESS</span>
                        <span class="account-info-value">
                            <?= e(maskEmail($user['email'])) ?>
                        </span>
                    </div>

                    <div class="account-info-row">
                        <span class="account-info-label">PHONE</span>
                        <span class="account-info-value">
                            <?= e(maskPhone($user['phone'])) ?>
                        </span>
                    </div>

                    <div class="account-info-row">
                        <span class="account-info-label">GENDER</span>
                        <span class="account-info-value">
                            <?= e($genderDisplay) ?>
                        </span>
                    </div>

                    <div class="account-info-row">
                        <span class="account-info-label">BIRTH DATE</span>
                        <span class="account-info-value">
                            <?= e($birthDisplay) ?>
                        </span>
                    </div>

                    <div class="account-info-row">
                        <span class="account-info-label">MEMBER SINCE</span>
                        <span class="account-info-value">
                            <?= e($memberSince) ?>
                        </span>
                    </div>

                    <div class="account-info-row">
                        <span class="account-info-label">ACCOUNT STATUS</span>
                        <span class="account-info-value">
                            <?= e(ucfirst($user['status'])) ?>
                        </span>
                    </div>

                </div>

                <aside class="account-photo-card">

                    <span class="account-photo-eyebrow">
                        PROFILE PHOTO
                    </span>

                    <h3>Avatar</h3>

                    <p>
                        Add a photo so your account feels more personal.
                    </p>

                    <div class="account-photo-avatar"
                         id="profilePhotoPreview">

                        <?php if ($photoUrl): ?>
                            <img src="<?= e($photoUrl) ?>"
                                 alt="Current profile photo">
                        <?php else: ?>
                            <span><?= e($initials) ?></span>
                        <?php endif; ?>

                    </div>

                    <form novalidate
                        id="profilePhotoForm"
                        data-field-errors="<?= e(pvJson(['profile_photo' => $photoError], JSON_THROW_ON_ERROR)) ?>"
                        method="POST"
                        enctype="multipart/form-data"
                        action="<?= BASE_URL ?>/actions/account/profile_photo.php"
                    >
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>"
                        >

                        <input
                            type="file"
                            name="profile_photo"
                            id="profilePhotoInput"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            hidden
                            aria-required="true"
                        >

                        <button
                            type="button"
                            id="uploadPhotoBtn"
                            class="account-photo-upload-btn"
                        >
                            Upload photo
                        </button>

                    </form>

                    <small>JPG, PNG, or WebP. Maximum 5 MB.</small>

                </aside>

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
