<?php

declare(strict_types=1);

namespace EduQR\I18n;

final class I18nService
{
    private static string $locale = 'tr'; // Varsayılan dil Türkçe
    private static array $translations = [];

    public static function init(): void
    {
        // 1. Dile karar ver (Çerez veya Tarayıcı dili kontrolü yapılabilir)
        if (isset($_GET['lang'])) {
            self::$locale = $_GET['lang'] === 'en' ? 'en' : 'tr';
            setcookie('eduqr_locale', self::$locale, [
                'expires' => time() + 365 * 24 * 3600,
                'path' => '/',
                'samesite' => 'Lax'
            ]);
        } elseif (isset($_COOKIE['eduqr_locale'])) {
            self::$locale = $_COOKIE['eduqr_locale'] === 'en' ? 'en' : 'tr';
        }

        // 2. Dil dosyasını yükle
        $path = __DIR__ . '/../../locales/' . self::$locale . '.json';
        if (file_exists($path)) {
            $content = file_get_contents($path);
            self::$translations = json_decode($content ?: '{}', true) ?: [];
        }
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    public static function setLocale(string $locale): void
    {
        self::$locale = $locale === 'en' ? 'en' : 'tr';
        setcookie('eduqr_locale', self::$locale, [
            'expires' => time() + 365 * 24 * 3600,
            'path' => '/',
            'samesite' => 'Lax'
        ]);
    }

    public static function translate(string $key, array $replace = []): string
    {
        $message = self::$translations[$key] ?? $key;

        foreach ($replace as $placeholder => $value) {
            $message = str_replace('{' . $placeholder . '}', (string) $value, $message);
        }

        return $message;
    }
}
