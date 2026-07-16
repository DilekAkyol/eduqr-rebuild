<?php
use EduQR\Services\AuthService;
use EduQR\Repositories\UserRepository;

$sessionUser = AuthService::user();
$userRepo = new UserRepository();
$dbUser = $userRepo->findById((int)$sessionUser['id']);
$user = $dbUser ?: $sessionUser;

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
    <title><?= htmlspecialchars(t('admin.report.sidebar_settings')) ?> - eduQR</title>
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

        /* ── Card & Grid ────────────────────────────────────────── */
        .settings-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        @media (max-width: 991px) {
            .settings-grid { grid-template-columns: 1fr; }
        }

        .settings-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 18px;
            padding: 28px;
            box-shadow: var(--shadow);
            transition: background 0.3s, border-color 0.3s;
        }
        .card-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 20px; color: var(--text-main); }
        
        .form-label-custom {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
            display: block;
        }

        .form-input-custom {
            width: 100%;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 10px;
            padding: 10px 14px;
            color: var(--input-color);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .form-input-custom::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.75 !important;
        }
        .form-input-custom option, select option {
            background-color: var(--card-bg) !important;
            color: var(--text-main) !important;
        }
        .form-input-custom:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }

        .btn-save {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 11px 22px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(99,102,241,0.3); }
        .btn-save:disabled { opacity: 0.6; cursor: not-allowed; }

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
        .toast-pill.success .toast-icon { color: #34d399; font-size: 1.1rem; }
        .toast-pill.error .toast-icon { color: #f87171; font-size: 1.1rem; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
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
        <a href="<?= eduqr_path('/admin/question-bank') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_qbank')) ?></a>
        <a href="<?= $recentSessionId ? eduqr_path('/admin/sessions/' . $recentSessionId . '/report#participant-list-card') : '#' ?>" class="nav-item-custom<?= !$recentSessionId ? ' disabled' : '' ?>"><?= htmlspecialchars(t('admin.report.sidebar_participants')) ?></a>
        <a href="<?= $recentSessionId ? eduqr_path('/admin/sessions/' . $recentSessionId) : '#' ?>" class="nav-item-custom<?= !$recentSessionId ? ' disabled' : '' ?>"><?= htmlspecialchars(t('admin.report.live_session_nav')) ?></a>
        <a href="<?= eduqr_path('/admin/archive') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_archive')) ?></a>
        <a href="<?= eduqr_path('/admin/settings') ?>" class="nav-item-custom active"><?= htmlspecialchars(t('admin.report.sidebar_settings')) ?></a>
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
            <strong><?= htmlspecialchars(trim(mb_substr($settings, 1))) ?></strong>
        </div>
        <div class="controls-pill">
            <button class="lang-btn <?= $locale === 'tr' ? 'active' : '' ?>" id="btn-tr" onclick="switchLang('tr')">TR</button>
            <button class="lang-btn <?= $locale === 'en' ? 'active' : '' ?>" id="btn-en" onclick="switchLang('en')">EN</button>
            <div class="divider-pill"></div>
            <button class="theme-toggle-btn" id="theme-toggle" onclick="toggleTheme()">🌙</button>
        </div>
    </header>

    <div class="page-content">
        <h1 class="page-title"><?= htmlspecialchars($settings) ?></h1>
        <p class="page-desc"><?= $locale === 'en' ? 'Manage your account settings and profile.' : 'Hesap ayarlarınızı ve profilinizi yönetin.' ?></p>

        <div class="settings-grid">
            <!-- Profile Info Card -->
            <div class="settings-card">
                <h3 class="card-title"><?= $locale === 'en' ? 'Profile Details' : 'Profil Bilgileri' ?></h3>
                
                <form id="update-profile-form" onsubmit="handleUpdateProfile(event)">
                    <div class="mb-3">
                        <label for="profile_name" class="form-label-custom"><?= $locale === 'en' ? 'Full Name' : 'Ad Soyad' ?></label>
                        <input type="text" class="form-input-custom" id="profile_name" required value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="profile_email" class="form-label-custom"><?= $locale === 'en' ? 'Email Address' : 'E-posta Adresi' ?></label>
                        <input type="email" class="form-input-custom" id="profile_email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label-custom"><?= $locale === 'en' ? 'Role' : 'Rol' ?></label>
                        <input type="text" class="form-input-custom" value="<?= htmlspecialchars($user['role'] ?? '') ?>" style="text-transform: capitalize;" disabled>
                    </div>
                    
                    <button type="submit" id="update-profile-btn" class="btn-save"><?= $locale === 'en' ? 'Update Profile' : 'Profili Güncelle' ?></button>
                </form>
            </div>

            <!-- Password Change Card -->
            <div class="settings-card">
                <h3 class="card-title"><?= $locale === 'en' ? 'Change Password' : 'Şifre Değiştir' ?></h3>
                
                <form id="change-password-form" onsubmit="handleChangePassword(event)">
                    <div class="mb-3">
                        <label for="old_password" class="form-label-custom"><?= $locale === 'en' ? 'Current Password' : 'Mevcut Şifre' ?></label>
                        <input type="password" class="form-input-custom" id="old_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label-custom"><?= $locale === 'en' ? 'New Password' : 'Yeni Şifre' ?></label>
                        <input type="password" class="form-input-custom" id="new_password" required>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label-custom"><?= $locale === 'en' ? 'Confirm New Password' : 'Yeni Şifre (Tekrar)' ?></label>
                        <input type="password" class="form-input-custom" id="confirm_password" required>
                    </div>
                    
                    <button type="submit" id="save-btn" class="btn-save"><?= $locale === 'en' ? 'Update Password' : 'Şifreyi Güncelle' ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
    const changePasswordUrl = <?= json_encode(eduqr_path('/admin/settings/change-password')) ?>;
    const updateProfileUrl = <?= json_encode(eduqr_path('/admin/settings/update-profile')) ?>;

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

    function showToast(msg, type = 'success') {
        const c = document.getElementById('toast-container');
        const toast = document.createElement('div');
        const icon = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
        toast.className = `toast-pill ${type}`;
        toast.innerHTML = `<span class="toast-icon">${icon}</span><span>${msg}</span>`;
        c.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 3500);
    }

    async function handleUpdateProfile(e) {
        e.preventDefault();
        const name = document.getElementById('profile_name').value;
        const email = document.getElementById('profile_email').value;
        const btn = document.getElementById('update-profile-btn');

        btn.disabled = true;

        try {
            const res = await fetch(updateProfileUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, email })
            });
            const data = await res.json();
            
            if (data.success) {
                showToast('<?= $locale === 'en' ? "Profile updated successfully!" : "Profil başarıyla güncellendi!" ?>', 'success');
                // Reload to update sidebar name and other possible places
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.error || 'Profil güncellenemedi.', 'error');
            }
        } catch (err) {
            showToast('Bağlantı hatası: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    }

    async function handleChangePassword(e) {
        e.preventDefault();
        const old_password = document.getElementById('old_password').value;
        const new_password = document.getElementById('new_password').value;
        const confirm_password = document.getElementById('confirm_password').value;
        const btn = document.getElementById('save-btn');

        if (new_password !== confirm_password) {
            showToast('<?= htmlspecialchars(t('auth.login.forgot_password_error_mismatch')) ?>', 'error');
            return;
        }

        btn.disabled = true;

        try {
            const res = await fetch(changePasswordUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ old_password, new_password, confirm_password })
            });
            const data = await res.json();
            
            if (data.success) {
                showToast('<?= $locale === 'en' ? "Password updated successfully!" : "Şifreniz başarıyla güncellendi!" ?>', 'success');
                document.getElementById('change-password-form').reset();
            } else {
                showToast(data.error || 'Şifre güncellenemedi.', 'error');
            }
        } catch (err) {
            showToast('Bağlantı hatası: ' + err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    }
</script>
</body>
</html>
