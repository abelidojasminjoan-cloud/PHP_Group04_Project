<?php if ($staffPasswordWarning): ?>
<section class="staff-password-warning" aria-labelledby="staffPasswordWarningTitle" role="note">
    <div>
        <h2 id="staffPasswordWarningTitle">Change your initial staff password</h2>
        <p>Your account is still using an initial password. Replace it with a unique password that only you know.</p>
        <ol>
            <li>Select Change initial password and enter your current password.</li>
            <li>Create and confirm a different password that meets the displayed requirements, then select Update Password.</li>
        </ol>
        <p class="staff-password-warning-risk">If you leave it unchanged, anyone who knows the shared initial password can attempt to sign in as you. This reminder will stay visible; it does not automatically deactivate your account. Your configured Google Authenticator checks still apply.</p>
    </div>
    <a class="staff-password-warning-action" href="<?= BASE_URL ?>/account/renew-password.php">Change initial password</a>
</section>
<?php endif; ?>