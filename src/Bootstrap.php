<?php
declare(strict_types=1);

namespace EduQR;

final class Bootstrap
{
    public static function run(): void
    {
        // 1. Ayarları Yükle (.env)
        Config::load(__DIR__ . '/../.env');

        // 1.5 Dil Altyapısını Başlat
        \EduQR\I18n\I18nService::init();

        // 1.75 CSRF Koruması (sadece normal form POST isteklerinde, API/ngrok isteklerini atla)
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isApiOrNgrok = strpos($host, 'ngrok') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;
        $isJson = ($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json';
        $isMultipart = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isApiOrNgrok && !$isJson && !$isAjax && !$isMultipart) {
            \EduQR\Middleware\CsrfMiddleware::validate();
        }

        // 2. Hata Gösterimi Ayarları
        $debug = Config::bool('APP_DEBUG', false);
        if ($debug) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(0);
        }

        // 3. Rotaları Oluştur
        $router = new Router();

        // ── Auth Rotaları ──────────────────────────────────────────────────
        $router->get('/eduqr-rebuild/public/login', function (): void {
            (new \EduQR\Controllers\AuthController())->showLogin();
        });

        $router->post('/eduqr-rebuild/public/login', function (): void {
            (new \EduQR\Controllers\AuthController())->login();
        });

        $router->get('/eduqr-rebuild/public/logout', function (): void {
            (new \EduQR\Controllers\AuthController())->logout();
        });

        $router->get('/eduqr-rebuild/public/register', function (): void {
            (new \EduQR\Controllers\AuthController())->showRegister();
        });

        $router->post('/eduqr-rebuild/public/register', function (): void {
            (new \EduQR\Controllers\AuthController())->register();
        });

        $router->get('/eduqr-rebuild/public/verify-email', function (): void {
            (new \EduQR\Controllers\AuthController())->showVerifyEmail();
        });

        $router->post('/eduqr-rebuild/public/verify-email', function (): void {
            (new \EduQR\Controllers\AuthController())->verifyEmail();
        });

        $router->post('/eduqr-rebuild/public/forgot-password', function (): void {
            (new \EduQR\Controllers\AuthController())->forgotPassword();
        });

        $router->post('/eduqr-rebuild/public/reset-password', function (): void {
            (new \EduQR\Controllers\AuthController())->resetPassword();
        });

        // ── Korumalı Admin Dashboard ───────────────────────────────────────
        $router->get('/eduqr-rebuild/public/admin/dashboard', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            $user = \EduQR\Services\AuthService::user();
            $sessionRepo = new \EduQR\Repositories\SessionRepository();
            $recentSessionId = $sessionRepo->findRecentActiveSessionIdByUserId((int)$user['id']);
            $activeSessionsCount = $sessionRepo->countActiveSessionsByUserId((int)$user['id']);
            include __DIR__ . '/../templates/admin/dashboard.php';
        });

        $router->get('/eduqr-rebuild/public/admin/archive', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            $user = \EduQR\Services\AuthService::user();
            $recentSessionId = (new \EduQR\Repositories\SessionRepository())->findRecentActiveSessionIdByUserId((int)$user['id']);
            $archivedCourses = (new \EduQR\Repositories\CourseRepository())->findArchivedByUserId((int)$user['id']);
            include __DIR__ . '/../templates/admin/archive.php';
        });

        $router->get('/eduqr-rebuild/public/admin/settings', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            $user = \EduQR\Services\AuthService::user();
            $recentSessionId = (new \EduQR\Repositories\SessionRepository())->findRecentActiveSessionIdByUserId((int)$user['id']);
            include __DIR__ . '/../templates/admin/settings.php';
        });

        $router->post('/eduqr-rebuild/public/admin/settings/change-password', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\AuthController())->changePassword();
        });

        $router->post('/eduqr-rebuild/public/admin/settings/update-profile', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\AuthController())->updateProfile();
        });

        $router->post('/eduqr-rebuild/public/admin/settings/delete-account', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\AuthController())->deleteAccount();
        });

        // ── Korumalı Ders Rotaları ──────────────────────────────────────────
        $router->post('/eduqr-rebuild/public/admin/courses', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\CourseController())->create();
        });

        $router->post('/eduqr-rebuild/public/admin/courses/{id}/archive', function (array $params): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\CourseController())->archive($params);
        });

        $router->post('/eduqr-rebuild/public/admin/courses/{id}/restore', function (array $params): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\CourseController())->restore($params);
        });

        $router->get('/eduqr-rebuild/public/admin/courses/{id}', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\CourseController())->show($p);
        });

        // ── Korumalı Oturum Rotaları ────────────────────────────────────────
        $router->post('/eduqr-rebuild/public/admin/courses/{course_id}/sessions', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\SessionController())->create($p);
        });

        $router->get('/eduqr-rebuild/public/admin/sessions/{id}', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\SessionController())->show($p);
        });

        $router->get('/eduqr-rebuild/public/admin/sessions/{id}/qr.png', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\SessionController())->qrPng($p);
        });

        $router->get('/eduqr-rebuild/public/admin/sessions/{id}/report', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\ReportController())->showReport($p);
        });

        $router->get('/eduqr-rebuild/public/admin/sessions/{id}/report/csv', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\ReportController())->exportCsv($p);
        });

        $router->post('/eduqr-rebuild/public/admin/sessions/{id}/anonymize', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\ReportController())->anonymize($p);
        });

        $router->post('/eduqr-rebuild/public/admin/sessions/{id}/ai-analysis', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\ReportController())->generateAiAnalysis($p);
        });

        $router->post('/eduqr-rebuild/public/admin/sessions/{id}/close', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\SessionController())->close($p);
        });

        $router->post('/eduqr-rebuild/public/admin/sessions/{id}/pause', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\SessionController())->pause($p);
        });

        $router->post('/eduqr-rebuild/public/admin/sessions/{id}/resume', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\SessionController())->resume($p);
        });

        $router->post('/eduqr-rebuild/public/admin/sessions/{id}/toggle-results', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\SessionController())->toggleResults($p);
        });

        $router->post('/eduqr-rebuild/public/admin/sessions/{id}/delete', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\ReportController())->delete($p);
        });

        $router->get('/eduqr-rebuild/public/admin/sessions/{id}/participants/count', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\SessionController())->participantsCount($p);
        });

        $router->get('/eduqr-rebuild/public/admin/sessions/{id}/results', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\SessionController())->results($p);
        });

        // ── Korumalı Soru Rotaları ──────────────────────────────────────────
        $router->post('/eduqr-rebuild/public/admin/sessions/{session_id}/questions', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionController())->create($p);
        });

        $router->post('/eduqr-rebuild/public/admin/sessions/{session_id}/questions/import', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionController())->import($p);
        });

        $router->post('/eduqr-rebuild/public/admin/questions/{id}/activate', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionController())->activate($p);
        });

        $router->post('/eduqr-rebuild/public/admin/questions/{id}/close', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionController())->close($p);
        });

        // ── Soru Bankası Rotaları ───────────────────────────────────────────
        $router->get('/eduqr-rebuild/public/admin/question-bank', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionBankController())->showBank();
        });

        $router->post('/eduqr-rebuild/public/admin/question-bank/generate', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionBankController())->generate();
        });

        $router->post('/eduqr-rebuild/public/admin/question-bank/copy-to-session', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionBankController())->copyToSession();
        });

        $router->post('/eduqr-rebuild/public/admin/question-bank/add-manual', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionBankController())->addManual();
        });

        $router->post('/eduqr-rebuild/public/admin/question-bank/import-json', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionBankController())->importJson();
        });

        $router->post('/eduqr-rebuild/public/admin/question-bank/{id}/delete', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionBankController())->deleteQuestion($p);
        });

        $router->get('/eduqr-rebuild/public/admin/question-bank/{id}', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionBankController())->getQuestion($p);
        });

        $router->post('/eduqr-rebuild/public/admin/question-bank/{id}/update', function (array $p): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            (new \EduQR\Controllers\QuestionBankController())->update($p);
        });

        // ── Öğrenci Rotaları ────────────────────────────────────────────────
        $router->get('/eduqr-rebuild/public/join/{short_code}', function (array $p): void {
            (new \EduQR\Controllers\JoinController())->showJoin($p);
        });

        $router->post('/eduqr-rebuild/public/join/{short_code}', function (array $p): void {
            (new \EduQR\Controllers\JoinController())->join($p);
        });

        $router->get('/eduqr-rebuild/public/join/{short_code}/wait', function (array $p): void {
            (new \EduQR\Controllers\JoinController())->showWait($p);
        });

        $router->post('/eduqr-rebuild/public/join/{short_code}/wait', function (array $p): void {
            (new \EduQR\Controllers\JoinController())->submitWaitAnswer($p);
        });

        // ── Öğrenci API Rotaları ────────────────────────────────────────────
        $router->get('/eduqr-rebuild/public/api/v1/sessions/{short_code}/active-question', function (array $p): void {
            (new \EduQR\Controllers\Api\PublicQuestionController())->activeQuestion($p['short_code']);
        });

        $router->post('/eduqr-rebuild/public/api/v1/answers', function (): void {
            (new \EduQR\Controllers\Api\PublicQuestionController())->submitAnswer();
        });

        // Basit Ana Sayfa Rotası (Öğrenci Girişi veya Öğretmen Girişi Seçimi)
        $router->get('/eduqr-rebuild/public/', function (): void {
            (new \EduQR\Controllers\JoinController())->showHome();
        });

        $router->post('/eduqr-rebuild/public/join-code', function (): void {
            (new \EduQR\Controllers\JoinController())->joinCode();
        });

        // Audit Log Sayfası
        $router->get('/eduqr-rebuild/public/admin/audit-logs', function (): void {
            \EduQR\Middleware\AuthMiddleware::handle();
            include __DIR__ . '/../templates/admin/audit-logs.php';
        });

        // Audit Log JSON API
        $router->get('/eduqr-rebuild/public/api/v1/audit-logs', function (): void {
            header('Content-Type: application/json; charset=utf-8');
            $user = \EduQR\Services\AuthService::user();
            if ($user === null) {
                echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
                exit;
            }
            $limit = (int)($_GET['limit'] ?? 50);
            $repo = new \EduQR\Repositories\AuditLogRepository();
            $logs = $repo->findByUserId((int)$user['id'], $limit);
            echo json_encode(['success' => true, 'logs' => $logs, 'total' => $repo->count()], JSON_UNESCAPED_UNICODE);
            exit;
        });

        // Test Rotası
        $router->get('/eduqr-rebuild/public/test-db', function (): void {
            try {
                $db = \EduQR\Support\Database::connect();
                $stmt = $db->query("SELECT VERSION() as version");
                $row = $stmt->fetch();
                echo "<h1>Veritabanı Bağlantısı Başarılı!</h1><p>MySQL Versiyonu: " . htmlspecialchars($row['version']) . "</p>";
            } catch (\Throwable $e) {
                echo "<h1>Veritabanı Bağlantı Hatası!</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
            }
        });

        // İstekleri Yönlendir
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        $router->dispatch($method, $uri);
    }
}
