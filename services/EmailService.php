<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail(
    string $recipientEmail,
    string $recipientName,
    string $token
): bool {
    $config = require __DIR__ . '/../config/mail.php';

    if (
        empty($config['username']) ||
        empty($config['password']) ||
        empty($config['from_email'])
    ) {
        error_log('PureVia SMTP configuration is incomplete.');
        return false;
    }

    $verificationUrl = APP_URL
        . '/verify-email.php?token='
        . urlencode($token);

    $safeName = htmlspecialchars(
        $recipientName,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $safeUrl = htmlspecialchars(
        $verificationUrl,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->Port = $config['port'];

        $mail->SMTPSecure =
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            $config['from_email'],
            $config['from_name']
        );

        $mail->addAddress(
            $recipientEmail,
            $recipientName
        );

        $mail->isHTML(true);

        $mail->Subject = 'Verify Your PureVia Account';

        $mail->Body = '
            <div style="
                font-family: Arial, sans-serif;
                max-width: 560px;
                margin: auto;
                padding: 24px;
            ">
                <h2>Welcome to PureVia!</h2>

                <p>Hello ' . $safeName . ',</p>

                <p>
                    Thank you for creating your PureVia account.
                    Please verify your email address to activate
                    your account.
                </p>

                <p>
                    <a href="' . $safeUrl . '"
                       style="
                           display:inline-block;
                           padding:12px 20px;
                           background:#426b58;
                           color:#ffffff;
                           text-decoration:none;
                           border-radius:6px;
                       ">
                        Verify Email Address
                    </a>
                </p>

                <p>
                    This verification link expires in 30 minutes.
                </p>

                <p>
                    If you did not create this account,
                    you can ignore this email.
                </p>

                <p>PureVia Team</p>
            </div>
        ';

        $mail->AltBody =
            "Welcome to PureVia, {$recipientName}!\n\n"
            . "Verify your email address:\n"
            . $verificationUrl
            . "\n\nThis link expires in 30 minutes.";

        return $mail->send();

    } catch (Exception $e) {
        error_log(
            'PureVia verification email failed: '
            . $e->getMessage()
        );

        return false;
    }
}
