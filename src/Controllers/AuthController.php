<?php

declare(strict_types=1);

namespace EduQR\Controllers;

use EduQR\Services\AuthService;
use EduQR\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;



final class AuthController
{
    private AuthService $authService;

    private \EduQR\Repositories\AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->auditRepo = new \EduQR\Repositories\AuditLogRepository();
    }

    public function showLogin(): void
    {
        if (AuthService::check()) {
            header('Location: ' . eduqr_path('/admin/dashboard'));
            exit;
        }
        include __DIR__ . '/../../templates/auth/login.php';
    }

    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        \EduQR\Middleware\RateLimitMiddleware::check($ip, 'login');

        // 1. E-posta format kontrolü
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = t('auth.login.invalid_email_format');
            include __DIR__ . '/../../templates/auth/login.php';
            exit;
        }

        // 2. Alan adı (domain) MX/A kaydı kontrolü
        $domain = substr(strrchr($email, "@"), 1);
        $isLocalDomain = preg_match('/\.(local|test|dev)$|localhost$/i', $domain);
        if (!$isLocalDomain) {
            if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
                $error = t('auth.login.invalid_email_domain');
                include __DIR__ . '/../../templates/auth/login.php';
                exit;
            }
        }

        try {
            if ($this->authService->login($email, $password)) {
                \EduQR\Middleware\RateLimitMiddleware::record($ip, 'login', true);
                $this->auditRepo->log('login', 'instructor', (int)($_SESSION['user_id'] ?? 0), 'user', (int)($_SESSION['user_id'] ?? 0), ['email' => $email]);
                header('Location: ' . eduqr_path('/admin/dashboard'));
                exit;
            }
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'unverified') {
                $userRepo = new \EduQR\Repositories\UserRepository();
                $user = $userRepo->findByEmail($email);
                if ($user !== null) {
                    $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                    $expiresAt = date('Y-m-d H:i:s', time() + 900);
                    $userRepo->updateVerificationCode((int)$user['id'], $code, $expiresAt);
                    $this->sendVerificationEmail($email, $code);

                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        session_start();
                    }
                    $_SESSION['verify_email'] = $email;

                    $error = t('auth.login.unverified');
                    include __DIR__ . '/../../templates/auth/verify.php';
                    exit;
                }
            }
        }

        \EduQR\Middleware\RateLimitMiddleware::record($ip, 'login', false);
        $error = t('auth.login.invalid');
        include __DIR__ . '/../../templates/auth/login.php';
    }

    public function logout(): void
    {
        $this->authService->logout();
        header('Location: ' . eduqr_path('/login'));
        exit;
    }

    public function showRegister(): void
    {
        if (AuthService::check()) {
            header('Location: ' . eduqr_path('/admin/dashboard'));
            exit;
        }
        include __DIR__ . '/../../templates/auth/register.php';
    }

    public function register(): void
    {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $error = t('common.error');
            include __DIR__ . '/../../templates/auth/register.php';
            exit;
        }

        // 1. E-posta format kontrolü
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = t('auth.login.invalid_email_format');
            include __DIR__ . '/../../templates/auth/register.php';
            exit;
        }

        // 2. Alan adı (domain) MX/A kaydı kontrolü
        $domain = substr(strrchr($email, "@"), 1);
        $isLocalDomain = preg_match('/\.(local|test|dev)$|localhost$/i', $domain);
        if (!$isLocalDomain) {
            if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
                $error = t('auth.login.invalid_email_domain');
                include __DIR__ . '/../../templates/auth/register.php';
                exit;
            }
        }

        // 3. E-posta adresi önceden eklenmiş mi kontrol et
        $userRepo = new \EduQR\Repositories\UserRepository();
        if ($userRepo->findByEmail($email) !== null) {
            $error = t('auth.register.email_exists');
            include __DIR__ . '/../../templates/auth/register.php';
            exit;
        }

        // 4. Şifreyi bcrypt ile hash'le (cost 12)
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // 5. Doğrulama kodu oluştur
        $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 dakika geçerli

        $userId = $userRepo->create($name, $email, $passwordHash, 'instructor', 0, $code, $expiresAt);
        $this->auditRepo->log('user_register', 'instructor', $userId, 'user', $userId, ['email' => $email]);
        $this->sendVerificationEmail($email, $code);

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['verify_email'] = $email;

        header('Location: ' . eduqr_path('/verify-email'));
        exit;
    }

    public function showVerifyEmail(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['verify_email'])) {
            header('Location: ' . eduqr_path('/login'));
            exit;
        }
        include __DIR__ . '/../../templates/auth/verify.php';
    }

    public function verifyEmail(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $email = $_SESSION['verify_email'] ?? '';
        if ($email === '') {
            header('Location: ' . eduqr_path('/login'));
            exit;
        }

        $code = trim($_POST['code'] ?? '');
        $userRepo = new \EduQR\Repositories\UserRepository();
        $user = $userRepo->findByEmail($email);

        if ($user !== null && $user['verification_code'] === $code && $user['verification_code'] !== null) {
            $expires = strtotime($user['verification_expires_at'] ?? '');
            if ($expires > time()) {
                $userRepo->markAsVerified((int)$user['id']);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                unset($_SESSION['verify_email']);

                header('Location: ' . eduqr_path('/admin/dashboard'));
                exit;
            }
        }

        $error = t('auth.verify.invalid');
        include __DIR__ . '/../../templates/auth/verify.php';
    }

    private function sendVerificationEmail(string $email, string $code): void
    {
        $subject = "eduQR E-posta Doğrulama Kodu";
        $message = "eduQR sistemine kayıt olduğunuz için teşekkürler.\n\nDoğrulama kodunuz: {$code}\nBu kod 15 dakika geçerlidir.";

        $logPath = __DIR__ . '/../../temp_mail.txt';
        $logContent = "=== E-POSTA GÖNDERİLDİ ===\n" .
                      "Alıcı: {$email}\n" .
                      "Konu: {$subject}\n" .
                      "Tarih: " . date('Y-m-d H:i:s') . "\n" .
                      "Kod: {$code}\n" .
                      "===========================\n\n";
        @file_put_contents($logPath, $logContent, FILE_APPEND);

        $smtpHost = Config::get('SMTP_HOST', '');
        $smtpUser = Config::get('SMTP_USER', '');
        $smtpPass = Config::get('SMTP_PASS', '');

        if ($smtpUser === '' || $smtpPass === '' || $smtpPass === 'your-16-character-app-password') {
            return;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            
            $secure = Config::get('SMTP_SECURE', 'ssl');
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
            
            $mail->Port = (int)Config::get('SMTP_PORT', '465');
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($smtpUser, Config::get('SMTP_FROM_NAME', 'eduQR'));
            $mail->addAddress($email);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
        } catch (Exception $e) {
            $errContent = "=== MAİL GÖNDERİM HATASI ===\n" .
                          "Hata: " . $mail->ErrorInfo . "\n" .
                          "============================\n\n";
            @file_put_contents($logPath, $errContent, FILE_APPEND);
        }
    }

    public function forgotPassword(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $rawBody = file_get_contents('php://input') ?: '{}';
        $body = json_decode($rawBody, true) ?: [];

        $email = trim((string)($body['email'] ?? $_POST['email'] ?? ''));

        if ($email === '') {
            echo json_encode(['success' => false, 'error' => t('auth.login.invalid_email_format')]);
            exit;
        }

        $userRepo = new \EduQR\Repositories\UserRepository();
        $user = $userRepo->findByEmail($email);

        if ($user !== null) {
            // Generate reset code
            $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 mins

            $userRepo->updateVerificationCode((int)$user['id'], $code, $expiresAt);
            $this->sendResetEmail($email, $code);
        }

        // Return success always to prevent email enumeration
        echo json_encode(['success' => true]);
        exit;
    }

    private function sendResetEmail(string $email, string $code): void
    {
        $subject = "eduQR Şifre Sıfırlama Kodu";
        $message = "eduQR şifrenizi sıfırlamak için doğrulama kodunuz:\n\n{$code}\nBu kod 15 dakika geçerlidir.\nEğer bu isteği siz yapmadıysanız bu e-postayı dikkate almayınız.";

        $logPath = __DIR__ . '/../../temp_mail.txt';
        $logContent = "=== ŞİFRE SIFIRLAMA E-POSTASI GÖNDERİLDİ ===\n" .
                      "Alıcı: {$email}\n" .
                      "Konu: {$subject}\n" .
                      "Tarih: " . date('Y-m-d H:i:s') . "\n" .
                      "Kod: {$code}\n" .
                      "===========================================\n\n";
        @file_put_contents($logPath, $logContent, FILE_APPEND);

        $smtpHost = Config::get('SMTP_HOST', '');
        $smtpUser = Config::get('SMTP_USER', '');
        $smtpPass = Config::get('SMTP_PASS', '');

        if ($smtpUser === '' || $smtpPass === '' || $smtpPass === 'your-16-character-app-password') {
            return;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            
            $secure = Config::get('SMTP_SECURE', 'ssl');
            if ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
            
            $mail->Port = (int)Config::get('SMTP_PORT', '465');
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($smtpUser, Config::get('SMTP_FROM_NAME', 'eduQR'));
            $mail->addAddress($email);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();
        } catch (Exception $e) {
            $errContent = "=== MAİL GÖNDERİM HATASI (SIFIRLAMA) ===\n" .
                          "Hata: " . $mail->ErrorInfo . "\n" .
                          "=======================================\n\n";
            @file_put_contents($logPath, $errContent, FILE_APPEND);
        }
    }

    public function resetPassword(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $rawBody = file_get_contents('php://input') ?: '{}';
        $body = json_decode($rawBody, true) ?: [];

        $email    = trim((string)($body['email'] ?? $_POST['email'] ?? ''));
        $code     = trim((string)($body['code'] ?? $_POST['code'] ?? ''));
        $password = $body['password'] ?? $_POST['password'] ?? '';

        if ($email === '' || $code === '' || $password === '') {
            echo json_encode(['success' => false, 'error' => 'Tüm alanlar zorunludur.']);
            exit;
        }

        $userRepo = new \EduQR\Repositories\UserRepository();
        $user = $userRepo->findByEmail($email);

        if ($user === null || $user['verification_code'] !== $code || $user['verification_code'] === null) {
            echo json_encode(['success' => false, 'error' => t('auth.login.forgot_password_error_invalid_code')]);
            exit;
        }

        $expires = strtotime($user['verification_expires_at'] ?? '');
        if ($expires <= time()) {
            echo json_encode(['success' => false, 'error' => t('auth.login.forgot_password_error_invalid_code')]);
            exit;
        }

        // Hashing the password (cost 12)
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $userRepo->updatePassword((int)$user['id'], $passwordHash);

        echo json_encode(['success' => true]);
        exit;
    }

    public function changePassword(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        $rawBody = file_get_contents('php://input') ?: '{}';
        $body = json_decode($rawBody, true) ?: [];

        $oldPassword = $body['old_password'] ?? $_POST['old_password'] ?? '';
        $newPassword = $body['new_password'] ?? $_POST['new_password'] ?? '';
        $confirmPassword = $body['confirm_password'] ?? $_POST['confirm_password'] ?? '';

        if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
            echo json_encode(['success' => false, 'error' => t('error.all_fields_required')]);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'error' => t('auth.login.forgot_password_error_mismatch')]);
            exit;
        }

        $userRepo = new \EduQR\Repositories\UserRepository();
        $dbUser = $userRepo->findById((int)$user['id']);

        if ($dbUser === null || !password_verify($oldPassword, $dbUser['password_hash'])) {
            echo json_encode(['success' => false, 'error' => t('auth.login.invalid')]);
            exit;
        }

        // Hashing the password (cost 12)
        $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $userRepo->updatePassword((int)$dbUser['id'], $passwordHash);

        echo json_encode(['success' => true]);
        exit;
    }

    public function updateProfile(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        $rawBody = file_get_contents('php://input') ?: '{}';
        $body = json_decode($rawBody, true) ?: [];

        $name = trim($body['name'] ?? $_POST['name'] ?? '');
        $email = trim($body['email'] ?? $_POST['email'] ?? '');

        if ($name === '' || $email === '') {
            echo json_encode(['success' => false, 'error' => t('error.all_fields_required')]);
            exit;
        }

        $userRepo = new \EduQR\Repositories\UserRepository();
        $dbUser = $userRepo->findById((int)$user['id']);

        if ($dbUser === null) {
            echo json_encode(['success' => false, 'error' => t('error.user_not_found')]);
            exit;
        }

        // Check if email is being changed and is already taken
        if ($email !== $dbUser['email']) {
            $existing = $userRepo->findByEmail($email);
            if ($existing !== null) {
                echo json_encode(['success' => false, 'error' => t('error.email_in_use')]);
                exit;
            }
        }

        $userRepo->updateProfile((int)$dbUser['id'], $name, $email);

        // Update session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['user_name'] = $name;

        echo json_encode(['success' => true]);
        exit;
    }

    public function deleteAccount(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        $rawBody  = file_get_contents('php://input') ?: '{}';
        $body     = json_decode($rawBody, true) ?: [];
        $password = $body['password'] ?? '';

        if ($password === '') {
            echo json_encode(['success' => false, 'error' => t('error.password_required')]);
            exit;
        }

        $userRepo = new \EduQR\Repositories\UserRepository();
        $dbUser   = $userRepo->findById((int)$user['id']);

        if ($dbUser === null || !password_verify($password, $dbUser['password_hash'])) {
            echo json_encode(['success' => false, 'error' => t('error.incorrect_password_delete')]);
            exit;
        }

        // Tüm verileri ve hesabı sil
        $userRepo->deleteById((int)$dbUser['id']);

        // Oturumu kapat
        $this->authService->logout();

        echo json_encode(['success' => true, 'redirect' => eduqr_path('/login')]);
        exit;
    }
}
