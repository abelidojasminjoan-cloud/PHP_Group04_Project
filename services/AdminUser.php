<?php
declare(strict_types=1);
require_once __DIR__ . '/security-policy.php';

final class AdminUser
{
    private const COLUMNS = 'u.id, u.role_id, u.first_name, u.last_name, u.email, u.phone,
        u.status, u.created_at, u.updated_at, u.password, u.email_verified_at,
        u.failed_login_attempts, u.locked_until, u.session_version, r.role_name';

    public static function all(mysqli $conn): array
    {
        $policy = securityPolicy($conn);
        $rows = $conn->query('SELECT ' . self::COLUMNS . '
            FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.id')->fetch_all(MYSQLI_ASSOC);
        return array_map(static fn(array $row): array => self::present($row, $policy), $rows);
    }

    public static function details(mysqli $conn, int $id): ?array
    {
        $row = self::find($conn, $id);
        return $row ? self::present($row, securityPolicy($conn)) : null;
    }

    private static function find(mysqli $conn, int $id, bool $lock = false): ?array
    {
        $stmt = $conn->prepare('SELECT ' . self::COLUMNS . '
            FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?' . ($lock ? ' FOR UPDATE' : ''));
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    private static function present(array $row, array $policy): array
    {
        $revision = array_map(static fn($value) => $value === null ? null : (string) $value, $row);
        return [
            'id' => (int) $row['id'], 'role_id' => (int) $row['role_id'],
            'first_name' => $row['first_name'], 'last_name' => $row['last_name'],
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'email' => $row['email'], 'phone' => $row['phone'] ?? '',
            'role' => $row['role_name'], 'status' => $row['status'],
            'joined' => substr($row['created_at'], 0, 10),
            'email_verified' => $row['email_verified_at'] !== null,
            'login_locked' => ($row['status'] === 'locked' || $row['locked_until'] !== null || (int) $row['failed_login_attempts'] >= (int) $policy['max_login_attempts']),
            'revision' => hash('sha256', json_encode($revision, JSON_THROW_ON_ERROR)),
        ];
    }

    private static function field(array $data, string $key, int $max, bool $required = true): string
    {
        $value = $data[$key] ?? '';
        if (!is_string($value)) throw new InvalidArgumentException('Invalid ' . str_replace('_', ' ', $key) . '.');
        $value = trim($value);
        if (($required && $value === '') || mb_strlen($value) > $max || preg_match('/[\x00-\x1F\x7F]/u', $value)) {
            throw new InvalidArgumentException('Enter a valid ' . str_replace('_', ' ', $key) . ' (maximum ' . $max . ' characters).');
        }
        return $value;
    }

    public static function save(mysqli $conn, int $adminId, array $data): array
    {
        if (($data['action'] ?? '') !== 'update') {
            throw new DomainException('User deletion and general account creation are disabled. Deactivate an existing account, or use Add Staff.');
        }
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) throw new InvalidArgumentException('Invalid user.');
        if (isset($data['password']) && $data['password'] !== '') {
            throw new DomainException('Administrators cannot set user passwords here. The user must use the email-verified Forgot password process.');
        }
        if (isset($data['unlock_account']) && !is_bool($data['unlock_account'])) throw new InvalidArgumentException('Invalid unlock request.');
        $unlock = ($data['unlock_account'] ?? false) === true;
        $policy = securityPolicy($conn);
        $conn->begin_transaction();
        try {
            // Serialize admin changes and recheck the acting administrator under lock.
            $admins = $conn->query('SELECT id,role_id,status,failed_login_attempts,locked_until
                FROM users WHERE role_id=1 ORDER BY id FOR UPDATE')->fetch_all(MYSQLI_ASSOC);
            $actor = null;
            foreach ($admins as $admin) if ((int) $admin['id'] === $adminId) $actor = $admin;
            if (!$actor || $actor['status'] !== 'active' || isPureViaLoginLocked($conn, $actor, $policy)) {
                throw new DomainException('Your admin access changed. Please sign in again.');
            }
            $current = self::find($conn, $id, true);
            if (!$current) throw new OutOfBoundsException('User not found.');
            $revision = $data['revision'] ?? '';
            if (!is_string($revision) || !hash_equals(self::present($current, $policy)['revision'], $revision)) {
                throw new DomainException('This user changed since you opened it. Close and reopen the form.');
            }
            if (array_key_exists('role_id', $data) && filter_var($data['role_id'], FILTER_VALIDATE_INT) !== (int) $current['role_id']) {
                throw new DomainException('An existing account role cannot be changed from user management.');
            }
            if (array_key_exists('email', $data) && $data['email'] !== $current['email']) {
                throw new DomainException('The verified sign-in email cannot be changed from this form.');
            }
            $first = self::field($data, 'first_name', 100);
            $last = self::field($data, 'last_name', 100, (int) $current['role_id'] !== 2);
            $phone = self::field($data, 'phone', 30, false);
            if ($phone !== '' && (!preg_match('/^\+?[0-9().\s-]+$/D', $phone) || !preg_match('/^[0-9]{7,15}$/D', preg_replace('/\D/', '', $phone)))) {
                throw new InvalidArgumentException('Enter a valid phone number or leave it blank.');
            }
            $status = $data['status'] ?? '';
            if (!in_array($status, ['active','inactive','locked','pending'], true)) throw new InvalidArgumentException('Select a valid status.');
            $statusChanged = $status !== $current['status'];
            if ($id === $adminId && ($statusChanged || $unlock)) {
                throw new DomainException('You cannot deactivate, lock, or unlock your own administrator account.');
            }
            if (($current['status'] === 'pending' && ($statusChanged || $unlock)) || ($status === 'pending' && $statusChanged)) {
                throw new DomainException('Pending accounts must complete their own email verification and activation. Their access status cannot be changed here.');
            }
            if ($status === 'active' && $statusChanged && $current['email_verified_at'] === null) {
                throw new DomainException('This account must verify its email before it can be reactivated.');
            }
            $locked = isPureViaLoginLocked($conn, $current, $policy);
            if ($unlock && (!$locked || $status !== 'active' || $current['email_verified_at'] === null)) {
                throw new DomainException('Only a locked, email-verified account can be explicitly unlocked to Active.');
            }
            if ($status === 'active' && $statusChanged && $locked && !$unlock) {
                throw new DomainException('Confirm Unlock sign-in access to reactivate this locked account.');
            }
            $accessChanged = $statusChanged || $unlock;
            $reason = '';
            if ($accessChanged) {
                if (($data['confirm_access_change'] ?? false) !== true) throw new DomainException('Confirm the account access change before saving.');
                $reason = self::field($data, 'reason', 500);
                if (mb_strlen($reason) < 5) throw new InvalidArgumentException('Enter a reason of at least 5 characters for changing account access.');
            }
            $failures = $unlock ? 0 : (int) $current['failed_login_attempts'];
            $lockedUntil = $unlock ? null : $current['locked_until'];
            $revoke = $accessChanged ? 1 : 0;
            $stmt = $conn->prepare('UPDATE users SET first_name=?,last_name=?,phone=NULLIF(?,\'\'),status=?,
                failed_login_attempts=?,locked_until=?,session_version=session_version+?,updated_at=NOW() WHERE id=?');
            $stmt->bind_param('ssssisii', $first, $last, $phone, $status, $failures, $lockedUntil, $revoke, $id);
            $stmt->execute();
            $stmt->close();
            $action = $accessChanged ? 'USER_ACCESS_UPDATE' : 'USER_PROFILE_UPDATE';
            $description = $accessChanged
                ? 'User #' . $id . ': ' . $current['status'] . ' to ' . $status . ($unlock ? '; sign-in lock explicitly cleared' : '') . '; existing sessions revoked. Reason: ' . $reason
                : 'Updated profile for user #' . $id . '; credentials and access unchanged.';
            $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: null;
            $browser = pureViaAuditBrowser();
            $stmt = $conn->prepare("INSERT INTO audit_logs(user_id,action,module,record_id,description,ip_address,browser)
                VALUES(?,?,'Users',?,?,?,?)");
            $record = (string) $id;
            $stmt->bind_param('isssss', $adminId, $action, $record, $description, $ip, $browser);
            $stmt->execute();
            $stmt->close();
            $result = self::details($conn, $id);
            $conn->commit();
            return $result;
        } catch (Throwable $error) {
            $conn->rollback();
            throw $error;
        }
    }
}