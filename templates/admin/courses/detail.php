<?php
use EduQR\Services\AuthService;
$user = AuthService::user();
$locale = \EduQR\I18n\I18nService::getLocale();

// Fetch the user's most recent session ID across all their courses
$db = \EduQR\Support\Database::connect();
$recentSessionStmt = $db->prepare("
    SELECT s.id 
    FROM sessions s
    JOIN courses c ON s.course_id = c.id
    WHERE c.user_id = :user_id AND c.status = 'active'
    ORDER BY s.created_at DESC
    LIMIT 1
");
$recentSessionStmt->execute(['user_id' => (int)$user['id']]);
$recentSession = $recentSessionStmt->fetch();
$recentSessionId = $recentSession ? (int)$recentSession['id'] : null;
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['title']) ?> - eduQR</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Theme Fast-Init script to prevent white flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('eduqr_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <style>
        :root {
            /* Light Mode Variables */
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --card-border: rgba(0, 0, 0, 0.08);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --item-bg: rgba(0, 0, 0, 0.015);
            --item-border: rgba(0, 0, 0, 0.05);
            --item-hover-bg: rgba(0, 0, 0, 0.03);
            --item-hover-border: rgba(0, 0, 0, 0.1);
            --navbar-bg: rgba(255, 255, 255, 0.85);
            --navbar-border: rgba(0, 0, 0, 0.08);
            --modal-bg: #ffffff;
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --input-color: #0f172a;
            --shadow-opacity: 0.03;
            --ambient-opacity-1: 0.02;
            --primary: #3b82f6;
            --accent: #8b5cf6;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --btn-custom-outline-border: rgba(0, 0, 0, 0.15);
            --btn-custom-outline-hover-bg: rgba(0, 0, 0, 0.05);
            --btn-custom-outline-color: #0f172a;
        }

        [data-theme="dark"] {
            /* Dark Mode Variables */
            --bg-color: #030712;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f9fafb;
            --text-muted: #94a3b8;
            --item-bg: rgba(255, 255, 255, 0.02);
            --item-border: rgba(255, 255, 255, 0.05);
            --item-hover-bg: rgba(255, 255, 255, 0.05);
            --item-hover-border: rgba(59, 130, 246, 0.3);
            --navbar-bg: rgba(3, 7, 18, 0.6);
            --navbar-border: rgba(255, 255, 255, 0.08);
            --modal-bg: #0b0f19;
            --input-bg: rgba(255, 255, 255, 0.05);
            --input-border: rgba(255, 255, 255, 0.1);
            --input-color: #ffffff;
            --shadow-opacity: 0.5;
            --ambient-opacity-1: 0.08;
            --sidebar-bg: #0b0f19;
            --sidebar-hover: #111827;
            --btn-custom-outline-border: rgba(255, 255, 255, 0.15);
            --btn-custom-outline-hover-bg: rgba(255, 255, 255, 0.05);
            --btn-custom-outline-color: var(--text-main);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Ambient Glow Effect */
        .ambient-glow-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, rgba(59, 130, 246, var(--ambient-opacity-1)) 0%, rgba(0,0,0,0) 70%);
            z-index: -1;
            pointer-events: none;
        }

        /* ── Sidebar Design matching Report Page ────────────────── */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            flex-shrink: 0;
            min-height: 100vh;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            transition: background-color 0.3s ease;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 2.5rem;
            color: #ffffff;
            text-decoration: none;
        }
        .sidebar-logo-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            flex-grow: 1;
        }

        .nav-item-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .nav-item-custom:hover {
            background-color: var(--sidebar-hover);
            color: #ffffff;
        }

        .nav-item-custom.active {
            background-color: var(--primary);
            color: #ffffff;
        }

        .nav-item-custom.disabled {
            opacity: 0.4;
            pointer-events: none;
            cursor: not-allowed;
        }

        .sidebar-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 1.2rem;
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .profile-img {
            width: 38px;
            height: 38px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .content-area {
            flex-grow: 1;
            padding: 2.5rem;
            max-width: 1200px;
            min-width: 0;
        }

        /* Cards */
        .card-custom {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2.2rem;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, var(--shadow-opacity));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Session Item Row */
        .session-item {
            background: var(--item-bg);
            border: 1px solid var(--item-border);
            border-radius: 12px;
            padding: 1.2rem;
            transition: all 0.2s;
            text-decoration: none;
            color: var(--text-main);
            display: block;
        }

        .session-item:hover {
            background: var(--item-hover-bg);
            border-color: var(--item-hover-border);
            color: var(--text-main);
        }

        .btn-custom-outline {
            background: transparent;
            border: 1px solid var(--btn-custom-outline-border);
            color: var(--btn-custom-outline-color);
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-custom-outline:hover {
            background: var(--btn-custom-outline-hover-bg);
            border-color: var(--btn-custom-outline-border);
            color: var(--btn-custom-outline-color);
        }

        /* Status Badges */
        .badge-active {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }

        .badge-paused {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #f59e0b;
        }

        .badge-closed {
            background: rgba(156, 163, 175, 0.1);
            border: 1px solid rgba(156, 163, 175, 0.3);
            color: #9ca3af;
        }

        /* Buttons */
        .btn-custom-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.35);
            color: #fff;
        }

        /* Modal styling */
        .modal-content {
            background-color: var(--modal-bg);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            border-radius: 20px;
        }
        .modal-header {
            border-bottom: 1px solid var(--card-border);
        }
        .modal-footer {
            border-top: 1px solid var(--card-border);
        }
        .form-control {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--input-color) !important;
            border-radius: 10px !important;
            padding: 0.8rem 1rem !important;
        }
        .form-control::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.75 !important;
        }
        .form-control option, select option {
            background-color: var(--modal-bg) !important;
            color: var(--text-main) !important;
        }
        .form-control:focus {
            background: var(--input-bg) !important;
            border-color: #3b82f6 !important;
            color: var(--input-color) !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15) !important;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }
    </style>
</head>
<body>

    <div class="ambient-glow-1"></div>

    <!-- Left Sidebar matching Slide 5 -->
    <div class="sidebar no-print">
        <a href="<?= eduqr_path('/admin/dashboard') ?>" class="sidebar-logo">
            <div class="sidebar-logo-icon">❖</div>
            <span>eduQR</span>
        </a>
        <div class="nav-menu">
            <a href="<?= $recentSessionId ? eduqr_path('/admin/sessions/' . $recentSessionId . '/report') : '#' ?>" class="nav-item-custom<?= !$recentSessionId ? ' disabled' : '' ?>"><?= htmlspecialchars(t('admin.report.sidebar_reports')) ?></a>
            <a href="<?= eduqr_path('/admin/dashboard') ?>" class="nav-item-custom active"><?= htmlspecialchars(t('admin.report.sidebar_courses')) ?></a>
            <a href="<?= eduqr_path('/admin/question-bank') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_qbank')) ?></a>
            <a href="<?= $recentSessionId ? eduqr_path('/admin/sessions/' . $recentSessionId . '/report#participant-list-card') : '#' ?>" class="nav-item-custom<?= !$recentSessionId ? ' disabled' : '' ?>"><?= htmlspecialchars(t('admin.report.sidebar_participants')) ?></a>
            <a href="<?= $recentSessionId ? eduqr_path('/admin/sessions/' . $recentSessionId) : '#' ?>" class="nav-item-custom<?= !$recentSessionId ? ' disabled' : '' ?>"><?= htmlspecialchars(t('admin.report.live_session_nav')) ?></a>
            <a href="<?= eduqr_path('/admin/archive') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_archive')) ?></a>
            <a href="<?= eduqr_path('/admin/settings') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_settings')) ?></a>
        </div>
        <div class="sidebar-footer">
            <div class="profile-img">👤</div>
            <div>
                <div class="small fw-bold text-white"><?= htmlspecialchars($user['name'] ?? ($locale === 'en' ? 'Instructor' : 'Öğretmen')) ?></div>
                <div class="text-muted small" style="font-size: 0.75rem;"><?= htmlspecialchars(t('admin.report.sidebar_admin')) ?></div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="content-area">
        <!-- Top Toolbar -->
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <a href="<?= eduqr_path('/admin/dashboard') ?>" class="btn btn-sm btn-outline-secondary rounded-3"><?= htmlspecialchars(t('admin.course.back')) ?></a>
            
            <div class="d-flex align-items-center gap-3">
                <!-- Theme and Language controls -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Theme Toggle Switcher -->
                    <div style="background: rgba(0, 0, 0, 0.05); border: 1px solid rgba(0, 0, 0, 0.1); padding: 0.2rem; border-radius: 10px;">
                        <button class="btn btn-sm d-flex align-items-center justify-content-center p-1 rounded-2" onclick="toggleTheme()" style="width: 28px; height: 28px; border: none; background: transparent; font-size: 0.85rem;">
                            <span id="theme-icon-light" class="d-none">☀️</span>
                            <span id="theme-icon-dark">🌙</span>
                        </button>
                    </div>
                    <!-- Language Switcher -->
                    <div class="d-flex align-items-center gap-2" style="background: rgba(0, 0, 0, 0.05); border: 1px solid rgba(0, 0, 0, 0.1); padding: 0.2rem; border-radius: 10px;">
                        <a href="?lang=en" class="px-2 py-1 small rounded-2 text-decoration-none <?= $locale === 'en' ? 'bg-primary text-white' : 'text-muted' ?>" style="font-weight: 700; font-size: 0.75rem;">EN</a>
                        <a href="?lang=tr" class="px-2 py-1 small rounded-2 text-decoration-none <?= $locale === 'tr' ? 'bg-primary text-white' : 'text-muted' ?>" style="font-weight: 700; font-size: 0.75rem;">TR</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card-custom mb-4">
                    <div class="d-flex align-items-center gap-3 mb-1">
                        <span class="text-primary fw-bold text-uppercase tracking-wider small"><?= htmlspecialchars($course['code']) ?></span>
                        <?php if (!empty($course['term'])): ?>
                            <span class="badge bg-secondary bg-opacity-20 text-muted py-1 px-2.5 rounded-pill small" style="font-size: 0.75rem; border: 1px solid rgba(255,255,255,0.08);"><?= htmlspecialchars($course['term']) ?></span>
                        <?php endif; ?>
                    </div>
                    <h1 class="h2 fw-bold mt-1 mb-2"><?= htmlspecialchars($course['title']) ?></h1>
                    <?php if (!empty($course['description'])): ?>
                        <p class="text-muted mb-3 fs-5"><?= htmlspecialchars($course['description']) ?></p>
                    <?php endif; ?>
                    <p class="text-muted mb-0 small"><?= htmlspecialchars(t('admin.course.sessions_desc')) ?></p>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <!-- Sessions list -->
            <div class="col-12">
                <div class="card-custom">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0"><?= htmlspecialchars(t('admin.course.sessions_title')) ?></h4>
                        <button class="btn btn-custom-primary" data-bs-toggle="modal" data-bs-target="#newSessionModal"><?= htmlspecialchars(t('admin.course.start_session')) ?></button>
                    </div>

                    <?php if (empty($sessions)): ?>
                        <div class="empty-state text-center py-5">
                            <div class="empty-icon fs-1">⏱️</div>
                            <h5 class="fw-semibold mb-2"><?= htmlspecialchars(t('admin.course.no_sessions')) ?></h5>
                            <p class="text-muted mb-0"><?= htmlspecialchars(t('admin.course.no_sessions_desc')) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($sessions as $session): ?>
                                <a href="<?= eduqr_path('/admin/sessions/' . (int)$session['id']) ?>" class="session-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h5 class="fw-bold mb-0"><?= htmlspecialchars($session['title']) ?></h5>
                                                <?php
                                                $statusClass = 'badge-active';
                                                $statusText = t('admin.course.session_active');
                                                if ($session['status'] === 'paused') {
                                                    $statusClass = 'badge-paused';
                                                    $statusText = t('admin.course.session_paused');
                                                } elseif ($session['status'] === 'closed') {
                                                    $statusClass = 'badge-closed';
                                                    $statusText = t('admin.course.session_closed');
                                                }
                                                ?>
                                                <span class="badge <?= $statusClass ?> py-1 px-2.5 rounded-pill small"><?= $statusText ?></span>
                                            </div>
                                            <span class="text-muted small"><?= htmlspecialchars(t('admin.course.session_code')) ?>: <code class="text-primary font-monospace fw-bold"><?= htmlspecialchars($session['short_code']) ?></code></span>
                                        </div>
                                        <span class="btn btn-sm btn-custom-outline py-2 px-3 rounded-3"><?= htmlspecialchars(t('admin.course.view_session')) ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- New Session Modal -->
    <div class="modal fade" id="newSessionModal" tabindex="-1" aria-labelledby="newSessionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="newSessionModalLabel"><?= htmlspecialchars(t('admin.course.new_session_title')) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= eduqr_path('/admin/courses/' . (int)$course['id'] . '/sessions') ?>" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="session_title" class="form-label text-muted small fw-semibold"><?= htmlspecialchars(t('admin.course.session_subject')) ?></label>
                            <input type="text" class="form-control" id="session_title" name="title" required placeholder="<?= htmlspecialchars(t('admin.course.session_subject_placeholder')) ?>" autocomplete="off">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2 rounded-3 border-opacity-10" data-bs-dismiss="modal"><?= htmlspecialchars(t('admin.dashboard.cancel')) ?></button>
                        <button type="submit" class="btn btn-custom-primary px-4 py-2"><?= htmlspecialchars(t('admin.course.start_session')) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Theme State Script -->
    <script>
        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('eduqr_theme', theme);
            const sunIcon = document.getElementById('theme-icon-light');
            const moonIcon = document.getElementById('theme-icon-dark');
            if (sunIcon && moonIcon) {
                if (theme === 'dark') {
                    sunIcon.classList.add('d-none');
                    moonIcon.classList.remove('d-none');
                } else {
                    sunIcon.classList.remove('d-none');
                    moonIcon.classList.add('d-none');
                }
            }
        }
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
        }
        document.addEventListener('DOMContentLoaded', () => {
            applyTheme(document.documentElement.getAttribute('data-theme') || 'dark');
        });
    </script>
</body>
</html>
