<?php
declare(strict_types=1);

/** Encode text for HTML text nodes and quoted HTML attributes. */
function pvHtml(mixed $value, int $flags = ENT_QUOTES, string $encoding = 'UTF-8', bool $doubleEncode = true): string
{
    return htmlspecialchars((string) ($value ?? ''), $flags | ENT_QUOTES | ENT_SUBSTITUTE, $encoding, $doubleEncode);
}

/** Encode JSON safely even when it is embedded inside an HTML script element. */
function pvJson(mixed $value, int $flags = 0): string
{
    return json_encode($value, $flags | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS |
        JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
}

/** A new nonce for each response; only explicitly marked application scripts may execute. */
function pvScriptNonce(): string
{
    static $nonce;
    return $nonce ??= base64_encode(random_bytes(24));
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: script-src 'nonce-" . pvScriptNonce() . "' 'strict-dynamic' 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.google.com https://www.gstatic.com; script-src-attr 'none'; object-src 'none'; base-uri 'none'; frame-ancestors 'self'; form-action 'self'");
}
