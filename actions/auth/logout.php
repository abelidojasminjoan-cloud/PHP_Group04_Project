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

// Audit the authenticated actor before clearing the session. Audit failures never prevent sign-out.
$logoutUserId=(int)($_SESSION['user_id']??0);
if($logoutUserId>0){
    try{
        require_once __DIR__.'/../../services/AuditContext.php';
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        // Avoid loading session-enforcement middleware during logout; reuse the private .env settings.
        $auditConnection=new mysqli($_ENV['DB_HOST']??'localhost',$_ENV['DB_USERNAME']??'root',$_ENV['DB_PASSWORD']??'',$_ENV['DB_NAME']??'purevia_db',(int)($_ENV['DB_PORT']??3306));
        $auditConnection->set_charset('utf8mb4');
        recordPureViaAuthAudit($auditConnection,$logoutUserId,'LOGOUT');
        $auditConnection->close();
    }catch(Throwable $error){error_log('PureVia logout audit failed: '.$error->getMessage());}
}
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