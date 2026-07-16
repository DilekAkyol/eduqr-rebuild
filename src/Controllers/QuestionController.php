<?php

declare(strict_types=1);

namespace EduQR\Controllers;

use EduQR\Repositories\SessionRepository;
use EduQR\Repositories\QuestionRepository;
use EduQR\Services\AuthService;

final class QuestionController
{
    private SessionRepository $sessionRepo;
    private QuestionRepository $questionRepo;

    public function __construct()
    {
        $this->sessionRepo = new SessionRepository();
        $this->questionRepo = new QuestionRepository();
    }

    public function create(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            header('Location: ' . eduqr_path('/login'));
            exit;
        }

        $sessionId = (int) $params['session_id'];
        $session = $this->sessionRepo->findById($sessionId);

        if ($session === null) {
            http_response_code(404);
            echo "Session not found.";
            exit;
        }

        $text = trim($_POST['question_text'] ?? '');
        $type = $_POST['type'] ?? 'multiple_choice';

        $options = null;
        $correctAnswer = null;

        if ($type === 'multiple_choice') {
            $rawOptions = $_POST['options'] ?? [];
            $options = [];
            foreach ($rawOptions as $opt) {
                $opt = trim($opt);
                if ($opt !== '') {
                    $options[] = $opt;
                }
            }
            $correctAnswer = $_POST['correct_answer'] ?? null;
        }

        if ($text !== '') {
            $this->questionRepo->create($sessionId, $text, $type, $options, $correctAnswer);
        }

        header('Location: ' . eduqr_path('/admin/sessions/' . $sessionId));
        exit;
    }

    public function activate(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            header('Location: ' . eduqr_path('/login'));
            exit;
        }

        $questionId = (int) $params['id'];
        $question = $this->questionRepo->findById($questionId);

        if ($question === null) {
            http_response_code(404);
            echo "Question not found.";
            exit;
        }

        // Deactivate other questions in this session
        $this->questionRepo->deactivateAllForSession((int)$question['session_id']);

        // Activate this question
        $this->questionRepo->updateStatus($questionId, 'active');

        header('Location: ' . eduqr_path('/admin/sessions/' . $question['session_id']));
        exit;
    }

    public function close(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            header('Location: ' . eduqr_path('/login'));
            exit;
        }

        $questionId = (int) $params['id'];
        $question = $this->questionRepo->findById($questionId);

        if ($question === null) {
            http_response_code(404);
            echo "Question not found.";
            exit;
        }

        $this->questionRepo->updateStatus($questionId, 'closed');

        header('Location: ' . eduqr_path('/admin/sessions/' . $question['session_id']));
        exit;
    }

    public function import(array $params): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $sessionId = (int) $params['session_id'];
        $session = $this->sessionRepo->findById($sessionId);
        if ($session === null) {
            echo json_encode(['success' => false, 'error' => 'Oturum bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rawBody = file_get_contents('php://input') ?: '{}';
        $body = json_decode($rawBody, true) ?: [];

        try {
            $items = $this->normalizeImportPayload($body);
            $createdIds = [];

            foreach ($items as $item) {
                $text = trim((string)($item['question_text'] ?? ''));
                $type = $item['type'] ?? 'multiple_choice';
                $options = $item['options'] ?? null;
                $correctAnswer = $item['correct_answer'] ?? null;

                if ($text !== '') {
                    $createdIds[] = $this->questionRepo->create($sessionId, $text, $type, $options, $correctAnswer);
                }
            }

            echo json_encode([
                'success' => true,
                'count' => count($createdIds),
                'message' => 'Sorular başarıyla içe aktarıldı.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (\InvalidArgumentException $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    private function normalizeImportPayload(array $body): array
    {
        $hasQuestions = isset($body['questions']);
        $hasSections = isset($body['sections']);

        if (!$hasQuestions && !$hasSections) {
            throw new \InvalidArgumentException('Geçersiz JSON formatı. "questions" veya "sections" anahtarı bulunmalı.');
        }

        $result = [];

        if ($hasQuestions) {
            if (!is_array($body['questions'])) {
                throw new \InvalidArgumentException('"questions" alanı bir dizi olmalıdır.');
            }
            foreach ($body['questions'] as $row) {
                if (!is_array($row)) continue;
                $result[] = [
                    'question_text' => $row['question_text'] ?? '',
                    'type' => $row['type'] ?? 'multiple_choice',
                    'options' => $row['options'] ?? null,
                    'correct_answer' => $row['correct_answer'] ?? null
                ];
            }
        }

        if ($hasSections) {
            $sections = $body['sections'];
            if (!is_array($sections)) {
                throw new \InvalidArgumentException('"sections" alanı bir nesne olmalıdır.');
            }

            $courseName = trim((string)($body['course_name'] ?? ''));
            $topicName = trim((string)($body['topic_name'] ?? ''));

            $phases = ['opening', 'middle', 'closing'];
            foreach ($phases as $phase) {
                if (isset($sections[$phase]) && is_array($sections[$phase])) {
                    foreach ($sections[$phase] as $row) {
                        if (!is_array($row)) continue;
                        $text = trim((string)($row['question_text'] ?? ''));
                        if ($text === '') continue;

                        // Metayı başlık olarak ekle
                        $prefixParts = [];
                        if ($courseName !== '') $prefixParts[] = $courseName;
                        if ($topicName !== '') $prefixParts[] = $topicName;
                        $prefixParts[] = ucfirst($phase);

                        $prefixedText = '[' . implode(' | ', $prefixParts) . '] ' . $text;

                        $result[] = [
                            'question_text' => $prefixedText,
                            'type' => $row['type'] ?? 'multiple_choice',
                            'options' => $row['options'] ?? null,
                            'correct_answer' => $row['correct_answer'] ?? null
                        ];
                    }
                }
            }
        }

        return $result;
    }
}
