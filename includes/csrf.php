<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';

function csrfToken(): string
{
    if (
        empty($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token'])
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $submitted = $_POST['csrf_token'] ?? null;

    if (
        !is_string($submitted) ||
        !hash_equals(csrfToken(), $submitted)
    ) {
        http_response_code(403);
        exit('Invalid or expired security token. Refresh the page.');
    }
}

// Compatibility with existing PureVia security code.
function securityCsrfToken(): string
{
    return csrfToken();
}

function verifySecurityCsrf(): void
{
    verifyCsrf();
}
