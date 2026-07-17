<?php
use EduQR\Services\AuthService;
use EduQR\Repositories\CourseRepository;

$user = AuthService::user();
$db = \EduQR\Support\Database::connect();
$stmt = $db->prepare("SELECT * FROM courses WHERE user_id = :user_id AND status = 'archived' ORDER BY created_at DESC");
$stmt->execute(['user_id' => $user['id']]);
$archivedCourses = $stmt->fetchAll() ?: [];
$locale = \EduQR\I18n\I18nService::getLocale();

// Fetch the user's most recent session ID across all their courses
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
    <title><?= htmlspecialchars(t('admin.report.sidebar_archive')) ?> - eduQR</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        (function() {
            const savedTheme = localStorage.getItem('eduqr_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <style>
        :root {
            /* Variables matching question bank */
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --card-border: rgba(0, 0, 0, 0.06);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --input-color: #0f172a;
            --accent: #4f46e5;
            --divider: rgba(0, 0, 0, 0.06);
            --header-bg: rgba(255, 255, 255, 0.85);
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --nav-active-bg: rgba(255, 255, 255, 0.08);
            --nav-active: #ffffff;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            --tag-bg: #f1f5f9;
            --tag-color: #475569;
            --primary: #3b82f6;
        }

        [data-theme="dark"] {
            --bg-color: #030712;
            --card-bg: rgba(17, 24, 39, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f9fafb;
            --text-muted: #94a3b8;
            --input-bg: rgba(255, 255, 255, 0.03);
            --input-border: rgba(255, 255, 255, 0.08);
            --input-color: #ffffff;
            --accent: #6366f1;
            --divider: rgba(255, 255, 255, 0.08);
            --header-bg: rgba(3, 7, 18, 0.6);
            --sidebar-bg: #0b0f19;
            --sidebar-hover: #111827;
            --nav-active-bg: rgba(255, 255, 255, 0.06);
            --nav-active: #ffffff;
            --shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            --tag-bg: rgba(255, 255, 255, 0.05);
            --tag-color: #cbd5e1;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            transition: background-color 0.3s ease, color 0.3s ease;
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

        .page-content {
            padding: 32px;
            flex: 1;
        }
        .page-title { font-size: 1.7rem; font-weight: 800; color: var(--text-main); margin-bottom: 6px; }
        .page-desc { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 28px; }

        /* ── Card ────────────────────────────────────────────────── */
        .archive-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 28px;
            box-shadow: var(--shadow);
            transition: background 0.3s, border-color 0.3s;
        }

        .archive-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-radius: 12px;
            border: 1.5px solid var(--card-border);
            background: var(--input-bg);
            margin-bottom: 12px;
            transition: all 0.2s;
        }
        .archive-item:hover { border-color: var(--accent); }
        .archive-item .item-title { font-weight: 600; color: var(--text-main); font-size: 1rem; }
        .archive-item .item-code { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; }
        
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
        }
        .empty-state .empty-icon { font-size: 3rem; margin-bottom: 14px; opacity: 0.5; }
        .empty-state p { font-size: 0.9rem; }

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
        <a href="<?= eduqr_path('/admin/question-bank') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_qbank')) ?></a>
        <a href="<?= $recentSessionId ? eduqr_path('/admin/sessions/' . $recentSessionId . '/report#participant-list-card') : '#' ?>" class="nav-item-custom<?= !$recentSessionId ? ' disabled' : '' ?>"><?= htmlspecialchars(t('admin.report.sidebar_participants')) ?></a>
        <a href="<?= $recentSessionId ? eduqr_path('/admin/sessions/' . $recentSessionId) : '#' ?>" class="nav-item-custom<?= !$recentSessionId ? ' disabled' : '' ?>"><?= htmlspecialchars(t('admin.report.live_session_nav')) ?></a>
        <a href="<?= eduqr_path('/admin/archive') ?>" class="nav-item-custom active"><?= htmlspecialchars(t('admin.report.sidebar_archive')) ?></a>
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
    <header class="top-header">
        <div class="breadcrumb-nav">
            <a href="<?= eduqr_path('/admin/dashboard') ?>"><?= htmlspecialchars(t('admin.dashboard.title')) ?></a>
            <span>›</span>
            <strong><?= htmlspecialchars(trim(mb_substr($archive, 1))) ?></strong>
        </div>
        <div class="controls-pill">
            <button class="lang-btn <?= $locale === 'tr' ? 'active' : '' ?>" id="btn-tr" onclick="switchLang('tr')">TR</button>
            <button class="lang-btn <?= $locale === 'en' ? 'active' : '' ?>" id="btn-en" onclick="switchLang('en')">EN</button>
            <div class="divider-pill"></div>
            <button class="theme-toggle-btn" id="theme-toggle" onclick="toggleTheme()">🌙</button>
        </div>
    </header>

    <div class="page-content">
        <h1 class="page-title"><?= htmlspecialchars($archive) ?></h1>
        <p class="page-desc"><?= $locale === 'en' ? 'View and restore archived courses.' : 'Arşivlenmiş derslerinizi görüntüleyin.' ?></p>

        <div class="archive-card">
            <?php if (empty($archivedCourses)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <p><?= $locale === 'en' ? 'No archived courses yet.' : 'Henüz arşivlenmiş ders yok.' ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($archivedCourses as $course): ?>
                    <div class="archive-item">
                        <div>
                            <div class="item-title"><?= htmlspecialchars(course_title($course)) ?></div>
                            <div class="item-code"><?= htmlspecialchars($course['code']) ?></div>
                        </div>
                        <button class="btn btn-sm btn-outline-primary rounded-3" onclick="restoreCourse(<?= $course['id'] ?>)">
                            <?= $locale === 'en' ? 'Restore' : 'Geri Yükle' ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
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

    function switchLang(lang) {
        document.cookie = `eduqr_locale=${lang}; path=/; max-age=31536000`;
        location.reload();
    }

    async function restoreCourse(id) {
        if (!confirm('<?= $locale === 'en' ? "Are you sure you want to restore this course?" : "Bu dersi geri yüklemek istediğinize emin misiniz?" ?>')) {
            return;
        }
        
        try {
            const res = await fetch(`<?= eduqr_path('/admin/courses/') ?>${id}/restore`, {
                method: 'POST'
            });
            const data = await res.json();
            
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Hata oluştu');
            }
        } catch (err) {
            alert('Bağlantı hatası: ' + err.message);
        }
    }
</script>
</body>
</html>
