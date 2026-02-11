<?php

namespace Tests\Skills;

use Laravel\Ai\Skillable;
use Laravel\Ai\Skills\Skill;
use Laravel\Ai\Skills\SkillMode;
use Laravel\Ai\Skills\SkillRegistry;
use Mockery;
use Tests\TestCase;

class SkillableTest extends TestCase
{
    public function test_it_can_resolve_skills_from_array()
    {
        $skillable = new class
        {
            use Skillable;

            public function skills(): iterable
            {
                return [
                    'coach',
                    '/path/to/skill',
                    'writer' => SkillMode::Full,
                ];
            }
        };

        $registry = Mockery::mock(SkillRegistry::class);
        $this->app->instance(SkillRegistry::class, $registry);

        $registry->shouldReceive('load')->with('coach')->once()->andReturn(new Skill('coach', 'desc', 'instr'));
        $registry->shouldReceive('load')->with('/path/to/skill')->once()->andReturn(new Skill('path-skill', 'desc', 'instr'));
        $registry->shouldReceive('load')->with('writer')->once()->andReturn(new Skill('writer', 'desc', 'instr'));
        $registry->shouldReceive('tools')->once()->andReturn([]);

        // Trigger lazy loading
        $skillable->skillTools();
    }

    public function test_it_returns_skill_tools()
    {
        $skillable = new class
        {
            use Skillable;

            public function skills(): iterable
            {
                return ['coach'];
            }
        };

        $registry = Mockery::mock(SkillRegistry::class);
        $this->app->instance(SkillRegistry::class, $registry);

        $registry->shouldReceive('load')->with('coach')->once();
        $registry->shouldReceive('tools')->once()->andReturn(['tool1', 'tool2']);

        $this->assertEquals(['tool1', 'tool2'], $skillable->skillTools());
    }

    public function test_it_returns_skill_instructions()
    {
        $skillable = new class
        {
            use Skillable;

            public function skills(): iterable
            {
                return ['coach'];
            }
        };

        $registry = Mockery::mock(SkillRegistry::class);
        $this->app->instance(SkillRegistry::class, $registry);

        $registry->shouldReceive('load')->with('coach')->once();

        // Default mode is Lite if not specified, but usually we pass it
        $registry->shouldReceive('instructions')->with('full')->once()->andReturn('full instructions');
        $registry->shouldReceive('instructions')->with('lite')->once()->andReturn('lite instructions');

        $this->assertEquals('full instructions', $skillable->skillInstructions(SkillMode::Full));
        $this->assertEquals('lite instructions', $skillable->skillInstructions(SkillMode::Lite));
    }
}
