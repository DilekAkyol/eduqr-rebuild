<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use EduQR\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

Config::load('.env');

$smtpHost = Config::get('SMTP_HOST', '');
$smtpUser = Config::get('SMTP_USER', '');
$smtpPass = Config::get('SMTP_PASS', '');
$smtpPort = (int)Config::get('SMTP_PORT', '465');
$smtpSecure = Config::get('SMTP_SECURE', 'ssl');

echo "SMTP Bağlantısı Test Ediliyor...\n";
echo "Host: {$smtpHost}\n";
echo "Port: {$smtpPort}\n";
echo "User: {$smtpUser}\n";
echo "Secure: {$smtpSecure}\n\n";

$mail = new PHPMailer(true);

try {
    // Enable SMTP Debugging
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtpHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUser;
    $mail->Password   = $smtpPass;
    
    if ($smtpSecure === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($smtpSecure === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } else {
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;
    }
    
    $mail->Port = $smtpPort;
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($smtpUser, 'eduQR Test');
    $mail->addAddress($smtpUser);

    $mail->Subject = 'eduQR SMTP Test Mail';
    $mail->Body    = 'Bu bir SMTP test e-postasıdır. Bağlantınız başarıyla kurulmuştur!';

    $mail->send();
    echo "\n🎉 BAŞARILI! E-posta başarıyla gönderildi.\n";
} catch (Exception $e) {
    echo "\n❌ HATA OLUŞTU: " . $e->getMessage() . "\n";
    echo "Mail ErrorInfo: " . $mail->ErrorInfo . "\n";
}
