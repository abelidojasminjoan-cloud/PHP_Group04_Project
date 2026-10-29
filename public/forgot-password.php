<?php
declare(strict_types=1);
require_once __DIR__.'/../config/db.php';
header('Cache-Control: no-store, private');
if(!empty($_SESSION['user_id'])){header('Location: '.BASE_URL.'/public/login.php');exit;}
$_SESSION['csrf_token']??=bin2hex(random_bytes(32));
if(isset($_SESSION['password_reset_grant'])&&(int)$_SESSION['password_reset_grant']['verified_at']<time()-600){
    unset($_SESSION['password_reset_grant'],$_SESSION['password_reset_challenge']);
    $_SESSION['password_reset_error']='Your verification expired. Request a new code.';
}
$challenge=$_SESSION['password_reset_challenge']??null;
$grant=$_SESSION['password_reset_grant']??null;
$step=$grant?'reset':($challenge?'verify':'email');
$error=$_SESSION['password_reset_error']??'';$notice=$_SESSION['password_reset_notice']??'';
unset($_SESSION['password_reset_error'],$_SESSION['password_reset_notice']);
$policy=securityPolicy($conn);
$heading=['email'=>'Forgot password?','verify'=>'Check your email','reset'=>'Create a new password'][$step];
$subtitle=['email'=>'Enter the email address registered with PureVia.','verify'=>'Enter the six-digit code sent to '.($challenge['email']??'your email').'.','reset'=>'Choose a new password for your PureVia account.'][$step];
$wait=$challenge?max(0,60-(time()-(int)$challenge['requested_at'])):0;
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= pvHtml($heading) ?> | PureVia</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth.css"><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/password-reset.css">
</head><body class="auth-page"><main class="auth-shell">
<section class="auth-visual" aria-label="PureVia skincare"><a class="auth-brand" href="<?= BASE_URL ?>/public/index.php"><img src="<?= BASE_URL ?>/assets/images/logo/purevia_logo_1.png" alt="PureVia" width="168" height="168"></a><h2 class="auth-visual-title">Skincare,<br>made simple.</h2></section>
<section class="auth-content"><div class="auth-form-area">
<div class="auth-heading"><p class="auth-eyebrow">ACCOUNT RECOVERY · STEP <?= ['email'=>1,'verify'=>2,'reset'=>3][$step] ?> OF 3</p><h1><?= pvHtml($heading) ?></h1><p class="auth-subtitle"><?= pvHtml($subtitle) ?></p></div>
<?php if($error): ?><div class="auth-alert auth-alert-error" role="alert"><?= pvHtml($error) ?></div><?php endif; ?>
<?php if($notice): ?><div class="auth-alert auth-alert-success" role="status"><?= pvHtml($notice) ?></div><?php endif; ?>
<form class="auth-form" method="post" action="<?= BASE_URL ?>/actions/auth/password-reset.php" data-reset-form>
<input type="hidden" name="csrf_token" value="<?= pvHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="<?= $step==='email'?'request':$step ?>">
<?php if($step==='email'): ?>
<div class="auth-field"><label for="resetEmail">Email address</label><input type="email" id="resetEmail" name="email" autocomplete="email" placeholder="you@example.com" maxlength="255" value="<?= pvHtml($_SESSION['password_reset_email']??'') ?>" required></div>
<button class="auth-submit" type="submit" data-busy="Sending code...">Send OTP</button>
<?php elseif($step==='verify'): ?>
<div class="auth-field"><label for="resetOtp">Verification code</label><input class="reset-otp" type="text" id="resetOtp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" aria-describedby="resetOtpHelp" required><small id="resetOtpHelp">The code expires after 10 minutes. Up to five guesses are allowed.</small></div>
<button class="auth-submit" type="submit" data-busy="Verifying...">Verify OTP</button>
<?php else: ?>
<div class="auth-field">
    <label for="resetPassword">Create Password</label>
    <div class="auth-password-wrapper">
        <input type="password" id="resetPassword" name="password" placeholder="At least <?= (int)$policy['password_min_length'] ?> characters" autocomplete="new-password" minlength="<?= (int)$policy['password_min_length'] ?>" maxlength="72" aria-describedby="resetPasswordFeedback" required>
        <button class="auth-password-toggle reset-password-toggle" type="button" data-reset-toggle="resetPassword" aria-label="Show new password" aria-pressed="false">Show</button>
    </div>
<div class="auth-password-feedback" id="resetPasswordFeedback" hidden>
                        <p class="auth-password-strength">
                            Password strength:
                            <strong id="resetPasswordStrength" data-level="empty" aria-live="polite" aria-atomic="true">Not set</strong>
                        </p>
                        <ul class="auth-password-checklist">
                            <?php foreach (securityPasswordRequirements($policy) as $passwordRuleKey => $passwordRuleLabel): ?>
                                <li data-password-rule="<?= pvHtml($passwordRuleKey) ?>" data-min-count="<?= securityPasswordRuleMinimum($policy, $passwordRuleKey) ?>" data-met="false">
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
                                    <span><?= pvHtml($passwordRuleLabel) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
</div>
<div class="auth-field">
    <label for="resetConfirmPassword">Confirm Password</label>
    <div class="auth-password-wrapper">
        <input type="password" id="resetConfirmPassword" name="confirm_password" placeholder="Enter your password again" autocomplete="new-password" maxlength="72" aria-describedby="resetConfirmPasswordError" aria-invalid="false" required>
        <button class="auth-password-toggle reset-password-toggle" type="button" data-reset-toggle="resetConfirmPassword" aria-label="Show password confirmation" aria-pressed="false">Show</button>
    </div>
    <span class="auth-field-error" id="resetConfirmPasswordError" role="alert" hidden></span>
</div>
<button class="auth-submit" type="submit" data-busy="Updating password...">Save new password</button>
<?php endif; ?>
</form>
<?php if($step==='verify'): ?>
<form class="reset-secondary-form" method="post" action="<?= BASE_URL ?>/actions/auth/password-reset.php" data-reset-form><input type="hidden" name="csrf_token" value="<?= pvHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="resend"><button class="reset-text-button" type="submit" data-wait="<?= $wait ?>" data-busy="Sending code...">Resend OTP</button></form>
<?php endif; ?>
<?php if($step!=='email'): ?><form class="reset-secondary-form" method="post" action="<?= BASE_URL ?>/actions/auth/password-reset.php"><input type="hidden" name="csrf_token" value="<?= pvHtml($_SESSION['csrf_token']) ?>"><input type="hidden" name="action" value="restart"><button class="reset-text-button" type="submit">Use another email</button></form><?php endif; ?>
<p class="auth-bottom-link">Remember your password? <a href="<?= BASE_URL ?>/public/login.php">Sign in</a></p>
</div><footer class="auth-footer"><div class="auth-footer-links"><a href="<?= BASE_URL ?>/public/privacy_policy.php">Privacy</a><a href="<?= BASE_URL ?>/public/terms.php">Terms</a><span>&copy; <?= date('Y') ?> PureVia</span></div></footer></section></main>
<script nonce="<?= pvScriptNonce() ?>" src="<?= BASE_URL ?>/assets/js/password-reset.js?v=<?= filemtime(__DIR__ . '/../assets/js/password-reset.js') ?>"></script></body></html>
