<?php
declare(strict_types=1);
require_once __DIR__.'/EmailService.php';

function sendPureViaPasswordResetCode(string $email,string $name,string $code): bool
{
    $html='<div style="font-family:Arial,sans-serif;max-width:540px;margin:auto;padding:30px"><h2 style="color:#38634e">Reset your PureVia password</h2><p>Hello '.pvHtml($name).',</p><p>Enter this code on the password reset page:</p><p style="font-size:30px;letter-spacing:6px;font-weight:bold">'.pvHtml($code).'</p><p>This code expires in 10 minutes and can be used once. Do not share it.</p><p>If you did not request this, you can ignore this email. Your password has not changed.</p></div>';
    $plain="Hello {$name},\n\nYour PureVia password reset code is: {$code}\nIt expires in 10 minutes. Do not share it.\nIf you did not request this, ignore this email; your password has not changed.";
    return sendPureViaMail($email,$name,'Your PureVia password reset code',$html,$plain,15);
}

function sendPureViaPasswordResetNotice(string $email,string $name): bool
{
    return sendPureViaMail($email,$name,'Your PureVia password was changed',
        '<div style="font-family:Arial,sans-serif;max-width:540px;margin:auto;padding:30px"><h2>Your password was changed</h2><p>Hello '.pvHtml($name).',</p><p>Your PureVia password was reset successfully. Existing signed-in sessions have been revoked.</p><p>If you did not make this change, contact PureVia support immediately.</p></div>',
        "Hello {$name},\nYour PureVia password was reset successfully. Existing sessions have been revoked. If you did not make this change, contact PureVia support immediately.",15);
}
