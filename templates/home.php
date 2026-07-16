<?php
$locale = \EduQR\I18n\I18nService::getLocale();
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eduQR - <?= htmlspecialchars(t('home.tagline')) ?></title>
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
            --input-placeholder: rgba(15, 23, 42, 0.3);
            --ambient-opacity: 0.03;
            --shadow-opacity: 0.05;
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.15);
            --accent: #8b5cf6;
            --accent-glow: rgba(139, 92, 246, 0.15);
        }

        [data-theme="dark"] {
            /* Dark Theme Variables */
            --bg-color: #030712;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f9fafb;
            --text-muted: #94a3b8;
            --input-bg: rgba(255, 255, 255, 0.04);
            --input-border: rgba(255, 255, 255, 0.08);
            --input-placeholder: rgba(255, 255, 255, 0.3);
            --ambient-opacity: 0.1;
            --shadow-opacity: 0.5;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Ambient Glow Backgrounds */
        .ambient-glow-1 {
            position: absolute;
            top: -20%;
            left: -20%;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, rgba(59, 130, 246, var(--ambient-opacity)) 0%, rgba(0,0,0,0) 70%);
            z-index: -1;
            pointer-events: none;
        }
        .ambient-glow-2 {
            position: absolute;
            bottom: -20%;
            right: -20%;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, rgba(139, 92, 246, var(--ambient-opacity)) 0%, rgba(0,0,0,0) 70%);
            z-index: -1;
            pointer-events: none;
        }

        /* Card container */
        .card-custom {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, var(--shadow-opacity));
            width: 100%;
            max-width: 460px;
            transition: all 0.3s ease;
        }

        .logo-header {
            font-size: 2.8rem;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .tagline {
            text-align: center;
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 2.5rem;
        }

        /* Code Input Field */
        .code-input {
            background: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--text-main) !important;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 6px;
            text-align: center;
            border-radius: 14px;
            padding: 0.8rem;
            text-transform: uppercase;
            transition: all 0.3s;
        }

        .code-input::placeholder {
            font-size: 1.1rem;
            letter-spacing: normal;
            font-weight: 500;
            color: var(--input-placeholder) !important;
        }

        .code-input:focus {
            background: var(--input-bg) !important;
            border-color: #3b82f6 !important;
            color: var(--text-main) !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25) !important;
        }

        .btn-join {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.1rem;
            padding: 1rem;
            border-radius: 14px;
            width: 100%;
            transition: all 0.3s;
            margin-top: 1.2rem;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
        }

        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
            color: #ffffff;
        }

        .teacher-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .teacher-link:hover {
            color: #3b82f6;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }
    </style>
</head>
<body>

    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

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

    <div class="container d-flex justify-content-center">
        <div class="card-custom">
            <h1 class="logo-header">eduQR</h1>
            <p class="tagline"><?= htmlspecialchars(t('home.tagline')) ?></p>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 small mb-4 text-center">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="<?= eduqr_path('/join-code') ?>" method="POST">
                <div class="mb-3">
                    <label for="short_code" class="form-label text-muted small fw-semibold d-block text-center mb-3"><?= htmlspecialchars(t('home.input_label')) ?></label>
                    <input type="text" class="form-control code-input" id="short_code" name="short_code" maxlength="6" placeholder="<?= htmlspecialchars(t('home.input_placeholder')) ?>" required autocomplete="off">
                </div>
                <button type="submit" class="btn btn-join"><?= htmlspecialchars(t('home.submit')) ?></button>
            </form>

            <a href="<?= eduqr_path('/login') ?>" class="teacher-link"><?= htmlspecialchars(t('home.teacher_panel')) ?></a>
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
