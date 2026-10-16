<?php
declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';

$message = '';
$success = false;

$token = $_GET['token'] ?? '';

if (!is_string($token) ||
    !preg_match('/^[a-f0-9]{64}$/', $token)) {

    $message = 'Invalid verification link.';

} else {

    $tokenHash = hash('sha256', $token);

    try {
        $conn->begin_transaction();

        // Lock token row during verification.
        $stmt = $conn->prepare(
            'SELECT
                evt.id,
                evt.user_id,
                evt.expires_at,
                evt.used_at,
                u.status,
                u.email_verified_at
             FROM email_verification_tokens evt
             INNER JOIN users u
                ON u.id = evt.user_id
             WHERE evt.token_hash = ?
             LIMIT 1
             FOR UPDATE'
        );

        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();

        $verification = $stmt
            ->get_result()
            ->fetch_assoc();

        $stmt->close();

        if (!$verification) {

            $message = 'Invalid verification link.';

        } elseif ($verification['used_at'] !== null) {

            $message = 'This verification link has already been used.';

        } elseif (
            strtotime($verification['expires_at']) <= time()
        ) {

            $message = 'This verification link has expired.';

        } elseif ($verification['status'] !== 'pending') {

            $message = 'This account cannot be activated using this link.';

        } else {

            $userId = (int) $verification['user_id'];
            $tokenId = (int) $verification['id'];

            // Activate customer account.
            $stmt = $conn->prepare(
                "UPDATE users
                 SET status = 'active',
                     email_verified_at = NOW()
                 WHERE id = ?
                   AND status = 'pending'"
            );

            $stmt->bind_param('i', $userId);
            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                throw new RuntimeException(
                    'Account activation failed.'
                );
            }

            $stmt->close();

            // Invalidate all unused activation tokens
            // belonging to this customer.
            $stmt = $conn->prepare(
                'UPDATE email_verification_tokens
                 SET used_at = NOW()
                 WHERE user_id = ?
                   AND used_at IS NULL'
            );

            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $stmt->close();

            $success = true;
            $message = 'Your PureVia account has been verified successfully!';
        }

        $conn->commit();

    } catch (Throwable $e) {

        try {
            $conn->rollback();
        } catch (Throwable $ignored) {
        }

        error_log(
            'PureVia verification error: '
            . $e->getMessage()
        );

        $message = 'Unable to verify your account right now.';
    }
}

if ($success) {
    $_SESSION['login_success'] = $message;

    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">

    <title>Email Verification | PureVia</title>
</head>
<body style="
    font-family: Arial, sans-serif;
    text-align: center;
    padding: 80px 20px;
">

    <h1>PureVia Email Verification</h1>

    <p>
        <?= htmlspecialchars(
            $message,
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </p>

    <a href="<?= htmlspecialchars(
        BASE_URL . '/login.php',
        ENT_QUOTES,
        'UTF-8'
    ) ?>">
        Return to Login
    </a>

</body>
</html>
