<?php
$locale = \EduQR\I18n\I18nService::getLocale();
?><!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - <?= $locale === 'en' ? 'Server Error' : 'Sunucu Hatası' ?> - eduQR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: #030712;
            color: #f9fafb;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 4rem;
            text-align: center;
            max-width: 500px;
            backdrop-filter: blur(16px);
        }
        .error-code {
            font-size: 6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">500</div>
        <h2 class="fw-bold mt-3 mb-2"><?= $locale === 'en' ? 'Server Error' : 'Sunucu Hatası' ?></h2>
        <p class="text-muted mb-4"><?= $locale === 'en' ? 'Something went wrong. Please try again later.' : 'Bir şeyler ters gitti. Lütfen daha sonra tekrar deneyin.' ?></p>
        <a href="<?= eduqr_path('/admin/dashboard') ?>" class="btn btn-custom-primary px-4 py-2 rounded-3 text-decoration-none fw-semibold" style="background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%);border:none;color:#fff;"><?= $locale === 'en' ? 'Go to Dashboard' : 'Panele Dön' ?></a>
    </div>
</body>
</html>
