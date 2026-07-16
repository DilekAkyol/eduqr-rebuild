<?php

declare(strict_types=1);

namespace EduQR\Controllers;

use EduQR\Repositories\SessionRepository;
use EduQR\Repositories\ParticipantRepository;

final class JoinController
{
    private SessionRepository $sessionRepo;
    private ParticipantRepository $participantRepo;

    public function __construct()
    {
        $this->sessionRepo = new SessionRepository();
        $this->participantRepo = new ParticipantRepository();
    }

    public function showHome(): void
    {
        include __DIR__ . '/../../templates/home.php';
    }

    public function joinCode(): void
    {
        $code = strtoupper(trim($_POST['short_code'] ?? ''));
        
        if ($code === '') {
            $error = "Oda kodu boş olamaz!";
            include __DIR__ . '/../../templates/home.php';
            exit;
        }

        $session = $this->sessionRepo->findByShortCode($code);

        if ($session === null || $session['status'] === 'closed') {
            $error = "Geçersiz veya süresi dolmuş katılım kodu!";
            include __DIR__ . '/../../templates/home.php';
            exit;
        }

        // Yönlendir
        header('Location: ' . eduqr_path('/join/' . $code));
        exit;
    }

    public function showJoin(array $params): void
    {
        $shortCode = strtoupper($params['short_code']);
        $session = $this->sessionRepo->findByShortCode($shortCode);

        if ($session === null) {
            http_response_code(404);
            echo "<h1>Oturum Bulunamadı (404)</h1>";
            exit;
        }

        if ($session['status'] === 'closed') {
            echo "<h1>Oturum Sonlandırılmış</h1><p>Bu oylama oturumu öğretmen tarafından kapatılmıştır.</p>";
            exit;
        }

        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        // Eğer timeout parametresi varsa katılımcıyı sil, çerezleri temizle ve otomatik yönlendirmeyi atla
        if (isset($_GET['timeout'])) {
            $participantId = (int)($_COOKIE['eduqr_participant'] ?? 0);
            if ($participantId > 0) {
                $this->participantRepo->delete($participantId);
            }
            setcookie('eduqr_participant', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'secure' => $isSecure,
                'samesite' => 'Lax'
            ]);
            setcookie('eduqr_device', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'secure' => $isSecure,
                'samesite' => 'Lax'
            ]);
        } else {
            // Cihaz çerezini veya oturum çerezini kontrol et, zaten katıldıysa doğrudan bekleme sayfasına yönlendir
            $deviceCookie = $_COOKIE['eduqr_device'] ?? null;
            if ($deviceCookie !== null) {
                $participant = $this->participantRepo->findByDeviceCookieAndSessionId($deviceCookie, (int)$session['id']);
                if ($participant !== null) {
                    // Katılımcı çerezini tazele
                    setcookie('eduqr_participant', (string)$participant['id'], [
                        'expires' => 0,
                        'path' => '/',
                        'httponly' => true,
                        'secure' => $isSecure,
                        'samesite' => 'Lax'
                    ]);
                    header('Location: ' . eduqr_path('/join/' . $shortCode . '/wait'));
                    exit;
                }
            }
        }

        include __DIR__ . '/../../templates/student/join.php';
    }

    public function join(array $params): void
    {
        $shortCode = strtoupper($params['short_code']);
        $session = $this->sessionRepo->findByShortCode($shortCode);

        if ($session === null || $session['status'] === 'closed') {
            header('Location: ' . eduqr_path('/join/' . $shortCode));
            exit;
        }

        // Anonim katılım: nickname gerekmez, cihaz kimliğinden anonim etiket üretilir
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        // Cihaz kimliği için çerez oluştur veya mevcudu oku (persistent)
        $deviceCookie = $_COOKIE['eduqr_device'] ?? null;
        if ($deviceCookie === null) {
            $deviceCookie = bin2hex(random_bytes(16));
            setcookie('eduqr_device', $deviceCookie, [
                'expires' => time() + 365 * 24 * 3600,
                'path' => '/',
                'httponly' => true,
                'secure' => $isSecure,
                'samesite' => 'Lax'
            ]);
        }

        $nickname = trim($_POST['nickname'] ?? '');
        if ($nickname === '') {
            $nickname = 'anon-' . substr($deviceCookie, 0, 8);
        }

        // Aynı cihaz daha önce bu oturuma katılmış mı?
        $existing = $this->participantRepo->findByDeviceCookieAndSessionId($deviceCookie, (int)$session['id']);
        if ($existing !== null) {
            $participantId = (int)$existing['id'];
            $updateStmt = \EduQR\Support\Database::connect()->prepare("UPDATE participants SET nickname = :nickname WHERE id = :id");
            $updateStmt->execute(['nickname' => $nickname, 'id' => $participantId]);
        } else {
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $participantId = $this->participantRepo->create((int)$session['id'], $nickname, $deviceCookie, $userAgent);
        }

        // Oturum süresince geçerli katılımcı çerezi
        setcookie('eduqr_participant', (string)$participantId, [
            'expires' => 0,
            'path' => '/',
            'httponly' => true,
            'secure' => $isSecure,
            'samesite' => 'Lax'
        ]);

        header('Location: ' . eduqr_path('/join/' . $shortCode . '/wait'));
        exit;
    }

    public function showWait(array $params): void
    {
        $shortCode = strtoupper($params['short_code']);
        $session = $this->sessionRepo->findByShortCode($shortCode);

        if ($session === null || $session['status'] === 'closed') {
            header('Location: ' . eduqr_path('/join/' . $shortCode));
            exit;
        }

        // Katılımcı doğrulaması yap
        $participantId = (int)($_COOKIE['eduqr_participant'] ?? 0);
        $participant = $this->participantRepo->findById($participantId);

        if ($participant === null || (int)$participant['session_id'] !== (int)$session['id']) {
            header('Location: ' . eduqr_path('/join/' . $shortCode));
            exit;
        }

        include __DIR__ . '/../../templates/student/wait.php';
    }
}
