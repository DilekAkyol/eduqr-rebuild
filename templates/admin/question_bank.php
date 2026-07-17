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
    <title><?= htmlspecialchars(t('admin.qbank.title')) ?> - eduQR</title>
    <meta name="description" content="<?= htmlspecialchars(t('admin.qbank.desc')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Theme Fast-Init script to prevent white flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('eduqr_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <style>
        :root {
            --bg-color: #f0f4ff;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --card-bg: #ffffff;
            --card-border: rgba(99,102,241,0.10);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --input-color: #0f172a;
            --nav-active: #6366f1;
            --nav-active-bg: rgba(99,102,241,0.08);
            --shadow: 0 4px 24px rgba(99,102,241,0.07);
            --accent: #6366f1;
            --accent2: #8b5cf6;
            --tag-bg: rgba(99,102,241,0.10);
            --tag-color: #6366f1;
            --badge-success: #10b981;
            --delete-color: #ef4444;
            --header-bg: #ffffff;
            --divider: rgba(0,0,0,0.06);
            --primary: #3b82f6;
        }
        [data-theme="dark"] {
            --bg-color: #0d1117;
            --sidebar-bg: #0b0f19;
            --sidebar-hover: #111827;
            --card-bg: #1c2330;
            --card-border: rgba(99,102,241,0.15);
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            --input-bg: #0d1117;
            --input-border: #30363d;
            --input-color: #e2e8f0;
            --nav-active: #818cf8;
            --nav-active-bg: rgba(129,140,248,0.12);
            --shadow: 0 4px 24px rgba(0,0,0,0.35);
            --accent: #818cf8;
            --accent2: #a78bfa;
            --tag-bg: rgba(129,140,248,0.15);
            --tag-color: #818cf8;
            --badge-success: #34d399;
            --delete-color: #f87171;
            --header-bg: #161b22;
            --divider: rgba(255,255,255,0.07);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            transition: background 0.3s, color 0.3s;
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

        /* ── Main Content ───────────────────────────────────────── */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* ── Top Header ─────────────────────────────────────────── */
        .top-header {
            background: var(--header-bg);
            border-bottom: 1px solid var(--divider);
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            transition: background 0.3s;
        }
        .breadcrumb-nav { font-size: 0.85rem; color: var(--text-muted); }
        .breadcrumb-nav a { color: var(--accent); text-decoration: none; font-weight: 500; }
        .breadcrumb-nav span { margin: 0 6px; }

        .controls-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 50px;
            padding: 5px 12px;
        }
        .lang-btn {
            background: none; border: none; cursor: pointer;
            font-size: 0.75rem; font-weight: 700;
            color: var(--text-muted); padding: 4px 6px;
            border-radius: 6px; transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .lang-btn.active { background: var(--accent); color: white; }
        .lang-btn:hover:not(.active) { color: var(--text-main); }
        .divider-pill { width: 1px; height: 16px; background: var(--divider); }
        .theme-toggle-btn {
            background: none; border: none; cursor: pointer;
            font-size: 1rem; padding: 4px 6px; border-radius: 6px;
            transition: transform 0.2s; line-height: 1;
        }
        .theme-toggle-btn:hover { transform: scale(1.2); }

        /* ── Page Content ────────────────────────────────────────── */
        .page-content {
            padding: 32px;
            flex: 1;
        }
        .page-title { font-size: 1.7rem; font-weight: 800; color: var(--text-main); margin-bottom: 6px; }
        .page-desc { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 28px; }

        /* ── Card ────────────────────────────────────────────────── */
        .qbank-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 28px;
            box-shadow: var(--shadow);
            height: 100%;
            transition: background 0.3s, border-color 0.3s;
        }
        .card-label {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        /* ── Form Elements ──────────────────────────────────────── */
        .form-input, .form-textarea, .form-select-el {
            width: 100%;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 10px;
            padding: 10px 14px;
            color: var(--input-color);
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-input::placeholder, .form-textarea::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.75 !important;
        }
        .form-select-el option {
            background-color: var(--card-bg) !important;
            color: var(--text-main) !important;
        }
        .form-input:focus, .form-textarea:focus, .form-select-el:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }
        .form-textarea { resize: vertical; min-height: 180px; line-height: 1.6; }

        /* ── Buttons ─────────────────────────────────────────────── */
        .btn-generate {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 11px 22px;
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(99,102,241,0.3);
        }
        .btn-generate:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99,102,241,0.4); }
        .btn-generate:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        .btn-copy {
            background: #10b981;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 9px 18px;
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-copy:hover { background: #059669; }
        .btn-copy:disabled { opacity: 0.5; cursor: not-allowed; }

        .btn-manual {
            background: var(--tag-bg);
            color: var(--accent);
            border: 1.5px solid var(--card-border);
            border-radius: 10px;
            padding: 9px 16px;
            font-family: 'Inter', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-manual:hover { background: var(--nav-active-bg); }

        .btn-select-all {
            background: none;
            border: 1.5px solid var(--input-border);
            border-radius: 8px;
            padding: 5px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }
        .btn-select-all:hover { border-color: var(--accent); color: var(--accent); }

        /* ── Count Pill ─────────────────────────────────────────── */
        .count-input {
            width: 70px;
            text-align: center;
            border-radius: 8px;
        }

        /* ── Bank Question Item ──────────────────────────────────── */
        .bank-question-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1.5px solid var(--card-border);
            background: var(--input-bg);
            margin-bottom: 10px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .bank-question-item:hover { border-color: var(--accent); background: var(--nav-active-bg); }
        .bank-question-item.selected {
            border-color: var(--accent);
            background: var(--nav-active-bg);
        }
        .bank-question-item .q-checkbox {
            width: 18px; height: 18px;
            accent-color: var(--accent);
            cursor: pointer;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .bank-question-item .q-body { flex: 1; min-width: 0; }
        .bank-question-item .q-text {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-main);
            line-height: 1.5;
            margin-bottom: 6px;
        }
        .bank-question-item .q-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .q-tag {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 50px;
            background: var(--tag-bg);
            color: var(--tag-color);
        }
        .q-correct {
            font-size: 0.7rem;
            color: var(--badge-success);
            font-weight: 600;
        }
        .bank-question-item .q-delete {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 0.85rem;
            padding: 4px;
            border-radius: 6px;
            transition: all 0.2s;
            flex-shrink: 0;
            line-height: 1;
        }
        .bank-question-item .q-delete:hover { color: var(--delete-color); background: rgba(239,68,68,0.08); }

        .bank-question-item .q-edit {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 0.85rem;
            padding: 4px;
            border-radius: 6px;
            transition: all 0.2s;
            flex-shrink: 0;
            line-height: 1;
            margin-right: 2px;
        }
        .bank-question-item .q-edit:hover { color: var(--accent); background: rgba(99,102,241,0.08); }

        /* ── Empty State ─────────────────────────────────────────── */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
        }
        .empty-state .empty-icon { font-size: 3rem; margin-bottom: 14px; opacity: 0.5; }
        .empty-state p { font-size: 0.9rem; }

        /* ── Toast ───────────────────────────────────────────────── */
        .toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; }
        .toast-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #1e293b;
            color: white;
            border-radius: 14px;
            padding: 14px 20px;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            animation: slideUp 0.3s ease;
            max-width: 360px;
        }
        [data-theme="light"] .toast-pill { background: #1e293b; }
        .toast-pill.success .toast-icon { color: #34d399; font-size: 1.1rem; }
        .toast-pill.error .toast-icon { color: #f87171; font-size: 1.1rem; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Spinner ─────────────────────────────────────────────── */
        .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: inline-block;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Copy Modal ─────────────────────────────────────────── */
        .copy-modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000;
            opacity: 0; pointer-events: none;
            transition: opacity 0.2s;
        }
        .copy-modal-overlay.open { opacity: 1; pointer-events: all; }
        .copy-modal {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 32px;
            max-width: 440px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            transform: scale(0.95);
            transition: transform 0.2s;
        }
        .copy-modal-overlay.open .copy-modal { transform: scale(1); }
        .copy-modal h4 { font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; }
        .copy-modal p { font-size: 0.875rem; color: var(--text-muted); margin-bottom: 20px; }

        /* ── Manual Modal ────────────────────────────────────────── */
        .manual-modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex; align-items: center; justify-content: center;
            z-index: 1000;
            opacity: 0; pointer-events: none;
            transition: opacity 0.2s;
        }
        .manual-modal-overlay.open { opacity: 1; pointer-events: all; }
        .manual-modal {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 32px;
            max-width: 480px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            transform: scale(0.95);
            transition: transform 0.2s;
            max-height: 90vh;
            overflow-y: auto;
        }
        .manual-modal-overlay.open .manual-modal { transform: scale(1); }

        .btn-modal-close {
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }
        .btn-modal-close:hover { color: var(--text-main); }

        /* ── API Key Notice ──────────────────────────────────────── */
        .api-key-notice {
            background: rgba(251,191,36,0.08);
            border: 1.5px solid rgba(251,191,36,0.3);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.8rem;
            color: #d97706;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
        }
        [data-theme="dark"] .api-key-notice { color: #fbbf24; }

        /* ── Scrollable Bank List ────────────────────────────────── */
        .bank-list-scroll {
            max-height: 520px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .bank-list-scroll::-webkit-scrollbar { width: 4px; }
        .bank-list-scroll::-webkit-scrollbar-track { background: transparent; }
        .bank-list-scroll::-webkit-scrollbar-thumb { background: var(--input-border); border-radius: 4px; }

        /* ── Responsive ─────────────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .page-content { padding: 20px 16px; }
            .top-header { padding: 12px 16px; }
        }

        .text-muted {
            color: var(--text-muted) !important;
        }
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
        <a href="<?= $recentSessionId ? eduqr_path('/admin/sessions/' . $recentSessionId . '/report') : '#' ?>" class="nav-item-custom<?= !$recentSessionId ? ' disabled' : '' ?>"><?= htmlspecialchars(t('admin.report.sidebar_reports')) ?></a>
        <a href="<?= eduqr_path('/admin/dashboard') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_courses')) ?></a>
        <a href="<?= eduqr_path('/admin/question-bank') ?>" class="nav-item-custom active"><?= htmlspecialchars(t('admin.report.sidebar_qbank')) ?></a>
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

<!-- ══ MAIN CONTENT ════════════════════════════════════════════ -->
<div class="main-content">
    <!-- Top Header -->
    <header class="top-header">
        <div class="breadcrumb-nav">
            <a href="<?= eduqr_path('/admin/dashboard') ?>"><?= htmlspecialchars(t('admin.dashboard.title')) ?></a>
            <span>›</span>
            <strong><?= htmlspecialchars(t('admin.qbank.title')) ?></strong>
        </div>
        <div class="controls-pill">
            <button class="lang-btn <?= $locale === 'tr' ? 'active' : '' ?>" id="btn-tr" onclick="switchLang('tr')">TR</button>
            <button class="lang-btn <?= $locale === 'en' ? 'active' : '' ?>" id="btn-en" onclick="switchLang('en')">EN</button>
            <div class="divider-pill"></div>
            <button class="theme-toggle-btn" id="theme-toggle" onclick="toggleTheme()" title="<?= htmlspecialchars(t('auth.login.theme_toggle')) ?>">🌙</button>
        </div>
    </header>

    <!-- Page Content -->
    <div class="page-content">
        <h1 class="page-title">❓ <?= htmlspecialchars(t('admin.qbank.title')) ?></h1>
        <p class="page-desc"><?= htmlspecialchars(t('admin.qbank.desc')) ?></p>

        <div class="row g-4">
            <!-- ── LEFT: Generate Panel ──────────────────────── -->
            <div class="col-12 col-lg-5">
                <div class="qbank-card">
                    <!-- API Key Warning -->
                    <?php if (!$hasApiKey): ?>
                    <div class="api-key-notice">
                        <span>⚠️</span>
                        <div>
                            <?= htmlspecialchars(t('admin.qbank.no_api_key')) ?>
                            <br><a href="https://aistudio.google.com" target="_blank" style="color:inherit; font-weight:700;">aistudio.google.com →</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Source Title -->
                    <div class="mb-4">
                        <div class="card-label"><?= htmlspecialchars(t('admin.qbank.source_title')) ?></div>
                        <input
                            type="text"
                            id="source-title"
                            class="form-input"
                            placeholder="<?= htmlspecialchars(t('admin.qbank.source_placeholder')) ?>"
                        >
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <div class="card-label mb-0"><?= htmlspecialchars(t('admin.qbank.notes_label')) ?></div>
                            <div>
                                <input type="file" id="file-upload" accept=".txt,.pdf" style="display:none;" onchange="handleFileUpload(event)">
                                <button type="button" class="btn btn-sm btn-outline-primary border-opacity-10 py-1 px-2 rounded-3" style="font-size: 0.75rem;" onclick="document.getElementById('file-upload').click()">
                                    <?= $locale === 'en' ? '📄 Upload File (.txt, .pdf)' : '📄 Dosyadan Yükle (.txt, .pdf)' ?>
                                </button>
                            </div>
                        </div>
                        <textarea
                            id="notes-area"
                            class="form-textarea"
                            placeholder="<?= htmlspecialchars(t('admin.qbank.notes_placeholder')) ?>"
                        ></textarea>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <div style="font-size:0.75rem; color:var(--text-muted);">
                                <?= htmlspecialchars(t('admin.qbank.notes_hint')) ?>
                            </div>
                            <div id="file-loading-indicator" style="display:none; font-size:0.75rem; color:var(--accent);">
                                <span class="spinner" style="width:12px; height:12px; border-width:1.5px; border-top-color:var(--accent); margin-right:4px;"></span>
                                <?= $locale === 'en' ? 'Reading file...' : 'Dosya okunuyor...' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Soru Tipi Selector -->
                    <div class="mb-4">
                        <div class="card-label"><?= $locale === 'en' ? 'Question Type' : 'Soru Tipi' ?></div>
                        <select class="form-select-el" id="question-type" style="background:var(--input-bg); color:var(--text-main); border:1px solid var(--input-border); border-radius:10px; width:100%; padding:0.6rem 0.8rem; font-size:0.875rem;">
                            <option value="multiple_choice" selected><?= $locale === 'en' ? 'Multiple Choice' : 'Çoktan Seçmeli' ?></option>
                            <option value="open_ended"><?= $locale === 'en' ? 'Open-Ended' : 'Açık Uçlu' ?></option>
                        </select>
                    </div>

                    <!-- Count Selector -->
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="card-label mb-0"><?= htmlspecialchars(t('admin.qbank.count_label')) ?>:</div>
                        <input type="number" id="question-count" class="form-input count-input" value="5" min="3" max="15">
                    </div>

                    <!-- Generate Button -->
                    <button class="btn-generate w-100" id="generate-btn" onclick="generateQuestions()"
                        <?= !$hasApiKey ? 'title="' . htmlspecialchars(t('admin.qbank.no_api_key')) . '"' : '' ?>>
                        <span id="generate-icon">✨</span>
                        <span id="generate-label"><?= htmlspecialchars(t('admin.qbank.generate_btn')) ?></span>
                    </button>

                    <div class="d-flex align-items-center gap-2 mt-3">
                        <div style="flex:1; height:1px; background:var(--divider)"></div>
                        <span style="font-size:0.75rem; color:var(--text-muted); white-space:nowrap;">veya</span>
                        <div style="flex:1; height:1px; background:var(--divider)"></div>
                    </div>

                    <!-- Manual Add and JSON Import Buttons Side-by-Side -->
                    <div class="row g-2 mt-3">
                        <div class="col-6">
                            <button class="btn-manual w-100 mt-0 py-2 px-1 text-center" style="font-size:0.85rem;" onclick="openManualModal()">
                                + <?= htmlspecialchars(t('admin.qbank.add_manual')) ?>
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn-manual w-100 mt-0 py-2 px-1 text-center" style="font-size:0.85rem; background: var(--bg-color); color: var(--text-main); border: 1.5px solid var(--input-border);" onclick="openImportModal()">
                                📥 <?= $locale === 'en' ? 'Import JSON' : 'JSON İçe Aktar' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── RIGHT: Question Bank ──────────────────────── -->
            <div class="col-12 col-lg-7">
                <div class="qbank-card d-flex flex-column" style="height:100%;">
                    <!-- Bank Header -->
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div>
                            <div style="font-size:1rem; font-weight:700; color:var(--text-main);">
                                <?= htmlspecialchars(t('admin.qbank.bank_title')) ?>
                                <span id="bank-count-badge" class="ms-2" style="font-size:0.8rem; background:var(--tag-bg); color:var(--tag-color); padding:2px 10px; border-radius:50px; font-weight:700;">
                                    <?= count($bankQuestions) ?>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn-select-all" onclick="toggleSelectAll()" id="select-all-btn">
                                <?= htmlspecialchars(t('admin.qbank.select_all')) ?>
                            </button>
                            <button class="btn-copy" id="copy-btn" onclick="openCopyModal()">
                                ➕ <?= htmlspecialchars(t('admin.qbank.copy_to_session')) ?>
                            </button>
                        </div>
                    </div>

                    <!-- Questions List -->
                    <div class="bank-list-scroll flex-grow-1" id="bank-list">
                        <?php if (empty($bankQuestions)): ?>
                        <div class="empty-state" id="empty-state">
                            <div class="empty-icon">📭</div>
                            <p><?= htmlspecialchars(t('admin.qbank.empty')) ?></p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($bankQuestions as $idx => $q): ?>
                            <div class="bank-question-item" id="qitem-<?= (int)$q['id'] ?>" onclick="toggleQuestion(<?= (int)$q['id'] ?>, event)">
                                <input type="checkbox" class="q-checkbox" id="qcheck-<?= (int)$q['id'] ?>"
                                    data-id="<?= (int)$q['id'] ?>" onchange="onCheckChange(this)" onclick="event.stopPropagation()">
                                <div class="q-body">
                                    <div class="q-text"><?= htmlspecialchars($q['question_text']) ?></div>
                                    <div class="q-meta">
                                        <?php if (!empty($q['source_title'])): ?>
                                            <span class="q-tag">📖 <?= htmlspecialchars($q['source_title']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($q['options']) && is_array($q['options'])): ?>
                                            <span class="q-tag"><?= count($q['options']) ?> şık</span>
                                        <?php endif; ?>
                                        <?php if (isset($q['type']) && $q['type'] === 'open_ended'): ?>
                                            <span class="q-tag" style="background:rgba(139, 92, 246, 0.1); color:#8b5cf6; font-weight: 600;"><?= $locale === 'tr' ? 'Açık Uçlu' : 'Open-Ended' ?></span>
                                        <?php elseif (isset($q['type']) && $q['type'] === 'yes_no'): ?>
                                            <span class="q-tag" style="background:rgba(16, 185, 129, 0.1); color:#10b981; font-weight: 600;"><?= $locale === 'tr' ? 'Evet/Hayır' : 'Yes/No' ?></span>
                                        <?php elseif (isset($q['type']) && $q['type'] === 'likert'): ?>
                                            <span class="q-tag" style="background:rgba(59, 130, 246, 0.1); color:#3b82f6; font-weight: 600;"><?= $locale === 'tr' ? 'Likert Ölçeği' : 'Likert Scale' ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($q['correct_answer'])): ?>
                                            <span class="q-correct">✓ <?= htmlspecialchars($q['correct_answer']) ?></span>
                                        <?php endif; ?>
                                        <span class="q-tag" style="background:transparent; color:var(--text-muted); font-weight:400;">
                                            <?= date('d.m.Y', strtotime($q['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1 no-print">
                                    <button class="q-edit" onclick="openEditModal(<?= (int)$q['id'] ?>, event)" title="Düzenle">✏️</button>
                                    <button class="q-delete" onclick="deleteQuestion(<?= (int)$q['id'] ?>, event)" title="Sil">🗑</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Selected Count -->
                    <div class="mt-3 pt-3" style="border-top:1px solid var(--divider); font-size:0.8rem; color:var(--text-muted);">
                        <span id="selected-count">0</span> soru seçildi
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ COPY TO SESSION MODAL ════════════════════════════════════ -->
<div class="copy-modal-overlay" id="copy-modal-overlay" onclick="closeCopyModal(event)">
    <div class="copy-modal">
        <h4>➕ <?= htmlspecialchars(t('admin.qbank.copy_to_session')) ?></h4>
        <p><?= $locale === 'tr' ? 'Seçili soruları hangi oturuma kopyalamak istersiniz?' : 'Which session would you like to copy the selected questions to?' ?></p>

        <?php if (empty($sessions)): ?>
            <div class="api-key-notice">
                <span>⚠️</span> <?= htmlspecialchars(t('admin.qbank.no_active_sessions')) ?>
            </div>
        <?php else: ?>
            <div class="mb-4">
                <div class="card-label"><?= htmlspecialchars(t('admin.qbank.select_session')) ?></div>
                <select class="form-select-el" id="session-select">
                    <option value="">— <?= htmlspecialchars(t('admin.qbank.select_session')) ?> —</option>
                    <?php foreach ($sessions as $s): ?>
                    <option value="<?= (int)$s['id'] ?>">
                        <?= htmlspecialchars(($locale === 'en' && !empty($s['course_name_en'])) ? $s['course_name_en'] : $s['course_name']) ?> › <?= htmlspecialchars($s['title']) ?>
                        (<?= htmlspecialchars($s['short_code']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-3 justify-content-end">
            <button class="btn-modal-close" onclick="closeCopyModalDirect()">
                <?= htmlspecialchars(t('admin.dashboard.cancel')) ?>
            </button>
            <?php if (!empty($sessions)): ?>
            <button class="btn-copy" id="confirm-copy-btn" onclick="confirmCopy()">
                ➕ <?= $locale === 'tr' ? 'Kopyala' : 'Copy' ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ MANUAL ADD MODAL ══════════════════════════════════════════ -->
<div class="manual-modal-overlay" id="manual-modal-overlay" onclick="closeManualModal(event)">
    <div class="manual-modal">
        <h4 class="mb-2" style="font-size:1.1rem; font-weight:700;">+ <?= htmlspecialchars(t('admin.qbank.add_manual')) ?></h4>
        <p class="mb-4" style="font-size:0.875rem; color:var(--text-muted);"><?= $locale === 'tr' ? 'Soruyu manuel olarak bankaya ekleyin.' : 'Add a question manually to the bank.' ?></p>

        <div class="mb-3">
            <div class="card-label"><?= $locale === 'tr' ? 'Soru Tipi' : 'Question Type' ?></div>
            <select id="manual-q-type" class="form-select-el" onchange="toggleManualQuestionFields()" style="background:var(--input-bg); color:var(--text-main); border:1px solid var(--input-border); border-radius:10px; width:100%; padding:0.6rem 0.8rem; font-size:0.875rem;">
                <option value="multiple_choice" selected><?= $locale === 'tr' ? 'Çoktan Seçmeli' : 'Multiple Choice' ?></option>
                <option value="open_ended"><?= $locale === 'tr' ? 'Açık Uçlu' : 'Open-Ended' ?></option>
                <option value="yes_no"><?= $locale === 'tr' ? 'Evet / Hayır' : 'Yes / No' ?></option>
                <option value="likert"><?= $locale === 'tr' ? 'Likert Ölçeği (5\'li)' : 'Likert Scale (5-point)' ?></option>
            </select>
        </div>
        <div class="mb-3">
            <div class="card-label"><?= htmlspecialchars(t('admin.qbank.manual_question')) ?></div>
            <textarea id="manual-q-text" class="form-textarea" style="min-height:80px;"
                placeholder="<?= htmlspecialchars(t('admin.qbank.source_placeholder')) ?>..."></textarea>
        </div>
        <div id="manual-mc-fields">
            <div class="mb-3" id="manual-options-container">
                <div class="card-label"><?= htmlspecialchars(t('admin.qbank.manual_options')) ?></div>
                <textarea id="manual-q-options" class="form-textarea" style="min-height:90px;"
                    placeholder="<?= $locale === 'tr' ? "A) Seçenek 1\nB) Seçenek 2\nC) Seçenek 3\nD) Seçenek 4" : "A) Option 1\nB) Option 2\nC) Option 3\nD) Option 4" ?>"></textarea>
            </div>
            <div class="mb-4" id="manual-correct-container">
                <div class="card-label"><?= htmlspecialchars(t('admin.qbank.manual_correct')) ?></div>
                <input type="text" id="manual-q-correct" class="form-input"
                    placeholder="<?= $locale === 'tr' ? 'Örn: A' : 'e.g., A' ?>">
            </div>
        </div>

        <div class="d-flex gap-3 justify-content-end">
            <button class="btn-modal-close" onclick="closeManualModalDirect()">
                <?= htmlspecialchars(t('admin.dashboard.cancel')) ?>
            </button>
            <button class="btn-generate" onclick="submitManual()" style="box-shadow:none; padding:9px 20px;">
                ✚ <?= htmlspecialchars(t('admin.qbank.manual_add_btn')) ?>
            </button>
        </div>
    </div>
</div>

<!-- ══ EDIT QUESTION MODAL ══════════════════════════════════════ -->
<div class="manual-modal-overlay" id="edit-modal-overlay" onclick="closeEditModal(event)">
    <div class="manual-modal">
        <h4 class="mb-2" style="font-size:1.1rem; font-weight:700;">✏️ <?= $locale === 'tr' ? 'Soruyu Düzenle' : 'Edit Question' ?></h4>
        <p class="mb-4" style="font-size:0.875rem; color:var(--text-muted);"><?= $locale === 'tr' ? 'Soru bankasındaki soruyu güncelleyin.' : 'Update the question in the question bank.' ?></p>

        <input type="hidden" id="edit-q-id">

        <div class="mb-3">
            <div class="card-label"><?= $locale === 'tr' ? 'Soru Tipi' : 'Question Type' ?></div>
            <select id="edit-q-type" class="form-select-el" onchange="toggleEditQuestionFields()" style="background:var(--input-bg); color:var(--text-main); border:1px solid var(--input-border); border-radius:10px; width:100%; padding:0.6rem 0.8rem; font-size:0.875rem;">
                <option value="multiple_choice"><?= $locale === 'tr' ? 'Çoktan Seçmeli' : 'Multiple Choice' ?></option>
                <option value="open_ended"><?= $locale === 'tr' ? 'Açık Uçlu' : 'Open-Ended' ?></option>
                <option value="yes_no"><?= $locale === 'tr' ? 'Evet / Hayır' : 'Yes / No' ?></option>
                <option value="likert"><?= $locale === 'tr' ? 'Likert Ölçeği (5\'li)' : 'Likert Scale (5-point)' ?></option>
            </select>
        </div>
        <div class="mb-3">
            <div class="card-label"><?= htmlspecialchars(t('admin.qbank.manual_question')) ?></div>
            <textarea id="edit-q-text" class="form-textarea" style="min-height:80px;" placeholder="..."></textarea>
        </div>
        <div id="edit-mc-fields">
            <div class="mb-3" id="edit-options-container">
                <div class="card-label"><?= htmlspecialchars(t('admin.qbank.manual_options')) ?></div>
                <textarea id="edit-q-options" class="form-textarea" style="min-height:90px;"
                    placeholder="<?= $locale === 'tr' ? "Her satıra bir seçenek yazın" : "Write one option per line" ?>"></textarea>
            </div>
            <div class="mb-3" id="edit-correct-container">
                <div class="card-label"><?= htmlspecialchars(t('admin.qbank.manual_correct')) ?></div>
                <input type="text" id="edit-q-correct" class="form-input" placeholder="e.g., A">
            </div>
        </div>
        <div class="mb-4">
            <div class="card-label"><?= $locale === 'tr' ? 'Kaynak Etiketi (Opsiyonel)' : 'Source Tag (Optional)' ?></div>
            <input type="text" id="edit-q-source" class="form-input" placeholder="e.g., Java">
        </div>

        <div class="d-flex gap-3 justify-content-end">
            <button class="btn-modal-close" onclick="closeEditModalDirect()">
                <?= htmlspecialchars(t('admin.dashboard.cancel')) ?>
            </button>
            <button class="btn-generate" onclick="submitEdit()" style="box-shadow:none; padding:9px 20px;">
                💾 <?= $locale === 'tr' ? 'Kaydet' : 'Save' ?>
            </button>
        </div>
    </div>
</div>

<!-- ══ TOAST ═══════════════════════════════════════════════════ -->
<div class="toast-container" id="toast-container"></div>

<script>
    // ── Locale Data ─────────────────────────────────────────────
    const t_generating      = <?= json_encode(t('admin.qbank.generating')) ?>;
    const t_generateBtn     = <?= json_encode(t('admin.qbank.generate_btn')) ?>;
    const t_generatedCount  = <?= json_encode(t('admin.qbank.generated_count')) ?>;
    const t_copiedSuccess   = <?= json_encode(t('admin.qbank.copied_success')) ?>;
    const t_selectFirst     = <?= json_encode(t('admin.qbank.select_first')) ?>;
    const t_selectSession   = <?= json_encode(t('admin.qbank.select_session_first')) ?>;
    const t_deleteConfirm   = <?= json_encode(t('admin.qbank.delete_confirm')) ?>;
    const t_noApiKey        = <?= json_encode(t('admin.qbank.no_api_key')) ?>;
    const t_selectAll       = <?= json_encode(t('admin.qbank.select_all')) ?>;
    const t_deselectAll     = <?= json_encode(t('admin.qbank.deselect_all')) ?>;
    const t_participantPrefix = <?= json_encode(t('admin.report.participant_prefix')) ?>;
    const hasApiKey         = <?= json_encode($hasApiKey) ?>;

    const generateUrl       = <?= json_encode(eduqr_path('/admin/question-bank/generate')) ?>;
    const copyUrl           = <?= json_encode(eduqr_path('/admin/question-bank/copy-to-session')) ?>;
    const deleteBaseUrl     = <?= json_encode(eduqr_path('/admin/question-bank/')) ?>;
    const manualUrl         = <?= json_encode(eduqr_path('/admin/question-bank/add-manual')) ?>;

    const locale            = <?= json_encode($locale) ?>;

    // ── State ────────────────────────────────────────────────────
    let selectedIds = new Set();
    let allQuestions = [];

    // Init existing from PHP
    document.querySelectorAll('.q-checkbox').forEach(cb => {
        allQuestions.push(parseInt(cb.dataset.id));
    });

    // ── Theme ────────────────────────────────────────────────────
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.getElementById('theme-toggle').textContent = theme === 'dark' ? '☀️' : '🌙';
    }
    function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        localStorage.setItem('eduqr_theme', next);
        applyTheme(next);
    }
    applyTheme(localStorage.getItem('eduqr_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

    // ── Language Switch ──────────────────────────────────────────
    function switchLang(lang) {
        document.cookie = `eduqr_locale=${lang}; path=/; max-age=31536000`;
        location.reload();
    }

    // ── Toast ────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
        const c = document.getElementById('toast-container');
        const toast = document.createElement('div');
        const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
        toast.className = `toast-pill ${type}`;
        toast.innerHTML = `<span class="toast-icon">${icon}</span><span>${msg}</span>`;
        c.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 3500);
    }

    // ── Select / Deselect ────────────────────────────────────────
    function toggleQuestion(id, event) {
        if (event.target.classList.contains('q-delete') || event.target.tagName === 'BUTTON') return;
        const cb = document.getElementById('qcheck-' + id);
        if (!cb) return;
        cb.checked = !cb.checked;
        onCheckChange(cb);
    }

    function onCheckChange(cb) {
        const id = parseInt(cb.dataset.id);
        const item = document.getElementById('qitem-' + id);
        if (cb.checked) { selectedIds.add(id); item.classList.add('selected'); }
        else { selectedIds.delete(id); item.classList.remove('selected'); }
        updateSelectedCount();
    }

    function updateSelectedCount() {
        document.getElementById('selected-count').textContent = selectedIds.size;
    }

    let allSelected = false;
    function toggleSelectAll() {
        allSelected = !allSelected;
        document.querySelectorAll('.q-checkbox').forEach(cb => {
            cb.checked = allSelected;
            onCheckChange(cb);
        });
        document.getElementById('select-all-btn').textContent = allSelected ? t_deselectAll : t_selectAll;
    }

    // ── Generate Questions ───────────────────────────────────────
    async function generateQuestions() {
        if (!hasApiKey) { showToast(t_noApiKey, 'error'); return; }

        const notes = document.getElementById('notes-area').value.trim();
        if (!notes) { showToast(locale === 'tr' ? 'Lütfen ders notlarını girin.' : 'Please enter course notes.', 'error'); return; }

        const sourceTitle = document.getElementById('source-title').value.trim();
        const count = parseInt(document.getElementById('question-count').value) || 5;
        const type = document.getElementById('question-type').value;

        const btn = document.getElementById('generate-btn');
        const icon = document.getElementById('generate-icon');
        const label = document.getElementById('generate-label');

        btn.disabled = true;
        icon.innerHTML = '<span class="spinner"></span>';
        label.textContent = t_generating;

        try {
            const res = await fetch(generateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notes, source_title: sourceTitle, count, type })
            });
            const data = await res.json();

            if (data.success && data.questions) {
                data.questions.forEach(q => appendQuestionToList(q));
                updateBankCount(document.querySelectorAll('.bank-question-item').length);
                showToast(t_generatedCount.replace('{count}', data.count), 'success');
                document.getElementById('notes-area').value = '';
            } else {
                showToast(data.error || 'Bilinmeyen hata', 'error');
            }
        } catch (e) {
            showToast('Bağlantı hatası: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            icon.textContent = '✨';
            label.textContent = t_generateBtn;
        }
    }

    function appendQuestionToList(q) {
        const empty = document.getElementById('empty-state');
        if (empty) empty.remove();

        const list = document.getElementById('bank-list');
        const div = document.createElement('div');
        div.className = 'bank-question-item';
        div.id = 'qitem-' + q.id;
        div.onclick = (e) => toggleQuestion(q.id, e);

        const optCount = (q.options && Array.isArray(q.options)) ? q.options.length : 0;
        const sourceTag = q.source_title ? `<span class="q-tag">📖 ${escapeHtml(q.source_title)}</span>` : '';
        const optTag   = optCount > 0 ? `<span class="q-tag">${optCount} şık</span>` : '';
        const typeTag  = q.type === 'open_ended' ? `<span class="q-tag" style="background:rgba(139, 92, 246, 0.1); color:#8b5cf6; font-weight: 600;">${locale === 'tr' ? 'Açık Uçlu' : 'Open-Ended'}</span>` : '';
        const correctTag = q.correct_answer ? `<span class="q-correct">✓ ${escapeHtml(q.correct_answer)}</span>` : '';
        const dateTag = `<span class="q-tag" style="background:transparent;color:var(--text-muted);font-weight:400;">${new Date().toLocaleDateString('tr-TR')}</span>`;

        div.innerHTML = `
            <input type="checkbox" class="q-checkbox" id="qcheck-${q.id}" data-id="${q.id}" onchange="onCheckChange(this)" onclick="event.stopPropagation()">
            <div class="q-body">
                <div class="q-text">${escapeHtml(q.question_text)}</div>
                <div class="q-meta">${sourceTag}${optTag}${typeTag}${correctTag}${dateTag}</div>
            </div>
            <button class="q-delete" onclick="deleteQuestion(${q.id}, event)" title="Sil">🗑</button>
        `;
        list.appendChild(div);
        allQuestions.push(q.id);
    }

    function updateBankCount(n) {
        const badge = document.getElementById('bank-count-badge');
        if (badge) badge.textContent = n;
    }

    // ── Delete Question ──────────────────────────────────────────
    async function deleteQuestion(id, event) {
        event.stopPropagation();
        if (!confirm(t_deleteConfirm)) return;

        try {
            const res = await fetch(deleteBaseUrl + id + '/delete', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                document.getElementById('qitem-' + id)?.remove();
                selectedIds.delete(id);
                allQuestions = allQuestions.filter(x => x !== id);
                updateSelectedCount();
                updateBankCount(document.querySelectorAll('.bank-question-item').length);
                if (document.querySelectorAll('.bank-question-item').length === 0) {
                    const list = document.getElementById('bank-list');
                    list.innerHTML = `<div class="empty-state" id="empty-state">
                        <div class="empty-icon">📭</div>
                        <p>${locale === 'tr' ? 'Henüz banka sorusu yok.' : 'No bank questions yet.'}</p>
                    </div>`;
                }
                showToast(locale === 'tr' ? 'Soru silindi.' : 'Question deleted.', 'success');
            } else {
                showToast(locale === 'tr' ? 'Silme işlemi başarısız.' : 'Delete failed.', 'error');
            }
        } catch (e) {
            showToast('Hata: ' + e.message, 'error');
        }
    }

    // ── Copy Modal ───────────────────────────────────────────────
    function openCopyModal() {
        if (selectedIds.size === 0) { showToast(t_selectFirst, 'error'); return; }
        document.getElementById('copy-modal-overlay').classList.add('open');
    }
    function closeCopyModal(event) {
        if (event.target === document.getElementById('copy-modal-overlay')) closeCopyModalDirect();
    }
    function closeCopyModalDirect() {
        document.getElementById('copy-modal-overlay').classList.remove('open');
    }

    async function confirmCopy() {
        const sessionId = parseInt(document.getElementById('session-select')?.value || '0');
        if (!sessionId) { showToast(t_selectSession, 'error'); return; }
        if (selectedIds.size === 0) { showToast(t_selectFirst, 'error'); return; }

        const btn = document.getElementById('confirm-copy-btn');
        btn.disabled = true;

        try {
            const res = await fetch(copyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: [...selectedIds], session_id: sessionId })
            });
            const data = await res.json();
            if (data.success) {
                showToast(t_copiedSuccess.replace('{count}', data.count), 'success');
                closeCopyModalDirect();
                // Deselect all
                selectedIds.clear();
                document.querySelectorAll('.q-checkbox').forEach(cb => {
                    cb.checked = false;
                    document.getElementById('qitem-' + cb.dataset.id)?.classList.remove('selected');
                });
                updateSelectedCount();
            } else {
                showToast(data.error || 'Kopyalama başarısız.', 'error');
            }
        } catch(e) {
            showToast('Hata: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
        }
    }

    // ── Manual Modal ─────────────────────────────────────────────
    function openManualModal() {
        document.getElementById('manual-modal-overlay').classList.add('open');
    }
    function closeManualModal(event) {
        if (event.target === document.getElementById('manual-modal-overlay')) closeManualModalDirect();
    }
    function closeManualModalDirect() {
        document.getElementById('manual-modal-overlay').classList.remove('open');
    }

    function toggleManualQuestionFields() {
        const type = document.getElementById('manual-q-type').value;
        const optCont = document.getElementById('manual-options-container');
        const corrCont = document.getElementById('manual-correct-container');

        if (type === 'multiple_choice') {
            optCont.style.display = 'block';
            corrCont.style.display = 'block';
            document.getElementById('manual-q-correct').placeholder = 'e.g., A';
        } else if (type === 'yes_no') {
            optCont.style.display = 'none';
            corrCont.style.display = 'block';
            document.getElementById('manual-q-correct').placeholder = 'e.g., A or B';
        } else { // open_ended, likert
            optCont.style.display = 'none';
            corrCont.style.display = 'none';
        }
    }

    async function submitManual() {
        const text = document.getElementById('manual-q-text').value.trim();
        if (!text) { showToast(locale === 'tr' ? 'Soru metni boş olamaz.' : 'Question text cannot be empty.', 'error'); return; }

        const type = document.getElementById('manual-q-type').value;
        const rawOptions = type === 'multiple_choice' ? document.getElementById('manual-q-options').value.trim() : '';
        const options = (type === 'multiple_choice' && rawOptions) ? rawOptions.split('\n').map(o => o.trim()).filter(o => o !== '') : null;
        const correct = (type === 'multiple_choice' || type === 'yes_no') ? (document.getElementById('manual-q-correct').value.trim() || null) : null;
        const sourceTitle = document.getElementById('source-title').value.trim();

        try {
            const res = await fetch(manualUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question_text: text, options, correct_answer: correct, source_title: sourceTitle, type })
            });
            const data = await res.json();
            if (data.success) {
                appendQuestionToList({ id: data.id, question_text: text, options, correct_answer: correct, source_title: sourceTitle, type });
                updateBankCount(document.querySelectorAll('.bank-question-item').length);
                showToast(locale === 'tr' ? 'Soru bankaya eklendi!' : 'Question added to bank!', 'success');
                closeManualModalDirect();
                document.getElementById('manual-q-text').value = '';
                document.getElementById('manual-q-options').value = '';
                document.getElementById('manual-q-correct').value = '';
            } else {
                showToast(data.error || 'Ekleme başarısız.', 'error');
            }
        } catch(e) {
            showToast('Hata: ' + e.message, 'error');
        }
    }

    // ── Edit Modal Actions ───────────────────────────────────────
    async function openEditModal(id, event) {
        if (event) event.stopPropagation();

        try {
            const res = await fetch(deleteBaseUrl + id);
            const data = await res.json();
            if (data.success && data.question) {
                const q = data.question;
                document.getElementById('edit-q-id').value = q.id;
                document.getElementById('edit-q-text').value = q.question_text;
                document.getElementById('edit-q-type').value = q.type;
                document.getElementById('edit-q-source').value = q.source_title || '';

                if (q.type === 'multiple_choice') {
                    document.getElementById('edit-q-options').value = Array.isArray(q.options) ? q.options.join('\n') : '';
                    document.getElementById('edit-q-correct').value = q.correct_answer || '';
                } else if (q.type === 'yes_no') {
                    document.getElementById('edit-q-options').value = '';
                    document.getElementById('edit-q-correct').value = q.correct_answer || '';
                } else {
                    document.getElementById('edit-q-options').value = '';
                    document.getElementById('edit-q-correct').value = '';
                }

                toggleEditQuestionFields();
                document.getElementById('edit-modal-overlay').classList.add('open');
            } else {
                showToast(data.error || 'Soru yüklenemedi.', 'error');
            }
        } catch (e) {
            showToast('Hata: ' + e.message, 'error');
        }
    }

    function closeEditModal(event) {
        if (event.target === document.getElementById('edit-modal-overlay')) closeEditModalDirect();
    }

    function closeEditModalDirect() {
        document.getElementById('edit-modal-overlay').classList.remove('open');
    }

    function toggleEditQuestionFields() {
        const type = document.getElementById('edit-q-type').value;
        const optCont = document.getElementById('edit-options-container');
        const corrCont = document.getElementById('edit-correct-container');

        if (type === 'multiple_choice') {
            optCont.style.display = 'block';
            corrCont.style.display = 'block';
            document.getElementById('edit-q-correct').placeholder = 'e.g., A';
        } else if (type === 'yes_no') {
            optCont.style.display = 'none';
            corrCont.style.display = 'block';
            document.getElementById('edit-q-correct').placeholder = 'e.g., A or B';
        } else { // open_ended, likert
            optCont.style.display = 'none';
            corrCont.style.display = 'none';
        }
    }

    async function submitEdit() {
        const id = document.getElementById('edit-q-id').value;
        const text = document.getElementById('edit-q-text').value.trim();
        if (!text) { showToast(locale === 'tr' ? 'Soru metni boş olamaz.' : 'Question text cannot be empty.', 'error'); return; }

        const type = document.getElementById('edit-q-type').value;
        const rawOptions = type === 'multiple_choice' ? document.getElementById('edit-q-options').value.trim() : '';
        const options = (type === 'multiple_choice' && rawOptions) ? rawOptions.split('\n').map(o => o.trim()).filter(o => o !== '') : null;
        const correct = (type === 'multiple_choice' || type === 'yes_no') ? (document.getElementById('edit-q-correct').value.trim() || null) : null;
        const sourceTitle = document.getElementById('edit-q-source').value.trim();

        try {
            const res = await fetch(deleteBaseUrl + id + '/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question_text: text, options, correct_answer: correct, source_title: sourceTitle, type })
            });
            const data = await res.json();
            if (data.success) {
                showToast(locale === 'tr' ? 'Soru güncellendi!' : 'Question updated!', 'success');
                closeEditModalDirect();
                location.reload();
            } else {
                showToast(data.error || 'Güncelleme başarısız.', 'error');
            }
        } catch(e) {
            showToast('Hata: ' + e.message, 'error');
        }
    }

    // ── Utility ──────────────────────────────────────────────────
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── File Upload Handler ─────────────────────────────────────
    async function handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        const indicator = document.getElementById('file-loading-indicator');
        const textarea = document.getElementById('notes-area');
        const ext = file.name.split('.').pop().toLowerCase();

        indicator.style.display = 'flex';

        try {
            if (ext === 'txt') {
                const text = await file.text();
                textarea.value = text;
                showToast(locale === 'tr' ? `"${file.name}" dosyası yüklendi!` : `"${file.name}" loaded!`, 'success');
            } else if (ext === 'pdf') {
                const arrayBuffer = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                let fullText = '';

                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const content = await page.getTextContent();
                    const pageText = content.items.map(item => item.str).join(' ');
                    fullText += pageText + '\n\n';
                }

                textarea.value = fullText.trim();
                showToast(locale === 'tr' ? `"${file.name}" (${pdf.numPages} sayfa) yüklendi!` : `"${file.name}" (${pdf.numPages} pages) loaded!`, 'success');
            } else {
                showToast(locale === 'tr' ? 'Desteklenmeyen dosya formatı. Lütfen .txt veya .pdf kullanın.' : 'Unsupported file format. Please use .txt or .pdf.', 'error');
            }
        } catch (err) {
            console.error('File read error:', err);
            showToast(locale === 'tr' ? 'Dosya okunamadı: ' + err.message : 'Failed to read file: ' + err.message, 'error');
        } finally {
            indicator.style.display = 'none';
            // Reset file input so same file can be re-selected
            event.target.value = '';
        }
    }

    // --- JSON Import Modal Actions ---
    function openImportModal() {
        document.getElementById('import-modal').classList.add('open');
        document.getElementById('modal-json-file').value = '';
        document.getElementById('import-source-title').value = document.getElementById('source-title').value || '';
        document.getElementById('modal-file-text').textContent = '<?= $locale === 'en' ? 'Click to select or drag & drop a .json file here' : '.json dosyasını seçmek için tıklayın veya buraya sürükleyin' ?>';
    }

    function closeImportModal() {
        document.getElementById('import-modal').classList.remove('open');
    }

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

    async function importBankQuestions() {
        const fileInput = document.getElementById('modal-json-file');
        const sourceTitleInput = document.getElementById('import-source-title');

        if (!fileInput.files || !fileInput.files[0]) {
            alert("<?= $locale === 'en' ? 'Please select a .json file.' : 'Lütfen bir .json dosyası seçin.' ?>");
            return;
        }

        const formData = new FormData();
        formData.append('json_file', fileInput.files[0]);
        formData.append('source_title', sourceTitleInput.value.trim());

        try {
            const res = await fetch(<?= json_encode(eduqr_path('/admin/question-bank/import-json')) ?>, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                const msg = "<?= $locale === 'en' ? 'Successfully imported ' : 'Başarıyla ' ?>" + data.count + 
                            "<?= $locale === 'en' ? ' questions.' : ' soru aktarıldı.' ?>" +
                            (data.skipped > 0 ? " (<?= $locale === 'en' ? 'Skipped: ' : 'Geçilen: ' ?>" + data.skipped + ")" : "");
                alert(msg);
                location.reload();
            } else {
                alert("Error: " + (data.error || "<?= t('admin.session.alert_submit_failed') ?>"));
            }
        } catch (e) {
            alert("<?= t('admin.session.alert_connection_error') ?>");
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Wire drag-drop zone inside import modal
        const modalDropZone = document.getElementById('modal-file-drop-zone');
        if (modalDropZone) {
            modalDropZone.addEventListener('click', () => {
                document.getElementById('modal-json-file').click();
            });
            modalDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                modalDropZone.style.borderColor = 'var(--primary)';
                modalDropZone.style.background = 'rgba(59, 130, 246, 0.05)';
            });
            modalDropZone.addEventListener('dragleave', () => {
                modalDropZone.style.borderColor = '';
                modalDropZone.style.background = '';
            });
            modalDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                modalDropZone.style.borderColor = '';
                modalDropZone.style.background = '';
                const fileInput = document.getElementById('modal-json-file');
                fileInput.files = e.dataTransfer.files;
                handleModalFileSelect(fileInput);
            });
        }
    });
</script>

<!-- JSON Import Modal Overlay -->
<div id="import-modal" class="manual-modal-overlay" onclick="if(event.target===this) closeImportModal()">
    <div class="manual-modal">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 style="font-weight:700; margin:0;"><?= $locale === 'en' ? 'Import from JSON' : 'JSON Dosyasından Aktar' ?></h4>
            <button class="btn-modal-close" style="font-size:1.5rem; line-height:1; padding:2px 8px; border:none; background:transparent;" onclick="closeImportModal()">×</button>
        </div>
        
        <!-- File Upload Option -->
        <div class="mb-3">
            <label class="form-label text-muted small fw-semibold"><?= $locale === 'en' ? 'Select JSON File' : 'JSON Dosyası Seçin' ?></label>
            <div class="file-drop-zone" id="modal-file-drop-zone" style="border: 2px dashed var(--input-border); border-radius:12px; padding:30px; text-align:center; cursor:pointer; background:var(--input-bg);">
                <input type="file" id="modal-json-file" accept=".json" style="display:none;" onchange="handleModalFileSelect(this)">
                <div class="file-drop-icon" style="font-size:2.5rem; margin-bottom:6px;">📄</div>
                <div class="file-drop-text" id="modal-file-text" style="font-size:0.85rem; color:var(--text-muted);"><?= $locale === 'en' ? 'Click to select or drag & drop a .json file here' : '.json dosyasını seçmek için tıklayın veya buraya sürükleyin' ?></div>
            </div>
        </div>

        <!-- Optional Source Tag -->
        <div class="mb-4">
            <label for="import-source-title" class="card-label text-muted small fw-semibold"><?= $locale === 'en' ? 'Source Tag (Optional)' : 'Kaynak Etiketi (Opsiyonel)' ?></label>
            <input type="text" id="import-source-title" class="form-input w-100" style="width:100%; border: 1.5px solid var(--input-border); border-radius:10px; padding:10px 14px; background:var(--input-bg); color:var(--text-main);" placeholder="e.g. Java, Python">
        </div>

        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn-modal-close" style="padding: 8px 18px;" onclick="closeImportModal()"><?= $locale === 'en' ? 'Cancel' : 'İptal' ?></button>
            <button type="button" onclick="importBankQuestions()" class="btn-generate" style="width:auto; padding:8px 24px; font-size:0.875rem; border-radius:10px; background: var(--accent); color: white; border: none; font-weight: 600; cursor: pointer;"><?= $locale === 'en' ? 'Import' : 'İçe Aktar' ?></button>
        </div>
    </div>
</div>

<!-- PDF.js for reading PDF files -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
</script>
</body>
</html>
