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
        $sessions = $this->sessionRepo->getActiveAndDraftSessionsByUserId((int)$user['id']);
        $recentSessionId = $this->sessionRepo->findRecentActiveSessionIdByUserId((int)$user['id']);

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
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        $apiKey = Config::get('GEMINI_API_KEY', '');
        if ($apiKey === '') {
            echo json_encode([
                'success' => false,
                'error'   => t('error.gemini_key_missing')
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
            echo json_encode(['success' => false, 'error' => t('error.notes_empty')], JSON_UNESCAPED_UNICODE);
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
            echo json_encode(['success' => false, 'error' => t('admin.session.connection_error') . ': ' . $curlError], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $geminiData = json_decode($response, true);

        if (isset($geminiData['error'])) {
            $errMsg = $geminiData['error']['message'] ?? 'Bilinmeyen API hatası';
            echo json_encode(['success' => false, 'error' => t('error.gemini_api_error', ['message' => $errMsg])], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($httpStatus !== 200) {
            echo json_encode(['success' => false, 'error' => t('error.gemini_api_error', ['message' => 'Status: ' . $httpStatus])], JSON_UNESCAPED_UNICODE);
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
                'error'   => t('error.gemini_api_error', ['message' => 'Invalid JSON'])
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
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
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
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        $rawBody  = file_get_contents('php://input') ?: '{}';
        $body     = json_decode($rawBody, true) ?: [];
        $ids      = array_map('intval', $body['ids'] ?? []);
        $sessionId = (int)($body['session_id'] ?? 0);

        if (empty($ids) || $sessionId === 0) {
            echo json_encode(['success' => false, 'error' => t('error.question_or_session_not_selected')], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            echo json_encode(['success' => false, 'error' => t('error.session_not_found')], JSON_UNESCAPED_UNICODE);
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
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        $rawBody     = file_get_contents('php://input') ?: '{}';
        $body        = json_decode($rawBody, true) ?: [];
        $text        = trim((string)($body['question_text'] ?? ''));
        $options     = $body['options'] ?? null;
        $correct     = $body['correct_answer'] ?? null;
        $sourceTitle = trim((string)($body['source_title'] ?? ''));
        $type        = trim((string)($body['type'] ?? 'multiple_choice'));

        if ($type === 'yes_no') {
            $options = [t('common.yes'), t('common.no')];
        } elseif ($type === 'likert') {
            $options = [
                t('question.likert.strongly_disagree'),
                t('question.likert.disagree'),
                t('question.likert.neutral'),
                t('question.likert.agree'),
                t('question.likert.strongly_agree')
            ];
            $correct = null;
        }

        if ($text === '') {
            echo json_encode(['success' => false, 'error' => t('error.question_text_empty')], JSON_UNESCAPED_UNICODE);
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

    /**
     * POST /admin/question-bank/import-json
     * JSON dosyasından toplu soru aktarır
     */
    public function importJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        // Dosya yükleme kontrolü
        if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => t('error.file_upload_failed')], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $file = $_FILES['json_file'];

        // Boyut kontrolü (maks 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => t('error.file_size_exceeded')], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Uzantı kontrolü
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'json') {
            echo json_encode(['success' => false, 'error' => t('error.file_extension_invalid')], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $contents = file_get_contents($file['tmp_name']);
        $data     = json_decode($contents, true);

        if (!is_array($data)) {
            echo json_encode(['success' => false, 'error' => t('error.json_root_must_be_array')], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sourceTitle = trim((string)($_POST['source_title'] ?? '')) ?: null;
        $created     = [];
        $skipped     = 0;

        foreach ($data as $index => $q) {
            if (!is_array($q)) { $skipped++; continue; }

            $text    = trim((string)($q['text'] ?? $q['question_text'] ?? ''));
            $options = $q['options'] ?? null;
            $answer  = $q['answer'] ?? $q['correct_answer'] ?? null;

            if ($text === '') { $skipped++; continue; }

            // Tip belirleme
            if (is_array($options) && count($options) >= 2) {
                $type = 'multiple_choice';
                // answer sayısal index ise şık metnine çevir
                if (is_int($answer) && isset($options[$answer])) {
                    $answer = $options[$answer];
                }
            } else {
                $type    = 'open_ended';
                $options = null;
                $answer  = null;
            }

            $id = $this->bankRepo->create(
                (int)$user['id'],
                $text,
                $type,
                $options,
                $answer,
                $sourceTitle
            );

            $created[] = [
                'id'            => $id,
                'question_text' => $text,
                'type'          => $type,
            ];
        }

        echo json_encode([
            'success' => true,
            'count'   => count($created),
            'skipped' => $skipped,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * GET /admin/question-bank/{id}
     */
    public function getQuestion(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        $id = (int) $params['id'];
        $q = $this->bankRepo->findById($id, (int)$user['id']);

        if ($q === null) {
            echo json_encode(['success' => false, 'error' => t('error.question_not_found')]);
            exit;
        }

        echo json_encode(['success' => true, 'question' => $q], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * POST /admin/question-bank/{id}/update
     */
    public function update(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => t('error.unauthorized')]);
            exit;
        }

        $id = (int) $params['id'];
        $q = $this->bankRepo->findById($id, (int)$user['id']);
        if ($q === null) {
            echo json_encode(['success' => false, 'error' => t('error.question_not_found')]);
            exit;
        }

        // Post verilerini oku
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $text = trim((string)($input['question_text'] ?? ''));
        $type = trim((string)($input['type'] ?? 'multiple_choice'));
        $sourceTitle = trim((string)($input['source_title'] ?? '')) ?: null;

        if ($text === '') {
            echo json_encode(['success' => false, 'error' => t('error.question_text_empty')], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $options = null;
        $correctAnswer = null;

        if ($type === 'multiple_choice') {
            $rawOptions = $input['options'] ?? [];
            if (is_array($rawOptions)) {
                $options = array_values(array_filter(array_map('trim', $rawOptions), function ($val) {
                    return $val !== '';
                }));
            }
            if (empty($options) || count($options) < 2) {
                echo json_encode(['success' => false, 'error' => t('error.mc_min_options')], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $correctAnswer = trim((string)($input['correct_answer'] ?? ''));
            if ($correctAnswer === '') {
                echo json_encode(['success' => false, 'error' => t('error.correct_answer_required')], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } elseif ($type === 'yes_no') {
            $options = [t('common.yes'), t('common.no')];
            $correctAnswer = trim((string)($input['correct_answer'] ?? ''));
            if ($correctAnswer === '') {
                $correctAnswer = null;
            }
        } elseif ($type === 'likert') {
            $options = [
                t('question.likert.strongly_disagree'),
                t('question.likert.disagree'),
                t('question.likert.neutral'),
                t('question.likert.agree'),
                t('question.likert.strongly_agree')
            ];
            $correctAnswer = null;
        }

        $updated = $this->bankRepo->update(
            $id,
            (int)$user['id'],
            $text,
            $type,
            $options,
            $correctAnswer,
            $sourceTitle
        );

        if ($updated) {
            echo json_encode(['success' => true, 'message' => t('admin.session.question_updated_success')], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => t('admin.session.question_update_failed')], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
