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
    <title><?= htmlspecialchars(t('admin.audit.title')) ?> - eduQR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>(function(){const t=localStorage.getItem('eduqr_theme')||(window.matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t)})();</script>
    <style>
        :root {
            --bg-color: #f8fafc; --card-bg: #ffffff; --card-border: rgba(0,0,0,0.08);
            --text-main: #0f172a; --text-muted: #64748b; --sidebar-bg: #0f172a; --sidebar-hover: #1e293b;
        }
        [data-theme="dark"] {
            --bg-color: #030712; --card-bg: rgba(255,255,255,0.03); --card-border: rgba(255,255,255,0.08);
            --text-main: #f9fafb; --text-muted: #94a3b8; --sidebar-bg: #0b0f19; --sidebar-hover: #111827;
        }
        body {
            background: var(--bg-color); color: var(--text-main);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh; display: flex; transition: background-color 0.3s, color 0.3s;
        }
        .sidebar {
            width: 260px; background: var(--sidebar-bg); color: #fff; display: flex;
            flex-direction: column; padding: 1.5rem; flex-shrink: 0; min-height: 100vh;
            border-right: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar-logo { display:flex; align-items:center; gap:10px; font-size:1.5rem; font-weight:800; margin-bottom:2.5rem; color:#fff; text-decoration:none; }
        .sidebar-logo-icon { background:linear-gradient(135deg,#3b82f6,#8b5cf6); width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; }
        .nav-item-custom { display:flex; align-items:center; gap:12px; padding:0.8rem 1rem; border-radius:10px; color:#94a3b8; text-decoration:none; font-weight:600; font-size:0.9rem; transition:all 0.2s; }
        .nav-item-custom:hover { background:var(--sidebar-hover); color:#fff; }
        .nav-item-custom.active { background:#3b82f6; color:#fff; }
        .sidebar-footer { border-top:1px solid rgba(255,255,255,0.08); padding-top:1.2rem; margin-top:auto; display:flex; align-items:center; gap:10px; }
        .profile-img { width:38px; height:38px; background:rgba(255,255,255,0.1); border-radius:50%; display:flex; align-items:center; justify-content:center; }
        .content-area { flex-grow:1; padding:2.5rem; max-width:1200px; }
        .card-custom { background:var(--card-bg); backdrop-filter:blur(16px); border:1px solid var(--card-border); border-radius:20px; padding:2.2rem; box-shadow:0 10px 40px -10px rgba(0,0,0,0.03); }
        .log-entry { padding:0.8rem; border-bottom:1px solid var(--card-border); }
        .log-entry:last-child { border-bottom:none; }
        .badge-log { padding:0.25rem 0.6rem; border-radius:6px; font-size:0.75rem; font-weight:600; }
    </style>
</head>
<body>
    <div class="sidebar no-print">
        <a href="<?= eduqr_path('/admin/dashboard') ?>" class="sidebar-logo"><div class="sidebar-logo-icon">❖</div><span>eduQR</span></a>
        <div class="nav-menu">
            <a href="<?= eduqr_path('/admin/dashboard') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.dashboard.title')) ?></a>
            <a href="<?= eduqr_path('/admin/question-bank') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.qbank.bank_title')) ?></a>
            <a href="<?= eduqr_path('/admin/audit-logs') ?>" class="nav-item-custom active"><?= htmlspecialchars(t('admin.audit.title')) ?></a>
            <a href="<?= eduqr_path('/admin/archive') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.report.sidebar_archive')) ?></a>
            <a href="<?= eduqr_path('/admin/settings') ?>" class="nav-item-custom"><?= htmlspecialchars(t('admin.settings.title')) ?></a>
        </div>
        <div class="sidebar-footer">
            <div class="profile-img">👤</div>
            <div><div class="small fw-bold text-white"><?= htmlspecialchars($user['name'] ?? '') ?></div><div class="text-muted small"><?= htmlspecialchars(t('admin.report.sidebar_admin')) ?></div></div>
        </div>
    </div>
    <div class="content-area">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><?= htmlspecialchars(t('admin.audit.title')) ?></h2>
        </div>
        <div class="card-custom">
            <div id="logs-container">
                <p class="text-muted"><?= htmlspecialchars(t('common.loading')) ?></p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadLogs() {
            try {
                const res = await fetch('<?= eduqr_path('/api/v1/audit-logs') ?>');
                const data = await res.json();
                const container = document.getElementById('logs-container');
                if (!data.success || !data.logs.length) {
                    container.innerHTML = '<p class="text-muted"><?= htmlspecialchars(t('admin.audit.no_logs')) ?></p>';
                    return;
                }
                let html = `<p class="text-muted small mb-3">\${data.total} <?= htmlspecialchars(t('admin.audit.total_entries')) ?></p>`;
                data.logs.forEach(log => {
                    const time = new Date(log.created_at + ' UTC').toLocaleString('tr-TR');
                    html += `<div class="log-entry d-flex justify-content-between align-items-start">
                        <div><strong>${escapeHtml(log.action)}</strong>
                        <div class="text-muted small">${log.entity_type ? escapeHtml(log.entity_type) + ' #' + log.entity_id + ' · ' : ''}${time}</div>
                        ${log.metadata_json ? '<pre class="small text-muted mt-1 mb-0" style="max-width:400px;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(JSON.stringify(JSON.parse(log.metadata_json), null, 1)) + '</pre>' : ''}
                    </div></div>`;
                });
                container.innerHTML = html;
            } catch (e) {
                document.getElementById('logs-container').innerHTML = '<p class="text-danger"><?= htmlspecialchars(t('admin.audit.failed')) ?></p>';
            }
        }
        function escapeHtml(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
        loadLogs();
    </script>
</body>
</html>
