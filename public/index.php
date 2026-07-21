<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Composer Autoloader'ı yükle
require_once __DIR__ . '/../vendor/autoload.php';

// Uygulamayı başlat
\EduQR\Bootstrap::run();
