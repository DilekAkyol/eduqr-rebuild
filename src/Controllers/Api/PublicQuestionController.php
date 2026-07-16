<?php

declare(strict_types=1);

namespace EduQR\Controllers\Api;

use EduQR\Repositories\SessionRepository;
use EduQR\Repositories\QuestionRepository;
use EduQR\Repositories\ParticipantRepository;
use EduQR\Repositories\AnswerRepository;

final class PublicQuestionController
{
    private SessionRepository $sessionRepo;
    private QuestionRepository $questionRepo;
    private ParticipantRepository $participantRepo;
    private AnswerRepository $answerRepo;

    public function __construct()
    {
        $this->sessionRepo = new SessionRepository();
        $this->questionRepo = new QuestionRepository();
        $this->participantRepo = new ParticipantRepository();
        $this->answerRepo = new AnswerRepository();
    }

    public function activeQuestion(string $shortCode): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $session = $this->sessionRepo->findByShortCode(strtoupper($shortCode));
        if ($session === null || $session['status'] === 'closed') {
            echo json_encode(['success' => false, 'error' => 'Oturum sonlandırılmış.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Katılımcıyı doğrula
        $participantId = (int)($_COOKIE['eduqr_participant'] ?? 0);
        $participant = $this->participantRepo->findById($participantId);
        if ($participant === null || (int)$participant['session_id'] !== (int)$session['id']) {
            echo json_encode(['success' => false, 'error' => 'Oturuma giriş yapılmamış.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $activeQuestion = $this->questionRepo->findActiveBySessionId((int)$session['id']);

        if ($activeQuestion === null) {
            echo json_encode(['active' => false]);
            exit;
        }

        // Öğrenci bu soruya zaten cevap vermiş mi?
        $hasAnswered = $this->answerRepo->findAnswer((int)$activeQuestion['id'], $participantId) !== null;

        echo json_encode([
            'active'       => true,
            'has_answered' => $hasAnswered,
            'question'     => [
                'id'            => $activeQuestion['id'],
                'question_text' => $activeQuestion['question_text'],
                'type'          => $activeQuestion['type'],
                'options'       => $activeQuestion['options'],
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function submitAnswer(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $participantId = (int)($_COOKIE['eduqr_participant'] ?? 0);
        $participant = $this->participantRepo->findById($participantId);
        if ($participant === null) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $body = json_decode(file_get_contents('php://input') ?: '{}', true);
        $questionId = (int)($body['question_id'] ?? 0);
        $value = trim((string)($body['answer_value'] ?? ''));

        $question = $this->questionRepo->findById($questionId);
        if ($question === null || $question['status'] !== 'active' || (int)$question['session_id'] !== (int)$participant['session_id']) {
            echo json_encode(['success' => false, 'error' => 'Soru aktif değil veya bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($value === '') {
            echo json_encode(['success' => false, 'error' => 'Geçersiz cevap.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $this->answerRepo->submitAnswer($questionId, $participantId, $value);

        echo json_encode(['success' => true]);
        exit;
    }
}
