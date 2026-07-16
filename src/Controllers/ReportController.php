<?php

declare(strict_types=1);

namespace EduQR\Controllers;

use EduQR\Repositories\CourseRepository;
use EduQR\Repositories\SessionRepository;
use EduQR\Repositories\QuestionRepository;
use EduQR\Repositories\AnswerRepository;
use EduQR\Services\AuthService;

final class ReportController
{
    private CourseRepository $courseRepo;
    private SessionRepository $sessionRepo;
    private QuestionRepository $questionRepo;
    private AnswerRepository $answerRepo;

    public function __construct()
    {
        $this->courseRepo = new CourseRepository();
        $this->sessionRepo = new SessionRepository();
        $this->questionRepo = new QuestionRepository();
        $this->answerRepo = new AnswerRepository();
    }

    public function showReport(array $params): void
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

        $questions = $this->questionRepo->findBySessionId($sessionId);
        
        // Katılımcıları listele
        $db = \EduQR\Support\Database::connect();
        $stmt = $db->prepare("SELECT * FROM participants WHERE session_id = :session_id ORDER BY created_at ASC");
        $stmt->execute(['session_id' => $sessionId]);
        $participants = $stmt->fetchAll() ?: [];

        // Her bir soru için oylama sonuçlarını çek
        $results = [];
        foreach ($questions as $q) {
            if ($q['type'] === 'open_ended') {
                $results[$q['id']] = $this->answerRepo->getAnswersForQuestion((int)$q['id']);
            } else {
                $results[$q['id']] = $this->answerRepo->getResultsForQuestion((int)$q['id']);
            }
        }

        include __DIR__ . '/../../templates/admin/sessions/report.php';
    }

    public function exportCsv(array $params): void
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

        $db = \EduQR\Support\Database::connect();
        $stmt = $db->prepare(
            "SELECT p.nickname, q.question_text, a.answer_value, a.created_at
             FROM answers a
             JOIN participants p ON a.participant_id = p.id
             JOIN questions q ON a.question_id = q.id
             WHERE q.session_id = :session_id
             ORDER BY q.id ASC, a.created_at ASC"
        );
        $stmt->execute(['session_id' => $sessionId]);
        $rows = $stmt->fetchAll() ?: [];

        // HTTP Headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=session-report-' . $session['short_code'] . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM to display Turkish characters properly in Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Map unique nicknames to sequential anonymous identifiers to protect privacy from teacher
        $participantMapping = [];
        $participantCounter = 1;

        // Header Row
        fputcsv($output, ['Katılımcı', 'Soru', 'Verilen Cevap', 'Cevaplama Tarihi']);

        foreach ($rows as $row) {
            $origNickname = $row['nickname'];
            if (!isset($participantMapping[$origNickname])) {
                $participantMapping[$origNickname] = 'Katılımcı ' . $participantCounter++;
            }
            $anonName = $participantMapping[$origNickname];

            fputcsv($output, [
                $anonName,
                $row['question_text'],
                $row['answer_value'],
                $row['created_at']
            ]);
        }

        fclose($output);
        exit;
    }

    public function anonymize(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            http_response_code(403);
            exit;
        }

        $sessionId = (int)$params['id'];
        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            http_response_code(404);
            exit;
        }

        $db = \EduQR\Support\Database::connect();
        // Fetch all participants for this session
        $stmt = $db->prepare("SELECT id FROM participants WHERE session_id = :session_id ORDER BY id ASC");
        $stmt->execute(['session_id' => $sessionId]);
        $participants = $stmt->fetchAll() ?: [];

        foreach ($participants as $idx => $p) {
            $update = $db->prepare("UPDATE participants SET nickname = :nickname WHERE id = :id");
            $update->execute(['nickname' => 'Katılımcı ' . ($idx + 1), 'id' => $p['id']]);
        }

        $this->sessionRepo->setAnonymized($sessionId);

        header('Location: ' . eduqr_path('/admin/sessions/' . $sessionId . '/report'));
        exit;
    }

    public function delete(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            http_response_code(403);
            exit;
        }

        $sessionId = (int)$params['id'];
        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            http_response_code(404);
            exit;
        }

        $courseId = (int)$session['course_id'];

        $db = \EduQR\Support\Database::connect();
        $stmt = $db->prepare("DELETE FROM sessions WHERE id = :id");
        $stmt->execute(['id' => $sessionId]);

        header('Location: ' . eduqr_path('/admin/courses/' . $courseId . '?deleted=1'));
        exit;
    }
}
