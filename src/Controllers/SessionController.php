<?php

declare(strict_types=1);

namespace EduQR\Controllers;

use EduQR\Repositories\AuditLogRepository;
use EduQR\Repositories\CourseRepository;
use EduQR\Repositories\SessionRepository;
use EduQR\Services\AuthService;
use EduQR\Support\ShortCode;

final class SessionController
{
    private CourseRepository $courseRepo;
    private SessionRepository $sessionRepo;
    private AuditLogRepository $auditRepo;

    public function __construct()
    {
        $this->courseRepo = new CourseRepository();
        $this->sessionRepo = new SessionRepository();
        $this->auditRepo = new AuditLogRepository();
    }

    public function create(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            header('Location: ' . eduqr_path('/login'));
            exit;
        }

        $courseId = (int) $params['course_id'];
        $course = $this->courseRepo->findByIdAndUserId($courseId, $user['id']);

        if ($course === null) {
            http_response_code(403);
            echo "Access denied.";
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            $title = 'Yeni Oturum';
        }

        // 6 haneli benzersiz kısa kod üret (çakışma durumunda tekrar dene)
        $shortCode = '';
        $maxAttempts = 5;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $candidate = ShortCode::generate();
            if ($this->sessionRepo->findByShortCode($candidate) === null) {
                $shortCode = $candidate;
                break;
            }
        }

        if ($shortCode === '') {
            http_response_code(500);
            echo "Failed to generate unique short code.";
            exit;
        }

        $this->sessionRepo->create($courseId, $title, $shortCode);

        header('Location: ' . eduqr_path('/admin/courses/' . $courseId));
        exit;
    }

    public function show(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            header('Location: ' . eduqr_path('/login'));
            exit;
        }

        $sessionId = (int) $params['id'];
        $session = $this->sessionRepo->findById($sessionId);

        if ($session === null) {
            http_response_code(404);
            echo "Session not found.";
            exit;
        }

        $course = $this->courseRepo->findByIdAndUserId((int)$session['course_id'], $user['id']);
        if ($course === null) {
            http_response_code(403);
            echo "Access denied.";
            exit;
        }

        // Gelecek fazlarda soruları ve katılımcıları buraya bağlayacağız.
        $questionRepo = new \EduQR\Repositories\QuestionRepository();
        $questions = $questionRepo->findBySessionId($sessionId);
        $participants = [];

        include __DIR__ . '/../../templates/admin/sessions/detail.php';
    }

    public function qrPng(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            http_response_code(403);
            exit;
        }

        $sessionId = (int) $params['id'];
        $session = $this->sessionRepo->findById($sessionId);

        if ($session === null) {
            http_response_code(404);
            exit;
        }

        // Build the target student join URL dynamically using the current request host
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? '10.11.98.7';
        $joinUrl = $protocol . $host . eduqr_path('/join/' . $session['short_code']);

        $result = \Endroid\QrCode\Builder\Builder::create()
            ->writer(new \Endroid\QrCode\Writer\PngWriter())
            ->data($joinUrl)
            ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
            ->errorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->build();

        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $result->getString();
        exit;
    }

    public function participantsCount(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $sessionId = (int) $params['id'];

        $db = \EduQR\Support\Database::connect();
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM participants WHERE session_id = :session_id");
        $stmt->execute(['session_id' => $sessionId]);
        $row = $stmt->fetch();

        echo json_encode([
            'count' => (int)($row['cnt'] ?? 0),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function results(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $sessionId = (int) $params['id'];

        $questionRepo = new \EduQR\Repositories\QuestionRepository();
        $activeQuestion = $questionRepo->findActiveBySessionId($sessionId);

        if ($activeQuestion === null) {
            echo json_encode(['active' => false]);
            exit;
        }

        $answerRepo = new \EduQR\Repositories\AnswerRepository();

        if ($activeQuestion['type'] === 'open_ended') {
            $db = \EduQR\Support\Database::connect();
            $stmt = $db->prepare(
                "SELECT a.answer_value, p.nickname, a.created_at 
                 FROM answers a
                 JOIN participants p ON a.participant_id = p.id
                 WHERE a.question_id = :question_id
                 ORDER BY a.created_at ASC"
            );
            $stmt->execute(['question_id' => (int)$activeQuestion['id']]);
            $answers = $stmt->fetchAll() ?: [];
            
            echo json_encode([
                'active' => true,
                'question_id' => $activeQuestion['id'],
                'type' => 'open_ended',
                'results' => $answers
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $counts = $answerRepo->getResultsForQuestion((int)$activeQuestion['id']);

        echo json_encode([
            'active' => true,
            'question_id' => $activeQuestion['id'],
            'type' => 'multiple_choice',
            'results' => $counts
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function close(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            http_response_code(403);
            exit;
        }

        $sessionId = (int) $params['id'];
        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            http_response_code(404);
            exit;
        }

        $course = $this->courseRepo->findByIdAndUserId((int)$session['course_id'], $user['id']);
        if ($course === null) {
            http_response_code(403);
            exit;
        }

        $this->sessionRepo->updateStatus($sessionId, 'closed');
        $this->auditRepo->log('session_close', 'instructor', $user['id'], 'session', $sessionId);

        header('Location: ' . eduqr_path('/admin/dashboard'));
        exit;
    }

    public function pause(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            http_response_code(403);
            exit;
        }

        $sessionId = (int) $params['id'];
        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            http_response_code(404);
            exit;
        }

        $course = $this->courseRepo->findByIdAndUserId((int)$session['course_id'], $user['id']);
        if ($course === null) {
            http_response_code(403);
            exit;
        }

        // Guard transition: Closed session cannot be paused
        if ($session['status'] === 'closed') {
            header('Location: ' . eduqr_path('/admin/sessions/' . $sessionId));
            exit;
        }

        $this->sessionRepo->updateStatus($sessionId, 'paused');
        $this->auditRepo->log('session_pause', 'instructor', $user['id'], 'session', $sessionId);

        header('Location: ' . eduqr_path('/admin/sessions/' . $sessionId));
        exit;
    }

    public function resume(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            http_response_code(403);
            exit;
        }

        $sessionId = (int) $params['id'];
        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            http_response_code(404);
            exit;
        }

        $course = $this->courseRepo->findByIdAndUserId((int)$session['course_id'], $user['id']);
        if ($course === null) {
            http_response_code(403);
            exit;
        }

        // Guard transition: Closed session cannot be resumed
        if ($session['status'] === 'closed') {
            header('Location: ' . eduqr_path('/admin/sessions/' . $sessionId));
            exit;
        }

        $this->sessionRepo->updateStatus($sessionId, 'active');
        $this->auditRepo->log('session_resume', 'instructor', $user['id'], 'session', $sessionId);

        header('Location: ' . eduqr_path('/admin/sessions/' . $sessionId));
        exit;
    }

    public function toggleResults(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            http_response_code(403);
            exit;
        }

        $sessionId = (int) $params['id'];
        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            http_response_code(404);
            exit;
        }

        $course = $this->courseRepo->findByIdAndUserId((int)$session['course_id'], $user['id']);
        if ($course === null) {
            http_response_code(403);
            exit;
        }

        // Closed sessions cannot be modified
        if ($session['status'] === 'closed') {
            header('Location: ' . eduqr_path('/admin/sessions/' . $sessionId));
            exit;
        }

        $newVal = (int)($session['show_results_to_students'] ?? 1) === 1 ? 0 : 1;
        $this->sessionRepo->updateShowResults($sessionId, $newVal);

        header('Location: ' . eduqr_path('/admin/sessions/' . $sessionId));
        exit;
    }
}
