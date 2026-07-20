<?php

declare(strict_types=1);

namespace EduQR\Services;

/**
 * Basit argo/kufur filtresi (FR-41, FR-43).
 *
 * Kelime listesi; tam kelime eslesmesi ile kontrol edilir.
 */
final class ProfanityFilter
{
    private array $words;

    public function __construct()
    {
        $this->words = $this->buildWordList();
    }

    public function contains(string $text): bool
    {
        $normalized = mb_strtolower($text, 'UTF-8');

        foreach ($this->words as $word) {
            if ($this->wordFound($normalized, $word)) {
                return true;
            }
        }

        return false;
    }

    private function wordFound(string $haystack, string $needle): bool
    {
        if ($haystack === $needle) {
            return true;
        }

        $pos = mb_strpos($haystack, $needle, 0, 'UTF-8');
        while ($pos !== false) {
            $before = $pos > 0 ? mb_substr($haystack, $pos - 1, 1, 'UTF-8') : ' ';
            $after  = mb_substr($haystack, $pos + mb_strlen($needle, 'UTF-8'), 1, 'UTF-8');

            $isWordStart = !$this->isAlphanumeric($before);
            $isWordEnd   = ($after === '' || !$this->isAlphanumeric($after));

            if ($isWordStart && $isWordEnd) {
                return true;
            }

            $pos = mb_strpos($haystack, $needle, $pos + 1, 'UTF-8');
        }

        return false;
    }

    private function isAlphanumeric(string $char): bool
    {
        return $char !== '' && preg_match('/[\p{L}\p{N}]/u', $char) === 1;
    }

    private function buildWordList(): array
    {
        return [
            // Turkce (UTF-8 + ASCII transliterasyon)
            'amk', 'amq', 'oç', 'oç', 'piç', 'pic', 'göt', 'got',
            'sik', 'sike', 'sikik', 'sikeyim', 'siktir', 'sikim',
            'orospu', 'kahpe', 'kaltak',
            'ibne', 'bok', 'boktan',
            'amcık', 'amcik', 'götveren', 'gtveren', 'puşt', 'pust', 'pezevenk', 'gavat',
            'yarrak', 'yarak', 'taşak', 'tasak',
            'fahişe', 'fahise', 'sürtük', 'surtuk', 'şerefsiz', 'serefsiz',
            'gerizekalı', 'gerizekali', 'aptal', 'salak',
            'pislik', 'soysuz', 'namussuz',
            // English
            'fuck', 'fucker', 'fucking', 'fucked', 'fck',
            'shit', 'shite', 'bullshit',
            'bitch', 'bastard', 'asshole', 'ass',
            'cunt', 'cock', 'dick', 'pussy',
            'whore', 'slut', 'nigger', 'nigga',
            'faggot', 'fag', 'retard', 'idiot', 'moron',
            'dumbass', 'jackass', 'dipshit',
            'piss', 'prick', 'wank', 'wanker',
        ];
    }
}
