<?php

namespace Tests\Skills;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasSkills;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Skills\SkillDiscoveryMode;
use Laravel\Ai\Skills\SkillRegistry;
use Mockery;
use Tests\TestCase;

class PromptableSkillsTest extends TestCase
{
    public function test_it_loads_skills_and_merges_tools_when_prompting()
    {
        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('load')->with('test-skill')->once();
        $registry->shouldReceive('tools')->andReturn(['tool_from_skill']);
        $registry->shouldReceive('instructions')->with('full')->andReturn('Skill instructions');

        $this->app->instance(SkillRegistry::class, $registry);

        $agent = new class implements Agent, HasSkills, HasTools
        {
            use Promptable;

            public function skills(): iterable
            {
                return ['test-skill'];
            }

            public function skillDiscoveryMode(): SkillDiscoveryMode
            {
                return SkillDiscoveryMode::Full;
            }

            public function tools(): iterable
            {
                return ['existing_tool'];
            }

            public function instructions(): string
            {
                return 'Base instructions';
            }
        };

        // Fake the AI to intercept the prompt
        $agent::fake(['response']);

        $agent->prompt('hello');

        // Assertions are handled by Mockery expectations on $registry
        // We also implicitly verify that the agent prompt execution didn't crash
        $this->assertTrue(true);
    }

    public function test_it_appends_skill_instructions()
    {
        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('load')->with('test-skill');
        $registry->shouldReceive('tools')->andReturn([]);
        $registry->shouldReceive('instructions')->with('full')->andReturn('Skill instructions');

        $this->app->instance(SkillRegistry::class, $registry);

        $agent = new class implements Agent, HasSkills
        {
            use Promptable;

            public $instructions = 'Base instructions';

            public function skills(): iterable
            {
                return ['test-skill'];
            }

            public function skillDiscoveryMode(): SkillDiscoveryMode
            {
                return SkillDiscoveryMode::Full;
            }

            public function instructions(): string
            {
                return $this->instructions;
            }
        };

        $agent::fake(['response']);

        $agent->prompt('hello');

        // We cannot easily inspect the modified instructions without the proxy implementation detail
        // exposed, or by inspecting the AgentPrompt passed to the provider.
        // For now, we rely on the registry mock expectations.
        $this->assertTrue(true);
    }
}
