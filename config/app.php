<?php

/* =========================================================
   PUREVIA
   APPLICATION CONFIGURATION
========================================================= */
declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$autoload = $projectRoot . '/vendor/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException(
        'Composer dependencies missing. Run composer install.'
    );
}

require_once $autoload;

$dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->load();

/* =========================================================
   APPLICATION INFORMATION
========================================================= */

define(
    'APP_NAME',
    'PureVia'
);

define(
    'APP_DESCRIPTION',
    'Clean skincare with clear guidance.'
);


/* =========================================================
   APPLICATION ENVIRONMENT

   development = local XAMPP
   production  = deployed website
========================================================= */

define(
    'APP_ENV',
    strtolower($_ENV['APP_ENV'] ?? 'local')
);

define(
    'APP_URL',
    rtrim(
        $_ENV['APP_URL'] ?? 'http://localhost/purevia_website',
        '/'
    )
);

/* =========================================================
   ERROR DISPLAY

   Development:
   Show PHP errors.

   Production:
   Hide PHP errors from users.
========================================================= */

if (APP_ENV !== 'production') {

    error_reporting(E_ALL);

    ini_set(
        'display_errors',
        '1'
    );

} else {

    error_reporting(E_ALL);

    ini_set(
        'display_errors',
        '0'
    );

}


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set(
    'Asia/Manila'
);


/* =========================================================
   BASE URL

   XAMPP example:

   http://localhost/purevia/

   If your actual folder is purevia_website,
   change /purevia to /purevia_website.
========================================================= */

define(
    'BASE_URL',
    '/purevia_website'
);


/* =========================================================
   ADMIN URL
========================================================= */

define(
    'ADMIN_URL',
    BASE_URL . '/admin'
);


/* =========================================================
   ASSET URL
========================================================= */

define(
    'ASSET_URL',
    BASE_URL . '/assets'
);


/* =========================================================
   UPLOAD URL
========================================================= */

define(
    'UPLOAD_URL',
    BASE_URL . '/uploads'
);


/* =========================================================
   ROOT DIRECTORY
========================================================= */

define(
    'ROOT_PATH',
    dirname(__DIR__)
);


/* =========================================================
   UPLOAD DIRECTORY
========================================================= */

define(
    'UPLOAD_PATH',
    ROOT_PATH . '/uploads'
);


/* =========================================================
   PRODUCT IMAGE DIRECTORY
========================================================= */

define(
    'PRODUCT_UPLOAD_PATH',
    UPLOAD_PATH . '/products'
);


/* =========================================================
   MAXIMUM PRODUCT IMAGE SIZE

   5 MB
========================================================= */

define(
    'MAX_IMAGE_SIZE',
    5 * 1024 * 1024
);


/* =========================================================
   ALLOWED PRODUCT IMAGE TYPES
========================================================= */

define(
    'ALLOWED_IMAGE_TYPES',
    [
        'image/jpeg',
        'image/png',
        'image/webp'
    ]
);


/* =========================================================
   SESSION CONFIGURATION

   Configure cookies BEFORE session_start().
========================================================= */

if (session_status() === PHP_SESSION_NONE) {

    ini_set(
        'session.use_strict_mode',
        '1'
    );

    ini_set(
        'session.use_only_cookies',
        '1'
    );

    ini_set(
        'session.cookie_httponly',
        '1'
    );


    /*
     * Local XAMPP normally uses HTTP,
     * so secure must be false locally.

     * Production HTTPS should use true.
     */

    $secureCookie =
        APP_ENV === 'production';


    session_set_cookie_params([

        'lifetime' => 0,

        'path' => '/',

        'secure' => $secureCookie,

        'httponly' => true,

        'samesite' => 'Lax'

    ]);


    session_start();

}


/* =========================================================
   SESSION TIMEOUT

   30 minutes
========================================================= */

define(
    'SESSION_TIMEOUT',
    1800
);


// The database bootstrap applies the saved session timeout before authorizing requests.


/* =========================================================
   LOGIN SECURITY
========================================================= */


/*
 * Maximum failed login attempts before
 * permanent account lockout. A locked account must be manually unlocked.
 */

define(
    'MAX_LOGIN_ATTEMPTS',
    3
);


/* =========================================================
   PASSWORD RESET
========================================================= */


/*
 * Password reset token validity:
 * 30 minutes
 */

define(
    'PASSWORD_RESET_EXPIRY',
    30 * 60
);


/* =========================================================
   OTP
========================================================= */


/*
 * OTP validity:
 * 10 minutes
 */

define(
    'OTP_EXPIRY',
    10 * 60
);


/* =========================================================
   PAGINATION
========================================================= */

define(
    'ITEMS_PER_PAGE',
    10
);
