<?php
declare(strict_types=1);

namespace EduQR\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use EduQR\Support\ShortCode;

final class ShortCodeTest extends TestCase
{
    public function test_short_code_has_correct_length(): void
    {
        $code = ShortCode::generate();
        $this->assertEquals(6, strlen($code));

        $code8 = ShortCode::generate(8);
        $this->assertEquals(8, strlen($code8));
    }

    public function test_short_code_only_contains_allowed_characters(): void
    {
        $allowed = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = ShortCode::generate(50); // longer to verify more characters

        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            $this->assertStringContainsString($char, $allowed);
            // Verify exclusion of ambiguous characters
            $this->assertNotEquals('0', $char);
            $this->assertNotEquals('1', $char);
            $this->assertNotEquals('I', $char);
            $this->assertNotEquals('O', $char);
        }
    }

    public function test_multiple_generated_codes_are_unique(): void
    {
        $codes = [];
        for ($i = 0; $i < 100; $i++) {
            $codes[] = ShortCode::generate();
        }

        $uniqueCodes = array_unique($codes);
        $this->assertEquals(count($codes), count($uniqueCodes));
    }
}
