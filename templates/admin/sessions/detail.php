<?php
use EduQR\Services\AuthService;
$user = AuthService::user();
$locale = \EduQR\I18n\I18nService::getLocale();
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($session['title']) ?> - eduQR</title>
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
            --btn-logout-bg: rgba(239, 68, 68, 0.05);
            --btn-logout-border: rgba(239, 68, 68, 0.2);
            --btn-custom-outline-border: rgba(0, 0, 0, 0.15);
            --btn-custom-outline-hover-bg: rgba(0, 0, 0, 0.05);
            --btn-custom-outline-color: #0f172a;
            --code-box-bg: rgba(59, 130, 246, 0.04);
            --code-box-border: rgba(59, 130, 246, 0.15);
            --qr-box-bg: rgba(0, 0, 0, 0.015);
            --qr-box-border: rgba(0, 0, 0, 0.05);
            --live-results-bg: linear-gradient(135deg, rgba(16, 185, 129, 0.02) 0%, rgba(59, 130, 246, 0.02) 100%);
            --live-results-border: rgba(16, 185, 129, 0.15);
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
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
            --btn-logout-bg: rgba(239, 68, 68, 0.08);
            --btn-logout-border: rgba(239, 68, 68, 0.25);
            --btn-custom-outline-border: rgba(255, 255, 255, 0.15);
            --btn-custom-outline-hover-bg: rgba(255, 255, 255, 0.05);
            --btn-custom-outline-color: var(--text-main);
            --code-box-bg: rgba(59, 130, 246, 0.06);
            --code-box-border: rgba(59, 130, 246, 0.2);
            --qr-box-bg: rgba(255, 255, 255, 0.03);
            --qr-box-border: rgba(255, 255, 255, 0.05);
            --live-results-bg: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
            --live-results-border: rgba(16, 185, 129, 0.2);
            --sidebar-bg: #0b0f19;
            --sidebar-hover: #111827;
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

        /* Access Code Card */
        .code-box {
            background: var(--code-box-bg);
            border: 1px solid var(--code-box-border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
        }

        .code-value {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: 4px;
            color: #3b82f6;
            font-family: monospace;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.35);
            color: #fff;
        }

        .btn-custom-outline {
            background: transparent;
            border: 1px solid var(--btn-custom-outline-border);
            color: var(--btn-custom-outline-color);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
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

        .btn-logout {
            background: var(--btn-logout-bg);
            border: 1px solid var(--btn-logout-border);
            color: #ef4444;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: #fff;
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.25);
        }

        .empty-state {
            padding: 3rem 1.5rem;
        }

        .empty-icon {
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Question Item Rows */
        .q-item {
            background: var(--item-bg);
            border: 1px solid var(--item-border);
            border-radius: 14px;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .q-item.active {
            background: var(--item-hover-bg);
            border-color: var(--item-hover-border);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.05);
        }

        /* Modal custom dark overrides */
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
        .form-control, .form-select {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--input-color) !important;
            border-radius: 10px !important;
            padding: 0.8rem 1rem !important;
            caret-color: var(--text-main) !important;
        }
        .form-control::placeholder, .form-select::placeholder, textarea::placeholder, input::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.75 !important;
        }
        .form-select option {
            background-color: var(--modal-bg) !important;
            color: var(--text-main) !important;
        }
        .form-control:focus, .form-select:focus {
            background: var(--input-bg) !important;
            border-color: #3b82f6 !important;
            color: var(--input-color) !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25) !important;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        /* ── File Upload Drop Zone ──────────────────────────────── */
        .file-drop-zone {
            border: 2px dashed var(--input-border);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--input-bg);
            position: relative;
        }
        .file-drop-zone:hover, .file-drop-zone.dragover {
            border-color: var(--primary);
            background: rgba(59, 130, 246, 0.04);
        }
        .file-drop-icon { font-size: 2rem; margin-bottom: 6px; }
        .file-drop-text { font-size: 0.85rem; color: var(--text-muted); }
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
            <a href="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/report') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_reports')) ?></a>
            <a href="<?= eduqr_path('/admin/dashboard') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_courses')) ?></a>
            <a href="<?= eduqr_path('/admin/question-bank') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_qbank')) ?></a>
            <a href="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/report#participant-list-card') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_participants')) ?></a>
            <a href="<?= eduqr_path('/admin/sessions/' . (int)$session['id']) ?>" class="nav-item-custom active"><?= htmlspecialchars(t('admin.report.live_session_nav')) ?></a>
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
            <a href="<?= eduqr_path('/admin/courses/' . (int)$session['course_id']) ?>" class="btn btn-sm btn-outline-secondary rounded-3"><?= htmlspecialchars(t('admin.session.back')) ?></a>
            
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
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="text-primary fw-bold text-uppercase tracking-wider small"><?= htmlspecialchars(course_title($course)) ?></span>
                        <?php
                        $statusClass = 'badge-active';
                        $statusText = t('admin.session.status_active');
                        if ($session['status'] === 'paused') {
                            $statusClass = 'badge-paused';
                            $statusText = t('admin.session.status_paused');
                        } elseif ($session['status'] === 'closed') {
                            $statusClass = 'badge-closed';
                            $statusText = t('admin.session.status_closed');
                        }
                        ?>
                        <span class="badge <?= $statusClass ?> py-1 px-2.5 rounded-pill small"><?= $statusText ?></span>
                    </div>
                    <h1 class="h2 fw-bold mt-1 mb-2"><?= htmlspecialchars($session['title']) ?></h1>
                    <p class="text-muted mb-0"><?= htmlspecialchars(t('admin.session.desc')) ?></p>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <!-- Left Side: Control panel, questions -->
            <div class="col-12 col-lg-8">
                
                <!-- Oturum Kontrolleri & Raporlar -->
                <div class="card-custom mb-4">
                    <h4 class="fw-bold mb-4"><?= htmlspecialchars(t('admin.session.controls_title')) ?></h4>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/report') ?>" class="btn btn-custom-primary"><?= htmlspecialchars(t('admin.session.view_report_pdf')) ?></a>
                        <a href="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/report/csv') ?>" class="btn btn-custom-outline"><?= htmlspecialchars(t('admin.session.download_report_csv')) ?></a>
                        <?php if ($session['status'] !== 'closed'): ?>
                        <form id="close-session-form"
                              action="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/close') ?>"
                              method="POST"
                              style="display:none;">
                        </form>
                        <button class="btn btn-logout ms-md-auto" onclick="confirmCloseSession(event)"><?= htmlspecialchars(t('admin.session.close_session')) ?></button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Live Results View (Visible only when there is an active question) -->
                <div id="live-results-card" class="card-custom mb-4 d-none" style="background: var(--live-results-bg); border-color: var(--live-results-border);">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <span class="badge badge-active py-1.5 px-3 rounded-pill small mb-2"><?= htmlspecialchars(t('admin.session.live_polling')) ?></span>
                            <h4 class="fw-bold mb-1" id="active-q-text"><?= htmlspecialchars(t('admin.session.active_question')) ?></h4>
                        </div>
                        <span class="text-muted small" id="total-votes">0 <?= htmlspecialchars(t('admin.session.answers_count', ['count' => '0'])) ?></span>
                    </div>
                    <div id="results-bars-container" class="d-flex flex-column gap-3">
                        <!-- Progress bars injected dynamically -->
                    </div>
                </div>

                <div class="card-custom mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold mb-0"><?= htmlspecialchars(t('admin.session.questions_title')) ?></h4>
                        <div class="d-flex gap-2">
                            <a href="<?= eduqr_path('/admin/question-bank') ?>" class="btn btn-custom-outline btn-sm">❓ <?= htmlspecialchars(t('admin.qbank.title')) ?></a>
                            <button class="btn btn-custom-outline btn-sm" data-bs-toggle="modal" data-bs-target="#importQuestionsModal"><?= htmlspecialchars(t('admin.session.import_json')) ?></button>
                            <button class="btn btn-custom-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newQuestionModal"><?= htmlspecialchars(t('admin.session.add_question')) ?></button>
                        </div>
                    </div>

                    <?php if (empty($questions)): ?>
                        <div class="empty-state text-center py-5">
                            <div class="empty-icon fs-1">❓</div>
                            <h5 class="fw-semibold mb-2"><?= htmlspecialchars(t('admin.session.no_questions')) ?></h5>
                            <p class="text-muted mb-0"><?= htmlspecialchars(t('admin.session.no_questions_desc')) ?></p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($questions as $q): ?>
                                <div class="q-item <?= $q['status'] === 'active' ? 'active' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <?php if ($q['status'] === 'active'): ?>
                                                    <span class="badge badge-active py-1 px-2 rounded-pill small"><?= htmlspecialchars(t('admin.session.status_live')) ?></span>
                                                <?php elseif ($q['status'] === 'closed'): ?>
                                                    <span class="badge badge-closed py-1 px-2 rounded-pill small"><?= htmlspecialchars(t('admin.session.status_closed_badge')) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary bg-opacity-20 text-muted py-1 px-2 rounded-pill small"><?= htmlspecialchars(t('admin.session.status_draft')) ?></span>
                                                <?php endif; ?>
                                                <?php
                                                $typeLabel = '';
                                                if ($q['type'] === 'open_ended') {
                                                    $typeLabel = $locale === 'en' ? 'Open-Ended' : 'Açık Uçlu';
                                                } elseif ($q['type'] === 'yes_no') {
                                                    $typeLabel = $locale === 'en' ? 'Yes / No' : 'Evet / Hayır';
                                                } elseif ($q['type'] === 'likert') {
                                                    $typeLabel = $locale === 'en' ? 'Likert Scale' : 'Likert Ölçeği';
                                                } else {
                                                    $typeLabel = htmlspecialchars(t('admin.session.type_mc'));
                                                }
                                                ?>
                                                <span class="text-muted small"><?= $typeLabel ?></span>
                                            </div>
                                            <h5 class="fw-bold mb-3"><?= htmlspecialchars($q['question_text']) ?></h5>
                                            
                                            <?php if (!empty($q['options'])): ?>
                                                <div class="d-flex flex-column gap-1 mb-3">
                                                    <?php foreach ($q['options'] as $idx => $opt): ?>
                                                        <div class="small <?= ($q['correct_answer'] === chr(65 + $idx)) ? 'text-success fw-bold' : 'text-muted' ?>">
                                                            <?= chr(65 + $idx) ?>) <?= htmlspecialchars($opt) ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <?php if ($q['status'] !== 'active'): ?>
                                                <form action="<?= eduqr_path('/admin/questions/' . (int)$q['id'] . '/activate') ?>" method="POST">
                                                    <button type="submit" class="btn btn-sm btn-success py-2 px-3 rounded-3"><?= htmlspecialchars(t('admin.session.publish')) ?></button>
                                                </form>
                                            <?php elseif ($q['status'] === 'active'): ?>
                                                <form action="<?= eduqr_path('/admin/questions/' . (int)$q['id'] . '/close') ?>" method="POST">
                                                    <button type="submit" class="btn btn-sm btn-danger py-2 px-3 rounded-3"><?= htmlspecialchars(t('admin.session.close')) ?></button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Side: Joining Info, QR placeholder, Participants list -->
            <div class="col-12 col-lg-4">
                <div class="card-custom mb-4">
                    <h4 class="fw-bold mb-3"><?= htmlspecialchars(t('admin.session.joining_info')) ?></h4>
                    <div class="code-box mb-4">
                        <span class="text-muted d-block small mb-2"><?= htmlspecialchars(t('admin.session.short_code')) ?></span>
                        <span class="code-value"><?= htmlspecialchars($session['short_code']) ?></span>
                    </div>
                    <div class="text-center py-4 rounded-4" style="background: var(--qr-box-bg); border: 1px solid var(--qr-box-border);">
                        <img src="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/qr.png') ?>" alt="Katılım QR Kodu" class="img-fluid rounded-3" style="max-width: 200px;">
                        <span class="text-muted d-block small mt-2"><?= htmlspecialchars(t('admin.session.qr_desc')) ?></span>
                    </div>
                </div>

                <div class="card-custom">
                    <h4 class="fw-bold mb-4" id="participants-title"><?= htmlspecialchars(t('admin.session.participants', ['count' => '0'])) ?></h4>
                    <div id="participants-container" class="d-flex flex-wrap gap-2">
                        <span class="text-muted small"><?= htmlspecialchars(t('admin.session.no_participants')) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Question Modal -->
    <div class="modal fade" id="newQuestionModal" tabindex="-1" aria-labelledby="newQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="newQuestionModalLabel"><?= htmlspecialchars(t('admin.session.add_question_title')) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/questions') ?>" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="question_text" class="form-label text-muted small fw-semibold"><?= htmlspecialchars(t('admin.session.question_text')) ?></label>
                            <textarea class="form-control" id="question_text" name="question_text" rows="3" required placeholder="<?= htmlspecialchars(t('admin.session.question_text_placeholder')) ?>" autocomplete="off"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="question_type" class="form-label text-muted small fw-semibold"><?= $locale === 'en' ? 'Question Type' : 'Soru Tipi' ?></label>
                            <select class="form-select" id="question_type" name="type" onchange="toggleQuestionTypeFields()">
                                <option value="multiple_choice" selected><?= $locale === 'en' ? 'Multiple Choice' : 'Çoktan Seçmeli' ?></option>
                                <option value="open_ended"><?= $locale === 'en' ? 'Open-Ended' : 'Açık Uçlu' ?></option>
                                <option value="yes_no"><?= $locale === 'en' ? 'Yes / No' : 'Evet / Hayır' ?></option>
                                <option value="likert"><?= $locale === 'en' ? 'Likert Scale (5-point)' : 'Likert Ölçeği (5\'li)' ?></option>
                            </select>
                        </div>
                        
                        <!-- Options Inputs Container -->
                        <div id="mc-options-container">
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-semibold"><?= htmlspecialchars(t('admin.session.options')) ?></label>
                                <input type="text" class="form-control mb-2 option-input" name="options[]" required placeholder="<?= htmlspecialchars(t('admin.session.option_placeholder', ['label' => 'A'])) ?>">
                                <input type="text" class="form-control mb-2 option-input" name="options[]" required placeholder="<?= htmlspecialchars(t('admin.session.option_placeholder', ['label' => 'B'])) ?>">
                                <input type="text" class="form-control mb-2 option-input" name="options[]" placeholder="<?= htmlspecialchars(t('admin.session.option_placeholder', ['label' => 'C'])) ?> (<?= $locale === 'en' ? 'Optional' : 'İsteğe Bağlı' ?>)">
                                <input type="text" class="form-control mb-2 option-input" name="options[]" placeholder="<?= htmlspecialchars(t('admin.session.option_placeholder', ['label' => 'D'])) ?> (<?= $locale === 'en' ? 'Optional' : 'İsteğe Bağlı' ?>)">
                            </div>
                        </div>

                        <!-- Correct Answer Selection -->
                        <div id="correct-answer-container">
                            <div class="mb-3">
                                <label id="correct-answer-label" for="correct_answer" class="form-label text-muted small fw-semibold"><?= htmlspecialchars(t('admin.session.correct_answer')) ?></label>
                                <select class="form-select" id="correct_answer" name="correct_answer">
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary px-4 py-2 rounded-3 border-opacity-10" data-bs-dismiss="modal"><?= htmlspecialchars(t('admin.dashboard.cancel')) ?></button>
                        <button type="submit" class="btn btn-custom-primary px-4 py-2"><?= htmlspecialchars(t('admin.session.add_question')) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JSON Import Modal -->
    <div class="modal fade" id="importQuestionsModal" tabindex="-1" aria-labelledby="importQuestionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="importQuestionsModalLabel"><?= htmlspecialchars(t('admin.session.import_questions_title')) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3"><?= htmlspecialchars(t('admin.session.import_instructions')) ?></p>
                    
                    <!-- File Upload Option -->
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold"><?= $locale === 'en' ? 'Option A: Upload JSON File' : 'Seçenek A: JSON Dosyası Yükle' ?></label>
                        <div class="file-drop-zone" id="modal-file-drop-zone">
                            <input type="file" id="modal-json-file" accept=".json" style="display:none;" onchange="handleModalFileSelect(this)">
                            <div class="file-drop-icon">📄</div>
                            <div class="file-drop-text" id="modal-file-text"><?= $locale === 'en' ? 'Click to select or drag & drop a .json file here' : '.json dosyasını seçmek için tıklayın veya buraya sürükleyin' ?></div>
                        </div>
                    </div>

                    <div class="text-center text-muted my-3 small fw-bold">— <?= $locale === 'en' ? 'OR' : 'VEYA' ?> —</div>

                    <!-- Textarea Option -->
                    <div class="mb-3">
                        <label for="import-json" class="form-label text-muted small fw-semibold"><?= $locale === 'en' ? 'Option B: Paste JSON Content' : 'Seçenek B: JSON İçeriğini Yapıştır' ?></label>
                        <textarea id="import-json" class="form-control font-monospace small" rows="6" placeholder='{
  "questions": [
    {
      "question_text": "<?= $locale === 'en' ? 'Sample Question Text?' : 'Örnek Soru Metni?' ?>",
      "options": ["<?= $locale === 'en' ? 'A Option' : 'A Seçeneği' ?>", "<?= $locale === 'en' ? 'B Option' : 'B Seçeneği' ?>"],
      "correct_answer": "B"
    }
  ]
}'></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary px-4 py-2 rounded-3 border-opacity-10" data-bs-dismiss="modal"><?= htmlspecialchars(t('admin.dashboard.cancel')) ?></button>
                    <button type="button" onclick="importQuestions()" class="btn btn-custom-primary px-4 py-2"><?= htmlspecialchars(t('admin.session.import')) ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Polling Logic script -->
    <script>
        const sessionId = <?= json_encode($session['id']) ?>;
        const apiParticipantsUrl = <?= json_encode(eduqr_path('/admin/sessions/')) ?> + sessionId + '/participants/count';
        const apiResultsUrl = <?= json_encode(eduqr_path('/admin/sessions/')) ?> + sessionId + '/results';

        const participantsTitle = document.getElementById('participants-title');
        const participantsContainer = document.getElementById('participants-container');
        
        const liveResultsCard = document.getElementById('live-results-card');
        const activeQText = document.getElementById('active-q-text');
        const totalVotes = document.getElementById('total-votes');
        const resultsBarsContainer = document.getElementById('results-bars-container');

        // Localized JS Variables
        const translationParticipants = <?= json_encode(t('admin.session.participants')) ?>;
        const translationNoParticipants = <?= json_encode(t('admin.session.no_participants')) ?>;
        const translationAnswersCount = <?= json_encode(t('admin.session.answers_count')) ?>;
        const translationAlertSubmitFailed = <?= json_encode(t('admin.session.alert_submit_failed')) ?>;
        const translationAlertConnectionError = <?= json_encode(t('admin.session.alert_connection_error')) ?>;
        const translationInvalidJson = <?= json_encode($locale === 'en' ? 'Invalid JSON format!' : 'Geçersiz JSON formatı!') ?>;
        const translationCloseWarning = <?= json_encode(t('admin.session.close_warning')) ?>;

        // Questions lookup mapping for results title
        const questionsLookup = {};
        <?php foreach ($questions as $q): ?>
            questionsLookup[<?= (int)$q['id'] ?>] = <?= json_encode($q['question_text']) ?>;
            questionsLookup[<?= (int)$q['id'] ?> + '_opts'] = <?= json_encode($q['options']) ?>;
        <?php endforeach; ?>

        async function pollData() {
            // 1. Fetch participants
            try {
                const res = await fetch(apiParticipantsUrl);
                const data = await res.json();

                participantsTitle.textContent = translationParticipants.replace('{count}', data.count);
                if (data.count === 0) {
                    participantsContainer.innerHTML = `<span class="text-muted small">${translationNoParticipants}</span>`;
                } else {
                    // Sadece sayı göster — nickname gösterilmez
                    participantsContainer.innerHTML = `<span class="badge bg-primary bg-opacity-10 text-primary py-2 px-4 rounded-3 fw-bold fs-5">${data.count}</span>`;
                }
            } catch (err) {
                console.error("Participants count fetch error:", err);
            }

            // 2. Fetch active question results
            try {
                const res = await fetch(apiResultsUrl);
                const data = await res.json();

                if (data.active) {
                    const qTextVal = questionsLookup[data.question_id] || "Active Question";
                    activeQText.textContent = qTextVal;
                    liveResultsCard.classList.remove('d-none');

                    if (data.type === 'open_ended') {
                        // Open-Ended Questions: render scrolling card grid of answers
                        const totalVal = data.results ? data.results.length : 0;
                        totalVotes.textContent = translationAnswersCount.replace('{count}', totalVal);

                        resultsBarsContainer.innerHTML = '';
                        if (totalVal === 0) {
                            resultsBarsContainer.innerHTML = `<span class="text-muted small">${locale === 'en' ? 'No responses yet.' : 'Henüz cevap yok.'}</span>`;
                        } else {
                            let html = `<div class="row g-2">`;
                            data.results.forEach((r, idx) => {
                                const timeStr = r.created_at ? new Date(r.created_at).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'}) : '';
                                html += `
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="p-3 rounded-3 border bg-white bg-opacity-5" style="border: 1px solid var(--item-border) !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                                                <span class="fw-semibold">Cevap #${idx + 1}</span>
                                                <span>${timeStr}</span>
                                            </div>
                                            <div class="text-white small fw-medium" style="word-break: break-word;">${escapeHtml(r.answer_value)}</div>
                                        </div>
                                    </div>
                                `;
                            });
                            html += `</div>`;
                            resultsBarsContainer.innerHTML = html;
                        }
                    } else {
                        // Multiple Choice Questions
                        const qOpts = questionsLookup[data.question_id + '_opts'] || [];

                        // Calculate total votes
                        const votesLookup = {};
                        let totalVal = 0;
                        if (data.results) {
                            data.results.forEach(r => {
                                votesLookup[r.answer_value] = parseInt(r.count);
                                totalVal += parseInt(r.count);
                            });
                        }

                        totalVotes.textContent = translationAnswersCount.replace('{count}', totalVal);

                        // Render progress bars
                        resultsBarsContainer.innerHTML = '';
                        qOpts.forEach((opt, idx) => {
                            const char = String.fromCharCode(65 + idx);
                            const count = votesLookup[char] || 0;
                            const pct = totalVal > 0 ? Math.round((count / totalVal) * 100) : 0;

                            resultsBarsContainer.innerHTML += `
                                <div>
                                    <div class="d-flex justify-content-between mb-1 small">
                                        <span><strong>${char})</strong> ${escapeHtml(opt)}</span>
                                        <span>${count} (${pct}%)</span>
                                    </div>
                                    <div class="progress bg-white bg-opacity-5" style="height: 10px; border-radius: 5px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: ${pct}%; border-radius: 5px; transition: width 0.4s ease;" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                } else {
                    liveResultsCard.classList.add('d-none');
                }
            } catch (err) {
                console.error("Results fetch error:", err);
            }
        }

        function handleModalFileSelect(input) {
            const text = document.getElementById('modal-file-text');
            if (input.files && input.files[0]) {
                text.innerHTML = '<strong>✅ ' + escapeHtml(input.files[0].name) + '</strong>';
            } else {
                text.textContent = '<?= $locale === 'en' ? 'Click to select or drag & drop a .json file here' : '.json dosyasını seçmek için tıklayın veya buraya sürükleyin' ?>';
            }
        }

        async function importQuestions() {
            const fileInput = document.getElementById('modal-json-file');
            const textarea = document.getElementById('import-json');

            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = async function(e) {
                    try {
                        const parsed = JSON.parse(e.target.result);
                        await sendImportPayload(parsed);
                    } catch (err) {
                        alert("<?= $locale === 'en' ? 'Invalid JSON file content.' : 'Geçersiz JSON dosyası içeriği.' ?>");
                    }
                };
                reader.readAsText(fileInput.files[0]);
            } else if (textarea.value.trim() !== "") {
                try {
                    const parsed = JSON.parse(textarea.value);
                    await sendImportPayload(parsed);
                } catch (e) {
                    alert(translationInvalidJson);
                }
            } else {
                alert("<?= $locale === 'en' ? 'Please select a file or paste JSON content.' : 'Lütfen bir dosya seçin veya JSON içeriği yapıştırın.' ?>");
            }
        }

        async function sendImportPayload(parsed) {
            let payload = {};
            if (Array.isArray(parsed)) {
                payload.questions = parsed.map(q => {
                    const options = q.options || null;
                    let answer = q.answer !== undefined ? q.answer : (q.correct_answer !== undefined ? q.correct_answer : null);
                    if (Array.isArray(options) && typeof answer === 'number' && options[answer] !== undefined) {
                        answer = String.fromCharCode(65 + answer);
                    }
                    return {
                        question_text: q.text || q.question_text || "",
                        type: Array.isArray(options) && options.length >= 2 ? "multiple_choice" : "open_ended",
                        options: options,
                        correct_answer: answer
                    };
                });
            } else {
                payload = parsed;
                if (payload.questions && Array.isArray(payload.questions)) {
                    payload.questions = payload.questions.map(q => {
                        const options = q.options || null;
                        let answer = q.answer !== undefined ? q.answer : (q.correct_answer !== undefined ? q.correct_answer : null);
                        if (Array.isArray(options) && typeof answer === 'number' && options[answer] !== undefined) {
                            answer = String.fromCharCode(65 + answer);
                        }
                        return {
                            question_text: q.text || q.question_text || "",
                            type: q.type || (Array.isArray(options) && options.length >= 2 ? "multiple_choice" : "open_ended"),
                            options: options,
                            correct_answer: answer
                        };
                    });
                }
            }

            try {
                const btn = document.querySelector("#importQuestionsModal .btn-custom-primary");
                if (btn) btn.disabled = true;

                const res = await fetch(<?= json_encode(eduqr_path('/admin/sessions/')) ?> + sessionId + '/questions/import', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert("Error: " + (data.error || translationAlertSubmitFailed));
                }
            } catch (e) {
                alert(translationAlertConnectionError);
            } finally {
                const btn = document.querySelector("#importQuestionsModal .btn-custom-primary");
                if (btn) btn.disabled = false;
            }
        }

        function confirmCloseSession(e) {
            e.preventDefault();
            if (!confirm(translationCloseWarning)) {
                return false;
            }
            document.getElementById('close-session-form').submit();
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        setInterval(pollData, 3000);
        pollData(); // First load immediately
    </script>

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
        function toggleQuestionTypeFields() {
            const type = document.getElementById('question_type').value;
            const optionsContainer = document.getElementById('mc-options-container');
            const correctContainer = document.getElementById('correct-answer-container');
            const correctSelect = document.getElementById('correct_answer');
            const optionInputs = optionsContainer.querySelectorAll('.option-input');

            // Reset correct select choices based on type
            correctSelect.innerHTML = '';

            if (type === 'multiple_choice') {
                optionsContainer.classList.remove('d-none');
                correctContainer.classList.remove('d-none');
                optionInputs.forEach((input, idx) => {
                    if (idx < 2) input.setAttribute('required', 'required');
                });
                // A, B, C, D
                ['A', 'B', 'C', 'D'].forEach(val => {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = val;
                    correctSelect.appendChild(opt);
                });
            } else if (type === 'yes_no') {
                optionsContainer.classList.add('d-none');
                correctContainer.classList.remove('d-none');
                optionInputs.forEach(input => input.removeAttribute('required'));
                // A (Evet), B (Hayır)
                const optA = document.createElement('option');
                optA.value = 'A';
                optA.textContent = 'A (Evet / Yes)';
                const optB = document.createElement('option');
                optB.value = 'B';
                optB.textContent = 'B (Hayır / No)';
                correctSelect.appendChild(optA);
                correctSelect.appendChild(optB);
                
                // Add none
                const optNone = document.createElement('option');
                optNone.value = '';
                optNone.textContent = '<?= $locale === 'en' ? 'None' : 'Yok' ?>';
                correctSelect.appendChild(optNone);
            } else { // likert, open_ended
                optionsContainer.classList.add('d-none');
                correctContainer.classList.add('d-none');
                optionInputs.forEach(input => input.removeAttribute('required'));
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            applyTheme(document.documentElement.getAttribute('data-theme') || 'dark');

            // Wire drag-drop zone inside import modal
            const modalDropZone = document.getElementById('modal-file-drop-zone');
            if (modalDropZone) {
                modalDropZone.addEventListener('click', () => {
                    document.getElementById('modal-json-file').click();
                });
                modalDropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    modalDropZone.classList.add('dragover');
                    modalDropZone.style.borderColor = 'var(--primary)';
                    modalDropZone.style.background = 'rgba(59, 130, 246, 0.05)';
                });
                modalDropZone.addEventListener('dragleave', () => {
                    modalDropZone.classList.remove('dragover');
                    modalDropZone.style.borderColor = '';
                    modalDropZone.style.background = '';
                });
                modalDropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    modalDropZone.classList.remove('dragover');
                    modalDropZone.style.borderColor = '';
                    modalDropZone.style.background = '';
                    const fileInput = document.getElementById('modal-json-file');
                    fileInput.files = e.dataTransfer.files;
                    handleModalFileSelect(fileInput);
                });
            }
        });
    </script>
</body>
</html>
