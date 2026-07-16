<?php

declare(strict_types=1);

use EduQR\I18n\I18nService;

if (!function_exists('t')) {
    function t(string $key, array $replace = []): string {
        return I18nService::translate($key, $replace);
    }
}

if (!function_exists('eduqr_path')) {
    function eduqr_path(string $path = ''): string {
        $base = '/eduqr-rebuild/public';
        return $base . '/' . ltrim($path, '/');
    }
}
