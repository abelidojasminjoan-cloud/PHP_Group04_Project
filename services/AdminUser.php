<?php
declare(strict_types=1);

require_once __DIR__ . '/security-policy.php';

final class AdminUser
{
    private const COLUMNS = 'u.id, u.role_id, u.first_name, u.last_name, u.email, u.phone,
        u.status, u.created_at, u.updated_at, u.password, u.email_verified_at,
        u.failed_login_attempts, u.locked_until, r.role_name';

    public static function all(mysqli $conn): array
    {
        $rows = $conn->query('SELECT ' . self::COLUMNS . '
            FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.id')->fetch_all(MYSQLI_ASSOC);
        return array_map([self::class, 'present'], $rows);
    }

    public static function details(mysqli $conn, int $id): ?array
    {
        $row = self::find($conn, $id);
        return $row ? self::present($row) : null;
    }

    private static function find(mysqli $conn, int $id, bool $lock = false): ?array
    {
        $stmt = $conn->prepare('SELECT ' . self::COLUMNS . '
            FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?' . ($lock ? ' FOR UPDATE' : ''));
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private static function present(array $row): array
    {
        // MySQL returns numbers as strings for ordinary queries and integers for prepared queries.
        $revision = array_map(static fn($value) => $value === null ? null : (string) $value, $row);
        return [
            'id' => (int) $row['id'], 'role_id' => (int) $row['role_id'],
            'first_name' => $row['first_name'], 'last_name' => $row['last_name'],
            'name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'email' => $row['email'], 'phone' => $row['phone'] ?? '',
            'role' => $row['role_name'], 'status' => $row['status'],
            'joined' => substr($row['created_at'], 0, 10),
            // Include credential changes in the revision without exposing credentials.
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
        $action = $data['action'] ?? '';
        if (!in_array($action, ['create', 'update', 'delete'], true)) throw new InvalidArgumentException('Invalid user action.');
        $id = $action === 'create' ? 0 : filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || $id === null) throw new InvalidArgumentException('Invalid user.');

        $policy = securityPolicy($conn);
        $email = '';
        $password = '';
        if ($action !== 'delete') {
            $first = self::field($data, 'first_name', 100);
            $last = self::field($data, 'last_name', 100);
            $email = self::field($data, 'email', 255);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid email address.');
            $phone = self::field($data, 'phone', 30, false);
            if ($phone !== '' && (!preg_match('/^\+?[0-9().\s-]+$/D', $phone) || !preg_match('/^[0-9]{7,15}$/D', preg_replace('/\D/', '', $phone)))) {
                throw new InvalidArgumentException('Enter a valid phone number or leave it blank.');
            }
            $role = filter_var($data['role_id'] ?? null, FILTER_VALIDATE_INT);
            if (!in_array($role, [1, 2, 3], true)) throw new InvalidArgumentException('Select a valid role.');
            $status = $data['status'] ?? '';
            if (!in_array($status, ['pending', 'active', 'inactive', 'locked'], true)) throw new InvalidArgumentException('Select a valid status.');
            $password = $data['password'] ?? '';
            if (!is_string($password)) throw new InvalidArgumentException('Invalid password.');
            if ($action === 'create' || $password !== '') {
                $errors = validatePureViaPassword($password, $policy);
                if ($errors) throw new InvalidArgumentException(implode(' ', $errors));
                $hash = password_hash($password, PASSWORD_DEFAULT);
            }
        }

        $conn->begin_transaction();
        try {
            // Serialize administrator changes and recheck the acting account under lock.
            $admins = $conn->query('SELECT id, role_id, status, failed_login_attempts, locked_until
                FROM users WHERE role_id = 1 ORDER BY id FOR UPDATE')->fetch_all(MYSQLI_ASSOC);
            $actor = null;
            foreach ($admins as $admin) if ((int) $admin['id'] === $adminId) $actor = $admin;
            if (!$actor || $actor['status'] !== 'active' || isPureViaLoginLocked($conn, $actor, $policy)) {
                throw new DomainException('Your admin access changed. Please sign in again.');
            }
            $current = $id ? self::find($conn, $id, true) : null;
            if ($id && !$current) throw new OutOfBoundsException('User not found.');
            if ($current) {
                $revision = $data['revision'] ?? '';
                if (!is_string($revision) || !hash_equals(self::present($current)['revision'], $revision)) {
                    throw new DomainException('This user changed since you opened it. Refresh the page and try again.');
                }
                if ($id === $adminId && ($action === 'delete' || $role !== 1 || $status !== 'active')) {
                    throw new DomainException('You cannot delete, deactivate, lock, or demote your own admin account.');
                }
            }

            if ($action === 'delete') {
                $stmt = $conn->prepare('SELECT id FROM orders WHERE user_id = ? LIMIT 1');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                if ($stmt->get_result()->fetch_assoc()) throw new DomainException('This user has orders. Set the account to Inactive instead to preserve order history.');
                $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
            } elseif ($action === 'create') {
                // An administrator explicitly grants access when selecting Active.
                $verified = $status === 'active' ? date('Y-m-d H:i:s') : null;
                $stmt = $conn->prepare('INSERT INTO users
                    (role_id, first_name, last_name, email, phone, password, password_changed_at, status, email_verified_at)
                    VALUES (?, ?, ?, ?, NULLIF(?, \'\'), ?, NOW(), ?, ?)');
                $stmt->bind_param('isssssss', $role, $first, $last, $email, $phone, $hash, $status, $verified);
                $stmt->execute();
                $id = (int) $conn->insert_id;
            } else {
                $hash = $hash ?? $current['password'];
                $changedAt = $password !== '' ? date('Y-m-d H:i:s') : null;
                $verified = $current['email_verified_at'];
                if ($email !== $current['email']) $verified = null;
                if ($status === 'active' && $verified === null) $verified = date('Y-m-d H:i:s');
                $failures = $status === 'active' ? 0 : (int) $current['failed_login_attempts'];
                $lockedUntil = $status === 'active' ? null : $current['locked_until'];
                $stmt = $conn->prepare('UPDATE users SET role_id = ?, first_name = ?, last_name = ?, email = ?,
                    phone = NULLIF(?, \'\'), password = ?, password_changed_at = COALESCE(?, password_changed_at),
                    status = ?, email_verified_at = ?, failed_login_attempts = ?, locked_until = ? WHERE id = ?');
                $stmt->bind_param('issssssssisi', $role, $first, $last, $email, $phone, $hash, $changedAt, $status, $verified, $failures, $lockedUntil, $id);
                $stmt->execute();
            }

            $auditAction = ['create' => 'create_user', 'update' => 'update_user', 'delete' => 'delete_user'][$action];
            $description = ucfirst($action) . ' user #' . $id . ($action === 'delete' ? '' : ' (role ' . $role . ', status ' . $status . ')');
            $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: null;
            $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description, ip_address)
                VALUES (?, ?, 'users', ?, ?, ?)");
            $stmt->bind_param('isiss', $adminId, $auditAction, $id, $description, $ip);
            $stmt->execute();
            $result = $action === 'delete' ? ['id' => $id] : self::details($conn, $id);
            $conn->commit();
            return $result;
        } catch (Throwable $error) {
            $conn->rollback();
            if ($error instanceof mysqli_sql_exception && $error->getCode() === 1062) {
                throw new DomainException('That email address is already used by another account.');
            }
            if ($error instanceof mysqli_sql_exception && $error->getCode() === 1451) {
                throw new DomainException('This user has linked records. Set the account to Inactive instead.');
            }
            throw $error;
        }
    }
}
