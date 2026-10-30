<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* =========================================
   AUTHENTICATION
========================================= */

if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

/* =========================================
   HELPER
========================================= */

function e(mixed $value): string {
    return pvHtml(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

/* =========================================
   FETCH CUSTOMER INFORMATION
========================================= */

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT
        id,
        first_name,
        last_name,
        email,
        phone,
        gender,
        birth_date,
        profile_photo
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

/* =========================================
   USER DISPLAY INFORMATION
========================================= */

$firstName = (string) $user['first_name'];
$lastName = (string) $user['last_name'];

$fullName = trim($firstName . ' ' . $lastName);

$initials = strtoupper(
    mb_substr($firstName, 0, 1) .
    mb_substr($lastName, 0, 1)
);

/* =========================================
   PROFILE PHOTO
========================================= */

$photoUrl = null;

if (!empty($user['profile_photo'])) {

    $filename = basename($user['profile_photo']);

    $photoPath = __DIR__ . '/../uploads/profiles/' . $filename;

    if (is_file($photoPath)) {

        $photoUrl = BASE_URL .
            '/uploads/profiles/' .
            rawurlencode($filename);
    }
}

/* =========================================
   SESSION AND CSRF
========================================= */

$_SESSION['role'] = 'customer';
$_SESSION['name'] = $fullName;
$_SESSION['email'] = $user['email'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =========================================
   FLASH MESSAGES
========================================= */

$fieldErrors = $_SESSION['profile_field_errors'] ?? [];
$oldProfile = $_SESSION['profile_old'] ?? [];
$photoError = (string) ($_SESSION['profile_photo_error'] ?? '');
unset($_SESSION['profile_photo_error']);
unset($_SESSION['profile_field_errors'], $_SESSION['profile_old']);
foreach (['first_name','last_name','phone','gender','birth_date'] as $field) {
    if (isset($oldProfile[$field]) && is_string($oldProfile[$field])) $user[$field] = $oldProfile[$field];
}
$firstName = $user['first_name'];
$lastName = $user['last_name'];
$error = $fieldErrors ? '' : (string) ($_SESSION['profile_error'] ?? '');
$success = (string) ($_SESSION['profile_success'] ?? '');

unset(
    $_SESSION['profile_error'],
    $_SESSION['profile_success']
);

/* =========================================
   GENDER OPTIONS
========================================= */

$genderOptions = [
    'Female',
    'Male',
    'Non-binary',
    'Prefer not to say'
];

$currentGender = (string) ($user['gender'] ?? '');

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Profile | PureVia</title>

    <!-- Bootstrap 5 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/header.css"
    >

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/search.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/assets/css/account.css"
    >

    <!-- Responsive adjustments -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
</head>

<body>

<?php require __DIR__ . '/../includes/header.php'; ?>

<main class="account-page">

    <!-- PAGE TITLE -->

    <div class="pv-account-intro">

        <h1>My Account</h1>

        <p>
            Manage your profile, addresses, and preferences
        </p>

    </div>

    <div class="account-layout">

        <!-- =====================================
             ACCOUNT SIDEBAR
        ====================================== -->

        <aside class="account-sidebar">

            <div class="account-user">

                <div class="account-user-avatar">

                    <?php if ($photoUrl): ?>

                        <img
                            src="<?= e($photoUrl) ?>"
                            alt="Your profile photo"
                        >

                    <?php else: ?>

                        <?= e($initials) ?>

                    <?php endif; ?>

                </div>

                <h3><?= e($fullName) ?></h3>

                <p><?= e($user['email']) ?></p>

                <span class="account-role">
                    CUSTOMER
                </span>

            </div>

            <div class="account-sidebar-divider"></div>

            <nav
                class="account-sidebar-nav"
                aria-label="Account navigation"
            >

                <a
                    href="<?= BASE_URL ?>/account/profile.php"
                    class="account-nav-item active"
                    aria-current="page"
                >
                    <span>👤</span>
                    Profile
                </a>

                <a
                    href="<?= BASE_URL ?>/account/addresses.php"
                    class="account-nav-item"
                >
                    <span>📍</span>
                    Addresses
                </a>

                <a href="<?= BASE_URL ?>/account/payment-cards.php"
                   class="account-nav-item">
                    <span aria-hidden="true">&#9635;</span> Payment Cards
                </a>

                <a href="<?= BASE_URL ?>/account/my-reviews.php"
                   class="account-nav-item">
                    <span aria-hidden="true">&#9734;</span> My Reviews
                </a>

                <a
                    href="<?= BASE_URL ?>/account/skin-profile.php"
                    class="account-nav-item"
                >
                    <span>✨</span>
                    Skin Profile
                </a>

                <a
                    href="<?= BASE_URL ?>/account/security.php"
                    class="account-nav-item"
                >
                    <span>🔒</span>
                    Security
                </a>

                <div class="account-sidebar-divider"></div>

                <a
                    href="<?= BASE_URL ?>/my-orders.php"
                    class="account-nav-item"
                >
                    <span>📦</span>
                    My Orders
                </a>

                <a
                    href="<?= BASE_URL ?>/shop.php"
                    class="account-nav-item"
                >
                    <span>🛒</span>
                    Shop
                </a>

            </nav>

        </aside>

        <!-- =====================================
             MAIN ACCOUNT CONTENT
        ====================================== -->

        <section class="account-content">

            <div class="account-content-header">

                <h2>Personal Information</h2>

            </div>

            <!-- ALERTS -->

            <?php if ($error !== ''): ?>

                <div
                    class="account-form-error"
                    role="alert"
                >
                    <?= e($error) ?>
                </div>

            <?php endif; ?>

            <?php if ($success !== ''): ?>

                <div
                    class="account-alert success"
                    role="status"
                >
                    <?= e($success) ?>
                </div>

            <?php endif; ?>

            <!-- =====================================
                 TWO-COLUMN EDIT LAYOUT
            ====================================== -->

            <div class="account-profile-grid account-edit-grid">

                <!-- =================================
                     LEFT: PERSONAL INFORMATION FORM
                ================================== -->

                <div class="account-edit-card">

                    <form novalidate
                        method="POST"
                        action="<?= BASE_URL ?>/actions/account/update_profile.php"
                        id="profileEditForm"
                        data-field-errors="<?= e(pvJson($fieldErrors, JSON_THROW_ON_ERROR)) ?>"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']) ?>"
                        >

                        <div class="account-edit-form">

                            <!-- FULL NAME -->

                            <div class="account-form-group">

                                <label>
                                    Full Name
                                </label>

                                <div class="account-name-fields">

                                    <input
                                        type="text"
                                        name="first_name"
                                        id="firstName"
                                        value="<?= e($firstName) ?>"
                                        placeholder="First Name"
                                        aria-label="First Name"
                                        maxlength="100"
                                        autocomplete="given-name"
                                        aria-required="true"
                                    >

                                    <input
                                        type="text"
                                        name="last_name"
                                        id="lastName"
                                        value="<?= e($lastName) ?>"
                                        placeholder="Last Name"
                                        aria-label="Last Name"
                                        maxlength="100"
                                        autocomplete="family-name"
                                        aria-required="true"
                                    >

                                </div>

                            </div>

                            <!-- EMAIL ADDRESS -->

                            <div class="account-form-group">

                                <label for="email">
                                    Email Address
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    value="<?= e($user['email']) ?>"
                                    readonly
                                >

                                <small>
                                    Email cannot be changed
                                </small>

                            </div>

                            <!-- PHONE NUMBER -->

                            <div class="account-form-group">

                                <label for="phone">
                                    Phone Number
                                </label>

                                <input
                                    type="tel"
                                    name="phone"
                                    id="phone"
                                    value="<?= e($user['phone']) ?>"
                                    placeholder="+63 9XX XXX XXXX"
                                    maxlength="30"
                                    autocomplete="tel"
                                >

                            </div>

                            <!-- GENDER -->

                            <div class="account-form-group">

                                <label for="gender">
                                    Gender
                                    <span class="account-optional">
                                        (Optional)
                                    </span>
                                </label>

                                <select
                                    name="gender"
                                    id="gender"
                                >

                                    <option
                                        value=""
                                        <?= $currentGender === '' ? 'selected' : '' ?>
                                    >
                                        Select gender
                                    </option>

                                    <?php foreach ($genderOptions as $option): ?>

                                        <option
                                            value="<?= e($option) ?>"
                                            <?= $currentGender === $option ? 'selected' : '' ?>
                                        >
                                            <?= e($option) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                            <!-- BIRTH DATE -->

                            <div class="account-form-group">

                                <label for="birthDate">
                                    Birth Date
                                    <span class="account-optional">
                                        (Optional)
                                    </span>
                                </label>

                                <input
                                    type="date"
                                    name="birth_date"
                                    id="birthDate"
                                    value="<?= e($user['birth_date']) ?>"
                                    min="1900-01-01"
                                    max="<?= date('Y-m-d') ?>"
                                >

                            </div>

                            <!-- FORM ACTIONS -->

                            <div class="account-form-actions">

                                <button
                                    type="submit"
                                    class="account-save-btn"
                                    id="saveProfileBtn"
                                >
                                    Save Changes
                                </button>

                                <a
                                    href="<?= BASE_URL ?>/account/profile.php"
                                    class="account-cancel-btn"
                                >
                                    Cancel
                                </a>

                            </div>

                        </div>

                    </form>

                </div>

                <!-- =================================
                     RIGHT: PROFILE PHOTO CARD
                ================================== -->

                <aside class="account-photo-card">

                    <span class="account-photo-eyebrow">
                        PROFILE PHOTO
                    </span>

                    <h3>Avatar</h3>

                    <p>
                        Add a photo so your account
                        feels more personal.
                    </p>

                    <!-- CURRENT PHOTO / INITIALS -->

                    <div
                        class="account-photo-avatar"
                        id="profilePhotoPreview"
                    >

                        <?php if ($photoUrl): ?>

                            <img
                                src="<?= e($photoUrl) ?>"
                                alt="Current profile photo"
                            >

                        <?php else: ?>

                            <span>
                                <?= e($initials) ?>
                            </span>

                        <?php endif; ?>

                    </div>

                    <!-- PHOTO UPLOAD FORM -->

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

                    <small>
                        JPG, PNG, or WebP. Maximum 5 MB.
                    </small>

                </aside>

            </div>

        </section>

    </div>

</main>

<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/account.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/search.js?v=public-root-1"></script>

<script nonce="<?= pvScriptNonce() ?>" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
