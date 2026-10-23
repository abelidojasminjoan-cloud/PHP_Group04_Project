<?php
declare(strict_types=1);

// PUREVIA - MANDATORY TWO-FACTOR AUTHENTICATION
// Self-service disabling is not permitted.

require_once __DIR__ . '/../../config/app.php';

header('Cache-Control: no-store, private');

http_response_code(403);

exit(
    'Google Authenticator is required for all PureVia accounts. ' .
    'Please contact an authorized administrator if you need ' .
    'assistance recovering access to your account.'
);
