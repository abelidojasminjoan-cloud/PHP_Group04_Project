<?php
declare(strict_types=1);

final class SecureUpload
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const MAX_IMAGES = 10;
    public const MAX_DIMENSION = 6000;

    private const TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    /** Validate and scan a single PHP upload before it reaches public storage. */
    public static function image(array $file): array
    {
        $image = self::inspect($file);
        self::scan([$image]);
        return $image;
    }

    /** Normalize PHP's multiple-file shape; reject malformed or excessive batches. */
    public static function images(mixed $files): array
    {
        if ($files === null) {
            return [];
        }
        if (!is_array($files)) {
            throw new RuntimeException('Invalid image upload.');
        }
        foreach (['name', 'tmp_name', 'size', 'error'] as $field) {
            if (!isset($files[$field]) || !is_array($files[$field])) {
                throw new RuntimeException('Invalid image upload.');
            }
        }
        $keys = array_keys($files['error']);
        if (count($keys) > self::MAX_IMAGES) {
            throw new RuntimeException('A maximum of 10 product images is allowed.');
        }
        foreach (['name', 'tmp_name', 'size'] as $field) {
            if (array_keys($files[$field]) !== $keys) {
                throw new RuntimeException('Invalid image upload.');
            }
        }
        $images = [];
        foreach ($keys as $index) {
            if ($files['error'][$index] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $images[$index] = self::inspect([
                'name' => $files['name'][$index],
                'tmp_name' => $files['tmp_name'][$index],
                'size' => $files['size'][$index],
                'error' => $files['error'][$index],
            ]);
        }
        self::scan($images);
        return $images;
    }

    private static function inspect(array $file): array
    {
        if (!isset($file['error']) || !is_int($file['error'])) {
            throw new RuntimeException('Invalid image upload.');
        }
        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new RuntimeException('Each image must be 5 MB or smaller.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed. Please select an image and try again.');
        }
        if (!is_string($file['name'] ?? null) || !is_string($file['tmp_name'] ?? null)
            || !is_int($file['size'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Invalid image upload.');
        }
        $path = realpath($file['tmp_name']);
        $size = $path === false ? false : filesize($path);
        if ($size === false || $size <= 0 || $size > self::MAX_BYTES || $size !== $file['size']) {
            throw new RuntimeException('Each image must be 5 MB or smaller.');
        }
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$extension])) {
            throw new RuntimeException('Only JPG, PNG, and WebP images are allowed.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if ($mime !== self::TYPES[$extension]) {
            throw new RuntimeException('The image contents do not match its file type. Use JPG, PNG, or WebP.');
        }
        $info = @getimagesize($path);
        if ($info === false || ($info['mime'] ?? '') !== $mime
            || $info[0] < 1 || $info[1] < 1
            || $info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
            throw new RuntimeException('Invalid image. Images must be no larger than 6000 × 6000 pixels.');
        }
        return [
            'tmp_name' => $path,
            'extension' => $extension === 'jpeg' ? 'jpg' : $extension,
            'hash' => hash_file('sha256', $path),
        ];
    }

    /** Only scanned, unchanged bytes can be promoted to an allowed image directory. */
    public static function store(array $image, string $folder): array
    {
        if (!in_array($folder, ['profiles', 'products'], true)) {
            throw new LogicException('Invalid upload directory.');
        }
        if (!is_uploaded_file($image['tmp_name'])
            || !hash_equals($image['hash'], (string) @hash_file('sha256', $image['tmp_name']))) {
            throw new RuntimeException('The image changed during processing. Please upload it again.');
        }
        $directory = __DIR__ . '/../uploads/' . $folder;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the image directory.');
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $image['extension'];
        $destination = $directory . '/' . $filename;
        if (!move_uploaded_file($image['tmp_name'], $destination)) {
            throw new RuntimeException('Unable to save the uploaded image.');
        }
        chmod($destination, 0644);
        return ['filename' => $filename, 'path' => $destination, 'relative_path' => 'uploads/' . $folder . '/' . $filename];
    }

    private static function scanner(): array
    {
        $engine = strtolower((string) ($_ENV['UPLOAD_SCANNER'] ?? (PHP_OS_FAMILY === 'Windows' ? 'defender' : 'clamav')));
        $path = (string) ($_ENV['UPLOAD_SCANNER_PATH'] ?? '');
        if ($path === '') {
            if ($engine === 'defender' && PHP_OS_FAMILY === 'Windows') {
                $path = (getenv('ProgramFiles') ?: 'C:\\Program Files') . '/Windows Defender/MpCmdRun.exe';
            } elseif ($engine === 'clamav') {
                $path = '/usr/bin/clamscan';
            }
        }
        if (!in_array($engine, ['defender', 'clamav'], true) || !is_file($path) || !function_exists('proc_open')) {
            error_log('Upload scanner unavailable: configure UPLOAD_SCANNER and UPLOAD_SCANNER_PATH.');
            throw new RuntimeException('Virus scanning is unavailable. Please try again later or contact the administrator.');
        }
        return [$engine, realpath($path)];
    }

    private static function scan(array $images): void
    {
        if (!$images) {
            return;
        }
        [$engine, $executable] = self::scanner();
        $timeout = max(1, min(60, (int) ($_ENV['UPLOAD_SCAN_TIMEOUT'] ?? 20)));
        $deadline = microtime(true) + $timeout;
        foreach ($images as $image) {
            // An argument array bypasses the shell; filenames never become commands.
            $command = $engine === 'defender'
                ? [$executable, '-Scan', '-ScanType', '3', '-File', $image['tmp_name'], '-DisableRemediation']
                : [$executable, '--no-summary', '--alert-exceeds-max=yes', '--max-filesize=6M', '--max-scansize=25M', '--', $image['tmp_name']];
            $output = tmpfile();
            if ($output === false) {
                throw new RuntimeException('Unable to run the security scan. Please try again later.');
            }
            $process = null;
            try {
                $process = @proc_open($command, [
                    0 => ['file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'r'],
                    1 => $output,
                    2 => $output,
                ], $pipes, null, null, ['bypass_shell' => true, 'create_new_console' => false]);
                if (!is_resource($process)) {
                    throw new RuntimeException('Virus scanning is unavailable. Please try again later or contact the administrator.');
                }
                do {
                    $status = proc_get_status($process);
                    if (!$status['running']) {
                        break;
                    }
                    if (microtime(true) >= $deadline) {
                        proc_terminate($process);
                        throw new RuntimeException('The security scan timed out. Please try uploading again.');
                    }
                    usleep(50000);
                } while (true);
                if ($status['exitcode'] !== 0) {
                    rewind($output);
                    error_log('Upload scan rejected a file (engine=' . $engine . ', exit=' . $status['exitcode'] . '): ' . substr((string) stream_get_contents($output), 0, 2000));
                    throw new RuntimeException('The upload was blocked by the security scan. Choose another image or contact the administrator.');
                }
                if (!is_file($image['tmp_name']) || !hash_equals($image['hash'], (string) @hash_file('sha256', $image['tmp_name']))) {
                    throw new RuntimeException('The image failed the security scan. Please choose another image.');
                }
            } finally {
                if (is_resource($process)) {
                    proc_close($process);
                }
                fclose($output);
            }
        }
    }
}
