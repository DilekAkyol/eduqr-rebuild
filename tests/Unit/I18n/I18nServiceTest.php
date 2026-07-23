<?php
declare(strict_types=1);

namespace EduQR\Tests\Unit\I18n;

use PHPUnit\Framework\TestCase;
use EduQR\I18n\I18nService;

final class I18nServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clear cookies and GET params to keep testing isolated
        $_GET = [];
        $_COOKIE = [];
        I18nService::init();
    }

    public function test_get_and_set_locale(): void
    {
        I18nService::setLocale('en');
        $this->assertEquals('en', I18nService::getLocale());

        I18nService::setLocale('tr');
        $this->assertEquals('tr', I18nService::getLocale());
    }

    public function test_translation_with_fallback(): void
    {
        I18nService::setLocale('en');
        I18nService::init();

        // Translate existing key
        $this->assertEquals('Unauthorized access.', I18nService::translate('error.unauthorized'));

        // Translate non-existing key (should fallback to key itself)
        $this->assertEquals('some.nonexistent.key.name', I18nService::translate('some.nonexistent.key.name'));
    }

    public function test_translation_with_placeholders(): void
    {
        I18nService::setLocale('en');
        I18nService::init();

        // Translate key with placeholder replacement
        $result = I18nService::translate('error.gemini_api_error', ['message' => 'Quota Exceeded']);
        $this->assertEquals('Gemini API Error: Quota Exceeded', $result);
    }
}
