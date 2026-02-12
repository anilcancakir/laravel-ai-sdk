<?php

namespace Tests\Skills;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Laravel\Ai\Skillable;
use Laravel\Ai\Skills\SkillMode;
use Laravel\Ai\Skills\SkillRegistry;
use Mockery;
use Tests\TestCase;

class PromptableSkillsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_appends_skill_instructions_when_prompting()
    {
        $registry = Mockery::mock(SkillRegistry::class);
        $registry->shouldReceive('load')->with('test-skill', SkillMode::Full)->once();
        $registry->shouldReceive('instructions')->with(null)->andReturn('Skill instructions');

        $this->app->instance(SkillRegistry::class, $registry);

        $agent = new class implements Agent
        {
            use Promptable, Skillable;

            public function skills(): iterable
            {
                return ['test-skill' => SkillMode::Full];
            }

            public function instructions(): \Illuminate\Support\Stringable|string
            {
                return 'Base instructions';
            }
        };

        $agent::fake(['response']);

        $agent->prompt('hello');

        $agent::assertPrompted(function ($prompt) {
            return str_contains($prompt->instructions, 'Base instructions')
                && str_contains($prompt->instructions, 'Skill instructions');
        });
    }

    public function test_get_tools_returns_tools_when_agent_defines_them()
    {
        $toolA = new class {};
        $toolB = new class {};

        $agent = new class($toolA, $toolB) implements Agent
        {
            use Promptable;

            private object $toolA;

            private object $toolB;

            public function __construct(object $toolA, object $toolB)
            {
                $this->toolA = $toolA;
                $this->toolB = $toolB;
            }

            public function tools(): array
            {
                return [$this->toolA, $this->toolB];
            }

            public function instructions(): string
            {
                return 'Test instructions';
            }
        };

        $agent::fake(['response']);

        $agent->prompt('hello');

        $agent::assertPrompted(function ($prompt) use ($toolA, $toolB) {
            return $prompt->tools === [$toolA, $toolB];
        });
    }

    public function test_get_tools_returns_empty_array_when_agent_has_no_tools()
    {
        $agent = new class implements Agent
        {
            use Promptable;

            public function instructions(): string
            {
                return 'No tools agent';
            }
        };

        $agent::fake(['response']);

        $agent->prompt('hello');

        $agent::assertPrompted(function ($prompt) {
            return $prompt->tools === [];
        });
    }
}
