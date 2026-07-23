<?php
declare(strict_types=1);

namespace EduQR\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use EduQR\Services\ProfanityFilter;

final class ProfanityFilterTest extends TestCase
{
    private ?ProfanityFilter $filter = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new ProfanityFilter();
    }

    public function test_clean_text_passes_validation(): void
    {
        $this->assertFalse($this->filter->contains('Ahmet'));
        $this->assertFalse($this->filter->contains('John Doe'));
        $this->assertFalse($this->filter->contains('class')); // class has "ass" inside, but should pass word boundary check
        $this->assertFalse($this->filter->contains('amigo')); // has "am" or "amk" partial matching but clean
    }

    public function test_profanity_is_detected(): void
    {
        $this->assertTrue($this->filter->contains('amk'));
        $this->assertTrue($this->filter->contains('fuck'));
        $this->assertTrue($this->filter->contains('Fucking User'));
        $this->assertTrue($this->filter->contains('Göt'));
        $this->assertTrue($this->filter->contains('aptal'));
    }

    public function test_profanity_boundary_matching(): void
    {
        $this->assertTrue($this->filter->contains('hey, fck!')); // boundary match with punctuation
        $this->assertTrue($this->filter->contains('ass'));
        $this->assertFalse($this->filter->contains('embassy')); // has "ass", boundary check should make it pass
    }
}
