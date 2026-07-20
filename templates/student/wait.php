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
    <noscript>
        <meta http-equiv="refresh" content="5">
    </noscript>
    
    <!-- Theme Fast-Init script to prevent white flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('eduqr_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    
    <style>
        #js-container {
            display: none;
        }
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
            --shadow-opacity: 0.05;
            --primary: #3b82f6;
            --accent: #8b5cf6;
        }

        [data-theme="dark"] {
            /* Dark Mode Variables */
            --bg-color: #030712;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f9fafb;
            --text-muted: #94a3b8;
            --item-bg: rgba(255, 255, 255, 0.02);
            --item-border: rgba(255, 255, 255, 0.08);
            --item-hover-bg: rgba(59, 130, 246, 0.08);
            --item-hover-border: #3b82f6;
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
        
        .wait-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, var(--shadow-opacity));
            max-width: 440px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .glow-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(59, 130, 246, 0.1);
            border-radius: 50%;
            border-left-color: #3b82f6;
            animation: spin 1s linear infinite;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.2);
            margin: 0 auto 2rem auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Option buttons */
        .option-btn {
            background: var(--item-bg);
            border: 1px solid var(--item-border);
            color: var(--text-main);
            border-radius: 12px;
            padding: 1rem 1.2rem;
            text-align: left;
            font-weight: 500;
            width: 100%;
            transition: all 0.2s;
            margin-bottom: 0.8rem;
        }
        
        .option-btn:hover:not(:disabled) {
            background: var(--item-hover-bg);
            border-color: #3b82f6;
            transform: translateY(-1px);
        }
        
        .option-btn:active:not(:disabled) {
            transform: translateY(1px);
        }
        
        .option-indicator {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #3b82f6;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 0.8rem;
            font-size: 0.9rem;
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(to right, #3b82f6, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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

    <div class="wait-card text-center" id="card-content">
        <noscript>
            <?php if ($activeQuestion === null): ?>
                <!-- Wait State -->
                <div>
                    <div class="glow-spinner" style="animation: none; box-shadow: none; border-color: #3b82f6;"></div>
                    <h4 class="fw-bold mb-3"><?= htmlspecialchars(t('student.join.waiting')) ?></h4>
                    <p class="text-muted mb-4 small px-3"><?= htmlspecialchars(t('student.join.waiting_desc')) ?></p>
                    <a href="" class="btn btn-primary w-100 py-3 mt-2" style="border-radius:12px;"><?= $locale === 'en' ? 'Refresh' : 'Sayfayı Yenile' ?></a>
                </div>
            <?php else: ?>
                <?php if ($hasAnswered): ?>
                    <!-- Answered State -->
                    <div>
                        <div class="text-success fs-1 mb-3">✓</div>
                        <h4 class="fw-bold mb-3"><?= htmlspecialchars(t('student.wait.answer_submitted')) ?></h4>
                        <p class="text-muted mb-4 small px-3"><?= htmlspecialchars(t('student.wait.wait_for_next')) ?></p>
                        <a href="" class="btn btn-primary w-100 py-3 mt-2" style="border-radius:12px;"><?= $locale === 'en' ? 'Refresh' : 'Sayfayı Yenile' ?></a>
                    </div>
                <?php else: ?>
                    <!-- Question State -->
                    <div class="text-start">
                        <div class="mb-4">
                            <span class="badge bg-primary bg-opacity-10 text-primary py-1.5 px-3 rounded-pill small mb-2"><?= htmlspecialchars(t('student.wait.active_question')) ?></span>
                            <h4 class="fw-bold lh-base"><?= htmlspecialchars($activeQuestion['question_text']) ?></h4>
                        </div>
                        
                        <form action="<?= eduqr_path('/join/' . $session['short_code'] . '/wait') ?>" method="POST">
                            <input type="hidden" name="question_id" value="<?= (int)$activeQuestion['id'] ?>">
                            <?php if ($activeQuestion['type'] === 'open_ended'): ?>
                                <div class="mb-3">
                                    <textarea name="answer_value" class="form-control" rows="4" style="background:var(--card-bg); color:var(--text-main); border:1px solid var(--card-border); border-radius:12px;" placeholder="<?= $locale === 'en' ? 'Write your response here...' : 'Buraya cevabınızı yazın...' ?>" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 py-3 mt-2" style="border-radius:12px;"><?= $locale === 'en' ? 'Submit Response' : 'Cevabı Gönder' ?></button>
                            <?php elseif (!empty($activeQuestion['options']) && is_array($activeQuestion['options'])): ?>
                                <?php foreach ($activeQuestion['options'] as $idx => $opt): ?>
                                    <?php $char = chr(65 + $idx); ?>
                                    <button type="submit" name="answer_value" value="<?= $char ?>" class="option-btn">
                                        <span class="option-indicator"><?= $char ?></span> <?= htmlspecialchars($opt) ?>
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </noscript>

        <div id="js-container">
            <!-- Default State: Waiting for question -->
            <div id="wait-state">
                <div class="glow-spinner"></div>
                <h4 class="fw-bold mb-3"><?= htmlspecialchars(t('student.join.waiting')) ?></h4>
                <p class="text-muted mb-4 small px-3"><?= htmlspecialchars(t('student.join.waiting_desc')) ?></p>
            </div>

            <!-- Question View State (Hidden initially) -->
            <div id="question-state" class="d-none text-start">
                <div class="mb-4">
                    <span class="badge bg-primary bg-opacity-10 text-primary py-1.5 px-3 rounded-pill small mb-2"><?= htmlspecialchars(t('student.wait.active_question')) ?></span>
                    <h4 class="fw-bold lh-base" id="q-text"><?= htmlspecialchars(t('student.wait.loading_question')) ?></h4>
                </div>
                
                <div id="options-container">
                    <!-- Option buttons injected here dynamically -->
                </div>
            </div>

            <!-- Answered / Thank You State (Hidden initially) -->
            <div id="answered-state" class="d-none">
                <div class="text-success fs-1 mb-3">✓</div>
                <h4 class="fw-bold mb-3"><?= htmlspecialchars(t('student.wait.answer_submitted')) ?></h4>
                <p class="text-muted mb-4 small px-3"><?= htmlspecialchars(t('student.wait.wait_for_next')) ?></p>
            </div>
        </div>
        
        <div class="mt-4 pt-3 border-top border-white border-opacity-10">
            <span class="logo-text">eduQR</span>
            <div class="text-muted small mt-1"><?= htmlspecialchars($participant ? $participant['nickname'] : t('student.wait.participant')) ?></div>
        </div>
    </div>

    <script>
        const shortCode = <?= json_encode($session['short_code']) ?>;
        const apiPath = <?= json_encode(eduqr_path('/api/v1/sessions/')) ?>;
        const answerPath = <?= json_encode(eduqr_path('/api/v1/answers')) ?>;
        const locale = <?= json_encode($locale) ?>;

        // Localized JS variables
        const translationSubmitFailed = <?= json_encode(t('student.wait.submit_failed')) ?>;
        const translationConnectionError = <?= json_encode(t('student.wait.connection_error')) ?>;

        document.getElementById('js-container').style.display = 'block';

        const waitState = document.getElementById('wait-state');
        const questionState = document.getElementById('question-state');
        const answeredState = document.getElementById('answered-state');
        
        const qText = document.getElementById('q-text');
        const optionsContainer = document.getElementById('options-container');

        let currentActiveQuestionId = null;
        let waitSeconds = 0;
        const maxWaitSeconds = 120; // 2 minutes wait timeout

        async function poll() {
            try {
                const res = await fetch(`${apiPath}${shortCode}/active-question`);
                const data = await res.json();

                if (data.success === false) {
                    // Redirect to join page if session is invalid or deleted
                    window.location.href = <?= json_encode(eduqr_path('/join/')) ?> + shortCode;
                    return;
                }

                if (data.active) {
                    waitSeconds = 0; // Reset counter when question arrives
                    if (data.has_answered) {
                        // Question active but student already voted
                        showState('answered');
                    } else {
                        // Voted false, render options
                        if (currentActiveQuestionId !== data.question.id) {
                            currentActiveQuestionId = data.question.id;
                            renderQuestion(data.question);
                        }
                        showState('question');
                    }
                } else {
                    // No active question, show wait loader
                    currentActiveQuestionId = null;
                    showState('wait');

                    // Increment wait seconds (polling runs every 3 seconds)
                    waitSeconds += 3;
                    if (waitSeconds >= maxWaitSeconds) {
                        window.location.href = <?= json_encode(eduqr_path('/join/')) ?> + shortCode + "?timeout=1";
                        return;
                    }
                }
            } catch (err) {
                console.error("Polling error:", err);
            }
        }

        function showState(state) {
            waitState.classList.add('d-none');
            questionState.classList.add('d-none');
            answeredState.classList.add('d-none');

            if (state === 'wait') {
                waitState.classList.remove('d-none');
            } else if (state === 'question') {
                questionState.classList.remove('d-none');
            } else if (state === 'answered') {
                answeredState.classList.remove('d-none');
            }
        }

        function renderQuestion(q) {
            qText.textContent = q.question_text;
            optionsContainer.innerHTML = '';

            if (q.type === 'open_ended') {
                optionsContainer.innerHTML = `
                    <div class="mb-3">
                        <textarea id="open-ended-answer" class="form-control" rows="4" style="background:var(--card-bg); color:var(--text-main); border:1px solid var(--card-border); border-radius:12px;" placeholder="${locale === 'en' ? 'Write your response here...' : 'Buraya cevabınızı yazın...'}" required></textarea>
                    </div>
                    <button onclick="submitOpenEndedAnswer(${q.id})" id="btn-submit-answer" class="btn btn-primary w-100 py-3 mt-2" style="border-radius:12px;">${locale === 'en' ? 'Submit Response' : 'Cevabı Gönder'}</button>
                `;
            } else if (q.options && Array.isArray(q.options)) {
                q.options.forEach((opt, idx) => {
                    const char = String.fromCharCode(65 + idx); // A, B, C, D...
                    const btn = document.createElement('button');
                    btn.className = 'option-btn';
                    btn.innerHTML = `<span class="option-indicator">${char}</span> ${escapeHtml(opt)}`;
                    btn.onclick = () => submitAnswer(q.id, char);
                    optionsContainer.appendChild(btn);
                });
            }
        }

        async function submitOpenEndedAnswer(questionId) {
            const textarea = document.getElementById('open-ended-answer');
            const answerVal = textarea.value.trim();
            if (!answerVal) {
                alert(locale === 'en' ? 'Please write your response first.' : 'Lütfen önce cevabınızı yazın.');
                return;
            }

            const btn = document.getElementById('btn-submit-answer');
            btn.disabled = true;
            textarea.disabled = true;

            try {
                const res = await fetch(answerPath, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        question_id: questionId,
                        answer_value: answerVal
                    })
                });
                const data = await res.json();

                if (data.success) {
                    showState('answered');
                } else {
                    alert(data.error || translationSubmitFailed);
                    btn.disabled = false;
                    textarea.disabled = false;
                }
            } catch (err) {
                console.error("Submit error:", err);
                alert(translationConnectionError);
                btn.disabled = false;
                textarea.disabled = false;
            }
        }

        async function submitAnswer(questionId, value) {
            // Lock options buttons
            const btns = optionsContainer.querySelectorAll('button');
            btns.forEach(b => b.disabled = true);

            try {
                const res = await fetch(answerPath, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        question_id: questionId,
                        answer_value: value
                    })
                });
                const data = await res.json();

                if (data.success) {
                    showState('answered');
                } else {
                    alert(data.error || translationSubmitFailed);
                    btns.forEach(b => b.disabled = false);
                }
            } catch (err) {
                console.error("Submit error:", err);
                alert(translationConnectionError);
                btns.forEach(b => b.disabled = false);
            }
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Run polling every 3 seconds
        setInterval(poll, 3000);
        poll(); // Run immediately on load
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
        document.addEventListener('DOMContentLoaded', () => {
            applyTheme(document.documentElement.getAttribute('data-theme') || 'dark');
        });
    </script>
</body>
</html>
