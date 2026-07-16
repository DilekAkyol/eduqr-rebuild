<?php
$locale = \EduQR\I18n\I18nService::getLocale();
$placeholder_email = ($locale === 'en') ? 'demo@example.org' : 'örnek@eduqr.local';
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(t('auth.login.title')) ?> - eduQR</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Light Theme Variables */
            --bg-color: #f8fafc;
            --split-bg: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            --card-bg: #ffffff;
            --card-border: rgba(0, 0, 0, 0.06);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --input-bg: #f8fafc;
            --input-border: #e2e8f0;
            --input-color: #0f172a;
            --input-focus-border: #4f46e5;
            --input-focus-glow: rgba(79, 70, 229, 0.1);
            --btn-bg: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            --btn-hover-shadow: rgba(79, 70, 229, 0.3);
            --accent-bg: rgba(79, 70, 229, 0.06);
            --accent-color: #4f46e5;
            --badge-bg: #f1f5f9;
            --badge-color: #475569;
            --glass-card-bg: rgba(255, 255, 255, 0.07);
            --glass-card-border: rgba(255, 255, 255, 0.12);
        }

        [data-theme="dark"] {
            /* Dark Theme Variables */
            --bg-color: #030712;
            --split-bg: linear-gradient(135deg, #090d1f 0%, #030712 100%);
            --card-bg: rgba(17, 24, 39, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f9fafb;
            --text-muted: #94a3b8;
            --input-bg: rgba(255, 255, 255, 0.03);
            --input-border: rgba(255, 255, 255, 0.08);
            --input-color: #ffffff;
            --input-focus-border: #6366f1;
            --input-focus-glow: rgba(99, 102, 241, 0.2);
            --btn-bg: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            --btn-hover-shadow: rgba(99, 102, 241, 0.4);
            --accent-bg: rgba(99, 102, 241, 0.12);
            --accent-color: #818cf8;
            --badge-bg: rgba(255, 255, 255, 0.05);
            --badge-color: #cbd5e1;
            --glass-card-bg: rgba(255, 255, 255, 0.03);
            --glass-card-border: rgba(255, 255, 255, 0.08);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            transition: background-color 0.3s ease, color 0.3s ease;
            overflow-x: hidden;
        }

        /* Split-screen wrapper */
        .login-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left Side: Brand Panel */
        .brand-side {
            width: 45%;
            background: var(--split-bg);
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        @media (max-width: 991.98px) {
            .brand-side {
                display: none;
            }
        }

        /* Left Side: Animated Glow Background */
        .brand-side::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -20%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 60%);
            z-index: 1;
            pointer-events: none;
        }

        .brand-side::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -20%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.15) 0%, transparent 60%);
            z-index: 1;
            pointer-events: none;
        }

        .brand-content {
            position: relative;
            z-index: 2;
            max-width: 460px;
            margin: 0 auto;
        }

        .logo-text {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #60a5fa 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            letter-spacing: -1.5px;
        }

        /* Left Side: Glass Cards styling */
        .glass-status-card {
            background: var(--glass-card-bg);
            border: 1px solid var(--glass-card-border);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 18px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .glass-status-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .status-icon-wrapper {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            font-size: 1.4rem;
        }

        /* Right Side: Login Panel */
        .login-side {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: var(--bg-color);
            position: relative;
            transition: background-color 0.3s ease;
        }

        /* Top Header Navigation inside Login Panel */
        .nav-header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 1.5rem 3rem;
            gap: 1rem;
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            z-index: 10;
        }

        @media (max-width: 575.98px) {
            .nav-header {
                padding: 1rem 1.5rem;
            }
        }

        .theme-toggle-btn {
            background: var(--badge-bg);
            border: 1px solid var(--card-border);
            color: var(--text-main);
            width: 42px;
            height: 42px;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 1.1rem;
        }

        .theme-toggle-btn:hover {
            transform: translateY(-2px);
            background: var(--input-bg);
        }

        .language-pill-toggle {
            background: var(--badge-bg);
            border: 1px solid var(--card-border);
            padding: 0.25rem;
            border-radius: 12px;
            display: flex;
            gap: 2px;
        }

        .lang-link {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 9px;
            transition: all 0.2s;
        }

        .lang-link.active {
            background-color: var(--accent-color);
            color: #ffffff !important;
        }

        .lang-link:hover:not(.active) {
            color: var(--text-main);
        }

        /* Center container of Form */
        .form-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5rem 2rem 2rem 2rem;
            position: relative;
        }

        /* The glassmorphic Form Card */
        .login-card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 28px;
            padding: 3rem 2.5rem;
            max-width: 460px;
            width: 100%;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04);
            position: relative;
            transition: all 0.3s ease;
        }

        [data-theme="dark"] .login-card {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        }

        .custom-badge-pill {
            background: var(--badge-bg);
            color: var(--badge-color);
            padding: 0.4rem 0.9rem;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--card-border);
        }

        .secure-badge {
            background: rgba(16, 185, 129, 0.08);
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.15);
        }

        [data-theme="dark"] .secure-badge {
            background: rgba(16, 185, 129, 0.12);
            color: #34d399;
        }

        /* Form elements styling */
        .form-label {
            color: var(--text-muted);
            font-size: 0.78rem;
            letter-spacing: 0.5px;
        }

        .input-group-text {
            border-color: var(--input-border) !important;
            background: var(--input-bg) !important;
            color: var(--text-muted);
            transition: all 0.3s;
        }

        .form-control {
            background-color: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            color: var(--input-color) !important;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--input-focus-border) !important;
            box-shadow: 0 0 0 4px var(--input-focus-glow) !important;
        }

        .form-control:focus + .input-group-text,
        .input-group:focus-within .input-group-text {
            border-color: var(--input-focus-border) !important;
            color: var(--accent-color);
        }

        .btn-outline-secondary {
            border-color: var(--input-border) !important;
            background: var(--input-bg) !important;
            color: var(--text-muted) !important;
            transition: all 0.3s;
        }

        .btn-outline-secondary:hover {
            color: var(--text-main) !important;
        }

        .forgot-link {
            font-size: 0.8rem;
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 700;
            transition: opacity 0.2s;
        }

        .forgot-link:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        .btn-login-submit {
            background: var(--btn-bg);
            border: none;
            color: #ffffff;
            font-weight: 700;
            font-size: 1rem;
            border-radius: 14px;
            padding: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-login-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--btn-hover-shadow);
            color: #ffffff;
        }

        .btn-login-submit:active {
            transform: translateY(0);
        }

        .register-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.2s;
        }

        .register-link:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }

        /* Floating Language Switcher inside card (matches bottom-right mockup placement) */
        .card-footer-lang-switcher {
            position: absolute;
            bottom: 1.5rem;
            right: 1.5rem;
            display: flex;
            gap: 0.5rem;
            align-items: center;
            background: var(--badge-bg);
            padding: 0.2rem 0.6rem;
            border-radius: 50px;
            border: 1px solid var(--card-border);
            font-size: 0.75rem;
            font-weight: 800;
        }

        .card-lang-link {
            text-decoration: none;
            color: var(--text-muted);
            transition: color 0.2s;
        }

        .card-lang-link.active {
            color: var(--accent-color);
        }

        .card-lang-link:hover:not(.active) {
            color: var(--text-main);
        }

        /* Modals & Overlays (Forgot Password screen overlay) */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1050;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            max-width: 420px;
            width: 90%;
            padding: 2.5rem;
            position: relative;
            transform: scale(0.95);
            transition: transform 0.3s ease;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .modal-overlay.active .modal-card {
            transform: scale(1);
        }

        .modal-close-btn {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            background: none;
            border: none;
            font-size: 1.8rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
        }

        .modal-close-btn:hover {
            color: var(--text-main);
        }

        .text-muted {
            color: var(--text-muted) !important;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <!-- Left Side: Decorative Branding -->
        <div class="brand-side">
            <div class="brand-content">
                <div class="mb-4">
                    <span class="custom-badge-pill" style="color: #60a5fa; background: rgba(96, 165, 250, 0.08); border-color: rgba(96, 165, 250, 0.15);">
                        <i class="bi bi-shield-lock-fill me-2"></i>
                        <?= htmlspecialchars(t('auth.login.system_ready_title')) ?>
                    </span>
                </div>
                
                <h1 class="logo-text">eduQR</h1>
                <p class="text-white opacity-75 mb-5 fs-5 fw-medium"><?= htmlspecialchars(t('auth.login.tagline')) ?></p>

                <div class="d-flex flex-column gap-3">
                    <div class="glass-status-card p-3 d-flex align-items-center gap-3">
                        <div class="status-icon-wrapper">
                            <i class="bi bi-grid-fill"></i>
                        </div>
                        <div>
                            <h5 class="m-0 text-white fw-bold" style="font-size: 0.95rem;"><?= htmlspecialchars(t('auth.login.system_ready_title')) ?></h5>
                            <p class="m-0 text-white opacity-50 small mt-0.5"><?= htmlspecialchars(t('auth.login.system_ready_desc')) ?></p>
                        </div>
                    </div>

                    <div class="glass-status-card p-3 d-flex align-items-center gap-3">
                        <div class="status-icon-wrapper" style="color: #c084fc;">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>
                        <div>
                            <h5 class="m-0 text-white fw-bold" style="font-size: 0.95rem;"><?= htmlspecialchars(t('auth.login.live_results_title')) ?></h5>
                            <p class="m-0 text-white opacity-50 small mt-0.5"><?= htmlspecialchars(t('auth.login.live_results_desc')) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Card and Switchers -->
        <div class="login-side">
            <!-- Header Toggles -->
            <div class="nav-header">
                <!-- Theme Switcher -->
                <button id="theme-toggle-btn" class="theme-toggle-btn" onclick="toggleTheme()" aria-label="<?= htmlspecialchars(t('auth.login.theme_toggle')) ?>">
                    <i class="bi bi-sun-fill" id="theme-icon-light"></i>
                    <i class="bi bi-moon-fill d-none" id="theme-icon-dark"></i>
                </button>

                <!-- Language Toggler -->
                <div class="language-pill-toggle">
                    <a href="?lang=en" class="lang-link <?= $locale === 'en' ? 'active' : '' ?>">EN</a>
                    <a href="?lang=tr" class="lang-link <?= $locale === 'tr' ? 'active' : '' ?>">TR</a>
                </div>
            </div>

            <!-- Login Container -->
            <div class="form-container">
                <div class="login-card">
                    <!-- Badges Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="custom-badge-pill">
                            <i class="bi bi-plus-lg me-1.5" style="font-size: 0.75rem;"></i>
                            <span><?= htmlspecialchars(t('auth.login.system_ready_title')) ?></span>
                        </span>
                        <span class="custom-badge-pill secure-badge">
                            <i class="bi bi-shield-fill-check me-1.5"></i>
                            <span><?= htmlspecialchars(t('auth.login.secure')) ?></span>
                        </span>
                    </div>

                    <!-- Title -->
                    <h2 class="fw-bold mb-4" style="color: var(--text-main); font-size: 1.85rem; letter-spacing: -0.5px;">
                        <?= htmlspecialchars(t('auth.login.system_ready_title')) ?>
                    </h2>

                    <!-- Error Alert -->
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 small mb-4 d-flex align-items-center" role="alert" style="border: 1px solid rgba(239, 68, 68, 0.15) !important;">
                            <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1rem;"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form action="<?= eduqr_path('/login') ?>" method="POST">
                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label text-muted small fw-semibold text-uppercase d-block mb-2">
                                <?= htmlspecialchars(t('auth.login.email')) ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text border-end-0" style="border-top-left-radius: 12px; border-bottom-left-radius: 12px;">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control border-start-0" id="email" name="email" required placeholder="<?= htmlspecialchars($placeholder_email) ?>" autocomplete="email" style="border-top-right-radius: 12px; border-bottom-right-radius: 12px; padding: 0.8rem 1rem;">
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="password" class="form-label text-muted small fw-semibold text-uppercase m-0">
                                    <?= htmlspecialchars(t('auth.login.password')) ?>
                                </label>
                                <a href="#" class="forgot-link" onclick="openForgotPasswordModal(event)">
                                    <?= htmlspecialchars(t('auth.login.forgot_password')) ?>
                                </a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text border-end-0" style="border-top-left-radius: 12px; border-bottom-left-radius: 12px;">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" required placeholder="••••••••" style="padding: 0.8rem 1rem;">
                                <button class="btn btn-outline-secondary border-start-0" type="button" id="toggle-password-btn" onclick="togglePasswordVisibility()" style="border-top-right-radius: 12px; border-bottom-right-radius: 12px;">
                                    <i class="bi bi-eye" id="password-eye-icon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-login-submit w-100 mb-4">
                            <span><?= htmlspecialchars(t('auth.login.submit')) ?></span>
                            <i class="bi bi-arrow-right-short ms-1.5" style="font-size: 1.25rem;"></i>
                        </button>
                    </form>

                    <!-- Footer Link -->
                    <div class="text-center" style="margin-bottom: 1rem;">
                        <a href="<?= eduqr_path('/register') ?>" class="register-link">
                            <?= htmlspecialchars(t('auth.login.no_account')) ?>
                        </a>
                    </div>

                    <!-- Inner Card Language Selector (Mockup Parity) -->
                    <div class="card-footer-lang-switcher">
                        <a href="?lang=en" class="card-lang-link <?= $locale === 'en' ? 'active' : '' ?>">EN</a>
                        <span style="color: var(--card-border); font-size: 0.7rem; font-weight: normal;">|</span>
                        <a href="?lang=tr" class="card-lang-link <?= $locale === 'tr' ? 'active' : '' ?>">TR</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal Overlay -->
    <div id="forgot-password-modal" class="modal-overlay" onclick="closeForgotPasswordModalOnOverlay(event)">
        <div class="modal-card">
            <button class="modal-close-btn" onclick="closeForgotPasswordModal()">&times;</button>
            
            <h3 class="fw-bold mb-2.5" style="color: var(--text-main); font-size: 1.45rem; letter-spacing: -0.4px;">
                <?= htmlspecialchars(t('auth.login.forgot_password_modal_title')) ?>
            </h3>
            
            <p class="text-muted small mb-4" style="line-height: 1.5;">
                <?= htmlspecialchars(t('auth.login.forgot_password_modal_desc')) ?>
            </p>
            
            <div id="modal-success-alert" class="alert alert-success border-0 bg-success bg-opacity-10 text-success rounded-3 small mb-4 d-none align-items-center" style="border: 1px solid rgba(16, 185, 129, 0.15) !important;">
                <i class="bi bi-check-circle-fill me-2" style="font-size: 1.05rem;"></i>
                <div><?= htmlspecialchars(t('auth.login.forgot_password_modal_success')) ?></div>
            </div>

            <div id="modal-error-alert" class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger rounded-3 small mb-4 d-none align-items-center" style="border: 1px solid rgba(239, 68, 68, 0.15) !important;">
                <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1rem;"></i>
                <div></div>
            </div>

            <form id="forgot-password-form" onsubmit="handleForgotPasswordSubmit(event)">
                <div class="mb-4">
                    <label for="reset-email" class="form-label text-muted small fw-semibold text-uppercase d-block mb-2">
                        <?= htmlspecialchars(t('auth.login.email')) ?>
                    </label>
                    <input type="email" class="form-control" id="reset-email" required placeholder="<?= htmlspecialchars($placeholder_email) ?>" style="padding: 0.8rem 1rem; border-radius: 12px;">
                </div>
                
                <button type="submit" id="reset-submit-btn" class="btn btn-login-submit w-100">
                    <span id="reset-btn-text"><?= htmlspecialchars(t('auth.login.forgot_password_modal_submit')) ?></span>
                    <div id="reset-btn-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" style="width: 1rem; height: 1rem;"></div>
                </button>
            </form>

            <form id="reset-password-form" class="d-none" onsubmit="handleResetPasswordSubmit(event)">
                <!-- Code Input -->
                <div class="mb-3">
                    <label for="reset-code" class="form-label text-muted small fw-semibold text-uppercase d-block mb-2">
                        <?= htmlspecialchars(t('auth.login.forgot_password_code_label')) ?>
                    </label>
                    <input type="text" class="form-control" id="reset-code" required placeholder="123456" style="padding: 0.8rem 1rem; border-radius: 12px; text-align: center; letter-spacing: 4px; font-weight: bold;">
                </div>

                <!-- New Password Input -->
                <div class="mb-3">
                    <label for="new-password" class="form-label text-muted small fw-semibold text-uppercase d-block mb-2">
                        <?= htmlspecialchars(t('auth.login.forgot_password_new_password_label')) ?>
                    </label>
                    <input type="password" class="form-control" id="new-password" required placeholder="••••••••" style="padding: 0.8rem 1rem; border-radius: 12px;">
                </div>

                <!-- Confirm Password Input -->
                <div class="mb-4">
                    <label for="confirm-password" class="form-label text-muted small fw-semibold text-uppercase d-block mb-2">
                        <?= htmlspecialchars(t('auth.login.forgot_password_confirm_password_label')) ?>
                    </label>
                    <input type="password" class="form-control" id="confirm-password" required placeholder="••••••••" style="padding: 0.8rem 1rem; border-radius: 12px;">
                </div>
                
                <button type="submit" id="confirm-reset-btn" class="btn btn-login-submit w-100">
                    <span id="confirm-reset-btn-text"><?= htmlspecialchars(t('auth.login.forgot_password_submit_reset')) ?></span>
                    <div id="confirm-reset-btn-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true" style="width: 1rem; height: 1rem;"></div>
                </button>
            </form>
        </div>
    </div>

    <!-- Theme and Password Interaction Logic -->
    <script>
        // Init theme state
        (function() {
            const savedTheme = localStorage.getItem('eduqr_theme') || 
                               (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            applyTheme(savedTheme);
        })();

        function applyTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('eduqr_theme', theme);

            const sunIcon = document.getElementById('theme-icon-light');
            const moonIcon = document.getElementById('theme-icon-dark');

            if (theme === 'dark') {
                sunIcon.classList.add('d-none');
                moonIcon.classList.remove('d-none');
            } else {
                sunIcon.classList.remove('d-none');
                moonIcon.classList.add('d-none');
            }
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme);
        }

        // Password visibility toggler
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('password-eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        let resetEmail = '';

        // Forgot Password Modal trigger
        function openForgotPasswordModal(e) {
            e.preventDefault();
            const modal = document.getElementById('forgot-password-modal');
            const successAlert = document.getElementById('modal-success-alert');
            const errorAlert = document.getElementById('modal-error-alert');
            const form = document.getElementById('forgot-password-form');
            const resetForm = document.getElementById('reset-password-form');
            
            // Reset alerts
            successAlert.classList.add('d-none');
            successAlert.querySelector('div').textContent = '<?= htmlspecialchars(t("auth.login.forgot_password_modal_success")) ?>';
            errorAlert.classList.add('d-none');

            // Reset forms
            form.classList.remove('d-none');
            resetForm.classList.add('d-none');

            // Reset inputs
            document.getElementById('reset-email').value = '';
            document.getElementById('reset-code').value = '';
            document.getElementById('new-password').value = '';
            document.getElementById('confirm-password').value = '';

            modal.classList.add('active');
        }

        function closeForgotPasswordModal() {
            const modal = document.getElementById('forgot-password-modal');
            modal.classList.remove('active');
        }

        function closeForgotPasswordModalOnOverlay(e) {
            if (e.target === document.getElementById('forgot-password-modal')) {
                closeForgotPasswordModal();
            }
        }

        // Intercept Forgot Password Form
        async function handleForgotPasswordSubmit(e) {
            e.preventDefault();
            const emailInput = document.getElementById('reset-email');
            const submitBtn = document.getElementById('reset-submit-btn');
            const btnText = document.getElementById('reset-btn-text');
            const spinner = document.getElementById('reset-btn-spinner');
            const successAlert = document.getElementById('modal-success-alert');
            const errorAlert = document.getElementById('modal-error-alert');
            const form = document.getElementById('forgot-password-form');
            const resetForm = document.getElementById('reset-password-form');

            resetEmail = emailInput.value.trim();

            // Clear old alerts
            errorAlert.classList.add('d-none');
            successAlert.classList.add('d-none');

            // Show loading
            submitBtn.disabled = true;
            btnText.classList.add('d-none');
            spinner.classList.remove('d-none');

            try {
                const res = await fetch('<?= eduqr_path("/forgot-password") ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: resetEmail })
                });
                const data = await res.json();

                if (data.success) {
                    // Show success, hide current form, show code entry form
                    successAlert.classList.remove('d-none');
                    form.classList.add('d-none');
                    resetForm.classList.remove('d-none');
                } else {
                    errorAlert.querySelector('div').textContent = data.error || '<?= htmlspecialchars(t("common.error")) ?>';
                    errorAlert.classList.remove('d-none');
                }
            } catch (err) {
                errorAlert.querySelector('div').textContent = '<?= htmlspecialchars(t("common.error")) ?>: ' + err.message;
                errorAlert.classList.remove('d-none');
            } finally {
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            }
        }

        async function handleResetPasswordSubmit(e) {
            e.preventDefault();
            const codeInput = document.getElementById('reset-code');
            const newPasswordInput = document.getElementById('new-password');
            const confirmPasswordInput = document.getElementById('confirm-password');
            const submitBtn = document.getElementById('confirm-reset-btn');
            const btnText = document.getElementById('confirm-reset-btn-text');
            const spinner = document.getElementById('confirm-reset-btn-spinner');
            const successAlert = document.getElementById('modal-success-alert');
            const errorAlert = document.getElementById('modal-error-alert');
            const resetForm = document.getElementById('reset-password-form');

            const code = codeInput.value.trim();
            const password = newPasswordInput.value;
            const confirm = confirmPasswordInput.value;

            // Clear old alerts
            errorAlert.classList.add('d-none');

            if (password !== confirm) {
                errorAlert.querySelector('div').textContent = '<?= htmlspecialchars(t("auth.login.forgot_password_error_mismatch")) ?>';
                errorAlert.classList.remove('d-none');
                return;
            }

            // Show loading
            submitBtn.disabled = true;
            btnText.classList.add('d-none');
            spinner.classList.remove('d-none');

            try {
                const res = await fetch('<?= eduqr_path("/reset-password") ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: resetEmail, code, password })
                });
                const data = await res.json();

                if (data.success) {
                    // Show final success message and hide form controls
                    successAlert.querySelector('div').textContent = '<?= htmlspecialchars(t("auth.login.forgot_password_reset_success")) ?>';
                    successAlert.classList.remove('d-none');
                    resetForm.classList.add('d-none');
                    
                    // Close modal after a few seconds
                    setTimeout(() => {
                        closeForgotPasswordModal();
                    }, 3000);
                } else {
                    errorAlert.querySelector('div').textContent = data.error || '<?= htmlspecialchars(t("common.error")) ?>';
                    errorAlert.classList.remove('d-none');
                }
            } catch (err) {
                errorAlert.querySelector('div').textContent = '<?= htmlspecialchars(t("common.error")) ?>: ' + err.message;
                errorAlert.classList.remove('d-none');
            } finally {
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            }
        }
    </script>
</body>
</html>
