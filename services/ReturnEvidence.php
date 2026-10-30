<?php
declare(strict_types=1);
require_once __DIR__ . '/SecureUpload.php';
final class ReturnEvidence
{
    public static function directory(): string
    {
        return trim((string)($_ENV['RETURN_EVIDENCE_PATH'] ?? '')) ?: (is_dir('/var/www/storage') ? '/var/www/storage/returns' : __DIR__ . '/../storage/returns');
    }
    public static function validate(mixed $files): array
    {
        if ($files !== null && (!is_array($files) || !is_array($files['error'] ?? null) || count($files['error']) > 5)) {
            throw new InvalidArgumentException('Upload up to 5 evidence photos.');
        }
        try { return SecureUpload::images($files); }
        catch (RuntimeException $error) { throw new InvalidArgumentException($error->getMessage()); }
    }
    public static function store(array $images): array
    {
        $saved = [];
        try {
            $dir = self::directory();
            if ($images && !is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('Evidence storage unavailable.');
            foreach ($images as $image) {
                if (!is_uploaded_file($image['tmp_name']) || !hash_equals($image['hash'], (string) hash_file('sha256', $image['tmp_name']))) throw new RuntimeException('Evidence changed during upload.');
                $name = bin2hex(random_bytes(20)) . '.' . $image['extension'];
                if (!move_uploaded_file($image['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Unable to save evidence.');
                $saved[] = $name; chmod($dir . '/' . $name, 0600);
            }
            return $saved;
        } catch (Throwable $error) { self::remove($saved); throw $error; }
    }
    public static function remove(array $names): void
    {
        foreach ($names as $name) {
            if (is_string($name) && preg_match('/^[a-f0-9]{40}\.(jpg|png|webp)$/D', $name)) {
                $file = self::directory() . '/' . $name;
                if (is_file($file)) unlink($file);
            }
        }
    }
}