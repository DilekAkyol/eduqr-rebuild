<?php

declare(strict_types=1);

namespace EduQR\Controllers;

use EduQR\Repositories\QuestionBankRepository;
use EduQR\Repositories\QuestionRepository;
use EduQR\Repositories\SessionRepository;
use EduQR\Services\AuthService;
use EduQR\Config;

final class QuestionBankController
{
    private QuestionBankRepository $bankRepo;
    private QuestionRepository $questionRepo;
    private SessionRepository $sessionRepo;

    public function __construct()
    {
        $this->bankRepo     = new QuestionBankRepository();
        $this->questionRepo = new QuestionRepository();
        $this->sessionRepo  = new SessionRepository();
    }

    public function showBank(): void
    {
        $user = AuthService::user();
        if ($user === null) {
            header('Location: ' . eduqr_path('/login'));
            exit;
        }

        $bankQuestions = $this->bankRepo->findByUserId((int)$user['id']);

        // Kullanıcının tüm aktif oturumlarını getir (oturum seçimi için)
        $db = \EduQR\Support\Database::connect();
        $stmt = $db->prepare(
            "SELECT s.id, s.title, s.short_code, s.status, c.title AS course_name
             FROM sessions s
             JOIN courses c ON s.course_id = c.id
             WHERE c.user_id = :user_id AND s.status != 'closed'
             ORDER BY s.created_at DESC"
        );
        $stmt->execute(['user_id' => $user['id']]);
        $sessions = $stmt->fetchAll() ?: [];

        $hasApiKey = Config::get('GEMINI_API_KEY', '') !== '';

        include __DIR__ . '/../../templates/admin/question_bank.php';
    }

    /**
     * POST /admin/question-bank/generate
     * Gemini API ile ders notlarından soru üretir, bankaya kaydeder
     */
    public function generate(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
            exit;
        }

        $apiKey = Config::get('GEMINI_API_KEY', '');
        if ($apiKey === '') {
            echo json_encode([
                'success' => false,
                'error'   => 'GEMINI_API_KEY .env dosyasında tanımlanmamış.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rawBody     = file_get_contents('php://input') ?: '{}';
        $body        = json_decode($rawBody, true) ?: [];
        $notes       = trim((string)($body['notes'] ?? ''));
        $sourceTitle = trim((string)($body['source_title'] ?? ''));
        $count       = max(3, min(15, (int)($body['count'] ?? 5)));
        $type        = trim((string)($body['type'] ?? 'multiple_choice'));

        if ($notes === '') {
            echo json_encode(['success' => false, 'error' => 'Ders notları boş olamaz.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($type === 'open_ended') {
            $prompt = <<<PROMPT
Aşağıdaki ders notlarından $count adet açık uçlu soru üret.
Öğrencilerin bu sorulara kendi cümleleriyle kısa veya uzun yanıtlar yazması beklenecektir. Bu yüzden seçenek (şık) veya doğru cevap belirtilmemelidir.

Ders notları:
"""
$notes
"""

Yanıtını SADECE aşağıdaki JSON formatında ver, başka hiçbir açıklama ekleme:
{
  "questions": [
    {
      "question_text": "Açık uçlu soru metni buraya"
    }
  ]
}
PROMPT;
        } else {
            $prompt = <<<PROMPT
Aşağıdaki ders notlarından $count adet çoktan seçmeli soru üret.
Her soru için tam olarak 4 şık (A, B, C, D) ve doğru cevabı belirt.

Ders notları:
"""
$notes
"""

Yanıtını SADECE aşağıdaki JSON formatında ver, başka hiçbir açıklama ekleme:
{
  "questions": [
    {
      "question_text": "Soru metni buraya",
      "options": ["A şıkkı", "B şıkkı", "C şıkkı", "D şıkkı"],
      "correct_answer": "A şıkkı"
    }
  ]
}
PROMPT;
        }

        $payload = json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature'      => 0.7,
                'maxOutputTokens'  => 2048,
                'responseMimeType' => 'application/json',
            ]
        ]);

        $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key={$apiKey}";

        $ch = curl_init($geminiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response   = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            echo json_encode(['success' => false, 'error' => 'API bağlantı hatası: ' . $curlError], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $geminiData = json_decode($response, true);

        if (isset($geminiData['error'])) {
            $errMsg = $geminiData['error']['message'] ?? 'Bilinmeyen API hatası';
            echo json_encode(['success' => false, 'error' => 'Gemini API Hatası (' . $httpStatus . '): ' . $errMsg], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($httpStatus !== 200) {
            echo json_encode(['success' => false, 'error' => 'Gemini API geçersiz durum kodu döndürdü (' . $httpStatus . '). Yanıt: ' . substr((string)$response, 0, 300)], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rawText = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // JSON bloğunu temizle (Gerekirse)
        $rawText = preg_replace('/^```json\s*/i', '', trim($rawText));
        $rawText = preg_replace('/```\s*$/', '', $rawText);

        $parsed = json_decode($rawText, true);

        if (!isset($parsed['questions']) || !is_array($parsed['questions'])) {
            echo json_encode([
                'success' => false,
                'error'   => 'Gemini geçerli JSON döndürmedi. Ham yanıt: ' . substr($rawText, 0, 300)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $created = [];
        foreach ($parsed['questions'] as $q) {
            $text    = trim((string)($q['question_text'] ?? ''));
            $options = $q['options'] ?? null;
            $correct = $q['correct_answer'] ?? null;

            if ($text === '') continue;

            $id = $this->bankRepo->create(
                (int)$user['id'],
                $text,
                $type,
                is_array($options) ? $options : null,
                $correct,
                $sourceTitle !== '' ? $sourceTitle : null
            );

            $created[] = [
                'id'             => $id,
                'question_text'  => $text,
                'options'        => $options,
                'correct_answer' => $correct,
                'source_title'   => $sourceTitle,
                'created_at'     => date('Y-m-d H:i:s'),
            ];
        }

        echo json_encode([
            'success'   => true,
            'count'     => count($created),
            'questions' => $created,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * POST /admin/question-bank/{id}/delete
     */
    public function deleteQuestion(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
            exit;
        }

        $id      = (int)$params['id'];
        $deleted = $this->bankRepo->deleteById($id, (int)$user['id']);

        echo json_encode(['success' => $deleted], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * POST /admin/question-bank/copy-to-session
     * Seçili banka sorularını bir oturuma kopyalar
     */
    public function copyToSession(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
            exit;
        }

        $rawBody  = file_get_contents('php://input') ?: '{}';
        $body     = json_decode($rawBody, true) ?: [];
        $ids      = array_map('intval', $body['ids'] ?? []);
        $sessionId = (int)($body['session_id'] ?? 0);

        if (empty($ids) || $sessionId === 0) {
            echo json_encode(['success' => false, 'error' => 'Soru veya oturum seçilmedi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            echo json_encode(['success' => false, 'error' => 'Oturum bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $bankQuestions = $this->bankRepo->findByIds($ids, (int)$user['id']);

        $copied = 0;
        foreach ($bankQuestions as $q) {
            $this->questionRepo->create(
                $sessionId,
                $q['question_text'],
                $q['type'] ?? 'multiple_choice',
                $q['options'],
                $q['correct_answer']
            );
            $copied++;
        }

        echo json_encode([
            'success'   => true,
            'count'     => $copied,
            'session_id'=> $sessionId,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * POST /admin/question-bank/add-manual
     * Tek soru manuel ekle
     */
    public function addManual(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
            exit;
        }

        $rawBody     = file_get_contents('php://input') ?: '{}';
        $body        = json_decode($rawBody, true) ?: [];
        $text        = trim((string)($body['question_text'] ?? ''));
        $options     = $body['options'] ?? null;
        $correct     = $body['correct_answer'] ?? null;
        $sourceTitle = trim((string)($body['source_title'] ?? ''));
        $type        = trim((string)($body['type'] ?? 'multiple_choice'));

        if ($text === '') {
            echo json_encode(['success' => false, 'error' => 'Soru metni boş olamaz.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $id = $this->bankRepo->create(
            (int)$user['id'],
            $text,
            $type,
            is_array($options) ? $options : null,
            $correct,
            $sourceTitle !== '' ? $sourceTitle : null
        );

        echo json_encode([
            'success' => true,
            'id'      => $id,
            'question_text' => $text,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
