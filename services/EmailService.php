<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendPureViaMail(
    string $email,
    string $name,
    string $subject,
    string $html,
    string $plain
): bool {
    $config = require __DIR__ . '/../config/mail.php';

    if (
        empty($config['username']) ||
        empty($config['password']) ||
        empty($config['from_email'])
    ) {
        error_log('PureVia SMTP settings are incomplete.');
        return false;
    }

    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->Port = (int) $config['port'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            $config['from_email'],
            $config['from_name']
        );

        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = $plain;

        return $mail->send();

    } catch (Exception $e) {
        error_log('PureVia mail error: ' . $e->getMessage());
        return false;
    }
}

function sendOtpEmail(
    string $email,
    string $name,
    string $otp
): bool {
    $safeName = pvHtml(
        $name,
        ENT_QUOTES,
        'UTF-8'
    );

    $html = '
    <div style="font-family:Arial,sans-serif;
                max-width:540px;margin:auto;padding:30px;">
        <h2 style="color:#426b58;">Welcome to PureVia!</h2>

        <p>Hello ' . $safeName . ',</p>

        <p>Thank you for registering. Enter this
        one-time password to verify your email address:</p>

        <div style="background:#f0f5f1;padding:22px;
                    text-align:center;border-radius:10px;">
            <strong style="font-size:34px;
                           letter-spacing:8px;color:#426b58;">'
                . $otp .
            '</strong>
        </div>

        <p>This code expires in <b>10 minutes</b>.</p>

        <p>Do not share this code with anyone.</p>

        <p>If you did not register, ignore this email.</p>

        <p>PureVia Team</p>
    </div>';

    $plain = "Hello {$name},\n\n"
        . "Your PureVia verification code is: {$otp}\n"
        . "This code expires in 10 minutes.\n"
        . "Do not share it with anyone.";

    return sendPureViaMail(
        $email,
        $name,
        'Your PureVia Account Verification Code',
        $html,
        $plain
    );
}

function sendActivationEmail(
    string $email,
    string $name,
    string $activationUrl
): bool {
    $safeName = pvHtml(
        $name,
        ENT_QUOTES,
        'UTF-8'
    );

    $safeUrl = pvHtml(
        $activationUrl,
        ENT_QUOTES,
        'UTF-8'
    );

    $html = '
    <div style="font-family:Arial,sans-serif;
                max-width:540px;margin:auto;padding:30px;">

        <h2 style="color:#426b58;">
            Activate Your PureVia Account
        </h2>

        <p>Hello ' . $safeName . ',</p>

        <p>Your email address has been verified
        successfully.</p>

        <p>Click the button below to activate your account.
        You will then be taken to the login page.</p>

        <p style="margin:30px 0;">
            <a href="' . $safeUrl . '"
               style="background:#426b58;color:white;
                      padding:14px 24px;border-radius:6px;
                      text-decoration:none;">
                Activate Account &amp; Sign In
            </a>
        </p>

        <p>This link expires in 30 minutes and can only be used once.</p>

        <p>If you did not create this account, ignore this email.</p>

        <p>PureVia Team</p>
    </div>';

    $plain = "Hello {$name},\n\n"
        . "Your email address has been verified.\n"
        . "Activate your account and continue to login: {$activationUrl}\n\n"
        . "This single-use link expires in 30 minutes.\n"
        . "If you did not create this account, ignore this email.";

    return sendPureViaMail(
        $email,
        $name,
        'Activate Your PureVia Account',
        $html,
        $plain
    );
}
