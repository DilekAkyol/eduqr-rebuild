<?php

declare(strict_types=1);

namespace EduQR\Controllers;

use EduQR\Repositories\SessionRepository;
use EduQR\Repositories\ParticipantRepository;
use EduQR\Repositories\QuestionRepository;
use EduQR\Repositories\AnswerRepository;
use EduQR\Services\ProfanityFilter;

final class JoinController
{
    private SessionRepository $sessionRepo;
    private ParticipantRepository $participantRepo;
    private QuestionRepository $questionRepo;
    private AnswerRepository $answerRepo;

    public function __construct()
    {
        $this->sessionRepo = new SessionRepository();
        $this->participantRepo = new ParticipantRepository();
        $this->questionRepo = new QuestionRepository();
        $this->answerRepo = new AnswerRepository();
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
            $title = t('student.join.session_not_found');
            $desc = t('student.join.session_not_found_desc');
            echo "<h1>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h1><p>" . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . "</p>";
            exit;
        }

        $locale = \EduQR\I18n\I18nService::getLocale();
        if ($session['status'] === 'closed') {
            $title = t('student.wait.session_closed_title');
            $desc = t('student.join.session_closed_desc');
            echo "<h1>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h1><p>" . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . "</p>";
            exit;
        }

        if ($session['status'] === 'paused') {
            $title = t('student.wait.session_paused_title');
            $desc = t('student.join.session_paused_desc');
            echo "<h1>" . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . "</h1><p>" . htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') . "</p>";
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

        if ($session === null || $session['status'] === 'closed' || $session['status'] === 'paused') {
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
        $locale   = \EduQR\I18n\I18nService::getLocale();
        if ($nickname === '') {
            $nickname = 'anon-' . substr($deviceCookie, 0, 8);
        }

        // Küfür/argo filtresi (FR-41, FR-43) — anon- prefix'li otomatik isimler muaf
        if (!str_starts_with($nickname, 'anon-')) {
            $filter = new ProfanityFilter();
            if ($filter->contains($nickname)) {
                $error = t('student.join.profanity_blocked');
                include __DIR__ . '/../../templates/student/join.php';
                exit;
            }
        }

        // Aynı cihaz daha önce bu oturuma katılmış mı?
        $existing = $this->participantRepo->findByDeviceCookieAndSessionId($deviceCookie, (int)$session['id']);
        if ($existing !== null) {
            $participantId = (int)$existing['id'];
            $this->participantRepo->updateNickname($participantId, $nickname);
        } else {
            // Başka bir cihaz bu rumuzu daha önce aldı mı? (case-insensitive, FR-42)
            $takenBy = $this->participantRepo->findBySessionIdAndNickname((int)$session['id'], $nickname);
            if ($takenBy !== null) {
                $error = t('student.join.nickname_taken');
                include __DIR__ . '/../../templates/student/join.php';
                exit;
            }

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

        // JS Devre dışı durumu için aktif soruyu çek
        $activeQuestion = $this->questionRepo->findActiveBySessionId((int)$session['id']);
        $hasAnswered = false;
        $showResults = (int)($session['show_results_to_students'] ?? 1) === 1;
        $results = [];
        if ($activeQuestion !== null) {
            $hasAnswered = $this->answerRepo->findAnswer((int)$activeQuestion['id'], $participantId) !== null;
            if ($showResults && $hasAnswered && $activeQuestion['type'] !== 'open_ended') {
                $results = $this->answerRepo->getResultsForQuestion((int)$activeQuestion['id']);
            }
        }

        include __DIR__ . '/../../templates/student/wait.php';
    }

    public function submitWaitAnswer(array $params): void
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

        // POST verilerini al
        $questionId = (int)($_POST['question_id'] ?? 0);
        $answerValue = trim((string)($_POST['answer_value'] ?? ''));

        $question = $this->questionRepo->findById($questionId);
        if ($question !== null && $question['status'] === 'active' && (int)$question['session_id'] === (int)$session['id']) {
            // Sadece oturum aktifken (paused/closed değilken) cevapları kaydet
            if ($session['status'] === 'active' && $answerValue !== '') {
                $this->answerRepo->submitAnswer($questionId, $participantId, $answerValue);
            }
        }

        // Yönlendir (GET /wait)
        header('Location: ' . eduqr_path('/join/' . $shortCode . '/wait'));
        exit;
    }
}
