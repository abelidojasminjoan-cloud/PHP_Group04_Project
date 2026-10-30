<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/role-check.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/ManageAccount.php';
require_once __DIR__ . '/../services/StaffPassword.php';
$authenticated = requirePureViaAdminOrStaff($conn);
$id = (int) $authenticated['id'];
$tab = ($_GET['tab'] ?? '') === 'password' ? 'password' : 'profile';
$editing = $tab === 'profile' && ($_GET['edit'] ?? '') === '1';
$error = ''; $old = [];
$url = BASE_URL . '/account/manage.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verifyCsrf();
    $action = $_POST['account_action'] ?? '';
    $tab = $action === 'password' ? 'password' : 'profile';
    try {
        if ($action === 'profile') {
            ManageAccount::profile($conn, $id, $_POST);
            $message = 'Your personal information has been updated.';
        } elseif ($action === 'password') {
            $_SESSION['session_version'] = ManageAccount::password($conn, $id, $_POST);
            session_regenerate_id(true);
            $message = 'Your password has been changed. Other sessions have been signed out.';
        } elseif ($action === 'photo' && is_array($_FILES['profile_photo'] ?? null)) {
            ManageAccount::photo($conn, $id, $_FILES['profile_photo']);
            $message = 'Your profile photo has been updated.';
        } else { throw new InvalidArgumentException('Choose a valid account action.'); }
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['manage_account_success'] = $message;
        header('Location: ' . $url . ($tab === 'password' ? '?tab=password' : ''), true, 303); exit;
    } catch (InvalidArgumentException|DomainException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log('Manage Account failed: ' . get_class($exception));
        $error = 'Unable to save your changes. Please try again.';
    }
    if ($action === 'profile') { $editing = true; $old = $_POST; }
}
$user = ManageAccount::details($conn, $id);
$success = (string) ($_SESSION['manage_account_success'] ?? ''); unset($_SESSION['manage_account_success']);
$policy = securityPolicy($conn); $csrf = csrfToken();
$initials = mb_strtoupper(mb_substr($user['first_name'],0,1) . mb_substr($user['last_name'],0,1));
$photo = basename((string) ($user['profile_photo'] ?? ''));
$photoUrl = $photo !== '' && is_file(UPLOAD_PATH . '/profiles/' . $photo) ? BASE_URL . '/uploads/profiles/' . rawurlencode($photo) : '';
$emailParts = explode('@', $user['email'], 2);
$maskedEmail = mb_substr($emailParts[0],0,2) . '**********@' . ($emailParts[1] ?? '');
function maEsc(mixed $value): string { return pvHtml((string) ($value ?? '')); }
function maValue(string $key): string { global $old, $user; return maEsc(is_string($old[$key] ?? null) ? $old[$key] : ($user[$key] ?? '')); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage Account | PureVia</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/inventory.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/sidemenu.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/manage-account.css?v=<?= filemtime(__DIR__.'/../assets/css/manage-account.css') ?>">
</head><body class="manage-account-page">
<?php require __DIR__.'/../includes/sidemenu.php'; ?>
<div class="admin-layout"><header class="admin-topbar"><p class="admin-topbar-title">Manage Account</p></header>
<main class="ma-main"><h1>Manage Account</h1>
<nav class="ma-tabs" aria-label="Manage account sections"><a href="<?= maEsc($url) ?>" <?= $tab==='profile'?'aria-current="page"':'' ?>>Personal Information</a><a href="<?= maEsc($url) ?>?tab=password" <?= $tab==='password'?'aria-current="page"':'' ?>>Change Password</a></nav>
<?php if ($success): ?><p class="ma-message ma-success" role="status"><?= maEsc($success) ?></p><?php endif; ?>
<?php if ($error): ?><p class="ma-message ma-error" role="alert"><?= maEsc($error) ?></p><?php endif; ?>
<?php if ($tab === 'profile'): ?>
<div class="ma-heading"><h2>Personal Information</h2><?php if (!$editing): ?><a class="ma-button ma-outline" href="<?= maEsc($url) ?>?edit=1">Edit</a><?php endif; ?></div>
<div class="ma-columns"><section class="ma-card ma-information" aria-label="Personal information">
<?php if (!$editing): ?>
<dl class="ma-details">
<div><dt>Full name</dt><dd><?= maEsc(trim($user['first_name'].' '.$user['last_name'])) ?></dd></div>
<div><dt>Email address</dt><dd><?= maEsc($maskedEmail) ?></dd></div>
<div><dt>Phone</dt><dd><?= maEsc($user['phone'] ?: 'Not provided') ?></dd></div>
<div><dt>Gender</dt><dd><?= maEsc($user['gender'] ?: 'Not provided') ?></dd></div>
<div><dt>Birth date</dt><dd><?= maEsc($user['birth_date'] ?: 'Not provided') ?></dd></div>
<div><dt>Member since</dt><dd><?= maEsc(date('Y-m-d',strtotime($user['created_at']))) ?></dd></div>
<div><dt>Account status</dt><dd><?= maEsc(ucfirst($user['status'])) ?></dd></div>
</dl>
<?php else: ?>
<form method="post" action="<?= maEsc($url) ?>?edit=1" class="ma-form">
<input type="hidden" name="csrf_token" value="<?= maEsc($csrf) ?>"><input type="hidden" name="account_action" value="profile">
<fieldset class="ma-name"><legend>Full Name</legend><div class="ma-name-inputs"><input name="first_name" aria-label="First name" autocomplete="given-name" value="<?= maValue('first_name') ?>" maxlength="100" required><input name="last_name" aria-label="Last name" autocomplete="family-name" value="<?= maValue('last_name') ?>" maxlength="100" required></div></fieldset>
<div class="ma-field"><label for="maEmail">Email Address</label><input id="maEmail" type="email" value="<?= maEsc($user['email']) ?>" readonly aria-describedby="maEmailHelp"><small id="maEmailHelp">Email cannot be changed</small></div>
<div class="ma-field"><label for="maPhone">Phone Number</label><input id="maPhone" name="phone" type="tel" autocomplete="tel" placeholder="+63 9XX XXX XXXX" value="<?= maValue('phone') ?>" maxlength="30"></div>
<div class="ma-field"><label for="maGender">Gender <span>(Optional)</span></label><select id="maGender" name="gender"><option value="">Select gender</option><?php foreach(['Female','Male','Non-binary','Prefer not to say'] as $gender): ?><option value="<?= maEsc($gender) ?>" <?= maValue('gender')===maEsc($gender)?'selected':'' ?>><?= maEsc($gender) ?></option><?php endforeach; ?></select></div>
<div class="ma-field"><label for="maBirth">Birth Date <span>(Optional)</span></label><input id="maBirth" name="birth_date" type="date" autocomplete="bday" min="1900-01-01" max="<?= date('Y-m-d') ?>" value="<?= maValue('birth_date') ?>"></div>
<div class="ma-actions"><button class="ma-button ma-primary" type="submit">Save Changes</button><a class="ma-cancel" href="<?= maEsc($url) ?>">Cancel</a></div>
</form>
<?php endif; ?></section>
<aside class="ma-card ma-photo"><p class="ma-eyebrow">Profile Photo</p><h3>Avatar</h3><p>Add a photo so your account<br>feels more personal.</p><div class="ma-avatar"><?php if ($photoUrl): ?><img src="<?= maEsc($photoUrl) ?>" alt="Your profile photo"><?php else: ?><?= maEsc($initials) ?><?php endif; ?></div>
<form method="post" action="<?= maEsc($url) ?>" enctype="multipart/form-data" class="ma-photo-form"><input type="hidden" name="csrf_token" value="<?= maEsc($csrf) ?>"><input type="hidden" name="account_action" value="photo"><input class="ma-file" id="maPhoto" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" aria-label="Choose a profile photo" required><label class="ma-button ma-outline" for="maPhoto">Upload photo</label><p id="maPhotoName" aria-live="polite"></p><button type="submit" class="ma-button ma-primary" id="maPhotoSave" hidden>Save photo</button><small>JPG, PNG, or WebP. Maximum 5 MB.</small></form>
</aside></div>
<?php else: ?>
<div class="ma-heading"><h2>Change Password</h2></div><section class="ma-card ma-password">
<?php if (pureViaStaffNeedsPasswordChange($user)): ?><p class="ma-message ma-notice">You are using an initial staff password. Create a unique personal password to protect your account.</p><?php endif; ?>
<form method="post" action="<?= maEsc($url) ?>?tab=password" class="ma-form"><input type="hidden" name="csrf_token" value="<?= maEsc($csrf) ?>"><input type="hidden" name="account_action" value="password">
<div class="ma-field"><label for="maCurrent">Current Password</label><input id="maCurrent" name="current_password" type="password" autocomplete="current-password" required></div>
<div class="ma-field"><label for="maNew">New Password</label><input id="maNew" name="new_password" type="password" autocomplete="new-password" minlength="<?= (int)$policy['password_min_length'] ?>" aria-describedby="maRequirements" required><ul id="maRequirements"><?php foreach(securityPasswordRequirements($policy) as $requirement): ?><li><?= maEsc($requirement) ?></li><?php endforeach; ?></ul></div>
<div class="ma-field"><label for="maConfirm">Confirm Password</label><input id="maConfirm" name="confirm_password" type="password" autocomplete="new-password" required></div>
<p class="ma-password-note">Changing your password signs out your other sessions. Your authenticator settings stay enabled.</p><div class="ma-actions"><button type="submit" class="ma-button ma-primary">Change Password</button><a class="ma-cancel" href="<?= maEsc($url) ?>">Cancel</a></div>
</form></section>
<?php endif; ?></main></div>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/sidemenu.js"></script><script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/manage-account.js"></script>
</body></html>
