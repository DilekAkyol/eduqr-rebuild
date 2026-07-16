<?php

declare(strict_types=1);

namespace EduQR\Controllers;

use EduQR\Repositories\CourseRepository;
use EduQR\Repositories\SessionRepository;
use EduQR\Services\AuthService;

final class CourseController
{
    private CourseRepository $courseRepo;
    private SessionRepository $sessionRepo;

    public function __construct()
    {
        $this->courseRepo = new CourseRepository();
        $this->sessionRepo = new SessionRepository();
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

        if ($title !== '' && $code !== '') {
            $this->courseRepo->create(
                $user['id'],
                $title,
                $code,
                $term !== '' ? $term : null,
                $description !== '' ? $description : null,
                $defaultLanguage
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
        
        echo json_encode(['success' => true]);
        exit;
    }
}
