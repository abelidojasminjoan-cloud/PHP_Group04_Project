<?php
declare(strict_types=1);
require_once __DIR__ . '/AuditContext.php';

// Shared login completion, including password-only testing when explicitly enabled.

function pvCompleteTwoFactorLogin(
    mysqli $conn,
    int $userId
): string {
    return pvCompleteLogin($conn, $userId, true);
}

function pvCompletePasswordLogin(mysqli $conn, int $userId): string
{
    return pvCompleteLogin($conn, $userId, false);
}

function pvCompleteLogin(mysqli $conn, int $userId, bool $secondFactorVerified): string
{
    if (!$secondFactorVerified && TWO_FACTOR_ENABLED) {
        throw new RuntimeException('Authenticator verification is required.');
    }
    $requireEnrolled = $secondFactorVerified ? 1 : 0;

    // The user must have passed password verification.
    $pendingId = (int) (
        $_SESSION['pending_2fa_user_id'] ?? 0
    );

    $started = (int) (
        $_SESSION['pending_2fa_started'] ?? 0
    );

    if (
        $userId <= 0 ||
        $pendingId !== $userId ||
        $started <= 0 ||
        $started > time() ||
        time() - $started > 300
    ) {
        throw new RuntimeException(
            'Login verification has expired.'
        );
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            SELECT
                u.id,
                u.role_id,
                u.first_name,
                u.last_name,
                u.email,
                u.status,
                u.totp_enabled_at,
                u.session_version,
                r.role_name
            FROM users u
            INNER JOIN roles r
                ON r.id = u.role_id
            WHERE u.id = ?
              AND u.status = 'active'
              AND (? = 0 OR u.totp_enabled_at IS NOT NULL)
            LIMIT 1
            FOR UPDATE
        ");

        $stmt->bind_param('ii', $userId, $requireEnrolled);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            throw new RuntimeException(
                'Unable to complete login.'
            );
        }

        if ((int)($user['session_version']??0) !== (int)($_SESSION['pending_2fa_session_version']??0)) {
            throw new RuntimeException('Your password changed. Start sign-in again.');
        }
        $role = strtolower(
            (string) $user['role_name']
        );

        if (!in_array(
            $role,
            ['admin', 'staff', 'customer'],
            true
        )) {
            throw new RuntimeException(
                'Invalid account role.'
            );
        }

        // Recheck account lockout before completing login.
        $policy = securityPolicy($conn);

        $lockStmt = $conn->prepare("
            SELECT status, failed_login_attempts, locked_until
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $lockStmt->bind_param('i', $userId);
        $lockStmt->execute();
        $lockUser = $lockStmt->get_result()->fetch_assoc();
        $lockStmt->close();

        if (
            !$lockUser ||
            isPureViaLoginLocked($conn, $lockUser, $policy)
        ) {
            throw new RuntimeException(
                'Account access is locked.'
            );
        }

        // Update only after completing all currently required login checks.
        $stmt = $conn->prepare("
            UPDATE users
            SET failed_login_attempts = 0,
                locked_until = NULL,
                last_login_at = NOW()
            WHERE id = ?
              AND status = 'active'
              AND (? = 0 OR totp_enabled_at IS NOT NULL)
        ");

        $stmt->bind_param('ii', $userId, $requireEnrolled);
        $stmt->execute();
        $stmt->close();

        // Record completed login for the admin log.
        $ip = filter_var(
            $_SERVER['REMOTE_ADDR'] ?? '',
            FILTER_VALIDATE_IP
        ) ?: null;

        $agent = (string) (
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        $browser = match (true) {
            str_contains($agent, 'Edg/'),
            str_contains($agent, 'EdgiOS/') => 'Edge',
            str_contains($agent, 'OPR/'),
            str_contains($agent, 'Opera/') => 'Opera',
            str_contains($agent, 'Firefox/'),
            str_contains($agent, 'FxiOS/') => 'Firefox',
            str_contains($agent, 'Chrome/'),
            str_contains($agent, 'CriOS/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Unknown'
        };

        $email = (string) $user['email'];
        $successful = 1;
        $reason = null;

        $stmt = $conn->prepare("
            INSERT INTO login_attempts
                (user_id, email_attempted, ip_address,
                 browser, successful, failure_reason)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'isssis',
            $userId,
            $email,
            $ip,
            $browser,
            $successful,
            $reason
        );

        $stmt->execute();
        $stmt->close();

        recordPureViaAuthAudit($conn, $userId, 'LOGIN');
        $conn->commit();

    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    // Establish an authenticated session only now.
    session_regenerate_id(true);

    unset(
        $_SESSION['pending_2fa_user_id'],
        $_SESSION['pending_2fa_session_version'],
        $_SESSION['pending_2fa_started'],
        $_SESSION['pending_2fa_attempts'],
        $_SESSION['2fa_setup_secret'],
        $_SESSION['2fa_setup_user_id'],
        $_SESSION['2fa_setup_expires'],
        $_SESSION['2fa_setup_attempts']
    );

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['session_version'] = (int) $user['session_version'];
    $_SESSION['role_id'] = (int) $user['role_id'];
    $_SESSION['role'] = $role;

    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['name'] = $user['first_name'];

    $_SESSION['email'] = $user['email'];
    $_SESSION['last_activity'] = time();
    $_SESSION['2fa_verified'] = $secondFactorVerified;

    if ($role === 'admin') {
        return BASE_URL . '/admin/dashboard.php';
    }

    if ($role === 'staff') {
        return BASE_URL . '/staff/dashboard.php';
    }

    return BASE_URL . '/public/index.php';
}
