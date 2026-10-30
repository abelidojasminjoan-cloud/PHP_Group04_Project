<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../services/PasswordReset.php';
header('Cache-Control: no-store');
function resetRedirect(): never{header('Location: '.BASE_URL.'/forgot-password.php',true,303);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');http_response_code(405);exit('POST required.');}
$csrf=$_POST['csrf_token']??null;
if(!is_string($csrf)||empty($_SESSION['csrf_token'])||!hash_equals($_SESSION['csrf_token'],$csrf)){http_response_code(403);exit('Invalid request. Refresh the password reset page and try again.');}
try{
    $action=$_POST['action']??'';
    if($action==='restart'){
        unset($_SESSION['password_reset_challenge'],$_SESSION['password_reset_grant'],$_SESSION['password_reset_email']);resetRedirect();
    }
    if($action==='request'||$action==='resend'){
        $email=$action==='resend'?($_SESSION['password_reset_challenge']['email']??''):($_POST['email']??'');
        if(!is_string($email))throw new DomainException('Enter a valid email address.');
        $_SESSION['password_reset_email']=mb_substr(trim($email),0,255);
        $ip=filter_var($_SERVER['REMOTE_ADDR']??'',FILTER_VALIDATE_IP)?:'unknown';
        $challenge=PasswordReset::request($conn,$email,$ip);
        $_SESSION['password_reset_challenge']=$challenge;unset($_SESSION['password_reset_grant']);
        $_SESSION['password_reset_notice']='A six-digit code was sent to your registered email. It expires in 10 minutes.';
        resetRedirect();
    }
    if($action==='verify'){
        $code=$_POST['otp']??'';
        if(!is_string($code))throw new DomainException('Enter the six-digit code.');
        $grant=PasswordReset::verify($conn,$_SESSION['password_reset_challenge']??[],trim($code));
        session_regenerate_id(true);$_SESSION['password_reset_grant']=$grant;
        $_SESSION['password_reset_notice']='Email verified. Create your new password.';resetRedirect();
    }
    if($action==='reset'){
        $password=$_POST['password']??'';$confirmation=$_POST['confirm_password']??'';
        if(!is_string($password)||!is_string($confirmation))throw new DomainException('Enter and confirm your new password.');
        $result=PasswordReset::complete($conn,$_SESSION['password_reset_grant']??[],$password,$confirmation,securityPolicy($conn));
        $_SESSION=[];session_regenerate_id(true);$_SESSION['csrf_token']=bin2hex(random_bytes(32));
        $_SESSION['login_success']=$result['locked']?'Your password was updated. Your account is still locked; contact support to unlock it.':'Your password was updated. Sign in using your new password.';
        try{require_once __DIR__ . '/../../services/PasswordResetMail.php';if(!sendPureViaPasswordResetNotice($result['email'],$result['name']))error_log('PureVia password-change notice could not be sent.');}catch(Throwable $mailError){error_log('PureVia password-change notice failed.');}
        header('Location: '.BASE_URL.'/login.php',true,303);exit;
    }
    throw new DomainException('Invalid password reset action.');
}catch(Throwable $error){
    if(!$error instanceof DomainException)error_log('PureVia password reset: '.$error->getMessage());
    $_SESSION['password_reset_error']=$error instanceof DomainException?$error->getMessage():'Unable to complete this step. Please try again. If you requested an email, wait a minute before resending.';
    resetRedirect();
}
