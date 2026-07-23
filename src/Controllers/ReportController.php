<?php

declare(strict_types=1);

namespace EduQR\Controllers;

use EduQR\Repositories\AuditLogRepository;
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
    private AuditLogRepository $auditRepo;
    private \EduQR\Repositories\ParticipantRepository $participantRepo;

    public function __construct()
    {
        $this->courseRepo = new CourseRepository();
        $this->sessionRepo = new SessionRepository();
        $this->questionRepo = new QuestionRepository();
        $this->answerRepo = new AnswerRepository();
        $this->auditRepo = new AuditLogRepository();
        $this->participantRepo = new \EduQR\Repositories\ParticipantRepository();
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
            echo htmlspecialchars(t('error.session_not_found'), ENT_QUOTES, 'UTF-8');
            exit;
        }

        $course = $this->courseRepo->findByIdAndUserId((int)$session['course_id'], $user['id']);
        if ($course === null) {
            http_response_code(403);
            echo htmlspecialchars(t('error.unauthorized'), ENT_QUOTES, 'UTF-8');
            exit;
        }

        $questions = $this->questionRepo->findBySessionId($sessionId);
        
        $anonymize = ((int)($session['is_anonymized'] ?? 0) === 1) || (($_GET['anonymize'] ?? '') === 'true');
        
        // Katılımcıları listele
        $participants = $this->participantRepo->getAllBySessionId($sessionId);

        // Dinamik anonimleştirme haritası
        $participantMapping = [];
        $participantCounter = 1;

        if ($anonymize) {
            foreach ($participants as &$p) {
                $orig = $p['nickname'];
                if (!isset($participantMapping[$orig])) {
                    $participantMapping[$orig] = 'Katılımcı ' . $participantCounter++;
                }
                $p['nickname'] = $participantMapping[$orig];
            }
            unset($p);
        }

        // Her bir soru için oylama sonuçlarını çek
        $results = [];
        foreach ($questions as $q) {
            if ($q['type'] === 'open_ended') {
                $answers = $this->answerRepo->getAnswersForQuestion((int)$q['id']);
                if ($anonymize) {
                    foreach ($answers as &$ans) {
                        $orig = $ans['nickname'];
                        if (!isset($participantMapping[$orig])) {
                            $participantMapping[$orig] = 'Katılımcı ' . $participantCounter++;
                        }
                        $ans['nickname'] = $participantMapping[$orig];
                    }
                    unset($ans);
                }
                $results[$q['id']] = $answers;
            } else {
                $results[$q['id']] = $this->answerRepo->getResultsForQuestion((int)$q['id']);
            }
        }

        $recentSessionId = $this->sessionRepo->findRecentActiveSessionIdByUserId((int)$user['id']);

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

        $rows = $this->answerRepo->getAnswersReportForSession($sessionId);

        // HTTP Headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=session-report-' . $session['short_code'] . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM to display Turkish characters properly in Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        $anonymize = ((int)($session['is_anonymized'] ?? 0) === 1) || (($_GET['anonymize'] ?? '') === 'true');

        // Map unique nicknames to sequential anonymous identifiers to protect privacy if anonymization is active
        $participantMapping = [];
        $participantCounter = 1;

        // Header Row
        fputcsv($output, ['Katılımcı', 'Soru', 'Verilen Cevap', 'Cevaplama Tarihi']);

        foreach ($rows as $row) {
            $origNickname = $row['nickname'];
            if ($anonymize) {
                if (!isset($participantMapping[$origNickname])) {
                    $participantMapping[$origNickname] = 'Katılımcı ' . $participantCounter++;
                }
                $displayName = $participantMapping[$origNickname];
            } else {
                $displayName = $origNickname;
            }

            fputcsv($output, [
                $displayName,
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

        $this->participantRepo->anonymizeAllInSession($sessionId);

        $this->sessionRepo->setAnonymized($sessionId);
        $this->auditRepo->log('session_anonymize', 'instructor', $user['id'], 'session', $sessionId);

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

        $this->sessionRepo->delete($sessionId);
        $this->auditRepo->log('session_delete', 'instructor', $user['id'], 'session', $sessionId);

        header('Location: ' . eduqr_path('/admin/courses/' . $courseId . '?deleted=1'));
        exit;
    }

    /**
     * POST /admin/sessions/{id}/ai-analysis
     */
    public function generateAiAnalysis(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        $sessionId = (int)$params['id'];



        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            echo json_encode(['success' => false, 'error' => t('error.session_not_found')]);
            exit;
        }

        $course = $this->courseRepo->findByIdAndUserId((int)$session['course_id'], $user['id']);
        if ($course === null) {
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        $questions = $this->questionRepo->findBySessionId($sessionId);
        
        // Build the text description of questions and student answers
        $analysisPayload = "Ders: " . $course['title'] . "\nOturum: " . $session['title'] . "\n\nSoru ve Cevap Dağılımları:\n";
        
        foreach ($questions as $idx => $q) {
            $num = $idx + 1;
            $analysisPayload .= "Soru {$num}: " . $q['question_text'] . " (Tip: " . ($q['type'] === 'open_ended' ? 'Açık Uçlu' : 'Çoktan Seçmeli') . ")\n";
            
            if ($q['type'] === 'open_ended') {
                $answers = $this->answerRepo->getAnswersForQuestion((int)$q['id']);
                $analysisPayload .= "Öğrenci Cevapları:\n";
                if (empty($answers)) {
                    $analysisPayload .= "- Cevap yok.\n";
                } else {
                    foreach ($answers as $ans) {
                        $analysisPayload .= "- " . $ans['answer_value'] . "\n";
                    }
                }
            } else {
                $results = $this->answerRepo->getResultsForQuestion((int)$q['id']);
                $analysisPayload .= "Şık Dağılımı: ";
                $parts = [];
                foreach ($results as $opt => $count) {
                    $parts[] = "{$opt}: {$count} öğrenci";
                }
                $analysisPayload .= implode(', ', $parts) . "\n";
                if (!empty($q['correct_answer'])) {
                    $analysisPayload .= "Doğru Cevap: " . $q['correct_answer'] . "\n";
                }
            }
            $analysisPayload .= "\n";
        }

        $apiKey = \EduQR\Config::get('GEMINI_API_KEY', '');
        if ($apiKey === '') {
            echo json_encode(['success' => false, 'error' => t('error.gemini_key_missing')]);
            exit;
        }

        $prompt = "Aşağıda bir sınıfın eduQR platformu üzerinden gerçekleştirdiği canlı oturuma dair ders adı, oturum başlığı, sorular ve öğrencilerin verdiği cevap/şık dağılımları yer almaktadır.\n\n" .
                  "Bu verileri pedagojik açıdan derinlemesine analiz et:\n" .
                  "1. Sınıfın genel başarı durumunu kısaca özetle.\n" .
                  "2. Öğrencilerin en çok zorlandığı soruları ve konuları tespit et.\n" .
                  "3. Sıklıkla yapılan hataları ve yanlış cevapların/şıkların olası nedenlerini analiz et.\n" .
                  "4. Öğretmene sınıfın başarısını artırması için pratik, uygulanabilir kazanım önerileri (hangi konular tekrar edilmeli, nelere dikkat edilmeli) sun.\n\n" .
                  "Yanıtını tamamen Türkçe ve markdown formatında (başlıklar, kalın yazılar, listeler kullanarak) düzenle. Herhangi bir kod bloğu (```) içine alma, doğrudan okunabilir markdown dön.\n\n" .
                  "Veriler:\n" . $analysisPayload;

        $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$apiKey}";
        $postData = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($geminiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatus !== 200) {
            $errData = json_decode((string)$response, true);
            $errMsg = $errData['error']['message'] ?? 'Bilinmeyen API hatası';
            echo json_encode(['success' => false, 'error' => t('error.gemini_api_error', ['message' => $errMsg])]);
            exit;
        }

        $geminiData = json_decode((string)$response, true);
        $rawText = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (trim($rawText) === '') {
            echo json_encode(['success' => false, 'error' => t('error.gemini_empty')]);
            exit;
        }

        // Save to database
        $this->sessionRepo->saveAiAnalysis($sessionId, $rawText);

        echo json_encode(['success' => true, 'analysis' => $rawText], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
