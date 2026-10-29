<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

function activationLogin(string $message = '', bool $success = false): never
{
    unset($_SESSION['login_error'], $_SESSION['login_success'], $_SESSION['login_errors']);
    if ($message !== '') {
        $_SESSION[$success ? 'login_success' : 'login_error'] = $message;
    }
    header('Location: ' . BASE_URL . '/public/login.php');
    exit;
}

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit('Method not allowed.');
}

$token = $_GET['token'] ?? null;
if (!is_string($token) || !preg_match('/\A[0-9a-f]{64}\z/', $token)) {
    activationLogin();
}

$hash = hash('sha256', $token);
$transactionStarted = false;

try {
    // Resolve the owner first, then lock the user before the token, as OTP verification does.
    $stmt = $conn->prepare(
        'SELECT user_id FROM email_verification_tokens WHERE token_hash = ? LIMIT 1'
    );
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $owner = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$owner) {
        activationLogin();
    }

    $userId = (int) $owner['user_id'];
    $conn->begin_transaction();
    $transactionStarted = true;

    $stmt = $conn->prepare(
        'SELECT email, role_id, status, email_verified_at
         FROM users WHERE id = ? LIMIT 1 FOR UPDATE'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT id, used_at, (expires_at > NOW()) AS is_valid
         FROM email_verification_tokens
         WHERE token_hash = ? AND user_id = ? LIMIT 1 FOR UPDATE'
    );
    $stmt->bind_param('si', $hash, $userId);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (
        !$user || !$record || !in_array((int) $user['role_id'], [2, 3], true) ||
        $user['status'] !== 'pending' || $user['email_verified_at'] === null ||
        $record['used_at'] !== null || (int) $record['is_valid'] !== 1
    ) {
        $conn->rollback();
        $transactionStarted = false;
        activationLogin();
    }

    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        throw new RuntimeException('Account activation did not update the pending account.');
    }
    $stmt->close();

    $stmt = $conn->prepare('UPDATE email_verification_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    $transactionStarted = false;

    if ((int) ($_SESSION['pending_verification_user_id'] ?? 0) === $userId) {
        unset($_SESSION['pending_verification_user_id'], $_SESSION['account_activation']);
    }
    $_SESSION['login_email'] = $user['email'];
    activationLogin('Your PureVia account has been activated successfully! Please log in.', true);
} catch (Throwable $error) {
    if ($transactionStarted) {
        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }
    }
    error_log('PureVia account activation failed: ' . $error->getMessage());
    activationLogin('Unable to activate your account right now. Please try the link again later.');
}
