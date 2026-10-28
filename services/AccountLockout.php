<?php
declare(strict_types=1);

/** Called in the same transaction as the threshold failure and its login-attempt record. */
function recordPureViaAccountLockout(mysqli $conn, int $userId, int $attemptId, ?string $ip, string $browser): void
{
    $s=$conn->prepare("INSERT INTO account_lockout_logs
        (user_id,source_attempt_id,name_snapshot,email_snapshot,failed_attempts,ip_address,browser)
        SELECT id,?,TRIM(CONCAT_WS(' ',first_name,last_name)),email,failed_login_attempts,?,?
        FROM users WHERE id=? AND status='locked'");
    $s->bind_param('issi',$attemptId,$ip,$browser,$userId);
    $s->execute();
    $s->close();
}
