<?php
declare(strict_types=1);
require_once __DIR__ . '/security-policy.php';
require_once __DIR__ . '/AuditContext.php';
require_once __DIR__ . '/SecureUpload.php';

final class ManageAccount
{
    public static function details(mysqli $conn, int $id, bool $lock = false): array
    {
        $s = $conn->prepare("SELECT id,role_id,first_name,last_name,email,phone,gender,birth_date,
            profile_photo,status,created_at,password,password_changed_at,session_version,
            failed_login_attempts,locked_until FROM users WHERE id=?" . ($lock ? ' FOR UPDATE' : ''));
        $s->bind_param('i', $id); $s->execute(); $user = $s->get_result()->fetch_assoc(); $s->close();
        if (!$user || !in_array((int) $user['role_id'], [1,2], true) || $user['status'] !== 'active'
            || isPureViaLoginLocked($conn, $user, securityPolicy($conn))) {
            throw new DomainException('This account cannot manage its profile.');
        }
        return $user;
    }

    private static function value(array $data, string $key, int $max): string
    {
        if (!is_string($data[$key] ?? null)) throw new InvalidArgumentException('Please check the form fields.');
        $value = trim($data[$key]);
        if (mb_strlen($value) > $max) throw new InvalidArgumentException('The ' . str_replace('_', ' ', $key) . ' is too long.');
        return $value;
    }

    public static function profile(mysqli $conn, int $id, array $data): void
    {
        $first = self::value($data, 'first_name', 100); $last = self::value($data, 'last_name', 100);
        $phone = self::value($data, 'phone', 30); $gender = self::value($data, 'gender', 30);
        $birth = self::value($data, 'birth_date', 10);
        if ($first === '' || $last === '') throw new InvalidArgumentException('Enter your first and last name.');
        if ($phone !== '' && !preg_match('/^[0-9+\s()\-]{7,30}$/D', $phone)) throw new InvalidArgumentException('Enter a valid phone number.');
        if (!in_array($gender, ['', 'Female', 'Male', 'Non-binary', 'Prefer not to say'], true)) throw new InvalidArgumentException('Select a valid gender.');
        if ($birth !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birth);
            if (!$date || $date->format('Y-m-d') !== $birth || $date > new DateTimeImmutable('today')
                || $date < new DateTimeImmutable('1900-01-01')) throw new InvalidArgumentException('Enter a valid birth date.');
        }
        $conn->begin_transaction();
        try {
            self::details($conn, $id, true);
            $s = $conn->prepare("UPDATE users SET first_name=?,last_name=?,phone=NULLIF(?,''),gender=NULLIF(?,''),birth_date=NULLIF(?,''),updated_at=NOW() WHERE id=?");
            $s->bind_param('sssssi', $first, $last, $phone, $gender, $birth, $id); $s->execute(); $s->close();
            self::audit($conn, $id, 'PROFILE_UPDATE', 'Updated own personal information.');
            $conn->commit();
        } catch (Throwable $error) { $conn->rollback(); throw $error; }
    }

    public static function password(mysqli $conn, int $id, array $data): int
    {
        foreach (['current_password','new_password','confirm_password'] as $key) {
            if (!is_string($data[$key] ?? null) || strlen($data[$key]) > 4096) throw new InvalidArgumentException('Enter valid password fields.');
        }
        $conn->begin_transaction();
        try {
            $user = self::details($conn, $id, true);
            if (!password_verify($data['current_password'], $user['password'])) throw new DomainException('Your current password is incorrect.');
            if ($errors = validatePureViaPassword($data['new_password'], securityPolicy($conn))) throw new InvalidArgumentException($errors[0]);
            if ($data['new_password'] !== $data['confirm_password']) throw new InvalidArgumentException('Passwords do not match.');
            if (password_verify($data['new_password'], $user['password'])) throw new InvalidArgumentException('Choose a different password from your current password.');
            $default = (string) ($_ENV['STAFF_DEFAULT_PASSWORD'] ?? '');
            if ((int) $user['role_id'] === 2 && $default !== '' && hash_equals($default, $data['new_password'])) {
                throw new InvalidArgumentException('Choose a personal password instead of the shared initial password.');
            }
            $hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $s = $conn->prepare('UPDATE users SET password=?,password_changed_at=NOW(),session_version=session_version+1,updated_at=NOW() WHERE id=?');
            $s->bind_param('si', $hash, $id); $s->execute(); $s->close();
            self::audit($conn, $id, 'PASSWORD_CHANGE', 'Changed own password; other sessions revoked.');
            $conn->commit();
            return (int) $user['session_version'] + 1;
        } catch (Throwable $error) { $conn->rollback(); throw $error; }
    }

    public static function photo(mysqli $conn, int $id, array $file): void
    {
        self::details($conn, $id);
        $stored = SecureUpload::store(SecureUpload::image($file), 'profiles');
        $conn->begin_transaction();
        try {
            $user = self::details($conn, $id, true);
            $s = $conn->prepare('UPDATE users SET profile_photo=?,updated_at=NOW() WHERE id=?');
            $s->bind_param('si', $stored['filename'], $id); $s->execute(); $s->close();
            self::audit($conn, $id, 'PROFILE_PHOTO_UPDATE', 'Updated own profile photo.');
            $conn->commit();
        } catch (Throwable $error) {
            $conn->rollback(); @unlink($stored['path']); throw $error;
        }
        $old = basename((string) ($user['profile_photo'] ?? ''));
        if (preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $old) && $old !== $stored['filename']) {
            $oldPath = dirname($stored['path']) . DIRECTORY_SEPARATOR . $old;
            if (is_file($oldPath)) @unlink($oldPath);
        }
    }

    private static function audit(mysqli $conn, int $id, string $action, string $description): void
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: null;
        $browser = pureViaAuditBrowser(); $record = (string) $id;
        $s = $conn->prepare("INSERT INTO audit_logs(user_id,action,module,record_id,description,ip_address,browser) VALUES(?,?,'Account',?,?,?,?)");
        $s->bind_param('isssss', $id, $action, $record, $description, $ip, $browser); $s->execute(); $s->close();
    }
}
