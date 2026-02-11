<?php

namespace Tests\Skills;

use Laravel\Ai\Skills\SkillDiscoveryMode;
use PHPUnit\Framework\TestCase;

class SkillDiscoveryModeTest extends TestCase
{
    public function test_enum_has_expected_cases(): void
    {
        $this->assertEquals('none', SkillDiscoveryMode::None->value);
        $this->assertEquals('lite', SkillDiscoveryMode::Lite->value);
        $this->assertEquals('full', SkillDiscoveryMode::Full->value);
    }

    public function test_enum_cases_count(): void
    {
        $this->assertCount(3, SkillDiscoveryMode::cases());
    }
}
