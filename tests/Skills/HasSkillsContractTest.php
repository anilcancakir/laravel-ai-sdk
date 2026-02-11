<?php

namespace Tests\Skills;

use Laravel\Ai\Contracts\HasSkills;
use Laravel\Ai\Skills\SkillDiscoveryMode;
use PHPUnit\Framework\TestCase;

class HasSkillsContractTest extends TestCase
{
    public function test_contract_can_be_implemented(): void
    {
        $implementation = new class implements HasSkills
        {
            public function skills(): iterable
            {
                return [];
            }

            public function skillDiscoveryMode(): SkillDiscoveryMode
            {
                return SkillDiscoveryMode::Lite;
            }
        };

        $this->assertInstanceOf(HasSkills::class, $implementation);
        $this->assertIsIterable($implementation->skills());
        $this->assertEquals(SkillDiscoveryMode::Lite, $implementation->skillDiscoveryMode());
    }
}
