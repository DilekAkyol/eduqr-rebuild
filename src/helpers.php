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

if (!function_exists('course_title')) {
    function course_title(array $course): string {
        $locale = I18nService::getLocale();
        if ($locale === 'en' && !empty($course['title_en'])) {
            return $course['title_en'];
        }
        return $course['title'];
    }
}

if (!function_exists('course_desc')) {
    function course_desc(array $course): string {
        $locale = I18nService::getLocale();
        if ($locale === 'en' && !empty($course['description_en'])) {
            return $course['description_en'];
        }
        return $course['description'] ?? '';
    }
}

