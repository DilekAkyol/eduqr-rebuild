<?php

declare(strict_types=1);

namespace EduQR\Controllers;

use EduQR\Repositories\AuditLogRepository;
use EduQR\Repositories\CourseRepository;
use EduQR\Repositories\SessionRepository;
use EduQR\Services\AuthService;

final class CourseController
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

    public function create(): void
    {
        $user = AuthService::user();
        if ($user === null) {
            header('Location: ' . eduqr_path('/login'));
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $code  = trim($_POST['code'] ?? '');
        $term  = trim($_POST['term'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $defaultLanguage = trim($_POST['default_language'] ?? 'tr');

        $finalTitleTr = $title;
        $finalTitleEn = $title;
        $finalDescTr = $description;
        $finalDescEn = $description;

        if ($title !== '') {
            if ($defaultLanguage === 'tr') {
                // Input is Turkish. Translate to English.
                $translated = $this->autoTranslate($title, $description, 'tr', 'en');
                $finalTitleTr = $title;
                $finalTitleEn = $translated['title'];
                $finalDescTr = $description;
                $finalDescEn = $translated['description'];
            } else {
                // Input is English. Translate to Turkish.
                $translated = $this->autoTranslate($title, $description, 'en', 'tr');
                $finalTitleTr = $translated['title'];
                $finalTitleEn = $title;
                $finalDescTr = $translated['description'];
                $finalDescEn = $description;
            }
        }

        if ($title !== '' && $code !== '') {
            $this->courseRepo->create(
                $user['id'],
                $finalTitleTr,
                $code,
                $term !== '' ? $term : null,
                $finalDescTr !== '' ? $finalDescTr : null,
                $defaultLanguage,
                $finalTitleEn !== '' ? $finalTitleEn : null,
                $finalDescEn !== '' ? $finalDescEn : null
            );
        }

        header('Location: ' . eduqr_path('/admin/dashboard'));
        exit;
    }

    public function show(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            header('Location: ' . eduqr_path('/login'));
            exit;
        }

        $courseId = (int) $params['id'];
        $course = $this->courseRepo->findByIdAndUserId($courseId, $user['id']);

        if ($course === null) {
            http_response_code(404);
            echo "<h1>Course not found</h1>";
            exit;
        }

        $sessions = $this->sessionRepo->findByCourseId($courseId);
        $recentSessionId = $this->sessionRepo->findRecentActiveSessionIdByUserId((int)$user['id']);

        include __DIR__ . '/../../templates/admin/courses/detail.php';
    }

    public function archive(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
            exit;
        }

        $courseId = (int) $params['id'];
        $course = $this->courseRepo->findByIdAndUserId($courseId, $user['id']);

        if ($course === null) {
            echo json_encode(['success' => false, 'error' => 'Ders bulunamadı']);
            exit;
        }

        $this->courseRepo->updateStatus($courseId, $user['id'], 'archived');
        $this->auditRepo->log('course_archive', 'instructor', $user['id'], 'course', $courseId);
        
        echo json_encode(['success' => true]);
        exit;
    }

    public function restore(array $params): void
    {
        $user = AuthService::user();
        if ($user === null) {
            echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
            exit;
        }

        $courseId = (int) $params['id'];
        $course = $this->courseRepo->findByIdAndUserId($courseId, $user['id']);

        if ($course === null) {
            echo json_encode(['success' => false, 'error' => 'Ders bulunamadı']);
            exit;
        }

        $this->courseRepo->updateStatus($courseId, $user['id'], 'active');
        $this->auditRepo->log('course_restore', 'instructor', $user['id'], 'course', $courseId);
        
        echo json_encode(['success' => true]);
        exit;
    }

    private function autoTranslate(string $title, string $description, string $sourceLang, string $targetLang): array
    {
        $apiKey = \EduQR\Config::get('GEMINI_API_KEY', '');
        if ($apiKey === '') {
            return ['title' => $title, 'description' => $description];
        }

        $prompt = "You are a professional translator. Translate the following JSON object's values from " . 
                  ($sourceLang === 'tr' ? 'Turkish' : 'English') . " to " . ($targetLang === 'en' ? 'English' : 'Turkish') . 
                  ". Keep the keys exactly the same. Do not output anything else other than the translated JSON object. Return it as a valid JSON object without markdown formatting.
JSON object to translate:
" . json_encode(['title' => $title, 'description' => $description], JSON_UNESCAPED_UNICODE);

        $payload = json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature'      => 0.2,
                'maxOutputTokens'  => 1024,
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
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response   = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return ['title' => $title, 'description' => $description];
        }

        $geminiData = json_decode($response, true);
        $rawText = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if ($rawText === '') {
            return ['title' => $title, 'description' => $description];
        }

        $rawText = preg_replace('/^```json\s*/i', '', trim($rawText));
        $rawText = preg_replace('/```\s*$/', '', $rawText);

        $decoded = json_decode($rawText, true);
        if ($decoded && isset($decoded['title'])) {
            return [
                'title' => trim((string)$decoded['title']),
                'description' => isset($decoded['description']) ? trim((string)$decoded['description']) : $description
            ];
        }

        return ['title' => $title, 'description' => $description];
    }
}
