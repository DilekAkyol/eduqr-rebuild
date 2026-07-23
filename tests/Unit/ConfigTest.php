<?php
declare(strict_types=1);

namespace EduQR\Tests\Unit;

use PHPUnit\Framework\TestCase;
use EduQR\Config;

final class ConfigTest extends TestCase
{
    public function test_config_get_returns_correct_value_or_default(): void
    {
        $this->assertEquals('eduqr_rebuild_test', Config::get('DB_NAME'));
        $this->assertEquals('default_val', Config::get('NOT_EXISTING_RANDOM_KEY', 'default_val'));
    }

    public function test_config_require_throws_exception_on_missing_key(): void
    {
        $this->expectException(\RuntimeException::class);
        Config::require('NOT_EXISTING_RANDOM_KEY_AT_ALL');
    }

    public function test_config_bool_resolves_correct_boolean_values(): void
    {
        // Set environment variables dynamically to check Config::bool parsing
        putenv('TEST_KEY_TRUE_1=true');
        putenv('TEST_KEY_TRUE_2=1');
        putenv('TEST_KEY_FALSE_1=false');
        putenv('TEST_KEY_FALSE_2=0');

        $this->assertTrue(Config::bool('TEST_KEY_TRUE_1'));
        $this->assertTrue(Config::bool('TEST_KEY_TRUE_2'));
        $this->assertFalse(Config::bool('TEST_KEY_FALSE_1'));
        $this->assertFalse(Config::bool('TEST_KEY_FALSE_2'));
        $this->assertTrue(Config::bool('TEST_KEY_NON_EXISTENT', true));
    }
}
