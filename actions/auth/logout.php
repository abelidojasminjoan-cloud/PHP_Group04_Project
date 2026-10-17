<?php

/* =========================================================
   PUREVIA - LOGOUT
========================================================= */

require_once __DIR__ . '/../../config/app.php';


/* =========================================================
   START SESSION IF NEEDED
========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/* =========================================================
   CLEAR SESSION VARIABLES
========================================================= */

$_SESSION = [];


/* =========================================================
   DELETE SESSION COOKIE
========================================================= */

if (ini_get('session.use_cookies')) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}


/* =========================================================
   DESTROY SESSION
========================================================= */

session_destroy();


/* =========================================================
   REDIRECT TO PUREVIA HOMEPAGE
========================================================= */

header('Location: ' . BASE_URL . '/public/index.php');
exit;