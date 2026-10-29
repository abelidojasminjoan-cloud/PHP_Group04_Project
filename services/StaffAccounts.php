<?php
declare(strict_types=1);

require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/AuditContext.php';

function pureViaStaffDisplayName(string $email): string
{
    $local = explode('@', $email, 2)[0];
    $name = trim(preg_replace('/[._+\-]+/', ' ', $local) ?? '');
    return mb_substr(mb_convert_case($name !== '' ? $name : 'Staff', MB_CASE_TITLE, 'UTF-8'), 0, 100);
}

function sendPureViaStaffInvitation(string $email, string $name, string $password): bool
{
    $loginUrl = APP_URL . '/public/login.php';
    $html = '<div style="font-family:Arial,sans-serif;max-width:540px;margin:auto;padding:30px"><h2 style="color:#426b58">Your PureVia staff account</h2><p>Hello ' . pvHtml($name) . ',</p><p>An administrator has created your staff account.</p><p>Email: <strong>' . pvHtml($email) . '</strong><br>Initial password: <strong>' . pvHtml($password) . '</strong></p><p>Sign in with these details to receive your email verification code. After you verify the code, use the activation button in the next email to activate your account.</p><p><a href="' . pvHtml($loginUrl) . '" style="display:inline-block;padding:12px 20px;background:#3f6b50;color:white;border-radius:8px;text-decoration:none">Sign in to PureVia</a></p><p>Keep these details private. Update your initial password after account setup.</p><p>PureVia Team</p></div>';
    $plain = "Hello {$name},\n\nYour PureVia staff account has been created.\nEmail: {$email}\nInitial password: {$password}\n\nSign in: {$loginUrl}\nVerify the emailed OTP, then use the activation link in the next email.\nKeep these details private and update your initial password after setup.";
    return sendPureViaMail($email, $name, 'Your PureVia Staff Account', $html, $plain, 15);
}

/** Admin-only creation. A delivery failure rolls back the pending account. */
function createPureViaStaffAccount(mysqli $conn, int $adminId, string $email, string $defaultPassword, ?callable $send = null): int
{
    $email = strtolower(trim($email));
    if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Please enter a valid email address.');
    if ($defaultPassword === '' || validatePureViaPassword($defaultPassword, securityPolicy($conn))) throw new RuntimeException('STAFF_DEFAULT_PASSWORD must be configured to meet the current password policy.');
    $send ??= 'sendPureViaStaffInvitation';
    $name = pureViaStaffDisplayName($email);
    $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("SELECT id,failed_login_attempts,locked_until,status FROM users WHERE id = ? AND role_id = 1 AND status = 'active' FOR UPDATE");
        $stmt->bind_param('i', $adminId); $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$admin || isPureViaLoginLocked($conn, $admin, securityPolicy($conn))) throw new DomainException('Administrator access is required.');
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email); $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($exists) throw new DomainException('An account with this email address already exists.');
        // Role and pending status are set on the server and cannot be supplied by the form.
        $stmt = $conn->prepare("INSERT INTO users (role_id, first_name, last_name, email, password, status) VALUES (2, ?, '', ?, ?, 'pending')");
        $stmt->bind_param('sss', $name, $email, $hash); $stmt->execute();
        $userId = (int) $conn->insert_id; $stmt->close();
        $action = 'CREATE'; $module = 'Users'; $record = (string) $userId;
        $description = 'Created pending staff account for ' . $email;
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: null;
        $browser = pureViaAuditBrowser();
        $stmt = $conn->prepare('INSERT INTO audit_logs (user_id,action,module,record_id,description,ip_address,browser) VALUES (?,?,?,?,?,?,?)');
        $stmt->bind_param('issssss', $adminId, $action, $module, $record, $description, $ip, $browser); $stmt->execute(); $stmt->close();
        if (!$send($email, $name, $defaultPassword)) throw new RuntimeException('The staff invitation could not be delivered. No account was created; check the email settings and try again.');
        $conn->commit();
        return $userId;
    } catch (Throwable $error) {
        $conn->rollback();
        if ($error instanceof mysqli_sql_exception && $error->getCode() === 1062) throw new DomainException('An account with this email address already exists.');
        throw $error;
    }
}

/** Called with the pending staff user locked inside the login transaction. */
function preparePureViaStaffVerification(mysqli $conn, array $user, ?callable $send = null): string
{
    if ((int) $user['role_id'] !== 2 || $user['status'] !== 'pending') throw new DomainException('This staff account is not awaiting activation.');
    $userId = (int) $user['id'];
    $stmt = $conn->prepare("SELECT COUNT(*) AS total, COALESCE(TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()), 999999) AS elapsed FROM otp_codes WHERE user_id = ? AND purpose = 'email_verification' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->bind_param('i', $userId); $stmt->execute();
    $limits = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if ((int) $limits['total'] >= 5) return 'The OTP request limit has been reached. Please try again in an hour.';
    if ((int) $limits['elapsed'] < 60) return 'A verification code was recently sent. Check your inbox, or wait ' . (60 - (int) $limits['elapsed']) . ' seconds before requesting another.';
    $otp = (string) random_int(100000, 999999);
    $hash = password_hash($otp, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE otp_codes SET used_at = NOW() WHERE user_id = ? AND purpose = 'email_verification' AND used_at IS NULL");
    $stmt->bind_param('i', $userId); $stmt->execute(); $stmt->close();
    $stmt = $conn->prepare("INSERT INTO otp_codes (user_id,otp_hash,purpose,expires_at) VALUES (?,?,'email_verification',DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
    $stmt->bind_param('is', $userId, $hash); $stmt->execute(); $stmt->close();
    $send ??= 'sendOtpEmail';
    $sent = $send($user['email'], $user['first_name'], $otp);
    return $sent ? 'We sent a 6-digit verification code to your staff email.' : 'Your verification email could not be delivered. Check the email settings, then use Resend OTP.';
}