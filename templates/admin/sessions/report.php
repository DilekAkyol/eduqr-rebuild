<?php
use EduQR\Services\AuthService;

$user = AuthService::user();
$locale = \EduQR\I18n\I18nService::getLocale();

// Calculate total possible answers: count(participants) * count(questions)
$totalParticipants = count($participants);
$totalQuestions = count($questions);
$totalPossibleAnswers = $totalParticipants * $totalQuestions;

// Count actual answers submitted
$totalAnswersSubmitted = 0;
foreach ($results as $qId => $votes) {
    foreach ($votes as $v) {
        $totalAnswersSubmitted += (int)$v['count'];
    }
}

// Calculate participation rate
$participationRate = $totalPossibleAnswers > 0 
    ? (int)round(($totalAnswersSubmitted / $totalPossibleAnswers) * 100) 
    : 0;
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eduQR <?= htmlspecialchars(t('admin.report.title')) ?> - <?= htmlspecialchars($session['short_code']) ?></title>
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
            --bg-color: #f1f5f9;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #3b82f6;
            --border-color: #e2e8f0;
            --shadow-opacity: 0.02;
            --list-item-bg: #f8fafc;
            --list-item-border: #cbd5e1;
        }

        [data-theme="dark"] {
            /* Dark Mode Variables */
            --bg-color: #030712;
            --sidebar-bg: #0b0f19;
            --sidebar-hover: #111827;
            --card-bg: rgba(255, 255, 255, 0.03);
            --text-main: #f9fafb;
            --text-muted: #94a3b8;
            --border-color: rgba(255, 255, 255, 0.08);
            --shadow-opacity: 0.5;
            --list-item-bg: rgba(255, 255, 255, 0.015);
            --list-item-border: rgba(255, 255, 255, 0.05);
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

        /* Sidebar Design matching Slide 5 */
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

        /* Main Content Panel */
        .content-area {
            flex-grow: 1;
            padding: 2.5rem;
            max-width: 1200px;
        }

        .card-custom {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.8rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, var(--shadow-opacity));
            margin-bottom: 1.5rem;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        /* KPI Cards */
        .kpi-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.2rem;
            background-color: var(--card-bg);
            box-shadow: 0 4px 12px rgba(0, 0, 0, var(--shadow-opacity));
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        .kpi-blue { background: rgba(59, 130, 246, 0.08); color: #3b82f6; }
        .kpi-purple { background: rgba(139, 92, 246, 0.08); color: #8b5cf6; }
        .kpi-green { background: rgba(16, 185, 129, 0.08); color: #10b981; }
        .kpi-orange { background: rgba(245, 158, 11, 0.08); color: #f59e0b; }

        .kpi-value {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.2;
        }

        /* Horizontal Bar Chart for Multiple Choice */
        .bar-row {
            margin-bottom: 1.2rem;
        }
        .bar-container {
            height: 32px;
            background-color: var(--list-item-bg);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        .bar-fill {
            height: 100%;
            border-radius: 8px;
            transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .bar-fill-blue {
            background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
        }
        .bar-fill-green {
            background: linear-gradient(90deg, #10b981 0%, #34d399 100%);
        }

        /* Open Ended Responses */
        .response-item {
            border-bottom: 1px solid var(--border-color);
            padding: 0.8rem 0;
        }
        .response-item:last-child {
            border-bottom: none;
        }

        /* Footer buttons */
        .btn-action {
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            background-color: var(--card-bg);
            color: var(--text-main);
            transition: all 0.2s;
        }
        .btn-action:hover {
            background-color: var(--list-item-bg);
        }

        /* Print styles */
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .sidebar, .no-print {
                display: none !important;
            }
            .content-area {
                padding: 0;
                max-width: 100%;
            }
            .card-custom {
                box-shadow: none;
                border: 1px solid #cbd5e1;
            }
        }

        /* Actions Box */
        .actions-card {
            background-color: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin-top: 2rem;
            transition: all 0.3s;
        }
        
        [data-theme="dark"] .actions-card {
            background-color: rgba(239, 68, 68, 0.05);
            border-color: rgba(239, 68, 68, 0.25);
        }

        .actions-card h5 {
            color: #ef4444;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .btn-anonymize-session {
            background: transparent;
            border: 1.5px solid #d97706;
            color: #d97706;
            font-weight: 600;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }
        .btn-anonymize-session:hover {
            background-color: rgba(217, 119, 6, 0.08);
            color: #b45309;
            border-color: #b45309;
        }

        [data-theme="dark"] .btn-anonymize-session {
            border-color: #f59e0b;
            color: #f59e0b;
        }
        [data-theme="dark"] .btn-anonymize-session:hover {
            background-color: rgba(245, 158, 11, 0.1);
            color: #fbbf24;
            border-color: #fbbf24;
        }

        .btn-delete-session {
            background: transparent;
            border: 1.5px solid #ef4444;
            color: #ef4444;
            font-weight: 600;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }
        .btn-delete-session:hover {
            background-color: rgba(239, 68, 68, 0.08);
            color: #dc2626;
            border-color: #dc2626;
        }

        [data-theme="dark"] .btn-delete-session {
            border-color: #f87171;
            color: #f87171;
        }
        [data-theme="dark"] .btn-delete-session:hover {
            background-color: rgba(248, 113, 113, 0.1);
            color: #ef4444;
            border-color: #ef4444;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        /* ── Modal Customizations matching Detail Page ──────────── */
        .modal-content {
            background-color: var(--card-bg) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
            border-radius: 20px !important;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .modal-header {
            border-bottom: 1px solid var(--border-color) !important;
        }
        .modal-footer {
            border-top: 1px solid var(--border-color) !important;
        }
        .form-control {
            background: var(--bg-color) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
            border-radius: 10px !important;
            padding: 0.8rem 1rem !important;
        }
        .form-control:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25) !important;
        }

        /* ── File Upload Drop Zone ──────────────────────────────── */
        .file-drop-zone {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: var(--bg-color);
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

    <!-- Left Sidebar matching Slide 5 -->
    <div class="sidebar no-print">
        <a href="<?= eduqr_path('/admin/dashboard') ?>" class="sidebar-logo">
            <div class="sidebar-logo-icon">❖</div>
            <span>eduQR</span>
        </a>
        <div class="nav-menu">
            <a href="#" class="nav-item-custom active"><?= htmlspecialchars(t('admin.report.sidebar_reports')) ?></a>
            <a href="<?= eduqr_path('/admin/dashboard') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_courses')) ?></a>
            <a href="<?= eduqr_path('/admin/question-bank') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_qbank')) ?></a>
            <a href="#participant-list-card" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_participants')) ?></a>
            <a href="<?= eduqr_path('/admin/sessions/' . (int)$session['id']) ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.live_session_nav')) ?></a>
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
            <a href="<?= eduqr_path('/admin/sessions/' . (int)$session['id']) ?>" class="btn btn-sm btn-outline-secondary rounded-3"><?= htmlspecialchars(t('admin.report.back')) ?></a>
            
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
                <span class="text-muted small"><?= htmlspecialchars(t('admin.report.title')) ?></span>
            </div>
        </div>

        <!-- Session Header info -->
        <div class="card-custom mb-4">
            <div class="row align-items-center">
                <div class="col-12 col-md-8 mb-3 mb-md-0">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary bg-opacity-10 text-primary px-2.5 py-1 rounded-pill small"><?= htmlspecialchars(t('admin.report.lesson_report')) ?></span>
                        <span class="text-muted small"><?= htmlspecialchars(t('admin.report.date')) ?>: <?= date('d M Y', strtotime($session['created_at'])) ?></span>
                    </div>
                    <h3 class="fw-bold mb-1"><?= htmlspecialchars($session['title']) ?></h3>
                    <div class="text-muted small">
                        📚 <?= htmlspecialchars($course['title']) ?> (<?= htmlspecialchars($course['code']) ?>)
                    </div>
                </div>
                <div class="col-12 col-md-4 text-md-end">
                    <span class="text-muted small d-block"><?= htmlspecialchars(t('admin.report.session_code')) ?></span>
                    <span class="fs-2 fw-extrabold font-monospace text-primary"><?= htmlspecialchars($session['short_code']) ?></span>
                </div>
            </div>
        </div>

        <!-- KPI Grid -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon kpi-blue">👥</div>
                    <div>
                        <div class="text-muted small fw-semibold"><?= htmlspecialchars(t('student.wait.participant')) ?></div>
                        <div class="kpi-value" id="kpi-participants"><?= $totalParticipants ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon kpi-purple">❓</div>
                    <div>
                        <div class="text-muted small fw-semibold"><?= htmlspecialchars(t('admin.report.total_questions')) ?></div>
                        <div class="kpi-value"><?= $totalQuestions ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon kpi-green">💬</div>
                    <div>
                        <div class="text-muted small fw-semibold"><?= htmlspecialchars(t('admin.report.total_answers')) ?></div>
                        <div class="kpi-value"><?= $totalAnswersSubmitted ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon kpi-orange">📈</div>
                    <div>
                        <div class="text-muted small fw-semibold"><?= htmlspecialchars(t('admin.report.participation_rate')) ?></div>
                        <div class="kpi-value">%<?= $participationRate ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Card -->
        <div class="actions-card mb-4 no-print">
            <h5><?= $locale === 'en' ? 'Actions' : 'İşlemler' ?></h5>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- Import Questions Button -->
                <button type="button" class="btn-import-session" style="border: 1px solid var(--border-color); color: var(--text-main); font-weight: 600; padding: 0.6rem 1.2rem; border-radius: 10px; transition: all 0.3s; background: transparent;" data-bs-toggle="modal" data-bs-target="#importQuestionsModal">
                    📥 <?= $locale === 'en' ? 'Import Questions (JSON)' : 'Soruları İçe Aktar (JSON)' ?>
                </button>

                <!-- Anonymize Session Form -->
                <?php if (!$session['is_anonymized']): ?>
                <form action="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/anonymize') ?>" method="POST" onsubmit="return confirm('<?= $locale === 'en' ? 'Are you sure you want to permanently anonymize this session\'s participants?' : 'Bu oturumdaki tüm katılımcı isimlerini kalıcı olarak anonimleştirmek istediğinize emin misiniz?' ?>');">
                    <button type="submit" class="btn-anonymize-session">
                        <?= $locale === 'en' ? 'Anonymize Session' : 'Oturumu Anonimleştir' ?>
                    </button>
                </form>
                <?php endif; ?>

                <!-- Delete Session Form -->
                <form action="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/delete') ?>" method="POST" onsubmit="return confirm('<?= $locale === 'en' ? 'Are you sure you want to permanently delete this session and all its data?' : 'Bu oturumu ve tüm verilerini kalıcı olarak silmek istediğinize emin misiniz?' ?>');">
                    <button type="submit" class="btn-delete-session">
                        <?= $locale === 'en' ? 'Delete Session' : 'Oturumu Sil' ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Main Report Body -->
        <div class="row g-4">
            <!-- Left Side: Questions & Charts -->
            <div class="col-12 col-lg-8">
                <div class="card-custom">
                    <h4 class="fw-bold mb-4"><?= htmlspecialchars(t('admin.report.question_analysis')) ?></h4>

                    <?php if (empty($questions)): ?>
                        <div class="text-center py-5 text-muted"><?= htmlspecialchars(t('admin.report.no_questions_asked')) ?></div>
                    <?php else: ?>
                        <?php foreach ($questions as $qIndex => $q): ?>
                            <div class="mb-5 pb-4 border-bottom last-border-none">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary py-1 px-2.5 rounded-pill small"><?= htmlspecialchars(t('admin.report.question_number', ['num' => ($qIndex + 1)])) ?></span>
                                    <span class="text-muted small"><?= $q['type'] === 'open_ended' ? ($locale === 'en' ? 'Open-Ended' : 'Açık Uçlu') : htmlspecialchars(t('admin.session.type_mc')) ?></span>
                                </div>
                                <h5 class="fw-bold mb-3"><?= htmlspecialchars($q['question_text']) ?></h5>

                                <?php if ($q['type'] === 'open_ended'): ?>
                                    <?php $answers = $results[$q['id']] ?? []; ?>
                                    <div class="d-flex flex-column gap-2 mt-3">
                                        <?php if (empty($answers)): ?>
                                            <span class="text-muted small"><?= $locale === 'en' ? 'No responses yet.' : 'Henüz cevap yok.' ?></span>
                                        <?php else: ?>
                                            <?php foreach ($answers as $idx => $ans): ?>
                                                <div class="p-3 rounded-3 border small" style="background: var(--list-item-bg); border: 1px solid var(--list-item-border) !important;">
                                                    <div class="d-flex justify-content-between align-items-center mb-1 text-muted extra-small" style="font-size: 0.75rem;">
                                                        <span class="fw-semibold"><?= $locale === 'en' ? 'Response' : 'Cevap' ?> #<?= $idx + 1 ?></span>
                                                        <span><?= date('H:i', strtotime($ans['created_at'])) ?></span>
                                                    </div>
                                                    <div class="fw-medium text-main" style="word-break: break-word;"><?= htmlspecialchars($ans['answer_value']) ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif (!empty($q['options'])): ?>
                                    <?php
                                    $votes = $results[$q['id']] ?? [];
                                    $votesLookup = [];
                                    $totalQVotes = 0;
                                    foreach ($votes as $v) {
                                        $votesLookup[$v['answer_value']] = (int)$v['count'];
                                        $totalQVotes += (int)$v['count'];
                                    }
                                    ?>
                                    <div class="d-flex flex-column gap-3">
                                        <?php foreach ($q['options'] as $idx => $opt): ?>
                                            <?php
                                            $char = chr(65 + $idx);
                                            $count = $votesLookup[$char] ?? 0;
                                            $pct = $totalQVotes > 0 ? round(($count / $totalQVotes) * 100) : 0;
                                            ?>
                                            <div class="bar-row">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span class="<?= ($q['correct_answer'] === $char) ? 'text-success fw-bold' : '' ?>">
                                                        <strong><?= $char ?>)</strong> <?= htmlspecialchars($opt) ?>
                                                        <?php if ($q['correct_answer'] === $char): ?> <?= htmlspecialchars(t('admin.report.correct_label')) ?><?php endif; ?>
                                                    </span>
                                                    <span class="text-muted fw-semibold"><?= t('admin.report.responses_count', ['count' => $count, 'pct' => $pct]) ?></span>
                                                </div>
                                                <div class="bar-container">
                                                    <div class="bar-fill <?= ($q['correct_answer'] === $char) ? 'bar-fill-green' : 'bar-fill-blue' ?>" style="width: <?= $pct ?>%;"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Side: Open-Ended answers & Participant details -->
            <div class="col-12 col-lg-4">
                <div class="card-custom mb-4" id="participant-list-card">
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars(t('admin.report.participants_log')) ?></h4>
                    <p class="text-muted small mb-0"><?= $totalParticipants ?> <?= htmlspecialchars(t('admin.report.participant_prefix')) ?></p>
                </div>

                <div class="card-custom no-print">
                    <h4 class="fw-bold mb-3"><?= htmlspecialchars(t('admin.report.security_title')) ?></h4>
                    <p class="text-muted small mb-0"><?= htmlspecialchars(t('admin.report.security_desc')) ?></p>
                </div>
            </div>
        </div>

        <!-- Footer Actions Panel -->
        <div class="d-flex justify-content-between align-items-center mt-5 p-3 rounded-4 bg-white border no-print" style="background-color: var(--card-bg) !important; border: 1px solid var(--border-color) !important;">
            <div class="d-flex gap-2">
                <a href="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/report/csv') ?>" class="btn-action text-decoration-none"><?= htmlspecialchars(t('admin.report.export_csv')) ?></a>
                <button onclick="window.print()" class="btn-action"><?= htmlspecialchars(t('admin.report.export_pdf')) ?></button>
            </div>
        </div>
    </div>

    <script>
        // JS translations
        const translationQBank = <?= json_encode(t('admin.report.sidebar_qbank')) ?>;
        const translationArchive = <?= json_encode(t('admin.report.sidebar_archive')) ?>;
        const translationSettings = <?= json_encode(t('admin.report.sidebar_settings')) ?>;
        const translationEnterpriseText = <?= json_encode(t('admin.report.enterprise_text')) ?>;
        const translationParticipantPrefix = <?= json_encode(t('admin.report.participant_prefix')) ?>;

        function showDemoToast(moduleName) {
            const toast = document.getElementById('demo-toast');
            document.getElementById('toast-title').textContent = moduleName;
            toast.style.display = 'block';
            toast.style.opacity = '1';
            
            // Auto hide after 3 seconds
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 300);
            }, 3000);
        }
    </script>

    <!-- Demo Alert Toast -->
    <div id="demo-toast" class="no-print" style="position: fixed; bottom: 24px; right: 24px; z-index: 9999; background: rgba(15, 23, 42, 0.95); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 1rem 1.5rem; border-radius: 12px; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); display: none; box-shadow: 0 10px 30px rgba(0,0,0,0.4); transition: opacity 0.3s ease;">
        <div class="d-flex align-items-center gap-3">
            <span style="font-size: 1.5rem;">🛡️</span>
            <div>
                <strong id="toast-title" class="d-block text-white" style="font-size: 0.9rem; font-weight: 700;">Modül</strong>
                <span class="text-muted small" style="font-size: 0.8rem; font-weight: 500;"><?= htmlspecialchars(t('admin.report.enterprise_text')) ?></span>
            </div>
        </div>
    </div>

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
            applyTheme(document.documentElement.getAttribute('data-theme') || 'light'); // reports default light

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
                    <button type="button" onclick="importQuestions()" class="btn btn-custom-primary px-4 py-2" style="background: var(--primary); color: white; border: none; border-radius: 10px;"><?= htmlspecialchars(t('admin.session.import')) ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS (needed for Modals on this page) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const sessionId = <?= json_encode($session['id']) ?>;
        const translationInvalidJson = <?= json_encode($locale === 'en' ? 'Invalid JSON format!' : 'Geçersiz JSON formatı!') ?>;

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
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
                    alert("Error: " + (data.error || "<?= t('admin.session.alert_submit_failed') ?>"));
                }
            } catch (e) {
                alert("<?= t('admin.session.alert_connection_error') ?>");
            } finally {
                const btn = document.querySelector("#importQuestionsModal .btn-custom-primary");
                if (btn) btn.disabled = false;
            }
        }
    </script>
</body>
</html>
