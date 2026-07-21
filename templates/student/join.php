<?php
$locale = \EduQR\I18n\I18nService::getLocale();
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('student.join.title')) ?> - eduQR</title>
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
            /* Light Theme Variables */
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --card-border: rgba(0, 0, 0, 0.08);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --input-bg: #ffffff;
            --input-border: #cbd5e1;
            --input-color: #0f172a;
            --shadow-opacity: 0.05;
            --primary: #3b82f6;
        }

        [data-theme="dark"] {
            /* Dark Theme Variables */
            --bg-color: #030712;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f9fafb;
            --text-muted: #94a3b8;
            --input-bg: rgba(255, 255, 255, 0.05);
            --input-border: rgba(255, 255, 255, 0.1);
            --input-color: #ffffff;
            --shadow-opacity: 0.5;
        }

        body {
            background: radial-gradient(circle at 10% 20%, var(--bg-color) 0%, var(--bg-color) 90%);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .join-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, var(--shadow-opacity));
            max-width: 400px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .form-control {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--input-color) !important;
            border-radius: 12px;
            padding: 0.9rem 1.1rem;
            transition: all 0.3s;
        }
        .form-control::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.75 !important;
        }

        .form-control:focus {
            background: var(--input-bg) !important;
            border-color: #3b82f6 !important;
            color: var(--input-color) !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15) !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            border-radius: 12px;
            padding: 0.9rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        .logo-text {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .code-badge {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-weight: 700;
            letter-spacing: 1px;
            font-family: monospace;
            display: inline-block;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }
    </style>
</head>
<body>

    <!-- Top Corner Controls -->
    <div class="d-flex align-items-center gap-3" style="position: absolute; top: 1.5rem; right: 1.5rem; z-index: 10;">
        <!-- Theme Toggle Switcher -->
        <div style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 0.2rem; border-radius: 10px;">
            <button class="btn btn-sm d-flex align-items-center justify-content-center p-1 rounded-2" onclick="toggleTheme()" style="width: 28px; height: 28px; border: none; background: transparent; font-size: 0.85rem;">
                <span id="theme-icon-light" class="d-none">☀️</span>
                <span id="theme-icon-dark">🌙</span>
            </button>
        </div>
        <!-- Language Switcher -->
        <div class="d-flex align-items-center gap-2" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); padding: 0.2rem; border-radius: 10px;">
            <a href="?lang=en" class="px-2 py-1 small rounded-2 text-decoration-none <?= $locale === 'en' ? 'bg-primary text-white' : 'text-muted' ?>" style="font-weight: 700; font-size: 0.75rem;">EN</a>
            <a href="?lang=tr" class="px-2 py-1 small rounded-2 text-decoration-none <?= $locale === 'tr' ? 'bg-primary text-white' : 'text-muted' ?>" style="font-weight: 700; font-size: 0.75rem;">TR</a>
        </div>
    </div>

    <div class="join-card text-center">
        <div class="mb-4">
            <span class="logo-text">eduQR</span>
            <div class="mt-3">
                <span class="code-badge"><?= htmlspecialchars(t('student.join.code_label')) ?> <?= htmlspecialchars($session['short_code']) ?></span>
            </div>
            <p class="text-muted mt-3 mb-0 small"><?= htmlspecialchars($session['title']) ?></p>
        </div>

        <?php if (isset($_GET['timeout'])): ?>
            <div class="alert alert-warning border-0 bg-warning bg-opacity-10 text-warning rounded-3 small mb-3" role="alert">
                <?= htmlspecialchars(t('student.join.timeout')) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 small" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= eduqr_path('/join/' . $session['short_code']) ?>" method="POST" class="text-start">
            <div class="mb-3">
                <label for="nickname" class="form-label text-muted small fw-semibold"><?= htmlspecialchars(t('student.join.choose_nickname'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control <?= isset($error) ? 'is-invalid' : '' ?>" id="nickname" name="nickname" required
                       placeholder="<?= htmlspecialchars(t('student.join.nickname_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                       value="<?= htmlspecialchars($_POST['nickname'] ?? '') ?>"
                       autocomplete="off" maxlength="30">
            </div>
            <button type="submit" class="btn btn-primary w-100"><?= htmlspecialchars(t('student.join.submit_btn'), ENT_QUOTES, 'UTF-8') ?></button>
        </form>

        <!-- Privacy Notice -->
        <div class="mt-4 pt-3 border-top border-white border-opacity-10 text-muted text-start" style="font-size: 0.72rem; line-height: 1.45; font-weight: 500;">
            <?= htmlspecialchars(t('student.join.privacy_notice')) ?>
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
            applyTheme(document.documentElement.getAttribute('data-theme') || 'dark');
        });
    </script>
</body>
</html>
