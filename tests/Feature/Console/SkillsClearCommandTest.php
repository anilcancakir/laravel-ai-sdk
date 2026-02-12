<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Console\Commands\SkillsClearCommand;
use Tests\TestCase;

class SkillsClearCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Artisan::registerCommand($this->app->make(SkillsClearCommand::class));
    }

    public function test_clears_skill_cache(): void
    {
        $store = Cache::store(config('ai.skills.cache'));
        $store->put('ai_sdk_skills', ['cached-data'], 3600);

        $this->assertTrue($store->has('ai_sdk_skills'));

        $this->artisan('skill:clear')
            ->expectsOutputToContain('Skill cache cleared successfully.')
            ->assertExitCode(0);

        $this->assertFalse($store->has('ai_sdk_skills'));
    }
}
