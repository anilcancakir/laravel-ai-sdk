<?php

namespace Tests\Skills;

use Laravel\Ai\Skills\Skill;
use Tests\TestCase;

class SkillTest extends TestCase
{
    public function test_skill_slug_generation()
    {
        $skill = new Skill(
            name: 'My Coding Skill',
            description: 'Desc',
            instructions: 'Inst'
        );

        $this->assertSame('my-coding-skill', $skill->slug());
    }

    public function test_skill_has_tools()
    {
        $skillWithoutTools = new Skill(
            name: 'Skill',
            description: 'Desc',
            instructions: 'Inst'
        );

        $skillWithTools = new Skill(
            name: 'Skill',
            description: 'Desc',
            instructions: 'Inst',
            tools: ['weather']
        );

        $this->assertFalse($skillWithoutTools->hasTools());
        $this->assertTrue($skillWithTools->hasTools());
    }

    public function test_skill_matches_trigger()
    {
        $skill = new Skill(
            name: 'Skill',
            description: 'Desc',
            instructions: 'Inst',
            triggers: ['coding', 'developer']
        );

        $this->assertTrue($skill->matchesTrigger('I love coding'));
        $this->assertTrue($skill->matchesTrigger('developer tasks'));
        $this->assertTrue($skill->matchesTrigger('CODING'));
        $this->assertFalse($skill->matchesTrigger('testing'));
    }

    public function test_skill_is_immutable()
    {
        $this->assertTrue((new \ReflectionClass(Skill::class))->isReadOnly());
    }
}
