<?php
declare(strict_types=1);

// PUREVIA - GOOGLE AUTHENTICATOR SERVICE

function pvBase32Encode(string $data): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';

    foreach (str_split($data) as $char) {
        $bits .= str_pad(
            decbin(ord($char)),
            8,
            '0',
            STR_PAD_LEFT
        );
    }

    $result = '';

    foreach (str_split($bits, 5) as $chunk) {
        $result .= $alphabet[
            bindec(str_pad($chunk, 5, '0'))
        ];
    }

    return $result;
}

function pvBase32Decode(string $secret): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper(rtrim($secret, '='));

    if ($secret === '' ||
        preg_match('/[^A-Z2-7]/', $secret)) {
        throw new InvalidArgumentException(
            'Invalid authenticator secret.'
        );
    }

    $bits = '';

    foreach (str_split($secret) as $char) {
        $position = strpos($alphabet, $char);
        $bits .= str_pad(
            decbin($position),
            5,
            '0',
            STR_PAD_LEFT
        );
    }

    $result = '';

    foreach (str_split($bits, 8) as $chunk) {
        if (strlen($chunk) === 8) {
            $result .= chr(bindec($chunk));
        }
    }

    return $result;
}

function pvGenerateSecret(): string
{
    return pvBase32Encode(random_bytes(20));
}

function pvTotpAtStep(string $secret, int $step): string
{
    $key = pvBase32Decode($secret);

    $counter = pack(
        'N2',
        ($step >> 32) & 0xffffffff,
        $step & 0xffffffff
    );

    $hash = hash_hmac('sha1', $counter, $key, true);
    $offset = ord($hash[19]) & 15;

    $number = (
        unpack('N', substr($hash, $offset, 4))[1]
        & 0x7fffffff
    ) % 1000000;

    return str_pad(
        (string) $number,
        6,
        '0',
        STR_PAD_LEFT
    );
}

function pvVerifyTotp(
    string $secret,
    string $code,
    int $lastStep = -1
): ?int {
    if (!preg_match('/^[0-9]{6}$/D', $code)) {
        return null;
    }

    $now = intdiv(time(), 30);

    for ($step = $now - 1; $step <= $now + 1; $step++) {
        if (
            $step > $lastStep &&
            hash_equals(
                pvTotpAtStep($secret, $step),
                $code
            )
        ) {
            return $step;
        }
    }

    return null;
}

function pvTotpKey(): string
{
    $encoded = $_ENV['TOTP_ENCRYPTION_KEY'] ?? '';

    $key = base64_decode($encoded, true);

    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException(
            'Invalid encryption key configuration.'
        );
    }

    return $key;
}

function pvEncryptSecret(string $secret): string
{
    $nonce = random_bytes(12);
    $tag = '';

    $cipher = openssl_encrypt(
        $secret,
        'aes-256-gcm',
        pvTotpKey(),
        OPENSSL_RAW_DATA,
        $nonce,
        $tag
    );

    if ($cipher === false) {
        throw new RuntimeException('Encryption failed.');
    }

    return base64_encode($nonce . $tag . $cipher);
}

function pvDecryptSecret(string $stored): string
{
    $data = base64_decode($stored, true);

    if ($data === false || strlen($data) < 29) {
        throw new RuntimeException('Invalid encrypted secret.');
    }

    $plain = openssl_decrypt(
        substr($data, 28),
        'aes-256-gcm',
        pvTotpKey(),
        OPENSSL_RAW_DATA,
        substr($data, 0, 12),
        substr($data, 12, 16)
    );

    if ($plain === false) {
        throw new RuntimeException('Decryption failed.');
    }

    return $plain;
}

function pvGenerateRecoveryCodes(): array
{
    $codes = [];

    for ($i = 0; $i < 8; $i++) {
        $codes[] = strtoupper(
            bin2hex(random_bytes(5))
        );
    }

    return $codes;
}
