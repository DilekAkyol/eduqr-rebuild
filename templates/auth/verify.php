<?php
$locale = \EduQR\I18n\I18nService::getLocale();
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('auth.verify.title')) ?> - eduQR</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 3rem 2.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, var(--shadow-opacity));
            max-width: 440px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .form-control {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--input-color) !important;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            transition: all 0.3s;
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            font-weight: 700;
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
            border-radius: 10px;
            padding: 0.8rem;
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

        .text-muted {
            color: var(--text-muted) !important;
        }

        .login-link {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .login-link:hover {
            color: #60a5fa;
            text-decoration: underline;
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

    <div class="login-card">
        <div class="text-center mb-4">
            <span class="logo-text">eduQR</span>
            <p class="text-muted mt-2"><?= htmlspecialchars(t('auth.verify.title')) ?></p>
            <p class="small text-muted"><?= htmlspecialchars(t('auth.verify.desc')) ?></p>
            <?php if (isset($_SESSION['verify_email'])): ?>
                <span class="badge bg-secondary bg-opacity-25 text-info p-2 small mt-1"><?= htmlspecialchars($_SESSION['verify_email']) ?></span>
            <?php endif; ?>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 text-center small" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= eduqr_path('/verify-email') ?>" method="POST">
            <div class="mb-4">
                <label for="code" class="form-label text-muted small fw-semibold d-block text-center"><?= htmlspecialchars(t('auth.verify.code')) ?></label>
                <input type="text" class="form-control" id="code" name="code" required maxlength="6" pattern="[0-9]{6}" autocomplete="one-time-code" autofocus placeholder="000000">
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3"><?= htmlspecialchars(t('auth.verify.submit')) ?></button>
        </form>

        <div class="text-center mt-3">
            <a href="<?= eduqr_path('/login') ?>" class="login-link"><?= htmlspecialchars(t('auth.verify.cancel')) ?></a>
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
