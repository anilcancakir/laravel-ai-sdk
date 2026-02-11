<?php

namespace Tests\Skills;

use Laravel\Ai\Ai;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasSkills;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Skills\SkillAgentDecorator;
use Laravel\Ai\Skills\SkillDiscoveryMode;
use Laravel\Ai\Skills\SkillRegistry;
use Mockery;
use Tests\TestCase;

class PromptableSkillsTest extends TestCase
{
    public function test_it_wraps_agent_in_decorator_and_merges_tools_when_prompting()
    {
        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('load')->with('test-skill')->once();
        $registry->shouldReceive('tools')->andReturn(['tool_from_skill']);
        $registry->shouldReceive('instructions')->with('full')->andReturn('Skill instructions');
        $registry->shouldReceive('getLoaded')->andReturn(['test-skill' => 'dummy']);

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

            public function instructions(): \Illuminate\Support\Stringable|string
            {
                return 'Base instructions';
            }
        };

        // Fake the AI to intercept the prompt
        $agent::fake(['response']);

        $agent->prompt('hello');

        $agent::assertPrompted(function ($prompt) {
            // The prompt agent MUST be the decorator for this to work
            return $prompt->agent instanceof SkillAgentDecorator;
        });
    }

    public function test_it_appends_skill_instructions_via_decorator()
    {
        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('load')->with('test-skill');
        $registry->shouldReceive('tools')->andReturn([]);
        $registry->shouldReceive('instructions')->with('full')->andReturn('Skill instructions');
        $registry->shouldReceive('getLoaded')->andReturn(['test-skill' => 'dummy']);

        $this->app->instance(SkillRegistry::class, $registry);

        $agent = new class implements Agent, HasSkills
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

            public function instructions(): \Illuminate\Support\Stringable|string
            {
                return 'Base instructions';
            }
        };

        $agent::fake(['response']);

        $agent->prompt('hello');

        $agent::assertPrompted(function ($prompt) {
            return $prompt->agent instanceof SkillAgentDecorator;
        });
    }
}
