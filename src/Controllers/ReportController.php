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

        $db = \EduQR\Support\Database::connect();
        // Runtime schema check for ai_analysis column
        try {
            $db->query("SELECT ai_analysis FROM sessions LIMIT 1");
        } catch (\PDOException $e) {
            // Column does not exist, add it
            $db->exec("ALTER TABLE sessions ADD COLUMN ai_analysis TEXT NULL");
        }

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

    /**
     * POST /admin/sessions/{id}/ai-analysis
     */
    public function generateAiAnalysis(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
            exit;
        }

        $sessionId = (int)$params['id'];

        $db = \EduQR\Support\Database::connect();
        // Runtime schema check for ai_analysis column
        try {
            $db->query("SELECT ai_analysis FROM sessions LIMIT 1");
        } catch (\PDOException $e) {
            // Column does not exist, add it
            $db->exec("ALTER TABLE sessions ADD COLUMN ai_analysis TEXT NULL");
        }

        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            echo json_encode(['success' => false, 'error' => 'Oturum bulunamadı.']);
            exit;
        }

        $course = $this->courseRepo->findByIdAndUserId((int)$session['course_id'], $user['id']);
        if ($course === null) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
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
            echo json_encode(['success' => false, 'error' => 'GEMINI_API_KEY .env dosyasında tanımlanmamış.']);
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
            echo json_encode(['success' => false, 'error' => 'Gemini API Hatası: ' . $errMsg]);
            exit;
        }

        $geminiData = json_decode((string)$response, true);
        $rawText = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (trim($rawText) === '') {
            echo json_encode(['success' => false, 'error' => 'Gemini boş bir yanıt döndürdü.']);
            exit;
        }

        // Save to database
        $stmt = $db->prepare("UPDATE sessions SET ai_analysis = :ai_analysis WHERE id = :id");
        $stmt->execute(['ai_analysis' => $rawText, 'id' => $sessionId]);

        echo json_encode(['success' => true, 'analysis' => $rawText], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
